from __future__ import division
from blinkstick import blinkstick
from random import randint

import math
import sys
import time
import colorsys

## Name of the requested system
lightMode = sys.argv[1]

## We have 32 LEDs available but some overlap so limit for now
totalLights = 32
useLights = 27


def getMirror(val, total):
	half = total / 2
	return int((val + half) % total)

def colorLoop(self, colArr):
	thisSegment = 0
	segmentLoop = 0
	perSegment = math.ceil(useLights / len(colArr))

	for lightLoop in range(useLights):
		self.bstick.set_color(0, lightLoop, 0, 0, 0, colArr[thisSegment])

		segmentLoop += 1
		if segmentLoop >= perSegment:
			thisSegment += 1
			segmentLoop = 0


def ledSleep():
	time.sleep(0.04)



class Main(blinkstick.BlinkStickPro):
	def run(self):
		self.off()

		if lightMode == 'emu':
			sysName = sys.argv[2]

			colArr = {}
			colArr['3do'] = ['Black', 'Black', 'Goldenrod', 'Goldenrod', 'Black', 'Black']
			colArr['3ds'] = ['DarkRed', 'DarkGrey', 'White']
			colArr['amiga'] = ['DarkRed', 'white', 'DarkRed', 'white']
			colArr['amigacd32'] = ['darkred', 'green', 'yellow', 'red', 'blue', 'white']
			colArr['atari2600'] = ['SaddleBrown', 'darkgrey']
			colArr['atari7800'] = ['OrangeRed', 'DarkYellow', 'DarkGreen', 'Blue', 'DarkBlue']
			colArr['atarijaguar'] = ['DarkRed']
			colArr['atarilynx'] = ['goldenrod', 'goldenrod', 'DarkRed']
			colArr['c64'] = ['darkblue', 'darkblue', 'darkred']
			colArr['colecovision'] = ['indianred', 'orange', 'yellow', 'green', 'turquoise']
			colArr['daphne'] = ['indianred', 'orange', 'indianred', 'orange']
			colArr['dosbox'] = ['DarkGrey', 'DarkRed', 'Purple', 'Yellow']
			colArr['dreamcast'] = ['DarkRed', 'Blue', 'OrangeRed']
			colArr['gamegear'] = ['Red', 'Teal', 'Blue']
			colArr['gb'] = ['Navy', 'DarkGrey', 'Purple']
			colArr['gba'] = ['Indigo', 'DarkBlue', 'Blue', 'Teal', 'Purple', 'Indigo']
			colArr['gbc'] = ['Red', 'Indigo', 'Lime', 'GoldenRod', 'Teal']
			colArr['gc'] = ['Purple', 'Grey', 'Grey', 'Purple']
			colArr['linux'] = ['Yellow']
			colArr['mame'] = ['DarkRed', 'DarkBlue', 'DarkGreen']
			colArr['mastersystem'] = ['DarkBlue', 'DarkRed']
			colArr['megadrive'] = ['Blue', 'SkyBlue', 'White', 'DarkBlue', 'Brown', 'Silver']
			colArr['msx'] = ['DarkBlue', 'Black', 'White', 'DarkBlue']
			colArr['msx2'] = ['Blue', 'Black', 'White', 'Blue']
			colArr['n64'] = ['DarkGreen', 'DarkRed', 'GoldenRod', 'DarkBlue']
			colArr['nds'] = ['DarkGrey', 'White', 'SlateGrey']
			colArr['nes'] = ['Grey', 'DarkRed', 'Grey', 'LightGrey']
			colArr['ngpc'] = ['DarkRed', 'DarkRed', 'DarkGreen', 'Blue']
			colArr['win'] = ['orangered', 'green', 'blue', 'darkgoldenrod']
			colArr['pcfx'] = ['GoldenRod', 'DarkGrey', 'White', 'DarkBlue', 'DarkRed']
			colArr['pico8'] = ['Goldenrod', 'Salmon', 'Orange', 'White', 'DarkGreen', 'DarkRed', 'DarkBlue']
			colArr['ps2'] = ['DarkSlateBlue', 'MidnightBlue', 'Navy', 'DarkBlue', 'Blue', 'DodgerBlue', 'DeepSkyBlue']
			colArr['ps3'] = ['Grey']
			colArr['psp'] = ['DarkGrey', 'Grey', 'Silver', 'Grey', 'DarkGrey']
			colArr['psx'] = ['darkred', 'darkblue', 'teal', 'gold']
			colArr['sega32x'] = ['darkgoldenrod', 'darkblue', 'darkblue', 'darkred', 'darkgoldenrod']
			colArr['snes'] = ['Purple', 'DarkGreen', 'DarkBlue', 'GoldenRod', 'DarkRed', 'DarkGrey']
			colArr['turbografx16'] = ['orangered', 'yellow', 'orangered', 'orangered']
			colArr['turbografxcd'] = ['orangered', 'yellow', 'orangered', 'grey']
			colArr['vectrex'] = ['LightBlue']
			colArr['wii'] = ['DarkGrey']
			colArr['wiiu'] = ['DarkGrey', 'DarkGrey', 'Teal']
			colArr['wonderswan'] = ['DarkBlue']
			colArr['wonderswancolor'] = ['dodgerblue']
			self.send_data_all()
			colorLoop(self, colArr[sysName])
			return


		## Do some bright colour light cycles around the case
		elif lightMode == 'wakeup':
			for x in range(totalLights):
				self.bstick.set_color(0, x, 255, 0, 0)
				ledSleep()

			for x in range(totalLights):
				self.bstick.set_color(0, x, 0, 255, 0)
				ledSleep()

			for x in range(totalLights):
				self.bstick.set_color(0, x, 0, 0, 255)
				ledSleep()

			for x in range(totalLights):
				self.bstick.set_color(0, x, 0, 255, 255)
				ledSleep()

			for x in range(totalLights):
				self.bstick.set_color(0, x, 255, 0, 255)
				ledSleep()

			for x in range(totalLights):
				self.bstick.set_color(0, x, 255, 255, 0)
				ledSleep()

			for x in range(totalLights):
				self.bstick.set_color(0, x, 255, 255, 255)
				ledSleep()

			for x in range(totalLights):
				self.bstick.set_color(0, x, 0, 0, 0)
				ledSleep()

			return


		## Cycle around the box, changing to a different A S T E T I C colour each time
		elif lightMode == 'neonchase':
			red = randint(150, 255)
			green = randint(0, 50)
			blue = randint(50, 255)
			x = 0

			while True:
				self.send_data_all()
				self.bstick.set_color(0, x,   red, green, blue)
				self.bstick.set_color(0, (x+1) % self.r_led_count, red, green, blue)
				self.bstick.set_color(0, (x+3) % self.r_led_count, red, green, blue)
				self.bstick.set_color(0, (x+4) % self.r_led_count, red, green, blue)

				self.bstick.set_color(0, getMirror(x, self.r_led_count),   red, green, blue)
				self.bstick.set_color(0, getMirror(x+1, self.r_led_count), red, green, blue)
				self.bstick.set_color(0, getMirror(x+3, self.r_led_count), red, green, blue)
				self.bstick.set_color(0, getMirror(x+4, self.r_led_count), red, green, blue)

				x += 1
				if x > self.r_led_count:
					red = randint(150, 255)
					green = randint(0, 50)
					blue = randint(50, 255)
					x = 0


		## Cycle around the box, a different colour each loop
		elif lightMode == 'colorchase':
			red = randint(0, 255)
			green = randint(0, 255)
			blue = randint(0, 255)
			x = 0

			while True:
				self.send_data_all()
				self.bstick.set_color(0, x,   red, green, blue)
				self.bstick.set_color(0, (x+1) % self.r_led_count, red, green, blue)
				self.bstick.set_color(0, (x+3) % self.r_led_count, red, green, blue)
				self.bstick.set_color(0, (x+4) % self.r_led_count, red, green, blue)

				self.bstick.set_color(0, getMirror(x, self.r_led_count),   red, green, blue)
				self.bstick.set_color(0, getMirror(x+1, self.r_led_count), red, green, blue)
				self.bstick.set_color(0, getMirror(x+3, self.r_led_count), red, green, blue)
				self.bstick.set_color(0, getMirror(x+4, self.r_led_count), red, green, blue)

				x += 1
				if x > self.r_led_count:
					red = randint(0, 255)
					green = randint(0, 255)
					blue = randint(0, 255)
					x = 0


		## Change 6 LEDs at a time to a random colour independently
		elif lightMode == 'random':
			x = 0
			while True:
				red = randint(0, 255)
				green = randint(0, 255)
				blue = randint(0, 255)

				self.bstick.set_color(0, randint(0, totalLights), red, green, blue)
				self.bstick.set_color(0, randint(0, totalLights), red, green, blue)
				self.bstick.set_color(0, randint(0, totalLights), red, green, blue)
				self.bstick.set_color(0, randint(0, totalLights), red, green, blue)
				self.bstick.set_color(0, randint(0, totalLights), red, green, blue)
				self.bstick.set_color(0, randint(0, totalLights), red, green, blue)
				time.sleep(1)
				self.send_data_all()

				x += 1
				if x >= useLights:
					x = 0


		## Randomly change an LED to red or green
		elif lightMode == 'christmas':
			x = 0
			thisChoice = 0
			while True:
				self.send_data_all()
				thisLed = randint(0, 31);
				if thisChoice == 0:
					self.bstick.set_color(0, thisLed, 255, 0, 0)
					thisChoice = 1
				else:
					self.bstick.set_color(0, thisLed, 20, 255, 20)
					thisChoice = 0

				time.sleep(0.05)


		## Fade in orange then back out
		elif lightMode == 'coinop':
			self.bstick.pulse(name='orange', duration=500)


		## Red, Yellow, Green sequence
		elif lightMode == 'flash':
			## Red
			self.bstick.set_led_data(0, [0, 255, 0] * 32)
			time.sleep(0.5)
			## Yellow
			self.bstick.set_led_data(0, [255, 255, 0] * 32)
			time.sleep(0.5)
			## Green
			self.bstick.set_led_data(0, [255, 0, 0] * 32)
			time.sleep(0.5)


		## Flash red twice
		elif lightMode == 'flashred':
			## Red
			self.off()
			time.sleep(0.3)
			self.bstick.set_led_data(0, [0, 255, 0] * 32)
			time.sleep(0.2)
			self.off()
			time.sleep(0.3)
			self.bstick.set_led_data(0, [0, 255, 0] * 32)
			time.sleep(0.2)
			self.off()


		## Flash all LEDs through lots of colours, one every 1/10th of a second for 3 seconds
		elif lightMode == 'cracktro':
			for _ in range(30):
				red = randint(0, 255)
				green = randint(0, 255)
				blue = randint(0, 255)
				self.bstick.set_led_data(0, [red, green, blue] * 32)
				time.sleep(0.1)

			self.off()



main = Main(r_led_count = useLights, max_rgb_value = 255, delay=0.004)
if main.connect():
	main.run()
