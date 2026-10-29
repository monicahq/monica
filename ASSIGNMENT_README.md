# Tag System Implementation for Monica CRM

This document outlines the implementation of a comprehensive tag system with filtering and analytics for the Monica CRM platform.

## Overview

This implementation extends Monica's existing Label system to support:
- Tag CRUD operations (Create, Read, Update, Delete)
- Multiple tag attachment to contacts
- Filtering contacts by multiple tags with AND logic
- Tag categorization and color customization
- Redis caching for performance
- Polymorphic tagging support for future extensibility
- Complete API with comprehensive test coverage

## Architecture & Design Decisions

### 1. Leveraging Existing Label System

**Decision**: Enhanced the existing `Label` model rather than creating a separate `Tag` model.

**Rationale**:
- Monica already has a well-integrated Label system for contacts
- Reusing existing infrastructure reduces code duplication
- All contacts already use the `contact_label` pivot table
- Maintains backward compatibility with existing features

**Trade-offs**:
- Minor naming confusion (Labels referred to as Tags in API)
- Not a breaking change, but requires documentation

### 2. Polymorphic Tagging Architecture

**Implementation**:
- Created a new `taggables` table for polymorphic relationships
- Allows future tagging of other models (Activities, Events, etc.)
- Maintains existing `contact_label` table for backward compatibility

**Schema**:
```
taggables table:
- id (primary key)
- label_id (foreign key to labels)
- taggable_id (polymorphic ID)
- taggable_type (model class name)
- timestamps

Indexes:
- Unique constraint on (taggable_id, taggable_type, label_id)
- Index on (taggable_type, taggable_id)
- Index on label_id
```

**Benefits**:
- Future-proof for non-contact tagging
- Maintains separation of concerns
- Supports multiple models tagged with same labels

### 3. Enhanced Label Model

**Added Fields**:
- `category` (nullable string) - Tag categorization (e.g., "Personal", "Work", "Networking")
- `color` (string) - Hex color code for UI rendering

**New Relationships**:
- `taggables()` - HasMany relationship to Taggable model
- `contacts()` - BelongsToMany (existing, maintained)

### 4. Contact Filtering with AND Logic

**Implementation** (single SQL query approach):
```php
$query->whereHas('labels', function ($q) use ($tagIds, $requiredTagCount) {
    $q->whereIn('labels.id', $tagIds)
        ->groupBy('contact_id')
        ->havingRaw("COUNT(DISTINCT labels.id) = ?", [$requiredTagCount]);
});
```

**Why this approach**:
- ✅ Single SQL query (no N+1 queries)
- ✅ Correct AND logic (contact must have ALL tags)
- ✅ Efficient with GROUP BY and HAVING clause
- ✅ Supports pagination seamlessly
- ✅ Works with existing relationship structure

**Alternative considered** (NOT used):
- Multiple `whereHas` calls (less efficient, multiple queries)
- Application-level filtering with loops (slow, memory intensive)

### 5. Redis Caching Strategy

**Implementation**:
- Cache key format: `tags:vault:{vault_id}`
- TTL: 10 minutes (600 seconds)
- Automatic invalidation on create, update, delete, attach, detach

**Cache invalidation points**:
```
1. TagController::store() - Tag creation
2. TagController::update() - Tag update
3. TagController::destroy() - Tag deletion
4. TagController::attachTag() - Tag attachment to contact
5. TagController::detachTag() - Tag detachment from contact
```

**Benefits**:
- Reduces database queries for frequently accessed tag lists
- Improves API response time for `/api/tags` endpoint
- Automatic invalidation ensures data consistency
- Simple, reliable cache management

**Trade-offs**:
- 10-minute stale data window (acceptable for tag data)
- Cache shared across all users in vault (privacy preserved at vault level)

## API Endpoints

### Tag Management

#### 1. List all tags for a vault
```
GET /api/vaults/{vaultId}/tags
Query Parameters:
  - limit: int (default: 10, max: 100)
Response: Array of tag objects with usage_count
```

#### 2. Create a new tag
```
POST /api/vaults/{vaultId}/tags
Body:
{
  "name": "string (required)",
  "category": "string (optional)",
  "color": "#RRGGBB (optional, hex format)",
  "description": "string (optional)"
}
Response: Created tag object (201 Created)
```

