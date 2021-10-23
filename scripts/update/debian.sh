#!/bin/bash

# And now starting the upgrade itself...

# 1. Open Monica's directory

     cd /var/www/monica  ; \

# 2. Checkout the current version

     git fetch upstream  ; \

# ...and make sure we're working on master

     git checkout master  ; \

# 3. Delete Composer packages

     rm -rf vendor  ; \

# Note: This step might *not* be strictly necessary, but it avoids fatal
#       fatal errors when attempting to update Monica on a Debian system.
#       More information: https://github.com/monicahq/monica/issues/5308

# 4. Now (re)install all required Composer packages

     composer install --no-dev  ; \

# 5. Install required yarn packages

     yarn install  ; \

# 6. Generate assets (CSS, JS, others) using yarn

     yarn run production  ; \

# 7. Update Monica itself.  Per the README, this "will run migration
#    scripts for database, and flush all cache for config, route, and 
#    view, as an optimization process. It's easier than running every 
#    required command individually".

     php artisan monica:update --force  ; \

# 8. And finally, set correct ownership and permissions for Monica.
#    This is another step that may not be necessary, but it should avoid
#    any "permission denied" or HTTP 500 errors being created.

     chown -R www-data:www-data /var/www/monica  ; \

     find  /var/www/monica -type f -exec chmod 664 {}  ; \

     find /var/www/monica -type d -exec chmod 775 {}

# And that's it!
