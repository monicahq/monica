# Research: The Me contact in every new vault

## R1. Where the Me contact is written

- Decision: `CreateVault` calls `CreateContact(isMe: true)` with the user's `first_name` and `last_name`, inside one `DB::transaction` that also holds the vault row and its template defaults.
- Rationale: `CreateContact` already refuses a second Me contact in a vault, skips the contact ceiling for `isMe`, keeps `contact_count` and `record_count` in step and writes default values. Writing it anywhere else would duplicate that.
- Alternatives: a listener on vault creation (hides the rule, outside the transaction); a queued job (the vault would exist without its contact).

## R2. Sign up order

Today `CreateAccount` creates the first vault inside its transaction, then queues `SeedValueLists` and `CreateDefaultTemplate` in a chain. The vault therefore has no contact default when it is made, and `CreateDefaultTemplate::seedTheVaultDefaults()` assigns it later. A Me contact cannot be written at that point.

- Decision: inside the sign up transaction, after the user and before the vault, run `CreateDefaultValueLists` and then `new CreateDefaultTemplate(user: ...)->handle()`. Remove `SeedValueLists` and the contact `CreateDefaultTemplate` from the chain. The animal template, life event categories and types, relationship types and notification preferences stay queued.
- Rationale: the contact template reads two value lists by key and writes a null list where none exists, so the lists must come first. Both are idempotent, so nothing else changes on a retry.
- `handle()` rather than `dispatchSync()`: `dispatchSync` goes through the queue resolver, so `Queue::fake()` in tests would swallow it and every sign up test would lose its template. `SeedAnimalTemplateCommand` already calls `handle()` the same way.
- Rejected: moving the template logic out of the job into an action. Principle I prefers it, but it is a refactor of a job this feature does not need to change.
- `SeedValueLists` keeps its test and loses its only caller. Removing it needs its own yes.

## R3. Queued work after commit

- Decision: add `->afterCommit()` to the `LogUserAction` dispatches in `CreateVault` and `CreateContact`, and to the `DetectDuplicateContacts` dispatch in `CreateContact`.
- Rationale: `queue.php` sets `after_commit` to false on every connection, so a dispatch inside the transaction would run even if the transaction is rolled back. Called outside any transaction, `afterCommit` dispatches at once, so other callers of `CreateContact` are unaffected.
- Test consequence: `QueueFake` ignores `afterCommit`. The rollback test uses the sync queue (as in `phpunit.xml`) and listens for `JobProcessing`, which never fires for a job discarded with its transaction.

## R4. No published contact template

- Decision: `CreateVault` lets `VaultHasNoContactDefault` escape, which rolls the vault back. The web `VaultController::create` answers with `back()->withErrors(['name' => ...])`, and the API with a 422 on `name`, as both already do for `AuthorizationException`.
- Rationale: FR-009 says no incomplete vault. The account reaches this state only if its system contact template was never created or has no published version.
- The exception's English message is internal. The controllers show a translated sentence of their own, so `monica:localize` must be run.

## R5. Which template the Me contact uses

- Decision: the system contact template seeded by `CreateVault`, even when the web or API request also passes `structure_version_id`. The controllers apply that choice after `CreateVault` returns, so it governs the contacts written afterwards.
- Rejected: passing the chosen version into `CreateVault`. It is a new parameter on both controllers and the action for a case the request did not raise. Somebody can move the Me contact to another version with `MoveRecordToStructureVersion`.

## R6. Counters

`contact_count` and the version's `record_count` include the Me contact, as `spec/models/007` says the count covers every contact the vault holds. A new vault therefore shows one person. The dashboard is out of scope and is not adjusted. `Account::contactCount()` already leaves `is_me` out of the ceiling.

## R7. Which documents disagree

| Document | Says | Change |
| --- | --- | --- |
| `.specify/memory/constitution.md`, Product Invariants | exactly one Me contact per account | one per vault, all standing for the same person; PATCH 2.4.3, since `spec/models/007` already decided it |
| `spec/spec.md` 4.3 | unique within the account | unique within each vault, created with it |
| `spec/models/007-vault` | nothing seeds the Me contact | a rule: every vault is created holding it |
| `spec/models/018-vault-default-template` | the first vault gets its default from the job | from `CreateVault`, like every other vault |
| `spec/models/012-template` | seeding the template is a job | the contact template is written in the sign up transaction |
| `spec/models/001-account` | the Me contact still waits; the lists are queued | the vault arrives with its Me contact; lists and contact template are synchronous |
| `spec/models/019-contact` | (consistent) | one line: created by `CreateVault` |
