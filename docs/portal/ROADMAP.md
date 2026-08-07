# Documentation Portal Roadmap

A roadmap for the OfficeLife product documentation portal.

This document describes what the portal should contain, section by section and page by page. It is an implementation plan, not the documentation itself. A writing agent should be able to build every page from this roadmap without having to rethink the structure.

---

## How to use this roadmap

Read this section first. It sets the scope, the audiences, and the conventions used everywhere below.

### What the product is

OfficeLife is an open source HR application. The interface calls itself "Open source HR" on the guest screens, and the public site lives at `officelife.io`.

The mental model the whole product rests on, and the one the portal must teach first, comes straight from the schema:

- A **company** is the workspace. Everything belongs to one.
- An **employee** is somebody who works for that company. The employee record exists on its own, before they arrive and after they leave, and does not need a login.
- A **user** is a login. It belongs to a company, and it usually points at the employee it gives access to. It does not have to.
- The first person to sign up creates the company, becomes its **owner**, and gets both a user and an employee record in one move.

That separation between "a person who works here" and "an account that can sign in" is the single most important idea in the product, and several sections below exist only to make it land.

### The state of the product, and what that means for the portal

**Read this before writing a single page.** OfficeLife is early. As of this roadmap, the user facing surfaces that exist are the guest and authentication screens, plus one screen inside the application: the profile, where somebody edits their own employee record, uploads a photo of themselves, and fills in their emergency contact. There is no dashboard, no employee directory, and no billing screen. There is no API either, though the screen that hands out keys for one exists, which is a distinction the writer has to keep: see `security.api-keys`. `routes/web.php` still sends `/` to a landing page, with a comment saying it stands in "until there is a dashboard to send people to."

This has two consequences the writer must respect:

1. **Every numbered section below is writable today.** They cover accounts, signing in, your profile, security, language and preferences, the emails the product sends, and running your own instance. Every claim in them is backed by code that exists and by tests that pass.
2. **"Not yet documentable", at the end, is not writable.** It is an inventory of things the database or the enums anticipate but no screen or route delivers. It exists so nobody writes those pages by accident, and so they can be written the moment the feature lands.

Never present a planned capability as finished. When a page has to mention something that is half built, say plainly what works and what does not.

### Who the readers are

Three audiences, needing different pages:

1. **Employees.** The people who sign in and use the product. Not developers. Most of the portal serves them.
2. **Company owners and administrators.** The person who signed up, plus anyone they later put in charge. Today the owner is the only distinguished role in the code: `companies.owner_user_id`, checked directly in `DestroyUser`, `DestroyCompany`, and `UpdateUserInformation`. There is no roles table and no permission system beyond that single check. Do not write about "roles" or "permission levels" in the plural.
3. **Operators.** Whoever installs, configures, and runs an instance. Developers and sysadmins.

Every page below names its audience.

### Where the portal lives

There is no documentation route today. `/docs` is free, and is the natural home. The portal is written as Markdown under `docs/portal/{locale}/`, mirroring the four locales the application already ships: `en`, `fr_FR`, `de_DE`, `es_ES` (see `config/officelife.php`). Write `en` first and completely; the others are translations of it.

### Conventions for every page entry

Each proposed page lists:

- **Purpose.** What the page accomplishes for the reader.
- **Audience.** Which reader group it serves.
- **Summary.** What it should cover, grounded in behavior that exists.
- **Prerequisites.** What the reader should have read or done first.
- **Complexity.** Low, medium or high, as a hint at how much work the page is.
- **Related pages.** Where to go next or sideways.

The `id` given for each page is its permanent frontmatter identifier, in `domain.action` form. Ids must not change once assigned, and `@doc(...)` links must always reference them rather than filenames. Folders and files carry an `N-` ordering prefix on disk that never appears in frontmatter. The first file of every folder is `1-introduction.md`. All of this follows the writer skill's rules, which take precedence over anything here if the two ever disagree.

### Ordering

Sections are ordered by how somebody actually meets the product: find out what it is, create an account, understand the vocabulary, learn to get back in, fill in your profile, secure the account, adjust the interface, understand the mail it sends, and (for operators) run it. Nobody should need a later section to finish an earlier one.

---

## Section 1: Introduction

**Why this section exists.** The portal needs a front door: one page that says what OfficeLife is, who it is for, and where to go next. It is also the only honest place to set expectations about how young the product is.

**Who it is for.** Everybody.

**On disk.** `1-introduction.md` at the portal root. Section value `portal`, slug `portal`.

### Welcome to OfficeLife

- **id.** `portal.introduction`
- **Purpose.** Orient a first time reader in under a minute and route them to the right section.
- **Audience.** All three.
- **Summary.** What OfficeLife is: open source HR software a company runs for its own people. The one line model, stated early and plainly: a company holds employees, and some of those employees have an account they sign in with. Who the product suits. That it is open source and can be run by you. A short map of the portal: start with Getting started; owners should read Your company and your people; operators should jump to Running your own instance. Then a candid paragraph, in plain language, that the product is in early development and this portal currently documents accounts, signing in, and security, with the rest arriving as it is built. No roadmap promises and no dates.
- **Prerequisites.** None. This is the true entry point.
- **Complexity.** Low.
- **Related pages.** `getting.what-is-officelife`, `getting.create-account`, `hosting.introduction`.

---

## Section 2: Getting started

**Why this section exists.** The shortest path from "I found this" to "I have an account and I am inside." Everything advanced is deferred.

**Who it is for.** Anyone creating a company, and anyone evaluating whether to.

**On disk.** `2-getting-started/`. Section value `getting-started`.

### Getting started

- **id.** `getting.introduction`
- **Purpose.** Section index. Say what the reader will have achieved by the end of it.
- **Audience.** New users.
- **Summary.** Three outcomes: you understand what OfficeLife is, you have a company and an account, and you have confirmed your email address. List the pages in order.
- **Prerequisites.** None.
- **Complexity.** Low.
- **Related pages.** Every page in this section.

### What is OfficeLife

- **id.** `getting.what-is-officelife`
- **Purpose.** Tell a newcomer what the product does and whether it fits them, before they invest any time.
- **Audience.** Employees and prospective owners.
- **Summary.** The problem it addresses: companies keeping people information in spreadsheets, inboxes and memory. The model in one paragraph: one company, its employees, and accounts for the ones who need to sign in. That it is open source, so you can read every line and run it yourself. Be explicit and unembarrassed about scope: today the product handles accounts, sign in and company creation, and more is being built. A reader who discovers that limitation on their own after signing up will not come back.
- **Prerequisites.** None.
- **Complexity.** Low.
- **Related pages.** `getting.cloud-or-self-hosted`, `concepts.model`.

### Using officelife.io or running it yourself

- **id.** `getting.cloud-or-self-hosted`
- **Purpose.** Help the reader choose how they will run OfficeLife before they create anything.
- **Audience.** Prospective owners and operators.
- **Summary.** Two ways to run it: use the hosted service, or install it on your own server. The application knows which it is (`companies.is_self_hosted`) but the software is the same either way. State clearly what the portal can and cannot promise about the hosted service: the codebase has a `plan` field on every company and a thirty day `trial_ends_at` set at signup, but **no billing, payment or subscription code exists**, nothing reads either field, and no screen shows a plan. So: do not write pricing, do not describe upgrade paths, do not mention tiers. Say that self hosting is free and point at the self hosting section.
- **Prerequisites.** `getting.what-is-officelife`.
- **Complexity.** Medium. The temptation to invent a pricing story here is the main risk.
- **Related pages.** `hosting.introduction`, `concepts.company`.

### Create your company and your account

- **id.** `getting.create-account`
- **Purpose.** Walk somebody through the sign up form and explain what it creates.
- **Audience.** The person starting a company in OfficeLife.
- **Summary.** A `::::steps` walkthrough of `/register`: first name, last name, company name, email address, password, and the terms checkbox. What each field does and what it becomes. Explain, because the screen says so and users ask, that you can rename the company later and that you become its first administrator. Then the part nobody expects: submitting this form creates three things at once, a company, your user account, and your own employee record, and it makes you the owner. Cover the rules the form enforces, in the reader's words rather than the validator's: a password of at least eight characters, typed twice; an email address that no other account already uses; disposable or throwaway addresses are refused (the `disposable_email` rule); agreeing to the terms of use and the privacy policy is required, and links to both come from configuration so a self hosted instance can point at its own. Mention the human check that may appear (Cloudflare Turnstile), which is off by default and switched on per instance. Finish by saying what happens next: you are signed in and sent straight to the email confirmation screen.
- **Prerequisites.** `getting.what-is-officelife`.
- **Complexity.** Medium.
- **Related pages.** `getting.confirm-email`, `concepts.owner`, `concepts.employee-vs-user`.

