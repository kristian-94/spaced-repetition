# Spaced Repetition App

## Deployment

After committing and pushing, deploy to the homelab server:

```bash
ssh -A kristian@homelab 'cd /home/kristian/projects/spaced-repetition && ./deploy.sh'
```

This rebuilds the app, pulls the latest code, and runs migrations.

## Testing

**NEVER run tests on the homelab server. EVER.** The homelab runs the production database and tests will wipe it.

Always run tests locally:
```bash
php artisan test --env=testing
```
