# Dashboard widgets

The dashboard consists of customizable widgets. Each widget can be individually enabled or disabled, 
assigned a specific width, and configured if it supports custom options.
Below is an example dashboard configuration — this is also the default setup, which you can use as a starting point.

```yaml
appearance:
  dashboard:
    # The width is a percentage of the available space, so 33 means the widget will take up one third of the available space.
    # The allowed values for width are 33, 50, 66, and 100.
    # The order of the widgets in the list determines their order on the dashboard.
    layout:
      - { 'widget': 'mostRecentActivities', 'width': 66, 'enabled': true, 'config': { 'numberOfActivitiesToDisplay': 5 } }
      - { 'widget': 'introText', 'width': 33, 'enabled': true }
      - { 'widget': 'weeklyStats', 'width': 100, 'enabled': true }
      - { 'widget': 'peakPowerOutputs', 'width': 50, 'enabled': true }
      - { 'widget': 'heartRateZones', 'width': 50, 'enabled': true }
      - { 'widget': 'activityGrid', 'width': 100, 'enabled': true }
      - { 'widget': 'monthlyStats', 'width': 100, 'enabled': true, 'config': { enableLastXYearsByDefault: 10 } }
      - { 'widget': 'trainingLoad', 'width': 100, 'enabled': true }
      - { 'widget': 'weekdayStats', 'width': 50, 'enabled': true }
      - { 'widget': 'dayTimeStats', 'width': 50, 'enabled': true }
      - { 'widget': 'distanceBreakdown', 'width': 100, 'enabled': true }
      - { 'widget': 'yearlyStats', 'width': 100, 'enabled': true }
      - { 'widget': 'zwiftStats', 'width': 50, 'enabled': true }
      - { 'widget': 'gearStats', 'width': 50, 'enabled': true, 'config': { 'includeRetiredGear': true } }
      - { 'widget': 'eddington', 'width': 50, 'enabled': true }
      - { 'widget': 'challengeConsistency', 'width': 50, 'enabled': true, 'config': { 'consistencyChallenges': [] } }
      - { 'widget': 'mostRecentChallengesCompleted', 'width': 50, 'enabled': true, 'config': { 'numberOfChallengesToDisplay': 5 } }
      - { 'widget': 'ftpHistory', 'width': 50, 'enabled': true }
```

<div class="alert info">
    🔥 PRO TIP: You can add the same widget multiple times, each with its own configuration.
</div>

## mostRecentActivities

This widget displays your most recent activities, providing a quick overview of your latest workouts.

* __numberOfActivitiesToDisplay__: the number of activities to display.

```yml
{ 'widget': 'mostRecentActivities', 'width': 66, 'enabled': true, 'config': { 'numberOfActivitiesToDisplay': 5 } }
```

![mostRecentActivities widget](../assets/images/dashboard-widgets/most-recent-activities.png)

## introText

This widget allows you to display a custom introduction text on your dashboard.
It contains a summary of your workout history.

```yml
{ 'widget': 'introText', 'width': 33, 'enabled': true }
```

![introText widget](../assets/images/dashboard-widgets/intro-text.png)

## weeklyStats

This widget provides a summary of your weekly statistics per sport type, including total distance and moving time

```yml
{ 'widget': 'weeklyStats', 'width': 100, 'enabled': true }
```

![weeklyStats widget](../assets/images/dashboard-widgets/weekly-stats.png)

## peakPowerOutputs

This widget displays your peak power outputs, allowing you to track your performance over time.

```yml
{ 'widget': 'peakPowerOutputs', 'width': 50, 'enabled': true }
```

![peakPowerOutputs widget](../assets/images/dashboard-widgets/peak-power-outputs.png)

## heartRateZones

This widget shows your heart rate zones, helping you understand your training intensity.

```yml
{ 'widget': 'heartRateZones', 'width': 50, 'enabled': true }
```

![heartRateZones widget](../assets/images/dashboard-widgets/heart-rate-zones.png)

## activityGrid

This widget provides an overview your activities in a GitHub style graph.

```yml
{ 'widget': 'activityGrid', 'width': 100, 'enabled': true }
```