:::note for the writer
The screen also carries a line telling people joining an existing company to ask an administrator to invite them rather than signing up again. Repeat that advice on this page, but do **not** describe an invitation flow: no invitation code, route, mail or screen exists yet. Say only that a second signup creates a second, separate company, which is almost never what they want, and that they should ask whoever runs their company for an account.
:::

### Confirm your email address

- **id.** `getting.confirm-email`
- **Purpose.** Get a brand new account over its last hurdle.
- **Audience.** Anyone who just signed up.
- **Summary.** Why confirmation exists. What the screen shows, and where the email goes. That the link is signed and expires after an hour, and what to do when it has: use **Send the email again** from the same screen. That resending is rate limited to six attempts a minute, so hammering it does not help. The reassurance the product itself offers: it can take a minute and it sometimes lands in spam. What you see when it works. Note that confirming later, rather than now, does not currently lock you out of anything.
- **Prerequisites.** `getting.create-account`.
- **Complexity.** Low.
- **Related pages.** `email.verify`, `signin.password`, `troubleshoot.no-email`.

### What you can do today

- **id.** `getting.what-works-today`
- **Purpose.** Set honest expectations for somebody who just got in and is looking at a bare page.
- **Audience.** New owners.
- **Summary.** A short, factual page. What exists: your company, your account, your employee record, the profile screen where you edit it, the preferences screen where you choose your language and how times are written, two factor authentication, the list of emails the product has sent you, and everything in the account and security sections of this portal. What does not exist yet: any screen for browsing employees, editing your company, or managing accounts. Where the project is going, only insofar as the code shows it, with no dates. Where to follow along or contribute, since it is open source.
- **Prerequisites.** `getting.create-account`.
- **Complexity.** Low, but must be revisited every time a feature ships. Flag it in the page as the portal's "what is built" page.
- **Related pages.** `portal.introduction`, `concepts.introduction`.

---

## Section 3: Core concepts

**Why this section exists.** OfficeLife makes one distinction that no amount of clicking will teach you: employees and accounts are different things. Every later page leans on it. Concepts stay here, tasks stay elsewhere.

**Who it is for.** Everybody, but especially owners.

**On disk.** `3-core-concepts/`. Section value `core-concepts`.

### Core concepts

- **id.** `concepts.introduction`
- **Purpose.** Section index and the shortest possible statement of the model.
- **Audience.** All.
- **Summary.** Company, employee, user, owner, in one paragraph each, each linking to its page. Say why the reader should care: it explains why somebody can leave the company without their record disappearing, and why an account can exist for somebody who does not work here.
- **Prerequisites.** None.
- **Complexity.** Low.
- **Related pages.** All pages in this section.

### How OfficeLife is organised

- **id.** `concepts.model`
- **Purpose.** Give the reader the mental picture the whole product assumes.
- **Audience.** All.
- **Summary.** One company at the top. Employees belong to it. Accounts belong to it too, and each account may point at one employee. Draw it in prose (and mark a diagram placeholder). Explain the boundary that follows from it: everything you can see is inside your company, and there is no way to reach another company's data. State the current limit plainly: an account belongs to exactly one company, so somebody who genuinely works for two needs two accounts with two addresses.
- **Prerequisites.** None.
- **Complexity.** Medium.
- **Related pages.** `concepts.company`, `concepts.employee-vs-user`.

### Your company

- **id.** `concepts.company`
- **Purpose.** Explain what a company is in OfficeLife and what it records.
- **Audience.** Owners.
- **Summary.** The company is the workspace and the boundary. What it holds: a name, a URL friendly identifier derived from that name, a legal name, a logo, a website, an industry, a size range, a founding date, a timezone, a language, a currency, and how the company works (fully remote, hybrid, or office based). Explain the two that are not self evident: the size range is a declared band rather than a headcount the product counts, and the timezone and language act as the default for everyone who has not chosen their own. Be explicit that **there is no screen to edit any of this yet**: the fields are set when you sign up, and the ability to change them is coming. Do not describe a settings page.
- **Prerequisites.** `concepts.model`.
- **Complexity.** Medium. The honesty about the missing screen is the whole difficulty.
- **Related pages.** `concepts.owner`, `language.company-default`.

### Employees and accounts are not the same thing

- **id.** `concepts.employee-vs-user`
- **Purpose.** Teach the distinction that everything else depends on.
- **Audience.** All, above all owners.
- **Summary.** Lead with a scenario rather than a definition. Pam works in the warehouse and has no computer: she is an employee with no account. Michael runs the branch: he is an employee with an account. The bookkeeper who visits on Fridays has an account but is not on the payroll: an account with no employee. Then the definitions. An employee record holds who somebody is at work: their name, the name they go by if it differs, a work email, a job title, the country and timezone they work from, when they were hired, when they left, and the private details they fill in themselves (personal email and phone, date of birth, home address, emergency contact). An account holds only what is needed to sign in. Explain the two consequences that matter: deleting an account does not erase the person's employment record, and somebody who has left keeps their record. Explain "still employed" as the product defines it, which surprises people: somebody serving a notice period has a departure date in the future and still works here until that date arrives.
- **Prerequisites.** `concepts.model`.
- **Complexity.** High. This is the most important page in the portal.
- **Related pages.** `concepts.employee-record`, `concepts.owner`.

### What an employee record holds

- **id.** `concepts.employee-record`
- **Purpose.** Say exactly what the product knows about a person, which is a question people ask about HR software before they ask anything else.
- **Audience.** Employees and owners.
- **Summary.** A plain inventory, grouped by who it is for. Work facts: employee number as used by payroll, legal first and last name, the name they go by, photo, work email, job title, country, timezone, hire date, departure date. Personal facts: personal email and phone, date of birth, home address, and an emergency contact with their relationship to the employee. Explain the naming rule the product applies, because it shows up everywhere: the product calls you by the name you go by when you have given one, and by your legal name otherwise. Be clear about what is not stored: no salary, no compensation, no performance record, none of it exists in the product today. Be equally clear about which of it you can reach: the profile screen edits four of these fields on your own record, no screen creates an employee except the one the product makes for you when you sign up, and nothing lets you see or edit anybody else's.
- **Prerequisites.** `concepts.employee-vs-user`.
- **Complexity.** Medium.
- **Related pages.** `concepts.employee-vs-user`, `security.privacy`.

### The company owner

- **id.** `concepts.owner`
- **Purpose.** Explain the one privileged position in the product.
- **Audience.** Owners.
- **Summary.** Who the owner is: the person who created the company. What being owner means today, stated as the code actually enforces it: only the owner can delete another account in the company, only the owner can delete the company, and the owner can edit another member's account details where an ordinary member can only edit their own. The two safeguards: the owner cannot delete their own account, because a company always needs one, and nobody can act on an account outside their own company. Then the honest caveat: there is no roles or permissions system beyond this. There is no administrator role, no groups, no per feature permission. Ownership cannot currently be transferred through any screen.
- **Prerequisites.** `concepts.model`.
- **Complexity.** Medium.
- **Related pages.** `concepts.company`, `account.delete`.

### The activity log

- **id.** `concepts.activity-log`
- **Purpose.** Explain that the product keeps a record of what people do, and what it means for the reader.
- **Audience.** Owners, and privacy conscious employees.
- **Summary.** What is recorded, listed exactly and no more: a company created, a company updated, account details updated, a password changed, an account deleted, an employee created, an employee's details updated, an emergency contact updated, an email address confirmed, a sign in, and a sign in link requested. Each entry keeps who did it, when, and the email address they had at the time, so the record still says who acted after their account is gone. Why that design choice was made. That the log belongs to the company, while the screens that display it only ever show the reader their own entries. Then the two limits worth stating: nobody can read another person's entries anywhere in the product today, and an action this version of the product no longer recognises is shown by its raw name rather than a sentence. Send the reader to `profile.logs` for how to read their own.
- **Prerequisites.** `concepts.model`.
- **Complexity.** Low.
- **Related pages.** `profile.logs`, `security.privacy`, `email.record`.

---

## Section 4: Signing in

**Why this section exists.** Signing in is, today, the largest genuinely finished part of the product, and it offers more ways in than most applications. Each deserves its own page, because a reader arrives at exactly one of them with exactly one problem.

**Who it is for.** Everybody with an account.

**On disk.** `4-signing-in/`. Section value `signing-in`.

### Signing in

