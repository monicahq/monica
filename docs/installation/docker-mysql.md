# Connect to MySQL inside of a Docker container

There may come a time when developing or managing a Monica instance that you
would like to connect directly to the MySQL database being used.

For example you may want to
- alter test data
- generate custom metrics
- ...

---

1. Stop your Monica and MySQL containers if they are running
1. Bind port 3306 in your container to 3306 on your host machine
    1. Add `EXPOSE 3306:3306` to your `Dockerfile` under `EXPOSE 80:80`
    1. Add a `ports` node under `mysql` in `docker-compose.yml` with a `3306:3306` entry
1. Start your containers with `docker-compose up`, wait for them to spin up
1. Connect to the database via a MySQL client (CLI, GUI, etc.)

---

## Sequel Pro (macOS)

You can install Sequel Pro from [the website](http://www.sequelpro.com/) or via
`brew cask install sequel-pro` if you have homebrew cask installed.

Connect to the database using the information in your `.env` file.
In this example image we are using the default values.

![SequelProConnecting](https://user-images.githubusercontent.com/25419741/38381051-e8e0b184-3905-11e8-85ce-3122beb83513.png)


If that is successful you are now connected and can execute queries, look at schemas, etc.

![SequelProConnected](https://user-images.githubusercontent.com/25419741/38381084-fbe6b008-3905-11e8-8c1c-d2a29c2d5e7e.png)
