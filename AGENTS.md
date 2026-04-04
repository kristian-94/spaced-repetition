# Spaced Repetition API

Base URL: https://anki.ringertech.org/api
Auth: Bearer token (generate in Settings at /settings)

## Authentication

All API requests require a Bearer token in the Authorization header:

```
Authorization: Bearer {your-token}
```

Generate tokens in the app at /settings.

---

## Decks

### List decks (to find deck IDs)
```
GET /api/decks
```

Response:
```json
{
  "data": [
    {
      "id": 1,
      "name": "French Vocabulary",
      "description": "...",
      "is_active": true,
      "color": "#3b82f6",
      "cards_count": 42,
      "created_at": "...",
      "updated_at": "..."
    }
  ]
}
```

### Create a deck
```
POST /api/decks
Content-Type: application/json

{
  "name": "French Vocabulary",
  "description": "Common French words and phrases"
}
```

---

## Cards

### Add cards to a deck

```
POST /api/decks/{deck_id}/cards
Content-Type: application/json
```

**Single card:**
```json
{"front": "Bonjour", "back": "Hello"}
```

Or using explicit field names:
```json
{"front_content": "Bonjour", "back_content": "Hello"}
```

**Batch (array of cards):**
```json
[
  {"front": "Bonjour", "back": "Hello"},
  {"front": "Merci", "back": "Thank you"},
  {"front": "Au revoir", "back": "Goodbye"}
]
```

**With base64 image:**
```json
{
  "front": "What does this show?",
  "back": "A cat",
  "front_image_base64": "data:image/jpeg;base64,/9j/4AAQ..."
}
```

Response:
```json
{
  "data": [...cards],
  "count": 3
}
```

### List cards in a deck
```
GET /api/decks/{deck_id}/cards
```

### Update a card
```
PUT /api/cards/{card_id}
Content-Type: application/json

{
  "front_content": "Updated question",
  "back_content": "Updated answer"
}
```

### Delete a card
```
DELETE /api/cards/{card_id}
```

### Toggle card suspension
```
POST /api/cards/{card_id}/suspend
```

---

## Typical Workflow for Claude

1. Get list of decks to find the right deck ID:
   ```
   GET /api/decks
   ```

2. Add cards to the deck:
   ```
   POST /api/decks/{id}/cards
   [{"front": "...", "back": "..."}, ...]
   ```

Cards are immediately due for review once created.