#### 3. Get a specific tag
```
GET /api/vaults/{vaultId}/tags/{tagId}
Response: Tag object
```

#### 4. Update a tag
```
PUT /api/vaults/{vaultId}/tags/{tagId}
Body: All fields optional (name, category, color, description)
Response: Updated tag object
```

#### 5. Delete a tag
```
DELETE /api/vaults/{vaultId}/tags/{tagId}
Response: 204 No Content
Cascade behavior: Removes tag from all contacts
```

### Contact-Tag Endpoints

#### 6. Attach tags to a contact
```
POST /api/vaults/{vaultId}/contacts/{contactId}/tags
Body:
{
  "tag_ids": [1, 2, 3]
}
Response: 200 OK with confirmation
```

#### 7. Detach a tag from a contact
```
DELETE /api/vaults/{vaultId}/contacts/{contactId}/tags/{tagId}
Response: 204 No Content
```

### Contact Filtering

#### 8. List contacts with tag filtering (AND logic)
```
GET /api/contacts
Query Parameters:
  - vault_id: string (required)
  - tags[]: array (optional) - Array of tag IDs
  - sort: string (optional) - Sort field (first_name, last_name, created_at)
  - sort_order: asc|desc (optional)
  - limit: int (optional)

Example:
GET /api/contacts?vault_id=123&tags[]=1&tags[]=2
Returns: Only contacts that have BOTH tag 1 AND tag 2
```

## Database Migrations

### Migration 1: Add category and color to labels
```php
File: database/migrations/2024_06_06_000001_add_category_to_labels_table.php
- Adds `category` column (nullable string)
- Adds `color` column (string, default: #6B7280)
- Adds indexes on vault_id, slug, category
```

### Migration 2: Create taggables table
```php
File: database/migrations/2024_06_06_000002_create_taggables_table.php
- Creates polymorphic pivot table for future extensibility
- Includes indexes for efficient queries
- Supports tagging multiple model types
```

### Migration 3: Add indexes to contact_label
```php
File: database/migrations/2024_06_06_000003_add_indexes_to_contact_label_table.php
- Adds indexes on label_id and contact_id
- Optimizes existing contact-tag queries
```

## Testing

### Test Coverage

**File**: `tests/Feature/Api/TagManagementTest.php`

**Test Cases**:
1. ✅ List all tags
2. ✅ Create a new tag and verify in list
3. ✅ Update a tag
4. ✅ Delete a tag
5. ✅ Attach tags to a contact
6. ✅ Filter contacts by multiple tags (AND logic)
7. ✅ Detach tags from contacts
8. ✅ Delete tag attached to multiple contacts
9. ✅ Cache invalidation on tag creation
10. ✅ Cache invalidation on tag update
11. ✅ Cache invalidation on tag deletion
12. ✅ Cache invalidation on tag attach
13. ✅ Cache invalidation on tag detach
14. ✅ Tag usage count in responses

**Running Tests**:
```bash
# Run all tag tests
php artisan test tests/Feature/Api/TagManagementTest.php

# Run with coverage
php artisan test tests/Feature/Api/TagManagementTest.php --coverage
```

## Response Format

### Success Response (201 Created)
```json
{
  "data": {
    "id": 1,
    "name": "Colleague",
    "slug": "colleague",
    "category": "Work",
    "color": "#FF5733",
    "description": "Work colleagues",
    "bg_color": "bg-zinc-200",
    "text_color": "text-zinc-700",
    "usage_count": 5,
    "created_at": "2024-06-06T10:30:00Z",
    "updated_at": "2024-06-06T10:30:00Z"
  }
}
```

### Error Response (422 Validation Error)
```json
{
  "error": {
    "message": "Validation failed",
    "error_code": 32
  }
}
```

### List Response (Paginated)
```json
{
  "data": [
    { ...tag objects... }
  ],
  "links": {
    "first": "http://...",
    "last": "http://...",
    "prev": null,
    "next": "http://..."
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 1,
    "per_page": 10,
    "total": 23
  }
}
```

## Key Assumptions

