# Full configuration example

The following example shows a configuration file with extended settings. You can use it as a reference when creating your own configuration.
Thanks to [lennon101](https://github.com/lennon101) for providing this example.

```yaml
general:
  # The URL on which the app will be hosted. This URL will be used in the manifest file. 
  # This will allow you to install the web app as a native app on your device.
  appUrl: 'https://<your-domain>/'
  # Optional subtitle to display in the navbar.
  # Useful for distinguishing between multiple instances of the app.
  # Leave empty to disable.
  appSubTitle: null
  # Optional, a link to your profile picture. Will be used to display in the nav bar and link to your Strava profile.
  # Any image can be used; a square format is recommended.
  # Leave empty to disable this feature.
  profilePictureUrl: https://<url-to-your-profile-picture>.jpg
  athlete:
    # Your birthday. Needed to calculate heart rate zones.
    birthday: '<YYYY-MM-DD>'
    # The formula used to calculate your max heart rate. The default is Fox (220 - age).
    # Allowed values: arena, astrand, fox, gellish, nes, tanaka (https://pmc.ncbi.nlm.nih.gov/articles/PMC7523886/table/t2-ijes-13-7-1242/)
    # Or you can set a fixed number for any given date range.  
    maxHeartRateFormula: 'nes' # Chose nes because I know my max heart rate, and this is the one that gets closest to it when using my age
    # If you're not sure about your zones, leave this unchanged — the defaults are sensible.
    heartRateZones:
      # Relative or absolute. 
      # Relative will treat the zone numbers as percentages based on your max heart rate, while absolute will treat them as actual heartbeats per minute.
      # This mode will apply to all heart rate zones you define.
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
    # History of weight (in kg or pounds, depending on appearance.unitSystem). Needed to calculate relative w/kg.
    # Make sure to replace the YYYY-MM-DD examples with your own weight history.
    # Read more about the weight history on https://statistics-for-strava-docs.robiningelbrecht.be/#/configuration/main-configuration?id=athlete-weight-history
    weightHistory:
      "2025-09-30": 76
      "2018-01-01": 78
    # Optional, history of FTP. Needed to calculate activity stress level.
    ftpHistory:
      "2023-04-01": 198
      "2023-05-25": 220
      "2023-08-01": 238
      "2023-09-24": 250
      "2024-03-26": 258
      "2025-03-01": 261
      "2025-08-10": 266
appearance:
  # Allowed options: en_US, fr_FR, it_IT, nl_BE, de_DE, pt_BR, pt_PT or zh_CN
  locale: 'en_US'
  # Allowed options: metric or imperial
  unitSystem: 'metric'
  # Time format to use when rendering the app
  # Allowed formats: 24 or 12 (includes AM and PM)
  timeFormat: 24
  # Date format to use when rendering the app
  # For valid PHP date formats: https://www.php.net/manual/en/datetime.format.php
  # If you don't know how to use these formats, leave this unchanged — the defaults are sensible.
  dateFormat:
    short: 'd-m-y' # This renders to 01-01-25
    normal: 'd-m-Y' # This renders to 01-01-2025
  dashboard:
    # The dashboard is built using widgets. You can enable or disable each widget, and set their respective width.
    #   Note: "width" property must be one of [33, 50, 66, 100]
    # Leave this setting unchanged to use the default dashboard.
    # For a detailed guide on how to override these defaults, visit: https://statistics-for-strava-docs.robiningelbrecht.be/#/configuration/dashboard-widgets
    #layout: null # sets the dashboard to the default settings 
    layout:
      - { 'widget': 'mostRecentActivities', 'width': 66, 'enabled': true, 'config': { 'numberOfActivitiesToDisplay': 5 } }
      - { 'widget': 'introText', 'width': 33, 'enabled': true }
      - { 'widget': 'weeklyGoals', 'width': 33, 'enabled': true, 'config': 
          {
            'goals': 
            [
              # Running
              { label: 'Run 60km', enabled: true, type: 'distance', unit: 'km', goal: 60,  sportTypesToInclude: ['Run', 'TrailRun'] },
              { label: 'Run 8hrs', enabled: true, type: 'movingTime', unit: 'hour', goal: 8,  sportTypesToInclude: ['Run', 'TrailRun'] },
              { label: 'Climb 1500m', enabled: true, type: 'elevation', unit: 'm', goal: 1500,  sportTypesToInclude: ['Run', 'TrailRun'] },
            ]
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
    # Specifies the color of polylines drawn on the heatmap. Accepts any valid CSS color.
    # (e.g. "red", "#FF0000", "rgb(255,0,0)")
    polylineColor: '#fc6719'
    # Specifies the type of map to use. Must be a valid tile layer URL.
    # For example, a satellite layer: https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}.png
    # tileLayerUrl: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png'
    # You can also define multiple tile layers — for example, to overlay place names on a satellite tile layer.
    tileLayerUrl:
     - 'https://tile.openstreetmap.org/{z}/{x}/{y}.png'
     - 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}.png'
    #  - 'https://server.arcgisonline.com/ArcGIS/rest/services/Reference/World_Boundaries_and_Places/MapServer/tile/{z}/{y}/{x}.png'
    # Enables or disables grayscale styling on the heatmap.
    enableGreyScale: false
  photos:
    # Optional, an array of sport types for which photos should be hidden on the photos page.
    # A full list of allowed options is available on https://statistics-for-strava-docs.robiningelbrecht.be/#/configuration/main-configuration?id=supported-sport-types
    hidePhotosForSportTypes: []  
  # With this list you can decide the order the sport types will be rendered in. For example in the tabs on the dashboard.
  # You don't have to include all sport types. Sport types not included in this list will be rendered by the app default.
  # A full list of allowed options is available on https://statistics-for-strava-docs.robiningelbrecht.be/#/configuration/main-configuration?id=supported-sport-types 
  sportTypesSortingOrder: [Run, Ride, Walk]  
import:
  # Strava API has rate limits (https://statistics-for-strava-docs.robiningelbrecht.be/#/troubleshooting/faq?id=why-does-it-take-so-long-to-import-my-data),
  # to make sure we don't hit the rate limit, we want to cap the number of new activities processed
  # per import. Considering there's a 1000 request per day limit and importing one new activity can
  # take up to 3 API calls, 250 should be a safe number.
  numberOfNewActivitiesToProcessPerImport: 250
  # Sport types to import. Leave empty to import all sport types
  # A full list of allowed options is available on https://statistics-for-strava-docs.robiningelbrecht.be/#/configuration/main-configuration?id=supported-sport-types 
  sportTypesToImport: []
  # Activity visibilities to import. Leave empty to import all visibilities
  # This list can be combined with sportTypesToImport.
  # Allowed values: ["everyone", "followers_only", "only_me"]
  activityVisibilitiesToImport: []
  # Optional, the date (YYYY-MM-DD) from which you want to start importing activities. 
  # Any activity recorded before this date, will not be imported.
  # This can be used if you want to skip the import of older activities. Leave empty to disable.
  skipActivitiesRecordedBefore: null
  # An array of activity ids to skip during import. 
  # This allows you to skip specific activities during import.
  # ["123456789", "987654321"]
  activitiesToSkipDuringImport: []
  # Setting this to true will import segment details. This means each segment will need an extra Strava API call to fetch the segment details.
  # This is required to be able to display a map of the segment.
  # Setting this to true will increase the import time significantly if you have a lot of segments.
  # Each segment only needs to be imported once, so this will not affect the import time for subsequent imports.
  optInToSegmentDetailImport: false
metrics:
  # By default, the app calculates your Eddington score for Rides, Runs, and Walks.
  # Each category includes a list of sport types used in the calculation.
  # This setting lets you customize which sport types are grouped together and how the Eddington score is calculated.
  # If you're not familiar with the Eddington score, it's best to leave this as is for now and explore it once the app is running.
  #  PRO tip: it's possible to use the same sport type over multiple eddington numbers.
  eddington:
      # The label to be used for the tabs on the Eddington page.
    - label: 'Run'
      showInNavBar: true
      showInDashboardWidget: true
      sportTypesToInclude: ['Run', 'TrailRun', 'VirtualRun']
    - label: 'Ride'
      # A boolean to indicate if this score should be displayed in the side navigation.
      # You can only enable two of these at the same time.
      showInNavBar: true
      # A boolean to indicate if this score should be used in the dashboard widget.
      # You can only enable two of these at the same time.
      showInDashboardWidget: true
      # The sport types to include in the Eddington score for this tab.
      # Only sport types that belong to the same activity type (category) can be combined.
      # For a complete list of supported sport and activity types, visit: https://statistics-for-strava-docs.robiningelbrecht.be/#/configuration/main-configuration?id=supported-sport-types
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
  # Optional, your Zwift level (1 - 100). Will be used to render your Zwift badge. Leave empty to disable this feature
  level: null
  # Optional, your Zwift racing score (0 - 1000). Will be used to add to your Zwift badge if zwift.level is filled out.
  racingScore: null
integrations:
  notifications:
    services:
       - 'ntfy://admin:admin@ntfy.sh/topic'
       - 'discord://token@webhookid?thread_id=123456789'
  # All configuration options related to AI integrations.
  # For a comprehensive explanation on how to set up this integration, visit: https://statistics-for-strava-docs.robiningelbrecht.be/#/configuration/ai-integration
  ai:
    # Enable or disable AI features.
    # Use caution when enabling this feature if your app is publicly accessible!
    enabled: true
    # Enable or disable AI features in the UI. By default, these features are only accessible via a CLI command.
    # Use caution when enabling this feature if your app is publicly accessible!
    enableUI: true
    # The provider you want to use. 
    # Allowed values: ["anthropic", "gemini", "ollama", "openAI", "deepseek", "mistral"]
    provider: 'openAI'
    configuration:
      key: 'sk-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'  # Your API key for the selected provider.
      # The model you want to use.
      model: 'gpt-4.1-mini-2025-04-14' # 'gpt-5-mini'  # 'gpt-4.1-mini' # 'gpt-4o-128k' # 'gpt-4.1' # 'gpt-3.5-turbo'  # or try gpt-4o
      # This option is only required when using provider "ollama"
      # The url to your hosted Ollama instance.
      # url: 'http://host.docker.internal:11434/api'
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
  # A list of actions that the application runs at regular intervals according to their defined schedule.
  # Notification-related actions require the integrations.notifications.ntfyUrl setting to be configured.
  cron:
    - action: 'gearMaintenanceNotification'
      expression: '0 14 * * *'
      enabled: true
    - action: 'appUpdateAvailableNotification'
      expression: '0 14 * * *'
      enabled: true
```