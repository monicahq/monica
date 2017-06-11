#!/bin/sh

EXPECTED_SIGNATURE=$(curl -s https://composer.github.io/installer.sig)
curl -s -o composer-setup.php https://getcomposer.org/installer
ACTUAL_SIGNATURE=$(openssl sha384 composer-setup.php | cut -d' ' -f2)

if [ "$EXPECTED_SIGNATURE" != "$ACTUAL_SIGNATURE" ]
then
    >&2 echo 'ERROR: Invalid installer signature'
    rm composer-setup.php
    exit 1
fi

php composer-setup.php --quiet
RESULT=$?
rm composer-setup.php
exit $RESULT
