This document lists some tips that developers can use to add new content in Monica and contains the solution to uncommon problems.

## Add a new changelog entry

Changelog entries are used to describe what's new in the product. Each time an entry is created, we also need to create an association between users of the instance and the entry that has been created. This association is stored in `changelog_user`.

To add a new changelog entry, you need to:
* create a new entry in the `changelog.json` file in the public folder.
* make sure your new entry is at the TOP of the file, not the bottom.

### When is it relevant to create a changelog entry

We should not create a changelog entry every single time we make a change to the platform. The rule is to warn users only when we introduce something that they will benefit from. A bug fix is not something they will benefit from and is not worth mentioning. Simple visual changes, unless they drastically change the UI, should not be mentioned. Anything made on the Docker image or a packaging issue should not be mentioned.

## Failed Jobs queue

PostgreSQL users who previously failed Monica's update may receive errors similar to these:

> SQLSTATE[Number]: Duplicate table: 7 ERROR:  relation "**failed_jobs**" already exists (SQL: create table "failed_jobs" ("id" bigserial primary key not null, "connection" text not null, "queue" text not null, "payload" text not null, "exception" text not null, "failed_at" timestamp(0) without time zone default CURRENT_TIMESTAMP not null))

> SQLSTATE[Number]: Duplicate table: 7 ERROR:  relation "**failed_jobs**" already exists

The problem is solved by running the command:

```
php artisan queue:flush
```
...with the appropriate privileges.

Immediately after (through a tool like `psql`) access the Monica database and run this command:
```
DROP TABLE "failed_jobs";
```
This will ensure that the failed job table has actually been deleted.
