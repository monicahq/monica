# Deploy on Heroku

Monica can be deployed on Heroku using the button below:

[![Deploy](https://www.herokucdn.com/deploy/button.svg)](https://heroku.com/deploy?template=https://github.com/monicahq/monica/tree/master)

Please ensure to enter a custom `APP_KEY` when asked. Your Monica instance will
utilise a [ClearDB Ignite plan](https://elements.heroku.com/addons/cleardb) (free)
by default. Additional environment variables, such as details of the mail server,
can be added after setup through the Heroku interface.

Email configuration isn't required to use Monica on Heroku, but it's
recommended. Mailgun has a
[free email add-on on Heroku](https://elements.heroku.com/addons/mailgun) that is
easy to set up.

Monica doesn't require a lot of power - it means it will run on the free plan
provided by Heroku.

There is one issue with it though at the moment: you won't be able to upload
photos to your contacts, as Heroku doesn't support storage. We'll need to fix
this in the future.

## Updating Heroku instance

You can update your Monica instance to the latest version by cloning the repository
and pushing it to Heroku git.

Clone the Monica repository to your local environment by `git clone https://github.com/monicahq/monica`,
and add heroku git repository by `heroku git:remote -a (heroku app name)`. Then,
push to heroku by `git push heroku master`. Heroku will build and update the
repository, automatically.
