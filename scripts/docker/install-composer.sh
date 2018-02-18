#!/bin/sh

SETUP=composer-setup.php
cd /tmp

EXPECTED_SIGNATURE=$(curl -sS https://composer.github.io/installer.sig)
curl -sS -o $SETUP https://getcomposer.org/installer
ACTUAL_SIGNATURE=$(openssl sha384 $SETUP | cut -d' ' -f2)

if [ "$EXPECTED_SIGNATURE" != "$ACTUAL_SIGNATURE" ]
then
    >&2 echo 'ERROR: Invalid installer signature'
    rm $SETUP
    exit 1
fi

php $SETUP --quiet --install-dir=/usr/local/bin --filename=composer
RESULT=$?
rm $SETUP

exit $RESULT
