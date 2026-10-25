# Implementation Plan: Remove a Telegram or text message setup, and ask for another code

**Branch**: `083-notification-channel-removal` | **Date**: 2026-10-25 | **Spec**: [spec.md](spec.md)

## Summary

The unused reset becomes `RemoveChannelSetup`, which also clears a Telegram connection in progress and
journals its own entry. It is reached through a new `DELETE .../{channel}/setup` route on the web and the
API, whose controller runs its own account query so a withdrawn text message channel can still be
cleared ([R1 to R3](research.md)). Asking for another code reuses the existing proof route; a new
`NotificationChannelPolicy::sendProofCode` holds the wait and the limit so the screen and the action
give one answer ([R4, R5](research.md)). No migration: one configuration key is added.

## Technical Context

| | |
| --- | --- |
| Language | PHP 8.5, Laravel 13, Sanctum |
| Storage | `notification_channels`, unchanged; `proof_last_sent_at` is read for the first time |
| Configuration | `monica.notification_proof_resend_seconds`, env `MONICA_NOTIFICATION_PROOF_RESEND_SECONDS`, default 60 |
| Front end | Blade, `x-delete-action`, `x-button`; no new JavaScript |
| Testing | PHPUnit; scenarios in [quickstart.md](quickstart.md) |
| Scale | One row per account per channel; every operation touches one row |

No open question remains. `data-model.md` is omitted: no table, column or state is added, and the
fields cleared are listed in FR-003.

## Constitution Check

| Principle | How it holds |
| --- | --- |
| I. Actions | `RemoveChannelSetup` and `SendChannelProofCode` hold the rules; four controllers delegate |
| II. Simplicity | One action renamed, one policy, one controller per surface, one model method, one key |
| III. Tested | Action, policy, model, view model and both controllers, per `.claude/rules/testing.md` |
| IV. Laravel | Policy through `User::authorize()` and `User::inspect()`; `DELETE` route, `destroy` |
| V. Localized, safe | Removal behind `x-delete-action`; copy through `__()` and `trans_choice()` |
| VI. Nothing underfoot | Only the account's own action changes its channel |
| VIII. Nothing disappears silently | The removal is what the person asked for, warned and journalled |
| IX. Accessible | The refused resend control stays, `aria-disabled` with `aria-describedby` on its reason |
| X. API | `DELETE api/v1/notification-channels/{channel}/setup`; resend through the existing proof route ([contracts/api.md](contracts/api.md)) |
| XI. Short record | spec 120, plan, research, contract and quickstart under their caps |

**Rule set aside**: `.claude/rules/policies.md` keeps a refused control on screen. The removal is not
an ability: it is absent where nothing is set up, as the connect button already is, because FR-001
and FR-006 say so (the conversation outranks the rules).

## Project Structure

```text
specs/083-notification-channel-removal/
├── spec.md, plan.md, research.md, quickstart.md
└── contracts/api.md

app/Actions/RemoveChannelSetup.php                 # renamed from ResetNotificationChannel
app/Actions/SendChannelProofCode.php               # authorize, conditional write
app/Policies/NotificationChannelPolicy.php         # new: sendProofCode
app/Models/NotificationChannel.php                 # holdsSetup()
app/Enums/UserActionEnum.php                       # NotificationChannelSetupRemoved
app/Http/Controllers/App/Settings/Account/Notifications/
├── ChannelSetupController.php                     # new: destroy
├── ChannelProofController.php                     # catches the refusal
└── NotificationChannelController.php              # reset() removed
app/Http/Controllers/Api/ChannelSetupController.php   # new: destroy
app/Http/Controllers/Api/ChannelProofController.php   # 422 on the refusal
app/Http/Resources/NotificationChannelResource.php    # can_send_proof_code, proof_code_refusal
app/ViewModels/Settings/Account/Notifications/NotificationChannelsViewModel.php
resources/views/app/settings/account/notifications/{index,_channel,_setup}.blade.php
routes/web.php, routes/api.php, config/monica.php
resources/docs/api/15-notification-channels.php
docs/portal/en/5-reminders-and-notifications/5-notification-channels.md
lang/*.json                                        # through monica:localize

tests/Unit/Actions/{RemoveChannelSetupTest,SendChannelProofCodeTest}.php
tests/Unit/Policies/NotificationChannelPolicyTest.php
tests/Unit/Models/NotificationChannelTest.php
tests/Unit/ViewModels/Settings/Account/Notifications/NotificationChannelsViewModelTest.php
tests/Feature/Controllers/App/Settings/Account/Notifications/{ChannelSetupControllerTest,ChannelProofControllerTest}.php
tests/Feature/Controllers/Api/{ChannelSetupControllerTest,NotificationChannelControllerTest}.php
```

**Structure Decision**: the existing Laravel layout; nothing new at the top level.

## Complexity Tracking

None.
