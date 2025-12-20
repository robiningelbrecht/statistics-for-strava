```yaml
general:
  # The URL on which the app will be hosted. This URL will be used in the manifest file. 
  # This will allow you to install the web app as a native app on your device.
  appUrl: 'http://localhost:8080/'
  # Optional subtitle to display in the navbar.
  # Useful for distinguishing between multiple instances of the app.
  # Leave empty to disable.
  appSubTitle: null
  # Optional, a link to your profile picture. Will be used to display in the nav bar and link to your Strava profile.
  # Any image can be used; a square format is recommended.
  # Leave empty to disable this feature.
  profilePictureUrl: null
  athlete:
    # Your birthday. Needed to calculate heart rate zones.
    birthday: 'YYYY-MM-DD'
    # The formula used to calculate your max heart rate. The default is Fox (220 - age).
    # Allowed values: arena, astrand, fox, gellish, nes, tanaka (https://pmc.ncbi.nlm.nih.gov/articles/PMC7523886/table/t2-ijes-13-7-1242/)
    # Or you can set a fixed number for any given date range.  
    maxHeartRateFormula: 'fox'
    # maxHeartRateFormula:
    #    "2020-01-01": 198
    #    "2025-01-10": 193
    # If you're not sure about your zones, leave this unchanged, the defaults are sensible.
    heartRateZones:
      # Relative or absolute. 
      # Relative will treat the zone numbers as percentages based on your max heart rate, while absolute will treat them as actual heartbeats per minute.
      # This mode will apply to all heart rate zones you define.
      mode: relative
      # The default zones for all activities.
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
      # 🔥 PRO tip: You can further refine your heart rate zones by specifying date ranges and sport types.
      #    Read more about the possibilities on https://statistics-for-strava-docs.robiningelbrecht.be/#/configuration/main-configuration?id=athlete-heart-rate-zones
    # History of weight (in kg or pounds, depending on appearance.unitSystem). Needed to calculate relative w/kg.
    # Make sure to replace the YYYY-MM-DD examples with your own weight history.
    # Read more about the weight history on https://statistics-for-strava-docs.robiningelbrecht.be/#/configuration/main-configuration?id=athlete-weight-history
    weightHistory:
      "YYYY-MM-DD": 100
    # Optional, history of FTP. Needed to calculate activity stress level.
    # ftp_history:
    #   cycling:
    #     "2023-01-01": 198
    #     "2023-03-22": 220
    ftpHistory:
      cycling: []
      running: []
appearance:
  # Allowed options: en_US, fr_FR, it_IT, nl_BE, de_DE, pt_BR, pt_PT sv_SE or zh_CN
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
    # Leave this setting unchanged to use the default dashboard.
    # For a detailed guide on how to override these defaults, visit: https://statistics-for-strava-docs.robiningelbrecht.be/#/configuration/dashboard-widgets
    layout: null
  heatmap:
    # Specifies the color of polylines drawn on the heatmap. Accepts any valid CSS color.
    # (e.g. "red", "#FF0000", "rgb(255,0,0)")
    polylineColor: '#fc6719'
    # Specifies the type of map to use. Must be a valid tile layer URL.
    # For example, a satellite layer: https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}.png
    tileLayerUrl: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png'
    # You can also define multiple tile layers — for example, to overlay place names on a satellite tile layer.
    # tileLayerUrl:
    #  - 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}.png'
    #  - 'https://server.arcgisonline.com/ArcGIS/rest/services/Reference/World_Boundaries_and_Places/MapServer/tile/{z}/{y}/{x}.png'
    # Enables or disables grayscale styling on the heatmap.
    enableGreyScale: true
  photos:
    # Optional, an array of sport types for which photos should be hidden on the photos page. 
    # They will not be rendered, and they won't show up in the filters. 
    # A full list of allowed options is available on https://statistics-for-strava-docs.robiningelbrecht.be/#/configuration/main-configuration?id=supported-sport-types
    hidePhotosForSportTypes: []
    # Optional, a list of filters that are enabled by default. For example, you can use this to automatically hide all photos from virtual activities.
    defaultEnabledFilters:
      # A full list of allowed options is available on https://statistics-for-strava-docs.robiningelbrecht.be/#/configuration/main-configuration?id=supported-sport-types
      sportTypes: []
      # A valid ISO2 country code.
      countryCode: null  
  # With this list you can decide the order the sport types will be rendered in. For example in the tabs on the dashboard.
  # You don't have to include all sport types. Sport types not included in this list will be rendered by the app default.
  # A full list of allowed options is available on https://statistics-for-strava-docs.robiningelbrecht.be/#/configuration/main-configuration?id=supported-sport-types 
  sportTypesSortingOrder: []  
import:
  # Strava API has rate limits (https://statistics-for-strava-docs.robiningelbrecht.be/#/troubleshooting/faq?id=why-does-it-take-so-long-to-import-my-data),
  # to make sure we don't hit the rate limit, we want to cap the number of new activities processed
  # per import. Considering there's a 1000 request per day limit and importing one new activity can
  # take up to 3 API calls, 250 should be a safe number.
  numberOfNewActivitiesToProcessPerImport: 250
  # Sport types to import. Leave empty to import all sport types
  # A full list of allowed options is available on https://statistics-for-strava-docs.robiningelbrecht.be/#/configuration/main-configuration?id=supported-sport-types 
  # ⚠️ Changing this setting after activities have already been imported will delete all activities that are not included in the list of sport types.
  sportTypesToImport: []
  # Activity visibilities to import. Leave empty to import all visibilities
  # This list can be combined with sportTypesToImport.
  # Allowed values: ["everyone", "followers_only", "only_me"]
  # ⚠️ Changing this setting after activities have already been imported will delete all activities that are not included in the list of visibilities.
  activityVisibilitiesToImport: []
  # Optional, the date (YYYY-MM-DD) from which you want to start importing activities. 
  # Any activity recorded before this date, will not be imported.
  # This can be used if you want to skip the import of older activities. Leave empty to disable.
  # ⚠️ Changing this setting after activities have already been imported will delete all activities recorded before the specified date.
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
  # 🔥 PRO tip: it's possible to use the same sport type over multiple eddington numbers.
  eddington:
      # The label to be used for the tabs on the Eddington page.
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
    - label: 'Run'
      showInNavBar: true
      showInDashboardWidget: true
      sportTypesToInclude: ['Run', 'TrailRun', 'VirtualRun']
    - label: 'Walk'
      showInNavBar: false
      showInDashboardWidget: false
      sportTypesToInclude: ['Walk', 'Hike']
gear:
    # Optional, Used to enrich gear with data that cannot be configured in Strava.  
    stravaGear:
      # The Strava gear ID.
      # ⚠️ Do NOT copy the gear ID from the Strava URL, that value will not work.
      # Instead, open the gear details popup (click the question-mark icon in the top-right corner of the gear page) 
      # and copy the gear ID shown there.
      # https://statistics-for-strava-docs.robiningelbrecht.be/#/configuration/gear-maintenance?id=strava-gear-ids
      - gearId: 'g12337767'
        # Used to calculate the relative cost per workout and hour.
        purchasePrice:
          amountInCents: 123456
          currency: 'EUR'
    # Optional, this is useful for gear that Strava doesn't allow you to track
    # Read how to configure on https://statistics-for-strava-docs.robiningelbrecht.be/#/configuration/custom-gear      
    customGear: []
zwift:
  # Optional, your Zwift level (1 - 100). Will be used to render your Zwift badge. Leave empty to disable this feature
  level: null
  # Optional, your Zwift racing score (0 - 1000). Will be used to add to your Zwift badge if zwift.level is filled out.
  racingScore: null
integrations:
  notifications:
    # Optional: Specify one or more notification services. For a complete list of available services, see: https://shoutrrr.nickfedor.com/dev/services/overview/
    # Leave empty to disable notifications entirely.
    services: []
    # - 'ntfy://ntfy.sh/topic'
  # All configuration options related to AI integrations.
  # For a comprehensive explanation on how to set up this integration, visit: https://statistics-for-strava-docs.robiningelbrecht.be/#/configuration/ai-integration
  ai:
    # Enable or disable AI features.
    # ⚠️ Use caution when enabling this feature if your app is publicly accessible!
    enabled: false
    # Enable or disable AI features in the UI. By default, these features are only accessible via a CLI command.
    # ⚠️ Use caution when enabling this feature if your app is publicly accessible!
    enableUI: false
    # The provider you want to use. 
    # Allowed values: ["anthropic", "azureOpenAI", "gemini", "ollama", "openAI", "deepseek", "mistral"]
    provider: 'PROVIDER-YOU-CHOOSE'
    configuration:
      key: 'YOUR-API-KEY'
      model: 'MODEL-NAME'
      # This option is only required when using provider "ollama"
      # The url to your hosted Ollama instance.
      url: 'http://host.docker.internal:11434/api'
    # agent:
    #   commands:
    #    For a detailed guide on how to configure pre-defined chat commands, visit: https://statistics-for-strava-docs.robiningelbrecht.be/#/configuration/ai-integration?id=pre-defining-chat-commands
daemon:
  # A list of actions that the application runs at regular intervals according to their defined schedule.
  # Notification-related actions require the integrations.notifications.ntfyUrl setting to be configured.
  # ⚠️ This configuration only applies if you have set up the daemon container:
  #   https://statistics-for-strava-docs.robiningelbrecht.be/#/getting-started/installation?id=docker-composeyml
  cron:
      # Action name. Allowed values: importDataAndBuildApp, gearMaintenanceNotification, appUpdateAvailableNotification
    - action: 'importDataAndBuildApp'
      # Cron expression specifying when the action should run.
      # Example: '0 14 * * *' runs every day at 14:00 (2 PM).
      # See https://crontab.guru/ for help creating or testing cron expressions.
      expression: '0 14 * * *'
      # Whether this action should be executed (true/false)
      enabled: true
    - action: 'gearMaintenanceNotification'
      expression: '0 14 * * *'
      enabled: false
    - action: 'appUpdateAvailableNotification'
      expression: '0 14 * * *'
      enabled: false
```
