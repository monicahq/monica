# Quickstart: Admin account screen redesign

The commands that gate the change are the ones in `CLAUDE.md`, under "When a change is done".

## By hand, at `https://erica.test/admin/accounts/1`, signed in as an administrator

| Check | Expected |
| --- | --- |
| Your own account | "Your own account" badge, the note, deactivation shown unavailable with its reason |
| Another active person | "Can sign in", deactivation behind its confirmation |
| A deactivated person | "Deactivated", the panel offers reactivation |
| Change limit, then grant and withdraw lifetime access | Each confirms as before and lands in the record |
| Switch a flag off and on | Only this account changes |
| Width 320 pixels, zoom 200%, dark theme | Nothing clipped, no horizontal page scroll |
| Keyboard and screen reader | The trail, every button and the storage bar are reached and announced |
