#!/bin/sh

file=$1
release=$2

curl -H "Authorization: token $GITHUB_TOKEN" \
    -H "Content-Type: application/octet-stream" \
    -X POST \
    --data-binary @"$file" \
    "https://uploads.github.com/repos/monicahq/monica/releases/$release/assets?name=$(basename $file)"
