# Update your server

Monica uses the concept of releases and tries to follow
[Semantic Versioning](http://semver.org/) as much as possible. If you run the project locally,
or if you have installed Monica on your own server, you need to follow the steps below to update it, **every single time**, or you will run into problems.

1. Always make a backup of your data before upgrading.
1. Check that your backup is valid.
1. Read the [release notes](https://github.com/monicahq/monica/blob/master/CHANGELOG.md) to check for breaking changes.
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
   composer install --no-interaction --no-suggest --no-dev --ignore-platform-reqs
   php artisan monica:update --force
   ```

The `monica:update` command will run migrations scripts for database, and flush all cache for config, route, and view, as an optimization process.
As the configuration of the application is cached, any update on the `.env` file will not be detected after that. You may have to run `php artisan config:cache` manually after every update of `.env` file.


Your instance should be updated.

## Updating Heroku instance

You can update your Monica instance to the latest version by cloning the repository and pushing it to Heroku git.

1. Clone the Monica repository to your local environment by `git clone https://github.com/monicahq/monica.git`.
1. Add your app's heroku git repository by `heroku git:remote -a (heroku app name)` (this of course requires the [Heroku CLI](https://devcenter.heroku.com/articles/heroku-cli).
1. Push to heroku by `git push heroku master`. Heroku will build and update the repository, automatically.

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

### Importing SQL into Heroku

If you're running your own Monica Heroku instance as mentioned in the [Heroku Installation Documentation](https://github.com/monicahq/monica/blob/master/docs/installation/heroku.md), you're not actually running your own SQL server, which means that the solutions above might not be of assitance.

Heroku dynos use a [ClearDB MySQL add-on](https://devcenter.heroku.com/articles/cleardb) as their database. You can still use an SQL admin tool (like phpMyAdmin or Sequel Pro) to interact with the database, as well as use the `mysql-client` command line tool, you just need to know where to look for the credentials. 

If you open your app on the Heroku web interface, and click the "Settings" tabk, you'll have an option to reveal your configuration vars. Do so, and look for the `CLEARDB_DATABASE_URL` variable. It's format should look like this:

`mysql://<USERNAME>:<PASSWORD>@<HOST>/<DATABASE>?reconnect=true`

Which are the database's `HOST` URL, its name (i.e. `DATABASE`)  and your `USERNAME` and `PASSWORD`.
The `HOST` should be the region where the databse is located (i.e. `us-cdbr-iron-east-01.cleardb.net`), the `DATABASE` should be prepended with `heroku_` (i.e. `heroku_xxxx`) and the `USERNAME` and `PASSWORD` should be strings of alphanumeric characters.

Now that you have the database's URL and access credentials, you can log into the database from your favorite database management tool. If you'd like to use a command-line tool, here are the step by step instructions for debian-based (e.g. Ubuntu) Linux:

#### WARNING: This will delete your current database. Only use on fresh installations, or if you know what you're doing. 

1. **Update your Monica instance to the same version as the one you're importing into.** This will prevent nasty SQL mismatches later on.
2. Download your export file as explained above. Make sure you remember the username and password of the instance you **exported from**, as those will be your new sign-in information for the instance you're **importing into**.
3. Get `mysql-client` by `sudo apt-get install mysql-client`. Note you might need to first add the relevant repositry using the instructions [here](https://downloads.mariadb.org/mariadb/repositories/#mirror=kku) (although don't follow them all the way, or you'll get a full running server on your own machine). If you're going to follow the scripted truncatiobn listed on the steps below, you'll need access to the MySQL socket, which is only available if you also installed `mysql-server`. You can do so by `sudo apt-get install mysql-server`. 
4. Connect to your database - `mysql --host=<HOST> --user=<USERNAME> --password=<PASSWORD> --reconnect <DATABASE>`. You should see something like this in your terminal:

```
mysql: [Warning] Using a password on the command line interface can be insecure.
Reading table information for completion of table and column names
You can turn off this feature to get a quicker startup with -A
```

We are indeed using the password on the CLI, so disregard the warning. The `Reading table....` part should only take 10-20 seconds or so, wait it out. After that you should be prompted by your instllation's MySQL database:

```
Welcome to the MySQL monitor.  Commands end with ; or \g.
Your MySQL connection id is 195775195
Server version: 5.5.62-log MySQL Community Server (GPL)

Copyright (c) 2000, 2019, Oracle and/or its affiliates. All rights reserved.

Oracle is a registered trademark of Oracle Corporation and/or its
affiliates. Other names may be trademarks of their respective
owners.

Type 'help;' or '\h' for help. Type '\c' to clear the current input statement.

mysql> 
```
5. Take a look around, if you'd like. If you'll enter `SHOW DATABASES` you'll see:

```
Connection id:    195779265
Current database: heroku_xxxxxxxxx

+------------------------+
| Database               |
+------------------------+
| information_schema     |
| heroku_xxxxxxxxx       |
+------------------------+
2 rows in set (19.85 sec)

```
Where `heroku_xxxxxxxxx` is `DATABASE`, your database's name. Note that the `Current database` is your Monica database, `DATABASE`.

We're now done looking around and you can disconnect from the database by entering `quit` and hitting the return key.

**Note:** If at any point the server disconnects, you'll see something like this:
```
mysql> SHOW DATABASES;
ERROR 2013 (HY000): Lost connection to MySQL server during query
mysql> SHOW DATABASES;
ERROR 2006 (HY000): MySQL server has gone away
No connection. Trying to reconnect...
```

This is prefectly fine, and the reason behind the `--reconnect` flag you saw earlier.

6. **DANGER: This will delete all the things.** Make sure you're not connected to the database anymore (i.e. you entered `quit` and got back to your own machine). 

Empty out all tables by running the following few lines of code (slightly modified from [this SO question](https://stackoverflow.com/questions/1912813/truncate-all-tables-in-a-mysql-database-in-one-command)), where all the credentials are the samen as mentioned earlier. You can also copy and paste it into a `.sh` file, `chmod 777 <FILE_NAME>` and then run it by `./<FILE_NAME>`.

```
# USAGE: mysql_run_query <QUERY>
mysql_run_query() {
# Connect to the database silently (-N and -s) and execute the given command (-e)
  mysql --host=<HOST> --user=<USERNAME> --password=<PASSWORD> --reconnect <DATABASE> -Nse "$1"
}


# The command below lists all the tables in your database, and pipes it to this while loop
echo "Getting all of the database's table names..."
mysql_run_query "SHOW TABLES;" |
while read table; do

  # Empty out (i.e. "TRUNCATE" each table)
  echo "Emptying out $table..."
  mysql_run_query "SET FOREIGN_KEY_CHECKS = 0;TRUNCATE TABLE $table;SET FOREIGN_KEY_CHECKS = 1;"

done

echo "Done!"
```

This should take a bit of time to run, but you should be able to see the process as the truncated table go by. Wait for the `Done!` message.

**Notes:**
* This script performs the table truncations independent of one another, and one by one - on different connections. This is done on purpose, to avoid any catastrophic finger-slips on the actual database's MySQL console. If something bad happens, this should allow you to kill the terminal in time, or at least `CTRL+C` out of there. If you know what you're doing, then you can just connect to the database and follow [this article](https://tableplus.com/blog/2018/08/mysql-how-to-truncate-all-tables.html) on how to truncate all the tables with one SQL query.
* If you get the following error:
```
ERROR 2002 (HY000): Can't connect to local MySQL server through socket '/var/run/mysqld/mysqld.sock' (2)
```
This probably means you have not installed `mysql-server` as mentioned before. Please do so now, and repeat the process.
* The `SET_FOREIGN_KEYS` part above relieves you of facing these type of errors:
```
ERROR 1701 (42000) at line 1: Cannot truncate a table referenced in a foreign key constraint
```
Due to the database's schema. If you do end up seeing those types of errors, please open an issue.

7. On your own machine (i.e. not on the remote database) import the fresh database into your installation (blatantly copied from this [SO answer](https://stackoverflow.com/questions/11803496/dump-sql-file-to-cleardb-in-heroku)):
```
mysql ---host=<HOST> --user=<USERNAME> --password=<PASSWORD> --reconnect <DATABASE> < monica.sql
```

If you get an error of the following format:
```
ERROR 1452 (23000) at line 8: Cannot add or update a child row: a foreign key constraint fails
```

Than open up `monica.sql` and at the following at the start of the file, right before the first `INSERT INTO...` statement:

```
SET FOREIGN_KEY_CHECKS = 0;
```

And this, at the very end of the file (after the last `INSERT INTO...` statement:

```
SET FOREIGN_KEY_CHECKS = 0
```

**Notes:**

* If you get an error of the following format:
```
ERROR 1064 (42000) at line 264: You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near .....
```
It means that the database schema you're trying to import into does not match the schema of that database you've exported from. This is usually due to a change in the schema between Monica version, and should only happen if you're migrating from an old, unupdated version of Monica to a new version on a new machine. Please file an issue if you see this error and we will attempt to assist you.

You should now be able to access your Monica instance with the same credentials used for the old instance.