- **id.** `signin.introduction`
- **Purpose.** Section index, and a decision aid.
- **Audience.** All.
- **Summary.** The three ways in, and when each is right: your password, a link emailed to you, or a new password when you have forgotten the old one. A sentence on the two factor code, for those who have set it up. Link each.
- **Prerequisites.** An account.
- **Complexity.** Low.
- **Related pages.** All pages in this section.

### Sign in with your password

- **id.** `signin.password`
- **Purpose.** Get somebody in, and explain the choices the screen offers.
- **Audience.** All.
- **Summary.** The form, and the two things on it worth explaining. **Remember me**, which keeps you signed in for thirty days by default on that browser (the screen names the number, and a self hosted instance can change it), and what that means on a shared machine. What happens after a correct password when two factor authentication is on: you are not in yet, you get the code screen. Then the two behaviours that generate support requests, explained rather than apologised for. First, every refusal says the same thing, "these credentials do not match our records", whether the password was wrong, the account is suspended, or no account exists, because saying which would let a stranger find out who has an account here. Second, after five failed attempts from the same address and place you are locked out for a few minutes and told how many seconds remain. Add that a failed attempt sends an email to the address's owner, and link to it.
- **Prerequisites.** An account.
- **Complexity.** Medium.
- **Related pages.** `signin.magic-link`, `signin.forgot-password`, `security.two-factor`, `email.login-failed`.

### Sign in with a link instead of a password

- **id.** `signin.magic-link`
- **Purpose.** Explain passwordless sign in, which most readers will not have met before.
- **Audience.** All.
- **Summary.** What it is: you give your address, the product emails you a link, you click it, you are in. Why you would: you cannot remember your password, or you would rather not type one. The three rules that matter, all stated up front because each one catches people out: the link works **once**, it expires after **five minutes** by default, and it works from any browser on any device, so you can request it on your laptop and open it on your phone. What to do when it expires: ask for another. Why the confirmation screen says "if that address has an account", rather than confirming it does. That requesting links is rate limited to six a minute. That, unlike a password sign in, getting in this way sends you an email saying so, and why that is deliberate.
- **Prerequisites.** An account.
- **Complexity.** Medium.
- **Related pages.** `email.magic-link`, `email.new-login`, `signin.password`.

### Forgotten your password

- **id.** `signin.forgot-password`
- **Purpose.** Get somebody who is locked out back in.
- **Audience.** All.
- **Summary.** A `::::steps` walkthrough: request the link, open the email, choose a new password, sign in with it. The rules: the reset link works once and is thrown away as soon as it is used, and the new password must be at least eight characters and typed twice. The message you get either way, "if that address has an account, a link is on its way", and why it is worded like that. What "that link no longer works, ask for another one" means and what to do about it. That requests are rate limited. Offer the alternative for somebody who only needs to get in this once: a sign in link is faster and changes nothing.
- **Prerequisites.** An account.
- **Complexity.** Low.
- **Related pages.** `signin.magic-link`, `security.password`, `troubleshoot.no-email`.

### Signing out

- **id.** `signin.sign-out`
- **Purpose.** Cover the small thing nobody documents and everybody eventually needs.
- **Audience.** All.
- **Summary.** How to sign out, and what it does: your session ends on that browser, and remember me is forgotten with it. That signing out on one device does not sign you out on the others, and that there is currently no screen to end your other sessions. What to do if you think somebody else is signed in as you: change your password, and link there.
- **Prerequisites.** Being signed in.
- **Complexity.** Low.
- **Related pages.** `security.password`, `security.suspicious-activity`.

---

## Section 5: Your profile and your logs

**Why this section exists.** The profile screen is the first and, so far, the only place in the signed in application where somebody changes something about themselves. It is what a new user reaches for once they are in, and it is where the abstract distinction between an employee and an account finally becomes something you can click. It also holds the one piece of genuinely private information the product asks for, which deserves its own page rather than a paragraph. Beside it in the sidebar sits **Logs**, the second screen of the signed in application, where somebody reads what they have done and what the product sent them. The two belong together in the portal because they are the two things the sidebar offers, and because a reader who has just changed something goes looking for the record of it.

**Who it is for.** Every employee with an account.

**On disk.** `5-your-profile/`. Section value `your-profile`.

### Your profile

- **id.** `profile.introduction`
- **Purpose.** Section index, and the reader's first tour of a screen inside the application.
- **Audience.** All.
- **Summary.** Where the screen is and how to reach it: **Settings**, then **Profile**, at `/settings/account/profile`. What it is made of, in the order it appears: your avatar, the details your colleagues can see, and your emergency contact, which they cannot. Make the connection to the concepts section explicit, because this is where it lands: this screen edits your **employee record**, not your account, which is why your sign in address is nowhere on it. Note what the sidebar shows and what it does not: under **Profile** sit **Logs**, which holds the record of what you have done and the emails the product sent you, **Security and access**, where you change your password and look after your API keys, and **Preferences**, where you choose the language of the interface and the clock times are written on. Because this is the first page in the portal to send somebody to the sidebar, add one sentence for readers who cannot see one: on a phone that sidebar is behind the menu button at the top left of the screen, and `appearance.mobile` explains it.
- **Prerequisites.** `concepts.employee-vs-user`.
- **Complexity.** Low.
- **Related pages.** The three pages below, `concepts.employee-record`, `security.introduction`, `appearance.mobile`.

### Edit your details

- **id.** `profile.details`
- **Purpose.** Walk somebody through the only editing form the product has, and explain the one field whose behavior is not obvious.
- **Audience.** All.
- **Summary.** The four fields, and which are required: **first name** and **last name** are, **display name** and **work email** are not. Explain the display name properly, since it is the field people ask about: it is the name you go by, and when you give one the product calls you by it everywhere, including the sidebar and your initials; leave it empty and your legal name is used instead. Say plainly who sees all of this: everyone in your company. Describe what saving looks like, because it is not what a reader expects from a web form: the page does not reload, a short confirmation slides into the bottom right corner and leaves on its own after a few seconds (it can be dismissed sooner with its close button), and the **Last saved** line under the fields updates in place. Note that saving is recorded in the company's activity log. Point at `profile.photo` for the avatar box rather than covering it here.
- **Prerequisites.** `profile.introduction`.
- **Complexity.** Low.
- **Related pages.** `concepts.employee-record`, `concepts.activity-log`, `profile.photo`, `profile.logs`, `profile.emergency-contact`.

### Add a profile photo

- **id.** `profile.photo`
- **Purpose.** Walk somebody through putting a face to their name, and answer the two questions they will have about it: who can see it, and how to get rid of it.
- **Audience.** All.
- **Summary.** What the avatar is and where it turns up: beside your name across the product, in the sidebar and on your profile. What the product accepts, in the screen's own words: a JPEG, PNG or WebP up to 5 MB, square for the best result, because the avatar is shown in a circle and anything else is cropped to the middle rather than squashed. What happens after you choose a file and press **Upload**: the picture is converted to WebP and kept at two sizes, one for an ordinary screen and one for a sharp one, so it stays crisp on a laptop without costing a phone extra data. Say that the file you picked is not the file that is stored: it is resized, renamed, and the name you gave it is discarded. Be exact about who can see it, because this is where a reader looks for that: the photo is not on a public address, it is served through the application, and only to people who work at the same company. Then removal: **Remove the photo** asks once more before it goes, and taking it away brings back the initials the product drew before. Note that both uploading and removing are recorded in the company's activity log, and that neither is something an administrator can do on your behalf today.
- **Prerequisites.** `profile.introduction`.
- **Complexity.** Low.
- **Related pages.** `profile.details`, `concepts.employee-record`, `concepts.activity-log`, `security.privacy`.

### Add an emergency contact

- **id.** `profile.emergency-contact`
- **Purpose.** Explain the one form in the product that asks for somebody else's personal details, and be exact about who can read them.
- **Audience.** All.
- **Summary.** What it is for, in the product's own framing: who to call if something happens to you at work. The three fields, all optional: **name**, **phone number**, **relationship**. Be precise about privacy, because this is the page where a reader will be looking for it. What the code actually does: the details are stored on your employee record, they are never written into the activity log, which records only that they changed, and no screen in the product shows them to anybody but you. Note that the screen tells you your company administrators can see this, and that no such screen exists yet, so today the honest answer is that only you can read them in the application. Tell the reader they can empty the fields and save to remove the contact, and that saving here behaves exactly as it does on the details form: no reload, and a confirmation in the bottom right corner that goes away by itself.
- **Prerequisites.** `profile.introduction`.
- **Complexity.** Medium, because the privacy claim has to be worded carefully.
- **Related pages.** `security.privacy`, `concepts.employee-record`, `concepts.activity-log`.

