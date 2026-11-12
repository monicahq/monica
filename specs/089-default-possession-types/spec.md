# Feature Specification: Default Possession Types

**Feature Branch**: `2026-10-25-location-management`
**Created**: 2026-10-26 · **Status**: Draft
**Input**: "ajoute tous ces item type par defaut dans le compte. ca doit venir d'un job." Followed by a
catalogue of 25 possession types: 12 taken from an earlier product, with their English names, and 13
proposed in French, with their groups, fields and kinds. Then: harmonise the vocabulary, give CD Genre
its choices, widen the video game platforms, and add Other to the card grades and the coin metals.

Specification 088 lets an account define possession types, and states that no type is seeded. This
reverses that: an account starts with 25 types already written, so somebody recording their first
watch, bicycle or dishwasher finds the fields there, and changes or deletes what does not suit them.

## Clarifications

- Q: Do accounts created before this ships receive the 25 types? → A: Yes, through a migration, not a
  command: deploying the release is enough.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - A new account starts with the 25 types (Priority: P1)

Somebody signs up. Shortly after, the Possession types screen in the account settings lists the 25
types of the catalogue below, each with its groups and fields in the catalogue's order, written in the
language they chose.

**Why this priority**: It is the feature. **Independent Test**: Create an account, let the queue run,
open the list and one type of each kind of field.

**Acceptance Scenarios**:

1. **Given** a new account, **When** its setup work has run, **Then** it holds exactly the 25 types of
   the catalogue, and the account's journal records one entry saying the types were set up.
2. **Given** the "Comics" type, **When** it is opened, **Then** "My rating" stands alone first, then
   the group "Publishing info" holds Issue #, Publisher, Writer, Artist, Cover date in that order, then
   "Condition & grading" holds Variant and Signed, each with the kind the catalogue names.
3. **Given** "Coins", **When** its Grade field is opened, **Then** it is a select offering the 18
   grades in the catalogue's order.
4. **Given** any select field of the catalogue, **When** it is opened, **Then** it offers at least one
   choice, and every list that cannot be complete ends with Other.
5. **Given** "Other", **When** it is opened, **Then** it holds no group and no field.
6. **Given** an account created in French, **When** the list opens, **Then** the names of types,
   groups, fields and translatable options are in French; option codes such as "PSA 10", "MS-70",
   "NTSC" or "33" are the same in every language.

### User Story 2 - The seeded types are ordinary types (Priority: P1)

A seeded type is the account's own from the moment it exists. It is renamed, recoloured, extended,
exported and deleted like a type the account created.

**Why this priority**: A default that cannot be changed is a constraint, not a help.
**Independent Test**: Rename "Wine", add a field to it, delete "Stamps", export "Watches".

**Acceptance Scenarios**:

1. **Given** a seeded type, **When** it is renamed or a field is added, removed or moved, **Then** it
   behaves exactly as in specification 088, and nothing marks it as seeded.
2. **Given** a seeded type deleted, **When** the setup work is run again for that account, **Then**
   it does not come back.

### User Story 3 - Accounts that existed before (Priority: P2)

Possession types are not yet released, so every account that exists when this ships holds none. The
release's migrations give each of those accounts the catalogue, through the same work a new account
receives, with nothing for an operator to run.

**Why this priority**: Without it, everybody already using the product starts from an empty list.
**Independent Test**: With two existing accounts, one holding a type of its own, run the migration.

**Acceptance Scenarios**:

1. **Given** an existing account with no possession type, **When** the migration runs, **Then** it
   holds the 25 types.
2. **Given** an existing account that already holds a type, **When** the migration runs, **Then**
   nothing is added to it.
3. **Given** the migration run twice, or an account nobody signs in to, **Then** nothing more is added.

### Edge Cases

- The setup work runs twice for one account (a retried job): the account still holds 25 types, not 50.
- The account already holds types of its own when the work runs: they are left untouched, and the
  work adds nothing.
- One failure while writing the catalogue: nothing is kept for that account, so it never holds half a
  catalogue, and the job can be retried.
- A field the catalogue names twice in one type under different groups (none today) would be two
  fields; names are not required to be unique (specification 088).

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: Creating an account MUST queue work that gives it the 25 possession types of the
  catalogue, with their groups and fields, in the catalogue's order.
- **FR-002**: The work MUST run on the queue, after the account exists, and MUST NOT delay sign-up.
- **FR-003**: The work MUST write the whole catalogue or nothing for an account.
- **FR-004**: The work MUST add nothing to an account that already holds at least one possession type.
- **FR-005**: Names of types, groups, fields and options MUST be written in the language of the user
  the account was created for, at the moment the work runs. Option codes listed as untranslated in the
  catalogue MUST be written as they are.
- **FR-006**: Every catalogue field kind MUST map to the kinds of specification 088: text, number,
  date, yes or no, select, and rating (written "note" in the input).
- **FR-007**: Each type MUST receive a colour from the fixed palette of specification 088; the plan
  fixes which colour goes to which type.
