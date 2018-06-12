Monica can be installed on a variety of platforms. The choice of the platform is yours.

- [Requirements](#requirements)
- [Installation instructions for specific platforms](#installation-instructions-for-specific-platforms)
    - [Generic Linux instructions](#generic-linux-instructions)
    - [Platforms](#platforms)

<a id="markdown-requirements" name="requirements"></a>
## Requirements

Monica depends on the following:

* [Git](https://git-scm.com/book/en/v2/Getting-Started-Installing-Git)
* PHP 7.1+
* [Composer](https://getcomposer.org/)
* [MySQL](https://support.rackspace.com/how-to/installing-mysql-server-on-ubuntu/)
* Optional: Redis or Beanstalk

**Git:** Git should come pre-installed with your server. If it doesn't - use the installation instructions in the link.

**PHP 7.1+:** The required version of PHP for Monica is included in the default `apt` package repository at the moment. One way to overcome this is to add a new PPA and pull the required version of PHP (along with all the neccessary modules) from there:

    sudo add-apt-repository ppa:ondrej/php
    sudo apt-get update
    sudo apt-get install php7.1 php7.1-cli php7.1-common php7.1-json php7.1-opcache php7.1-mysql php7.1-mbstring php7.1-mcrypt php7.1-zip php7.1-fpm php7.1-bcmath php7.1-intl php7.1-simplexml php7.1-dom php7.1-curl php7.1-gd

**Composer:** After you're done installing PHP, you'll need the Composer dependency manager. It is not enough to just install Composer, you also need to make sure it is installed globally for Monica's installation to run smoothly:

    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    php -r "if (hash_file('SHA384', 'composer-setup.php') === '544e09ee996cdf60ece3804abc52599c22b1f40f4323403c44d44fdfdd586475ca9813a858088ffbc1f233e9b180f061') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
    php composer-setup.php
    php -r "unlink('composer-setup.php');"
    mv composer.phar /usr/local/bin/composer

**Mysql:** Note that this only installs the package, but does not setup MySQL. This is done later in the instructions:

    sudo apt-get update
    sudo apt-get install mysql-server

The preferred OS distribution is Ubuntu 16.04, simply because all the development is made on it and we know it works. However, any OS that lets you install the above packages should work.

<a id="markdown-installation-instructions-for-specific-platforms" name="installation-instructions-for-specific-platforms"></a>
## Installation instructions for specific platforms

<a id="markdown-generic-linux-instructions" name="generic-linux-instructions"></a>
### Generic Linux instructions
* [Generic Instructions](/docs/installation/generic.md)
* [Ubuntu](/docs/installation/ubuntu.md)
* [Debian](/docs/installation/debian.md)

<a id="markdown-platforms" name="platforms"></a>
### Platforms

* [Docker](/docs/installation/docker.md)
* [Heroku](/docs/installation/heroku.md)
* [Vagrant](/docs/installation/vagrant.md)
* [YunoHost](https://github.com/YunoHost-Apps/monica_ynh)