### Read what you have done

- **id.** `profile.logs`
- **Purpose.** Show somebody where their own record of activity is, and teach them to read a line of it.
- **Audience.** All, and particularly anybody who has had an email about a sign in they do not recognise.
- **Summary.** Where it is: **Logs** in the settings sidebar, under **Profile**, at `/settings/account/logs`, where the **Logs** box is the first thing on the screen. Say plainly whose actions these are: your own, and only your own. Read one line apart, because each part answers a different question: the name of whoever acted, the short technical name of the action in a monospaced face, which is the thing to quote when asking for help, the sentence describing what happened, and how long ago it was, with the exact date and time available by resting the pointer on it. That the newest is first. How the screen loads more: five at a time, with a **Load more** link that adds the next five under the ones already there without leaving the page, and which disappears when there is nothing left. What a new account sees instead of rows, since that is what most readers will meet first: a blank panel saying **No activity yet**, explaining that nothing has happened on the account and that changes made by the reader or by an administrator will show up here. Say that this is the normal state of a fresh account and not a fault. Two honest notes: an action the product no longer recognises shows its raw name in place of a sentence, and there is no way to filter, search or export the log today.
- **Prerequisites.** `profile.introduction`, `concepts.activity-log`.
- **Complexity.** Low.
- **Related pages.** `concepts.activity-log`, `security.suspicious-activity`, `email.record`.

### Read the emails we sent you

- **id.** `profile.emails`
- **Purpose.** Show somebody where to check what the product sent them, and teach them to read the delivery state, which is the reason most people open this box in the first place.
- **Audience.** All, and in practice anybody who was expecting an email that never arrived.
- **Summary.** Where it is: the **Emails sent** box under the logs, on the same **Logs** screen, the five most recent first, with **Browse all emails** leading to the whole list at `/settings/account/logs/emails`, ten at a time behind a **Load more** link. What a row shows: who it went to, the subject, and how long ago it left. What the coloured dot means, and this is the part worth being exact about: amber means the email left but the mail service has not confirmed it arrived, green means it was delivered, red means it bounced. Say plainly that on most instances the dot stays amber for good, because delivery and bounce reporting only arrives from a provider that reports it back, and that amber therefore is not a sign of a problem. What happens when a row is opened: the copy of the email as it was sent, minus its links. Explain that omission honestly rather than as a footnote, since a reader who wanted to click the link will be looking for it: sign in links are stored nowhere, because a stored link is a stored way into the account, and any link in an old email has almost certainly expired anyway. What an account that has received nothing sees: a blank panel saying **No emails yet**, naming the kinds of email that will land there, such as sign in links and password changes. Close with the diagnostic use: no entry at all means the email was never sent, which is a different problem from one that was sent and never arrived, and point at the troubleshooting page for both.
- **Prerequisites.** `profile.introduction`.
- **Complexity.** Medium, because the delivery states have to be explained without alarming anybody.
- **Related pages.** `email.record`, `email.introduction`, `troubleshoot.no-email`, `security.privacy`.

---

## Section 6: Your account and its security

**Why this section exists.** Account and security questions are the ones users ask under pressure, when something looks wrong. They need direct, calm answers in one place. This section also has to be scrupulous about which protections are on by default, which are optional, and which are half built.

**Who it is for.** Everybody, plus owners for the parts about other people's accounts.

**On disk.** `6-account-and-security/`. Section value `account-and-security`.

### Your account and its security

- **id.** `security.introduction`
- **Purpose.** Section index, and a short account of how OfficeLife protects you without being asked.
- **Audience.** All.
- **Summary.** What is on by default for everyone: passwords are stored hashed and never recoverable, sign in attempts are rate limited, failed attempts and sign ins from a new place email you, sign in links are single use and short lived, and only the fingerprint of a sign in link is ever stored so the link in your inbox cannot be rebuilt from the database. Then what you can add: a stronger password, and a code from your phone. Link onwards.
- **Prerequisites.** An account.
- **Complexity.** Medium.
- **Related pages.** All pages in this section.

### Change your password

- **id.** `security.password`
- **Purpose.** Explain what the product requires of a password and what changing one does.
- **Audience.** All.
- **Summary.** Where the screen is: **Security and access** in the settings sidebar, at `/settings/account/security`, whose first box changes the password. What it asks for and why all three fields are there: the current password, because we hold only a hash and cannot show you the old one, then the new one twice. The rule, which is short: at least eight characters. Advice on choosing one, in the screen's own framing, that long beats complicated. What saving looks like, since it is not what a reader expects from a web form: the page does not reload, a confirmation slides into the bottom right corner, and the fields come back empty. What a wrong current password looks like: a message under that field, and nothing changed. The line to the left of the Save button, which answers the question the box invites: **Last changed**, followed by how long ago it was, in words rather than a date. Explain what it counts, because it is not only what the reader did on this screen: the clock starts when the password was first set, at registration, and is reset by a change made here or through a reset link, so a brand new account already shows the day it signed up. Say what it means when the line is absent, since a reader will meet that case: the account has never had a password set, which today means it signs in through an identity provider. That the change is recorded in the company's activity log, so it can be read back on the Logs screen. That you stay signed in on this browser, and that the product cannot end your other sessions today, so a password nobody else should know is worth changing from every device you use. That an account which signs in through an identity provider sees a line of explanation in place of the form, because there is no password to change. Close with the other way in, for somebody who cannot remember the current one: the forgotten password flow, in one line, with a link.
- **Prerequisites.** An account.
- **Complexity.** Medium.
- **Related pages.** `signin.forgot-password`, `concepts.activity-log`, `security.suspicious-activity`.

### Two factor authentication

- **id.** `security.two-factor`
- **Purpose.** Explain what two factor authentication is, why it is worth the trouble, and what changes about signing in once it is on.
- **Audience.** Employees, and operators.
- **Summary.** What two factor authentication is and why it helps: a password can be guessed, reused or stolen from a distance, and a code that changes every thirty seconds on a phone in a pocket cannot. Where the switch is: **Settings**, then **Security and access**, the box under the password. What it looks like when it is off, and what turning it on gives you. Then how the challenge works from then on: after your password is accepted you are not signed in, you are asked for the six digits your authenticator app is showing, and only then are you in. That the same happens after a sign in link, since a link that skipped the password must not also skip the code. That a recovery code works in place of the app's code, that each one works once and is spent the moment it is used, and that this is what you use when you have lost your phone. What "that code is not right" and "that took too long, please sign in again" mean, the second being a session that expired between the two screens. That attempts at the challenge are rate limited.
- **Prerequisites.** `signin.password`.
- **Complexity.** Medium.
- **Related pages.** `security.two-factor-set-up`, `security.recovery-codes`, `signin.password`, `signin.magic-link`, `security.suspicious-activity`.

### Turn on two factor authentication

- **id.** `security.two-factor-set-up`
- **Purpose.** Walk somebody through enrolling, with their phone in one hand, without them fearing they will lock themselves out.
- **Audience.** Employees.
- **Summary.** A tutorial, in the order the screens come. What you need first: an authenticator app on your phone, any of them. **Settings**, then **Security and access**, then **Turn it on**, which lands on a screen with two numbered steps. Step one: point the app's camera at the square, or, when the app is on the same machine you are reading this on and there is no camera to point, type in the secret printed beside it, which is the same thing in letters. That the app files the account under your email address, which is how you will recognise it in a list. Step two: type back the six digits the app is showing, and press the button. Say plainly what the screen says, because it is the sentence that stops people hesitating: nothing about your account changes until that code is accepted, so leaving halfway through leaves you exactly as you were. What happens next: you land back on Security and access, the box now says the feature is on, and your recovery codes are printed underneath. Send the reader to `security.recovery-codes` before they close the tab. Then how to turn it off, and what turning it off costs: the secret and the codes are both forgotten, so turning it on again means pairing the app afresh, and until then your password is all it takes to sign in. Note that the screen asks you to confirm before it does either of those things.
- **Prerequisites.** `security.two-factor`.
- **Complexity.** Medium.
- **Related pages.** `security.recovery-codes`, `security.two-factor`, `profile.logs`.

### Your recovery codes

