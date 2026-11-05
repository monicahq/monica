---
id: security.suspicious-activity
title: An email says somebody signed in
description: Find the subject of a security email from Monica, learn what it means, and see what to do if the sign in was not you.
slug: when-something-looks-wrong
section: your-account-and-security
last_updated: 2026-10-29
---

# An email says somebody signed in

Monica emails you when something happens to the way into your account. Most of the time it was you. This page helps you tell, and says what to do when it was not. Find the subject of the email you received below.

## Failed sign-in attempt on your Monica account

Somebody typed your address and the wrong password. If it was you, ignore it. If not, somebody knows your address and is guessing: @doc(security.password, "change your password") to one you use nowhere else, and @doc(security.two-factor, "turn on two factor authentication").

## You signed in to Monica without a password

Somebody used a sign in link sent to your address. If it was not you, whoever did it may have access to your mailbox. Secure your email account first, then change your Monica password.

## You signed in to Monica with a passkey

A passkey signed in. If it was not you, remove the passkeys you do not recognise, as @doc(security.passkeys) shows, and change your password.

## You signed in to Monica with Google

The name of the service varies: GitHub, Google, Microsoft or Facebook. If it was not you, secure your account on that service, then disconnect it from Monica, as @doc(signin.provider) shows.

## A sign-in from a new place on your Monica account

Your account was signed in to from an internet address Monica had not seen for your last sign in. Monica compares only that address with the previous one: it knows nothing about the device or the country. Travelling, a VPN, a new phone or even your internet provider changing your address can all cause it, so it is often nothing. If you do not recognise the time, change your password.

## A passkey was added, or removed, from your Monica account

If you did not do it, somebody else may have reached your account. Change your password now, and remove any passkey you do not recognise.

## A password was set, changed or removed

The subjects are **A password was set on your Monica account**, **Your Monica password was changed** and **Your Monica password was removed**. If you did not do it, @doc(signin.forgot-password, "ask for a link to choose a new password") straight away, and check your passkeys and connected services.

## Google was disconnected from your Monica account

A service no longer signs you in. Nothing else changed. If you did not do it, connect it again from your security settings.

## Secure your account

If you think somebody else reached your account:

1. @doc(security.password, "Change your password") to one you use nowhere else.
2. Under **Where you are signed in**, in **Security and access**, remove any device you do not recognise. It is asked to sign in again at its next request. To end every session but the one you are using, choose **Sign out everywhere else** and type your password.
3. Remove any passkey, API key or connected assistant you do not recognise, in the same place.
4. @doc(security.two-factor, "Turn on two factor authentication").
5. Read @doc(account.logs, "your activity log") to see what was done.
