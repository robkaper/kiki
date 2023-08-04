#!/bin/sh

egrep -rinI "(// |@)(FIXME|TODO|DEBUG)" htdocs/ lib/ bin/ 2>&1 | egrep -v "(^htdocs/vendor)"