- **id.** `security.recovery-codes`
- **Purpose.** Make sure somebody actually saves the codes, and knows what to do when they run low.
- **Audience.** Employees who have turned two factor authentication on.
- **Summary.** What they are: eight codes, each of which signs you in once in place of the code your app would show, for the day the phone is lost, broken or wiped. Where they are: on the Security and access screen, under the two factor box, for as long as the feature is on. Where to keep them, which is anywhere that is not the phone the app is on. That a code disappears from the list the moment it is used, so the list doubles as a count of how many you have left. How to get a fresh set, with the warning the screen gives: asking for new ones stops every old one working, immediately, so do it when you have somewhere to put them. When to do it: when you are running low, or when you think somebody else has seen the list. What the screen says when you have spent them all, and why you should not wait for that. That both handing out a new set and turning the feature on or off are written to your logs.
- **Prerequisites.** `security.two-factor-set-up`.
- **Complexity.** Low.
- **Related pages.** `security.two-factor-set-up`, `profile.logs`.

:::note for the writer
Recovery codes are stored encrypted but they are recoverable, which is why the screen can keep showing them rather than printing them once and forgetting. Do not write "save these now, you will never see them again", because that is not how this product behaves and a reader who believed it would panic. Say instead that they stay on the Security and access screen for as long as the feature is on.
:::

### Your API keys

- **id.** `security.api-keys`
- **Purpose.** Explain what an API key is, how to make one and revoke one, and be straight about the fact that nothing accepts one yet.
- **Audience.** Employees and operators who write scripts, and anybody who finds the box and wonders what it is for.
- **Summary.** What a key is, in the screen's own framing: a password for machines, which lets a script or another system act as you with exactly the access you have. Where the box is: **Settings**, then **Security and access**, the third box, under two factor authentication. Say the limitation first rather than burying it, because the screen does: there is nothing to point a key at today, since the API that would accept one is not built, and a key made now starts working the moment it lands. How to make one: **Create key**, which opens a field for a name in place of the button, a name of at least three characters, and what the name is for, which is telling one key from another in a year's time. What happens next, and the one sentence that matters most on the page: the key is printed once, in a block at the top of the box, and only a hash of it is kept, so a key you fail to copy is replaced rather than looked up. That it is gone from the screen as soon as you reload. What a row shows: the name, how long ago it was made, and when it was last used, which reads "never used" until something uses it, and will keep reading that until there is an API. How to revoke one: **Revoke**, which asks first, and takes effect immediately. That keys are personal, so a colleague cannot see or revoke yours and you cannot see theirs. That making and revoking a key are both written to your logs, with the name of the key in the entry.
- **Prerequisites.** An account.
- **Complexity.** Low.
- **Related pages.** `concepts.activity-log`, `security.introduction`, `getting.what-works-today`.

:::note for the writer
Do not write this page as an API tutorial, and do not show a request carrying the key: there is no endpoint to send it to. The page documents the key management screen and nothing more. Keys are Sanctum personal access tokens with no abilities and no expiry, so do not promise scopes, read only keys, or keys that lapse on their own. Revisit this page the moment the API ships; the limitation paragraph comes out and the page grows a link to the API reference.
:::

### When something looks wrong

- **id.** `security.suspicious-activity`
- **Purpose.** Give a worried reader one page that tells them what happened and what to do.
- **Audience.** All.
- **Summary.** Organised by what landed in their inbox. "A failed sign in attempt": somebody typed your address and the wrong password; if it was not you, change your password, because somebody knows your address and is guessing. "A sign in from a new place": you signed in from an address the product has not seen before; travelling, a VPN, a new phone or a browser update all cause this, so it is often nothing, but if you do not recognise it, change your password. "You signed in without a password": somebody used a sign in link for your account; the link only works once and is already spent, but whoever used it can read your email, so change your password. For each, the concrete steps, in order: change your password, sign out, tell whoever runs your company. Point out the one fact the Security and access screen gives a worried reader for free: the **Last changed** line next to the Save button says how long ago the password was last set, so somebody who suspects a change they did not make can check it in a glance. Explain what the product does and does not check, so nobody over trusts it: it compares your sign in address with the previous one and only notices a change, it does not geolocate, score risk, or block anything.
- **Prerequisites.** `security.introduction`.
- **Complexity.** Medium.
- **Related pages.** `email.login-failed`, `email.new-login`, `email.ip-changed`, `security.password`.

### Suspended and deleted accounts

- **id.** `account.delete`
- **Purpose.** Explain what happens to an account and its data when somebody leaves, which is an HR product's most predictable question.
- **Audience.** Owners, and employees asking about themselves.
- **Summary.** Two different things, kept apart. **Suspending**: an account can be marked inactive, which stops it signing in entirely, including by sign in link, without deleting anything. Note that this is a state the product supports but no screen sets today. **Deleting**: only the owner can delete an account in their company, and the owner cannot delete their own. What deletion leaves behind, which is the part people need to understand: the employee record survives, because the person still worked here, and the activity log keeps the email address of anybody who acted, so the history still says who did what. Deleting the whole company, which only the owner can do, removes its people, its accounts, its logs and its record of emails with it. Note that neither deletion has a screen yet.
- **Prerequisites.** `concepts.owner`, `concepts.employee-vs-user`.
- **Complexity.** High. Get the consequences exactly right and put them in a `:::warning`.
- **Related pages.** `concepts.employee-vs-user`, `concepts.activity-log`, `security.privacy`.

### What OfficeLife knows about you

- **id.** `security.privacy`
- **Purpose.** Answer the privacy question directly, for a product that by its nature holds personal data.
- **Audience.** Employees.
- **Summary.** What is stored about a person, pointing at the employee record page for the full list rather than repeating it. What is stored about an account: your address, when and from where you last signed in, your language. What is stored about your activity, pointing at the log page. What is stored about the emails sent to you: every one, with its subject and body, so your company can see what it sent, with the links stripped out. What is protected: passwords are hashed, two factor secrets and recovery codes are encrypted, and only the fingerprint of a sign in link is stored. Who can see what, as the code actually allows: everything is scoped to your company, and the owner has the extra powers listed on the owner page. Where the privacy policy and terms live, and that a self hosted instance points at its own.
- **Prerequisites.** `concepts.employee-record`.
- **Complexity.** Medium.
- **Related pages.** `concepts.activity-log`, `email.record`, `account.delete`.

---

## Section 7: Language and appearance

**Why this section exists.** The things every reader can change immediately, on any screen, signed in or not, plus the one thing that changes on its own: the interface rearranges itself on a small screen, and a reader who meets it on a phone needs to be told where the navigation went. Small, satisfying, and genuinely finished.

**Who it is for.** Everybody.

**On disk.** `7-language-and-appearance/`. Section value `language-and-appearance`.

### Language and appearance

- **id.** `language.introduction`
- **Purpose.** Section index.
- **Audience.** All.
- **Summary.** Five short pages: pick your language, keep that choice on your account along with the clock times are written on, understand which language setting wins, pick light or dark, and find your way around on a phone.
- **Prerequisites.** None.
- **Complexity.** Low.
- **Related pages.** Every page below.

### Change the language of the interface

- **id.** `language.change`
- **Purpose.** Show the picker and explain which setting wins.
- **Audience.** All.
- **Summary.** Where the picker is and how to use it, including that it works before you sign in. The four languages currently shipped: English, French, German and Spanish. Then the precedence rule, in the reader's terms and in order: what you picked in this session wins, because it is the most recent thing you asked for; failing that, the language on your account; failing that, your company's language; failing that, the application's own default. The practical consequence: a choice made from the picker on a guest screen lasts as long as your session, which is why the interface can revert when you next visit, and why the way to make a language stick is to choose it on the Preferences screen once you are signed in. Point at `preferences.screen` for that rather than explaining it twice. That translations are community maintained and how to help.
- **Prerequisites.** None.
- **Complexity.** Medium. The precedence chain is the whole page.
- **Related pages.** `preferences.screen`, `language.company-default`, `hosting.locales`.

### Your preferences

- **id.** `preferences.screen`
- **Purpose.** Cover the Preferences screen: the two choices it holds, and the fact that they belong to the account rather than to the browser.
- **Audience.** All.
- **Summary.** Where the screen is and how to reach it: **Settings**, then **Preferences**, at `/settings/account/preferences`. The sentence at the top of it is the point of the page, so make it early and plainly: these settings apply to your account only, and they follow you to every device you sign in from, which is what separates them from the theme toggle, covered by `appearance.theme`, which stays in the browser you set it in. Then the two rows, in the order they appear. **Language**, the same four languages as the picker on the guest screens, saved to your account this time, so it survives signing out; link to `language.change` for the precedence rule rather than restating it. **Time format**, either a 24 hour or a 12 hour clock, with the same afternoon hour written both ways beside each choice so the difference is visible rather than described. Then the mechanics of the screen, which are unusual enough to be worth one sentence each: there is no save button, because picking a value from a menu saves it immediately and a confirmation slides in at the corner; and choosing a new language redraws the screen in it there and then, confirmation included, so the change is visible without reloading. Close with what changing either one is recorded as: a line in your logs saying you changed your preferences, which `profile.logs` explains how to read.
- **Prerequisites.** Being signed in. `language.change` for the precedence rule.
- **Complexity.** Low.
- **Related pages.** `language.change`, `appearance.theme`, `profile.logs`.

