#!/usr/bin/env python3
# /// script
# requires-python = ">=3.11"
# dependencies = [
#   "requests",
#   "shapely",
# ]
# ///
"""
Generate a World Geography flashcard deck.

- Downloads Natural Earth country GeoJSON (once, cached locally)
- Downloads REST Countries metadata (once, cached locally)
- Renders one SVG per country: target highlighted, neighbours visible, ~25% padding
- Saves SVGs to public/maps/{cca2}.svg
- Inserts deck + cards into the local SQLite DB (database/database.sqlite)

Run with:
    uv run scripts/generate_geography_deck.py
"""

import json
import math
import os
from pathlib import Path

import requests
from shapely.geometry import shape, MultiPolygon, Polygon
from shapely.ops import unary_union

# ---------------------------------------------------------------------------
# Paths (relative to project root — script must be run from project root)
# ---------------------------------------------------------------------------
PROJECT_ROOT = Path(__file__).parent.parent
MAPS_DIR = PROJECT_ROOT / "public" / "maps"
CACHE_DIR = PROJECT_ROOT / "scripts" / ".cache"

GEOJSON_URL = "https://datahub.io/core/geo-countries/r/countries.geojson"
LAKES_URL = "https://raw.githubusercontent.com/nvkelso/natural-earth-vector/master/geojson/ne_10m_lakes.geojson"
RESTCOUNTRIES_URL = "https://restcountries.com/v3.1/all?fields=name,capital,capitalInfo,latlng,cca2,cca3,borders"

GEOJSON_CACHE = CACHE_DIR / "countries.geojson"
LAKES_CACHE = CACHE_DIR / "ne_10m_lakes.geojson"
RESTCOUNTRIES_CACHE = CACHE_DIR / "restcountries.json"

# Simplification tolerance in degrees
SIMPLIFY_TOLERANCE = 0.05

# ---------------------------------------------------------------------------
# SVG rendering settings
# ---------------------------------------------------------------------------
SVG_WIDTH = 800
SVG_HEIGHT = 500
PADDING_FRACTION = 0.30   # 30% padding around the target country's bounding box
NEIGHBOUR_DEPTH = 1       # how many border-hops to include as context

TARGET_FILL = "#4A90D9"
TARGET_STROKE = "#1a5fa8"
NEIGHBOUR_FILL = "#C8D8E8"
NEIGHBOUR_STROKE = "#8BAABB"
OCEAN_FILL = "#A8C8E8"      # ocean/sea
LAKE_FILL = "#A8C8E8"       # lakes — same as ocean so they blend naturally
LAND_FILL = "#E8E0D0"       # distant land
LAND_STROKE = "#B8B0A0"


# ---------------------------------------------------------------------------
# Helpers
# ---------------------------------------------------------------------------

def fetch_cached(url: str, cache_path: Path) -> dict | list:
    if cache_path.exists():
        print(f"  [cache] {cache_path.name}")
        with open(cache_path) as f:
            return json.load(f)
    print(f"  [fetch] {url}")
    r = requests.get(url, timeout=60)
    r.raise_for_status()
    data = r.json()
    cache_path.parent.mkdir(parents=True, exist_ok=True)
    with open(cache_path, "w") as f:
        json.dump(data, f)
    return data


def bbox(geom):
    """Return (minx, miny, maxx, maxy) for a shapely geometry."""
    b = geom.bounds  # (minx, miny, maxx, maxy)
    return b


def pad_bbox(minx, miny, maxx, maxy, fraction):
    dx = (maxx - minx) * fraction
    dy = (maxy - miny) * fraction
    return minx - dx, miny - dy, maxx + dx, maxy + dy


def make_projection(vp_minx, vp_miny, vp_maxx, vp_maxy, svg_w, svg_h):
    """Uniform-scale projection with letterboxing and Mercator Y warping.

    Scale is determined from geographic degrees so both axes use the same
    unit. Mercator Y warping is applied only to position, keeping shape correct.
    Returns (minlon, miny, maxy, scale, off_x, off_y).
    """
    geo_w = vp_maxx - vp_minx   # degrees longitude
    geo_h = vp_maxy - vp_miny   # degrees latitude (used only for scale)

    # At the centre latitude, 1 degree longitude = cos(lat) degrees of arc,
    # so we correct the horizontal extent to match the vertical.
    mid_lat = math.radians((vp_miny + vp_maxy) / 2)
    geo_w_corrected = geo_w * math.cos(mid_lat)  # effective width in lat-equivalent degrees

    scale = min(svg_w / geo_w_corrected, svg_h / geo_h)

    render_w = geo_w_corrected * scale
    render_h = geo_h * scale
    off_x = (svg_w - render_w) / 2
    off_y = (svg_h - render_h) / 2

    return (vp_minx, vp_miny, vp_maxy, scale, off_x, off_y, geo_w_corrected / geo_w)


