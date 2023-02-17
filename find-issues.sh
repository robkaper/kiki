#!/bin/sh

egrep -rinI "(// |@)(FIXME|TODO|DEBUG)" htdocs/ lib/ bin/ 2>&1 | egrep -v "(^htdocs/vendor)"

egrep -rinI "(\$debug|em-|(<style=)|<script>)" htdocs/ lib/ bin/ 2>&1 | egrep -v "(^htdocs/(vendor|images))"