![activityGrid widget](../assets/images/dashboard-widgets/activity-intensity.png)$

## monthlyStats

This widget displays your monthly statistics and lets you compare your performance with the same months in previous years.

* __enableLastXYearsByDefault__: enable the last X years of data dy default. Earlier years will be disabled but can be toggled.

```yml
{ 'widget': 'monthlyStats', 'width': 100, 'enabled': true, 'config': { enableLastXYearsByDefault: 10 } }
```

![monthlyStats widget](../assets/images/dashboard-widgets/monthly-stats.png)

<div class="alert info">
You can add this widget multiple times each with a different configuration.
</div>

## trainingLoad

This widget displays your training load, helping you monitor your training stress and recovery.

```yml
{ 'widget': 'trainingLoad', 'width': 100, 'enabled': true }
```

![trainingLoad widget](../assets/images/dashboard-widgets/training-load.png)

## weekdayStats

This widget provides a breakdown of your activities by weekday, allowing you to see your training patterns throughout the week.

```yml
{ 'widget': 'weekdayStats', 'width': 50, 'enabled': true }
```

![weekdayStats widget](../assets/images/dashboard-widgets/weekday-stats.png)

## dayTimeStats

This widget shows a breakdown of your activities by time of day, helping you understand when you are most active.

```yml
{ 'widget': 'dayTimeStats', 'width': 50, 'enabled': true }
```

![dayTimeStats widget](../assets/images/dashboard-widgets/day-time-stats.png)

## distanceBreakdown

This widget provides a breakdown of your activities by distance and activity type, allowing you to see how your training distances vary.

```yml
{ 'widget': 'distanceBreakdown', 'width': 100, 'enabled': true }
```

![distanceBreakdown widget](../assets/images/dashboard-widgets/distance-breakdown.png)

## yearlyStats

This widget shows your yearly stats per activity type, allowing you to track your long-term training progress.

* __enableLastXYearsByDefault__: enable the last X years of data dy default. Earlier years will be disabled but can be toggled.

```yml
{ 'widget': 'yearlyStats', 'width': 100, 'enabled': true, 'config': { enableLastXYearsByDefault: 10 } }
```

![yearlyStats widget](../assets/images/dashboard-widgets/yearly-stats.png)

## zwiftStats

This widget displays detailed stats for your Zwift activities.

```yml
{ 'widget': 'zwiftStats', 'width': 50, 'enabled': true }
```

![zwiftStats widget](../assets/images/dashboard-widgets/zwift-stats.png)

## gearStats

This widget displays your hours spent per gear.

* __includeRetiredGear__: flag indicating if the widget needs to include retired gear.

```yml
{ 'widget': 'gearStats', 'width': 50, 'enabled': true, 'config': { 'includeRetiredGear': true } }
```

![gearStats widget](../assets/images/dashboard-widgets/gear-stats.png)

## eddington

This widget displays your Eddington number(s). To determine which Eddington number(s) to display,
you can configure this in the Eddington settings using `showInDashboardWidget: true|false`.

```yml
{ 'widget': 'eddington', 'width': 50, 'enabled': true }
```

![eddington widget](../assets/images/dashboard-widgets/eddington.png)

## challengeConsistency

This widget displays your consistency in completing challenges, helping you stay motivated and track your progress.
The app uses sensible defaults for the challenges. Leave this setting unchanged to use them.

* __challenges__: An array of challenges.

```yml
{ 'widget': 'challenges', 'width': 50, 'enabled': true, config: { 'consistencyChallenges': [] } }
```

Each configured challenge should contain the following properties:

```yaml
# The label to be used for this challenge
label: 'Ride a total of 200km'
# Enable or disable the challenge. When disabled, it will no longer appear on the dashboard.
# Alternatively, you can remove the entire entry to exclude it completely.
enabled: true
# The challenge type.
# Allowed values: ["distance", "distanceInOneActivity", "elevation", "elevationInOneActivity", "movingTime", "numberOfActivities", "calories"]
type: 'distance'
# The unit to use for measuring this challenge. This setting does not apply to types "numberOfActivities" and "calories"
# Allowed values: ["km", "m", "mi", "ft", "hour", "minute"]
unit: 'km'
# The goal of the challenge.
goal: 200
# The sport types to include in this challenge.
# For a complete list of supported sport types, visit: https://statistics-for-strava-docs.robiningelbrecht.be/#/configuration/main-configuration?id=supported-sport-types
sportTypesToInclude: ['Ride', 'MountainBikeRide', 'GravelRide', 'VirtualRide']
```

