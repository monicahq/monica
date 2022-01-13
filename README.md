<p align="center">

![Monica's Logo](https://user-images.githubusercontent.com/61099/37693034-5783b3d6-2c93-11e8-80ea-bd78438dcd51.png)

<p>
<h1 align="center">Personal Relationship Manager</h1>

<div align="center">

[![Build Status](https://img.shields.io/github/workflow/status/monicahq/monica/Build?style=flat-square&label=Build%20Status)](https://github.com/monicahq/monica/actions)
[![Docker pulls](https://img.shields.io/docker/pulls/library/monica)](https://hub.docker.com/_/monica/)
![Lines of code](https://img.shields.io/tokei/lines/github/monicahq/monica)
[![Code coverage](https://img.shields.io/sonar/coverage/monica?server=https%3A%2F%2Fsonarcloud.io&style=flat-square&label=Coverage%20Status)](https://sonarcloud.io/project/activity?custom_metrics=coverage&amp;graph=custom&amp;id=monica)
[![License](https://img.shields.io/github/license/monicahq/monica)](https://github.com/monicahq/monica/blob/main/LICENSE.md)


</div>

Monica is a great open source personal relationship management system.

- [Introduction](#introduction)
  - [Purpose](#purpose)
  - [Features](#features)
  - [Who is it for?](#who-is-it-for)
  - [What Monica isn’t](#what-monica-isnt)
  - [Where does this tool come from?](#where-does-this-tool-come-from)
- [Get started](#get-started)
  - [Requirements](#requirements)
  - [Update your instance](#update-your-instance)
- [Contribute](#contribute)
  - [Contribute as a community](#contribute-as-a-community)
  - [Contribute as a developer](#contribute-as-a-developer)
- [Principles, vision, goals and strategy](#principles-vision-goals-and-strategy)
  - [Principles](#principles)
  - [Vision](#vision)
  - [Goals](#goals)
  - [Strategy](#strategy)
  - [Monetization](#monetization)
  - [Why Open Source?](#why-open-source)
  - [Patreon](#patreon)
- [Contact](#contact)
- [Team](#team)
- [Thank you, open source](#thank-you-open-source)
- [License](#license)

## Introduction

Monica is an open-source web application to organize and record your interactions with your loved ones. We call it a PRM, or Personal Relationship Management. Think of it as a [CRM](https://en.wikipedia.org/wiki/Customer_relationship_management) (a popular tool used by sales teams in the corporate world) for your friends or family. This is what it currently looks like:

<p align="center">

![Screenshot of the application](docs/images/main-app.png)

</p>

### Purpose

Monica allows people to keep track of everything that’s important about their friends and family. Like the activities with them. When you last called someone and what you talked about. It will help you remember the name and the age of their kids. It can also remind you to call someone you haven’t talked to in a while.

### Features

* Add and manage contacts
* Define relationships between contacts
* Reminders
* Automatic reminders for birthdays
* Stay in touch with a contact by sending reminders at a given interval
* Management of debts
* Ability to add notes to a contact
* Ability to record how you met someone
* Management of activities with a contact
* Management of tasks
* Management of gifts given and received and ideas for gifts
* Management of addresses and all the different ways to contact someone
* Management of contact field types
* Management of a contact’s pets
* Basic journal
* Ability to record how your day went
* Upload documents and photos
* Export and import of data
* Export contacts as vCards
* Ability to define custom genders
* Ability to define custom activity types
* Ability to favorite contacts
* Track conversations on social media or SMS
* Multiple users
* Tags to organize contacts
* Ability to define what section should appear on the contact sheet
* Multiple currencies
* Multiple languages
* An API that covers most of the data

### Who is it for?

This project is **for people who have difficulty remembering details about other people’s lives** – especially those they care about. Yes, you can still use Facebook to achieve this, but you will only be able to see what people do and post, and not add your own notes about them.

We’ve also received lots of positive feedback from users who suffer from Asperger syndrome, Alzheimer’s disease, or simply introverts who use this application on a daily basis.

### What Monica isn’t

 * Monica is not a social network and **it never will be**. It’s not meant to be social. It’s designed to be the opposite: it’s for your eyes only.
 * Monica is not a smart assistant. It won’t guess what you want to do. It’s actually pretty dumb: it will only send you emails for the things you asked to be reminded of.
 * Monica is not a tool that will scan your data and do nasty things with it. It’s your data, your server, do whatever you want with it. You’re in control of your data.

### Where does this tool come from?

I originally built this tool to help me in my private life: I’ve been living outside my own country for a long time now. I want to keep notes and remember the life of my friends in my home country and be able to ask the relevant questions when I email them or talk to them over the phone.

Moreover, as a foreigner in my new country, I met a lot of other foreigners – and most go back to their countries. I still want to remember the names or ages of their kids. You may call it cheating but considering my poor memory, I call it caring.

After a few months, I decided to open source Monica so it could help other people as well.

## Get started

There are multiple ways of getting started with Monica:

1. You can use [our Hosted version](https://monicahq.com "Monica website").  This is the simplest way to use Monica.
1. You can install it on your own server by following the [installation instructions here](/docs/installation/readme.md). There are no limitations on Monica if you install it on your own server.

    - The downloadable version will always be the most complete version – the same as offered on the paid plan on the Hosted version.
    - Self-hosted will always be completely free with no strings attached and you will be in complete control.

1. You can deploy straight on a [PaaS platform](https://en.wikipedia.org/wiki/Platform_as_a_service) like:
   
    - Platform.sh [![Deploy on Platform.sh](https://platform.sh/images/deploy/deploy-button-lg-blue.svg)](https://console.platform.sh/projects/create-project/?template=https%3A%2F%2Fgithub.com%2Fmonicahq%2Fmonica&amp;utm_campaign=deploy_on_platform&amp;utm_medium=button&amp;utm_source=affiliate_links&amp;utm_content=https%3A%2F%2Fgithub.com%2Fmonicahq%2Fmonica)

    - [Heroku](https://heroku.com) [![Deploy to Heroku](https://www.herokucdn.com/deploy/button.svg)](https://heroku.com/deploy?template=https://github.com/monicahq/monica/tree/main)


### Requirements

If you want to host Monica yourself, you will need a server with:

- PHP 7.4 or newer
- HTTP server with PHP support (eg: Apache, Nginx, Caddy)
- Composer
- MySQL

To successfully build and host Monica, we recommend a system with at least 1.5&thinsp;GB for RAM.  Monica can run on systems with significantly less memory, but due to the high memory requirements of the build process during updates, you may encounter issues and failed builds.

### Update your instance

Once the software is installed, you’ll need to update it from time to time to have access to the latest features. [Read this document](/docs/installation/update.md) to learn how to do it.

## Contribute

Do you want to help? That’s awesome. We welcome contributions of all kinds from everyone.

Here are some of the things you can do to help.

### Contribute as a community

- Unlike Fight Club, the best way to help is **to actually talk about Monica** as much as you can in blog posts and articles, or on Twitter and  Facebook.

- You can answer questions in [the issue tracker](https://github.com/monicahq/monica/issues) to help other community members.

- You can financially support Monica’s development [on Patreon](https://www.patreon.com/monicahq) or by subscribing to [a paid account](https://monicahq.com/pricing).

### Contribute as a developer

- Read our [Contribution Guide](/CONTRIBUTING.md).

- Install [the developer version locally](/docs/contribute/readme.md) so you can start contributing.

- Look for [issues labelled ‘Bugs’](https://github.com/monicahq/monica/issues?q=is%3Aopen+is%3Aissue+label%3Abug) if you are looking to have an immediate impact on Monica.

- Look for [issues labelled ‘Help Wanted’](https://github.com/monicahq/monica/issues?q=is%3Aissue+is%3Aopen+label%3A%22help+wanted%22). These are issues that you can solve relatively easily.

- Look for [issues labelled ’Good First Issue’](https://github.com/monicahq/monica/labels/good%20first%20issue). These issues are for people who want to contribute, but try to work on a small feature first.

- If you are an advanced developer, you can try to tackle [issues labelled ‘Feature Requests’](https://github.com/monicahq/monica/issues?q=is%3Aopen+is%3Aissue+label%3A%22feature+request%22). These are harder to do and will require a lot of back-and-forth with the repository administrator to make sure we are going to the right direction with the product.


## Principles, vision, goals and strategy

We want to use technology in a way that does not harm human relationships, like big social networks can do.

### Principles

Monica has a few principles.

- It should help have better relationships.

- It should be simple to use, simple to contribute to, simple to understand, extremely simple to maintain.

- It is not a social network and never will be.

- It is not and never will be ad-supported.

- Users are not and never will be tracked.

- It should be transparent.

- It should be open-source.

- It should do one thing (documenting social interactions) extremely well, and nothing more.

- It should be well documented.

### Vision

Monica’s vision is to **help people have more meaningful relationships**.

### Goals

We want to provide a platform that is:

- **really easy to use**: we value simplicity over anything else.

- **open-source**: we believe everyone should be able to contribute to this tool, and see for themselves that nothing nasty is done behind the scenes that would go against the best interests of the users. We also want to leverage the community to build attractive features and do things that would not be possible otherwise.

- **easy to contribute to**: we want to keep the codebase as simple as possible. This has two big advantages: anyone can contribute, and it’s easily maintainable on the long run.

- **available everywhere**: Monica should be able to run on any desktop OS or mobile phone easily. This will be made possible by making sure the tool is easily installable by anyone who wants to either contribute or host the platform themselves.

### Strategy

We think Monica has to become a platform more than an application, so people can build on it.

Here what we should do in order to realize our vision:

- (**done**) Build an API in order to create an ecosystem. The ecosystem is what will make Monica a successful platform.

- (**done**) Build importers and exporters of data. We don’t want to have any vendor lock-ins. Data is the property of the users and they should be able to do whatever they want with it.

- (**done**) Be the central point of contact management, by supporting CardDav protocol.

- (**done**) Be the central point of calendar events, by supporting CalDav protocol.

- (**partially done**) Build great reports so people can have interesting insights on how they interact with their loved ones.

- Create a smart recommendation system for gifts. For instance, if my nephew is soon 6 years old in a month, I will be able to receive an email with a list of 5 potential gifts I can offer to a 6 year old boy.

- Add more ways of being reminded: Telegram, SMS,...

- Create Chrome extensions to load Monica’s data in a sidebar when viewing a contact on Facebook, letting us take additional notes as we see them on Facebook.

- Add modules that can be activated on demand. One would be for instance, for the people who wants to use Monica for dating purposes (yes, we’ve received this kind of feedback already).

### Monetization

While it’s not the driving force behind Monica, it would be great if the tool could generate money so we could work full time on it and sustain it on the long run. We are big fans of [Sentry](https://sentry.io), Wordpress and GitLab and we believe this kind of business model is an inspiring one where everyone wins.

If you want to support the development of Monica, consider taking [a paid account](https://www.monicahq.com/pricing), or support us [on Patreon](https://www.patreon.com/monicahq).

- The [Hosted version of Monica](https://monicahq.com) is offered in two versions:

    * a [free plan](https://app.monicahq.com/register) which includes:
        + 10 contacts
        + data exporters

    * a [paid plan](https://www.monicahq.com/pricing) which includes:
        + unlimited contacts
        + email reminders
        + data importers
        + advanced features

    * We’re still working on the features included in the paid plan, and these may be subject to change while we work out our business model to make Monica’s development sustainable.

    * People who substantially contribute to the GitHub repository (with a pull request that adds value, that gets merged – not a typo fix, for instance) will also have access to the paid version for free.

- There is a [Patreon account](https://www.patreon.com/monicahq) for those who want to financially support Monica’s development in another way. The best way to support Monica it is to actually talk about it and help grow its userbase.


There are no ads on the platform and there never will be. We will never resell your data on [the Hosted version](https://monicahq.com/) and we have no access to it if you self-host.

We are like you, and this is why we are on GitHub: we hate big corporations that do not have at heart the best interests of their users, even if they say otherwise. We believe that the only way to sustain the development of Monica is to actually make money in a good old-fashioned way.

### Why Open Source?

Why is Monica open source? Is it risky? Will someone steal my code and do a for-profit business that will kill my own business? Why reveal my strategy to the world? These are the kind of questions we’ve received by email already.

The answer to these questions is simple: yes, you can fork Monica and make a competing project, make money out of it (even if the license is not super friendly towards that) and I’ll never know. But it’s okay, I don’t mind.

I wanted to open source Monica for several reasons:

- **I believe that this tool can really change people’s lives.**  
    While I aim to make money out of it, I also want everyone to benefit from it. Open sourcing a project like this will help Monica become much bigger than what I imagine myself. While I strongly believe that this software has to follow the vision I have for it, I need to be humble enough to know that ideas come from everywhere, and people have much better ideas than what I can have.

- **You can’t make something great alone.**  
    While Monica could become a company and hire a bunch of super smart people to work on it, you can’t beat the manpower of an entire community. Open sourcing the product means bugs will be fixed faster, features will be developed faster, and more importantly, developers will be able to contribute to a tool that positively changes their own lives and the lives of other people.

- **Doing things in a transparent way leads to formidable things.**  
    People respect the project more when they can see how it’s being worked on. You can’t hide nasty things in the code. You can’t do things behind the backs of your users. Doing everything in the open is a major driving force that motivates you to keep doing what’s right.

- **Once you’ve created a community of passionate developers around your project, you’ve won.**  
    Because developers are very powerful influencers. Developers will create apps around your product, talk about it on forums, and share the project with their friends, families, and colleagues. Cherish the developers – users will follow.

### Patreon

You can support the development of Monica [on Patreon](https://www.patreon.com/monicahq). Thanks for your help.

## Contact

## Team

Our team is made of two core members:

- [Regis Freyd (djaiss)](https://github.com/djaiss)

- [Alexis Saettler (asbiin)](https://github.com/asbiin)

We are also fortunate to have an amazing [community of developers](https://github.com/monicahq/monica/graphs/contributors) who help us greatly.

## Thank you, open source

Monica uses a lot of open source projects and we thank them with all our hearts. We hope that providing Monica as an free, open source project will help other people the same way those softwares have helped us.

## License

Copyright © 2016–2022

Licensed under [the AGPL License](/LICENSE.md).
