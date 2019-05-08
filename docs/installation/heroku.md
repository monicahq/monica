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
Monica doesn't require a lot of power - it will run perfectly fine on the free plan provided by Heroku. 

After deployment, the configuration of your app should look like this:

![Picture Of Configuration](https://user-images.githubusercontent.com/25419741/45253146-9f904800-b362-11e8-916b-8980fc2a83d8.png)

Note that when you deploy with the "Deploy to Heroku" purple button, only 1 dyno ("web") is activated while the "queue" one is not. That is OK - the "queue" dyno is only helpful if you set `QUEUE_DRIVER=database` (default is 'sync').

In addition, make sure to setup a new job that runs every hour using the Heroku Scheduler (it's located on the bottom of the screen shown above). After creating a new job, set it to be: `php artisan schedule:run`.

### Generating Personal Access Tokens

In order to generate personal access tokens from the UI, you need to:

* Install the [Heroku CLI](https://devcenter.heroku.com/categories/command-line) and log in.
* From your command line, run
```sh
heroku run bash -a <APP-ID>
```
* Run
```sh
php artisan passport:install
```
  This command display two keys, each with an integer key ID and a secret:
  ```
Encryption keys generated successfully.
Personal access client created successfully.
Client ID: 1
Client secret: RS4dkVC...OYIUamD
Password grant client created successfully.
Client ID: 2
Client secret: tV6RRPo...ZCoW8Cx
```
* Open the Heroku dashboard and navigate to Settings -> Reveal Config Vars. This is where you can set environment variables.
* Create two new environment variables using the details from the `Password grant client` key you just created, e.g. set `MOBILE_CLIENT_ID` to `2`, and set `MOBILE_CLIENT_SECRET` to `tV6RRPo...`
* Still in the Heroku CLI, run this command to output the private key:
```
sed ':a;N;$!ba;s/\n/\\n/g' ~/storage/oauth-private.key
```
   Copy the output to a new Heroku environment variable called `PASSPORT_PRIVATE_KEY`
* Do the same thing with the contents of the public key:
```
sed ':a;N;$!ba;s/\n/\\n/g' ~/storage/oauth-public.key
```
copying its contents to an environment variable called `PASSPORT_PUBLIC_KEY`

Once Heroku is re-deploy, you should be able to use the 'Create new token' function in https://XXX.herokuapp.com/settings/api

Once you have the token, you can use the API with a command line:
```
curl -H "Authorization: Bearer eyJ0eXIh7ARV1Xjcf4qNo" https://XXX.herokuapp.com/api
```
If everything is well, this call will return:
```json
{"success":{"message":"Welcome to Monica"}}
```

## Limitations

* No upload of photos for your contacts. Heroku doesn't support storage.
* No email by default - email configuration isn't required to use Monica on Heroku, but it's recommended. The easiest way to go about this is to use Mailgun's [free email add-on on Heroku](https://elements.heroku.com/addons/mailgun):
  * [Sign up for Mailgun](https://signup.mailgun.com/new/signup) (the [free plan](https://www.mailgun.com/pricing) should be sufficient)
  * In Heroku, go to your app, then to the Settings tab. In it, you will have a button that reads "Reveal Config Vars". Click it, and change the following vars:
    * `MAIL_DRIVER` = `mailgun`
    * `MAILGUN_DOMAIN`: your Mailgun domain
    * `MAILGUN_SECRET`: your Mailgun API key — find it [here](https://app.mailgun.com/app/account/security)
    * `MAIL_FROM_ADDRESS`: email address to use for 'from' email (could just use your own)
    * `MAIL_FROM_NAME`: name of the 'from' user (could just use "Monica")
  

## Updating Heroku instance

You can update your Monica instance to the latest version by cloning the repository and pushing it to Heroku git.

Clone the Monica repository to your local environment by `git clone https://github.com/monicahq/monica`, and add heroku git repository by `heroku git:remote -a (heroku app name)`. Then, push to heroku by `git push heroku master`. Heroku will build and update the repository, automatically.
