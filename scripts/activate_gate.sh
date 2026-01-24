#!/bin/bash
# Gate activation script
# Called by crontab or manually
# Usage: activate_gate.sh [gpio_pin]
# Default GPIO pin is 9

GPIO_PIN=${1:-9}

/usr/bin/gpio mode $GPIO_PIN out
/usr/bin/gpio write $GPIO_PIN 1
sleep 1
/usr/bin/gpio write $GPIO_PIN 0
