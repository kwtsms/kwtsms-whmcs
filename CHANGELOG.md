# Changelog

## [Unreleased]

## [1.0.1] - 2026-11-08

### Added
- Support for WHMCS 9.1.
- Requests to kwtSMS identify the integration and its version (no personal data).
- MIT licence file.

### Fixed
- WHMCS 9.1: gateway login, test SMS and log actions in the admin area work again.
- Invoice paid SMS is now sent to the client.
- New order alerts show the client's name.

### Changed
- Settings and documentation improvements.

## [1.0.0] - 2026-03-26

### Added
- Initial release
- Gateway login, logout, reload with balance and sender ID sync
- Test SMS with inline AJAX feedback through full send pipeline
- ClientAdd hook: SMS to new client on registration
- InvoicePaid hook: SMS to client on invoice payment
- AfterShoppingCartCheckout hook: SMS alert to admin on new order
- Full send() pipeline: normalize, validate, coverage filter, deduplicate, balance check (24h TTL), clean, send
- SMS log (mod_kwtsms_log), attempts log (mod_kwtsms_attempts), debug log (mod_kwtsms_debug_log)
- Seven-tab admin UI: Dashboard, Settings, Templates, Integrations, Logs, Help
- English and Arabic message templates with placeholder substitution
- Daily cron sync for balance, sender IDs, and coverage
- PHPStan level 5 and PHPCS PSR-12 compliant
