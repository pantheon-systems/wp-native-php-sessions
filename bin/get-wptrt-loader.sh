#!/bin/bash

# URL of the remote file
remote_url="https://raw.githubusercontent.com/WPTT/autoload/master/src/Loader.php"

# Path to the local file
local_file="lib/Loader.php"

# Get the last modification time of the remote file in seconds since epoch
remote_epoch=$(curl -sI $remote_url | grep -i last-modified | awk -F': ' '{print $2}' | awk -F' ' '{print mktime($5" "$4" "$3" "$2" "$1" 00 00 00")}')

if [[ -e $local_file ]]; then
    echo "Found local WPTRT Loader..."
    # Get the modification time of the local file in seconds since epoch
    local_epoch=$(stat -c %Y $local_file 2>/dev/null || stat -f %m $local_file)

    # Compare modification times
    if (( remote_epoch > local_epoch )); then
        echo "Remote WPTRT Loader is newer, downloading..."
        # Remote file is newer, download it
        wget -O $local_file $remote_url
    fi
else
    echo "No local WPTRT Loader found, downloading..."
    # Local file doesn't exist, download the remote file
    wget -O $local_file $remote_url
fi
