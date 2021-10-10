# Installing Monica (Generic) <!-- omit in toc -->

Monica can be installed on a variety of platforms. The choice of the platform is yours.

- [Requirements](#requirements)
- [Installation instructions for specific platforms](#installation-instructions-for-specific-platforms)
  - [Generic Linux instructions](#generic-linux-instructions)
  - [Platforms](#platforms)
  - [Other documentation](#other-documentation)

<a id="markdown-requirements" name="requirements"></a>
## Requirements

If you don't want to use [Docker](/docs/installation/providers/docker.md), the best way to setup the project is to use the same configuration that [Homestead](https://laravel.com/docs/homestead) uses. Basically, Monica depends on the following:

* [Git](https://git-scm.com/book/en/v2/Getting-Started-Installing-Git)
* PHP 7.4+
* [Composer](https://getcomposer.org/)
* [MySQL](https://www.mysql.com/)
* Optional: Redis or Beanstalk

<a id="markdown-installation-instructions-for-specific-platforms" name="installation-instructions-for-specific-platforms"></a>
## Installation instructions for specific platforms

The preferred OS distribution is Ubuntu 18.04, simply because all the development is made on it and we know it works. However, any OS that lets you install the above packages should work.

<a id="markdown-generic-linux-instructions" name="generic-linux-instructions"></a>
### Generic Linux instructions
* [Generic Instructions](/docs/installation/providers/generic.md)
* [Ubuntu](/docs/installation/providers/ubuntu.md)
* [Debian](/docs/installation/providers/debian.md)

<a id="markdown-platforms" name="platforms"></a>
### Platforms

* [Docker](/docs/installation/providers/docker.md)
* [Heroku](/docs/installation/providers/heroku.md)
* [Vagrant](/docs/installation/providers/vagrant.md)
* [YunoHost](https://github.com/YunoHost-Apps/monica_ynh)
* [Cloudron](/docs/installation/providers/cloudron.md)
* [cPanel-based Shared Hosting](/docs/installation/providers/cpanel.md)

### Other documentation

* [Mail settings](/docs/installation/mail.md): allowing your instance to send mails. Useful for reminders.
* [Storage](/docs/installation/storage.md): define an external storage for your instance.
* [Ssl](/docs/installation/ssl.md): how to set ssl for your production-level instance.
* [FAQ](/docs/installation/faq.md): a list of common problems and solutions.
