#	  -netdev tap,id=net0,ifname=tap0,script=no,downscript=no -device e1000-82545em,netdev=net0,id=net0,mac=52:54:00:c9:18:27 \
#	  -drive if=pflash,format=raw,readonly,file=/home/arcade/VMs/OSX-KVM/OVMF_CODE.fd \
#	  -cpu Penryn,kvm=off,vendor=GenuineIntel,+invtsc,vmware-cpuid-freq=on,+aes,+xsave,+avx,+xsaveopt,avx2,+smep \
#	  -drive if=pflash,format=raw,readonly,file=/usr/share/ovmf-macboot/OVMF.fd \
#	  -drive if=pflash,format=raw,readonly,file=/usr/share/ovmf/x64/OVMF_CODE.fd \
#	  -drive if=pflash,format=raw,readonly,file=/usr/share/ovmf/x64/OVMF_VARS.fd \
#	-device ide-drive,bus=ahci.1,drive=MacDVD \
#	-drive id=MacDVD,if=none,snapshot=on,media=cdrom,file='/home/arcade/VMs/Discs/HighSierra-10.13.5.iso' \
#	-device qxl-vga,id=video0,ram_size=67108864,vram_size=67108864,vgamem_mb=64 \
#	-cpu Penryn,kvm=off,vendor=GenuineIntel,+invtsc,vmware-cpuid-freq=on,+aes,+xsave,+avx,+xsaveopt,avx2,+smep \
#	-cpu IvyBridge,vendor=GeniuneIntel,kvm=on,+invtsc,vmware-cpuid-freq=on,-tsc-deadline \
#	-full-screen -display sdl,gl=on \

qemu-system-x86_64 -enable-kvm -m 8192 \
	-cpu Penryn,kvm=off,vendor=GenuineIntel,vmware-cpuid-freq=on,+invtsc,+aes,+xsave,+xsaveopt,+avx,+avx2,+smep,+pcid,+ssse3,+sse4.2,+popcnt \
	-machine q35,accel=kvm -vga virtio \
	-full-screen -display sdl,gl=on \
	-smp cpus=4,sockets=1,cores=2,threads=2 \
	-usb -device usb-kbd -device usb-tablet \
	-device isa-applesmc,osk="ourhardworkbythesewordsguardedpleasedontsteal(c)AppleComputerInc" \
	-smbios type=2 \
	-drive if=pflash,format=raw,readonly,file=/home/arcade/VMs/OSX-KVM/OVMF_CODE.fd \
	-drive if=pflash,format=raw,file=/home/arcade/VMs/Misc/OVMF_VARS.fd \
	-device ich9-intel-hda -device hda-duplex -device ich9-ahci,id=ahci \
	-device ide-drive,bus=ahci.1,drive=Clover \
	-drive id=Clover,if=none,snapshot=on,format=qcow2,file=/home/arcade/VMs/OSX-KVM/HighSierra/Clover.qcow2 \
	-device ide-drive,bus=ahci.0,drive=MacHDD \
	-drive id=MacHDD,if=none,file=/home/arcade/VMs/Disks/OSX.qcow2 \
	-net nic,model=e1000-82545em -net user \
	-monitor stdio

