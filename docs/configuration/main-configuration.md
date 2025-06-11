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

If you do not know what FTP is, or you don't need it, leave this value empty:

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