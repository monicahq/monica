#!/bin/sh
SELF_PATH=$(cd -P -- "$(dirname -- "$0")" && /bin/pwd -P)

cat $SELF_PATH/../azure-pipelines.yml $SELF_PATH/*.yml > $SELF_PATH/azure
gpg -sb $SELF_PATH/azure || true
rm -f $SELF_PATH/azure
