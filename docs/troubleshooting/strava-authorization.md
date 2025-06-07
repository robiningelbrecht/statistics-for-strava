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
or you did not restart your Docker container after updating the `.env` file. 
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
