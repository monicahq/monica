# Installing Monica on Heroku

Monica can be deployed on Heroku using the button below:

[![Deploy](https://www.herokucdn.com/deploy/button.svg)](https://heroku.com/deploy?template=https://github.com/monicahq/monica/tree/master)

Before deployment, Heroku will ask you to define a few variables.
Please ensure to enter a custom `APP_KEY` when asked (if, for example, you have the `pwgen` utility installed, you could copy and paste the output of `pwgen -s 32 1`).
In addition, you can edit the E-Mail address Monica will send e-mails to, the name of the sender and some other important variables on that screen.

After deployment, and when you visit the app for the first time, you will be prompted to register a new account. Put in your email address and password and you will be logged into the app. This is the only account that has access unless you invite other people.

Feel free to update these credentials in the settings after installation.

### Configuration 

Your Monica instance will use a [ClearDB Ignite plan](https://elements.heroku.com/addons/cleardb) (free) by default. Additional environment variables, such as details of the mail server, can be added after setup through the Heroku interface.
Monica doesn't require a lot of power - it means it will run on the free plan provided by Heroku. 

Note the configuration of your app should look like this:

![Picture Of Configuration](https://user-images.githubusercontent.com/25419741/45253146-9f904800-b362-11e8-916b-8980fc2a83d8.png)

Note that when you deploy with the heroku button, only 1 dyno is activated for the "web" one - that is OK.
The "queue" dyno is only helpful if you set `QUEUE_DRIVER=database` (default is 'sync').

In addition, Make sure to setup a new job that runs every hour using the Heroku Scheudler, on the bottom of the screen shown above. After creating a new job, set it to be: `php artisan schedule:run`.

### Generating Personal Access Tokens

You cannot generate personal access tokens from the UI. Instead:

* Install the [Heroku CLI](https://devcenter.heroku.com/categories/command-line) and log in.
* From your command line, run `heroku run bash -a <APP-ID>`.
* Run `php artisan passport:install`.
* Store the tokens that are generated.

Read the general [setup instructions](https://github.com/monicahq/monica/blob/master/docs/installation/generic.md#3-configure-monica) for more ways to customize your app and enable background alerts.

## Limitations

* No email by default. Email configuration isn't required to use Monica on Heroku, but it's recommended. Mailgun has a [free email add-on on Heroku](https://elements.heroku.com/addons/mailgun) that is easy to set up.
* No upload of photos for your contacts. Heroku doesn't support storage.

## Updating Heroku instance

You can update your Monica instance to the latest version by cloning the repository and pushing it to Heroku git.

Clone the Monica repository to your local environment by `git clone https://github.com/monicahq/monica`, and add heroku git repository by `heroku git:remote -a (heroku app name)`. Then, push to heroku by `git push heroku master`. Heroku will build and update the repository, automatically.
