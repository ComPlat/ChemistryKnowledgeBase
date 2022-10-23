#!/usr/bin/env bash

## Clear temporary files
#
# should be called periodically 
# and removes files older than 50 days

find /tmp/* -mtime +50 -exec rm -rf {} \;