#	-full-screen -display sdl,gl=on \

qemu-system-x86_64 \
	-boot menu=off \
	-drive file=~/VMs/Disks/ReactOS.qcow2 \
	-m 8G -enable-kvm -M pc,accel=kvm -cpu host,kvm=off -smp 4 \
	-soundhw ac97 -k en-gb -vga qxl \
	-vnc :0 \
	-net nic,model=ne2k_pci -net user \
	-usb -device usb-tablet \
	-monitor stdio
