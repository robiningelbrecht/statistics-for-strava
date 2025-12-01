# Full configuration example

The following example shows a configuration file with extended settings. You can use it as a reference when creating your own configuration.
Thanks to [lennon101](https://github.com/lennon101) for providing this example.

```yaml
general:
  appUrl: 'https://<your-domain>/'
  appSubTitle: null
  profilePictureUrl: https://<url-to-your-profile-picture>.jpg
  athlete:
    birthday: '<YYYY-MM-DD>'
    maxHeartRateFormula: 'nes' # Chose nes because I know my max heart rate, and this is the one that gets closest to it when using my age
    heartRateZones:
      mode: absolute. # I prefer absolute mode because I have a good awareness of my zones and the hr values that represent them 
      # The default zones for all activities.
      default:
        zone1:
          from: 50
          to: 139
        zone2:
          from: 140
          to: 158
        zone3:
          from: 159
          to: 166
        zone4:
          from: 167
          to: 179
        zone5:
          from: 180
          to: null # Infinity and beyond.
      sportTypes:
        Run:
          # The default zones for all runs.
          default:
            zone1:
              from: 50
              to: 129
            zone2:
              from: 130
              to: 158
            zone3:
              from: 159
              to: 166
            zone4:
              from: 167
              to: 179
            zone5:
              from: 180
              to: null # Infinity and beyond.
        Ride:
          # The default zones for all rides.
          default:
            zone1:
              from: 50
              to: 99
            zone2:
              from: 100
              to: 135
            zone3:
              from: 136
              to: 149
            zone4:
              from: 150
              to: 159
            zone5:
              from: 160
              to: null # Infinity and beyond.
    weightHistory:
      "2025-09-30": 76
      "2018-01-01": 78
    ftpHistory:
      "2023-04-01": 198
      "2023-05-25": 220
      "2023-08-01": 238
      "2023-09-24": 250
      "2024-03-26": 258
      "2025-03-01": 261
      "2025-08-10": 266
appearance:
  locale: 'en_US'
  unitSystem: 'metric'
  timeFormat: 24
  dateFormat:
    short: 'd-m-y' # This renders to 01-01-25
    normal: 'd-m-Y' # This renders to 01-01-2025
  dashboard:
    layout:
      - { 'widget': 'mostRecentActivities', 'width': 66, 'enabled': true, 'config': { 'numberOfActivitiesToDisplay': 5 } }
      - { 'widget': 'introText', 'width': 33, 'enabled': true }
      - { 'widget': 'trainingGoals', 'width': 33, 'enabled': true, 'config': 
          {
            'goals': {
              'weekly': [
                # Cycling
                { label: 'Cycling', enabled: true, type: 'distance', unit: 'km', goal: 200,  sportTypesToInclude: ['Ride', 'MountainBikeRide', 'GravelRide', 'VirtualRide'] },
                { label: 'Cycling', enabled: true, type: 'movingTime', unit: 'hour', goal: 8,  sportTypesToInclude: ['Ride', 'MountainBikeRide', 'GravelRide', 'VirtualRide'] },
                { label: 'Cycling', enabled: true, type: 'elevation', unit: 'm', goal: 1000,  sportTypesToInclude: ['Ride', 'MountainBikeRide', 'GravelRide', 'VirtualRide'] },
                # Running
                { label: 'Running', enabled: true, type: 'movingTime', unit: 'hour', goal: 2,  sportTypesToInclude: ['Run'] },
              ],
              'monthly': [
                # Cycling
                { label: 'Cycling', enabled: true, type: 'distance', unit: 'km', goal: 1000,  sportTypesToInclude: ['Ride', 'MountainBikeRide', 'GravelRide', 'VirtualRide'] },
                { label: 'Cycling', enabled: true, type: 'movingTime', unit: 'hour', goal: 30,  sportTypesToInclude: ['Ride', 'MountainBikeRide', 'GravelRide', 'VirtualRide'] },
                { label: 'Cycling', enabled: true, type: 'elevation', unit: 'm', goal: 1500,  sportTypesToInclude: ['Ride', 'MountainBikeRide', 'GravelRide', 'VirtualRide'] },
              ]
            }
          }
        }
      - { 'widget': 'weeklyStats', 'width': 66, 'enabled': true }
      - { 'widget': 'eddington', 'width': 33, 'enabled': true }
      - { 'widget': 'heartRateZones', 'width': 66, 'enabled': true }
      - { 'widget': 'monthlyStats', 'width': 100, 'enabled': true, 'config': { 'context': 'distance', enableLastXYearsByDefault: 3, metricsDisplayOrder: ['movingTime', 'distance', 'elevation'] } }
      - { 'widget': 'weekdayStats', 'width': 50, 'enabled': true }
      - { 'widget': 'dayTimeStats', 'width': 50, 'enabled': true }
      - { 'widget': 'activityGrid', 'width': 100, 'enabled': true }
      - { 'widget': 'trainingLoad', 'width': 100, 'enabled': true }
      - { 'widget': 'yearlyDistances', 'width': 100, 'enabled': true, 'config': { enableLastXYearsByDefault: 3 } }
      - { 'widget': 'distanceBreakdown', 'width': 100, 'enabled': true }
      - { 'widget': 'gearStats', 'width': 50, 'enabled': true, 'config': {'includeRetiredGear': false } }    
      - { 'widget': 'challengeConsistency', 'width': 50, 'enabled': true, config: 
          { 'challenges': 
            [
              # Totals 
              { label: '24hrs of activity', enabled: true, type: 'movingTime', unit: 'hour', goal: 24,   sportTypesToInclude: ['Run', 'TrailRun', 'VirtualRun', 'Walk', 'Hike', 'Ride', 'MountainBikeRide'] },
              { label: '32hrs of activity', enabled: true, type: 'movingTime', unit: 'hour', goal: 32,   sportTypesToInclude: ['Run', 'TrailRun', 'VirtualRun', 'Walk', 'Hike', 'Ride', 'MountainBikeRide'] },
              
              # Running: 
              { label: 'Run/walk/hike a total of 200km', enabled: true, type: 'distance', unit: 'km', goal: 200,   sportTypesToInclude: ['Run', 'TrailRun', 'VirtualRun', 'Walk', 'Hike'] },
              { label: 'Run/walk/hike a total of 300km', enabled: true, type: 'distance', unit: 'km', goal: 300,   sportTypesToInclude: ['Run', 'TrailRun', 'VirtualRun', 'Walk', 'Hike'] },
              { label: 'Climb 4000m for the month', enabled: true, type: 'elevation', unit: 'm', goal: 4000,   sportTypesToInclude: ['Run', 'TrailRun', 'VirtualRun', 'Walk', 'Hike'] }
            ]

          } 
        }
      # disabled widgets 
      - { 'widget': 'zwiftStats', 'width': 50, 'enabled': false }
      - { 'widget': 'peakPowerOutputs', 'width': 50, 'enabled': false }
      - { 'widget': 'mostRecentChallengesCompleted', 'width': 50, 'enabled': false, 'config': { 'numberOfChallengesToDisplay': 5 } }
      - { 'widget': 'ftpHistory', 'width': 50, 'enabled': false }
  heatmap:
    polylineColor: '#fc6719'
    tileLayerUrl:
     - 'https://tile.openstreetmap.org/{z}/{x}/{y}.png'
     - 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}.png'
    enableGreyScale: false
  photos:
    hidePhotosForSportTypes: []  
  sportTypesSortingOrder: [Run, Ride, Walk]  
import:
  numberOfNewActivitiesToProcessPerImport: 250
  sportTypesToImport: []
  activityVisibilitiesToImport: []
  skipActivitiesRecordedBefore: null
  activitiesToSkipDuringImport: []
  optInToSegmentDetailImport: false
metrics:
  eddington:
    - label: 'Run'
      showInNavBar: true
      showInDashboardWidget: true
      sportTypesToInclude: ['Run', 'TrailRun', 'VirtualRun']
    - label: 'Ride'
      showInNavBar: true
      showInDashboardWidget: true
      sportTypesToInclude: ['Ride', 'MountainBikeRide', 'GravelRide', 'VirtualRide']
    - label: 'Walk'
      showInNavBar: false
      showInDashboardWidget: false
      sportTypesToInclude: ['Walk', 'Hike']
gear:
  # Optional, Used to enrich gear with data that cannot be configured in Strava.  
  stravaGear:
      - gearId: 'g23671865'   # ASICS Gel-Nimbus 26 Shoe 
        # Used to calculate the relative cost per workout and hour.
        purchasePrice:
          amountInCents: 18999
          currency: 'AUD'
      - gearId: 'b5248891'    # Rocky Mountain Altitude 730 Mountain Bike
        # Used to calculate the relative cost per workout and hour.
        purchasePrice:
          amountInCents: 180000
          currency: 'AUD'
      - gearId: 'b13795730'   # Frankenstein Road Bike
        # Used to calculate the relative cost per workout and hour.
        purchasePrice:
          amountInCents: 200000
          currency: 'AUD'
      - gearId: 'g16767005'  # Nike InfinityRN 4 Shoe
        # Used to calculate the relative cost per workout and hour.
        purchasePrice:
          amountInCents: 16827
          currency: 'AUD'
      - gearId: 'g8312694'    # HOKA Bondi 7 Shoe
        # Used to calculate the relative cost per workout and hour.
        purchasePrice:
          amountInCents: 31998
          currency: 'AUD'
      - gearId: 'g19471623'    # HOKA Carbon X2 Shoe
        # Used to calculate the relative cost per workout and hour.
        purchasePrice:
          amountInCents: 23199
          currency: 'AUD'
      - gearId: 'g26338439'    # ASICS Trabuco Max 3 Shoe
        # Used to calculate the relative cost per workout and hour.
        purchasePrice:
          amountInCents: 18999
          currency: 'AUD'
      - gearId: 'g16162886'    # Brooks Adrenaline GTS 23 Shoe
        # Used to calculate the relative cost per workout and hour.
        purchasePrice:
          amountInCents: 25999
          currency: 'AUD'  
  customGear:
    enabled: true
    hashtagPrefix: sfs
    customGears:
      - tag: peddle-board
        label: Peddle Board
        isRetired: false
      - tag: workout-shoes
        label: Fancy workout shoes
        isRetired: true
zwift:
  level: null
  racingScore: null
integrations:
  notifications:
    services:
       - 'ntfy://admin:admin@ntfy.sh/topic'
       - 'discord://token@webhookid?thread_id=123456789'
  ai:
    enabled: true
    enableUI: true
    provider: 'openAI'
    configuration:
      key: 'sk-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'  # Your API key for the selected provider.
      model: 'gpt-4.1-mini-2025-04-14' # 'gpt-5-mini'  # 'gpt-4.1-mini' # 'gpt-4o-128k' # 'gpt-4.1' # 'gpt-3.5-turbo'  # or try gpt-4o
    agent:
      commands:
        # Performance & Training Analysis
        - command: 'analyse-last-workout'  
          message: 'You are my run coach. Analyse my most recent run with regard to aspects such as heart rate, pace, and how these were affected by elevation. Give me an assessment of my performance level and possible improvements for future training sessions.'
        - command: 'compare-last-two-weeks'
          message:  'You are my run coach. Compare my workouts and performance of the last 7 days with the 7 days before and give a short assessment.'
        - command: 'identify-training-trends'
          message: 'You are my run coach. Review my recent 4 weeks of workouts and identify any key trends such as improving endurance, fatigue signs, or plateauing fitness. Suggest adjustments if needed.'
        - command: 'analyse-interval-session'
          message: 'You are my run coach. Analyse my most recent interval workout, focusing on pacing consistency, recovery between reps, and heart rate control. Give feedback on execution and how to improve next time.'
        - command: 'evaluate-long-run'
          message: 'You are my run coach. Evaluate my most recent long run in terms of pacing strategy, heart rate drift, and endurance performance. Offer guidance for the next long run.'
        - command: 'analyse-race-effort'
          message: 'You are my run coach. Analyse my most recent race or hard effort run. Evaluate pacing, execution, and whether I met my target performance. Suggest what to take forward into future races.'
        
        # Readiness & Recovery
        - command: 'assess-fatigue-level' 
          message: 'You are my run coach. Based on my last week’s workouts and overall load, assess my likely fatigue and readiness to train. Suggest whether I should prioritise recovery, maintenance, or progression.'
        - command: 'suggest-recovery-day'
          message: 'You are my run coach. Review my most recent training load and suggest what kind of recovery activity or rest day I should take today.'
        
        # Training Plan & Goal Setting
        - command: 'plan-next-week'  
          message: 'You are my run coach. Based on my last 2 weeks of training and upcoming goals, propose a balanced 7-day plan including key workouts, easy runs, and rest days.'
        - command: 'set-next-goal'
          message: 'You are my run coach. Based on my recent fitness and race history, suggest a realistic short-term goal (e.g., 5K, 10K, or trail race) with a timeline and key focus areas.'
        - command: 'evaluate-training-balance'
          message: 'You are my run coach. Evaluate the balance between my easy, moderate, and hard runs over the last 4 weeks. Suggest any adjustments for optimal progression.'
        
        # Environmental & Terrain Insights
        - command: 'analyse-heat-impact'  
          message: 'You are my run coach. Analyse how temperature and humidity affected my heart rate and pace in my most recent runs. Suggest how to adjust my effort in future hot conditions.'
        - command: 'analyse-elevation-impact'
          message: 'You are my run coach. Analyse how elevation gain and loss affected my pacing and heart rate in my recent trail or hilly runs.'  
        
        # Consistency & Habits
        - command: 'review-training-consistency'  
          message: 'You are my run coach. Review my last 8 weeks of running frequency, volume, and intensity. Comment on my consistency and identify any gaps or strong points.'
        - command: 'summarise-monthly-progress'
          message: 'You are my run coach. Summarise my training for the past month, highlighting total distance, time, average paces, and any key improvements.'
daemon:
  cron:
    - action: 'gearMaintenanceNotification'
      expression: '0 14 * * *'
      enabled: true
    - action: 'appUpdateAvailableNotification'
      expression: '0 14 * * *'
      enabled: true
```