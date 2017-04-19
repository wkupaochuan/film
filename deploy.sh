#!/bin/bash
rsync -tIpgocrvl --delay-updates --timeout=60 ./* filmfilm@166.62.86.13:/home/www/film/