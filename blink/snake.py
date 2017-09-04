#!/bin/python2

from random import randint
from blinkstick import blinkstick

import time

useLights = 32

class Main(blinkstick.BlinkStickPro):
    def run(self):
        self.send_data_all()

        x = 0
        try:
            while True:
                red = randint(0, 255)
                green = randint(0, 255)
                blue = randint(0, 255)

                self.bstick.set_color(0, x, red, green, blue)
                time.sleep(0.10)

                x += 1
                if x >= useLights:
                    x = 0

        except KeyboardInterrupt:
            return

main = Main()
if main.connect():
    main.run()
