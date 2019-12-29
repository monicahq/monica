# Configuring a Mail Server

The Monica registration flow will send a validation email to the user who sent it. Whilst this is not required by default (see `APP_SIGNUP_DOUBLE_OPTIN` in your `.env` file), setting up a mail server is encouraged so that you can receive reminders.

For this, you will require an SMTP server. If you don't have one of these, your options include (but are not limited to):

* [Mailtrap](https://mailtrap.io/)
* [Postmark](https://postmarkapp.com/)
* [Mailgun](https://signup.mailgun.com/new/signup) (the [free plan](https://www.mailgun.com/pricing) should be sufficient)
* [Amazon Simple Email Service](https://aws.amazon.com/ses/)
* [Sendgrid](https://sendgrid.com)

## Use SMTP with Monica

The generic way to send emails with Monica is to provide a SMTP server, each one of the services mentionned above can provide you SMTP settings. While Amazon SES is a little bit custom, see bellow, here the configuration for a standard SMTP configuration.

You need to add few environment variables in your configuration (working in generic installation and Docker):
```
MAIL_DRIVER: smtp
MAIL_HOST: smtp.service.com # ex: smtp.sendgrid.net
MAIL_PORT: 587 # is using tls, as you should
MAIL_USERNAME: my_service_username # ex: apikey
MAIL_PASSWORD: my_service_password # ex: SG.Psuoc6NZTrGHAF9fdsgsdgsbvjQ.JuxNWVYmJ8LE0
MAIL_ENCRYPTION: tls
MAIL_FROM_ADDRESS: no-reply@xxx.com # ex: email you want the email to be FROM
MAIL_FROM_NAME: Monica # ex: name of the sender
```

Restart Monica to take in effect the new settings, quickest option to confirm is to add someone to your account (add one of your own email) and you will receive the invitation!


## Use Amazon SES with Monica

Simple Email Service is a service provided through Amazon Web Services. This guide will assume that you have an [AWS Account](https://aws.amazon.com/) and have basic familiarity with the Management Console.

For more detailed information on SES, see the [Amazon SES Docs](https://docs.aws.amazon.com/ses/latest/DeveloperGuide/choose-email-sending-method.html).

### 1. Obtain SES Credentials

The SES SMTP server will require Monica to authenticate with it. These are set through the `MAIL_USERNAME` and `MAIL_PASSWORD` fields in your `.env` file.

Go to the SES Console - and take note of which Region you're working in. You'll need this to configure the correct SMTP server later.

In the SES Console, go to "SMTP Settings", and select "Create My SMTP Credentials". This will take you into IAM (Identity and Access Management), which is where these credentials will be stored. The name is unimportant - just hit "Create".



### 2. Verify the Address You'll be Sending From

When using SES, you must verify that you own the email address that your email from Monica will appear to be from. This is the `MAIL_FROM_ADDRESS` in your `.env` file (Note that the `MAIL_FROM_NAME` can be whatever you like - and will be the "friendly name" that appears in your email client).

In the SES console, go to "Email Addresses" and "Verify a New Email Address". Follow that flow through until the "Verification Status" for your email shows up as "Verified".



### 3. Allow SES to Send Emails Out

SES does not, by default, allow you to send email to arbitrary addresses. If you're only planning on having a single user on your Monica Instance, with a single email address for notifications, you can simply Verify the Address you're planning on sending to, just like in Step 2 above.

If you're planning on having multiple users with unknown email addresses, you'll have to [move out of the SES Sandbox Environment](https://docs.aws.amazon.com/ses/latest/DeveloperGuide/request-production-access.html).



### 4. Configure Monica to Use SES SMTP Server

You now simply need to configure your `.env` file to use the SES SMTP server. Make sure you use the correct server for the Region where you've configured your email addresses, or this will not work!

```
# Mail credentials used to send emails from the application.
MAIL_DRIVER=smtp
MAIL_HOST=email-smtp.us-east-1.amazonaws.com
MAIL_PORT=25
MAIL_USERNAME=<Step 1>
MAIL_PASSWORD=<Step 1>
MAIL_ENCRYPTION=tls
# Outgoing emails will be sent with these identity
MAIL_FROM_ADDRESS=<Step 2>
MAIL_FROM_NAME="Monica"
# New registration notification sent to this email
APP_EMAIL_NEW_USERS_NOTIFICATION=
```



Now you're all done! If you've changed your `.env` file since you last started Monica, use `php artisan setup:production -v` so that Monica reads your new configuration.
