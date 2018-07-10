This document lists some tips that developers can use to add new content in Monica.

## Add a new changelog entry

Changelog entries are used to describe what's new in the product. Each time an entry is created, we also need to create an association between users of the instance and the entry that has been created. This association is stored in `changelog_user`.

To add a new changelog entry, you need to:
* create a migration that will insert a new changelog entry (example here)
* call `addUnreadChangelogEntry` method on the Instance object. This will create jobs to create the association between users and the entry.

Note: why jobs and not direct inserts? Because on our hosted version, we have so many users that this will timeout. So we process jobs, that are short and will never time out. The downside of this approach is that it creates a lot of jobs, but that's ok.

### When is it relevant to create a changelog entry

We should not create a changelog entry every single time we make a change to the platform. The rule is to warn users only when we introduce something that they will benefit from. A bug fix is not something they will benefit from and is not worth mentioning. Simple visual changes, unless they drastically change the UI, should not be mentioned. Anything made on the Docker image or a packaging issue should not be mentioned.
