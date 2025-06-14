# Strava authorization

During the authorization process with Strava, you may encounter various errors. 
Below is a list of common issues and their solutions.

## Invalid permissions

When you attempt to access Strava data without the necessary permissions, 
you may receive an error message indicating that the required permissions are missing.
This typically occurs when the access token does not have the required scopes for the requested data 
and usually means that you didn't finish the authorization process correctly. 

Be sure to [complete the authorization flow](getting-started/installation.md?id=obtaining-a-strava-refresh-token) and copy/paste the access token to your `.env` file correctly. 
When you have done so, restart your Docker container to apply the changes.

```json
{
  "message": "Authorization Error",
  "errors": [
    {
      "resource": "AccessToken",
      "field": "activity:read_permission",
      "code": "missing"
    }
  ]
}
```

## Invalid client_id

When you encounter an error related to the `client_id`, 
it usually means that the client ID provided in your `.env` file is incorrect, 
or you did not recreate your Docker container after updating the `.env` file. 
Make sure that you have done both of these steps correctly.

```json
{
  "message": "Bad Request",
  "errors": [
    {
      "resource": "Application",
      "field": "client_id",
      "code": "missing"
    }
  ]
}
```

## Invalid redirect_uri

If you receive an error regarding the `redirect_uri`, this probably means that you misconfigured 
the `Website` and `Authorization Callback Domain` in your Strava application settings. 
Ensure that the `Website` and `Authorization Callback Domain` match the URL/domain you are using to access your application.

```json
{
  "message": "Bad Request",
  "errors": [
    {
      "resource": "Application",
      "field": "redirect_uri",
      "code": "invalid"
    }
  ]
}
```

## 500 Internal Server Error

If you receive an error stating something like:
```
Strava API threw error: Server Error: 'GET https://www.strava.com/api/v3/activities/[activityid]' restulted in a `500 Internal Server Error` response: 
{"message":"error"}
```

Please try to acces the activity in a browser by copieing the id from the error massage and navigatae to https://www.strava.com/activities/[ACTIVITY-ID]
If the activity is accasable by the url from step 1, then the activity is corrupted on Strava's end, and you have 2 options:
1. Update the activity by changing the private note or title of the activity and try to import again.
2. Skip the activity from importing by setting the `activitiesToSkipDuringImport` in your `config.yaml`. 
