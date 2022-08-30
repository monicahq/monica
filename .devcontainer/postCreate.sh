#!/bin/bash

DATABASE=database/database.sqlite

chmod a+x /root && sudo rm -rf /var/www/html && sudo ln -s "$(pwd)/public" /var/www/html
cp .env.example .env && echo "APP_TRUSTED_PROXIES=*" >> .env
sed -i "s%DB_CONNECTION=.*%DB_CONNECTION=sqlite%" .env
sed -i "s%DB_DATABASE=.*%DB_DATABASE=$(pwd)/$DATABASE%" .env
touch $DATABASE && chgrp www-data database $DATABASE && chmod g+w database $DATABASE

sed -i "s%MAIL_MAILER=.*%MAIL_MAILER=log%" .env
sed -i "s%MAIL_FROM_ADDRESS=.*%MAIL_FROM_ADDRESS=from@mail.com%" .env
sed -i "s%MAIL_REPLY_TO_ADDRESS=.*%MAIL_REPLY_TO_ADDRESS=reply@mail.com%" .env
chgrp -R www-data storage && chmod -R g+w storage

composer install
php artisan key:generate --no-interaction
php artisan migrate --no-interaction -v
php artisan monica:setup -vvv
yarn install
yarn run build

a2enmod rewrite
service apache2 restart
