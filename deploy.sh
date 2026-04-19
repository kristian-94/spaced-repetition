#!/usr/bin/env bash
set -e

cd "$(dirname "$0")"

# Backup the database before doing anything
BACKUP_DIR="$HOME/backups/spaced-repetition"
mkdir -p "$BACKUP_DIR"
DB_SRC="/var/lib/docker/volumes/spaced-repetition_db-data/_data/database.sqlite"
BACKUP_FILE="$BACKUP_DIR/database-$(date +%Y%m%d-%H%M%S).sqlite"

if sudo test -f "$DB_SRC"; then
    echo "==> Backing up database to $BACKUP_FILE ..."
    sudo cp "$DB_SRC" "$BACKUP_FILE"
    echo "    Done. Keeping last 10 backups."
    # Keep only the 10 most recent backups
    ls -t "$BACKUP_DIR"/database-*.sqlite | tail -n +11 | xargs -r rm --
else
    echo "==> No database found at $DB_SRC, skipping backup."
fi

echo "==> Pulling latest code..."
git pull

echo "==> Rebuilding Docker image..."
docker compose build

echo "==> Restarting containers..."
docker compose up -d --remove-orphans

echo ""
echo "NOTE: To run tests use: docker compose exec app php artisan test --env=testing"
echo "      NEVER run 'php artisan test' without --env=testing in this container."
echo ""
echo "==> Done."
