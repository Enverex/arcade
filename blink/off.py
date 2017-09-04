#!/bin/python2

from blinkstick import blinkstick

class Main(blinkstick.BlinkStickPro):
    def run(self):
        self.send_data_all()
        self.off()
        return

main = Main(r_led_count=32)
if main.connect():
    main.run()
