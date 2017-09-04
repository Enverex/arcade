#!/bin/python2

from blinkstick import blinkstick
import time

ledCount = 32

def ledSleep():
	time.sleep(0.04)

class Main(blinkstick.BlinkStickPro):
	def run(self):
		self.send_data_all()

		self.bstick.set_color(0, 25, 80, 80, 255)
		self.bstick.set_color(0, 26, 255, 255, 255)
		self.bstick.set_color(0, 27, 80, 80, 255)


main = Main(r_led_count=ledCount)
if main.connect():
	main.run()