def geo_to_svg(lon, lat, proj):
    minlon, miny, maxy, scale, off_x, off_y, lon_scale = proj
    x = off_x + (lon - minlon) * lon_scale * scale
    y = off_y + (maxy - lat) * scale
    return x, y


def polygon_to_path(poly, proj):
    parts = []
    for ring in [poly.exterior] + list(poly.interiors):
        coords = list(ring.coords)
        if not coords:
            continue
        pts = [geo_to_svg(lon, lat, proj) for lon, lat in coords]
        d = "M " + " L ".join(f"{x:.2f},{y:.2f}" for x, y in pts) + " Z"
        parts.append(d)
    return " ".join(parts)


def geometry_to_path(geom, proj):
    if isinstance(geom, Polygon):
        polys = [geom]
    elif isinstance(geom, MultiPolygon):
        polys = list(geom.geoms)
    else:
        return ""
    paths = [polygon_to_path(p, proj) for p in polys]
    return " ".join(p for p in paths if p)


def render_svg(target_geom, neighbour_geoms, all_geoms, all_lakes, vp, svg_w, svg_h) -> str:
    from shapely.geometry import box as shapely_box
    vp_minx, vp_miny, vp_maxx, vp_maxy = vp
    vp_box = shapely_box(vp_minx, vp_miny, vp_maxx, vp_maxy)
    proj = make_projection(vp_minx, vp_miny, vp_maxx, vp_maxy, svg_w, svg_h)

    lines = [
        f'<svg xmlns="http://www.w3.org/2000/svg" width="{svg_w}" height="{svg_h}" viewBox="0 0 {svg_w} {svg_h}">',
        f'<rect width="{svg_w}" height="{svg_h}" fill="{OCEAN_FILL}"/>',
    ]

    neighbour_ids = set(id(g) for g in neighbour_geoms)
    target_id = id(target_geom)

    # Draw only land that intersects the viewport, clipped to it
    for geom in all_geoms:
        if id(geom) == target_id or id(geom) in neighbour_ids:
            continue
        if not geom.intersects(vp_box):
            continue
        clipped = geom.intersection(vp_box)
        if clipped.is_empty:
            continue
        d = geometry_to_path(clipped, proj)
        if d:
            lines.append(f'<path d="{d}" fill="{LAND_FILL}" stroke="{LAND_STROKE}" stroke-width="0.5"/>')

    # Draw neighbours clipped to viewport
    for geom in neighbour_geoms:
        if not geom.intersects(vp_box):
            continue
        clipped = geom.intersection(vp_box)
        if clipped.is_empty:
            continue
        d = geometry_to_path(clipped, proj)
        if d:
            lines.append(f'<path d="{d}" fill="{NEIGHBOUR_FILL}" stroke="{NEIGHBOUR_STROKE}" stroke-width="0.8"/>')

    # Draw target clipped to viewport
    clipped_target = target_geom.intersection(vp_box)
    if not clipped_target.is_empty:
        d = geometry_to_path(clipped_target, proj)
        if d:
            lines.append(f'<path d="{d}" fill="{TARGET_FILL}" stroke="{TARGET_STROKE}" stroke-width="1.2"/>')

    # Draw lakes on top (covers holes in country polygons that show as ocean)
    for lake_geom in all_lakes:
        if not lake_geom.intersects(vp_box):
            continue
        clipped = lake_geom.intersection(vp_box)
        if clipped.is_empty:
            continue
        d = geometry_to_path(clipped, proj)
        if d:
            lines.append(f'<path d="{d}" fill="{LAKE_FILL}" stroke="none"/>')

    lines.append('</svg>')
    return "\n".join(lines)


# ---------------------------------------------------------------------------
# Main
# ---------------------------------------------------------------------------

