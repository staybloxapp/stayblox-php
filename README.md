# Stayblox PHP SDK

The official PHP SDK for building apps on the [Stayblox](https://stayblox.com) platform. It handles the Stayblox side of an app so you can focus on your integration: OAuth install, the Developer GraphQL API client, request-signature verification, and the inbox and distribution channel modules.

> **This is a Laravel package.** It requires **Laravel 13** and PHP 8.4+, and reuses Laravel's Eloquent, HTTP client, encryption, and middleware. Building on another framework? Use the [Developer API](https://dev.stayblox.com/apps/graphql), [OAuth](https://dev.stayblox.com/apps/oauth), and [request signing](https://dev.stayblox.com/apps/signing) directly.

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
- **`Stayblox\Distribution`** — for channel apps: receive signed `ari_push` availability/rate commands, submit reservations, and pull listing content and rates.

## Distribution

Channel apps (Airbnb, Booking.com, and similar) receive availability and rate updates from Stayblox as signed `ari_push` commands, and push reservations, listing content, and rates back through the Developer API. `AriPushReceiver` decodes and dispatches the inbound command, the same way `Stayblox\Inbox`'s `MessageSendReceiver` does for outbound messages. `DistributionApiClient` is the authenticated GraphQL client for everything the app initiates: registering integrations, linking listings, submitting reservations, and reading content.

```php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Stayblox\Distribution\AriPushReceiver;
use Stayblox\Distribution\Dto\AriApplyResult;
use Stayblox\Distribution\Dto\AriPushCommand;
use Stayblox\Distribution\DistributionApiClient;
use Stayblox\Distribution\Dto\Reservation;

Route::post('/ari-push', fn (Request $request) => response()->json(
    (new AriPushReceiver)->handle($request->json()->all(), function (AriPushCommand $command) {
        // Publish $command->listings' rates and availability to the OTA...
        return AriApplyResult::applied();
    })
))->middleware('stayblox.signed');

// Once the OTA confirms a booking:
$install = $request->attributes->get('stayblox_install');
$client = new DistributionApiClient($graphqlUrl);

$client->reservationUpsert($install, new Reservation(
    integrationId: $integrationId,
    externalId: 'ABJQXKRZ55',
    revisionId: 'rev-001',
    status: 'NEW',
    checkIn: '2026-08-10',
    checkOut: '2026-08-12',
    currency: 'EUR',
    paymentCollect: 'OTA',
    guestFirstName: 'Ana',
    guestLastName: 'Petrova',
    totalAmount: 260.0,
    rooms: [/* ReservationRoom(...) */],
));
```

## Documentation

Full guides, the API reference, and worked examples live in the Stayblox developer documentation:

- **[Build apps → PHP SDK](https://dev.stayblox.com/apps/php-sdk)**

## License

MIT. See [LICENSE](LICENSE).
