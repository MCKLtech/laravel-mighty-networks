# Changelog

All notable changes to `laravel-mighty-networks` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.0] - 2026-11-09

### Added

- Initial release.

### Fixed

- Rate limits are namespaced per network (`mighty-networks:{network_id}:{Class}`), so networks no longer share one limiter. Connections without a `network_id` keep the previous prefix.
