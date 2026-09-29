---
id: signin.provider
title: Sign in with GitHub, Google, Microsoft or Facebook
slug: sign-in-with-a-provider
section: your-account-and-security
---

# Sign in with GitHub, Google, Microsoft or Facebook

Use an account you already have elsewhere to sign in to Monica. Only the services your Monica offers appear, as **Continue with GitHub**, **Continue with Google**, and so on. If you see none, your Monica offers none.

## Sign in

On the sign in screen, choose **Continue with**, followed by the service. Sign in there if it asks, and allow Monica to see your name and email address. You come back to Monica, signed in. If you turned on two factor authentication, Monica asks for a code first.

Monica emails you each time, with a subject such as **You signed in to Monica with Google**.

## The first time

If nobody has an account with the address the service gives, Monica creates one for you, named after the name the service shares, with no password. It then asks you to confirm your address, as @doc(start.create-account) explains.

If an account already uses that address, Monica refuses and says **An account already uses that email address. Sign in the way you usually do, then connect Google from your security settings.** This stops somebody from reaching your account through a service account with your address.

## Connect a service to your account

::::steps
:::step title="Open your security settings"
Open your settings and choose **Security and access**.
:::

:::step title="Connect"
Under **Outside providers**, choose **Connect** next to the service. Monica asks you to confirm it is you first.
:::

:::step title="Allow it"
Sign in to the service if it asks, and allow Monica. You come back to Monica, which says **Google now signs you in.**
:::
::::

Your account can be connected to one service at a time. To switch, disconnect the current one first.

## Disconnect a service

Under **Outside providers**, choose **Disconnect**, then confirm. You sign in with your password or a passkey from then on, and Monica emails you with the subject **Google was disconnected from your Monica account**.

You can disconnect a service only when your account has another way in. If it has neither a password nor a passkey, Monica says **Set a password or add a passkey before you disconnect it.** See @doc(security.password) or @doc(security.passkeys).
