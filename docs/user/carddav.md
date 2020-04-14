# CardDAV and CalDAV

**Using Monica as a CardDAV and CalDAV server**

---

CardDAV is a protocol based on WebDAV, allowing you to **synchronize your contacts** between multiple devices (mobile phone, mail software, etc.).
CalDAV is pretty much the same, with Calendars. In Monica it allows you to synchronize the birthdays anniversary of your contacts, and the task list (which uses the same CalDAV protocol).

CardDAV and CalDAV for Monica are implemented with [sabre/dav](https://sabre.io/) library.


## Authentication

To authenticate with the server, you'll need to create an API token.

Go to the [Settings > API](https://app.monicahq.com/settings/api) page of your instance, and Create a new token.
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

For Android devices, you'll need to install some extra application.

I recommend to install [DAVx5](https://www.davx5.com/) which works perfectly. You will find the application on the [Google Play store](https://play.google.com/store/apps/details?id=at.bitfire.davdroid) or even on [F-Droid store](https://f-droid.org/fr/packages/at.bitfire.davdroid/) (free).

After installation the application, add a new account, using the /dav url, and email + API token.

![Davx5 config](/docs/images/carddav_davx5_1.png)


### Apple iOS

_missing documentation_


### Thunderbird

For [Mozilla Thunderbird](https://www.thunderbird.net), you'll need to install some extra Add-on.

I recommend to install [CardBook](https://addons.thunderbird.net/thunderbird/addon/cardbook/).
Download the add-on and install it on thunderbird.



### Mail (Windows 10)


### Outlook (Office)
