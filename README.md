<picture>
  <source media="(prefers-color-scheme: dark)" srcset=".github/assets/readme-header-dark.png">
  <img alt="kwtSMS: SMS gateway for Kuwait" src=".github/assets/readme-header-light.png">
</picture>

# kwtSMS for WHMCS

[![WHMCS](https://img.shields.io/badge/WHMCS-8.12%2B-4a9ed6)](https://marketplace.whmcs.com)
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white)](https://php.net)
[![Release](https://img.shields.io/github/v/release/kwtsms/kwtsms-whmcs)](https://github.com/kwtsms/kwtsms-whmcs/releases)
[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](https://opensource.org/license/mit)

Send SMS to your WHMCS clients when an invoice is paid or they register, and to your admins on new orders, through
kwtSMS, the Kuwaiti SMS gateway. Free and open source.

kwtSMS delivers to Zain, Ooredoo, stc and Virgin in Kuwait and to more than 200 countries, with your own Sender ID,
free API testing and credits that do not expire.

## Features

**Notifications**
- SMS to clients on invoice paid and on new registration.
- SMS to admins on new orders.
- English and Arabic templates with placeholders.

**Admin**
- Gateway management: login, balance, Sender ID choice, coverage sync.
- Test SMS through the full send pipeline, with the result shown on the page.
- Send log, attempt log (security events) and debug log.
- Test mode: messages are queued on kwtSMS and not delivered.

## Requirements

- WHMCS 8.12.x, 8.13.x or 9.0.x, PHP 8.2 or 8.3 with `allow_url_fopen` on.
- International phone number input enabled in WHMCS settings.
- A kwtSMS account (free test account, no papers): https://www.kwtsms.com/cportal/signup/

## Installation

1. Upload `modules/addons/kwtsms/` to the `modules/addons/` folder of your WHMCS.
2. In the WHMCS admin go to **Setup > Addon Modules**, find **kwtSMS** and click **Activate**.
3. Click **Configure** and give the admin roles that should use it access.

## Setup

1. Open the kwtSMS addon and go to **Settings**.
2. Enter the API username and password of your kwtSMS account (not your mobile number) and click **Login**.
3. Choose your Sender ID and the default country code (for example `965` for Kuwait).
4. Keep test mode on for a first try, send a test SMS, then turn the gateway on and test mode off.

Test mode messages are not delivered, but their credits are held until you delete them from the Sending Queue in your
kwtSMS account.

## Arabic

The addon screens and the SMS templates come in English and Arabic. Arabic SMS are sent as Unicode: one SMS page holds
70 Arabic characters, against 160 English ones.

## FAQ

**Which username do I use?** The API username and password of your kwtSMS account, not your mobile number.

**Why do test messages not arrive?** Test mode queues messages without delivering them. Turn it off in Settings.

**What is a Sender ID?** The name shown as the sender on the phone. See the
[Sender ID help](https://www.kwtsms.com/sender-id-help.html).

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

## Support

Help: https://www.kwtsms.com/support.html | FAQ: https://www.kwtsms.com/faq/ |
API: [developers](https://www.kwtsms.com/developers.html), [API reference](https://www.kwtsms.com/api-documentation.html) |
[Issues](https://github.com/kwtsms/kwtsms-whmcs/issues)

## Security

See [SECURITY.md](SECURITY.md).

## License

MIT, (c) kwtSMS (BoxLink Center).
