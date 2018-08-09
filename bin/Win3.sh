## Passthrough
#-device vfio-pci,host=01:00.0,x-vga=on -vga none \
## Spice
#-display none -vga qxl -device virtio-serial-pci -device virtserialport,chardev=spicechannel0,name=com.redhat.spice.0 -chardev spicevmc,id=spicechannel1,name=vdagent -spice unix,addr=/run/user/1000/qemu.sock,disable-ticketing -chardev spicevmc,id=spicechannel0,name=vdagent -daemonize \
#sleep 1; spicy -f --uri="spice+unix:///run/user/1000/qemu.sock"
#	-vga virtio -full-screen -sdl -display sdl,gl=on -no-frame \
#	-full-screen  \
#	-drive file=fat:rw:~/VMs/Misc \
#	-cdrom ~/VMs/Discs/Win98SE.iso \
#	-net nic,model=rtl8139 -net user \

qemu-system-x86_64 \
	-boot menu=off \
	-hda ~/VMs/Disks/Win3.qcow2 \
	-m 32 -M isapc \
	-vga cirrus -soundhw sb16 -k en-gb -full-screen \
	-monitor stdio