- **FR-008**: The account's journal MUST record one entry when the catalogue is set up, not one per
  type, group or field.
- **FR-009**: A seeded type MUST carry no mark distinguishing it from a type the account created.
- **FR-010**: Every name in the catalogue MUST be translatable in every configured locale, under the
  glossary of the translations rules.
- **FR-011**: The product guide page on possession types MUST say an account starts with these types
  and that they can be changed or deleted.
- **FR-012**: A migration MUST give the catalogue to every existing account holding no possession
  type, through the same work a new account receives.

### The catalogue

Notation: text, number, date, yes/no, select (options in order), rating. "Standalone" means the field
belongs to no group and comes before the groups. An option in `code` style is written untranslated.

**1. Comics.** Standalone: My rating (rating). *Publishing info*: Issue # (number); Publisher (select:
`Marvel`, `DC`, `Image`, `Dark Horse`, Independent); Writer (text); Artist (text); Cover date (date).
*Condition & grading*: Variant (yes/no); Signed (yes/no).

**2. Trading Cards.** *Card details*: Card number (text); Set/series (text); Player/character (text).
*Condition & grading*: Grade (select: `PSA 10`, `PSA 9`, Raw, Other); Rookie card (yes/no);
Autographed (yes/no).

**3. Vinyl Records.** Standalone: My rating (rating). *Release info*: Artist (text); Album (text);
Release year (number). *Pressing details*: Pressing/edition (text); Speed (select: `33`, `45`, `78`);
Coloured vinyl (text).

**4. CD.** Standalone: My rating (rating). *Release info*: Artist (text); Album (text); Record label
(text); Release year (number); Genre (select: Rock, Pop, Jazz, Classical, Hip hop, Electronic, Folk,
Other). *Extras*: Bonus disc (yes/no); Signed (yes/no).

**5. DVD.** Standalone: My rating (rating). *Release info*: Title (text); Director (text); Studio
(text); Release year (number). *Edition*: Region code (select: `1`, `2`, `3`, `4`, `5`, `6`, Region
free); Special edition (yes/no); Box set (yes/no).

**6. Coins.** *Identification*: Denomination (text); Year minted (number); Mint mark (text); Country
(text). *Condition & metal*: Grade (select: `MS-70`, `MS-65`, `MS-60`, `AU-58`, `AU-50`, `XF-45`,
`XF-40`, `VF-30`, `VF-20`, `F-15`, `F-12`, `VG-10`, `VG-8`, `G-6`, `G-4`, `AG-3`, `FR-2`, `PR-1`);
Metal (select: Silver, Gold, Copper, Other).

**7. Stamps.** *Issue info*: Country (text); Year issued (number); Denomination (text). *Condition*:
Perforated (yes/no); Cancelled/mint (select: Cancelled (used), Mint (unused)).

**8. Books.** Standalone: My rating (rating). *Publishing info*: Author (text); Edition (text);
Publisher (text); ISBN (text). *Copy details*: First edition (yes/no); Signed (yes/no).

**9. Action Figures / Toys.** *Product info*: Brand (text); Line/series (text); Year (number); Scale
(text). *Condition*: In box / MIB (yes/no).

**10. Video Games.** Standalone: My rating (rating). *Release info*: Platform (select: `PC`, `PS5`,
`PS4`, `PS3`, `PS2`, `PS1`, `Xbox Series`, `Xbox One`, `Xbox 360`, `Xbox`, `Switch`, `Wii U`, `Wii`,
`GameCube`, `N64`, `SNES`, `NES`, `Game Boy`, `DS`, `3DS`, `Mega Drive`, `Dreamcast`, Other);
Publisher (text); Release year (number); Region (select: `NTSC`, `PAL`). *Condition*: Complete in box
(yes/no).

**11. Watches.** *Model info*: Brand (text); Model (text); Movement (select: Automatic, Quartz,
Manual); Serial number (text). *Condition*: Box & papers (yes/no).

**12. Wine.** Standalone: Bottle size (select: Split/187ml, Half/375ml, Standard/750ml, Magnum/1.5L,
Double Magnum/3L, Jeroboam/4.5L, Imperial/6L); My rating (rating). *Origin*: Producer/winery (text);
Vintage (number); Region (text); Varietal (text).

**13. Cars.** *Identification*: Make (text); Model (text); Trim (text); Model year (number);
Registration number (text); First registration date (date). *Characteristics*: Engine (text); Fuel
(select: Petrol, Diesel, Electric, Hybrid, Plug-in hybrid, LPG, Other); Transmission (select: Manual,
Automatic, Other); Colour (text); Power in kW (number). *Maintenance references*: Tyre size (text);
Oil reference (text).

**14. Bicycles.** *Identification*: Brand (text); Model (text); Year (number); Use (select: City,
Road, Gravel, Mountain, Cargo, `BMX`, Other). *Equipment*: Frame size (text); Wheel size (text);
Drivetrain (text); Brakes (text); Electric assist (yes/no); Battery capacity in Wh (number).

