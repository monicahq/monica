<p align="center"><img src="https://app.monicahq.com/img/small-logo.png"></p>
<h1 align="center">Monica</h1>

<p align="center">
<a href="https://travis-ci.org/monicahq/monica"><img src="https://travis-ci.org/monicahq/monica.svg?branch=master" alt="Build Status"></a>
<a href="https://github.com/djaiss/monica/blob/master/LICENSE"><img src="https://img.shields.io/badge/License-AGPL-blue.svg" alt="License"></a>
</p>

* [Introduction](#introduction)
   * [Purpose](#purpose)
   * [Principles](#principles)
   * [Who is it for?](#who-is-it-for)
   * [What Monica isn't](#what-monica-isnt)
* [Get started](#get-started)
   * [Update your instance](#update-your-instance)
* [Contribute as a developer](#contribute-as-a-developer)
   * [How the community can help](#how-the-community-can-help)
* [Vision, goals and strategy](#vision-goals-and-strategy)
   * [Vision](#vision)
   * [Goals](#goals)
   * [Strategy](#strategy)
   * [Monetization](#monetization)
      * [The API](#the-api)
   * [Why Open Source?](#why-open-source)
   * [Patreon](#patreon)
* [Contact](#contact)
* [License](#license)

## Introduction

Monica is an open-source web application to organize the interactions with your
loved ones. I call it a PRM, or Personal Relationship Management of software.
Think of it as a [CRM](https://en.wikipedia.org/wiki/Customer_relationship_management)
(a popular tool used by sales teams in the corporate world) for your friends or
family. This is what it currently looks like:

<p align="center">
<img src="https://app.monicahq.com/img/main-app.png" alt="screenshot of the application">
</p>

### Purpose

Monica allows people to keep track of everything that's important about their
friends and family. Like the activities done with them. When you last called
someone. What you talked about. It will help you remember the name and the age
of the kids. It can also remind you to call someone you haven't talked to in a
while.

### Principles

* It should be open-source.
* It should be transparent.
* It should be simple to use, simple to contribute to, simple to
understand, extremely simple to maintain.
* It is not a social network and shall never be.
* It should do one thing (organizing interactions) extremely well, and nothing
more.
* It should be well documented.
* It should help have better relationships.

### Features

* Add and manage contacts
* Add significant others and children
* Auto reminders for birthdays
* Reminders are sent by email
* Management of debts
* Ability to add notes to a contact
* Ability to indicate how you've met someone
* Management of activities done with a contact
* Management of tasks
* Management of gifts
* Basic journal
* Export and import of data
* Multi users
* Labels to organize contacts
* Multi currencies
* Multi languages
* An API that covers most of the data

### Who is it for?

This project is for people who have hard time remembering details about other
people's lives - especially the ones they care about. Yes, you can still use
Facebook to achieve this, but you will only be able to see what people do and
post - and if they are not on Facebook, you are stuck anyway.

I originally built this tool to help me in my private life: I've been living
away of my own country for a long time now. I want to keep notes and remember
the life of my friends in my home country and be able to ask the relevant
questions when I email them or talk to them over the phone. Moreover, as a
foreigner in my new country, I met a lot of other foreigners - and most come
back to their countries. I still want to remember the names or ages of their
kids. Call it cheating - I call it caring.

We've already received numerous feedback of users who suffer from Asperger's
syndrome who use this application on a daily basis. It helps them have better
social interactions.

### What Monica isn't

Monica is not a social network and never will be. It's not meant to be social.
In fact, it's for your eyes only. Monica is also not a smart assistant - it
won't guess what you want to do. It's pretty dumb: it will send you
emails only for the things you asked to be reminded of.

## Get started

There are multiple ways of getting started with Monica.

1. You can use our hosted-version (this is the simplest way to use the product)
on [https://monicahq.com](https://monicahq.com).
1. You can run it with Docker ([instructions](docs/installation/docker.md)).
1. You can install it on your server
([generic instructions](docs/installation/generic.md)).
1. You can install it from scratch on Debian Stretch
([instructions](docs/installation/debian.md)).
1. You can deploy to Heroku ([instructions](docs/installation/heroku.md)).

### Update your instance

Once the software is installed, you'll need to update it from time to time to
have access to the latest features. [Read this document](docs/installation/update.md)
to learn how to do it.

## Contribute as a developer

[Installation instructions](/docs/contribute/contribute.md) for the developer version.

We welcome contributions of all kinds from anyone. We do however have rules.

* Monica is written with a great framework, Laravel. We care deeply about
keeping Monica very simple on purpose. The simpler the code is, the simpler it
will be to maintain it and debug it when needed. That means we don't want to
make it a one page application, or add any kind of complexities whatsoever.
* That means we won't accept pull requests that add too much complexity, or
written in a way we don't understand. Again, the number 1 priority should be to
simplify the maintenance on the long run.
* When adding a feature, do not introduce a new software in the existing stack.
For instance, at the moment, the current version does not require Redis to be
used. If we do create a feature that (for some reasons) depends on Redis, we
will need all existing instances to install Redis on top of all the other things
people have to setup to install Monica (there are thousands of them). We can't
afford to do that.
* It's better to move forward fast by shipping good features, than waiting for
months and ship a perfect feature.
* Our product philosophy is simple. Things do not have to be perfect. They just
need to be shipped. As long as it works and aligns with the vision, you should
ship as soon as possible. Even if it's ugly, or very small, that does not
matter.

### How the community can help

There are several ways to help this project to move forward:
* Unlike Fight Club, the best way to help is to actually talk about the project
as much as you can.
* You can answer questions in the issue tracker to help other community members.
* If you are a developer:
   * Read our [Contribution Guide](/CONTRIBUTING.md).
   * Look for [issues labelled bugs](https://github.com/monicahq/monica/issues?q=is%3Aopen+is%3Aissue+label%3Abug)
     if you are looking to have an immediate impact on the project.
   * Look for [issues labelled enhancements](https://github.com/monicahq/monica/issues?q=is%3Aopen+is%3Aissue+label%3Aenhancement)
     These are issues that you can solve relatively easily.
   * If you are an advanced developer, you can try to tackle
     [issues labelled feature requests](https://github.com/monicahq/monica/issues?q=is%3Aopen+is%3Aissue+label%3A%22feature+request%22).
     Beware though - they are harder to do and will require a lot of back and
     forth with the repository administrator in order to make sure we are going
     to the right direction with the product.
   * Finally, and most importantly, we are looking for people willing to write
     tests for the existing features.

## Vision, goals and strategy

We want to use technology in a way that does not harm human relationships, like
big social networks can do.

### Vision

Monica's vision is to **help people have more meaningful relationships**.

### Goals

We want to provide a platform that is:

* **really easy to use**: we value simplicity over anything else.
* **open-source**: we believe everyone should be able to contribute to this
tool, and see for themselves that nothing nasty is done behind the scenes that
would go against the best interests of the users. We also want to leverage the
community to build attractive features and do things that would not be possible
otherwise.
* **easy to contribute to**: we want to keep the codebase as simple as possible.
This has two big advantages: anyone can contribute, and it's easily maintainable
on the long run.
* **available everywhere**: Monica should be able to run on any desktop OS
or mobile phone easily. This will be made possible by making sure the tool is
easily installable by anyone who wants to either contribute or host the platform
themselves.
* **robust API**: the platform will have a robust API so it can communicate both
ways to other systems.

### Strategy

To reach this ambitious vision, we'll use technology in a way that does not harm
human relationships, like big social networks can do.

We think Monica has to become a platform more than an application, so people can
build on it.

Here what we should do in order to realize our vision:
* Build an API in order to create an ecosystem. The ecosystem is what will make
Monica a successful platform.
* Build importers and exporters of data. We don't want to have any vendor
lock-ins. Data is the property of the users and they should be able to do
whatever they want with it.
* Be the central point of contact management, by supporting CardDav protocol.
* Be the central point of calendar events, by supporting CalDav protocol.
* Be available on mobile apps, not just a responsive site.
* Build great reports so people can have interesting insights on how they
interact with their loved ones.
* Create a smart recommendation system for gifts. For instance, if my nephew is
soon 6 years old in a month, I will be able to receive an email with a list of
5 potential gifts I can offer to a 6 year old boy.
* Add more ways of being reminded: Telegram, SMS,...
* Create Chrome extensions to load Monica's data in a sidebar when viewing a
contact on Facebook, letting us take additional notes as we see them on Facebook.
* Add modules that can be activated on demand. One would be for instance, for
the people who wants to use Monica for dating purposes (yes, we've received this
kind of feedback already).
* Add functional and unit tests so the main features are tested. Stability is
key.

### Monetization

The big topic. Yes, we plan to make money out of this tool to sustain it on the
long run. We are a big fan of [Sentry](https://sentry.io), Wordpress and GitLab
and we believe this kind of business model is inspiring, where everyone wins.

* On https://monicahq.com, Monica will be offered in two versions. Note that
this can change anytime as we are trying different business models to see if
this project can be sustained in the long run:
  * a free plan:
    * No limits of contacts
    * Exporters
  * a paid plan:
    * Advanced features
    * Email reminders
    * Importers
    * People who contribute to the GitHub repository (with a pull request that
    adds value, that gets merged (not a typo fix, for instance) will also have
    access to the Paid version for free.
* You can also **run it yourself**. This is sometimes also called on-premise. Download the code, run it on Heroku, with
Docker. The choice is yours.
  * The downloadable version will always be the most complete version - the same
  offered on the paid plan on `.com`.
  * This version will be completely free with no strings attached and you will
  be in complete control.
* There is a [Patreon account](https://www.patreon.com/monicahq) for those who
still want to support the tool. Keep in mind that the best way to support it is
to actually talk about it around you.

There is currently not, and will never be, ads on the platform. I will never
resell your data on `.com`. I'm like you: I hate big corporations that do not
have at heart the best thing for their users, even if they say otherwise. The only
way, therefore, to sustain the development of the product is to actually make
money in a good-old fashioned way.

#### The API

The API will be opened to everyone, for both on `.com` and on-premises.

### Why Open Source?

Why is Monica open source? Is it risky? Will someone steal my code and do a
for-profit business that will kill my own business? Why reveal my strategy to
the world? This is the kind of questions we've received by email already.

The answer to these questions is simple: yes, you can fork the project and do a
competing project, make money out of it (even if the license is not super
friendly to achieve that) and I'll never know. But it's ok, I don't mind.

I wanted to open source this project for several reasons:

* I believe, perhaps naively, that this project can really change people's
lives. While I aim to make money out of it, I also want everyone to benefit
from it. Open sourcing a project like this will help Monica become much bigger
than what I imagine myself. While I strongly believe that the project has to
follow the vision I have for it, I need to be humble enough to know that ideas
come from everywhere, and people have much better ideas than what I can have.
* You can't do something great alone. While Monica could become a company and
hire a bunch of super smart people to work on it, you can't beat the manpower of
an entire community. Open sourcing the product means bugs will be fixed faster,
features will be developed faster, and more importantly, developers will be able
to contribute to the project that changes either their own lives, or other
people's lives.
* Doing things in a transparent manner, like it's the case when you open source
something, lead to formidable things. People respect the project more. You can't
hide nasty piece of code. You can't do things behind the back of your users.
It's a major driving force that motivates you to keep doing what's right.
* I believe that once you have created a community of passionate developers
around your project, you've won - because developers are very powerful
influencers. Developers will create apps around your product, talk about it on
forums, and tell about the project to their friends. Cherish the developers -
users will follow.

### Patreon

You can support the development of this tool
[on Patreon](https://www.patreon.com/monicahq). Thanks for your help.

## Contact

If you need to talk, you can contact me at regis AT monicahq DOT com. You can
also reach me [on Twitter](https://twitter.com/djaiss).

## License

Copyright (c) 2016-2017 Regis Freyd

Licensed under the AGPL License.
