#!/bin/bash

if [ "$SONAR_TOKEN" == "\$(SONAR_TOKEN)" ]; then
    echo -e "\033[0;36mFix SONAR_TOKEN\033[0;37m"
    export SONAR_TOKEN=
fi
if [ "$GITHUB_TOKEN" == "\$(GITHUB_TOKEN)" ]; then
    echo -e "\033[0;36mFix GITHUB_TOKEN\033[0;37m"
    export GITHUB_TOKEN=
fi
if [ "$GH_TOKEN" == "\$(GH_TOKEN)" ]; then
    echo -e "\033[0;36mFix GH_TOKEN\033[0;37m"
    export GH_TOKEN=
fi
if [ "$ASSETS_GITHUB_TOKEN" == "\$(ASSETS_GITHUB_TOKEN)" ]; then
    echo -e "\033[0;36mFix ASSETS_GITHUB_TOKEN\033[0;37m"
    export ASSETS_GITHUB_TOKEN=
fi
if [ "$STRIPE_SECRET" == "\$(STRIPE_SECRET)" ]; then
    echo -e "\033[0;36mFix STRIPE_SECRET\033[0;37m"
    export STRIPE_SECRET=
fi
if [ "$CYPRESS_RECORD_KEY" == "\$(CYPRESS_RECORD_KEY)" ]; then
    echo -e "\033[0;36mFix CYPRESS_RECORD_KEY\033[0;37m"
    export CYPRESS_RECORD_KEY=
fi
