#!/bin/bash

## Important script that needs to run every n-minutes.

echo "I'm still doing great stuff, and using environment variable example: $EXAMPLE!" >> /var/www/cron.no_environment.log
. /run/supervisord.env
echo "I'm still doing great stuff, and using environment variable example: $EXAMPLE!" >> /var/www/cron.sourced_environment.log
