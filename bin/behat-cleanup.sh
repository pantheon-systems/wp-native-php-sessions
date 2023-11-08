#!/bin/bash

###
# Delete the Pantheon site environment after the Behat test suite has run.
###

if [ -z "$TERMINUS_SITE" ] || [ -z "$TERMINUS_ENV" ]; then
	echo "TERMINUS_SITE and TERMINUS_ENV environment variables must be set"
	exit 1
fi

if [ -z "$WORDPRESS_ADMIN_USERNAME" ] || [ -z "$WORDPRESS_ADMIN_PASSWORD" ]; then
	echo "WORDPRESS_ADMIN_USERNAME and WORDPRESS_ADMIN_PASSWORD environment variables must be set"
	exit 1
fi

if [ -v PHP_VERSION ] && [ "$PHP_VERSION" == "8.3" ]; then
	SITE_ENV="83-$SITE_ENV"
fi

set -ex

###
# Delete the environment used for this test run.
###
terminus multidev:delete "$SITE_ENV" --delete-branch --yes
