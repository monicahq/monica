# Contact API Endpoints Documentation

## Overview
This document describes the API endpoints for managing contact favorites and personal notes in Monica.

## Base URL
All endpoints are prefixed with `/api/vaults/{vault_id}`

## Authentication
All endpoints require authentication via Laravel Sanctum with the `auth:sanctum` middleware.

## Permissions
- Read operations require `read` ability
- Write operations require `write` ability

---

## Endpoints

### 1. List All Contacts (with Filtering & Search)
**GET** `/api/vaults/{vault_id}/contacts`

Lists all contacts in a vault with optional filtering and search capabilities.

**Parameters:**
- `limit` (optional, integer): Number of results per page (1-100, default: 10)
- `favorite` (optional, integer): Filter by favorite status
  - `1` - Show only favorite contacts
  - `0` - Show only non-favorite contacts
  - Omit to show all contacts
- `search` (optional, string): Search term to filter contacts by name
  - Searches across: first_name, last_name, middle_name, nickname, maiden_name
  - Case-insensitive partial match

**Filtering Examples:**
```bash
# Get all contacts
GET /api/vaults/{vault_id}/contacts

# Get only favorite contacts
GET /api/vaults/{vault_id}/contacts?favorite=1

# Search for contacts named "john"
GET /api/vaults/{vault_id}/contacts?search=john

# Get favorite contacts named "john"
GET /api/vaults/{vault_id}/contacts?favorite=1&search=john

# Paginate with 20 results per page
GET /api/vaults/{vault_id}/contacts?limit=20

# Combine all filters
GET /api/vaults/{vault_id}/contacts?favorite=1&search=john&limit=20
```

**Response:**
```json
{
  "data": [
    {
      "id": "uuid",
      "vault_id": "uuid",
      "first_name": "John",
      "last_name": "Doe",
      "is_favorite": true,
      "personal_note": "Important contact",
      "created_at": 1234567890,
      "updated_at": 1234567890
    }
  ],
  "links": {
    "first": "http://localhost/api/vaults/{vault_id}/contacts?page=1",
    "last": "http://localhost/api/vaults/{vault_id}/contacts?page=5",
    "prev": null,
    "next": "http://localhost/api/vaults/{vault_id}/contacts?page=2"
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 5,
    "per_page": 10,
    "to": 10,
    "total": 50
  }
}
```

**Features:**
- ✅ Pagination continues working with filters
- ✅ Existing sorting continues working
- ✅ No duplicated query logic
- ✅ Multiple filters can be combined
- ✅ Case-insensitive search

---

### 2. Get Contact Details
**GET** `/api/vaults/{vault_id}/contacts/{contact_id}`

Retrieves a specific contact with all details including favorite status and personal note.

**Response:**
```json
{
  "id": "uuid",
  "vault_id": "uuid",
  "first_name": "John",
  "last_name": "Doe",
  "middle_name": null,
  "nickname": "Johnny",
  "is_favorite": true,
  "personal_note": "Met at conference 2024",
  "job_position": "Software Engineer",
  "listed": true,
  "can_be_deleted": true,
  "last_updated_at": 1234567890,
  "created_at": 1234567890,
  "updated_at": 1234567890,
  "links": {
    "self": "http://localhost/api/vaults/{vault_id}/contacts/{contact_id}"
  }
}
```

---

### 3. List Favorite Contacts
**GET** `/api/vaults/{vault_id}/contacts/favorites`

Lists all contacts marked as favorites in a vault.

**Parameters:**
- `limit` (optional, integer): Number of results per page (1-100, default: 10)

**Response:**
```json
{
  "data": [
    {
      "id": "uuid",
      "vault_id": "uuid",
      "first_name": "John",
      "last_name": "Doe",
      "is_favorite": true,
      "personal_note": "Important contact",
      "created_at": 1234567890,
      "updated_at": 1234567890
    }
  ]
}
```

---

### 4. Mark Contact as Favorite
**POST** `/api/vaults/{vault_id}/contacts/{contact_id}/favorite`

Marks a contact as favorite.

**Response:**
```json
{
  "id": "uuid",
  "vault_id": "uuid",
  "first_name": "John",
  "last_name": "Doe",
  "is_favorite": true,
  "personal_note": null,
  "created_at": 1234567890,
  "updated_at": 1234567890
}
```

---

### 5. Remove Contact from Favorites
**DELETE** `/api/vaults/{vault_id}/contacts/{contact_id}/favorite`

Removes a contact from favorites.

**Response:**
```json
{
  "id": "uuid",
  "vault_id": "uuid",
  "first_name": "John",
  "last_name": "Doe",
  "is_favorite": false,
  "personal_note": null,
  "created_at": 1234567890,
  "updated_at": 1234567890
}
```

---

### 6. Toggle Favorite Status
**PATCH** `/api/vaults/{vault_id}/contacts/{contact_id}/favorite`

Toggles the favorite status of a contact (if favorite, removes; if not favorite, marks as favorite).

