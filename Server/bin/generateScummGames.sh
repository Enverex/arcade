#!/bin/bash

rm "/mnt/store/Emulation/Games/PC - ScummVM/"*.sh

cd "/mnt/store/Emulation/Games/PC - ScummVM"

for FOLDER in *; do
	GNAME=$(echo "${FOLDER}" | sed -e 's/[ ]*([^()]*)[ ]*//g')
	echo "eLord scumm \"${FOLDER}\"" > "/mnt/store/Emulation/Games/PC - ScummVM/${GNAME}.sh"
done
