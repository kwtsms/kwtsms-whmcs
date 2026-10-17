# Security Policy

## Reporting a Vulnerability

Report security vulnerabilities through kwtSMS support: https://www.kwtsms.com/support.html
<!-- 2026-10-18 01:10 +03: direct contact replaced with the support page link (task 344). -->

Please include:
- Description of the vulnerability
- Steps to reproduce
- Potential impact

We respond within 48 hours. Do not disclose publicly until a fix is released.

## Scope

- SQL injection via WHMCS module inputs
- Cross-site scripting (XSS) in admin templates
- Remote code execution
- API credential exposure in logs or HTML output
- Phone number enumeration via error messages
