#!/bin/python2

from __future__ import division
from blinkstick import blinkstick

import math
import sys

## Name of the requested system
sysName = sys.argv[1]

## We have 32 LEDs available but some overlap so limit for now
totalLights = 32
useLights = 27

def colorLoop(self, colArr):
	## Blank all lights first
	for clearLoop in range(totalLights):
		self.bstick.set_color(0, clearLoop, 0, 0, 0)

	thisSegment = 0
	segmentLoop = 0
	perSegment = math.ceil(useLights / len(colArr))

	for lightLoop in range(useLights):
		self.bstick.set_color(0, lightLoop, 0, 0, 0, colArr[thisSegment])

		segmentLoop += 1
		if segmentLoop >= perSegment:
			thisSegment += 1
			segmentLoop = 0



class Main(blinkstick.BlinkStickPro):
	def run(self):
		self.send_data_all()
		self.off()

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
		colArr['psp'] = ['DarkGrey', 'Grey', 'Silver', 'Grey', 'DarkGrey']
		colArr['psx'] = ['darkred', 'darkblue', 'teal', 'gold']
		colArr['sega32x'] = ['darkgoldenrod', 'darkblue', 'darkblue', 'darkred', 'darkgoldenrod']
		colArr['snes'] = ['Purple', 'DarkGreen', 'DarkBlue', 'GoldenRod', 'DarkRed', 'DarkGrey']
		colArr['turbografx16'] = ['orangered', 'yellow', 'orangered', 'orangered']
		colArr['turbografxcd'] = ['orangered', 'yellow', 'orangered', 'grey']
		colArr['vectrex'] = ['LightBlue']
		colArr['wii'] = ['DarkGrey']
		colArr['wonderswan'] = ['DarkBlue']
		colArr['wonderswancolor'] = ['dodgerblue']

		colorLoop(self, colArr[sysName])


main = Main(r_led_count=32)
if main.connect():
	main.run()
