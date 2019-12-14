#!/bin/bash

set -evuo pipefail

SELF_PATH=$(cd -P -- "$(dirname -- "$0")" && /bin/pwd -P)
source $SELF_PATH/realpath.sh
ROOT=$(realpath $SELF_PATH/../..)

SLUG=${SLUG:-monicahq}
REPO=${REPO:-docker}
PACKAGE=${PACKAGE:-monicahq%3Amonicahq}

curl -sS --user "${BINTRAY_USER}:${BINTRAY_APIKEY}" -H "Content-Type: application/json" \
    -X PATCH https://api.bintray.com/packages/${SLUG}/${REPO}/${PACKAGE}/versions/${BUILD} \
    -d "$(jq -r ".version" $ROOT/.deploy.json)"

curl -sS --user "${BINTRAY_USER}:${BINTRAY_APIKEY}" -H "Content-Type: application/json" \
    -X POST https://api.bintray.com/packages/${SLUG}/${REPO}/${PACKAGE}/versions/${BUILD}/attributes \
    -d "$(jq -r ".version.attributes" $ROOT/.deploy.json)"
