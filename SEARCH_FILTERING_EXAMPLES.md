# Contact Search & Filtering Examples

## Overview
This document provides practical examples of using the search and filtering capabilities in the Contacts API.

---

## Basic Usage

### 1. List All Contacts
```bash
GET /api/vaults/{vault_id}/contacts
```

**Response:**
```json
{
  "data": [
    {"id": "1", "first_name": "John", "last_name": "Doe", "is_favorite": true},
    {"id": "2", "first_name": "Jane", "last_name": "Smith", "is_favorite": false},
    {"id": "3", "first_name": "Johnny", "last_name": "Walker", "is_favorite": true},
    {"id": "4", "first_name": "Bob", "last_name": "Johnson", "is_favorite": false}
  ],
  "meta": {
    "current_page": 1,
    "per_page": 10,
    "total": 4
  }
}
```

---

## Filtering by Favorite Status

### 2. Get Only Favorite Contacts
```bash
GET /api/vaults/{vault_id}/contacts?favorite=1
```

**Response:**
```json
{
  "data": [
    {"id": "1", "first_name": "John", "last_name": "Doe", "is_favorite": true},
    {"id": "3", "first_name": "Johnny", "last_name": "Walker", "is_favorite": true}
  ],
  "meta": {
    "current_page": 1,
    "per_page": 10,
    "total": 2
  }
}
```

### 3. Get Only Non-Favorite Contacts
```bash
GET /api/vaults/{vault_id}/contacts?favorite=0
```

**Response:**
```json
{
  "data": [
    {"id": "2", "first_name": "Jane", "last_name": "Smith", "is_favorite": false},
    {"id": "4", "first_name": "Bob", "last_name": "Johnson", "is_favorite": false}
  ],
  "meta": {
    "current_page": 1,
    "per_page": 10,
    "total": 2
  }
}
```

---

## Searching Contacts

### 4. Search by Name
```bash
GET /api/vaults/{vault_id}/contacts?search=john
```

**What it searches:**
- first_name
- last_name
- middle_name
- nickname
- maiden_name

**Response:** (Returns contacts matching "john" in any name field)
```json
{
  "data": [
    {"id": "1", "first_name": "John", "last_name": "Doe", "is_favorite": true},
    {"id": "3", "first_name": "Johnny", "last_name": "Walker", "is_favorite": true},
    {"id": "4", "first_name": "Bob", "last_name": "Johnson", "is_favorite": false}
  ],
  "meta": {
    "current_page": 1,
    "per_page": 10,
    "total": 3
  }
}
```

**Note:** The search is:
- ✅ Case-insensitive ("john" matches "John", "Johnny", "Johnson")
- ✅ Partial match (finds substrings)
- ✅ Searches across multiple name fields

---

## Combined Filtering

### 5. Favorite Contacts Named "John"
```bash
GET /api/vaults/{vault_id}/contacts?favorite=1&search=john
```

**Response:** (Only favorites matching "john")
```json
{
  "data": [
    {"id": "1", "first_name": "John", "last_name": "Doe", "is_favorite": true},
    {"id": "3", "first_name": "Johnny", "last_name": "Walker", "is_favorite": true}
  ],
  "meta": {
    "current_page": 1,
    "per_page": 10,
    "total": 2
  }
}
```

---

## Pagination with Filters

### 6. Paginated Search Results
```bash
GET /api/vaults/{vault_id}/contacts?search=smith&limit=5&page=1
```

**Response:**
```json
{
  "data": [
    {"id": "2", "first_name": "Jane", "last_name": "Smith", "is_favorite": false},
    {"id": "5", "first_name": "John", "last_name": "Smith", "is_favorite": true},
    {"id": "8", "first_name": "Sarah", "last_name": "Smithson", "is_favorite": false}
  ],
  "links": {
    "first": "/api/vaults/{vault_id}/contacts?search=smith&limit=5&page=1",
    "last": "/api/vaults/{vault_id}/contacts?search=smith&limit=5&page=2",
    "prev": null,
    "next": "/api/vaults/{vault_id}/contacts?search=smith&limit=5&page=2"
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 2,
    "per_page": 5,
    "to": 5,
    "total": 8
  }
}
```

**Key Points:**
- Pagination parameters are preserved in links
- Filter parameters are maintained across pages
- Total count reflects filtered results

---

## Complex Query Examples

### 7. All Combinations
```bash
# Favorite contacts, search "doe", 20 per page
GET /api/vaults/{vault_id}/contacts?favorite=1&search=doe&limit=20

# Non-favorites, search "smith", page 2
GET /api/vaults/{vault_id}/contacts?favorite=0&search=smith&page=2

# Search only, custom page size
GET /api/vaults/{vault_id}/contacts?search=robert&limit=15
```

