---
id: signin.provider
title: Sign in with GitHub, Google, Microsoft or Facebook
description: Use a GitHub, Google, Microsoft or Facebook account to sign in, and connect one to an account you already have.
slug: sign-in-with-a-provider
section: your-account-and-security
last_updated: 2026-11-10
---

# Sign in with GitHub, Google, Microsoft or Facebook

Use an account you already have elsewhere to sign in to Monica. Only the services your Monica offers appear, as **Continue with GitHub**, **Continue with Google**, and so on. If you see none, your Monica offers none.

A Monica can also offer Keycloak, Auth0, Okta, or the identity provider of an organisation, which appears under the name whoever hosts your Monica gave it.

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

## If you host Monica

A service is offered once every setting it needs is supplied, either in the environment or in the admin space, under **Signing in with an outside provider**. At the service, register the address `/sso/{name}/callback` of your Monica, where `{name}` is the name in the second column.

| Service | Name | What you supply |
| --- | --- | --- |
| GitHub | `github` | `GITHUB_CLIENT_ID`, `GITHUB_CLIENT_SECRET` |
| Google | `google` | `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET` |
| Microsoft | `entra` | `ENTRA_CLIENT_ID`, `ENTRA_CLIENT_SECRET`, and `ENTRA_TENANT` to accept one organisation only |
| Facebook | `facebook` | `FACEBOOK_CLIENT_ID`, `FACEBOOK_CLIENT_SECRET` |
| Keycloak | `keycloak` | `KEYCLOAK_SERVER_URL`, `KEYCLOAK_REALM`, `KEYCLOAK_CLIENT_ID`, `KEYCLOAK_CLIENT_SECRET` |
| Auth0 | `auth0` | `AUTH0_DOMAIN`, `AUTH0_CLIENT_ID`, `AUTH0_CLIENT_SECRET` |
| Okta | `okta` | `OKTA_DOMAIN`, `OKTA_CLIENT_ID`, `OKTA_CLIENT_SECRET`, and `OKTA_AUTH_SERVER` for a custom authorization server |
| Any other OpenID Connect provider | `openidconnect` | `OIDC_BASE_URL`, `OIDC_CLIENT_ID`, `OIDC_CLIENT_SECRET`, and `OIDC_NAME` for the name on the button |

Keycloak, Auth0, Okta, Microsoft and the last line use OpenID Connect. The service must be reached over https, publish a discovery document, and share an email address. Microsoft shares the sign in name, which Monica uses as the email address when it has the shape of one.
