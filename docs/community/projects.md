# Community projects

Besides the official Statistics for Strava app, several community members have built their own tools and extensions on top of the project.
These projects can add extra functionality, provide alternative ways to manage your setup, or help automate certain workflows.

Below is a list of community-made apps that integrate with or complement Statistics for Strava.

> [!NOTE]
> **Note** These projects are not officially maintained. If you run into issues, please report them in the relevant repository.

## Stats for Strava config tool

> A web app used to edit the stats for Strava configuration file

https://github.com/dschoepel/stats-for-strava-config-tool

## Apple Health to SfS

> Lightweight webserver listening for Apple Health payloads to insert into statistics for strava

https://github.com/steveredden/health-to-sfs

## Home Assistant - Statistics for Strava

> Single-container Home Assistant add-on draft for statistics-for-strava

https://github.com/cgtobi/ha-apps/tree/main/statistics_for_strava

## Use of Home Assistant weight-sensor

This guide is intended for users who:
- have [home-assistant.io](https://www.home-assistant.io) installed
- have a working weight sensor configured in Home Assistant

**Situation**: Home Assistant running as a Docker container with a scale
**Goal**: Automatically provide weight sensor readings to Statistics for Strava

#### 1. Split SfS-config-file

Create a new file called `config-athlete-weight.yaml` in your `/config` directory:

```yaml
general:
  athlete:
    weightHistory:
      "YYYY-MM-DD": 100
```

#### 2. Update Docker Volume Mapping in Home Assistant

Make sure the SfS config directory is mounted inside your Home Assistant container. 
Add the following volume mapping to your `docker-compose.yml`:
```yaml
services:
  homeassistant:
    volumes:
      - /path/to/sfs/config:/sfsconfig
```


#### 3. Add Shell Command to home-assistant

Find your Home Assistant weight sensor (for example from a bluetooth scale, a fitness integration, etc.).
The entity will typically be named something like `sensor.weight_name`.

Add the following snippet to your `configuration.yaml.

This command checks if today's date already exists (to prevent duplicates) 
and then appends the value below `weightHistory`:.

```yaml
shell_command:
  update_sfs_weight: >
    grep -q '"{{ now().strftime("%Y-%m-%d") }}":' /sfsconfig/config-athlete-weight.yaml || sed -i '/weightHistory:/a \      "{{ now().strftime("%Y-%m-%d") }}": {{ states("sensor.weight_name") }}' /sfsconfig/config-athlete-weight.yaml

```

> [!NOTE]
> **Note** Ensure the 6 spaces after \ match the indentation used in your YAML file (see step 1).
> Also replace `sensor.weight_name` with your actual sensor entity.

#### 4. Create an automation in Home Assistant

Create a weekly automation to trigger the update. Example: every Monday at 15:00.

```yaml
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

> [!NOTE]
> **Note** Replace `sensor.weight_name` with your actual sensor entity.