### The defaults

```yaml
config: {
  consistencyChallenges: [
    {
      label: 'Ride a total of 200km',
      enabled: true,
      type: 'distance',
      unit: 'km',
      goal: 200,
      sportTypesToInclude: [ 'Ride', 'MountainBikeRide', 'GravelRide', 'VirtualRide' ]
    },
    {
      label: 'Ride a total of 600km',
      enabled: true,
      type: 'distance',
      unit: 'km',
      goal: 600,
      sportTypesToInclude: [ 'Ride', 'MountainBikeRide', 'GravelRide', 'VirtualRide' ]
    },
    {
      label: 'Ride a total of 1250km',
      enabled: true,
      type: 'distance',
      unit: 'km',
      goal: 1250,
      sportTypesToInclude: [ 'Ride', 'MountainBikeRide', 'GravelRide', 'VirtualRide' ]
    },
    {
      label: 'Complete a 100km ride',
      enabled: true,
      type: 'distanceInOneActivity',
      unit: 'km',
      goal: 100,
      sportTypesToInclude: ['Ride', 'MountainBikeRide', 'GravelRide', 'VirtualRide']
    },
    {
      label: 'Climb a total of 7500m',
      enabled: true,
      type: 'elevation',
      unit: 'm',
      goal: 7500,
      sportTypesToInclude: ['Ride', 'MountainBikeRide', 'GravelRide', 'VirtualRide']
    },
    {
      label: 'Complete a 5 km run',
      enabled: true,
      type: 'distanceInOneActivity',
      unit: 'km',
      goal: 5,
      sportTypesToInclude: ['Run', 'TrailRun', 'VirtualRun']
    },
    {
      label: 'Complete a 10 km run',
      enabled: true,
      type: 'distanceInOneActivity',
      unit: 'km',
      goal: 10,
      sportTypesToInclude: ['Run', 'TrailRun', 'VirtualRun']
    },
    {
      label: 'Complete a half marathon run',
      enabled: true,
      type: 'distanceInOneActivity',
      unit: 'km',
      goal: 21.1,
      sportTypesToInclude: ['Run', 'TrailRun', 'VirtualRun']
    },
    {
      label: 'Run a total of 100km',
      enabled: true,
      type: 'distance',
      unit: 'km',
      goal: 100,
      sportTypesToInclude: ['Run', 'TrailRun', 'VirtualRun']
    },
    {
      label: 'Climb a total of 2000m',
      enabled: true,
      type: 'elevation',
      unit: 'm',
      goal: 2000,
      sportTypesToInclude: ['Run', 'TrailRun', 'VirtualRun']
    }
  ]
}
```
<div class="alert info">
Challenges are always configured for a monthly basis. 
This will not be made configurable, as allowing other intervals could unintentionally replicate features restricted to Strava’s paid tier.
</div>

![challengeConsistency widget](../assets/images/dashboard-widgets/challenge-consistency.png)

## mostRecentChallengesCompleted

This widget displays your most recent challenges.

* __numberOfChallengesToDisplay__: the number of challenges to display.

```yml
{ 'widget': 'mostRecentChallengesCompleted', 'width': 50, 'enabled': true, 'config': { 'numberOfChallengesToDisplay': 5 } }
```

![mostRecentChallengesCompleted widget](../assets/images/dashboard-widgets/most-recent-challenges.png)

## ftpHistory

This widget shows your Functional Threshold Power (FTP) history, allowing you to track your cycling performance over time.

```yml
{ 'widget': 'ftpHistory', 'width': 50, 'enabled': true }
```

![ftpHistory widget](../assets/images/dashboard-widgets/ftp-history.png)
