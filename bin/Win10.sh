## Passthrough
#-device vfio-pci,host=01:00.0,x-vga=on -vga none \
## Spice
#-display none -vga qxl -device virtio-serial-pci -device virtserialport,chardev=spicechannel0,name=com.redhat.spice.0 -chardev spicevmc,id=spicechannel1,name=vdagent -spice unix,addr=/run/user/1000/qemu.sock,disable-ticketing -chardev spicevmc,id=spicechannel0,name=vdagent -daemonize \
#	-vga virtio -full-screen -display sdl,gl=on \
#	-net nic,model=virtio -net user \
#	-display none -vnc :0 \
#	-drive file=~/VMs/Discs/Win10_1803_EnglishInternational_x64.iso,index=0,media=cdrom \
#	-drive file=~/VMs/Discs/virtio-win-0.1.141.iso,index=1,media=cdrom \
#	-full-screen -display sdl,gl=on -vnc :0 \

qemu-system-x86_64 \
	-boot menu=off \
	-bios /usr/share/ovmf/x64/OVMF_CODE.fd \
	-drive file=~/VMs/Disks/Win10.qcow2,if=virtio,media=disk \
	-cdrom ~/VMs/Discs/virtio-win-0.1.149.iso \
	-m 8192M -enable-kvm -M q35,accel=kvm -cpu host,kvm=off -smp 4 \
	-vga qxl -soundhw hda -k en-gb \
	-net nic,model=virtio -net user \
	-vnc :0 \
	-usb -device usb-tablet \
	-monitor stdio
