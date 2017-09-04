from os.path import dirname
from subprocess import call
import time

from adapt.intent import IntentBuilder
from mycroft.skills.core import MycroftSkill
from mycroft.util.log import getLogger

__author__ = 'Enverex'

LOGGER = getLogger(__name__)

class MiscSystemSkills(MycroftSkill):
    def __init__(self):
		super(MiscSystemSkills, self).__init__(name="MiscSystemSkills")

    def initialize(self):
		switchAudioIntent = IntentBuilder("SwitchAudioIntent").require("AudioDevice").build()
		self.register_intent(switchAudioIntent, self.handle_switchAudioIntent)

    def initialize(self):
		restartProgramIntent = IntentBuilder("restartProgramIntent").require("restartCommand").build()
		self.register_intent(restartProgramIntent, self.handle_restartProgram)

    def handle_switchAudioIntent(self, message):
		newAudioDevice = message.data.get('AudioDevice')
		self.speak("Switching audio to " + newAudioDevice)
		sleep(3)
		call('/home/arcade/.bin/switchAudio ' + newAudioDevice, shell=True)

    def handle_restartProgram(self, message):
		restartProgramName = message.data.get('restartCommand')

		if restartProgramName == 'yourself':
			restartCommand = "sudo systemctl restart mycroft"
		elif restartProgramName == 'arcade':
			restartCommand = "killall attractWrap; /home/arcade/.bin/attractWrap"
		elif restartProgramName == 'system':
			restartCommand = "sudo reboot"
		elif restartProgramName == 'chrome':
			restartCommand = "chromium"
		elif restartProgramName == 'quiet':
			restartCommand = "killWindow; killall attractWrap; killall attract; killall chromium"

		if restartCommand:
			self.speak("Executing")
			time.sleep(2)
			call(restartCommand, shell=True)

    def stop(self):
		pass

def create_skill():
    return MiscSystemSkills()