def main():
    MAPS_DIR.mkdir(parents=True, exist_ok=True)

    print("Fetching data...")
    geojson = fetch_cached(GEOJSON_URL, GEOJSON_CACHE)
    lakes_geojson = fetch_cached(LAKES_URL, LAKES_CACHE)
    rest_countries = fetch_cached(RESTCOUNTRIES_URL, RESTCOUNTRIES_CACHE)

    # Build list of lake geometries (simplified)
    all_lakes_list = []
    for feat in lakes_geojson["features"]:
        try:
            g = shape(feat["geometry"]).simplify(SIMPLIFY_TOLERANCE, preserve_topology=True)
            if not g.is_empty:
                all_lakes_list.append(g)
        except Exception:
            pass

    # Build lookup: ISO A2 -> rest countries entry
    rest_by_cca2 = {c["cca2"]: c for c in rest_countries if c.get("cca2")}
    rest_by_cca3 = {c["cca3"]: c for c in rest_countries if c.get("cca3")}

    # Build lookup: ISO A3 -> shapely geometry (Natural Earth uses ISO_A3)
    features = geojson["features"]
    geom_by_iso3 = {}
    geom_by_iso2 = {}
    all_geoms_list = []

    geom_by_name = {}
    print("  Loading and simplifying geometries...")
    for feat in features:
        props = feat["properties"]
        iso3 = props.get("ISO3166-1-Alpha-3") or props.get("ISO_A3") or props.get("iso_a3") or ""
        iso2 = props.get("ISO3166-1-Alpha-2") or props.get("ISO_A2") or props.get("iso_a2") or ""
        name_geojson = props.get("name") or props.get("NAME") or props.get("ADMIN") or ""
        geom = shape(feat["geometry"]).simplify(SIMPLIFY_TOLERANCE, preserve_topology=True)
        all_geoms_list.append(geom)
        if iso3 and iso3 not in ("-99", "-1"):
            geom_by_iso3[iso3.upper()] = geom
        if iso2 and iso2 not in ("-99", "-1"):
            geom_by_iso2[iso2.upper()] = geom
        if name_geojson:
            geom_by_name[name_geojson.lower()] = geom

    # Build deck cards
    cards = []
    skipped = []

    for cca2, rc in rest_by_cca2.items():
        name = rc["name"]["common"]
        capital_list = rc.get("capital", [])
        capital = capital_list[0] if capital_list else None
        cca3 = rc.get("cca3", "")
        borders_cca3 = rc.get("borders", [])

        # Find geometry (fall back to name match for countries with -99 ISO codes)
        target_geom = (
            geom_by_iso2.get(cca2.upper())
            or geom_by_iso3.get(cca3.upper())
            or geom_by_name.get(name.lower())
        )
        if target_geom is None:
            skipped.append(f"{name} ({cca2}/{cca3}) — no geometry")
            continue

        # Find neighbour geometries
        neighbour_geoms = []
        for b_cca3 in borders_cca3:
            g = geom_by_iso3.get(b_cca3.upper())
            if g:
                neighbour_geoms.append(g)

        # Viewport: pad target bbox
        minx, miny, maxx, maxy = bbox(target_geom)

        # For small island nations, ensure a minimum viewport size (2 degrees)
        min_span = 2.0
        if (maxx - minx) < min_span:
            cx = (minx + maxx) / 2
            minx, maxx = cx - min_span / 2, cx + min_span / 2
        if (maxy - miny) < min_span:
            cy = (miny + maxy) / 2
            miny, maxy = cy - min_span / 2, cy + min_span / 2

        vp = pad_bbox(minx, miny, maxx, maxy, PADDING_FRACTION)
        # Clamp to valid lat/lon range
        vp = (
            max(vp[0], -180), max(vp[1], -90),
            min(vp[2], 180),  min(vp[3], 90),
        )

        svg = render_svg(target_geom, neighbour_geoms, all_geoms_list, all_lakes_list, vp, SVG_WIDTH, SVG_HEIGHT)

        svg_filename = f"{cca2.lower()}.svg"
        svg_path = MAPS_DIR / svg_filename
        svg_path.write_text(svg)

        back = name
        if capital:
            back = f"{name}\n{capital}"

        cards.append({
            "cca2": cca2.lower(),
            "front_content": "",
            "back_content": back,
            "front_image_url": f"/maps/{svg_filename}",
        })

    print(f"\nGenerated {len(cards)} SVGs")
    if skipped:
        print(f"Skipped {len(skipped)}:")
        for s in skipped:
            print(f"  - {s}")

    # ---------------------------------------------------------------------------
    # Write deck data to JSON
    # ---------------------------------------------------------------------------
    output = {
        "deck": {
            "name": "World Geography",
            "description": "Identify countries and their capitals from their map shape and surrounding geography.",
            "is_active": True,
            "new_cards_per_day": 10,
        },
        "cards": cards,
    }
    json_path = PROJECT_ROOT / "scripts" / "geography_deck.json"
    json_path.write_text(json.dumps(output, indent=2, ensure_ascii=False))
    print(f"\nWrote {len(cards)} cards to {json_path}")
    print("\nTo import on the server, run:")
    print("  php artisan db:seed --class=GeographyDeckSeeder")


if __name__ == "__main__":
    main()
