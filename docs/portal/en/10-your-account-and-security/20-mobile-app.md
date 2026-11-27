---
id: security.mobile-app
title: Use Monica on your phone
description: Log in from the Monica app on Android or iOS, and sign a phone out if you lose it.
slug: mobile-app
section: your-account-and-security
last_updated: 2026-11-27
---

# Use Monica on your phone

The Monica app for Android and iOS reaches the same account as this website. You never type your password into the app: it opens the sign-in page of Monica in your phone’s browser, and you sign in there the way you always do, with your password, a passkey, a link sent by email or an outside provider, plus your second factor if you use one.

## Log in from the app

::::steps
:::step title="Choose your Monica"
The app connects to app.monicahq.com unless you change it. If you or somebody you know runs Monica on their own server, open the settings of the app and type its address, such as monica.example.com. The app checks that it is a Monica it can work with before keeping it.
:::

:::step title="Sign in"
Tap **Log in**. Your browser opens the sign-in page of your Monica. Sign in.
:::

:::step title="Allow the app"
Monica asks **Allow Monica for Android to reach your account?**, or **Allow Monica for iOS to reach your account?** on an iPhone. Choose **Allow** only if you just tapped **Log in** or **Sign up** in the app. Monica asks this every time a phone signs in.
:::
::::

The app opens, signed in. It stays signed in while you use it, even when your phone is offline for a while.

If you have no account yet, tap **Sign up** in the app instead. Monica opens its registration page, then brings you back to the app. When your Monica does not accept new accounts, the app tells you before opening anything.

## See the phones signed in

Open your settings and choose **Security and access**. **Mobile apps** lists each phone signed in through the app, with the name it gave itself, when it signed in and when it was last used. A phone you have not used for 30 days signs out on its own and leaves the list.

## Sign a phone out

Do this when you lose a phone, sell it, or no longer recognise it.

::::steps
:::step title="Start"
Choose **Sign out** on the phone.
:::

:::step title="Confirm"
Monica says the app on this phone is signed out at once and the other phones stay signed in. Choose **Sign out**. Monica asks you to confirm it is you first.
:::
::::

Monica says **The phone is signed out.** The app on it has to log in again before it can reach your account. Your other phones, your assistants and your API keys keep working. Logging out from the app does the same for that phone.
