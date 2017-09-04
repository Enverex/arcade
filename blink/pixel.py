#!/bin/python2

import time
import math
import colorsys
from random import randint

from blinkstick import blinkstick

class Main(blinkstick.BlinkStickPro):
    def run(self):
        self.send_data_all()

        red = 255
        green = 0
        blue = 150

        x = 0
        try:
            while True:
                self.bstick.set_color(0, x, red, green, blue)
                self.bstick.set_color(0, x+1, red, green, blue)
                self.bstick.set_color(0, x+2, red, green, blue)
                self.bstick.set_color(0, x+3, red, green, blue)
                time.sleep(0.001)
                self.bstick.set_color(0, x, 0, 0, 0)
                self.bstick.set_color(0, x+1, 0, 0, 0)
                self.bstick.set_color(0, x+2, 0, 0, 0)
                self.bstick.set_color(0, x+3, 0, 0, 0)
                time.sleep(0.001)

                x += 1
                if x > self.r_led_count:
             		red = randint(150, 255)
              		green = randint(0, 50)
              		blue = randint(50, 255)
			x = 0



        except KeyboardInterrupt:
            self.off()
            return

# Change the number of LEDs for r_led_count
main = Main(r_led_count=27, max_rgb_value=255)
if main.connect():
    main.run()
