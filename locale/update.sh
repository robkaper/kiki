#!/bin/sh

files=`find ../htdocs ../lib ../templates -name \*.php -or -name \*.tpl`
xgettext --force-po -L PHP --copyright-holder "Rob Kaper" --package-name "Kiki" ${files}
perl -p -i -e's,CHARSET,ASCII,' messages.po

languages=`find . -maxdepth 1 -mindepth 1 -type d | sed s',^./,,'`

for language in $languages; do
	messages="${language}/LC_MESSAGES"
	if [ -d "${messages}" ]; then
		msgmerge -v -U ${messages}/messages.po messages.po
	else
		mkdir -p ${messages}
		msginit --no-translator -l ${language} -o ${messages}/messages.po -i messages.po
	fi

	msgfmt -v ${messages}/messages.po -o ${messages}/messages.mo
done
