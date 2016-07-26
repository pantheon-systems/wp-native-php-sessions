#!/bin/bash

###
# Execute the Behat test suite against a prepared Pantheon site environment.
###

if [ -z "$TERMINUS_TOKEN" ]; then
	echo "TERMINUS_TOKEN environment variables missing; assuming unauthenticated build"
	exit 0
fi

set -ex

export BEHAT_PARAMS='{"extensions" : {"Behat\\MinkExtension" : {"base_url" : "http://'$TERMINUS_ENV'-'$TERMINUS_SITE'.pantheonsite.io"} }}'

./vendor/bin/behat "$@"
