#!/bin/bash -e

cd /mnt/store/Emulation/Assets/MAME-Extra

echo -e "\n\n==== Syncing Games\n"
rsync -vrs --delete --size-only --progress -e 'ssh -p 222' "root@xnode.org:/srv/transmission/MAME 0.1* ROMs (merged)/" /mnt/store/Emulation/Games/MAME

echo -e "\n\n==== Syncing Videos\n"
rsync -vrs --size-only --progress -e 'ssh -p 222' "root@xnode.org:/srv/transmission/MAME 0.1* Multimedia/videosnaps" /mnt/store/Emulation/Assets/MAME-Extra/

echo -e "\n\n==== Syncing Artwork\n"
rsync -vrs --size-only --progress -e 'ssh -p 222' "root@xnode.org:/srv/transmission/MAME 0.1* EXTRAs/"{samples,artwork,flyers,marquees,logo,titles,snap} /mnt/store/Emulation/Assets/MAME-Extra/

#for ARC in *.zip; do
#	7z x -aoa "${ARC}" -o./$(basename "${ARC}" .zip)
#done
