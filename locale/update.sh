#!/bin/sh

echo "Finding templates... "
files=`find ../templates/ -name \*.tpl`

echo "Extracting static strings... "
../bin/extract-i18n.php ${files} > ./generated.php

echo "Finding PHP files... "
files=`find ../htdocs ../lib ./ -name \*.php`

echo "Running xgettext... "
xgettext --force-po -L PHP --copyright-holder "Rob Kaper" --package-name "Kiki" ${files}

echo "Settings charset to UTF-8... "
perl -p -i -e's,CHARSET,UTF-8,' messages.po

echo -n "Finding languages... "
languages=`find . -maxdepth 1 -mindepth 1 -type d | sed s',^./,,'`
echo "${languages}"

for language in $languages; do
	echo
	messages="${language}/LC_MESSAGES"
	if [ -d "${messages}" ]; then
		echo "Running msgmerge for ${language}:"
		msgmerge -v -U ${messages}/messages.po messages.po
		echo
	else
		echo "Running msginit for ${language}:"
		mkdir -p ${messages}
		msginit --no-translator -l ${language} -o ${messages}/messages.po -i messages.po
		echo
	fi

	echo "Running msgfmt for ${language}... "
	msgfmt -v ${messages}/messages.po -o ${messages}/messages.mo
done
