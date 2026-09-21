# 002. User

**Status:** exists (`app/Models/User.php`), built as far as the models it depends on allow
**Spec:** 3.7, 4.1, 10.3, 10.4

## What it is

The person who signs in. There is exactly one per account (4.1). The user carries authentication, and it carries the preferences that decide how everything is rendered: language, clock, time zone, name order.

The distinction with the account matters and is worth stating once. The account owns the data. The user owns the way that data is presented and the credentials that reach it. A preference that changes how a date reads belongs here. A configuration that changes what a record contains belongs to the account.

## Fields

```
id                          int                             primary key
account_id                  int                             account the user belongs to
first_name                  string (255)                    first name of the user
last_name                   string (255)                    last name of the user
photo_path                  string (255)                    base path of the photo file, without extension or size suffix
email                       string (255)                    address the user signs in with, unique across the instance
email_verified_at           datetime                        when the address was verified
password                    string (255)                    hashed password, null when the user signs up through SSO
password_changed_at         datetime                        when the password was last set, null when the user signs up through SSO
sso_provider                string (255)                    SSO provider the user signs in with
is_active                   boolean                         whether the user can sign in, so an account can be suspended without being deleted
locale                      string (5)                      language of the interface, falls back to the language of the instance
timezone                    string (64)                     IANA time zone, used to render every date and to seed new reminder rules
time_format                 string (2)                      whether times read on a 24 hour or a 12 hour clock
date_format                 string (20)                     order the parts of a date read in, null to follow the locale
name_order                  enum (given_first, family_first) order the name reads in, which the locale cannot always decide
number_format               string (20)                     separators used for numbers, null to follow the locale
last_login_at               datetime                        when the user last signed in
last_login_ip               string (45)                     address the last sign in came from, so a new one can be flagged
two_factor_secret           text                            encrypted TOTP secret, null until the user enrols
two_factor_confirmed_at     datetime                        when enrolment finished, null while two factor is not in use
two_factor_recovery_codes   text                            encrypted list of single use recovery codes
last_saved_at               datetime                        when the user last saved their own profile, shown on that screen
remember_token              string (100)                    remember token
created_at                  datetime
updated_at                  datetime
deleted_at                  datetime
```

`account_id` is unique: one account holds one user (4.1), and the database enforces it rather than the application. `password` is the column Laravel authenticates against by default, so nothing has to tell the guard where to look. `timezone` is not a nicety: section 10.3 says a reminder rule freezes the time zone it was created in and that changing the personal time zone later never shifts an existing reminder, and neither half of that can be implemented without a stored time zone. Name order cannot be derived from the interface language, since somebody reading Monica in English may still want their family name first.

No role, no permission, no invitation, no membership. Section 4.1 removes them, and nothing should be added that quietly reintroduces them.

## Relationships

- `belongsTo` [Account](001-account-in-progress.md)
- `hasMany` [MagicLink](003-magic-link-in-progress.md), [Log](004-log-in-progress.md), [EmailSent](005-email-sent-in-progress.md), [Notification](042-notification-done.md)
- `morphMany` [PersonalAccessToken](006-personal-access-token-in-progress.md)

## Rules

- A user is soft deleted. A hard delete would orphan logs, which must survive the user they describe (see [Log](004-log-in-progress.md)).
- `timezone` must be a valid IANA identifier. It defaults from the browser at sign up and is editable afterwards.
- Changing `timezone`, `locale` or any format never rewrites stored data. It changes rendering only, and it never shifts an already computed reminder occurrence (3.1, 10.3).
- The user is not the `Moi` contact. `Moi` is a contact in a vault ([Contact](019-contact-done.md)) and carries the fields, life events and relations. The user carries the credentials. Renaming one does not rename the other, and the specification does not ask it to.

## Not done yet

- The screen for the rest of the preferences. The preferences screen offers the language, the clock, the order dates read in, the separators numbers are written with, and the time zone, which is searched by city or country or pointed at on a world map. `name_order` is stored and asked for nowhere (3.7): a contact carries its own, and one that carries none reads given name first rather than following this column.
- Only the preferences screen reads `timezone`, where the clock and the date it previews are written in it so the choice can be seen to have taken. Every other screen still renders in the time zone of the instance, and the rule that freezes a zone belongs to [Reminder](040-reminder-done.md).
- `date_format` is read on a record and nowhere else. `FieldValueFormatter` writes every date somebody entered in the order this person reads dates in, falling back to the order of their language. Every other date on every other screen is either how long ago it was or a fixed long form, and neither asks the preference. `number_format` is read by nothing outside the screen that sets it, since no screen shows a number worth grouping.
- Nothing here reads the notifications addressed to the user. [Notification](042-notification-done.md) carries `user_id` and the relationship can be added the day a screen asks for it.

## Open questions

- Should signing up create the `Moi` contact from the user's first and last name, or leave it blank? Copying is friendlier and matches what section 4.3 describes as a real contact. Nothing later depends on the answer.
