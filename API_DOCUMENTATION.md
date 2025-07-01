# What Deck API Documentation

A comprehensive Magic: The Gathering card collection management API built with Laravel 11.

## 🚀 OpenAPI/Swagger Documentation

This API is fully documented using **OpenAPI 3.0.0** specification with interactive Swagger UI.

### Accessing the Documentation

- **Interactive Swagger UI**: `http://localhost:8000/api/documentation`
- **JSON Specification**: `http://localhost:8000/docs`
- **Generated Spec File**: `storage/api-docs/api-docs.json`

### Implementation Details

The OpenAPI documentation is implemented using **swagger-php** with PHP 8 attributes:

#### Resource Schemas (5 schemas)
- **Card**: MTG card template with relationships and computed properties
- **CardInstance**: Individual physical card with condition and deck assignment
- **Collection**: User collections with card statistics
- **Deck**: Constructed decks with card composition analysis
- **User**: User data with collection/deck relationships

#### Common Response Schemas (4 schemas)
- **PaginatedResponse**: Standard pagination format
- **ValidationError**: Form validation error responses
- **ErrorResponse**: General error responses  
- **SuccessResponse**: Success confirmation responses

#### API Coverage
- **14 documented endpoints** across 4 resource controllers
- **4 API tags**: Cards, Card Instances, Collections, Decks
- **29 total operations** (GET, POST, PUT, DELETE)

#### Advanced Features
- Comprehensive parameter documentation with examples
- Request/response body schemas with validation rules
- Relationship handling (conditional loading)
- Error response documentation with proper HTTP status codes
- Filtering and pagination parameter documentation
- Computed properties and business logic documentation

## 📋 API Endpoints

### Cards Management
- `GET /api/cards` - List cards with filtering (type, subtype, search)
- `POST /api/cards` - Create new card
- `GET /api/cards/{id}` - Get specific card with relationships
- `PUT /api/cards/{id}` - Update card
- `DELETE /api/cards/{id}` - Delete card (if no instances exist)

### Card Instances Management  
- `GET /api/card-instances` - List instances with filtering
- `POST /api/card-instances` - Create new card instance
- `GET /api/card-instances/{id}` - Get specific instance
- `PUT /api/card-instances/{id}` - Update instance condition/foil
- `DELETE /api/card-instances/{id}` - Delete instance
- `PUT /api/card-instances/{cardInstanceId}/move-to-deck/{deckId}` - Move to deck
- `PUT /api/card-instances/{id}/remove-from-deck` - Remove from deck

### Collections Management
- `GET /api/collections` - List collections
- `POST /api/collections` - Create new collection
- `GET /api/collections/{id}` - Get specific collection
- `PUT /api/collections/{id}` - Update collection
- `DELETE /api/collections/{id}` - Delete collection (if empty)
- `GET /api/collections/{id}/card-instances` - Get collection contents

### Decks Management
- `GET /api/decks` - List decks with format filtering
- `POST /api/decks` - Create new deck
- `GET /api/decks/{id}` - Get specific deck with card analysis
- `PUT /api/decks/{id}` - Update deck
- `DELETE /api/decks/{id}` - Delete deck
- `GET /api/decks/{id}/card-instances` - Get deck contents
- `POST /api/decks/{deckId}/add-card-instance/{cardInstanceId}` - Add card to deck
- `DELETE /api/decks/{deckId}/remove-card-instance/{cardInstanceId}` - Remove card from deck

## 🔧 Technical Implementation

### Validation & Form Requests
All endpoints use Laravel Form Request classes with comprehensive validation:
- **StoreCardRequest** & **UpdateCardRequest**: MTG card validation
- **StoreCollectionRequest** & **UpdateCollectionRequest**: Collection validation
- **StoreDeckRequest** & **UpdateDeckRequest**: Deck format validation
- **StoreCardInstanceRequest** & **UpdateCardInstanceRequest**: Condition validation

### API Resources
Sophisticated response formatting with Laravel API Resources:
- Conditional relationship loading
- Computed properties and statistics
- Consistent JSON structure
- Pagination support

### OpenAPI Attributes Architecture
- **Controller-level tags** for endpoint grouping
- **Method-level documentation** for each endpoint
- **Parameter documentation** with types and examples
- **Request body schemas** with validation rules
- **Response schemas** with proper HTTP status codes
- **Schema references** for consistent data structures

### Key Features
- **Individual card tracking**: Each physical card tracked separately
- **Smart deck management**: Prevents double-assignment, user isolation
- **Flexible filtering**: Type, format, availability, collection, deck filters
- **Comprehensive relationships**: Cards ↔ Instances ↔ Collections ↔ Decks ↔ Users
- **Business rule enforcement**: Referential integrity protection
- **Computed properties**: Statistics, availability, condition analysis

## 🧪 Testing

Comprehensive test suite with **49 tests** and **252 assertions**:
- Unit tests for core functionality
- Feature tests for all endpoints
- Integration tests for complete workflows
- Form request validation tests
- Business rule and security tests

All tests pass with OpenAPI implementation.

## 🛠️ Setup Instructions

1. **Install dependencies**:
   ```bash
   composer install
   npm install
   ```

2. **Configure environment**:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. **Setup database**:
   ```bash
   php artisan migrate
   php artisan db:seed
   ```

4. **Generate API documentation**:
   ```bash
   php artisan l5-swagger:generate
   ```

5. **Start development server**:
   ```bash
   php artisan serve
   ```

6. **Access documentation**:
   - API: `http://localhost:8000/api/documentation`
   - JSON spec: `http://localhost:8000/docs`

## 📚 Architecture

### Data Model
```
Users (1) → (*) Collections (1) → (*) CardInstances (*) ← (1) Cards
Users (1) → (*) Decks (1) → (*) CardInstances
```

### Response Format
```json
{
  "data": [...],           // Resource data
  "meta": {               // Pagination metadata
    "current_page": 1,
    "total": 67
  },
  "links": {              // Pagination links
    "first": "...",
    "next": "..."
  }
}
```

### Error Format
```json
{
  "message": "Validation failed",
  "errors": {
    "field": ["Error message"]
  }
}
```

This API provides a complete, documented, and tested solution for managing Magic: The Gathering card collections with professional-grade OpenAPI documentation. 