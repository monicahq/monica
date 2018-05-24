#!/bin/bash

set -euvo pipefail

GIT_TAG=$(git describe --abbrev=0 --tags)
VERSION=${GIT_TAG##v}
API=https://app.vagrantup.com/api/v1

# Create a new version
response=$(
curl -sSL --header "Content-Type: application/json" --header "Authorization: Bearer $VAGRANT_CLOUD_TOKEN" \
    "$API/box/monicahq/monicahq/versions" \
    --data "{ \
        \"version\": { \
            \"version\": \"$VERSION\", \
            \"description\": \"https://github.com/monicahq/monica/releases/tag/$GIT_TAG\" \
        } \
    }"
)
if [ $(echo "$response" | jq .success) == "false" ]; then
  if [ $(echo "$response" | jq .errors[0]) != "Version has already been taken" ]; then
    echo "$response" | jq .errors[0]
    exit 1
  fi
fi

# Create provider
response=$(
curl -sSL --header "Content-Type: application/json" --header "Authorization: Bearer $VAGRANT_CLOUD_TOKEN" \
  "$API/box/monicahq/monicahq/version/$VERSION/providers" \
  --data "{ \
      \"provider\": { \
        \"name\": \"virtualbox\" \
      } \
    }"
)
if [ $(echo "$response" | jq .success) == "false" ]; then
  if [ $(echo "$response" | jq .errors[0]) != "Metadata provider must be unique for version" ]; then
    echo "$response" | jq .errors[0]
    exit 1
  fi
fi

# Upload the box
response=$(
curl -sSL --header "Authorization: Bearer $VAGRANT_CLOUD_TOKEN" \
  "$API/box/monicahq/monicahq/version/$VERSION/provider/virtualbox/upload"
)
if [ $(echo "$response" | jq .success) == "false" ]; then
  echo "$response" | jq .errors[0]
  exit 1
fi
upload_path=$(echo "$response" | jq .upload_path)
upload_path="${upload_path%\"}"
upload_path="${upload_path#\"}"

curl -fL --progress-bar \
  --request PUT \
  "$upload_path" \
  --upload-file "monicahq-$GIT_TAG.box"
retval=$?
if [ $? -ne 0 ]; then
    exit 1
fi

# Publish the version
response=$(
curl -sSL --header "Authorization: Bearer $VAGRANT_CLOUD_TOKEN" \
    --request PUT \
    "$API/box/monicahq/monicahq/version/$VERSION/release"
)
if [ $(echo "$response" | jq .success) == "false" ]; then
  echo "$response" | jq .errors[0]
  exit 1
fi
