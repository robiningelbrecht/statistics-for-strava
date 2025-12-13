# Shoutrrr notifications

When configuring Shoutrrr services, you may encounter errors like:

![Shoutrrr error](../assets/images/shoutrrr-notifications-error.png)

This indicates that Shoutrrr attempted to send a notification to one of your configured services,
but the request failed most likely due to a misconfiguration on your end. 
This is not something Statistics for Strava can resolve, you'll need to diagnose the issue.

To debug the problem, you can manually test your configuration using:

```bash
docker compose exec app shoutrrr send -v --url="generic://https://example.com" --message="Le message" --title="Le title"
```

This command allows you to send a test notification and inspect the exact response from the service, 
making it easier to identify what’s going wrong.

## Using ntfy.sh with an authentication token

If you are:

- Running a locally hosted NTFY server
- Requiring authentication to send notifications
- Using an authentication token

You may notice that the [Shoutrrr](https://shoutrrr.nickfedor.com/v0.12.0/services/push/ntfy/) documentation
does not clearly explain how to format the notification URL in this scenario. Use the following format: 

```
ntfy://:authentication-token@my-notify-server.com/topic-name
```

> [!IMPORTANT]
> **Important** Make sure to include the colon (:) before the authentication token.
Omitting the colon will cause authentication to fail.


```yaml
integrations:
  notifications:
    services:
      - 'ntfy://:tk_sdu7lm0oiefvieqxe1i4852j02gzu@ntfy.yourhost.com/statsforstrava'
```

> [!NOTE]
> Big thanks to [dschoepel](https://github.com/dschoepel) for figuring this out and sharing the solution.