1. **User Authentication**: All endpoints require Sanctum token authentication with appropriate abilities (read/write)
2. **Vault-scoped Access**: All tags belong to a vault; users can only access tags in vaults they have permission to
3. **Cascade Delete**: Deleting a tag automatically removes it from all contacts
4. **Atomic Operations**: All tag operations are atomic (transaction support)
5. **Case-insensitive Filtering**: Tag filtering is case-sensitive (can be made insensitive if needed)
6. **Slug Generation**: Tag slugs are auto-generated from name using Laravel's Str::slug()
7. **Color Validation**: Color field expects hex format (#RRGGBB)
8. **Listed Contacts Only**: Contact filtering only returns contacts marked as 'listed'

## Security Considerations

1. **Vault Isolation**: Tags are scoped to vaults; users can't access tags from other vaults
2. **Permission Checks**: Sanctum middleware ensures only authenticated users access API
3. **Input Validation**: All inputs validated using Laravel validation rules
4. **Mass Assignment**: Only safe fields are in the fillable array
5. **SQL Injection Prevention**: Using parameterized queries with havingRaw()

## Performance Optimizations

1. **Single Query Filtering**: Tag filter uses one SQL query with GROUP BY/HAVING
2. **Redis Caching**: 10-minute cache on tag lists reduces database load
3. **Database Indexes**: 
   - Composite index on (taggable_id, taggable_type, label_id)
   - Individual indexes on frequently queried columns
4. **Eager Loading**: Using `with()` to prevent N+1 queries
5. **Pagination**: Supports limit parameter (default 10, max 100)

## Future Enhancements

1. **Batch Operations**: Support bulk tag operations (attach/detach multiple tags at once)
2. **Tag Analytics**: Endpoint to get tag usage statistics across contacts
3. **Tag Search**: Full-text search for tag names and descriptions
4. **Polymorphic Tagging**: Extend to tag Activities, Events, and other models
5. **Tag Hierarchies**: Support tag hierarchies/parent-child relationships
6. **Tag Aliases**: Support multiple names for same tag
7. **Audit Logging**: Track all tag operations for compliance
8. **WebSocket Updates**: Real-time tag updates via WebSockets

## File Structure

```
database/migrations/
  ├── 2024_06_06_000001_add_category_to_labels_table.php
  ├── 2024_06_06_000002_create_taggables_table.php
  └── 2024_06_06_000003_add_indexes_to_contact_label_table.php

app/Models/
  ├── Label.php (modified)
  ├── Contact.php (modified)
  └── Taggable.php (new)

app/Http/Resources/
  ├── TagResource.php (new)
  ├── ContactTagResource.php (new)
  └── ContactResource.php (new)

app/Domains/Vault/ManageTags/Api/Controllers/
  └── TagController.php (new)

app/Domains/Contact/ManageContact/Api/Controllers/
  └── ContactController.php (new)

tests/Feature/Api/
  └── TagManagementTest.php (new)

routes/
  └── api.php (modified)
```

## Running Migrations

```bash
# Run all migrations
php artisan migrate

# Run specific migration
php artisan migrate --path=database/migrations/2024_06_06_000001_add_category_to_labels_table.php

# Rollback migrations
php artisan migrate:rollback

# Reset database
php artisan migrate:reset && php artisan migrate
```

## Summary of Changes

### Added Features
- ✅ Tag CRUD operations via REST API
- ✅ Multi-tag filtering with AND logic
- ✅ Tag categorization and color support
- ✅ Redis caching for performance
- ✅ Polymorphic tagging infrastructure
- ✅ Comprehensive API documentation
- ✅ Full test coverage (14 test cases)

### Database Changes
- ✅ 3 migrations (category, taggables table, indexes)
- ✅ Backward compatible with existing label system
- ✅ Proper indexes for query optimization

### Code Quality
- ✅ Follows Monica's Domain-Driven Design patterns
- ✅ Consistent with existing API conventions
- ✅ Type-hinted relationships and methods
- ✅ Comprehensive error handling
- ✅ Well-documented with PHPDoc comments

## Conclusion

This implementation provides a robust, scalable tag system for Monica CRM with a clean REST API, proper caching, and comprehensive test coverage. The design is extensible for future enhancements while maintaining backward compatibility with the existing label system.
