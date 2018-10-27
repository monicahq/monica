<p align="center"><img src="https://user-images.githubusercontent.com/61099/37693034-5783b3d6-2c93-11e8-80ea-bd78438dcd51.png"></p>
<h1 align="center">Personal Relationship Manager</h1>

<p align="center">
<a href="https://circleci.com/gh/monicahq/monica"><img src="https://img.shields.io/circleci/project/github/monicahq/monica/master.svg" alt="Build Status"></a>
<a href="https://styleci.io/repos/82238168"><img src="https://styleci.io/repos/82238168/shield?branch=master" alt="StyleCI"></a>
<a href="https://greenkeeper.io/"><img src="https://badges.greenkeeper.io/monicahq/monica.svg" /></a>
<a href="https://sonarcloud.io/project/activity?custom_metrics=coverage&amp;graph=custom&amp;id=monica"><img src="https://sonarcloud.io/api/project_badges/measure?project=monica&amp;metric=coverage" alt="Code coverage"/></a>
<a href="https://sonarcloud.io/dashboard?id=monica"><img src="https://sonarcloud.io/api/project_badges/measure?project=monica&amp;metric=alert_status" alt="Quality gate" /></a>
<a href="https://github.com/monicahq/monica/blob/master/LICENSE"><img src="https://img.shields.io/badge/License-AGPL-blue.svg" alt="License"></a>
<a class="badge-align" href="https://slack.monicahq.com"><img src="https://slack.monicahq.com/badge.svg"></a>
</p>

Monica is a great open source personal relationship management system.

