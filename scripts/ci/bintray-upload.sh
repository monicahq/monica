#!/bin/bash

set -evuo pipefail

SELF_PATH=$(cd -P -- "$(dirname -- "$0")" && /bin/pwd -P)
source $SELF_PATH/realpath.sh
ROOT=$(realpath $SELF_PATH/../..)

API=https://api.bintray.com
SLUG=$(jq -r ".package.subject" $ROOT/.deploy.json)
REPO=$(jq -r ".package.repo" $ROOT/.deploy.json)
PACKAGE=$(jq -r ".package.name" $ROOT/.deploy.json)
VERSION=$(jq -r ".version.name" $ROOT/.deploy.json)

# Create version
curl -sS --user "${BINTRAY_USER}:${BINTRAY_APIKEY}" -H "Content-Type: application/json" \
    -X POST "$API/packages/${SLUG}/${REPO}/${PACKAGE}/versions" \
    -d "$(jq -r ".version" $ROOT/.deploy.json)"

# Upload file
# TODO
curl -sS --user "${BINTRAY_USER}:${BINTRAY_APIKEY}" \
    -X PUT -T $INPUT \
    "$API/content/${SLUG}/${REPO}/${PACKAGE}/${VERSION}/$FILE?publish=1&override=1"

# Update properties
curl -sS --user "${BINTRAY_USER}:${BINTRAY_APIKEY}" -H "Content-Type: application/json" \
    -X PATCH "$API/packages/${SLUG}/${REPO}/${PACKAGE}/versions/${VERSION}" \
    -d "$(jq -r ".version" $ROOT/.deploy.json)"

# Update attributes
curl -sS --user "${BINTRAY_USER}:${BINTRAY_APIKEY}" -H "Content-Type: application/json" \
    -X POST "$API/packages/${SLUG}/${REPO}/${PACKAGE}/versions/${VERSION}/attributes" \
    -d "$(jq -r ".version.attributes" $ROOT/.deploy.json)"
