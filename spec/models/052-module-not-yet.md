# 052. Module

**Status:** to create, foundations only
**Spec:** 3.9, 15

## What it is

A package that adds behaviour to the product, as opposed to a template which configures data with the capabilities that already exist. Section 3.9 draws that line explicitly and it is the line to hold: a template configures, a module extends.

Modules matter most for self hosted instances, which need to adapt Erica to specialised needs without waiting for a feature to be accepted into the official product. A module may add screens, commands, events, integrations, field types, imports, exports, automations or connectors, at the extension points the product declares.

Section 3.9 also says the contract itself, the format, the lifecycle, the technical permissions, the compatibility rules and the distribution mechanism, is still to be specified. Section 15 puts the foundations in v1 and accepts that the catalogue may stay small at launch. This file describes the registry only, and deliberately stops where the specification does.

## Fields

```
id                          int                             primary key
key                         string (128)                    unique identifier of the module, such as vendor.name
name                        string (255)                    name shown to the user
description                 text                            what it does
author                      string (255)                    who wrote it
homepage_url                string (512)                    where to read more about it
repository_url              string (512)                    where the source is
version                     string (32)                     version of the package
minimum_app_version         string (32)                     oldest version of Erica it runs on
manifest                    json                            what it declares, its extension points, its permissions, its assets
extension_points            json                            which parts of the product it hooks into, extracted from the manifest for querying
permissions                 json                            what it asks to reach, which the user sees before installing
checksum                    string (64)                     hash of the package, so what runs is what was published
signature                   text                            signature of the package, verified before it is trusted
is_approved                 boolean                         whether we approved it, which is what the hosted version requires
approved_at                 datetime                        when it was approved
is_available                boolean                         whether it can still be installed
created_at                  datetime
updated_at                  datetime
```

## Relationships

- `hasMany` [ModuleInstallation](053-module-installation-not-yet.md)

## Rules

- **A module never bypasses the product's rules** (3.9). Not the API, not accessibility, not vault isolation, not history, not update compatibility. A module that needs to break one of those is asking for a change to the product, not for an extension point.
- **The hosted version runs only modules we approved and operate.** A self hosted instance installs whatever it wants and carries the security, maintenance and compatibility itself (3.9). `is_approved` is what separates the two, and the hosted version refuses anything without it.
- The module registry is instance level, not per account. What an account did with a module is [ModuleInstallation](053-module-installation-not-yet.md).
- Modules and the marketplace stay separate systems (3.9). A template is not a module and neither catalogue may absorb the other.
- Every action a module performs is logged with `source` set to module (12), so a change made by an extension is never indistinguishable from one made by hand.

## Open questions

Section 3.9 leaves the entire contract open, and section 16 asks two of these again. None of it should be built before it is answered.

- How far a module may extend the interface, the data model and the processing without compromising updates, security and accessibility.
- How modules are signed, distributed, updated, disabled and diagnosed.
- Whether a module may declare its own tables, or must live inside the existing ones. This is the decision with the longest shadow, because it determines whether uninstalling a module can ever be clean.
- Whether a module can add a field type. Section 3.9 says yes, and section 3.5 requires every field type to be accessible by construction, which means a module supplied field type has to meet a standard nobody can enforce on somebody else's code.
