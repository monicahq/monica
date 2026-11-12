# 058. Location

**Status:** in progress (`app/Models/Location.php`)
**Spec:** 3.2, specs/086-location-management

## What it is

A place of an [Account](001-account-in-progress.md): a town, a house, a room, a shelf, a box. Locations belong to the account rather than to a vault, since one house holds the things of every vault. A location may sit inside another one, as deep as somebody wants, so that "Maison › Garage › Établi" is three locations, each inside the one before. No kind and no level is imposed.

A location is not a thing owned. A house recorded as an owned item and the house as a place stay two records, and neither creates the other.

## Fields

```
id                          int                             primary key
uuid                        uuid                            route key, and identifier for the API later
account_id                  int                             account the location belongs to
parent_id                   int                             location it sits inside, null at the top level
name                        string (255)                    what it is called, required
emoji                       string (32)                     one emoji shown in place of the default icon, null for none
address                     text                            free text over several lines, null for none
```

## Rules

- The parent belongs to the same account, and is never the location itself or a location inside it, at any depth.
- A location holding another cannot be deleted; the refusal says how many are inside and that they must be moved first. Nothing is moved on anybody's behalf.
- A location without an address is described by the nearest ancestor's address, which is never copied into its own record.
- A location is named by its full path, joined by " › ", wherever one is chosen, so two locations with the same name are told apart.
- Locations are managed in the account settings, through the API (`/api/v1/locations`) and through the MCP server (`list-locations`, `get-location`, `create-location`, `update-location`, `delete-location`). Creating, changing and deleting one is written to the changelog.

## Not done yet

- A location any [possession stay](076-possession-location-stay-in-progress.md) refers to, open or past, cannot be deleted either; the refusal says how many stays are there and that they must be corrected or deleted first.
