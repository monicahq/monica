---
id: security.two-factor
title: Turn on two factor authentication
description: Add a six digit code from an authenticator app to your sign in, so a stolen password alone cannot open your account.
slug: two-factor-authentication
section: your-account-and-security
last_updated: 2026-11-20
---

# Turn on two factor authentication

A password can be guessed, reused or stolen. With two factor authentication on, Monica also asks for a six digit code from an app on your phone, a code that changes every thirty seconds and cannot be taken from a distance. Anybody who learns your password still cannot sign in without your phone.

You need an authenticator app on your phone, and any app works. A security key does the same job without a code, as explained below.

## Turn it on

::::steps
:::step title="Open your security settings"
Open your settings and choose **Security and access**. Under **Two-factor authentication**, next to **Authenticator app**, choose **Turn it on**. Monica asks you to confirm it is you first.

::screenshot{label="The Two-factor authentication section of the security settings, with an Authenticator app row and its Turn it on button" src="1-settings.security.index.webp"}
:::

:::step title="Pair your app"
In your authenticator app, add an account and point the camera at the square Monica shows. Without a camera, type the key shown under **No camera? Type this into the app instead.**
:::

:::step title="Prove it worked"
Type the six digits your app shows in **Six digit code**, then choose **Turn it on**.
:::

:::step title="Save your recovery codes"
Monica shows eight recovery codes. Save them now: they are shown only once. @doc(security.recovery-codes) explains them.
:::
::::

Nothing changes on your account until the code is accepted, so you cannot lock yourself out while setting it up. If Monica says **That code is not right**, check that the app shows the current code, and try again.

## Signing in with it on

After your password, a sign in link or an outside service, Monica shows **One more step**. Type the code your app shows in **Six digit code**, and choose **Verify**.

Monica does not ask for a code after a passkey, because a passkey already proves both that you hold the device and that you can unlock it.

If you lost your phone, type one of your recovery codes in the same field instead. Each works once.

## Use a security key instead of a code

A security key is something you touch rather than a code you type: a small USB or NFC key, or the fingerprint reader or screen lock of your computer or phone. It is a second step after your password, exactly like the app, and you can use one, the other, or both.

On **Security and access**, under **Two-factor authentication**, next to **Security keys**, choose **Add a security key**, then follow what your browser asks. The first key you add turns two factor authentication on and gives you recovery codes, the same way the app does.

When you sign in, **One more step** offers **Use a security key or passkey**. Touch your key, or unlock your device, when your browser asks.

If you remove your last key and no app is paired, two factor authentication turns off and your recovery codes stop working.

A security key only adds a step after your password. A passkey replaces the password and signs you in on its own: @doc(security.passkeys) explains it.

## Turn it off

On **Security and access**, choose **Turn it off**, then confirm.

:::warning
Turning it off forgets the pairing with your app and your recovery codes. Your password alone signs you in again. To turn it back on, you pair your app again from the start.
:::

Monica sends no email when you turn two factor authentication on or off.
