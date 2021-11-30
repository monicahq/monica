# CardDAV and CalDAV <!-- omit in toc -->

**Using Monica as a CardDAV and CalDAV server**

- [Authentication](#authentication)
- [CardDAV and CalDAV urls](#carddav-and-caldav-urls)
- [Clients](#clients)
  - [Android](#android)
  - [iPhone](#iphone)
  - [Apple iOS](#apple-ios)
  - [Thunderbird](#thunderbird)
  - [Windows 10 Contacts application](#windows-10-contacts-application)
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

The login is your email login.

## CardDAV and CalDAV urls 

On the [Settings > DAV Resources](https://app.monicahq.com/settings/dav) page of your instance you will find some help about the URL to use.

**In most of the cases, the base url should work.** So just copy/paste it to your client app to see the magic happen !

![Base url](/docs/images/carddav_url.png)


## Clients

This is some example of clients configuration.

This list is not exhaustive, as the synchronisation can work on every CardDAV compatible device.



### Android

Android devices do not support CardDAV natively, so you'll need to install a third-party application to use CardDAV.

We recommend installing [DAVx5](https://www.davx5.com/) which is a great CardDAV client. You will find the application on the [Google Play store](https://play.google.com/store/apps/details?id=at.bitfire.davdroid) or even on [F-Droid store](https://f-droid.org/fr/packages/at.bitfire.davdroid/) for free.

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

[Thunderbird](https://www.thunderbird.net) supports CardDAV natively as of version 91.

For older versions, or enhanced functionality, we recommend installing [CardBook](https://addons.thunderbird.net/thunderbird/addon/cardbook/).
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


### Windows 10 Contacts application

Windows 10 Contacts application support CardDAV. It is used to synchronize iCloud kind account.

- Open **Contacts** application
- Click on **Import contacts** or **Add an account** on the parameters
- Choose **iCloud** account kind and enter the following details:
  - **User name**: your Monica email login address
  - **Name**: enter your full name
  - **Password**: write some scrap (do **not** enter Monica credentials for now)

    ![](/docs/images/windows10_contacts_1.png)

- Click on **Connect**, then **OK**


After this step, the application will try to synchronize with iCloud servers. It will fail, but it's normal as we don't have an account on it.

Fix the settings:
- Open the **Mail** application
- Open on the wheel ![wheel](/docs/images/windows10_wheel.png) to go to the settings. If the settings are not reachable, add a fake POP, IMAP account
- Click on your Monica account settings — it is named _iCloud_ at this point — and select **Change parameters**
- Enter the following details:
  - **User name**: your Monica email login address
  - **Password**: The token you've created on the API settings page
  - **Account name**: enter the description for this account, like "Monica"
  
    ![](/docs/images/windows10_contacts_2.png)

- Click on **Change mailbox sync settings, Options for syncing your content**, and change the following settings
  - **Download new email**: select `manually` as we don't sync emails here
  - **Sync options**: unselect **Email**, and select **Calendar** and **Contacts**
- Click on **Advanced mailbox settings, Contacts (CardDAV) and Calendar (CalDAV) server settings**, and change the following settings
  - **Incoming email server**: enter `localhost`
  - **Outgoing (SMTP) email server**: enter `localhost`
  - **Contacts server (CardDAV)**: Paste the `/dav` base url, i.e. `https://app.monicahq.com/dav`
  - **Calendar server (CalDAV)**: Paste the `/dav` base url, i.e. `https://app.monicahq.com/dav`

    ![](/docs/images/windows10_contacts_3.png)

- Click on **Done** button, then **Save**

Your contacts and calendar are now syncing.
- On **Contacts** application, be sure to display your Monica contacts account on the **Filter**
- On **Calendar** application, display your Contacts' Anniversary calendar


### Outlook (Microsoft Office)