**Response:**
```json
{
  "id": "uuid",
  "vault_id": "uuid",
  "first_name": "John",
  "last_name": "Doe",
  "is_favorite": true,
  "personal_note": null,
  "created_at": 1234567890,
  "updated_at": 1234567890
}
```

---

### 7. Update Personal Note
**PUT** `/api/vaults/{vault_id}/contacts/{contact_id}/note`

Updates the personal note for a contact.

**Request Body:**
```json
{
  "personal_note": "Met at tech conference 2024. Interested in AI/ML."
}
```

**Response:**
```json
{
  "id": "uuid",
  "vault_id": "uuid",
  "first_name": "John",
  "last_name": "Doe",
  "is_favorite": false,
  "personal_note": "Met at tech conference 2024. Interested in AI/ML.",
  "created_at": 1234567890,
  "updated_at": 1234567890
}
```

---

### 8. Get Contact Statistics
**GET** `/api/vaults/{vault_id}/contacts/stats`

Returns summary statistics for contacts in the vault.

**Response:**
```json
{
  "total_contacts": 125,
  "favorite_contacts": 18,
  "contacts_with_notes": 42
}
```

**Statistics Breakdown:**
- `total_contacts` - Total number of listed contacts in the vault
- `favorite_contacts` - Number of contacts marked as favorites
- `contacts_with_notes` - Number of contacts with personal notes

**Performance:**
- Uses efficient single-query aggregation
- No contacts loaded into memory
- Optimized for large datasets

---

## Error Responses

### 401 Unauthorized
```json
{
  "message": "Unauthenticated."
}
```

### 403 Forbidden
```json
{
  "error": "Insufficient permissions"
}
```

### 404 Not Found
```json
{
  "error": "Resource not found"
}
```

### 422 Validation Error
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "personal_note": [
      "The personal note must not exceed 65535 characters."
    ]
  }
}
```

---

## Usage Examples

### cURL Examples

#### List favorite contacts:
```bash
curl -X GET "http://localhost/api/vaults/{vault_id}/contacts/favorites" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

#### Mark contact as favorite:
```bash
curl -X POST "http://localhost/api/vaults/{vault_id}/contacts/{contact_id}/favorite" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

#### Update personal note:
```bash
curl -X PUT "http://localhost/api/vaults/{vault_id}/contacts/{contact_id}/note" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"personal_note": "Important client from Q4 2024"}'
```

#### Toggle favorite status:
```bash
curl -X PATCH "http://localhost/api/vaults/{vault_id}/contacts/{contact_id}/favorite" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

#### Get statistics:
```bash
curl -X GET "http://localhost/api/vaults/{vault_id}/contacts/stats" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

## Implementation Details

### Services Used
- `MarkContactAsFavorite`: Marks a contact as favorite
- `RemoveContactFromFavorites`: Removes favorite status
- `ToggleFavoriteContact`: Toggles favorite status (existing service, enhanced)
- `UpdateContactPersonalNote`: Updates personal note

### Database Schema
- `contacts.is_favorite` (boolean, default: false)
- `contacts.personal_note` (text, nullable)

### Resource
All endpoints return data formatted through `ContactResource`.

---

## Notes
- All timestamp fields are returned as Unix timestamps
- The `is_favorite` field is now stored in the main `contacts` table
- Personal notes can be up to 65,535 characters (TEXT field)
- The `listed` field filters contacts visible in listings
- The `last_updated_at` timestamp is automatically updated on modifications

---

## Search & Filtering

### Query Parameters

The main listing endpoint (`GET /api/vaults/{vault_id}/contacts`) supports the following query parameters for filtering and searching:

#### 1. Favorite Filter (`favorite`)
- **Type**: Integer (0 or 1)
- **Usage**: `?favorite=1` for favorites only, `?favorite=0` for non-favorites
- **Default**: All contacts (when parameter is omitted)

#### 2. Search Filter (`search`)
- **Type**: String
- **Usage**: `?search=john`
- **Behavior**: 
  - Case-insensitive partial match
  - Searches across: `first_name`, `last_name`, `middle_name`, `nickname`, `maiden_name`
  - Uses SQL LIKE with wildcards

#### 3. Pagination (`limit`)
- **Type**: Integer (1-100)
- **Usage**: `?limit=20`
- **Default**: 10

### Combining Filters

Filters can be combined using the `&` operator:

```bash
# Favorite contacts named "john" with 20 per page
GET /api/vaults/{vault_id}/contacts?favorite=1&search=john&limit=20
```

### Implementation Details

- **No Query Duplication**: All filtering logic is centralized in `ContactQuery` class
- **Pagination Preserved**: Laravel's pagination works seamlessly with filters
- **Sorting Maintained**: Any existing sorting behavior is preserved
- **Performance**: Database indexes on name fields recommended for optimal search performance

### Architecture

```
Request → ContactController
    ↓
ContactQuery::apply()
    ↓
    ├─ filterByFavorite() - if favorite parameter present
    ├─ filterBySearch()   - if search parameter present
    ↓
Paginated Query → Response
```

The query builder pattern ensures:
- Single source of truth for filtering logic
- Easy to extend with new filters
- Maintainable and testable code
- No duplication across different endpoints
