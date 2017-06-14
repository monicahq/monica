# Deploy on Heroku

Monica can be deployed on Heroku using the button below:

[![Deploy](https://www.herokucdn.com/deploy/button.svg)](https://heroku.com/deploy)

Please ensure to enter a custom `APP_KEY` when asked. Your Monica instance will
utilise a [ClearDB Ignite plan](https://elements.heroku.com/addons/cleardb) by
default. Additional environment variables, such as details of the mail server,
can be added after setup through the Heroku interface.

Monica doesn't require a lot of power - it means it will run on the free plan
provided by Heroku.

There is one issue with it though at the moment: you won't be able to upload
photos to your contacts, as Heroku doesn't support storage. We'll need to fix
this in the future.
