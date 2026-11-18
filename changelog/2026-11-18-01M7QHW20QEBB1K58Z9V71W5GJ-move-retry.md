---
id: 01M7QHW20QEBB1K58Z9V71W5GJ
title: Moving contacts to another template version no longer fails when several run at once
type: fix
area: Contacts
published_at: 2026-11-18T14:44:45Z
---
When several contacts were moved to another template version at the same moment, for example from a script using the API, one of them could stop with a server error and you had to send it again. Monica now tries the move again by itself, and it goes through.
