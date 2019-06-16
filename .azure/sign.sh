#!/bin/sh
SELF_PATH=$(cd -P -- "$(dirname -- "$0")" && /bin/pwd -P)

cat $SELF_PATH/../azure-pipelines.yml $SELF_PATH/*.yml | gpg -sb -o $SELF_PATH/azure.sig -
