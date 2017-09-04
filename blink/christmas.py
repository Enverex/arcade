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
	thisChoice = 0
        #sign = 1
        try:
            while True:
		thisLed = randint(0, 31);
		if thisChoice == 0:
	                self.bstick.set_color(0, thisLed, 255, 0, 0)
			thisChoice = 1
		else:
	                self.bstick.set_color(0, thisLed, 20, 255, 20)
			thisChoice = 0


                time.sleep(0.05)

        except KeyboardInterrupt:
            return

main = Main(r_led_count=32, max_rgb_value=128)
if main.connect():
    main.run()
else:
    print "No BlinkSticks found"