:::note for the writer
Do not promise that the time format changes how times look everywhere. The choice is stored on the account and the screen shows it back to the reader, but the screens that display times, the logs and the emails sent, still write them their own way; the setting is not yet threaded through them. Say what is true: you choose it here, and it is what the rest of the product will use as it is built. Do not list screens it affects, because today there are none.
:::

### Your company's default language

- **id.** `language.company-default`
- **Purpose.** Explain the company wide setting for the person who chooses it.
- **Audience.** Owners.
- **Summary.** What the company language is for: the language anybody sees who has not chosen one. That it is set to English when the company is created, alongside a timezone of UTC. That changing it has no screen yet. Keep this page short and link back to the precedence rule rather than restating it.
- **Prerequisites.** `language.change`, `concepts.company`.
- **Complexity.** Low.
- **Related pages.** `concepts.company`, `hosting.locales`.

### Light and dark

- **id.** `appearance.theme`
- **Purpose.** Cover the theme toggle.
- **Audience.** All.
- **Summary.** Where the toggle is, what it does, that it applies to your browser, and that it works before you sign in. Note where it sits on a narrow screen, since the answer is not the same: on a phone the toggle rides inside the menu rather than in the sidebar, so it is reached through the menu button. Link to `appearance.mobile` rather than explaining the menu here.
- **Prerequisites.** None.
- **Complexity.** Low.
- **Related pages.** `language.change`, `appearance.mobile`.

### Using OfficeLife on a phone

- **id.** `appearance.mobile`
- **Purpose.** Tell somebody on a small screen where the navigation went, so they do not conclude the product has fewer screens on a phone than on a laptop.
- **Audience.** All.
- **Summary.** The one thing worth saying first: nothing is missing on a phone, it is only folded away. On a wide screen the settings sidebar is a column down the left; below roughly a laptop width it becomes a menu that slides in over the page, and the button that opens it is the three lines at the left of the bar at the top of every signed in screen. What the menu holds, which is exactly what the column held: your company, the light and dark toggle, **Profile**, **Logs**, **Security and access**, **Preferences**, and your own name at the foot. The three ways to close it: the cross beside the toggle, a tap on the dimmed page behind it, or the escape key on a device with a keyboard. That picking a destination closes it and takes you there. Then the smaller adjustments a reader may notice and wonder about, each in a sentence: the email address in the top bar is hidden on the narrowest screens because the menu already says who you are signed in as, the "?" help bubbles open along the bottom edge of the screen instead of beside the thing they explain, and the rows on the Logs screen let the time drop onto its own line rather than squeezing the sentence. Close by saying the product is the same product either way, with no separate mobile version and nothing to install.
- **Prerequisites.** Being signed in.
- **Complexity.** Low.
- **Related pages.** `profile.introduction`, `appearance.theme`, `profile.logs`.

:::note for the writer
Do not describe this as an app. There is no native application and no app store listing; this is the same web interface responding to the width of the window, which is also why the same rearrangement happens on a laptop with a narrow browser window. Do not name pixel widths in the page: say "on a phone" and "on a wide screen", because the exact breakpoints are an implementation detail that will move.
:::

---

## Section 8: Emails from OfficeLife

**Why this section exists.** Five emails exist, three of them are security warnings, and a reader who receives one arrives at the portal with that exact subject line in their hand. A page per email, findable by its subject, is the fastest possible answer. This section is also where the "we keep a copy of what we sent you" fact belongs.

**Who it is for.** Employees who received something, and owners who want to know what the product sends.

**On disk.** `8-emails/`. Section value `emails`.

### Emails from OfficeLife

- **id.** `email.introduction`
- **Purpose.** Section index and a complete inventory.
- **Audience.** All.
- **Summary.** The five emails the product sends, each with its subject line and a link to its page: confirm your email address, your sign in link, a failed sign in attempt, you signed in without a password, and a sign in from a new place. State the promise the product makes on its own screens and repeat here: it never sends marketing email. Explain that every one of them is triggered by something happening on your account, so an unexpected one is worth reading.
- **Prerequisites.** None.
- **Complexity.** Low.
- **Related pages.** Every page in this section.

### Confirm your email address

- **id.** `email.verify`
- **Purpose.** Explain the email a new account receives.
- **Audience.** New users.
- **Summary.** When it is sent, what the button does, that the link expires after an hour, and how to get another. That you can safely ignore it if you did not create an account.
- **Prerequisites.** None.
- **Complexity.** Low.
- **Related pages.** `getting.confirm-email`.

### Your sign in link

- **id.** `email.magic-link`
- **Purpose.** Explain the passwordless sign in email.
- **Audience.** All.
- **Summary.** When it is sent, that the link works once and only for the next few minutes, and that the number of minutes is named in the email itself because an instance can change it. What to do when you did not ask for it: ignore it, because somebody typed your address by mistake or is checking whether you have an account, and nothing has happened to your account.
- **Prerequisites.** None.
- **Complexity.** Low.
- **Related pages.** `signin.magic-link`.

### A failed sign in attempt

- **id.** `email.login-failed`
- **Purpose.** Explain a warning email that alarms people.
- **Audience.** All.
- **Summary.** What it means: somebody tried to sign in to your account and gave the wrong password. That it is sent on every failed attempt against a known address. If it was you, nothing is wrong: try again, or use a sign in link. If it was not, change your password now, because somebody knows your address and is guessing.
- **Prerequisites.** None.
- **Complexity.** Low.
- **Related pages.** `security.suspicious-activity`, `security.password`.

### You signed in without a password

- **id.** `email.new-login`
- **Purpose.** Explain the confirmation sent after a sign in link is used.
- **Audience.** All.
- **Summary.** What it means, and the address it was used from. Why it is sent for link sign ins but not password sign ins: getting in without a password is worth telling you about every time. If it was not you, change your password now, and note the specific reasoning the email gives: the link is already spent and cannot be used again, but whoever used it can read your email, which is the real problem.
- **Prerequisites.** None.
- **Complexity.** Low.
- **Related pages.** `signin.magic-link`, `security.suspicious-activity`.

### A sign in from a new place

- **id.** `email.ip-changed`
- **Purpose.** Explain the most frequently misread of the five.
- **Audience.** All.
- **Summary.** What triggers it: you signed in from an address the product had not seen since your last sign in. Why it is usually nothing: travelling, a VPN, a new phone, a browser update. That your very first sign in never triggers it, because there was nothing to compare against. What to do if you do not recognise it. Be precise about what the check is and is not, so nobody reads more into it than it says: it compares one address with the previous one, nothing more.
- **Prerequisites.** None.
- **Complexity.** Medium.
- **Related pages.** `security.suspicious-activity`.

### The record of what was sent

- **id.** `email.record`
- **Purpose.** Explain that the product keeps a copy of every email it sends.
- **Audience.** Owners and privacy conscious employees.
- **Summary.** What is kept: the type of email, the address it went to, the subject, the body, and when it was sent, delivered, or bounced. Why: so a company can see exactly what was sent on its behalf, and to whom. The one deliberate omission: links are stripped out of the stored copy, because a stored sign in link would be a stored way into somebody's account. That delivery and bounce information is only filled in on instances using a mail provider that reports it back. Where to read it, in one line, with the walkthrough left to the profile section: the **Emails sent** box on your profile.
- **Prerequisites.** `security.privacy`.
- **Complexity.** Medium.
- **Related pages.** `profile.emails`, `security.privacy`, `hosting.email`.

---

## Section 9: Running your own instance

**Why this section exists.** OfficeLife is open source, and self hosting is a first class way to use it. This audience is entirely different from every other section: they read commands, not walkthroughs. Everything here is grounded in the composer scripts, the environment file, and the configuration.

**Who it is for.** Operators and developers only. Say so at the top of every page.

**On disk.** `9-self-hosting/`. Section value `self-hosting`.

### Running your own instance

