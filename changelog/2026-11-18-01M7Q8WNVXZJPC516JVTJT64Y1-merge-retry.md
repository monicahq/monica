---
id: 01M7Q8WNVXZJPC516JVTJT64Y1
title: Merging two contacts no longer fails when several merges run at once
type: fix
area: Contacts
published_at: 2026-11-18T12:07:48Z
---
When several merges ran at the same moment, for example from a script using the API, one of them could stop with a server error and you had to send it again. Monica now tries the merge again by itself, and it goes through.
