# kwtSMS WHMCS Module

[![PHP 8.2+](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white)](https://php.net)
[![WHMCS 8.12+](https://img.shields.io/badge/WHMCS-8.12%2B-4a9ed6)](https://whmcs.com)
[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)
[![kwtSMS API](https://img.shields.io/badge/kwtSMS-API%20v4.1-FFA200?logo=data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjQiIGhlaWdodD0iMjQiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHRleHQgeD0iNCIgeT0iMTgiIGZvbnQtc2l6ZT0iMTYiIGZpbGw9IndoaXRlIj5TTVk8L3RleHQ+PC9zdmc+)](https://www.kwtsms.com)
[![PRs Welcome](https://img.shields.io/badge/PRs-welcome-brightgreen.svg)](https://github.com/kwtsms/kwtsms-whmcs/pulls)

Send automated SMS notifications to your WHMCS clients and admins through kwtSMS. Free and open source.

## About kwtSMS

[kwtSMS](https://www.kwtsms.com) is a Kuwait-based SMS gateway that delivers messages to 200+ countries. It supports dedicated sender IDs, Arabic messages, balance tracking, and a REST API built for developers.

[Sign up for a kwtSMS account](https://www.kwtsms.com/account/signup/) to get started.

## Features

- Automated SMS to clients on invoice paid and new registration
- Admin SMS alerts on new orders
- Gateway management: login, balance, sender ID selection, coverage sync
- Test SMS with full pipeline and inline feedback
- Full send logs, attempt logs (security events), debug logs
- English and Arabic message templates with placeholder substitution
- Kuwait market focused — Arabic critical, 70 chars/page

## Requirements

- WHMCS 8.12.x, 8.13.x, or 9.0.x
- PHP 8.2 or 8.3
- `allow_url_fopen` enabled in PHP
- kwtSMS account at kwtsms.com
- International phone number input enabled in WHMCS Settings

## Installation

1. Upload `modules/addons/kwtsms/` to your WHMCS `modules/addons/` directory
2. In WHMCS Admin: Setup > Addon Modules > kwtSMS > Activate
3. Click Configure, grant full admin permissions
4. Open the kwtSMS addon, go to Settings tab
5. Enter your kwtSMS API username and password, click Login
6. Select your Sender ID from the dropdown
7. Set Default Country Code (e.g. `965` for Kuwait)
8. Toggle Gateway to On

## Support

[www.kwtsms.com/support](https://www.kwtsms.com/support.html)
