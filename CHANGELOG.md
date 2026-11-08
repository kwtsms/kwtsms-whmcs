# Changelog

## [Unreleased]

### Added
- Requests to kwtSMS carry the integration name and version (no personal data) so kwtSMS can see which integrations are in use.

### Fixed
- WHMCS 9.1: gateway login, test SMS and log actions in the admin area work again.

### Changed
- Settings placeholders show 965XXXXXXXX instead of a sample mobile number; API send tests read KWTSMS_TEST_NUMBER and unit tests use 99912345678

### Docs
- Settings: test mode hint now says test messages hold their credits until you delete them from the Sending Queue (previously said no credits were consumed)

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
