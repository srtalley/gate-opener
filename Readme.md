# Gate Opener

This application activates the MightyMule gate control on the RaspberryPi computer located in the control box.

## Features

- Web-based gate activation button
- Scheduled automatic gate opening (configurable via web UI)
- Blue Iris camera feeds via iframe
- Mobile-friendly PWA with home screen support
- Shows deliveries people ticked on the house control panel; while any are
  expected, Activate takes a second tap

## File Structure

```
/var/www/html/  (or wherever Apache serves from)
├── index.php           # Main web interface
├── crontab-api.php     # Schedule management API
├── deliveries.php      # Today's expected deliveries, read from the house panel
├── css/
│   └── style.css
├── js/
│   ├── jquery-1.10.2.min.js
│   └── script.js
├── scripts/
│   └── activate_gate.sh  # Called by cron for scheduled activation
├── manifest.json
└── touch-icon-*.png    # PWA icons
```

## Initial Setup (New Pi)

### 1. Install Apache and PHP

```bash
sudo apt update
sudo apt install apache2 php libapache2-mod-php
```

### 2. Install WiringPi for GPIO access

```bash
# Check if gpio command exists
which gpio

# If not installed, install wiringpi
sudo apt install wiringpi

# On newer Pi OS, you may need to install from source:
# https://github.com/WiringPi/WiringPi
```

### 3. Deploy the application files

Copy all application files to the web root (typically `/var/www/html/`).

### 4. Set script permissions

```bash
chmod +x /var/www/html/scripts/activate_gate.sh
```

### 5. Set up scheduled activation

The schedule feature uses `/etc/cron.d/` with sudo tee for writing (since cron.d files must be owned by root).

```bash
# Create the cron file
sudo touch /etc/cron.d/gate-opener
sudo chmod 644 /etc/cron.d/gate-opener

# Give www-data permission to write via sudo tee
sudo visudo
```

Add this line to sudoers:
```
www-data ALL=(ALL) NOPASSWD: /usr/bin/tee /etc/cron.d/gate-opener
```

### 6. Test the setup

```bash
# Test GPIO manually
sudo /var/www/html/scripts/activate_gate.sh

# Test the API
curl http://localhost/crontab-api.php

# Test saving a schedule
curl -X POST http://localhost/crontab-api.php \
  -H "Content-Type: application/json" \
  -d '{"enabled":true,"hour":7,"minute":30}'

# Verify cron file was written
cat /etc/cron.d/gate-opener

# Watch cron logs
tail -f /var/log/syslog | grep -i cron
```

## Configuration

### GPIO Pin

The gate uses GPIO pin 9. To change this, edit:
- `index.php` - line 10: `"gpioNumber" => 9`
- `scripts/activate_gate.sh` - line 7: `GPIO_PIN=${1:-9}`

### Camera Feeds

Camera iframes are configured in `index.php`. Update the Blue Iris server URL and camera names as needed:
```php
<div class="ui3"><iframe src="http://sol.home/ui3.htm?maximize=1&cam=GarageSouth"></iframe></div>
```

### Schedule Script Path

The crontab API uses `__DIR__` to automatically determine the script path. If you move files around, the path will adjust automatically as long as the relative structure is maintained.

## API Reference

### GET /crontab-api.php

Returns current schedule and metadata.

Response:
```json
{
  "success": true,
  "schedule": {
    "enabled": true,
    "hour": 7,
    "minute": 30,
    "raw": "30 7 * * * root /var/www/html/scripts/activate_gate.sh"
  },
  "meta": {
    "fileExists": true,
    "fileReadable": true,
    "cronFile": "/etc/cron.d/gate-opener",
    "scriptPath": "/var/www/html/scripts/activate_gate.sh",
    "webUser": "www-data"
  }
}
```

### POST /crontab-api.php

Updates the schedule.

Request:
```json
{
  "enabled": true,
  "hour": 7,
  "minute": 30
}
```

## Troubleshooting

### Schedule not running

1. Check cron file ownership and permissions:
   ```bash
   ls -la /etc/cron.d/gate-opener
   # Should show: -rw-r--r-- 1 root root
   ```

2. Check syslog for cron errors:
   ```bash
   grep -i cron /var/log/syslog | tail -20
   ```

3. Common errors:
   - `WRONG FILE OWNER` - File must be owned by root
   - `INSECURE MODE` - File must not be group/world writable (644 not 664)

### API returns "Failed to write cron file"

Check sudo permissions:
```bash
sudo -u www-data sudo /usr/bin/tee /etc/cron.d/gate-opener <<< "test"
```

If prompted for password, the visudo entry is missing or incorrect.

### Gate doesn't activate

1. Test the script directly:
   ```bash
   sudo /var/www/html/scripts/activate_gate.sh
   ```

2. Check GPIO path:
   ```bash
   which gpio
   # Update scripts/activate_gate.sh if path differs from /usr/bin/gpio
   ```

## Versions

### v2026.11.13
* Shows "Delivery expected today" when anyone has ticked a delivery on the house
  control panel (https://controlpanel.relay.dustysun.com). While one is
  expected, the first tap on Activate only warns, and a second tap within 4 s
  activates the gate.
* `deliveries.php` asks the panel server side (`http://10.0.1.84:8095`), sending
  this Pi's control token. The panel accepts that token for this read only.
  If the panel is down, nothing is shown and the button works as before.

### v2026.01.23
* Added web-based schedule management for automatic gate opening
* Schedule UI with enable/disable toggle and time picker
* Uses /etc/cron.d/ with sudo tee for cron management
* Added activate_gate.sh script for cron execution

### v2023.03.20 
* Added two new camera views.

### v2021.12.05 
* Disabled the solar monitoring section.
* Changed the main camera view.

### v2021.06.12
* Changed the GPIO resting mode from 1 to 0.

### v2020.12.28
* Revised the look and added the Grafana solar dashboard stats.
* Deleted the old viewer code and instead just load UI3 via an iframe.

### v2020.12.03 
* Included v1.2.4 of the viewer script that allows setting a frame width to further reduce bandwidth usage.
* Set the defaults to quality 20, refresh rate 300 and width 600px.

### v2020.11.29
* Updated the appearance of the button.
* Added the updated viewer folder.
* Added a refresh button.
* Added a manifest.json.

### v2020.11.27 - Initial Version
* Based off the garage door opener app.