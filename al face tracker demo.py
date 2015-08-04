# al face tracker demo
# from http://doc.aldebaran.com/1-14/naoqi/trackers/trackers-sample.html
# 7/1/2015

import cv2
import time
from naoqi import ALProxy

IP = 'bobby.local'
PORT = 9559

print "Connecting to", IP, "with port", PORT

motion = ALProxy("ALMotion", IP, PORT)
faceTracker = ALProxy("ALFaceTracker", IP, PORT)
posture = ALProxy("ALRobotPosture", IP, PORT)

# change tracking from just head to whole body movement
faceTracker.setWholeBodyOn(True)

# stiffen body
motion.setStiffnesses("Body", 1.0)

# stand up in balanced stance
posture.goToPosture("StandInit", 0.5)

# Then, start tracker.
faceTracker.startTracker()

wait = 20

print "ALFaceTracker successfully started, now show your face to Nao!"
print "ALFaceTracker will be stopped in", wait, "seconds."

time.sleep(wait)

# Stop tracker, sit down, remove stiffness
faceTracker.stopTracker()
posture.goToPosture("Crouch", 0.2)
motion.setStiffnesses("Body", 0.0)

print "ALFaceTracker stopped."