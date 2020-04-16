# CardDAV and CalDAV <!-- omit in toc -->

**Using Monica as a CardDAV and CalDAV server**

- [Authentication](#authentication)
- [CardDAV and CalDAV urls](#carddav-and-caldav-urls)
- [Clients](#clients)
  - [Android](#android)
  - [iPhone](#iphone)
  - [Apple iOS](#apple-ios)
  - [Thunderbird](#thunderbird)
  - [Windows 10 Mail application](#windows-10-mail-application)
  - [Outlook (Microsoft Office)](#outlook-microsoft-office)


CardDAV is a protocol based on WebDAV, allowing you to **synchronize your contacts** between multiple devices (mobile phone, mail software, etc.).
CalDAV is pretty much the same, with Calendars. In Monica it allows you to synchronize the birthdays anniversary of your contacts, and the task list (which uses the same CalDAV protocol).

CardDAV and CalDAV for Monica are implemented with [sabre/dav](https://sabre.io/) library.


## Authentication

To authenticate with the server, you'll need to create an API token.

Go to the [Settings > API](https://app.monicahq.com/settings/api) page, and Create a new token.
![Create a token](/docs/images/carddav_token1.png)
![Create a token](/docs/images/carddav_token2.png)

Save this token to authenticate with CardDAV and CalDAV.

The login is you email login.

## CardDAV and CalDAV urls 

On the [Settings > DAV Resources](https://app.monicahq.com/settings/dav) page of you instance you will find some help about the URL to use.

**In most of the cases, the base url should work.** So just copy/paste it to your client app to see the magic happen !

![Base url](/docs/images/carddav_url.png)


## Clients

This is some example of clients configuration.

This list is not exhaustive, as the synchronisation can work on every CardDAV compatible device.



### Android

Android devices does not support CardDAV natively, so you'll need to install a third-party application to use CardDAV.

I recommend to install [DAVx5](https://www.davx5.com/) which is a great CardDAV client. You will find the application on the [Google Play store](https://play.google.com/store/apps/details?id=at.bitfire.davdroid) or even on [F-Droid store](https://f-droid.org/fr/packages/at.bitfire.davdroid/) for free.

To add an account:
- Click on the `+` button
- Choose **Connection with URL and username.**, and enter the following details:
  - **URL**: Enter the `/dav` base url, i.e. `https://app.monicahq.com/dav`
  - **Username**: Your email login address
  - **Password**: The token you've created on the API settings page.

![Davx5 config](/docs/images/carddav_davx5_1.png)

- Chose the option **Groups are per-contact categories**
- Click on **Connect**
- Select the data you want to sync

After that, you can use any Contacts application on your phone. Be sure to display your Monica account on the list of contacts, and to use it by default for new contacts.


### iPhone


### Apple iOS


### Thunderbird

[Thunderbird](https://www.thunderbird.net) does not support CardDAV natively, so you'll need to install a third party Add-on to use CardDAV.

I recommend to install [CardBook](https://addons.thunderbird.net/thunderbird/addon/cardbook/).
Download the add-on and install it through Thunderbird's add-on manager.

To add an account:
- Create a new Address Book.
- Choose **Remote**
- Choose **CardDAV** and enter the following details:
    - **URL**: Paste the `/dav` base url, i.e. `https://app.monicahq.com/dav`
    - **Username**: Your email login address
    - **Password**: The token you've created on the API settings page.
- Click **Validate** to check the credentials, then click on the **Next** button
- You can now see the address book, and the color to associate with. Select `4.0` as vCard format if you want.
- Click **Next** and **Finish**


### Windows 10 Mail application


### Outlook (Microsoft Office)