* [Introduction](#introduction)
  * [Purpose](#purpose)
  * [Features](#features)
  * [Who is it for?](#who-is-it-for)
  * [What Monica isn't](#what-monica-isnt)
  * [Where does this tool come from?](#where-does-this-tool-come-from)
* [Get started](#get-started)
  * [Requirements](#requirements)
  * [Update your instance](#update-your-instance)
* [Contribute](#contribute)
  * [As a community](#as-a-community)
* [Contribute as a developer](#contribute-as-a-developer)
* [Principles, vision, goals and strategy](#principles-vision-goals-and-strategy)
  * [Principles](#principles)
  * [Vision](#vision)
  * [Goals](#goals)
  * [Strategy](#strategy)
  * [Monetization](#monetization)
  * [Why Open Source?](#why-open-source)
  * [Patreon](#patreon)
* [Contact](#contact)
* [Thank you, open source](#thank-you-open-source)
* [License](#license)

## Introduction

Monica is an open-source web application to organize the interactions with your loved ones. We call it a PRM, or Personal Relationship Management. Think of it as a [CRM](https://en.wikipedia.org/wiki/Customer_relationship_management) (a popular tool used by sales teams in the corporate world) for your friends or family. This is what it currently looks like:

<p align="center">
<img src="docs/images/main-app.png" alt="screenshot of the application">
</p>

We also have official [open source mobile apps](https://github.com/monicahq/chandler) but they are extremely basic at this point and not well maintained.

### Purpose

Monica allows people to keep track of everything that's important about their friends and family. Like the activities done with them. When you last called someone. What you talked about. It will help you remember the name and the age of the kids. It can also remind you to call someone you haven't talked to in a while.

### Features

* Add and manage contacts
* Define relationships between contacts
* Reminders
* Auto reminders for birthdays
* Stay in touch with a contact by sending reminders at a given interval
* Management of debts
* Ability to add notes to a contact
* Ability to indicate how you've met someone
* Management of activities done with a contact
* Management of tasks
* Management of gifts
* Management of addresses and all the different ways to contact someone
* Management of contact field types
* Management of contact pets
* Basic journal
* Ability to indicate how the day went
* Export and import of data
* Export a contact as vCard
* Ability to set custom genders
* Ability to define custom activity types
* Ability to favorite contacts
* Track conversations made on social media or SMS
* Multi users
* Labels to organize contacts
* Ability to define what section should appear on the contact sheet
* Multi currencies
* Multi languages
* An API that covers most of the data
* We also have [official mobile apps](https://github.com/monicahq/chandler), also open source

### Who is it for?

This project is **for people who have hard time remembering details about other people's lives** - especially the ones they care about. Yes, you can still use Facebook to achieve this, but you will only be able to see what people do and post, and not add your own notes about them.

We've also received numerous feedback of users who suffer from Asperger's syndrome, alzheimer disease, or simply introverts who use this application on a daily basis.

### What Monica isn't

 * Monica is not a social network and **never will be**. It's not meant to be social. In fact, it's for your eyes only.
 * Monica is not a smart assistant - it won't guess what you want to do. It's actually pretty dumb: it will send you emails only for the things you asked to be reminded of.
 * Monica is not a tool that will scan your data and do nasty things with it. It's your data, your server, do whatever you want with it.

### Where does this tool come from?

I originally built this tool to help me in my private life: I've been living away of my own country for a long time now. I want to keep notes and remember the life of my friends in my home country and be able to ask the relevant questions when I email them or talk to them over the phone. Moreover, as a foreigner in my new country, I met a lot of other foreigners - and most come back to their countries. I still want to remember the names or ages of their kids. Call it cheating but considering my poor memory, I call it caring.

After a few months, I decided to open source the project so it would help other people as well.

## Get started

There are multiple ways of getting started with Monica.

1. You can use our hosted-version (this is the simplest way to use the product) on [https://monicahq.com](https://monicahq.com).
1. You can install it on your server: follow installation instructions ([here](/docs/installation/index.md)).

Note: while the .com version has a paid plan, there is no limitations on Monica if you install it on a server that you own.

### Requirements

If you want to host it yourself, you need

* PHP 7.1+ or newer
* HTTP server with PHP support (eg: Apache, Nginx, Caddy)
* Composer
* MySQL

### Update your instance

Once the software is installed, you'll need to update it from time to time to have access to the latest features. [Read this document](/docs/installation/update.md) to learn how to do it.

## Contribute

Do you want to help? That's awesome. Here are simple things you can do.

### As a community

* Unlike Fight Club, the best way to help is **to actually talk about the project** as much as you can (blog post, articles, Twitter, Facebook).
* You can answer questions in [the issue tracker](https://github.com/monicahq/monica/issues) to help other community members.
* You can support financially the project [on Patreon](https://www.patreon.com/monicahq) or [by subscribing to an account](https://monicahq.com/pricing).

## Contribute as a developer

* Read our [Contribution Guide](/CONTRIBUTING.md).
* Install the developer version locally so you can start contributing [instructions](/docs/contribute/index.md).
* Look for [issues labelled bugs](https://github.com/monicahq/monica/issues?q=is%3Aopen+is%3Aissue+label%3Abug) if you are looking to have an immediate impact on the project.
* Look for [issues labelled Help wanted](https://github.com/monicahq/monica/issues?q=is%3Aissue+is%3Aopen+label%3A%22help+wanted%22) These are issues that you can solve relatively easily.
* Look for [issues labelled Good first issue](https://github.com/monicahq/monica/labels/good%20first%20issue) These issues are for people who want to contribute, but try to work on a small feature first.
* If you are an advanced developer, you can try to tackle [issues labelled feature requests](https://github.com/monicahq/monica/issues?q=is%3Aopen+is%3Aissue+label%3A%22feature+request%22). Beware though - they are harder to do and will require a lot of back and forth with the repository administrator in order to make sure we are going to the right direction with the product.

We welcome contributions of all kinds from anyone.

## Principles, vision, goals and strategy

We want to use technology in a way that does not harm human relationships, like big social networks can do.

### Principles

Monica has a few principles.

* It should help have better relationships.
* It should be simple to use, simple to contribute to, simple to understand, extremely simple to maintain.
* It is not a social network and shall never be.
* It is not and will never be ad-supported.
* Users are not and will never be tracked.
* It should be transparent.
* It should be open-source.
* It should do one thing (documenting social interactions) extremely well, and nothing more.
* It should be well documented.

### Vision

Monica's vision is to **help people have more meaningful relationships**.

### Goals

We want to provide a platform that is:

* **really easy to use**: we value simplicity over anything else.
* **open-source**: we believe everyone should be able to contribute to this tool, and see for themselves that nothing nasty is done behind the scenes that would go against the best interests of the users. We also want to leverage the community to build attractive features and do things that would not be possible otherwise.
* **easy to contribute to**: we want to keep the codebase as simple as possible. This has two big advantages: anyone can contribute, and it's easily maintainable on the long run.
* **available everywhere**: Monica should be able to run on any desktop OS or mobile phone easily. This will be made possible by making sure the tool is easily installable by anyone who wants to either contribute or host the platform themselves.

### Strategy

We think Monica has to become a platform more than an application, so people can build on it.

Here what we should do in order to realize our vision:
* (**done**) Build an API in order to create an ecosystem. The ecosystem is what will make Monica a successful platform.
* (**done**) Build importers and exporters of data. We don't want to have any vendor lock-ins. Data is the property of the users and they should be able to do whatever they want with it.
* Be the central point of contact management, by supporting CardDav protocol.
* Be the central point of calendar events, by supporting CalDav protocol.
* (**done**) Be available on [mobile apps](https://github.com/monicahq/chandler), not just a responsive site.
* (**partially done**) Build great reports so people can have interesting insights on how they interact with their loved ones.
* Create a smart recommendation system for gifts. For instance, if my nephew is soon 6 years old in a month, I will be able to receive an email with a list of 5 potential gifts I can offer to a 6 year old boy.
* Add more ways of being reminded: Telegram, SMS,...
* Create Chrome extensions to load Monica's data in a sidebar when viewing a contact on Facebook, letting us take additional notes as we see them on Facebook.
* Add modules that can be activated on demand. One would be for instance, for the people who wants to use Monica for dating purposes (yes, we've received this kind of feedback already).

### Monetization

While it's not the driving force behind this project, it would be great if the tool could generate money, so we could work full time on it and sustain it on the long run. We are a big fan of [Sentry](https://sentry.io), Wordpress and GitLab and we believe this kind of business model is inspiring, where everyone wins.

If you want to have it for free with all the features, run the project yourself on a server you own. However, if you want to support the development of the project, consider taking a Pro account, or support the project on Patreon.

* On https://monicahq.com, Monica will be offered in two versions. Note that this can change anytime as we are trying different business models to see if this project can be sustained in the long run:
  * a free plan:
    * 10 contacts
    * Exporters
  * a paid plan:
    * unlimited contacts
    * Advanced features
    * Email reminders
    * Importers
    * People who contribute to the GitHub repository (with a pull request that adds value, that gets merged—not a typo fix, for instance) will also have access to the Paid version for free.
* There is a [Patreon account](https://www.patreon.com/monicahq) for those who still want to support the tool. Keep in mind that the best way to support it is to actually talk about it around you.

Note: you can also **run it yourself**. This is sometimes also called on-premise. Download the code, run it on Heroku, with Docker. The choice is yours.
  * The downloadable version will always be the most complete version - the same offered on the paid plan on `.com`.
  * This version will be completely free with no strings attached and you will be in complete control.

There is currently not, and will never be, ads on the platform. We will never resell your data on `.com`. We are like you, and this is why we are on GitHub: we hate big corporations that do not have at heart the best interest at heart for their users, even if they say otherwise. The only way, therefore, to sustain the development of the product is to actually make money in a good-old fashioned way.

### Why Open Source?

Why is Monica open source? Is it risky? Will someone steal my code and do a for-profit business that will kill my own business? Why reveal my strategy to the world? This is the kind of questions we've received by email already.

The answer to these questions is simple: yes, you can fork the project and do a competing project, make money out of it (even if the license is not super friendly to achieve that) and I'll never know. But it's ok, I don't mind.

I wanted to open source this project for several reasons:

* I believe, perhaps naively, that this project can really change people's lives. While I aim to make money out of it, I also want everyone to benefit from it. Open sourcing a project like this will help Monica become much bigger than what I imagine myself. While I strongly believe that the project has to follow the vision I have for it, I need to be humble enough to know that ideas come from everywhere, and people have much better ideas than what I can have.
* You can't do something great alone. While Monica could become a company and hire a bunch of super smart people to work on it, you can't beat the manpower of an entire community. Open sourcing the product means bugs will be fixed faster, features will be developed faster, and more importantly, developers will be able to contribute to the project that changes either their own lives, or other people's lives.
* Doing things in a transparent manner, like it's the case when you open source something, lead to formidable things. People respect the project more. You can't hide nasty piece of code. You can't do things behind the back of your users. It's a major driving force that motivates you to keep doing what's right.
* I believe that once you have created a community of passionate developers around your project, you've won - because developers are very powerful influencers. Developers will create apps around your product, talk about it on forums, and tell about the project to their friends. Cherish the developers - users will follow.

### Patreon

You can support the development of this tool [on Patreon](https://www.patreon.com/monicahq). Thanks for your help.

## Contact

## Team

Our team is made of 3 core members:
* [Regis Freyd (djaiss)](https://github.com/djaiss)
* [Théo Matthieu (mokto)](https://github.com/mokto)
* [Alexis Saettler (asbiin)](https://github.com/asbiin)

We are also fortunate to have an amazing community of external developers who help us greatly.

## Thank you, open source

Monica use a lot of open source projects and we thank them with all our hearts. We hope that providing Monica as an free, open source project will help other people the same way those softwares have helped us.

## License

Copyright (c) 2016-2018

Licensed under the AGPL License. [View license](/LICENSE).