---

## cURL Examples

### Using cURL from Command Line

#### Basic favorite filter:
```bash
curl -X GET \
  "http://localhost/api/vaults/{vault_id}/contacts?favorite=1" \
  -H "Authorization: Bearer {your_token}" \
  -H "Accept: application/json"
```

#### Search with filter:
```bash
curl -X GET \
  "http://localhost/api/vaults/{vault_id}/contacts?search=john&favorite=1" \
  -H "Authorization: Bearer {your_token}" \
  -H "Accept: application/json"
```

#### With pagination:
```bash
curl -X GET \
  "http://localhost/api/vaults/{vault_id}/contacts?favorite=1&search=doe&limit=25&page=1" \
  -H "Authorization: Bearer {your_token}" \
  -H "Accept: application/json"
```

---

## JavaScript/Fetch Examples

### Using Fetch API

```javascript
// Get favorite contacts
async function getFavoriteContacts(vaultId, token) {
  const response = await fetch(
    `http://localhost/api/vaults/${vaultId}/contacts?favorite=1`,
    {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json'
      }
    }
  );
  return await response.json();
}

// Search with filters
async function searchContacts(vaultId, searchTerm, isFavorite, token) {
  const params = new URLSearchParams({
    search: searchTerm,
    favorite: isFavorite ? '1' : '0',
    limit: '20'
  });
  
  const response = await fetch(
    `http://localhost/api/vaults/${vaultId}/contacts?${params}`,
    {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json'
      }
    }
  );
  return await response.json();
}

// Usage
const favorites = await getFavoriteContacts('vault-123', 'your-token');
const results = await searchContacts('vault-123', 'john', true, 'your-token');
```

---

## Query Parameter Reference

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `favorite` | integer (0 or 1) | No | All contacts | Filter by favorite status |
| `search` | string | No | - | Search term for names |
| `limit` | integer (1-100) | No | 10 | Results per page |
| `page` | integer | No | 1 | Page number |

---

## Search Behavior Details

### Fields Searched
The search parameter looks for matches in:
1. `first_name` - Primary first name
2. `last_name` - Primary last name  
3. `middle_name` - Middle name
4. `nickname` - Preferred nickname
5. `maiden_name` - Maiden/previous name

### Search Characteristics
- **Case-insensitive**: "JOHN" = "john" = "John"
- **Partial match**: "doe" matches "Doe", "Doerr", "Odeon"
- **Any field**: Finds matches in ANY of the name fields
- **OR logic**: If match found in any field, contact is included

### Examples of Search Behavior

| Search Term | Matches | Doesn't Match |
|-------------|---------|---------------|
| "john" | John, Johnny, Johnson | Jane, Joan |
| "doe" | Doe, Doerr | Don, Dom |
| "smith" | Smith, Smithson, Blacksmith | Smythe |
| "mc" | McCarthy, McDonald, McMahon | Mac, Mac- |

---

## Performance Considerations

### Optimized Queries
- All filtering happens at the database level
- No post-query filtering in application code
- Indexed columns for fast search (recommended)

### Best Practices
1. Use specific search terms when possible
2. Combine filters to reduce result set
3. Use appropriate page limits
4. Consider adding database indexes on name columns:
   ```sql
   CREATE INDEX idx_contacts_first_name ON contacts(first_name);
   CREATE INDEX idx_contacts_last_name ON contacts(last_name);
   CREATE INDEX idx_contacts_is_favorite ON contacts(is_favorite);
   ```

---

## Error Handling

### Invalid Parameters
```bash
GET /api/vaults/{vault_id}/contacts?favorite=invalid
```

**Response:** 200 OK (parameter ignored, treated as "not set")

### Empty Results
```bash
GET /api/vaults/{vault_id}/contacts?search=zzzzz
```

**Response:**
```json
{
  "data": [],
  "meta": {
    "current_page": 1,
    "per_page": 10,
    "total": 0
  }
}
```

---

## Testing Checklist

- [ ] List all contacts without filters
- [ ] Filter favorites only (`?favorite=1`)
- [ ] Filter non-favorites (`?favorite=0`)
- [ ] Search by first name
- [ ] Search by last name  
- [ ] Search by nickname
- [ ] Combine favorite + search
- [ ] Test pagination with filters
- [ ] Verify pagination links preserve filters
- [ ] Test case-insensitive search
- [ ] Test partial match search
- [ ] Test with empty search results
- [ ] Test with large result sets