- **id.** `hosting.introduction`
- **Purpose.** Section index, and an honest statement of what self hosting involves today.
- **Audience.** Operators.
- **Summary.** What you need: PHP 8.4 or later, Composer, Bun, and a database. That SQLite, MySQL and PostgreSQL are all exercised by the project's own test suite, so all three work. That there is no Docker image or compose file in the repository today, so installation is a manual PHP deployment. What the section covers, in order.
- **Prerequisites.** Comfort with a command line.
- **Complexity.** Medium.
- **Related pages.** Every page in this section.

### Install OfficeLife

- **id.** `hosting.install`
- **Purpose.** Take an operator from a clone to a running instance.
- **Audience.** Operators.
- **Summary.** A `::::steps` walkthrough built on the `composer setup` script the repository already defines, which installs dependencies, creates the environment file, generates the application key, runs the migrations, and builds the front end assets. Then what setup does not do and you must: choose and configure a database, configure mail, serve the application, and run a queue worker. Cover the local development path separately and briefly, since `composer dev` runs the server, the queue worker, the log tail and the asset watcher together. Point out that Laravel Herd serves the site automatically for anyone developing on macOS.
- **Prerequisites.** `hosting.introduction`.
- **Complexity.** Medium.
- **Related pages.** `hosting.configuration`, `hosting.queue`, `hosting.email`.

### Configure your instance

- **id.** `hosting.configuration`
- **Purpose.** A reference for every setting an operator can change, in one scannable place.
- **Audience.** Operators.
- **Summary.** A reference page, tables rather than prose. Application basics: name, environment, key, URL, debug, default and fallback locale. Database. Session driver and lifetime. Queue connection. Cache. Then the settings specific to OfficeLife, each with its default and, crucially, what the user sees change: how many days "remember me" lasts (thirty, and the sign in screen names this number, so changing it changes what you promise your users), how many minutes a sign in link stays valid (five, deliberately short, and the email names it), and the URLs of the terms of use and the privacy policy, which point at officelife.io by default and which any instance running its own documents must repoint. Mail settings and the Cloudflare Turnstile keys get their own pages below.
- **Prerequisites.** `hosting.install`.
- **Complexity.** High. Completeness and accuracy matter more than prose here.
- **Related pages.** `hosting.email`, `hosting.turnstile`, `hosting.locales`.

### Send email from your instance

- **id.** `hosting.email`
- **Purpose.** Get outbound mail working, without which nobody can confirm an address or reset a password.
- **Audience.** Operators.
- **Summary.** Why this matters first: five of the product's flows depend on email. The two paths. Ordinary Laravel mail, configured with the standard mail settings and working with any SMTP service, which is the default. Or Resend, switched on with a single flag and an API key, which additionally captures the provider's own identifier for each message so delivery and bounce information can be recorded. State plainly that the delivery and bounce columns stay empty on the ordinary path. The from address and name, which appear on every email your people receive. How to check it works in development, where mail is written to the log by default.
- **Prerequisites.** `hosting.install`.
- **Complexity.** Medium.
- **Related pages.** `email.record`, `hosting.queue`.

### Run the queue

- **id.** `hosting.queue`
- **Purpose.** Explain the piece of the deployment that silently breaks the product when it is missing.
- **Audience.** Operators.
- **Summary.** What runs in the background and therefore does not happen without a worker: every email the product sends, every entry in the activity log, and the check that notices a sign in from a new address. The symptom an operator will actually see if they skip this, which is that nobody ever receives a confirmation email and the product looks broken. That the default connection is the database, so no extra service is needed to start. That work is dispatched to a `high` queue for anything a person is waiting on, and a `low` queue for record keeping, so a worker should be told to drain `high` first. How to run one, and what to use in production to keep it running.
- **Prerequisites.** `hosting.install`.
- **Complexity.** Medium.
- **Related pages.** `hosting.email`, `concepts.activity-log`.

### Protect your sign up and sign in screens

- **id.** `hosting.turnstile`
- **Purpose.** Explain the optional human check.
- **Audience.** Operators.
- **Summary.** What Cloudflare Turnstile is and what it protects: the registration and sign in forms. That it is off by default and needs a site key and a secret key from Cloudflare. What your users see when it is on, and what they see when it fails. The deliberate strictness worth knowing before you enable it: if the check cannot be verified for any reason, including Cloudflare being unreachable from your server, the form is refused rather than let through. What else already protects those forms without it: rate limiting on sign in, on link requests and on password resets, and the rejection of disposable email addresses at sign up.
- **Prerequisites.** `hosting.configuration`.
- **Complexity.** Medium.
- **Related pages.** `getting.create-account`, `signin.password`.

### Languages on your instance

- **id.** `hosting.locales`
- **Purpose.** Explain how languages are configured and how to add one.
- **Audience.** Operators and translators.
- **Summary.** Where the shipped languages are declared, and that each is a locale key, a label, a region and a flag drawn in CSS rather than shipped as an image. Where the translated strings live: one JSON file per locale, keyed by the English sentence. How the application default relates to the per company and per user settings, linking to the precedence page rather than repeating it. How to add a language: register the locale, add its file, translate it, contribute it back. That translations are community maintained.
- **Prerequisites.** `hosting.configuration`.
- **Complexity.** Medium.
- **Related pages.** `language.change`, `hosting.contributing`.

### Upgrade your instance

- **id.** `hosting.upgrade`
- **Purpose.** Tell an operator how to move to a new version safely.
- **Audience.** Operators.
- **Summary.** The order that matters: back up the database first, pull, install dependencies, run migrations, rebuild assets, restart the queue worker so it picks up the new code. Why the last step is the one people forget. Keep it short and mechanical, and state clearly that the project publishes no formal release process yet, so this is the general procedure rather than a versioned upgrade guide.
- **Prerequisites.** `hosting.install`.
- **Complexity.** Medium.
- **Related pages.** `hosting.backups`, `hosting.queue`.

### Back up your data

- **id.** `hosting.backups`
- **Purpose.** Cover the thing an HR system's operator cannot get wrong.
- **Audience.** Operators.
- **Summary.** What matters and why: the database holds everything the product knows, including personal details, emergency contacts, and the record of every email sent. That the encrypted columns, two factor secrets and recovery codes, are readable only with the application key, so a database backup without that key is not a complete backup and will not restore. How to back up each supported database. What else to keep: the environment file and the application key. Testing that a restore actually works. No backup tooling ships with the product, so this is guidance rather than a feature.
- **Prerequisites.** `hosting.install`.
- **Complexity.** Medium.
- **Related pages.** `hosting.upgrade`, `security.privacy`.

### Contribute to OfficeLife

- **id.** `hosting.contributing`
- **Purpose.** Turn interested readers into contributors, which for an open source product is a documentation job.
- **Audience.** Developers and translators.
- **Summary.** Where the code is, how to get a development environment running, and how to run the test suite. The conventions a contributor meets immediately, described rather than reproduced: business logic lives in one class per user action, controllers stay thin, code style is enforced by a formatter, and static analysis runs in continuous integration. That translations are the easiest first contribution. Link to the repository's own contributing guidance rather than duplicating it, and keep this page thin so it does not drift.
- **Prerequisites.** `hosting.install`.
- **Complexity.** Low.
- **Related pages.** `hosting.locales`.

---

## Section 10: Troubleshooting

**Why this section exists.** A reader with a problem does not know which section their problem lives in. This one is organised by symptom, in their words, and does nothing but route them or answer them.

**Who it is for.** Everybody.

**On disk.** `10-troubleshooting/`. Section value `troubleshooting`.

### Troubleshooting

- **id.** `troubleshoot.introduction`
- **Purpose.** Section index, organised by symptom.
- **Audience.** All.
- **Summary.** A short list of "if this is happening to you" links covering the pages below plus the frequent ones that live elsewhere: I got a warning email, I forgot my password, my language keeps resetting.
- **Prerequisites.** None.
- **Complexity.** Low.
- **Related pages.** All pages in this section.

### I cannot sign in

- **id.** `troubleshoot.cannot-sign-in`
- **Purpose.** Work through every reason a sign in fails, in the order they are likely.
- **Audience.** All.
- **Summary.** Symptom by symptom. "These credentials do not match our records": your password is wrong, or your account has been suspended, or that address has no account here; the message is the same for all three on purpose, and here is how to tell them apart in practice, by asking for a sign in link and seeing whether one arrives, or by asking whoever runs your company. "Too many sign in attempts": wait the number of seconds you were told. "That link no longer works": the link was used already or has expired, ask for another. "That code is not right" and "that took too long": the two factor cases. Somebody who has genuinely been suspended cannot get in by any route, including a link, and only the person running their company can fix it.
- **Prerequisites.** `signin.introduction`.
- **Complexity.** Medium.
- **Related pages.** `signin.password`, `signin.magic-link`, `security.two-factor`.

