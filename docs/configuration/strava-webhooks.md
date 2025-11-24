# Strava Webhooks Integration

Statistics for Strava supports Strava webhooks to automatically import and build your data when new activities are uploaded. 
This eliminates the need to manually run import commands or set up cron jobs.

When enabled, your app will receive real-time notifications from Strava whenever:

- A new activity is created
- An existing activity is updated
- An activity is deleted

The app automatically runs the import and build processes in the background when receiving activity events.

> [!IMPORTANT]
> **Important** Your Statistics for Strava instance must be publicly accessible over HTTPS for Strava webhooks to work.


## Configure a webhook subscription

TODO

```bash
docker compose exec app bin/console app:strava:webhooks-create
```

## View webhook subscriptions

To see your current webhook subscription:

```bash
docker compose exec app bin/console app:strava:webhooks-view
```

This will display:

- Subscription ID
- Application ID
- Callback URL
- Creation and update timestamps

## Unsubscribe from webhooks

To delete your webhook subscription:

```bash
docker compose exec app bin/console app:strava:webhooks-unsubscribe <subscription-id>
```

Replace `<subscription-id>` with the ID from the view command.

You can also get the subscription ID from the view command:

```bash
# View subscription
docker compose exec app bin/console app:strava:webhooks-view

# Delete subscription (use the ID from above)
docker compose exec app bin/console app:strava:webhooks-unsubscribe 123456
```

## Troubleshooting tips

If you get the following error when trying to create a webhook subscription

```json
{
  "message": "Bad Request",
  "errors": [
    {
      "resource": "PushSubscription",
      "field": "callback url",
      "code": "not verifiable"
    }
  ]
}
```

be sure to:

* Check that your Statistics for Strava instance is publicly accessible over the HTTPS
* Check if there is already a subscription registered for your app. Check with `docker compose exec app bin/console app:strava:webhooks-view`
* Validate that your https://your-instance.com/strava/webhook responds with a 200 status to a validation request within 2 seconds. You can issue a request like the following to test:

```bash
$ curl -X GET 'https://your-instance.com/strava/webhook?hub.verify_token=test&hub.challenge=15f7d1a91c1f40f8a748fd134752feb3&hub.mode=subscribe'
```