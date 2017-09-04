#!/bin/python2

import time
import math
import colorsys
from random import randint

from blinkstick import blinkstick

class Main(blinkstick.BlinkStickPro):
    def run(self):
        self.send_data_all()

        try:
            while True:
		thisLed = randint(0, 31)
		red = randint(0, 255)
		green = randint(0, 255)
		blue = randint(0, 255)
		self.bstick.set_color(0, thisLed, red, green, blue)
                time.sleep(0.02)

        except KeyboardInterrupt:
            self.off()
            return

main = Main(max_rgb_value=128)
if main.connect():
    main.run()
else:
    print "No BlinkSticks found"
