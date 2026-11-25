# Changelog

All notable changes to `laravel-mighty-networks` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.1.0] - 2026-11-25

### Added

- `WebhookEventType::MemberReturned` (`MEMBER_RETURNED`). `MemberReturnedHook` is delivered but is not in the GraphQL SDL, and used to resolve to `null`.
- `VerifyWebhookSecret::resolveSecretUsing()` chooses the expected secret per request, for applications with a secret per network.
- `WebhookController::resolveNetworkUsing()`, and `$network` on `ProcessWebhookJob` and `WebhookReceived`. It defaults to `payload.network_id`.
- `docs/webhooks.md`: examples of the webhook messages Mighty Networks really sends, and a README summary.

### Changed

- `webhooks.tolerance` defaults to `null`: `event_timestamp` is no longer checked unless a tolerance is set. A published config that still says `300` keeps checking.
- The webhook idempotency key includes the network when it is known: `mighty-networks:webhook:{network}:{event_id}`.

### Fixed

- An event is no longer lost when a `WebhookReceived` listener throws. The idempotency claim is released, so a retry processes it again.

## [1.0.0] - 2026-11-09

### Added

- Initial release.

### Fixed

- Rate limits are namespaced per network (`mighty-networks:{network_id}:{Class}`), so networks no longer share one limiter. Connections without a `network_id` keep the previous prefix.
