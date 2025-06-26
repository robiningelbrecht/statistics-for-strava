# config.yaml

The main configuration yaml file contains all the settings for you to customize the app and set it up to your liking.

[include](config-yaml-example.md ':include')

<div class="alert important">
After each change to these values, you need to run the <i>app:strava:build-files</i> command again for the changes to take effect
</div>

## Athlete weight history

The `weightHistory` is meant to represent a history or evolution of your body weight. It is needed to be able to calculate your relative power. Consider following example:

```yml
general:
  athlete:
    weightHistory:
      "2024-11-21": 69.2
      "2023-04-03": 74.6
      "2023-01-01": 70.3
```

* For activities registered between `2023-01-01` and `2023-04-02` the weight `70.3` will be used
* For activities registered between `2023-04-03` and `2024-11-20` the weight `74.6` will be used
* For activities registered on or after `2024-11-21` the weight `69.2` will be used

<div class="alert info">
If you don't care about relative power, you can use <strong>"1970-01-01": YOUR_CURRENT_WEIGHT</strong> as a single entry in the `weightHistory` to set a fixed weight for all activities.
</div>

## Athlete FTP history

The `ftpHistory` is meant to represent a history or evolution of your FTP. It is needed to be able to calculate activity intensity. Consider following example:

```yml
general:
  athlete:
    ftpHistory:
      "2024-11-21": 243
      "2023-04-03": 209
      "2023-01-01": 200
```

* For activities registered between `2023-01-01` and `2023-04-02` the FTP value `200` will be used
* For activities registered between `2023-04-03` and `2024-11-20` the FTP value `209` will be used
* For activities registered on or after `2024-11-21` the FTP value `243` will be used

If you do not know what FTP is, or you don't need it, leave this value empty.

## Athlete heart rate zones

If one default heart rate zone doesn’t quite work for you, you can fine-tune your setup by adding date ranges and sport types. 
This lets you create very specific zones for different scenarios.

```yaml
  athlete:
    heartRateZones:
      # Relative or absolute. 
      # Relative will treat the zone numbers as percentages based on your max heart rate, while absolute will treat them as actual heartbeats per minute.
      # This mode will apply to all heart rate zones you define.
      mode: relative
      # The default zones for all activities.
      # If you have specified date ranges, this one will be used when there's no exact match for the activity date.
      default:
        zone1:
          from: 50
          to: 60
        zone2:
          from: 61
          to: 70
        zone3:
          from: 71
          to: 80
        zone4:
          from: 81
          to: 90
        zone5:
          from: 91
          to: null # Infinity and beyond.
      # You can further refine your zones by specifying date ranges.
      # This works the same way as weight and FTP history: https://statistics-for-strava-docs.robiningelbrecht.be/#/configuration/main-configuration?id=athlete-weight-historys
      dateRanges:
        "2025-01-01": ...
        "2024-11-08": ...
      # You can also override your heart rate zones for specific sport types.    
      # A full list of allowed options is available on https://statistics-for-strava-docs.robiningelbrecht.be/#/configuration/main-configuration?id=supported-sport-types      
      sportTypes:
        GravelRide:
          # The default heart rate zones for all GravelRide activities.
          # If you have specified date ranges, this one will be used when there's no exact match for the activity date.
          default: ...
          dateRanges:
            "2025-01-01": ...
            "2024-11-08": ...
```

## Consistency challenges

If the default consistency challenges do not quite work for you, you can reconfigure them.

<div class="alert info">
Challenges are always configured for a monthly basis. 
This will not be made configurable, as allowing other intervals could unintentionally replicate features restricted to Strava’s paid tier.
</div>

