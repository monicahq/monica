#!/bin/bash

l10nbranch=l10n_master3
newbranch=$(date +"%Y-%m-%d")-update-i18n
origin=origin

git checkout master

# Clean
git branch -D $l10nbranch || true
git branch -D $origin/$l10nbranch || true
git branch -D $newbranch || true
git branch -D $origin/$newbranch || true

# Create new branch
git fetch $origin
git checkout $origin/$l10nbranch
git branch --set-upstream-to=$newbranch $newbranch

# Merge from master
git merge $origin/master

# Update the new branch
php artisan lang:generate
git add public/js/langs/*.json
git commit -m "Update jsons"

# Push it to remote
git push $origin $newbranch