### I am not receiving emails from OfficeLife

- **id.** `troubleshoot.no-email`
- **Purpose.** Solve the single most common support request for any product that emails links.
- **Audience.** Employees, with a clearly separated part for operators.
- **Summary.** For the reader: it can take a minute, check spam, check you typed the address you actually signed up with, and remember that the confirmation screen says the same thing whether or not that address has an account. Then, plainly, if none of that helps, the problem is on the instance rather than with you, and here is what to tell whoever runs it. For the operator, a short checklist under its own heading: is a queue worker running, is mail configured, are messages going to the log instead of out, and is the from address one your provider accepts.
- **Prerequisites.** None.
- **Complexity.** Medium.
- **Related pages.** `hosting.queue`, `hosting.email`, `getting.confirm-email`.

### The interface is in the wrong language

- **id.** `troubleshoot.wrong-language`
- **Purpose.** Answer a small, common and genuinely confusing problem.
- **Audience.** All.
- **Summary.** Why the interface may not be in the language you expect, and why a choice you made earlier may have reverted: the picker's choice lasts for your session, and there is no per account language setting reachable from any screen yet. What to do about it. Link to the precedence rule rather than restating it.
- **Prerequisites.** `language.change`.
- **Complexity.** Low.
- **Related pages.** `language.change`, `language.company-default`.

---

## Section 11: Reference

**Why this section exists.** A few facts get asked repeatedly and belong somewhere scannable, not buried in prose. Reference pages are factual and short.

**Who it is for.** All, depending on the page.

**On disk.** `11-reference/`. Section value `reference`.

### Reference

- **id.** `reference.introduction`
- **Purpose.** Section index.
- **Audience.** All.
- **Summary.** What is here and what is not.
- **Prerequisites.** None.
- **Complexity.** Low.
- **Related pages.** All pages in this section.

### Words OfficeLife uses

- **id.** `reference.glossary`
- **Purpose.** A glossary, so no page has to stop and define its terms twice.
- **Audience.** All.
- **Summary.** Company, employee, user, account, owner, display name, legal name, employee number, work mode, size range, sign in link, recovery code, activity log. One or two sentences each, each linking to the page that explains it properly. Include the terms that mean something specific here and something looser elsewhere, above all employee and user.
- **Prerequisites.** None.
- **Complexity.** Medium.
- **Related pages.** `concepts.introduction`.

### Limits and defaults

- **id.** `reference.limits`
- **Purpose.** Put every number the product enforces in one table.
- **Audience.** All, and operators in particular.
- **Summary.** A single table: minimum password length, how long "remember me" lasts, how long a sign in link lasts, how long an email confirmation link lasts, how many sign in attempts before a lockout, the rate limits on link requests, password resets, resent confirmations and two factor attempts, how many recovery codes a set holds and how long each one is, and the session lifetime. Mark which are configurable per instance and which are fixed in code, and link to the configuration page for the former. Give defaults, not the values from any one instance.
- **Prerequisites.** None.
- **Complexity.** Medium. Verify every number against the code before publishing, and re-verify whenever it changes.
- **Related pages.** `hosting.configuration`, `signin.introduction`.

---

## Not yet documentable

Everything below is anticipated by the database, an enum, or a model method, but has **no screen, route, command or working implementation**. None of it may be written about as a feature. It is listed here so the gaps are known, so nobody invents a page for them, and so the page can be written the day the feature ships.

Each entry names the evidence, what is missing, and the page it should become.

- **Plans, trials and billing.** Every company carries a plan (free, starter, business, enterprise), a billing email address, and a trial end date set to thirty days at signup, and the model can answer whether it is still on trial. Nothing reads any of it. There is no billing code, no payment provider, no subscription library, and no screen. **Do not write pricing, plans, trials, invoices, or upgrade documentation.** When billing exists it becomes a section of its own, and `getting.cloud-or-self-hosted` will need rewriting.

- **Single sign on.** Accounts carry an identity provider field, the model can answer whether an account uses one, sign in refuses such accounts a password path, and password changes are refused for them with a specific message. No provider integration, configuration or route exists, so no account can be created this way. `security.password` may mention the message a user could see; nothing may describe setting SSO up.

- **Suspending an account.** Accounts have an active flag that is honoured everywhere, including by sign in links. Nothing sets it to false. `account.delete` explains the state; no page may describe how to suspend somebody.

- **Inviting people, and adding employees.** The registration screen tells people joining an existing company to ask an administrator to invite them, but no invitation exists: no route, no mail, no token, no screen. Creating an employee works as business logic and is exercised by tests, but the only employee ever created is the founder's own, during signup. **No page may describe inviting a colleague or adding an employee.** When it ships it is the most important tutorial in the portal.

- **Editing your company, your account, or somebody else's employee record.** The business logic for all three exists and is tested. Only your own employee record has a screen, covered by Section 5; nothing reaches the other three. Several pages above are shaped around this absence and say so; when the screens land, those caveats come out and the pages become how-tos.

- **Reading somebody else's activity, or the emails sent to them.** The log and the record of sent emails both belong to the company and both name the person they concern, but every screen filters to the signed in person's own entries, and no screen shows a company wide view. **No page may describe reading a colleague's activity or their emails, or an owner overseeing the company's.**

- **Filtering, searching or exporting the activity log or the emails sent.** Both screens read newest first, a page at a time, and offer nothing else. No page may promise a date range, a filter by action, or an export.

- **Ending your other sessions.** Sessions are stored per device, but nothing lists or revokes them. Mentioned as a limitation in `signin.sign-out` only.

- **Delivery and bounce reporting.** The record of sent emails has columns for delivery and bounce times, and the identifier a provider returns is captured when Resend is enabled, but no webhook endpoint exists to fill them in. `hosting.email` must say the columns stay empty, and `profile.emails` must say what that looks like on the screen: every email keeps the amber "on its way" dot, and neither the green nor the red one appears on an instance nothing reports back to.

- **Any API, though the keys for one exist.** Somebody can now mint and revoke personal API keys on the Security and access screen, and `security.api-keys` documents that screen. Nothing else is built: there is no API route file, no API controller, no resource, and no guard that accepts a key, so a key authenticates nothing and its "last used" column can never fill in. **The portal must not contain an API section, and no page may show a request carrying a key.** `security.api-keys` is the only page that may mention keys at all, and it must say plainly that there is nothing to point one at yet.

- **The application itself, past the profile screen.** There is no dashboard, no employee directory, no home screen, and the only navigation is the settings sidebar. `/` renders a landing page rather than anything signed in. `getting.what-works-today` is the page that tells readers this, and it is the page to revisit first as the product grows. Note for `appearance.mobile`: the signed in screens respond to the width of the window, but `/` is still the untouched framework landing page and is not part of the product's design, so no page may present it as a screen of OfficeLife on any size of device.

---

## Writing order

Build the portal in this order. Each block is independently publishable, so the portal is useful before it is complete.

1. **First pass, the essentials.** `portal.introduction`, `getting.introduction`, `getting.what-is-officelife`, `getting.create-account`, `getting.confirm-email`, `signin.introduction`, `signin.password`, `signin.magic-link`, `signin.forgot-password`. This alone answers most of what a real user will ask today.
2. **Second pass, the model.** All of Section 3, plus `getting.what-works-today`. This is where the product becomes comprehensible rather than merely usable.
3. **Third pass, the screens there are.** All of Section 5, ending with `profile.logs` and `profile.emails`, which need `concepts.activity-log` and `email.record` written first. Then `preferences.screen`, pulled forward out of Section 7 because it documents a screen rather than a behaviour, and because `profile.introduction` sends readers to it. It is short, it is the only part of the portal a reader can follow with the product open in front of them, and it makes Section 3 concrete.
4. **Fourth pass, security and email.** Sections 6 and 8. These are the pages people arrive at from an inbox, under stress, so they benefit from being written together and in one voice.
5. **Fifth pass, operators.** Section 9. A different audience and a different register; writing it in one block keeps it consistent.
6. **Sixth pass, the connective tissue.** The rest of Sections 7, 10 and 11. Language, troubleshooting and reference are best written last, because they mostly link to pages that must already exist. `appearance.mobile` is the one page left in this block worth pulling forward if the portal ships before the rest of Section 7, since a reader on a phone hits the question it answers on their first screen.
7. **Then translate.** `fr_FR`, `de_DE`, `es_ES`. Only the `id` stays identical across locales; titles, slugs and sections are translated with the rest.
