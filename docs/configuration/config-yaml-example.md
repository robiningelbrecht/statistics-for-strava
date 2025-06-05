The main configuration yaml file contains all the settings for you to customize the app and set it up to your liking.

```yaml
general:
  # The URL on which the app will be hosted. This URL will be used in the manifest file. 
  # This will allow you to install the web app as a native app on your device.
  appUrl: 'http://localhost:8080/'
  # Optional, a link to your profile picture. Will be used to display in the nav bar and link to your Strava profile.
  # Leave empty to disable this feature.
  profilePictureUrl: null
  # Optional, full URL with ntfy topic included. This topic will be used to notify you when a new HTML build has run.
  # Leave empty to disable notifications.
  ntfyUrl: null
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
    # History of weight (in kg or pounds, depending on appearance.unitSystem). Needed to calculate relative w/kg.
    # Check https://github.com/robiningelbrecht/statistics-for-strava/wiki for more info.
    weightHistory:
      "YYYY-MM-DD": 100
      "YYYY-MM-DD": 200
    # Optional, history of FTP. Needed to calculate activity stress level.
    # Check https://github.com/robiningelbrecht/statistics-for-strava/wiki for more info. Example:
    # ftpHistory
    #    "2024-10-03": 198
    #    "2025-01-10": 220
    #
    ftpHistory: []
appearance:
  # Allowed options: en_US, fr_FR, nl_BE, de_DE, pt_BR, pt_PT or zh_CN
  locale: 'en_US'
  # Allowed options: metric or imperial
  unitSystem: 'metric'
  # Time format to use when rendering the app
  # Allowed formats: 24 or 12 (includes AM and PM)
  timeFormat: 24
  # Date format to use when rendering the app
  # Allowed formats: DAY-MONTH-YEAR or MONTH-DAY-YEAR
  dateFormat: 'DAY-MONTH-YEAR'
import:
  # Strava API has rate limits (https://github.com/robiningelbrecht/statistics-for-strava/wiki),
  # to make sure we don't hit the rate limit, we want to cap the number of new activities processed
  # per import. Considering there's a 1000 request per day limit and importing one new activity can
  # take up to 3 API calls, 250 should be a safe number.
  numberOfNewActivitiesToProcessPerImport: 250
  # Sport types to import. Leave empty to import all sport types
  # With this list you can also decide the order the sport types will be rendered in.
  # A full list of allowed options is available on https://github.com/robiningelbrecht/statistics-for-strava/wiki/Supported-sport-types/
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
zwift:
  # Optional, your Zwift level (1 - 100). Will be used to render your Zwift badge. Leave empty to disable this feature
  level: null
  # Optional, your Zwift racing score (0 - 1000). Will be used to add to your Zwift badge if zwift.level is filled out.
  racingScore: null
```