# Gear maintenance

Keep track of your gear and stay on top of maintenance tasks with our **Gear Maintenance** feature. It allows you to:

- Automatically track gear usage based your Strava activities
- Monitor usage-based or time-based maintenance intervals.
- Visualize components and tasks with custom images.
- Organize everything via a simple YAML configuration.

---

## How It Works

* Every time a Strava activity is synced, the system checks if the activity involves any of your tracked gear.
* It then calculates the usage of each attached component and updates the progress automatically.
* If the Strava activity title contains one of the configured hashtags, the app will reset the maintenance task and counters from the next activity onwards and will start re-tracking from 0 again.

[Gear maintenance](https://www.youtube.com/embed/mYFmIFgUIYU ':include :type=iframe width=100% height=400px title="Statistics for Strava" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen')

## Setup

* Make sure you referenced the directory where you want to manage your config as a volume

```yaml
services:
  app:
    image: robiningelbrecht/strava-statistics:latest
    volumes:
      - ./config:/var/www/config/app
      # ...
      - ./storage/gear-maintenance:/var/www/storage/gear-maintenance
```

* Create a new file `gear-maintenance.yaml` in `./config`

## Example

```yml
# Set to true to enable the gear maintenance feature
enabled: false
# Prefix for the hashtags used in the Strava activity title
hashtagPrefix: 'sfs'
# If set to "nextActivityOnwards", adding a maintenance hashtag to an activity title will reset the counters from the next activity onwards.
# If set to "currentActivityOnwards", adding a maintenance hashtag to an activity title will reset the counters from the tagged activity onwards.
# If you are unsure, set this to "nextActivityOnwards" as this is the most common use case.
countersResetMode: nextActivityOnwards
# Set to true to ignore retired gear
ignoreRetiredGear: false
components:
  # Tag to be added to the Strava activity title.
  # Will be combined with the hashtag-prefix and must be unique across all components.
  # Example: #sfs-chain
  - tag: 'chain'
    # Label for the component
    label: 'Some cool chain'
    # Optional reference to an image. Will be used in the UI.
    # The image must be in the same directory as this config file.
    imgSrc: 'chain.png'
    # List of gear ids this component is attached to
    # See: https://statistics-for-strava-docs.robiningelbrecht.be/#/configuration/gear-maintenance?id=strava-gear-ids to obtain this ID
    attachedTo:
      - 'gxxxxxxxx' # May not always start with g 
      - 'gxxxxxxxx'
    # Optional, will be displayed in the UI along your component
    purchasePrice:
      amountInCents: 123456
      currency: 'EUR'
    # A list of maintenance tasks for this component
    maintenance:
      # Tag to be added to the Strava activity title.
      # Will be combined with the hashtag-prefix and the component tag.
      # Must be unique across all tasks in the component.
      # Example: #sfs-chain-lubed
      - tag: lubed
        # Label for the maintenance task
        label: Lube
        # Interval for the maintenance task
        interval:
          value: 500
          # Possible values:
          # - km (every x km used),
          # - mi (every x miles used),
          # - hours (every x hours used),
          # - days (every x days),
          unit: km
# If you don't want to reference images, set gears to an empty array: `gears: []`       
gears:
  # Optional reference to an image. Will be used in the UI.
  # The image must be located in the `storage/gear-maintenance` volume.
  - gearId: 'gxxxxxxxx'
    imgSrc: 'gear1.png'
```

> [!IMPORTANT]
> **Important** After each change to these values, you need to run the <i>app:strava:import-data</i> command again for the changes to take effect


## Components

Each component represents a part of your gear that you want to track maintenance for, such as a chain, cassette, or brake pads.

```yaml
- tag: 'chain'                
  label: 'Some cool chain'     
  imgSrc: 'chain.png'         
  attachedTo:
    - 'g12337767'             
  maintenance:
    - tag: lubed             
      label: Lube          
      interval:
        value: 500
        unit: km       
```

Adding the tag `#sfs-chain-lubed` to your Strava activity title will reset the maintenance task and counters from the next activity onwards.

## Gears

Define your Strava gear and associate images to display in the UI.

```yaml
gears:
  - gearId: 'g12337767'
    imgSrc: 'gear1.png'
```

Make sure the gearId matches your Strava gear, and the image is located in the `gear-maintenance`volume.

## Best Practices

* Keep tag values short and unique across all components and tasks.
* Be consistent with your hashtags
* Group components logically under relevant gear items.
* Choose intuitive labels and meaningful intervals to make maintenance easy.

## Strava gear IDs

To attach components to specific gear in your config, you’ll need the gear ID from Strava. You can find these IDs by clicking the question mark icon in the top-right corner:

![image](https://github.com/user-attachments/assets/4e7b8833-6d1d-4bdc-aae4-14ee7c4757a5)
