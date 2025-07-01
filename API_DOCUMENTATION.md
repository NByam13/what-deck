# Magic: The Gathering Collection API

## Overview

This Laravel API manages Magic: The Gathering card collections with the ability to:
- Track card templates and individual card instances
- Organize cards into collections (storage boxes)
- Build decks using specific card instances
- Know which deck each card instance belongs to

## Data Architecture

### Core Models

1. **Card** - Card templates (e.g., "Lightning Bolt")
2. **CardInstance** - Individual physical copies of cards
3. **Collection** - Storage containers (like different boxes)
4. **Deck** - Constructed decks using specific card instances
5. **User** - Card owners

### Key Relationships

- A User has many Collections and Decks
- A Collection belongs to a User and has many CardInstances
- A Deck belongs to a User and has many CardInstances
- A CardInstance belongs to a Card, Collection, and optionally a Deck
- A Card has many CardInstances

## API Endpoints

### Cards (Templates)

```
GET    /api/cards                    # List all cards with filters
POST   /api/cards                    # Create a new card template
GET    /api/cards/{id}               # Show specific card with instances
PUT    /api/cards/{id}               # Update card template
DELETE /api/cards/{id}               # Delete card (if no instances exist)
```

**Filters:**
- `?type=Creature` - Filter by card type
- `?subtype=Human` - Filter by subtype
- `?search=Lightning` - Search by title

### Collections (Storage)

```
GET    /api/collections                           # List all collections
POST   /api/collections                           # Create a new collection
GET    /api/collections/{id}                      # Show collection details
PUT    /api/collections/{id}                      # Update collection
DELETE /api/collections/{id}                      # Delete empty collection
GET    /api/collections/{id}/card-instances       # List cards in collection
```

### Decks

```
GET    /api/decks                                 # List all decks
POST   /api/decks                                 # Create a new deck
GET    /api/decks/{id}                            # Show deck with card counts
PUT    /api/decks/{id}                            # Update deck
DELETE /api/decks/{id}                            # Delete deck (frees card instances)
GET    /api/decks/{id}/card-instances             # List cards in deck
POST   /api/decks/{id}/add-card-instance/{cardInstanceId}    # Add card to deck
DELETE /api/decks/{id}/remove-card-instance/{cardInstanceId} # Remove card from deck
```

### Card Instances (Physical Cards)

```
GET    /api/card-instances                        # List card instances with filters
POST   /api/card-instances                        # Create new card instance
GET    /api/card-instances/{id}                   # Show instance details
PUT    /api/card-instances/{id}                   # Update condition/foil status
DELETE /api/card-instances/{id}                   # Delete card instance
PUT    /api/card-instances/{id}/move-to-deck/{deckId}       # Move to specific deck
PUT    /api/card-instances/{id}/remove-from-deck            # Remove from current deck
```

**Filters:**
- `?collection_id=1` - Filter by collection
- `?deck_id=1` - Filter by deck
- `?available=true` - Only unassigned instances

## Example Usage Workflow

### 1. Create a Card Template

```json
POST /api/cards
{
  "title": "Lightning Bolt",
  "description": "Lightning Bolt deals 3 damage to any target.",
  "cost": "R",
  "type": "Instant",
  "subtype": "Lightning"
}
```

### 2. Create a Collection

```json
POST /api/collections
{
  "user_id": 1,
  "name": "My Main Collection",
  "description": "My primary card storage"
}
```

### 3. Add Card Instances to Collection

```json
POST /api/card-instances
{
  "card_id": 1,
  "collection_id": 1,
  "condition": "near_mint",
  "foil": false
}
```

### 4. Create a Deck

```json
POST /api/decks
{
  "user_id": 1,
  "name": "Lightning Deck",
  "description": "Fast burn deck",
  "format": "Standard"
}
```

### 5. Add Card Instance to Deck

```json
POST /api/decks/1/add-card-instance/1
```

## Key Features

### Individual Card Tracking
- Each physical card is tracked separately
- Know exactly which copy of a card is in which deck
- Multiple copies of the same card can be in different states

### Collection Management
- Organize cards into different storage containers
- Track card conditions and foil status
- Prevent deletion of collections with cards

### Deck Building
- Add specific card instances to decks
- Prevent double-assignment of card instances
- Get detailed deck composition with card counts

### User Isolation
- Users can only manage their own collections and decks
- Security checks prevent cross-user card theft

## Data Validation

### Card Creation
- `title` (required, max 255 chars)
- `type` (required, max 100 chars)
- `cost` (optional, max 50 chars)
- `power/toughness` (optional, integers ≥ 0)

### Card Instance Creation
- `card_id` (required, must exist)
- `collection_id` (required, must exist)
- `condition` (enum: mint, near_mint, lightly_played, etc.)
- `foil` (boolean)

## Business Rules

1. Card instances must belong to exactly one collection
2. Card instances can belong to at most one deck
3. Only available (unassigned) card instances can be added to decks
4. Users can only assign their own card instances to their own decks
5. Cards cannot be deleted if instances exist
6. Collections cannot be deleted if they contain card instances
7. Deleting a deck releases all card instances back to available status

## Testing

The API includes comprehensive tests covering:
- CRUD operations for all models
- Business rule enforcement
- Complete workflow integration tests
- Security and authorization checks

Run tests with: `php artisan test` 