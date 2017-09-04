#!/bin/python2

from blinkstick import blinkstick

stk = blinkstick.find_first()

cols = [255,0,0,0,255,0,0,0,255,255,255,255,255,255,0,0,255,255,255,0,255]
stk.set_led_data(0,cols)
