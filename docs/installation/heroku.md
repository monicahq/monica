# Installing Monica on Heroku

Monica can be deployed on Heroku using the button below:

[![Deploy](https://www.herokucdn.com/deploy/button.svg)](https://heroku.com/deploy?template=https://github.com/monicahq/monica/tree/master)

Please ensure to enter a custom `APP_KEY` when asked.
For example, if you have the `pwgen` utility installed, you could copy and paste the output of `pwgen -s 32 1`.

Your Monica instance will use a [ClearDB Ignite plan](https://elements.heroku.com/addons/cleardb) (free) by default. Additional environment variables, such as details of the mail server, can be added after setup through the Heroku interface.
Monica doesn't require a lot of power - it means it will run on the free plan provided by Heroku.

When you visit the app for the first time, you will be prompted to register a new account. Put in your email address and password and you will be logged into the app. This is the only account that has access unless you invite other people.

Feel free to update these credentials in the settings after installation.

### Generating Personal Access Tokens

You cannot generate personal access tokens from the UI. Instead:

* Install the [Heroku CLI](https://devcenter.heroku.com/categories/command-line) and log in.
* From your command line, run `heroku run bash -a <APP-ID>`.
* Run `php artisan passport:install`.
* Store the tokens that are generated.

Read the general [setup instructions](https://github.com/monicahq/monica/wiki/Installing-Monica-(Generic)#3-configure-monica) for more ways to customize your app and enable background alerts.

## Limitations

* No email by default. Email configuration isn't required to use Monica on Heroku, but it's recommended. Mailgun has a [free email add-on on Heroku](https://elements.heroku.com/addons/mailgun) that is easy to set up.
* No upload of photos for your contacts. Heroku doesn't support storage.
* No crons on the free version. That means no reminders by email, nor automatic checking of new versions of Monica.

## Updating Heroku instance

You can update your Monica instance to the latest version by cloning the repository and pushing it to Heroku git.

Clone the Monica repository to your local environment by `git clone https://github.com/monicahq/monica`, and add heroku git repository by `heroku git:remote -a (heroku app name)`. Then, push to heroku by `git push heroku master`. Heroku will build and update the repository, automatically.
