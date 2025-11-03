# Strava Webhooks Integration

Statistics for Strava supports Strava webhooks to automatically import and build your data when new activities are uploaded. This eliminates the need to manually run import commands or set up cron jobs.

When enabled, your app will receive real-time notifications from Strava whenever:

- A new activity is created
- An existing activity is updated
- An activity is deleted
- An athlete revokes app access

The app automatically runs the import and build processes in the background when receiving activity events.

## Prerequisites

**Important:** Your callback URL must be publicly accessible over HTTPS for Strava webhooks to work.

### Production Setup

- Your app must be hosted on a server with a public domain
- HTTPS must be configured (required by Strava)
- The `/webhook/strava` endpoint must be accessible from the internet

### View Current Subscription

To see your current webhook subscription:

```bash
docker compose exec app bin/console app:strava:webhook:view
```

This will display:

- Subscription ID
- Application ID
- Callback URL
- Creation and update timestamps

### Unsubscribe from Webhooks

To delete your webhook subscription:

```bash
docker compose exec app bin/console app:strava:webhook:unsubscribe <subscription-id>
```

Replace `<subscription-id>` with the ID from the view command.

You can also get the subscription ID from the view command:

```bash
# View subscription
docker compose exec app bin/console app:strava:webhook:view

# Delete subscription (use the ID from above)
docker compose exec app bin/console app:strava:webhook:unsubscribe 123456
```
