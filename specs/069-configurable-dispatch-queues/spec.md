# Feature Specification: Configurable dispatch queues

**Feature Branch**: `2026-10-05-configurable-dispatch-queues`

**Created**: 2026-10-05

**Status**: Draft

**Input**: User description: "Configure queues choice for dispatches"

Today every piece of background work the application sends names its queue in the code: high priority work goes to a queue called `high`, low priority work to one called `low`, and the rest goes to the connection's default queue. About 210 places carry those two names literally, and the default queue's name is fixed too. Whoever runs the instance cannot change them, so a hosting platform whose workers listen to other names, or a single worker started without a list of queues (roadmap D25), leaves `high` and `low` work waiting forever.

A third setting names the queue behind the default priority, which is where work naming no queue goes, search indexing included.

## Clarifications

### Session 2026-10-05

- Q: What can the operator choose a queue for? → A: Two priorities, high and low, each mapped to one queue name. No routing per kind of work.
- Q: Can each priority use its own queue connection? → A: No. Names only, on the connection the instance already uses.
- Q: Can the administrator see or change the queue names in the administration screens? → A: No. Environment only, outside the instance settings the administrator edits.
- Q: Where does work go when a queue name is set but empty? → A: To the shipped name (`high` or `low`). The default queue is reached by writing its name.
- Q: What are the priorities called? → A: High and low, as in the code today (formerly referred to here as "urgent" and "deferred"). The setting is named after the priority; its value is the queue name.

### Session 2026-10-09

- Q: Does the default queue get a setting of its own? → A: Yes, a third priority, default, supersedes the two priority answer above. Search indexing stays on it rather than on high.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Name the queues the instance sends to (Priority: P1)

The person running the instance, Dwight on his own server or the team operating the hosted version, chooses which queue receives high, default and low priority work, without touching the code.

**Why this priority**: it is the whole feature. Without it the instance only works when its workers listen to the exact names the code chose.

**Independent Test**: set the high priority queue to `monica-high` and the low priority queue to `monica-low`, create an account, and find its setup work on `monica-high` and its journal entry on `monica-low`, with nothing on `high` or `low`.

**Acceptance Scenarios**:

1. **Given** no queue setting at all, **When** the instance sends background work, **Then** it lands on `high`, `low` and the default queue exactly as it does today.
2. **Given** the high priority queue is set to `monica-high`, **When** somebody fails to sign in, **Then** the warning email is queued on `monica-high`.
3. **Given** the low priority queue is set to `monica-low`, **When** Pam renames a contact, **Then** the journal entry and the duplicate detection are queued on `monica-low`.
4. **Given** these settings, **When** the scheduler starts its recurring work (reminder delivery every five minutes, the nightly orphaned file purge), **Then** each run lands on the queue configured for its priority.
5. **Given** a failed job retried by hand while the setting is unchanged, **Then** it goes back to the queue it failed on.

---

### User Story 2 - Run one worker for everything (Priority: P2)

A small self hosted instance runs a single worker that listens to the default queue only. Its operator gives all three priorities the same name and every piece of work is processed, with no list of queues to keep in step.

**Why this priority**: it removes the failure D25 recorded, and it is the cheapest setup an operator can run.

**Independent Test**: set the high and low priorities to `default`, start one worker with no queue list, sign up, and receive the verification email.

**Acceptance Scenarios**:

1. **Given** every priority is set to the same name, **When** any work is sent, **Then** all of it lands on that one queue.
2. **Given** several priorities point at the same name, **Then** the instance accepts it and reports nothing wrong.

---

### User Story 3 - Shipped workers follow the setting (Priority: P3)

The worker definitions the project ships (the container image, the two compose files and the development command) keep listing the priorities, high, default and low, and each priority reaches the queue configured for it, so changing the setting needs no second edit.

**Independent Test**: start the shipped container with custom queue names and see each processed.

**Acceptance Scenarios**:

1. **Given** custom names, **When** the shipped worker starts, **Then** it processes the high priority queue first, then the default queue, then the low priority queue.
2. **Given** no setting, **Then** the shipped worker listens to `high`, `default` and `low`, as today.

### Edge Cases

- A setting left empty or made only of spaces falls back to the shipped name rather than sending work to a nameless queue.
- When priorities share a name, the shipped worker processes all of it with no edit.
- A priority is sent to its configured name and no further: with high set to `default` and default set to `x`, high work lands on `default`, which nobody polls. To run everything on one queue, give all three the same name.
- Work already waiting on the old queue when a name changes stays there, and so does a failure retried after the change. The documentation tells the operator to change a name while its queue is empty, then restart the workers.
- Work that names no queue (the payment webhook listeners, search indexing) has the default priority and follows its setting.
- The development setting that runs work immediately, with no worker at all, behaves exactly as before.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The instance MUST read, from the server's environment, one queue name for each priority: high, default and low.
- **FR-002**: Each name MUST default to the one used today (`high`, `default`, `low`), so an instance that sets nothing behaves identically.
- **FR-003**: Every place in the application that sends work MUST reach the configured name of its priority, with no exception.
- **FR-004**: Scheduled recurring work MUST follow the same configured names.
- **FR-005**: Work that names no queue MUST have the default priority and follow its setting.
- **FR-006**: An empty or blank setting MUST fall back to its shipped name, never to another queue.
- **FR-007**: Several settings MAY hold the same name.
- **FR-008**: The shipped worker definitions MUST process the configured names, high priority first, default next and low priority last, with no edit when a name changes.
- **FR-009**: Every priority MUST stay on the queue connection the instance already uses; choosing a connection per priority is out of scope.
- **FR-010**: The choice MUST be made per priority, never per kind of work: each place that sends work keeps the priority it has today.
- **FR-011**: The self hosting documentation MUST name the three settings, their defaults, and what to do with work waiting on a queue whose name changed.

### Key Entities

- **Priority**: high, default or low, the three levels the application already distinguishes. Fixed in the code.
- **Queue name**: what the operator chooses for each priority. Read from the server environment, never per account.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: With no setting, 100% of background work lands on the same queue as before the change.
- **SC-002**: With every name changed, 100% of work lands on the new names and none on `high`, `default` or `low`, checked across every place that sends work.
- **SC-003**: An operator moves the instance to new queue names by changing configuration only, in under five minutes, with no code edit.
- **SC-004**: A single worker started with no queue list processes every kind of work once all three priorities share one name.

## Assumptions

- The setting belongs to the instance and is read from its environment only. It is not an instance setting the administrator edits, and is not shown in the administration screens: a worker reads its queues once at start, so a name changed on screen would strand new work on a queue nobody listens to.
- No API or MCP coverage is owed: this configures the server, not a capability of an account (principle X covers what a user can do).
- Hosted and self hosted read the same settings; nothing here differs by deployment.
- `high`, `default` and `low` stay the shipped defaults; renaming the defaults is out of scope.
