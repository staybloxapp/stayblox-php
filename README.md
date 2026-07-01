# Stayblox PHP SDK

The official PHP SDK for building apps on the [Stayblox](https://stayblox.com) platform. It handles the Stayblox side of an app so you can focus on your integration: OAuth install, the Developer GraphQL API client, request-signature verification, and the inbox messaging modules.

## Requirements

- PHP 8.4+
- Laravel 13 (the package ships Laravel glue and auto-registers via package discovery)

## Installation

```bash
composer require stayblox/stayblox-php
```

## Modules

- **`Stayblox\Core`** — OAuth install handshake, install storage, the authenticated Developer API GraphQL client, and the `stayblox.signed` middleware that verifies signed requests from Stayblox.
- **`Stayblox\Inbox`** — for channel apps: inject inbound guest messages and receive outbound send commands.

## Documentation

Full guides, the API reference, and worked examples live in the Stayblox developer documentation:

- **[Build apps → PHP SDK](https://dev.stayblox.com/apps/php-sdk)**

## License

See `composer.json`.
