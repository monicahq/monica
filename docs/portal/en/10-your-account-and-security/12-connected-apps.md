---
id: security.assistants
title: Manage your connected apps
description: See every app you allowed to reach your account, the Monica app on a phone or an AI assistant, and disconnect the ones you no longer use.
slug: connected-apps
section: your-account-and-security
last_updated: 2026-11-27
---

# Manage your connected apps

Some apps reach your account by asking you to sign in and approve them, rather than with an API key: the Monica app on your phone, or an AI assistant such as Claude or ChatGPT. Each approval is a connection. The app acts as you until it signs out, goes unused for 30 days, or you disconnect it.

Every app signs in the same way. Monica opens its sign-in page, you sign in as you always do, then Monica asks **Connect** followed by the name of the app, with the address it will send you back to. Choose **Allow** only if you started the connection yourself, since an app names itself. Monica asks this every time an app connects, even one you allowed before.

To connect the app on your phone, see @doc(security.mobile-app). The [MCP guide](/docs/mcp) explains how to connect an assistant.

## See your connected apps

Open your settings and choose **Security and access**. **Connected apps** lists each connection, newest first. Your phone, for example, shows as **Pixel 9**, followed by **Monica for mobile**, what it may do, when it connected and when it was last used. An assistant shows under its own name. When nothing is connected, it says **No app connected**.

Each sign-in is a connection of its own. Two phones are two lines, and an assistant you connected twice is two lines too. An app that uses an API key instead is listed under **API keys**. See @doc(security.api-keys).

:::note
An assistant you connected before connections were listed shows as one line for all its access. It keeps working, and disconnecting it cuts off all of it.
:::

## Disconnect an app

Do this when you lose a phone, sell it, or no longer use an assistant.

:::warning
The app loses access at once and has to ask you again before it can come back.
:::

::::steps
:::step title="Start"
Choose **Disconnect** on the app.
:::

:::step title="Confirm"
Monica says the app loses access at once and your other connections stay as they are. Choose **Disconnect**. Monica asks you to confirm it is you first.
:::
::::

Monica says the app is disconnected. Your other phones, your assistants and your API keys keep working. Logging out from the app does the same for that one connection.

## When an assistant reports a problem

When a tool of Monica fails, is missing or misleads an assistant, the assistant can send a report to the administrators of your Monica, so they can fix the tool. It is asked to describe your request in its own words and to leave out names, dates and other details from your vaults. The report says which account it came from.

Each report appears in your account log, and the call appears in **MCP history**, with what the assistant sent.
