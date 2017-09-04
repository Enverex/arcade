#!/bin/bash

function doFix {
	echo "$1"
	mogrify -strip -trim +repage "$1"
	optipng "$1"
}

export -f doFix
cd /mnt/store/Emulation/Assets/RomScan
find */Wheel -type f -iname "*.png" -execdir bash -c 'doFix "$0"' {} \;
