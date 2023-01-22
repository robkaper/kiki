#!/bin/sh

egrep -riI "(// |@)(FIXME|TODO|DEBUG)" htdocs/ lib/ bin/ 2>&1 | egrep -v "(^htdocs/vendor)"

egrep -riI "(\$debug|em-|(<style=)|<script>)" htdocs/ lib/ bin/ 2>&1 | egrep -v "(^htdocs/(vendor|images))"

