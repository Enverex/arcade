#!/bin/python2

from blinkstick import blinkstick
import time

ledCount = 32

def ledSleep():
	time.sleep(0.04)

class Main(blinkstick.BlinkStickPro):
	def run(self):
		self.send_data_all()
		self.off()

		for x in range(ledCount):
 			self.bstick.set_color(0, x, 255, 0, 0)
			ledSleep()

		for x in range(ledCount):
			self.bstick.set_color(0, x, 0, 255, 0)
			ledSleep()

		for x in range(ledCount):
			self.bstick.set_color(0, x, 0, 0, 255)
			ledSleep()

		for x in range(ledCount):
			self.bstick.set_color(0, x, 0, 255, 255)
			ledSleep()

		for x in range(ledCount):
			self.bstick.set_color(0, x, 255, 0, 255)
			ledSleep()

		for x in range(ledCount):
			self.bstick.set_color(0, x, 255, 255, 0)
			ledSleep()

		for x in range(ledCount):
			self.bstick.set_color(0, x, 255, 255, 255)
			ledSleep()

		for x in range(ledCount):
			self.bstick.set_color(0, x, 0, 0, 0)
			ledSleep()

		return


main = Main(r_led_count=ledCount)
if main.connect():
	main.run()
