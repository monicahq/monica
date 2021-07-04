## [3.1.3](https://github.com/monicahq/monica/compare/v3.1.2...v3.1.3) (2021-06-28)


### Bug Fixes

* fix layout selection ([#5313](https://github.com/monicahq/monica/issues/5313)) ([8b4821f](https://github.com/monicahq/monica/commit/8b4821f1393a1fad31d3ba3cf30679d483197664))
* use post request for exportToSql ([#5314](https://github.com/monicahq/monica/issues/5314)) ([cefeb9b](https://github.com/monicahq/monica/commit/cefeb9bdfa74e30ff77ff692c3d14c05ae081bff))

## [3.1.2](https://github.com/monicahq/monica/compare/v3.1.1...v3.1.2) (2021-06-24)


### Bug Fixes

* fix search being extremely slow ([#5306](https://github.com/monicahq/monica/issues/5306)) ([8ba7d98](https://github.com/monicahq/monica/commit/8ba7d983efcef7555b620cb1a0bbc32db3835f00))

## [3.1.1](https://github.com/monicahq/monica/compare/v3.1.0...v3.1.1) (2021-06-23)


### Bug Fixes

* fix search with additional info ([#5301](https://github.com/monicahq/monica/issues/5301)) ([13325cc](https://github.com/monicahq/monica/commit/13325cc8c1f32b391905a35abb166f843faef142))

# [3.1.0](https://github.com/monicahq/monica/compare/v3.0.1...v3.1.0) (2021-06-22)


### Features

* add a console command to see memcached stats ([#5186](https://github.com/monicahq/monica/issues/5186)) ([b359c90](https://github.com/monicahq/monica/commit/b359c90206bc4bddbd87fe8ad7a11993304a8542))
* add a rate limiter for locationiq queries ([#5185](https://github.com/monicahq/monica/issues/5185)) ([f8442ba](https://github.com/monicahq/monica/commit/f8442ba507181d425e3dafb6a06555a15518b384))
* add Indonesian language ([#5190](https://github.com/monicahq/monica/issues/5190)) ([16cd47e](https://github.com/monicahq/monica/commit/16cd47e925144b3ec20c0ad851bb7bd595af4438))
* add new logging stack for papertrail+errorlog ([#5166](https://github.com/monicahq/monica/issues/5166)) ([744efb0](https://github.com/monicahq/monica/commit/744efb0e6bfbe3f452999285583b290e21c8b61c))
* add notes when importing vcard ([#5216](https://github.com/monicahq/monica/issues/5216)) ([36912bc](https://github.com/monicahq/monica/commit/36912bc5ef7d06f281e555f93f42d91fbb7ccb63))
* allow recovery codes when disabling 2FA ([#4970](https://github.com/monicahq/monica/issues/4970)) ([1f4c4c4](https://github.com/monicahq/monica/commit/1f4c4c4b6c2c39dc4917600220d78a44580d1327))
* datestamp filename of exported SQL file. ([#5136](https://github.com/monicahq/monica/issues/5136)) ([a658fcf](https://github.com/monicahq/monica/commit/a658fcf074b36ba6a8855ecaa7b3c13a3e78888d))
* download and get storage files as private ([#5192](https://github.com/monicahq/monica/issues/5192)) ([7fdc445](https://github.com/monicahq/monica/commit/7fdc4453b688781a651f09d9b6cbc274ac3fbdbb))
* email field on add person ([#5097](https://github.com/monicahq/monica/issues/5097)) ([2392afc](https://github.com/monicahq/monica/commit/2392afc0aaa9d5f79d5f3f6357912c70a4d96ca1))
* make archived contact readonly ([#5285](https://github.com/monicahq/monica/issues/5285)) ([a3fdac9](https://github.com/monicahq/monica/commit/a3fdac949f662d08ced6e02bcf63fd5106600fb0))
* search notes when searching through contacts ([#5103](https://github.com/monicahq/monica/issues/5103)) ([6378bc1](https://github.com/monicahq/monica/commit/6378bc183df414175a9aee49c94780247ef27b94))


### Bug Fixes

* fix import vcard stability ([#5160](https://github.com/monicahq/monica/issues/5160)) ([3f2821d](https://github.com/monicahq/monica/commit/3f2821d75a4d2451f397d98d4095547d520f660e))
* fix importvcard job ([#5151](https://github.com/monicahq/monica/issues/5151)) ([cf8041c](https://github.com/monicahq/monica/commit/cf8041cfe7544889c7d4c28e5b1e27cd1671bbd0))
* fix name order selection and result  ([#5255](https://github.com/monicahq/monica/issues/5255)) ([d3217c0](https://github.com/monicahq/monica/commit/d3217c067e642614c3f2176e963f41d77a1aed29))
* fix stripe pages stability ([#5161](https://github.com/monicahq/monica/issues/5161)) ([53977cc](https://github.com/monicahq/monica/commit/53977cc9eb24e9a14d0ec8d8419c54595e680857))
* fix tags list filtering ([#5123](https://github.com/monicahq/monica/issues/5123)) ([99bd8e1](https://github.com/monicahq/monica/commit/99bd8e17f8ac78af45937673a38a15c72f1e0278))
* fix unarchive on limited account ([#5256](https://github.com/monicahq/monica/issues/5256)) ([8357d0f](https://github.com/monicahq/monica/commit/8357d0f57907d0fe568db1b51defece6d65b4ad0))
* fix vcard import to generate avatars ([#5193](https://github.com/monicahq/monica/issues/5193)) ([6323a5d](https://github.com/monicahq/monica/commit/6323a5d4cd4207eba9d0d46a3f2f6a425915afbe))
* left trim url if there is a trailing slash ([#5149](https://github.com/monicahq/monica/issues/5149)) ([56572bb](https://github.com/monicahq/monica/commit/56572bbd576bdc45eaa8ebf5b2f27c7aab6c8a9d))
* package.json & yarn.lock to reduce vulnerabilities ([#5269](https://github.com/monicahq/monica/issues/5269)) ([9c111c3](https://github.com/monicahq/monica/commit/9c111c3427e9ca2a8e9e84cf336bdd97cb67bee2))

## [3.0.1](https://github.com/monicahq/monica/compare/v3.0.0...v3.0.1) (2021-05-02)


### Bug Fixes

* fix deploy on fortrabbit with version number ([#5139](https://github.com/monicahq/monica/issues/5139)) ([c5394af](https://github.com/monicahq/monica/commit/c5394af9bc30207a9158488c7617f3bb265a3c72))
* fix import job without subscription bypass ([#5147](https://github.com/monicahq/monica/issues/5147)) ([fbac248](https://github.com/monicahq/monica/commit/fbac24891a9ace9f9c88fd4d23b8612243af283a))

# [3.0.0](https://github.com/monicahq/monica/compare/v2.22.1...v3.0.0) (2021-04-30)


### Features

* remove assets from repository (see [#4759](https://github.com/monicahq/monica/issues/4759)) ([#5133](https://github.com/monicahq/monica/issues/5133)) ([02ba369](https://github.com/monicahq/monica/commit/02ba3694929154ecdafbe95fa34ad6920680b6b7))


### BREAKING CHANGES

* The assets are no longer embedded in source code: javascript, css, font files. Run `yarn install` then `yarn run production` to recreate them from sources, or download a [release file](https://github.com/monicahq/monica/releases) that contains compiled files.
* For Heroku users: You'll have to manually go to `Settings` > `Buildpacks` and add buildpack: `nodejs`. See [this doc](https://github.com/monicahq/monica/blob/master/docs/installation/providers/heroku.md#update-from-2x-to-3x).
* See more information about how to install a Monica instance [here](https://github.com/monicahq/monica/tree/master/docs/installation).

## [2.22.1](https://github.com/monicahq/monica/compare/v2.22.0...v2.22.1) (2021-04-30)


### Code Refactoring

* remove assets from repository ([#4759](https://github.com/monicahq/monica/issues/4759)) ([570dde1](https://github.com/monicahq/monica/commit/570dde1a13096c8e15fa436eae99ddc572486922))


### BREAKING CHANGES

* The assets are no longer embedded in source code: javascript, css, font files. Run `yarn install` then `yarn run production` to recreate them from sources, or download a [release file](https://github.com/monicahq/monica/releases) that contains compiled files.
* For Heroku users: You'll have to manually go to `Settings` > `Buildpacks` and add buildpack: `nodejs`. See [this doc](https://github.com/monicahq/monica/blob/master/docs/installation/providers/heroku.md#update-from-2x-to-3x).
* See more information about how to install a Monica instance [here](https://github.com/monicahq/monica/tree/master/docs/installation).

# [2.22.0](https://github.com/monicahq/monica/compare/v2.21.0...v2.22.0) (2021-04-30)


### Bug Fixes

* fix bypass account limitation to create more contacts ([#5125](https://github.com/monicahq/monica/issues/5125)) ([3d66188](https://github.com/monicahq/monica/commit/3d66188350f107094309d3dcd62b8202aad25004))
* fix bypass invitation ([#5127](https://github.com/monicahq/monica/issues/5127)) ([d889475](https://github.com/monicahq/monica/commit/d88947523094d7159a937033f3a4ab05380fb4a9))
* fix stripe page ([#5113](https://github.com/monicahq/monica/issues/5113)) ([caa5bef](https://github.com/monicahq/monica/commit/caa5bef93bed33d269ef1260c805e0ffaffd08fa))


### Features

* create a new stacked log channel ([#5122](https://github.com/monicahq/monica/issues/5122)) ([71c3789](https://github.com/monicahq/monica/commit/71c3789b6013dcea5a3e856b5f6e52c32769b1f4))
* display gifts date ([#5081](https://github.com/monicahq/monica/issues/5081)) ([a478fd8](https://github.com/monicahq/monica/commit/a478fd8f4394ee650a89bbf62610e973fb98c03a))

# [2.21.0](https://github.com/monicahq/monica/compare/v2.20.0...v2.21.0) (2021-04-25)


### Features

* add ability to attach dates to gifts ([#4909](https://github.com/monicahq/monica/issues/4909)) ([da17b8d](https://github.com/monicahq/monica/commit/da17b8d1b48d894443fad165868086c4d04eb94d))
* add date of creation in journal ([#4949](https://github.com/monicahq/monica/issues/4949)) ([be85cad](https://github.com/monicahq/monica/commit/be85cadd2abf38aba2353f629fac1733dd922e9b))


### Bug Fixes

* fix udpate maintenance mode message ([#4983](https://github.com/monicahq/monica/issues/4983)) ([225e68e](https://github.com/monicahq/monica/commit/225e68e038349afe1c86631dca04297b8ba72955))
* sort and group relationships by relationship type ([#4985](https://github.com/monicahq/monica/issues/4985)) ([105b74f](https://github.com/monicahq/monica/commit/105b74f94e7f6b08da3883020670c9b8e3c72df0))

# [2.20.0](https://github.com/monicahq/monica/compare/v2.19.1...v2.20.0) (2021-03-18)


### Bug Fixes

* catch fatal error during install hooks ([#4642](https://github.com/monicahq/monica/issues/4642)) ([1c63ea0](https://github.com/monicahq/monica/commit/1c63ea0b3088e7701c4326bce365a8f09491d6f3))
* fix add gender type ([#4548](https://github.com/monicahq/monica/issues/4548)) ([c0561ce](https://github.com/monicahq/monica/commit/c0561cef7b6c296d41ee42405dec46dae8bb34af))
* fix broken stay in touch frequency input ([#4969](https://github.com/monicahq/monica/issues/4969)) ([500ecc8](https://github.com/monicahq/monica/commit/500ecc830282c79170121c2666a71025aaa65721))
* fix checkbox UI issue in invite user page ([#4546](https://github.com/monicahq/monica/issues/4546)) ([827154e](https://github.com/monicahq/monica/commit/827154e9bebb3a545c71eb73ef204feeaf59ed07))
* fix contact list description display & UI column names ([#4891](https://github.com/monicahq/monica/issues/4891)) ([aa090f8](https://github.com/monicahq/monica/commit/aa090f89846cc5323005636ca77b294953f2c5de))
* fix date missing on journal api ([#4905](https://github.com/monicahq/monica/issues/4905)) ([8de23ba](https://github.com/monicahq/monica/commit/8de23ba01b5b796cc7c9fbe4d22b4f8b8d2d8cd9))
* fix date you met update UX ([#4511](https://github.com/monicahq/monica/issues/4511)) ([288e3d0](https://github.com/monicahq/monica/commit/288e3d0af5bcd331ac83836f25d5b347194f11c9))
* fix docker build ([#4733](https://github.com/monicahq/monica/issues/4733)) ([4fa4561](https://github.com/monicahq/monica/commit/4fa4561c2c4b34d3f39c5266d1581fa2cd9ee75a))
* fix oauth login bad credentials ([#4688](https://github.com/monicahq/monica/issues/4688)) ([28d4cc9](https://github.com/monicahq/monica/commit/28d4cc94bb339e4345ae1a0d9c1a4f45716707ff))
* fix passport setup migration ([#4606](https://github.com/monicahq/monica/issues/4606)) ([e17b89b](https://github.com/monicahq/monica/commit/e17b89b656ea6d002f8347af75aebb631c4d0e4f))
* fix subscriptions list display ([#4967](https://github.com/monicahq/monica/issues/4967)) ([ca21705](https://github.com/monicahq/monica/commit/ca217056bb375d38b4673f6fcfb4db758b775298))
* fix the adorable url migration ([#4963](https://github.com/monicahq/monica/issues/4963)) ([ed2b3b7](https://github.com/monicahq/monica/commit/ed2b3b7667b8ea2c65bd151b4fe0c75364418eb8))
* fix the adorable url migration (again) ([#4964](https://github.com/monicahq/monica/issues/4964)) ([5894065](https://github.com/monicahq/monica/commit/5894065059e5877ba4486c64bc746378ac655fdb))
* update activity with emotions ([#4459](https://github.com/monicahq/monica/issues/4459)) ([d4adb4f](https://github.com/monicahq/monica/commit/d4adb4f206c7637ae5bd21f39009568e0ca639c3))
* update adorable api to api.hello-avatar.com ([#4778](https://github.com/monicahq/monica/issues/4778)) ([527131e](https://github.com/monicahq/monica/commit/527131e4e72f7a0deea5d4a9d8025a6d1a9d15fa))


### Features

* add a confirmation to delete a journal entry [#4308](https://github.com/monicahq/monica/issues/4308) ([#4514](https://github.com/monicahq/monica/issues/4514)) ([18fadb7](https://github.com/monicahq/monica/commit/18fadb77ce7fb4b46fd71ae8205127b9e8c9581d))
* add Android icon for use when bookmarking ([#4798](https://github.com/monicahq/monica/issues/4798)) ([dcee3a9](https://github.com/monicahq/monica/commit/dcee3a943212476f2e96e5c54165daae244c47fc))
* add Apple icons for use when bookmarking. ([#4743](https://github.com/monicahq/monica/issues/4743)) ([a28adcd](https://github.com/monicahq/monica/commit/a28adcdd7d13330271d198c470b6985eee39df11))
* add artisan command to create new account ([#4745](https://github.com/monicahq/monica/issues/4745)) ([b9ee793](https://github.com/monicahq/monica/commit/b9ee793669562c8bf44bd57322e9f5126b2af998))
* add notion of addressbooks ([#3749](https://github.com/monicahq/monica/issues/3749)) ([a18962e](https://github.com/monicahq/monica/commit/a18962ecbf09cb222ac943f8be19362985a7235a))
* add Swedish language ([#4652](https://github.com/monicahq/monica/issues/4652)) ([e1edcad](https://github.com/monicahq/monica/commit/e1edcad04b5cdee0c61883db0d64cf2ca9e9369c))
* allow customization of life event types ([#4243](https://github.com/monicahq/monica/issues/4243)) ([657d824](https://github.com/monicahq/monica/commit/657d824273e8eedc01ed099576571d47d0e26017))
* default gender to unknown ([#4753](https://github.com/monicahq/monica/issues/4753)) ([ebf7c08](https://github.com/monicahq/monica/commit/ebf7c085dd786b174055be389ba5fef35fae861a))
* set and clear personal description now appears in change log ([#4893](https://github.com/monicahq/monica/issues/4893)) ([686a0a1](https://github.com/monicahq/monica/commit/686a0a1f0b2dbbee91fef41eca318a3b9fbd48ff))


## v2.19.1 - 2020-09-12

### Fixes:

* Fix journal entry XSS vulnerability


## v2.19.0 - 2020-08-27

### Enhancements:

* Update tag management on the contact profile
* Add next and previous arrows when viewing photos
* Add dependency to php imagick module
* Renamed MOBILE_CLIENT_ID and MOBILE_CLIENT_SECRET variables to PASSPORT_PERSONAL_ACCESS_CLIENT_ID and PASSPORT_PERSONAL_ACCESS_CLIENT_SECRET

### Fixes:

* Fix amount display on subscription account settings
* Fix exception when registering in certain cases
* Fix vue-select usage


## v2.18.0 - 2020-05-23

### New features:

* Display age of death to relationship sidebar if the person is dead
* Crop contact photos on upload
* Add new name orders \<nickname> (\<First name> \<Last name>) & \<nickname> (\<Last name> \<First name>)
* Add console command to test email delivery
* Add Traditional Chinese language
* Add Japanese language
* Change title of birthday reminder for deceased people

### Enhancements:

* Change docker image sync
* Stores amount as integer-ish values, and fix debts and gifts amount forms
* Use current text from search bar to create a new person
* Always allow to add a new person from search bar
* Use queue to send email verification
* Improve autocomplete fields on signup and login forms
* Add cache for S3 storage, and use new standard variables
* Remove authentication with login+password for carddav
* Add new command monica:passport to generate encryption if needed
* Improve nginx config docker examples
* Remove u2f support (replaced with WebAuthn)
* Serialize photo content in VCard photo value

### Fixes:

* Fix life event categories and types are not translated when adding new life event
* Fix subdirectory config url
* Fix google2fa column size
* Fix errors display for api
* Fix currency in double
* Fix authentication with token on basic auth
* Fix editing multiple notes at the same time only edits one note
* Fix countries in fake contact seeder
* Fix docker rsync exclude rules
* Fix docker cron (legacy) on apache variant
* Fix login route already set by Laravel now
* Fix setMe contact controller
* Fix carddav sync-collection reporting wrong syncToken


## v2.17.0 - 2020-03-22

### New features:

* Add a weekly job to update gravatars
* Add ability to set 'me' contact
* Add middle name field to new contact and edit contact
* Add backend and api for contact field labels
* Add audit log when setting a contact's description
* Add support for audit logs on a contact page
* Add support for audit logs in the Settings page
* Add vue data validations
* Add ability to edit activities
* Associate a photo to a gift
* New API method: get all the contacts for a given tag

### Enhancements:

* Use Carbon v2 library as translator for dates
* Contacts displayed in the activity list are now clickable again
* Gift are now added and updated inline
* Add a link in the downgrade process to archive all contacts in the account

### Fixes:

* Fix dates being off by one day
* Fix wrong untagged contacts counter when viewing untagged contacts
* Fix markdown doesn't work on journal activity entries
* Fix markdown doesn't work on Activity entries
* Fix summary of activities showing the same date for every entry
* Fix vcard categories import/export as tags
* Fix resend email verification feature not sending email
* Fix edit conversation date not being editable
* Fix display of the toggle buttons in the Settings page
* Fix how you met date not being deleted upon save
* Fix description not being saved when creating/editing activity
* Markdown is now properly applied for a phone call description
* Fix contacts list UX with 2 tabs opened
* Fix activity mock data seeder
* Fix ordering of contact tags to be alphabetical


## v2.16.0 - 2019-12-31

### New features:

* Save contact tags in vCard 'CATEGORIES' field

### Enhancements:

* Activities are now added inline
* Improve modals bottom buttons display
* Add foreign keys to all tables
* Add English (UK) locale
* Add API methods to destroy and store documents
* Add API methods to manage photos and avatars
* Add emotions and participants to activities
* Enable API web navigation
* Enhance UI of API's Settings to add comprehension and documentation
* Improve trim string middleware to not trim password text
* Upgrade to Laravel 6.x
* Enhance user invitation mail
* Add job information next to the contact name on profile page
* Use supervisor in docker images
* Use JawsDB by default on heroku instances
* Add pluralization forms for non-english-like-plural languages, for vue.js translations
* Upload master docker image to GitHub packages

### Fixes:

* Fix contact list cells link
* Fix birthdate selection UX
* Fix OAuth login process with WebAuthn activated
* Fix journal entry edit
* Fix register in case country is not detected from ip address
* Fix Photo->contact relation
* Fix subscription page
* Fix relationship create and destroy with partial contact
* Fix 2fa route on webauthn page
* Fix tooltip on favorite icon
* Fix icons disappeared on contact information
* Fix CSV uploads with weird photo files
* Ensure disable_signup is checked on form register validation
* Fix password resetting page
* Fix email verification sending on test environments
* Fix contact export
* Fix currencies seeder by accounting for defaults
* Fix search when prefix table is used
* Fix storage page not being displayed if a contact does not exist anymore
* Fix API requests for Reminders failing with internal server error

## v2.15.2 - 2019-09-26

### Enhancements:

* Revert depends on php7.2+


## v2.15.1 - 2019-09-24

### Fixes:

* Fix people header file
* Fix query and scope searches with table prefix
* Remove monica:clean command confirmation


## v2.15.0 - 2019-09-22

### New features:

* Paginate the Contacts page and improve database performance
* Add ability to edit a Journal entry
* Add vcard photo/avatar import
* Add ability to change the avatar of your contacts
* Add the ability to set a 'me' contact (only API for now)
* Add stepparent/stepchild relationship

### Enhancements:

* Docker image: create passport keys for OAuth access
* Reduce a lot of queries
* Update to laravel cashier 10.0, and get ready with SCA/PSD2
* Add stripe webhook
* Depends on php7.3+
* Use pretty-radio and optimize vue.js components
* Hide stay-in-touch for deceased contacts

### Fixes:

* Fix query and scope search
* Reschedule missed stay-in-touch
* Fix tasks 'mark as done' UX
* Fix tattoo or piercing activity locale title
* Fix getting infos about country without providing ip
* Fix migration and contact delete in case a DB prefix is used
* Fix partial/real contact edit on relationship
* Fix same contact selection in multi-search
* Fix conversation creation
* Fix phone call update
* Fix conversation list show
* Fix subscription cancel
* Fix last consulted contact list
* Fix exception in case a user register twice
* Fix vcard export with empty gender
* Fix touch contact's updated_at on stay in touch trigger job
* Fix relationship list view
* Fix relationship id with no gender
* Fix some UX errors
* Fix stripe payment UI
* Fix datepicker for locale usage


## v2.14.0 - 2019-05-16

### New features:

* Add WebAuthn Multi-factor authentication
* Add multi factor auth on oauth

### Enhancements:

* Add Swiss CHF currency
* Add ability to enable DAV for some users
* Group relationships in create/edit forms
* Rewrite contact search fields
* Use string and array classes instead of helpers

### Fixes:

* Fix dav url on dav settings page
* Fix debt direction on debt edit
* Fix schedule run in case cron can't run on fix hours
* Fix contact create with birthdate age 0
* Fix contact link create on job queue
* Fix /settings/dav route
* Fix display relationship without a ofContact property
* Fix register request validate
* Fix relationship create


## v2.13.0 - 2019-04-07

### Enhancements:

* Add ability to update a relationship
* Add a sex type behind the gender
* Make gender optional on a contact profile
* Add a Collection::sortByCollator macro

### Fixes:

* Fix destroy relationship
* Fix event dispatch for login (google2fa, u2f) events handle
* Fix address input label mistake
* Fix dashboard crash when reminder is empty
* Fix import vCard with Cyrillic encoding
* Fix import/export vcard with birthday with year unknown
* Fix contact missing create form
* Fix money format for non two "2" minor unit currencies


## v2.12.1 - 2019-03-09

### Enhancements:

* Add eloquent relationships touches

### Fixes:

* Fix reminders not being sent
* Fix setting deceased information with removing date and reminder
* Fix contact information update
* Fix adding people on activity create and update
* Fix setting a relationship without selecting any birthdate option
* Fix several typos in English language files
* Fix Journal view now includes Activities as intended
* Fix deleting a LifeEvent no longer deletes the associated Contact


## v2.12.0 - 2019-02-09

### New features:

* Support CalDAV to export the collection of birthdays (breaking change: url of CardDAV is '/dav' now)
* Add a page in settings to display all DAV resources
* Add notion of instance administrator for a user
* Add ability to name u2f security keys and to delete register ones
* Add ability to add a comment when rating your day in the journal
* Add API methods to manage genders
* Breaking change: rewrite API methods to manage contacts

### Enhancements:

* Don't change timestamps on contact number_of_views update
* Redirect to the related real contact when trying to display a partial contact
* Use iterator reader for vcard imports
* Accept last name when using contact search field
* Register all app services as singleton
* Docker image: add sentry-cli and run sentry:release command if sentry is enabled
* Refactor reminders by removing Notifications table and creating two new tables: reminder outbox and reminder sent
* Shorten the value of the contact field if it does not fit into the contact field information
* Add foreign keys to activities table
* Add foreign keys to reminders, reminder rules, contacts and life events tables
* Add number of life events on the contact profile page
* Add base HTML tag and tweak all assets and urls to use relative paths
* Refactor activity types with services
* Refactor activity type categories with services

### Fixes:

* Fix addresses and contact fields imports on VCard import
* Remove users without an existing account in the accounts table
* Fix case when schedule date is null
* Add phpstan analyser, and fix a lot of issues
* Fix middleware priority order to always set locale after authenticate
* Accept lastname_firstname name order for VCard imports (FN field)
* Fix vue.js DateTime picker to type a date in other format than en-us one
* Fix DateTime parse when compact format is used
* Fix contact and relationship edit with reminder enabled
* Fix broken migration for the activities table
* Fix VCard import with partial N entry
* Fix using 'label' tag without 'for' attribute
* Fix model binding when it is a guest request (not logged in)
* Fix bug preventing to create life event without day and month
* Fix ability to delete a user with a u2f key activated
* Fix validation fails with Services
* Fix getting birthday reminders about related contacts
* Fix default temperature scale setting
* Fix API methods for Occupation object
* Fix activity date viewed as one day before the event happened
* Fix settags api call with an empty tag


## v2.11.2 - 2019-01-01

* Carddav: support sync-token (rfc6578)
* Fix premium feature flag appearing on self-hosted version
* Fix exception when user is logged out (again)
* Fix carddav group-member-set propfind call
* Fix contacts view in case birthdate returns null
* Fix conversation without message


## v2.11.1 - 2018-12-26

* Migrate LinkedIn url from the Contact object to a ContactFieldType object
* Activate eslint to check vue and javascript formatting
* Fix tasks store and update
* Fix error handling in vue components
* Fix exception when user is logged out
* Fix subscription plan display
* Fix dashboard calls display
* Fix tags getting error
* Fix contact getIncompleteName to work with UTF-8 last_name characters
* Fix associate null tags


## v2.11.0 - 2018-12-23

* Add ability to indicate temperature scale (Fahrenheit/Celsius) on the Settings page
* Add ability to see the current weather on the contact profile page
* Add ability to generate recovery codes in order to bypass 2FA/U2F
* Add ability to indicate latitude and longitude to addresses
* Add ability to upload photos
* Add ability to indicate how you felt when logging a call
* Add information about who initiated a phone call
* Add ability to edit a phone call
* Add ability to create tasks that are not linked to any contacts
* Remove limitation on the date field when creating an activity
* Fix Set Tag api method which deleted existing tags, which it shouldn't
* Fix editing relationship not working
* Fix Storage page not being displayed
* Fix VCard import without firstname
* Fix avatar display in searches
* Fix conversation add/update using contact add/update flash messages
* Fix incompatibility of people search queries with PostgreSQL
* Refactor how contacts are managed
* Add the notion of places


## v2.10.2 - 2018-11-14

* Fix composer install problems
* Fix editing conversations not working
* Fix deletion of relationships not working


## v2.10.1 - 2018-11-13

* Fix work information not being able to be edited
* Display contacts for each tag in the Tags view on the Settings page


## v2.10.0 - 2018-11-11

* Add ability to upload documents
* Add ability to archive a contact
* Add right-click support on contact list
* Add autocompletion on tags
* Add CardDAV support — disabled by default. To enable it, toggle the `CARDDAV_ENABLED` env variable.
* Add a command (export:all) to export all data from an instance in SQL
* New header on a profile page
* Standardize phonenumber format while importing vCard
* Set currency and timezone for new users
* Remove changelogs from the database and manage changelogs from a json file instead
* Highlight buttons when selected using keyboard
* Hide deceased people from dashboard's 'Last Consulted' section
* Improve API methods for tag management
* Fix settings' sidebar links and change security icon
* Fix CSV import
* Filter deceased people from people list by default
* Fix errors during PostgreSQL migration
* Better documentation for PostgreSQL users
* Fix some API methods
* API breaking change: Remove 'POST /contacts/:contact_id/pets' in favor of 'POST /pets/' with a 'contact_id'
* API breaking change: Remove 'PUT /contacts/:contact_id/pets/:id' in favor of 'PUT /pets/:id' with a 'contact_id'
* API breaking change: Every validator fails now send a HTTP 400 code (was 200) with the error 32
* API breaking change: Every Invald Parameters errors now send a HTTP 400 (was 500 or 200) code with the error 41
* Use Laravel email verification, and remove the old package used for that
* Prevent submitting an empty form when pressing enter
* Remove Antiflood package on oauth/login and use Laravel throttle


## v2.9.0 - 2018-10-14

* Allow to define a max file size for uploaded document in an ENV variable (default to 10240kb)
* Add description field for a contact
* Add ability to retrieve all conversations for one contact through the API
* Add all tasks not yet completed on the dashboard
* Fix gravatar not displayed on dashboard view


## v2.8.1 - 2018-10-08

* Add ability to set a reminder for a life event
* Stop reporting OAuth exceptions
* Replace karakus/laravel-cloudflare with monicahq/laravel-cloudflare to fix dependencies issues
* Fix use of 'json' mysql column type


## v2.8.0 - 2018-09-28

* Add ability to track life events
* Add ability to define the default email address used for support
* Add sentry:release command
* Add Envoy file template
* Add passport config file
* Add new variable APP_DISPLAY_NAME
* Rename env variable 2FA_ENABLED to MFA_ENABLED (2FA_ENABLED is still functional for compatibility reasons)
* Improve search
* Fix reminders displaying wrong date
* Fix select boxes not working properly anymore
* Fix confirm email sent when signup_double_optin is false
* Fix now() without timezone functions
* Remove notion of events
* Support papertrail logging


## v2.7.1 - 2018-09-05

* Fix duplication of modules in the Settings page


## v2.7.0 - 2018-09-04

* Add ability to log conversations made on social networks or SMS
* Add language selector on register page
* Support Arabic language
* Improve automatic route binding
* Split app css in two files for better support of ltr/rtl text direction
* Add helper function htmldir()
* Fix gifts not showing when value was not set
* Fix phpunit not parsing all test files
* Fix login remember with 2fa and u2f enabled
* Fix gender update
* Fix how comparing version is done
* Fix search with wrong search field
* Fix gift recipient relation
* Fix subscription cancel on account deletion
* Fix email maximum size on settings
* Fix reminder link in email sent


## v2.6.0 - 2018-08-17

* Add ability to set a contact as favorite
* Add ability to search for a contact in the dropdown when creating a relationship
* Add activity reports page, which shows useful statistics about activities with a specific contact
* Fix reminders not being sent for single-digit hours
* Fix accounts with an empty reminder time
* Fix account id get for acceptPolicy
* Use our own docker image (central perk) to run tests
* Add end-2-end testing with Cypress
* Render timezone listbox dynamically
* Use a new formatter to display money (debts), with right locale handle
* Get first existing gravatar if contact has multiple emails
* Display the date and time of the next reminder sent in settings page


## v2.5.0 - 2018-08-08

* Add ability to define custom activity types and activity type categories
* Add ability to search a contact by job title
* Fix invoice page not showing properly
* Fix translation not being displayed correctly on Subscription page
* Add the TrimStrings middleware to trim all inputs
* Call to monica:ping when updating instance
* Fix idHasher decode function
* Fix storage folder not being linked to public if migrations fail


## v2.4.2 - 2018-07-26

* Add functional tests for account deletion and account reset
* Fix activities not being displayed in the journal
* Fix food preferences not being able to be updated
* Add functional test for account exporting
* Fix fake content seeder for testing purposes


## v2.4.1 - 2018-07-25

* Add ability to discover Cloudflare trusted proxies automatically. This adds a new ENV variable.
* Fix avatar link in journal page
* Fix broken migration
* Fix Settings not displaying under some conditions


## v2.4.0 - 2018-07-23

* Fix account deletion, reset and export
* Fix export feature which exported 'changelog_user' table, which it shouldn't
* Change how dates are stored, from local timezone to UTC
* Remove the APP_TIMEZONE env variable
* Add U2F/yubikey support and refactor MultiFactor Authentication
* Add a script to update assets automatically
* Allow for plus sign search in contacts api (contact_fields_data)
* Fix sonar run for pull requests
* Improve date and datetime parsing


## v2.3.1 - 2018-06-21

* Fix journal entries not being displayed
* Add ability to click on entire row on the contact list
* Fix first name of a relation which could not be saved
* Fix last name not being reset when set empty


## v2.3.0 - 2018-06-13

* Add a new variable DB_USE_UTF8MB4. Please read instructions carefully for this one.
* Add support for nicknames
* Fix resetting account not working
* Fix CSV import that can break if dates have the wrong format
* Add default accounts email confirmation in setup:test
* Set the default tooltip delay to 0 so the tooltip does not stay displayed for 200ms by default
* Replace queries with hardcoded "monica" database name to use the current default connection database
* Set the default_avatar_color property before saving a contact model.
* Move docs folder back to the repository


## v2.2.1 - 2018-05-31

* Fix url of confirmation email resend
* Update translations
* Fix sonar run on release version


## v2.2.0 - 2018-05-30

* Add debts on the dashboard
* Add support for User and Currency objects in the API
* Add ability to force users to accept privacy and terms of use
* Fix journal entry with date different than today's date not working
* Fix Contact search dropdown showing non-contacts that link to nowhere
* Add ability to sort contact list by untagged contacts
* Allow multiple imported fields and replace existing contacts
* Add ex wife/husband relationship
* Fix duplication of tags when filtering contacts
* Add trusted proxies to run behind a ssl terminating loadbalancer
* Fix reminders for past events are visible on the dashboard
* Add email address verification on register, and email change
* Change table structure to support emojis in texts


## v2.1.1 - 2018-05-13

* Change file structure inside the People folder (backend change)
* Remove automatic birthday reminder creation when editing a contact
* Set fixed version for MySQL in docker-compose
* Build absolute path to stubs files in UploadVCardTest and UploadVCardsTest (backend)
* Refactor how countries are fetched
* Change address fetching in API
* Add ComposerScripts links
* Fix tests to prepare for foreign keys (backend)
* Fix deploy tagged version
* Fix vagrant box
* Fix notifications being sent even if reminder rule is set to off
* Fix API locale
* Fix update command (backend)


## v2.1.0 - 2018-05-03

* Refactor vCard import
* Add support for markdown on the Journal
* Add support for markdown for Notes
* Add many unit tests on the API
* Add ability to display contact fields for each contact in the contact list through the API
* Add ability to stay in touch with a contact by sending reminders at a given interval
* Add secure Oauth route for the API login
* Fix removal of tags


## v2.0.1 - 2018-04-17

* Add ability to set relationships through the API
* Fix ordering of activites in journal
* Fix how you meet section not being shown
* Add a changelog inside the application
* Fix monica:calculatestatistics command


## v2.0.0 - 2018-04-12

* Add ability to set a journal entry date
* Use UUID instead of actual ID to identify contacts
* Add ability to show/hide sections on the Contact sheet view
* Add many more relationship types to link contacts together
* Fix called_at field in the Call object returned by the API
* Add Linkedin URL in the Contact object returned by the API
* Improve localization: add plural forms, localize every needed messages
* Split app.js in 3 files, and load translations files for Vue in separate files
* Localize update tag message
* Fix some messages syntax and ponctuation
* Add a new monica:update command
* Fix gifts handle
* Remove old documentation from sources
* Fix Bug when editing gift


## v1.8.2 - 2018-03-20

* Add a Vagrantfile to run Monica on Vagrant
* Add support for Hebrew and Chinese Simplified
* Add bullet points to call lists when rendered from markdown
* Require debugbar on dev only
* Improve heroku integration
* Open register page after a clean installation
* API:  Add ability to sort tasks by completed_at attribute
* API: Add sorting capabilities to most models
* Update Czech, Italian, Portuguese, Russian, German, French language files
* Fix docker image creating wrong storage directories
* Fix notification messages


## v1.8.1 - 2018-03-02

* Fix message in contact edit page
* Fix months list for non english languages  in contact edit page
* Fix birthdate calendar for non english languages in contact edit page
* Fix Gravatar support
* Remove partial contacts from search results returned by the API
* Fix reset account deleting default account values
* Fix notifications not working with aysnchronous queue
* Support mysql unix socket


## v1.8.0 - 2018-02-26

* Add ability to search and sort in the API
* Add ability to define the hour the reminder should be sent
* Add notifications for reminders (30 and 7 days before an event happens)
* Add API calls to associate and remove tags to a contact
* Docker image: use cron to run schedule tasks
* Docker image: reduce size of image
* Docker image: create storage subdirectory in case they not exist
* Docker image: use rewrite rules in .htaccess from public directory instead of apache conf file
* Remove trailing slash from routes


## v1.7.2 - 2018-02-20

* Fix a bug where POST requests were not working with Apache
* Fix a bug preventing to delete a contact


## v1.7.1 - 2018-02-17

* Fix a bug that occured when running setup:production command


## v1.7.0 - 2018-02-16

* Add ability to create custom genders
* Add Annual plan for the .com site
* Fix avatar being invalid in the Contact API call
* DB_PREFIX is now blank in .env.example
* Fix empty message after updating a gift


## v1.6.2 - 2018-01-25

* Add support for pets in the API
* Add ability to export a contact to vCard
* Add ability to mark a gift idea as being offered
* Add translation for "preferences updated" message in the Settings page
* Add a lot of unit tests


## v1.6.1 - 2018-01-14

* Add missing journal link to the mobile main menu
* Remove list of events being loaded in the dashboard for no reason
* Remove duplicated code in Addresses.vue file
* Fix reminders not being sent in some cases
* Fix avatars not being displayed in an activity on the journal
* Fix filtering of contacts by tags not taking into account the selected tag from the profile page


## v1.6.0 - 2018-01-09

* Change the structure of the dashboard
* Add two factor authentication ability
* Add ability to edit a reminder
* Fix vCard import if custom field types are not present
* Fetch Countries in alphabetical order in "Add Address" form in People Profile page
* Display missing page when loading a contact that does not exist
* Add ability to filter contacts by more than one tag
* Change the structure of the dashboard
* Add two factor authentication ability
* Add pet support to API


## v1.5.0 - 2018-01-02

* Add Webmanifest to create bookmarks on phones
* Add pets management
* Activities made with contact now appears in the Journal
* Add ability to rate how a day went in the Journal
* Add validation when changing email address
* Add ability to change account's password in the settings
* Show a user's avatar when searching
* Fix timezone not being saved in the Settings tab


## v1.4.1 - 2017-12-13

* Add default user account on setup


## v1.4.0 - 2017-12-13

* Add ability to add a birthday (or any date) without knowing the year
* Add the artisan command (CLI) `php artisan setup:test` to setup the development environment
* Remove the table `important_dates` which was not used
* Change how resetting an account is achieved
* Add progress bar when generating fake data to populate the dev environment


## v1.3.0 - 2017-12-04

* Notes can be set as favorites
* Favorite notes are shown on the dashboard
* Notes are now managed inline
* Add dynamic notifications when adding/updating/deleting data from Vue files
* Add ability to change account's owner first and last names


## v1.2.0 - 2017-11-29

* Add a much better way to manage tasks of a contact
* Tasks can now be mark as completed and can now be edited
* Add more usage statistics to reflect latest changes in the DB


## v1.1.0 - 2017-11-26

* Add the ability to add multiple contact fields and addresses per contact
* Add a new Personalization tab under Settings


## v1.0.0 - 2017-11-09

* Add the ability to mark a contact as deceased
* Add a button to `Save and add another contact` straight from the Add contact screen
* Add the ability to indicate how you've met someone
* Replace former front-end build system by mix (which is the new default with Laravel 5.5)
* Add the first part of the API
* Fix the access to upgrade account view
* Add security.txt file
* Upgrade codebase to Laravel 5.5


## v0.7.1 - 2017-10-21

* Fix an error in the JS that broke the application


## v0.7.0 - 2017-10-21

* Add ability to assign a single activity to multiple people
* Improve german translations
* Fix reminders not being sent in case of wrong timezones
* Fix the access to upgrade account view
* Replace the custom RandomHelper by str_random
* Multiple small fixes


## v0.6.5 - 2017-08-28

* Add a new welcome screen for new users
* Fix typo when displaying message of no existing contact to link when adding a child
* Monicahq.com only: add limitations to free accounts


## v0.6.4 - 2017-08-23

* Add restriction of 50 characters for a first name, and 100 characters for a last name
* Add support for storing uploaded files on s3
* Sort contacts by first name, last name when linking significant others and kids
* Remove automatic uppercase of the first name
* Remove beginning / ending spaces in names when adding / saving a contact
* Fix birthday reminder creation bug on vCard import
* Fix search bar being hard to use


## v0.6.3 - 2017-08-16

* Fix kids not being able to be removed
* Fix some CSRF potential vulnerabilities


## v0.6.2 - 2017-08-16

* Add support for Markdown for the notes and call logs


## v0.6.1 - 2017-08-15

* Fix delete account bug
* Fix kid deletion bug
* Fix gift creation


## v0.6.0 - 2017-08-14

* Add ability to set significant other and kids as contact.
* Add Italian translation
* Add debt total below a contacts debt
* Add world currencies
* Add German translation


## v0.5.0 - 2017-07-24

* Add version checking.
* Add ability to search various fields in contacts through the top-nav search.
* Fix gift view not being shown.


## v0.4.2 - 2017-07-18

### New features:
* Add Indian rupee currency.
* Add Danish krone currency.
* Add Czech translation.

### Improvements:
* Fix https issue on password reset.


## v0.4.1 - 2017-07-13

* Fix reminders not being sent introduced by previous version.


## v0.4.0 - 2017-07-13

### New features:
* Add ability to keep track of phone calls.

### Improvements:
* Fix Google Contact instructions link on the Import screen.
* Input field are now automatically selected when a radio button is checked.
* Many small bug fixes.


## v0.3.0 - 2017-07-04

### New features:
* Add support for organizing people into tags (requires `bower update` for dev environment).
* Add ability to filter contacts per tags on the contact list.

### Improvements:
* Fix import translation key on the import reports.
* Settings' sidebar now has better icons.


## v0.2.1 - 2017-07-02

### Improvements:
* Update the design of the latest actions on the dashboard.
* Change order of first and last names fields on contact add/edit, if the name order is defined as "last name, first name".
* Speed up the display of the contact lists when there is a lot of contacts in the account.
* Remove the search on the list of contacts, which was broken for a while, until a proper solution is found.
* Bug fixes.


## v0.2.0 - 2017-06-29

### New features:
* Add import from vCard (or .vcf) in the Settings panel.
* Add ability to reset account. Resetting an account will remove everything - but won't close the account like deletion would.

### Improvements:
* Journal entries now respect new lines.
* Fix name not appearing in the latest actions tab on the dashboard.


## v0.1.0 - 2017-06-26

* First official release. We'll now follow this structure. If you self host, we highly recommend that you check the latest tag instead of pulling from master.


## 2017-06-24

### Improvements:
* On the people's tab, filters are now placed above the table.


## 2017-06-22

### New features:
* Add ability to define name order (Firstname Lastname or Lastname Firstname) in the Settings panel.

### Improvements:
* Fix the order of the address fields.
* Env variables are now read from config files rather than directly from the .env file.
* Some US typos fix.


## 2017-06-20

### New features:
* Add support for mutiple users in one account.
* Add subscriptions on .com. This has no effect on self hosted versions.


## 2017-06-16

### Improvements:
* Add automatic reminders when setting a birthdate When adding a birthdate (contact, kid, significant other). When updating or deleting the person, the reminder will be changed accordingly.


## 2017-06-15

### New features:
* Add reminder automatically when you set the birthdate of a contact.

### Improvements:
* Add timezone for Switzerland.
* Major refactoring of how contacts are managed in the codebase.


## 2017-06-14

### New features:
* Timezone can now be defined in a new ENV variable so every new user of the instance will have this timezone. Set to America/New_York by default.
* Add ability to edit a note.
* Add ability to edit a debt.
* Add support for South African ZAR currency.

### Improvements:
* Fix Deploy to Heroku button.
* Fix Bern timezone by actually removing it. The Carbon library does not support this timezone.


## 2017-06-13

### New features:
* You can now add job information and company name for your contacts.

### Improvements:
* Gifts table now display comments if defined, as well as who the gift is for.


## 2017-06-12

### New features:
* Add instructions to setup Monica from scratch on Debian Stretch.
* Add Export to SQL feature, under Settings > Export data.
* Add Deploy to Heroku button. Only caveat: you can't upload photos to contacts (Heroku has ephemeral storage).


## 2017-06-11

### New features:
* Add command line vCard importer

### Improvements:
* Email address of a contact is now a mailto:// field.
* Phone number of a contact is now a tel:// field.
* Fix debt description on the dashboard
* Fix typos
* Fix Bootstrap tabs on the dashboard


## 2017-06-10

### New features:
* Add support for other currencies (CAD $, EUR €, GBP £, RUB ₽) for the gifts and debts section. This is set in the User setting. Default is USD $.
* Add ability to define main social network accounts to a contact (Facebook, Twitter, LinkedIn)

### Improvements:
* Fix counter showing number of gifts on the dashboard
* Docker image now runs the cron to send emails
* Fix Russian translations
* Fix the wrong route after password change


## 2017-06-09

### New features:
* Add Docker support
* Add Russian language
* Add Portuguese (Brazil) language

### Improvements:
* Fix emails being sent too often
* Breaking change: Email name and address of the user who sends reminders are now ENV variables (MAIL_FROM_ADDRESS and MAIL_FROM_NAME).


## 2017-06-08

### New features:
* Add Gravatar automatically when adding an email address to a contact. If no gravatar found, defaults to the initials.

### Improvements:
* Dramatically reduce the number of queries necessary to load the list of contacts on the People's tab.
* Phone number are now treated like a string and not integers on the front-end side.
* Breaking change: Add a new env variable to define which email address should be used when sending notifications about new user signups. You need to add this new env variable (APP_EMAIL_NEW_USERS_NOTIFICATION) to your `.env` file.
* Fix typos and small bugs


## 2017-06-07

* Add ability to delete a contact
* Add a changelog
