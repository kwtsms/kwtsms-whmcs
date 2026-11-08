# End-to-end tests

Runs the kwtSMS module on a throwaway WHMCS in Docker and checks every client-side event through real WHMCS flows.

## Run

```bash
tests/e2e/run.sh /path/to/whmcs_v910_full.zip /path/to/e2e.env
tests/e2e/run.sh --upgrade-from 1.0.0 /path/to/whmcs_v910_full.zip /path/to/e2e.env
tests/e2e/run.sh -h
```

`e2e.env` (keep it private, mode 600):

```
WHMCS_LICENSE=...          # a development licence bound to http://localhost:8080 works
KWTSMS_USERNAME=...        # kwtSMS API username
KWTSMS_PASSWORD=...        # kwtSMS API password
KWTSMS_TEST_NUMBER=965XXXXXXXX
```

## What it checks

| Case | Flow |
|---|---|
| Client registration SMS | visitor registers in the client area (phone with the country dropdown) |
| New order admin alert | the client orders through the cart; the alert names the client |
| Invoice paid SMS | the order's invoice is paid; the SMS carries the amount |
| Arabic SMS | Arabic registration template |
| Event off | no SMS when the event is switched off |
| No phone | a client without a phone is skipped |
| Gateway off | no SMS at all |
| Admin test SMS | the Send Test SMS button |
| Upgrade (`--upgrade-from`) | the published release is configured, replaced by this checkout, settings unchanged |

The module is built the way the release workflow builds it. Every send is in test mode, and the test build refuses any
recipient other than `KWTSMS_TEST_NUMBER`. Each run sends about 5 test messages; they hold credits until deleted from
the kwtSMS Sending Queue. Requires Docker, Python 3, Composer and port 8080 free. Exit code 0 means every case passed. It tests the committed code (`git archive HEAD`), not uncommitted changes.
