# Update your server

Monica uses the concept of releases and tries to follow
[Semantic Versioning](http://semver.org/) as much as possible. If you run the project locally,
or if you have installed Monica on your own server, you need to follow these
steps below to update it, **every single time**, or you will run into problems.

1. Always make a backup of your data before upgrading.
1. Check that your backup is valid.
1. Read the [release notes](https://github.com/monicahq/monica/blob/master/CHANGELOG) to check for breaking changes.
1. Update sources:
    1. Consider check out a tagged version of Monica since `master` branch may not always be stable.
       Find the latest official version on the [release page](https://github.com/monicahq/monica/releases)
       ```sh
       # Get latest tags from GitHub
       git fetch
       # Clone the desired version
       git checkout tags/v1.6.2
       ```
    1. Or check out `master`
       ```sh
       git pull origin master
       ```
1. Then, run the following command at the root of the project:
   ```sh
   composer install --no-interaction --no-suggest --no-dev
   php artisan monica:update --force
   ```

The `monica:update` command will run migrations scripts for database, and flush all cache for config, route, and view, as an optimization process.
As the configuration of the application is cached, any update on the `.env` file will not be detected after that. You may have to run `php artisan config:cache` manually after every update of `.env` file.


Your instance should be updated.

## Updating Heroku instance

You can update your Monica instance to the latest version by cloning the repository and pushing it to Heroku git.

Clone the Monica repository to your local environment by git clone https://github.com/monicahq/monica, and add heroku git repository by heroku git:remote -a (heroku app name). Then, push to heroku by git push heroku master. Heroku will build and update the repository, automatically.

## Importing vCards (CLI only)

**Note**: this is only possible if you install Monica on your server or locally.

You can import your contacts in vCard format in your account with one simple
CLI command:
`php artisan import:vcard {email user} {filename}.vcf`

where `{email user}` is the email of the user in your Monica instance who will
be associated the new contacts to, and `{filename}` being the name of your .vcf file.
The .vcf file has to be in the root of your Monica installation (in the same directory
where the artisan file is).

Example: `php artisan import:vcard john@doe.com contacts.vcf`

The `.vcf` can contain as many contacts as you want.

## Importing SQL from the exporter feature

Monica allows you to export your data in SQL, under the Settings panel. When you
export your data in SQL, you'll get a file called `monica.sql`.

To import it into your own instance, you need to make sure that the database of
your instance is completely empty (no tables, no data).

Then, follow the steps:

* `php artisan migrate`
* Then import `monica.sql` into your database. Tools like phpmyadmin or Sequel
Pro might help you with that.
* Finally, sign in with the same credentials as the ones used on
https://monicahq.com and you are good to go.

There is one caveat with the SQL exporter: you can't get the photos you've uploaded for now.
