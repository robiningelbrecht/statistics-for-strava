# Custom gear

Statistics for Strava allows you to manage custom gear. This is useful for gear that Strava doesn't allow you to track. For example:

* Skateboards
* Peddle boards
* Snowboards
* Kayaks
* Kites
* ...

---

## How It Works

Custom gear is configured using a YAML file called `custom-gear.yml`. You can define any number of gear items and assign them to activities using hashtags in the Strava activity title (e.g. #sfs-peddle-board).
Custom gears will work just like Strava-imported ones — tracking stats, allowing maintenance setup, and letting you filter them in overviews.

## Setup

* Make sure you referenced the directory where you want to manage your config as a volume

```yaml
services:
  app:
    image: robiningelbrecht/strava-statistics:latest
    volumes:
      - ./config:/var/www/config/app
      # ...
```

* Create a new file `custom-gear.yaml` in `./config`

## Example

```yaml
# Enable or disable custom gear support
enabled: true
# Prefix for the hashtags used in the Strava activity title
hashtagPrefix: sfs
# List of custom gear entries
customGears:
    # Tag to be added to the Strava activity title.
    # Will be combined with the hashtag-prefix and must be unique across all customGears
    # Example: #sfs-peddle-board
  - tag: peddle-board
    # The readable name to display in the UI
    label: Peddle Board
    # If true, marks the gear as retired
    isRetired: false
  - tag: workout-shoes
    label: Fancy workout shoes
    isRetired: true
```

<div class="alert important">
After each change to these values, you need to run the both <i>app:strava:import-data</i> 
and <i>app:strava:build-files</i> commands again for the changes to take effect
</div>

## Tips

* Use unique tag values. Avoid spaces or special characters.
* Keep the hashtagPrefix short and memorable.
* Set isRetired to true for gear you no longer use