**15. Household appliances.** *Identification*: Category (select: Dishwasher, Washing machine, Tumble
dryer, Refrigerator, Freezer, Oven, Hob, Vacuum cleaner, Other); Brand (text); Model (text); Product
reference (text). *Characteristics*: Dimensions (text); Capacity (text); Power in W (number); Stated
energy class (text). *Installation*: Installation date (date); Filter or consumable reference (text).

**16. Electronics and computers.** *Identification*: Category (select: Computer, Phone, Tablet,
Monitor, Television, Audio, Network, Photography, Other); Brand (text); Model (text); Year (number).
*Configuration*: Processor (text); Memory in GB (number); Storage in GB (number); Operating system
(text); Ports (text). *Accessories and consumables*: Charger reference (text); Battery reference
(text).

**17. Furniture.** *Identification*: Category (select: Table, Chair, Sofa, Bed, Wardrobe, Shelf, Desk,
Other); Brand (text); Model or range (text); Designer (text). *Dimensions and materials*: Width in cm
(number); Height in cm (number); Depth in cm (number); Materials (text); Colour or finish (text).
*Upkeep*: Fabric or covering reference (text); Care instructions (text).

**18. Tools and equipment.** *Identification*: Category (text); Brand (text); Model (text); Product
reference (text). *Characteristics*: Power source (select: Manual, Mains, Battery, Combustion engine,
Pneumatic, Other); Voltage in V (number); Power in W (number); Battery platform (text).
*Compatibility*: Compatible accessories (text); Consumable references (text).

**19. Clothing and accessories.** *Identification*: Category (text); Brand (text); Model or range
(text). *Characteristics*: Size (text); Colour (text); Composition (text). *Upkeep*: Washing
instructions (text); Drying instructions (text).

**20. Jewellery.** *Identification*: Category (select: Ring, Necklace, Bracelet, Earrings, Pendant,
Other); Brand (text); Model (text). *Composition*: Metal (text); Fineness (text); Stones (text);
Weight in g (number); Size or dimensions (text). *Authentication*: Hallmark (text); Certificate
reference (text); Certifying body (text).

**21. Musical instruments.** *Identification*: Instrument (text); Brand (text); Model (text); Year
(number). *Characteristics*: Materials (text); Dimensions or format (text); Finish (text).
*Configuration*: Tuning (text); Strings or consumables reference (text); Particular features (text).

**22. Art and decoration.** *Creation*: Category (select: Painting, Drawing, Photograph, Sculpture,
Print, Decorative object, Other); Artist or maker (text); Year of creation (number); Technique (text);
Materials (text). *Dimensions and edition*: Dimensions (text); Copy number (text); Edition size
(number); Signed (yes/no). *Presentation*: Frame or mount (text); Conservation instructions (text).

**23. Sports and outdoors.** *Identification*: Activity (text); Equipment category (text); Brand
(text); Model (text). *Characteristics*: Size or dimensions (text); Weight in kg (number); Materials
(text). *Use*: Season or conditions of use (text); Compatibility (text); Care instructions (text).

**24. Homes.** *Building*: Category (select: House, Apartment, Chalet, Other); Year built (number);
Living area in m² (number); Land area in m² (number); Number of rooms (number). *Administrative
references*: Cadastral reference (text); Lot number (text). *Home equipment*: Heating type (text);
Heating energy (text); Hot water (text); Ventilation (text).

**25. Other.** No group, no field.

### Key Entities

No new entity. The work writes PossessionType, PossessionFieldGroup and PossessionField rows exactly as
specification 088 defines them.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Every account created after this ships holds the 25 types within the time its other
  setup work takes, with no change to how long sign-up takes.
- **SC-002**: For every type, an export of the seeded type matches the catalogue entry above: same
  groups, fields, kinds and options, in the same order.
- **SC-003**: Running the work any number of times for one account leaves it with 25 types.
- **SC-004**: Every name in the catalogue has a translation in every configured locale, and the
  localisation check passes.

## Assumptions

- The 12 type names taken from the earlier product are kept. Their field names follow the product's
  sentence case and British spelling ("Coloured vinyl"), one maker is "Brand" everywhere except "Make"
  on cars and "Artist or maker" in art, and a word that already carries another meaning in the
  product's translations is made precise: "Record label", "Region free", "Cancelled (used)" and "Mint
  (unused)". Watches keep their own Serial number field. Publishers and platforms are names and stay
  untranslated. The 13 French proposals are
  translated into English here, because English is the source language; the English names above are
  the keys, and French reaches the reader through the French translation file.
- The names are stored as plain text in the user's language when the work runs, since possession types
  hold a plain name (specification 088). A user who later switches language keeps the seeded names in
  the first one, as with any name they typed.
- Video Games has six fields, as the source defines.
- Everything the possession already carries, or will (name, description, condition, serial number or
  VIN, location, quantity, purchase, warranty, documents, maintenance, mileage, insurance, the address
  of a home), is out of this catalogue. Labelling the serial number by type belongs to the future
  possessions feature, not to this one.
- The work runs in the existing chain of account setup work, after the other seeding steps.
