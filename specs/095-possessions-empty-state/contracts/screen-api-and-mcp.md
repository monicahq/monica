# Contracts: Possessions Empty State

## Routes (`routes/web.php`)

| Method | URL | Name | Change |
| --- | --- | --- | --- |
| GET | `vaults/{vault}/possessions` | `possessions.index` | passes `PossessionsViewModel` |
| GET | `vaults/{vault}/possessions/new/{possessionType?}` | `possessions.new` | optional type, `whereUuid` (R7) |
| PUT | `settings/account/possession-types/{possessionType}` | `settings.possessionTypes.update` | `icon` nullable, `Rule::in(Section::ICONS)` |

## Screen (`app/vaults/possessions/index.blade.php`, empty vault)

| Element | Contract |
| --- | --- |
| Title, description | "All possessions"; "Everything you own in this vault, where it is kept and what it is worth." |
| Intro panel | `h2` "Nothing here yet", the paragraph; "Add your first possession" to `possessions.new`; "Set up locations" to `settings.locations.index` |
| Types | `h3` "Or start from a type", "Each one comes with its own fields."; each tile an `<a>` to `possessions.new` with the type, icon `aria-hidden` |
| No type | "Your account has no possession type yet." and "Manage types" to `settings.possessionTypes.index` |
| How it works | `h2` "How it works", "Three things to know. Only the name is required; the rest can come later."; three `article`, illustration `aria-hidden="true"`; step 3 links "Tags" to `settings.possessionTags.index` |
| Locations | "Your locations are ready." then the line; "Manage locations" to `settings.locations.index`; absent without locations |

A vault holding a possession keeps today's placeholder copy, under the new title.

## Type edit screen

Section "Name, colour and icon". Third form: `role="group"` labelled "Icon", one submit button per
glyph (`name="icon"`, `aria-label` the glyph's name, `aria-pressed`), plus "No icon" (`value=""`).
The name and colour forms send `icon` hidden; the icon form sends `name` and `colour` hidden. The
type list draws the icon in its colour where the dot is today.

## API

| Endpoint | Change |
| --- | --- |
| `POST /api/v1/possession-types` | `icon` nullable, `Rule::in(Section::ICONS)`, 422 outside |
| `PUT /api/v1/possession-types/{id}` | `icon` nullable; left out clears it (full replace) |
| `PossessionTypeResource` | `attributes.icon`, string or null, after `colour` |
| `PossessionResource` | `possession_type.icon` |

`resources/docs/api/26-possession-types.php` and `27-possessions.php` document it.

## MCP tools

| Tool | Change |
| --- | --- |
| `create-possession-type` | `icon` optional, `enum(Section::ICONS)`, "None when left out." |
| `update-possession-type` | `icon` optional, nullable; left out keeps it, null clears it |
| `get-possession-type`, `list-possession-types`, `get-possession` | return `icon` |
| `export-possession-type` | the file carries `icon` |

## Export file

`possession_type.icon`: string or null. Absent reads as none; unknown is a problem (R6).
`format_version` unchanged.
