## Passthrough
#-device vfio-pci,host=01:00.0,x-vga=on -vga none \
## Spice
#-display none -vga qxl -device virtio-serial-pci -device virtserialport,chardev=spicechannel0,name=com.redhat.spice.0 -chardev spicevmc,id=spicechannel1,name=vdagent -spice unix,addr=/run/user/1000/qemu.sock,disable-ticketing -chardev spicevmc,id=spicechannel0,name=vdagent -daemonize \
#	-vga virtio -full-screen -display sdl,gl=on \
#	-net nic,model=rtl8139 -net user \
#sleep 1; spicy -f --uri="spice+unix:///run/user/1000/qemu.sock"
#	-vga vmware -full-screen -display sdl,gl=on \

qemu-system-x86_64 \
	-boot menu=off \
	-drive file=~/VMs/Disks/Win7.qcow2,if=virtio \
	-m 8G -enable-kvm -M q35,accel=kvm -cpu host,kvm=off -smp 4 \
	-soundhw hda -k en-gb \
	-vga vmware -full-screen -display sdl,gl=on \
	-net nic,model=virtio -net user \
	-usb -device usb-tablet \
	-monitor stdio
