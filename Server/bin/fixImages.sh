#!/bin/bash

cd /mnt/store/Emulation/Assets/RomScan

## Rename PNG to JPEG if named incorrectly
find * -type f -iname "*.png" -print0 | while IFS= read -r -d '' thisImage; do
        ## Check if it's actually a JPEG
        jpegCheck=$(file "${thisImage}" 2>/dev/null | grep "JPEG image data")

        ## If the variable is set, it's a JPEG
        if ! [ -z "${jpegCheck}" ]; then
                if [ "${lastPrint}" == 'dot' ]; then
                        echo ''
                        lastPrint=''
                fi

                echo -en "\n== Renaming: ${thisImage}\n"
                perl-rename 's/.png/.jpg/' "${thisImage}"
                newName=$(echo ${thisImage} | sed 's/.png/.jpg/')
                exiv2 rm "${newName}"
                file "${newName}"
        else
                if ! [ "${lastPrint}" == 'dot' ]; then
                        echo ''
                        lastPrint='dot'
                fi
                echo -n "."
                lastPrint='dot'
        fi
done

## Rename JPEG to JPG
find * -type f -iname "*.jpeg" -print0 | while IFS= read -r -d '' thisImage; do
	echo -en "\n== Renaming: ${thisImage}\n"
	perl-rename 's/.jpeg/.jpg/' "${thisImage}"
	newName=$(echo ${thisImage} | sed 's/.jpeg/.jpg/')
	exiv2 rm "${newName}"
	file "${newName}"
done

echo ''
