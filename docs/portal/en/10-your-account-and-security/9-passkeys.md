---
id: security.passkeys
title: Add and remove passkeys
slug: passkeys
section: your-account-and-security
last_updated: 2026-11-01
---

# Add and remove passkeys

A passkey is kept on your phone, your computer or a security key, and unlocked by your fingerprint, your face or the device’s PIN. There is nothing to type, so nothing can be phished. Once you add one, you can @doc(signin.passkey, "sign in with it").

Passkeys need a secure connection, an address starting with `https`. Without one, the **Passkeys** section of your security settings says so, and offers nothing else.

## Add a passkey

::::steps
:::step title="Open your security settings"
Open your settings and choose **Security and access**.
:::

:::step title="Add it"
Under **Passkeys**, choose **Add a passkey**. Monica asks you to confirm it is you first.
:::

:::step title="Confirm on your device"
Your browser or device asks you to create the passkey, with your fingerprint, your face or its lock.
:::
::::

The passkey is listed with the name of the device it lives on, and when it was added and last used. Monica emails you with the subject **A passkey was added to your Monica account**.

If you close the device’s prompt, Monica says **No passkey was added. The prompt was dismissed.** If this device already has one, it says **This device already has a passkey here.**

## Rename a passkey

Choose **Rename** on the passkey, type a name you will recognise, such as `Work laptop`, and choose **Save**.

## Remove a passkey

Choose **Remove** on the passkey, then **Remove** again to confirm. That device no longer signs you in. Monica emails you with the subject **A passkey was removed from your Monica account**.

:::warning
If it is your last passkey and your account has no password, a link sent to your email address becomes the only way to sign in. Monica warns you before you remove it. Set a password first, as @doc(security.password) shows, if you want another way in.
:::

## Sign in with passkeys only

With a passkey on a device you hold, you can remove your password altogether, so there is nothing left to phish. See @doc(security.password).
