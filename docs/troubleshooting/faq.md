# FAQ

## Why does it take so long to import my data?

Running the import for the first time can take a while, depending on how many activities you have on Strava. 
Strava's API has a `rate limit` of `100 request per 15 minutes` and a `1000 requests per day`. 
We have to make sure this limit is not exceeded. See https://developers.strava.com/docs/rate-limits/. 
The app makes sure there is enough time between requests to not hit the 15-minute limit.

By default, the app imports up to `250 new activities per run`.
This limit helps ensure that additional metadata can also be fetched without exceeding the daily API rate limit.

You can adjust this value in your `.env` file. 
For an initial import where you want to fetch as many activities as possible, set it to _1000_.
If you hit the daily rate limit, the app will automatically import the remaining activities the next day(s).

## Can I sync multiple Strava accounts?

No, the app only supports one Strava account at a time. If you want to use multiple Strava accounts, 
you will need to run multiple instances of the app, each with its own Strava client ID and secret.

## Can I manage my settings through the UI?

No. All configuration is done through the config `yaml` files. Adding a settings UI would require implementing
user authentication and account management, which introduces significant complexity. 
To keep the app simple and lightweight, we intentionally avoid this and rely on file-based configuration instead.