```yaml
consistencyChallenges:
  # The label to be used for this challenge
  - label: 'Ride a total of 200km'
    # Enable or disable the challenge. When disabled, it will no longer appear on the dashboard.
    # Alternatively, you can remove the entire entry to exclude it completely.
    enabled: true
    # The challenge type.
    # Allowed values: ["distance", "distanceInOneActivity", "elevation", "elevationInOneActivity", "movingTime", "numberOfActivities"]
    type: 'distance'
    # The unit to use for measuring this challenge. This setting not apply to type "numberOfActivities"
    # Allowed values: ["km", "m", "mi", "ft", "hours", "minutes"]
    unit: 'km'
    # The goal of the challenge.
    goal: 200
    # The sport types to include in this challenge.
    # For a complete list of supported sport types, visit: https://statistics-for-strava-docs.robiningelbrecht.be/#/configuration/main-configuration?id=supported-sport-types
    sportTypesToInclude: ['Ride', 'MountainBikeRide', 'GravelRide', 'VirtualRide']
```

### The defaults

The app uses the following default consistency challenges:

```yaml
consistencyChallenges:
  - label: 'Ride a total of 200km'
    enabled: true
    type: 'distance'
    unit: 'km'
    goal: 200
    sportTypesToInclude: ['Ride', 'MountainBikeRide', 'GravelRide', 'VirtualRide']
  - label: 'Ride a total of 600km'
    enabled: true
    type: 'distance'
    unit: 'km'
    goal: 600
    sportTypesToInclude: ['Ride', 'MountainBikeRide', 'GravelRide', 'VirtualRide']
  - label: 'Ride a total of 1250km'
    enabled: true
    type: 'distance'
    unit: 'km'
    goal: 1250
    sportTypesToInclude: ['Ride', 'MountainBikeRide', 'GravelRide', 'VirtualRide']
  - label: 'Complete a 100km ride'
    enabled: true
    type: 'distanceInOneActivity'
    unit: 'km'
    goal: 100
    sportTypesToInclude: ['Ride', 'MountainBikeRide', 'GravelRide', 'VirtualRide']
  - label: 'Climb a total of 7500m'
    enabled: true
    type: 'elevation'
    unit: 'm'
    goal: 7500
    sportTypesToInclude: ['Ride', 'MountainBikeRide', 'GravelRide', 'VirtualRide']
  - label: 'Complete a 5 km run'
    enabled: true
    type: 'distanceInOneActivity'
    unit: 'km'
    goal: 5
    sportTypesToInclude: ['Run', 'TrailRun', 'VirtualRun']
  - label: 'Complete a 10 km run'
    enabled: true
    type: 'distanceInOneActivity'
    unit: 'km'
    goal: 10
    sportTypesToInclude: ['Run', 'TrailRun', 'VirtualRun']
  - label: 'Complete a half marathon run'
    enabled: true
    type: 'distanceInOneActivity'
    unit: 'km'
    goal: 21.1
    sportTypesToInclude: ['Run', 'TrailRun', 'VirtualRun']     
  - label: 'Run a total of 100km'
    enabled: true
    type: 'distance'
    unit: 'km'
    goal: 100
    sportTypesToInclude: ['Run', 'TrailRun', 'VirtualRun']
  - label: 'Climb a total of 2000m'
    enabled: true
    type: 'elevation'
    unit: 'm'
    goal: 2000
    sportTypesToInclude: ['Run', 'TrailRun', 'VirtualRun']
```

## Supported sport types

This is the list of sport types supported in the `config.yaml` file. Make sure the values are exactly the same, they are case-sensitive.

### Cycling

* Ride
* MountainBikeRide
* GravelRide
* EBikeRide
* EMountainBikeRide
* VirtualRide
* Velomobile

### Running

* Run
* TrailRun
* VirtualRun

### Walking

* Walk
* Hike

### Water sports

* Canoeing
* Kayaking
* Kitesurf
* Rowing
* StandUpPaddling
* Surfing
* Swim
* WindSurf

### Winter sports

* BackcountrySki
* AlpineSki
* NordicSki
* IceSkate
* Snowboard
* Snowshoe

### Skating

* InlineSkate
* RollerSki
* Skateboard

### Other

* Badminton
* Crossfit
* Elliptical
* Golf
* Handcycle
* HighIntensityIntervalTraining
* Pickleball
* Pilates
* Racquetball
* RockClimbing
* VirtualRow
* Sail
* Soccer
* Squash
* StairStepper
* TableTennis
* Tennis
* WeightTraining
* Wheelchair
* Workout
* Yoga