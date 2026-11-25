---
id: 01M89GN71FEEH08M1CWZ59HTEB
title: Telegram and text messages work in the Docker image
type: fix
area: Notifications
published_at: 2026-11-25T14:09:52Z
---
On a server running the Docker image, the Telegram and text message channels were listed as unavailable, whatever the administrator configured. The image now carries what both channels need, so they work once the bot or the Vonage account is set up.
