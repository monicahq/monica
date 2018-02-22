#!/bin/bash

set -v

curl -sS --user "${BINTRAY_USER}:${BINTRAY_APIKEY}" -H "Content-Type: application/json" \
    -X PATCH https://api.bintray.com/packages/monicahq/docker/monicahq%3Amonicahq/versions/${BUILD} \
    -d "{ \
            \"github_use_tag_release_notes\": true, \
            \"vcs_tag\": \"${TRAVIS_TAG}\" \
        }"

curl -sS --user "${BINTRAY_USER}:${BINTRAY_APIKEY}" -H "Content-Type: application/json" \
    -X POST https://api.bintray.com/packages/monicahq/docker/monicahq%3Amonicahq/versions/${BUILD}/attributes \
    -d "[ \
            {\"name\": \"commit\", \"values\" : [\"${GIT_COMMIT}\"], \"type\": \"string\"}, \
            {\"name\": \"build\", \"values\" : [${TRAVIS_BUILD_NUMBER}],}, \
            {\"name\": \"date\", \"values\" : [\"$(date --iso-8601=s)\"], \"type\": \"date\"} \
        ]"
