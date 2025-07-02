# What Deck API Documentation

This document provides comprehensive information about the Magic: The Gathering card collection management API.

## Table of Contents
- [Overview](#overview)
- [Authentication](#authentication)
- [Data Models](#data-models)
- [API Endpoints](#api-endpoints)
- [Import Functionality](#import-functionality)
- [Error Handling](#error-handling)
- [OpenAPI Documentation](#openapi-documentation)

## Overview

The What Deck API is a comprehensive solution for managing Magic: The Gathering card collections. It allows users to:

- **Manage Cards**: Store MTG card templates with detailed metadata
- **Track Collections**: Organize physical cards into collections (like storage boxes)
- **Build Decks**: Create constructed decks from your card instances
- **Import Collections**: Import existing collections from popular platforms like Moxfield
- **Individual Card Tracking**: Each physical card is tracked separately with condition, foil status, and more

## Authentication

Currently, the API uses Laravel Sanctum for authentication (routes are set up but not enforced in current version).

## Data Models

### Card
Represents a Magic: The Gathering card template/definition.

**Fields:**
- `id`: Unique identifier
- `title`: Card name
- `image_url`: URL to card image
- `description`: Card description/rules text
- `cost`: Mana cost
- `type`: Card type (Creature, Instant, etc.)
- `subtype`: Card subtype (Human, Warrior, etc.)
- `power`: Creature power (nullable)
- `toughness`: Creature toughness (nullable)
- `edition`: Set/edition code (e.g., "dft", "afr")
- `collector_number`: Collector number within set
- `created_at`: Creation timestamp
- `updated_at`: Last update timestamp

### CardInstance
Represents a physical copy of a card in someone's collection.

**Fields:**
- `id`: Unique identifier
- `card_id`: Reference to Card template
- `collection_id`: Which collection owns this instance
- `deck_id`: Which deck contains this instance (nullable)
- `condition`: Physical condition (mint, near_mint, lightly_played, etc.)
- `foil`: Whether the card is foil
- `language`: Card language (default: English)
- `tags`: User-defined tags (JSON array)
- `purchase_price`: What was paid for this card
- `alter`: Whether the card is altered
- `proxy`: Whether this is a proxy card
- `created_at`: Creation timestamp
- `updated_at`: Last update timestamp

### Collection
Represents a storage container/collection (like a binder or box).

**Fields:**
- `id`: Unique identifier
- `user_id`: Owner of the collection
- `name`: Collection name
- `description`: Optional description
- `created_at`: Creation timestamp
- `updated_at`: Last update timestamp

### Deck
Represents a constructed MTG deck.

**Fields:**
- `id`: Unique identifier
- `user_id`: Owner of the deck
- `name`: Deck name
- `description`: Optional description
- `format`: MTG format (Standard, Modern, etc.)
- `created_at`: Creation timestamp
- `updated_at`: Last update timestamp

## API Endpoints

### Cards
- `GET /api/cards` - List all cards (paginated)
- `POST /api/cards` - Create a new card
- `GET /api/cards/{id}` - Get specific card
- `PUT /api/cards/{id}` - Update card
- `DELETE /api/cards/{id}` - Delete card (if no instances exist)

**Query Parameters:**
- `type`: Filter by card type
- `page`: Page number for pagination
- `per_page`: Items per page (default: 15)

### Card Instances
- `GET /api/card-instances` - List all card instances (paginated)
- `POST /api/card-instances` - Create a new card instance
- `GET /api/card-instances/{id}` - Get specific card instance
- `PUT /api/card-instances/{id}` - Update card instance
- `DELETE /api/card-instances/{id}` - Delete card instance
- `PUT /api/card-instances/{id}/move-to-deck/{deckId}` - Move instance to deck
- `PUT /api/card-instances/{id}/remove-from-deck` - Remove instance from deck

**Query Parameters:**
- `collection_id`: Filter by collection
- `deck_id`: Filter by deck
- `available`: Filter available instances (not in any deck)
- `card_id`: Filter by card template
- `condition`: Filter by condition
- `foil`: Filter foil/non-foil cards

### Collections
- `GET /api/collections` - List all collections (paginated)
- `POST /api/collections` - Create a new collection
- `GET /api/collections/{id}` - Get specific collection
- `PUT /api/collections/{id}` - Update collection
- `DELETE /api/collections/{id}` - Delete collection (if empty)
- `GET /api/collections/{id}/card-instances` - Get card instances in collection

### Decks
- `GET /api/decks` - List all decks (paginated)
- `POST /api/decks` - Create a new deck
- `GET /api/decks/{id}` - Get specific deck
- `PUT /api/decks/{id}` - Update deck
- `DELETE /api/decks/{id}` - Delete deck
- `GET /api/decks/{id}/card-instances` - Get card instances in deck
- `POST /api/decks/{id}/add-card-instance/{instanceId}` - Add card instance to deck
- `DELETE /api/decks/{id}/remove-card-instance/{instanceId}` - Remove card instance from deck

**Query Parameters:**
- `format`: Filter by deck format
- `page`: Page number for pagination
- `per_page`: Items per page (default: 15)

## Import Functionality

The API supports importing card collections from external platforms. Currently supported:

### Moxfield Import

Import your entire collection from a Moxfield CSV export.

#### Endpoint
`POST /api/collections/{id}/import/moxfield`

#### Request
- **Method**: POST
- **Content-Type**: multipart/form-data
- **Body**:
  - `csv_file`: The Moxfield CSV export file (required)
  - `create_missing_cards`: Whether to create new card records (optional, default: true)

#### Response
```json
{
  "message": "Import completed successfully",
  "stats": {
    "processed": 150,
    "cards_created": 75,
    "cards_found": 75,
    "instances_created": 150,
    "errors": []
  }
}
```

#### CSV Format Expected
The Moxfield CSV should contain these columns:
- **Required**: Count, Name, Edition, Condition, Language, Foil, Collector Number
- **Optional**: Tags, Purchase Price, Alter, Proxy, Tradelist Count, Last Modified

#### How It Works
1. **Card Deduplication**: Cards are identified by name + edition + collector number
2. **Instance Creation**: Creates individual CardInstance records for each count
3. **Condition Mapping**: Maps Moxfield conditions to internal format
4. **Error Handling**: Continues processing even if some rows fail
5. **Transaction Safety**: All changes are wrapped in a database transaction

#### Supported Import Formats

Get list of supported formats:
`GET /api/import/formats`

```json
{
  "formats": [
    {
      "name": "Moxfield",
      "description": "Import from Moxfield CSV export",
      "endpoint": "/api/collections/{id}/import/moxfield",
      "required_columns": ["Count", "Name", "Edition", "Condition", "Language", "Foil", "Collector Number"],
      "optional_columns": ["Tags", "Purchase Price", "Alter", "Proxy"],
      "file_requirements": {
        "format": "CSV",
        "max_size": "10MB",
        "encoding": "UTF-8"
      }
    }
  ]
}
```

## Error Handling

The API uses standard HTTP status codes and provides detailed error messages:

### Status Codes
- `200`: Success
- `201`: Created
- `206`: Partial Content (import completed with some errors)
- `400`: Bad Request
- `404`: Not Found
- `422`: Validation Error
- `500`: Server Error

### Error Response Format
```json
{
  "message": "Error description",
  "errors": {
    "field_name": ["Specific error message"]
  }
}
```

### Business Rules
- Cards cannot be deleted if they have existing instances
- Collections cannot be deleted if they contain card instances
- Card instances cannot be assigned to decks belonging to different users
- Card instances cannot be assigned to multiple decks simultaneously

## OpenAPI Documentation

Interactive API documentation is available at:
- **Swagger UI**: `http://localhost:8000/api/documentation`
- **JSON Spec**: `http://localhost:8000/docs`

The OpenAPI specification includes:
- Complete endpoint documentation
- Request/response schemas
- Parameter descriptions
- Example requests and responses
- Error response formats

### Resource Schemas
All API responses use consistent resource formatting:
- **CardResource**: Complete card data with relationships
- **CardInstanceResource**: Individual card instance with computed properties
- **CollectionResource**: Collection with statistics
- **DeckResource**: Deck with card composition analysis
- **UserResource**: User data with collection/deck summaries

### Pagination
List endpoints return paginated responses:
```json
{
  "data": [...],
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 15,
    "total": 67
  },
  "links": {
    "first": "https://api.example.com/cards?page=1",
    "last": "https://api.example.com/cards?page=5",
    "prev": null,
    "next": "https://api.example.com/cards?page=2"
  }
}
```

## Example Workflow: Importing a Moxfield Collection

1. **Export from Moxfield**: Go to your Moxfield collection and export as CSV
2. **Create Collection**: `POST /api/collections` with name "My Moxfield Import"
3. **Import CSV**: `POST /api/collections/{id}/import/moxfield` with the CSV file
4. **Review Results**: Check the stats in the response for any errors
5. **Organize Cards**: Use the API to move cards into decks as needed

The import process will:
- Create new Card records for unknown cards
- Create CardInstance records for each physical card
- Preserve all metadata (condition, foil status, tags, prices)
- Handle duplicates intelligently
- Provide detailed statistics on the import results

This makes it easy to migrate your existing collection data while maintaining full compatibility with the What Deck API structure. 