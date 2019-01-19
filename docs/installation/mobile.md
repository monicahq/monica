# Use the mobile application with your server

## Activate login through a mobile application

* You need to use the CLI to generate a new key specific to your application. You CAN'T generate this kind of key using Monica's UI.
* Run `php artisan passport:install`. To generate a new set of OAuth keys, a personal access client, and a password grant client. It outputs something like this:
```
Personal access client created successfully.
Client ID: 1
Client Secret: mPWe3F9x1E2R4YsN9lXdTM6DfcCLyLQT11vMSg2K
Password grant client created successfully.
Client ID: 2
Client Secret: zsfOHGnEbadlBP8kLsjOV8hMpHAxb0oAhenfmSqq
```
* Edit your .env file and add the following lines (replace the values with the password grant client values generated above)
```
MOBILE_CLIENT_ID=2
MOBILE_CLIENT_SECRET=zsfOHGnEbadlBP8kLsjOV8hMpHAxb0oAhenfmSqq
```

This should let users of the mobile application to access their accounts on your instance.
