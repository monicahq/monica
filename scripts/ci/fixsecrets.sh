#!/bin/bash

if [ "$SONAR_TOKEN" == "\$(SONAR_TOKEN)" ]; then
    export SONAR_TOKEN=
fi
if [ "$GITHUB_TOKEN" == "\$(GITHUB_TOKEN)" ]; then
    export GITHUB_TOKEN=
fi
if [ "$ASSETS_GITHUB_TOKEN" == "\$(ASSETS_GITHUB_TOKEN)" ]; then
    export ASSETS_GITHUB_TOKEN=
fi
