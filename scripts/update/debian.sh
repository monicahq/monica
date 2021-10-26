#!/bin/bash
#
# ABOUT THIS SCRIPT
# -----------------
# Last updated: 2021-10-26
# Last tested:  Monica v3.3.1
#
# USE AT YOUR OWN RISK!  Make sure you have a good, working backup of
# your database and other files before using this script.
#
# WHAT THIS SCRIPT DOES
# ---------------------
# This script has been written to run the various steps required to
# upgrade Monica to the specified version.  You can check which is the
# current version here: https://github.com/monicahq/monica/releases
#
# HOW TO USE THIS SCRIPT
# ----------------------
# To run this command, you run the shell script and specify the version
# you want to update to, like so:
#
#       $ ./debian.sh 3.3.1
#
# Replacing "3.3.1" with the version you want.  The script will then run
# through the required steps to update you to 3.3.1.

# THE SCRIPT ITSELF
# -----------------
# 1. Open Monica's directory

     cd /var/www/monica ; \

# 2. Checkout the current version

     git fetch ; \

     git checkout tags/v$1

# 3. Delete Composer packages

     rm -rf vendor  ; \

# Note: This step might *not* be strictly necessary, but it avoids fatal
#       errors when attempting to update Monica on a Debian system.
#       More information: https://github.com/monicahq/monica/issues/5308

# 4. Now (re)install all required Composer packages

     composer install --no-dev  ; \

# 5. Delete all the node modules installed by yarn

     rm -rf node_modules

# Note: This step might *not* be strictly necessary, but it avoids yarn
#       throwing 126 exit errors that I can't otherwise fix.


# 6. Now (re)install required yarn packages

     yarn install  ; \

# 7. Generate assets (CSS, JS, others) using yarn

     yarn run production  ; \

# 8. Update Monica itself.  Per the README, this "runs migration scripts
#    for the database, and flushes all caches for config, route, and
#    view as an optimization process. It’s easier than running every
#    required command individually".

     php artisan monica:update --force  ; \

# 9. And finally, set correct ownership and permissions for Monica.
#    This is another step that may not be necessary, but it should avoid
#    any "permission denied" or HTTP 500 errors being created.

     chown -R www-data:www-data /var/www/monica  ; \

     find /var/www/monica -type f -exec chmod 664 {} \;

     find /var/www/monica -type d -exec chmod 775 {} \;

# And that's it!
