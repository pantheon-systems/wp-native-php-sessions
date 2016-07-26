#!/bin/bash

###
# Prepare a Pantheon site environment for the Behat test suite, by installing
# and configuring the plugin for the environment. This script is architected
# such that it can be run a second time if a step fails.
###

if [ -z "$TERMINUS_TOKEN" ]; then
	echo "TERMINUS_TOKEN environment variables missing; assuming unauthenticated build"
	exit 0
fi

set -ex

if [ -z "$TERMINUS_SITE" ] || [ -z "$TERMINUS_ENV" ]; then
	echo "TERMINUS_SITE and TERMINUS_ENV environment variables must be set"
	exit 1
fi

###
# Create a new environment for this particular test run.
###
terminus site create-env --to-env=$TERMINUS_ENV --from-env=dev
yes | terminus site wipe

###
# Get all necessary environment details.
###
PANTHEON_GIT_URL=$(terminus site connection-info --field=git_url)
PANTHEON_SITE_URL="$TERMINUS_ENV-$TERMINUS_SITE.pantheonsite.io"
PREPARE_DIR="/tmp/$TERMINUS_ENV-$TERMINUS_SITE"
BASH_DIR="$( cd -P "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

###
# Switch to git mode for pushing the files up
###
terminus site set-connection-mode --mode=git
rm -rf $PREPARE_DIR
git clone -b $TERMINUS_ENV $PANTHEON_GIT_URL $PREPARE_DIR

###
# Add the copy of this plugin itself to the environment
###
rm -rf $PREPARE_DIR/wp-content/plugins/wp-native-php-sessions
cd $BASH_DIR/..
rsync -av --exclude='vendor/' --exclude='node_modules/' --exclude='tests/' ./* $PREPARE_DIR/wp-content/plugins/wp-native-php-sessions
rm -rf $PREPARE_DIR/wp-content/plugins/wp-native-php-sessions/.git


###
# Add the debugging plugin to the environment
###
rm -rf $PREPARE_DIR/wp-content/mu-plugins/sessions-debug.php
cp $BASH_DIR/fixtures/sessions-debug.php $PREPARE_DIR/wp-content/mu-plugins/sessions-debug.php

###
# Push files to the environment
###
cd $PREPARE_DIR
git add wp-content
git config user.email "wp-native-php-sessions@getpantheon.com"
git config user.name "Pantheon"
git commit -m "Include WP Native PHP Sessions and its configuration files"
git push

# Sometimes Pantheon takes a little time to refresh the filesystem
sleep 10

###
# Set up WordPress, theme, and plugins for the test run
###
terminus wp "core install --title=$TERMINUS_ENV-$TERMINUS_SITE --url=$PANTHEON_SITE_URL --admin_user=pantheon --admin_email=wp-redis@getpantheon.com --admin_password=pantheon"
terminus wp "plugin activate wp-native-php-sessions"
