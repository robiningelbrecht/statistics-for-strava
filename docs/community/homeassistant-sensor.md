# Use of Homeassistant weight-sensor

First of all, this documentation is for users with
- [home-assistant.io](https://www.home-assistant.io)
- weight sensors in homeassistant

**Situation**: Home Assistant running as a Docker container with a scale

**Goal**: To provide sensor readings for SfS


**Implementation and solution**:


**1. Split SfS-config-file**

create new file ```config-athlete-weight.yaml``` in /config

please check the minimum-requirements of the split-file

```
general:
  athlete:
    weightHistory:
      "YYYY-MM-DD": 100
```


**2. Update Docker Volume Mapping in home-assistant**

Ensure the Strava config directory is mounted inside your Home Assistant container. Add this to your home-assistant ```docker-compose.yml```:

```
services:
  homeassistant:
    volumes:
      - /path/to/sfs/config:/sfsconfig
```


**3. Add Shell Command to home-assistant**

Please find your homeassistant weight-sensor, you can use any sensor available, f.e. bluetooth-scale, pulled data from various fitness-plattform integrations,... The sensor is something like ```sensor.weight_name```.

Add the following to your ```configuration.yaml```. This command checks if today's date already exists (grep) to prevent duplicates and then appends the new value (sed) right under the weightHistory: .

```configuration.yaml```
```
shell_command:
  update_sfs_weight: >
    grep -q '"{{ now().strftime("%Y-%m-%d") }}":' /sfsconfig/config-athlete-weight.yaml || sed -i '/weightHistory:/a \      "{{ now().strftime("%Y-%m-%d") }}": {{ states("sensor.weight_name") }}' /sfsconfig/config-athlete-weight.yaml

```
Note: 
- Ensure the 6 spaces after \  match your file's indentation. (See file in 1.)
- Change ```sensor.weight_name```


**4. Automation in home-assistant**

Create a weekly automation to trigger the update.
   for example: weekly on Monday 15:00
```
alias: Strava Update
description: 
triggers:
  - at: "15:00:00"
    trigger: time
conditions:
  - condition: time
    weekday:
      - mon
  - condition: template
    value_template: "{{ is_number(states('sensor.weight_name')) }}"
actions:
  - action: shell_command.update_sfs_weight
mode: single
```
Note: 
- Change ```sensor.weight_name```
