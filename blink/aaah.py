#!/bin/python2

import time
import math
import colorsys
from random import randint

from blinkstick import blinkstick

class Main(blinkstick.BlinkStickPro):
    def run(self):
        self.send_data_all()

        x = 0
        try:
            while True:
                if x == self.r_led_count - 1:
			x = 0

		red = randint(0, 255)
		green = randint(0, 255)
		blue = randint(0, 255)
		self.bstick.set_color(0, x, red, green, blue)
                time.sleep(0.02)
                x += 1

        except KeyboardInterrupt:
            self.off()
            return

main = Main(r_led_count=32, max_rgb_value=128)
if main.connect():
    main.run()
else:
    print "No BlinkSticks found"
