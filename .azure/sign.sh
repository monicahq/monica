#!/bin/sh
SELF_PATH=$(cd -P -- "$(dirname -- "$0")" && /bin/pwd -P)

cat $SELF_PATH/../azure-pipelines.yml $SELF_PATH/*.yml > $SELF_PATH/azure
rm -f $SELF_PATH/azure.sig
gpg --sign --armor --armor --output $SELF_PATH/azure.sig --detach-sig $SELF_PATH/azure || true
gpg --verify $SELF_PATH/azure.sig || true
rm -f $SELF_PATH/azure
