"""
FILE:      tests/e2e/admin.py
CREATED:   2026-11-08 08:40 +03
TASK:      whmcs-e2e (Boss 2026-11-08 08:26: test all client-side events, save the tests for easy redo)
AUTHOR:    M Alhassoun
WHAT:      Drives WHMCS over HTTP the way an admin and a client do (no browser): admin login and EULA, module
           activation and role access, gateway login and settings through the module's own AJAX endpoints, client
           registration, cart checkout; then checks every kwtSMS log row. Server-side steps go through events.php.
WHY:       A rerunnable end-to-end test of every client-side event (Boss 2026-11-08 08:26).
USAGE:     python3 admin.py setup|setup_old|snapshot|upgrade|events <admin_pass_file> <env_file>   (called by run.sh)
           python3 admin.py -h
DEPENDS:   Python 3 standard library; the stack from compose.yml on http://localhost:8080.
CHANGELOG:
  2026-11-08 08:40 +03  M Alhassoun  whmcs-e2e  first version
"""
import html as H
import http.cookiejar
import json
import os
import re
import subprocess
import sys
import time
import urllib.parse
import urllib.request

B = "http://localhost:8080"
WEB = "kwtsms-whmcs-e2e-web-1"
ML = "/admin/addonmodules.php?module=kwtsms"
RESULTS = []


def env(path):
    return {m.group(1): m.group(2).strip().strip('"\'') for line in open(path)
            for m in [re.match(r'^([A-Z_]+)=(.*)$', line.strip())] if m}


class Browser:
    def __init__(self):
        self.jar = http.cookiejar.CookieJar()
        self.op = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(self.jar))

    def get(self, url):
        with self.op.open(B + url, timeout=120) as r:
            return r.read().decode('utf-8', 'replace')

    def post(self, url, data):
        req = urllib.request.Request(B + url, data=urllib.parse.urlencode(data, doseq=True).encode())
        with self.op.open(req, timeout=120) as r:
            return r.read().decode('utf-8', 'replace')


def attrs(tag):
    return {k.lower(): H.unescape(v) for k, v in re.findall(r'([a-zA-Z_:\[\]-]+)="([^"]*)"', tag)}


def form_fields(form_html):
    out = {}
    for tag in re.findall(r'<input[^>]*>', form_html):
        a = attrs(tag)
        if 'name' in a and a.get('type', 'text') not in ('checkbox', 'radio', 'submit', 'button', 'image'):
            out[a['name']] = a.get('value', '')
    return out


def token(page):
    m = re.search(r'name="token" value="([0-9a-f]+)"', page)
    if not m:
        raise SystemExit('no token on page')
    return m.group(1)


def srv(*args, stdin=None):
    """Run events.php inside the web container as www-data; returns parsed JSON. Secrets go through stdin."""
    r = subprocess.run(['docker', 'exec', '-i', '-u', 'www-data', WEB, 'php', '/e2e/events.php', *args],
                       capture_output=True, text=True, timeout=300, input=stdin or '')
    line = r.stdout.strip().splitlines()[-1] if r.stdout.strip() else ''
    try:
        return json.loads(line)
    except ValueError:
        raise SystemExit('events.php ' + args[0] + ' failed: ' + re.sub(r'<[^>]+>', ' ', r.stdout + r.stderr)[:400])


def admin_login(pw_file):
    b = Browser()
    page = b.get('/admin/login.php')
    r = b.post('/admin/dologin.php', {'token': token(page), 'username': 'e2eadmin', 'password': open(pw_file).read()})
    if 'eula-accept' in r:
        f = re.search(r'<form[^>]*frmActivateLicense.*?</form>', r, re.S).group(0)
        r = b.post('/admin/index.php?rp=/admin/eula-accept', form_fields(f))
    if 'dologin.php' in r and 'logout' not in r:
        raise SystemExit('admin login failed')
    return b


def module_ajax(b, endpoint, data):
    page = b.get(ML + '&tab=settings')
    m = re.search(r'const kwtToken = "([^"]*)"', page)
    if not m or not m.group(1):
        raise SystemExit('module page has no AJAX token')
    data = dict(data, token=m.group(1))
    return json.loads(b.post(ML + '&ajax=' + endpoint, data))


def check(name, ok, detail=''):
    RESULTS.append((name, bool(ok)))
    print(('PASS ' if ok else 'FAIL ') + name + ((': ' + detail) if detail else ''), flush=True)


def setup(pw_file, e, server_side=False):
    b = admin_login(pw_file)
    page = b.get('/admin/configaddonmods.php')
    m = re.search(r"window\.location='(/admin/configaddonmods\.php\?action=activate&(?:amp;)?module=kwtsms&(?:amp;)?token=[0-9a-f]+)'", page)
    if m:
        b.get(H.unescape(m.group(1)))
    page = b.get('/admin/configaddonmods.php')
    forms = [f for f in re.findall(r'<form[^>]*>.*?</form>', page, re.S) if 'access[kwtsms]' in f]
    if not forms:
        raise SystemExit('kwtSMS not activated (no access form)')
    act = attrs(re.match(r'<form[^>]*>', forms[0]).group(0)).get('action', '/admin/configaddonmods.php')
    data = form_fields(forms[0])
    data['access[kwtsms][1]'] = '1'
    b.post(act if act.startswith('/') else '/admin/' + act, data)
    print('module activated, Full Administrator access granted')
    srv('prepare')
    wanted = {'gateway_enabled': '1', 'test_mode': '1', 'admin_phones': e['KWTSMS_TEST_NUMBER'],
              'admin_evt_admin_new_order': '1'}
    if server_side:
        r = srv('configure', stdin=json.dumps({'username': e['KWTSMS_USERNAME'], 'password': e['KWTSMS_PASSWORD'],
                                                'settings': wanted}))
        if not r.get('login'):
            raise SystemExit('gateway login (server side) failed')
    else:
        r = module_ajax(b, 'gateway_action', {'action': 'login', 'username': e['KWTSMS_USERNAME'], 'password': e['KWTSMS_PASSWORD']})
        if not r.get('success'):
            raise SystemExit('gateway login failed: ' + str(r.get('error', ''))[:120])
        module_ajax(b, 'gateway_action', dict(wanted, action='save_settings'))
    s = srv('settings')
    if s.get('test_mode') != '1' or s.get('gateway_enabled') != '1':
        raise SystemExit('settings not saved (test mode must be on)')
    print('gateway connected, enabled, test mode on, admin phone = test number')


def register(email, e, firstname='E2E'):
    """Client-area registration as a visitor; returns the logged-in client Browser."""
    c = Browser()
    page = c.get('/register.php')
    local = e['KWTSMS_TEST_NUMBER'][3:] if e['KWTSMS_TEST_NUMBER'].startswith('965') else e['KWTSMS_TEST_NUMBER']
    data = {'token': token(page), 'register': 'true', 'firstname': firstname, 'lastname': 'Client', 'email': email,
            'country-calling-code-phonenumber': '965', 'phonenumber': local, 'companyname': '',
            'address1': 'Street 1', 'address2': '', 'city': 'Kuwait', 'state': 'Capital', 'postcode': '13001',
            'country': 'KW', 'password': 'E2e!' + str(int(time.time())) + 'xQ', 'accepttos': 'on'}
    data['password2'] = data['password']
    r = c.post('/register.php', data)
    errs = re.findall(r'<li>([^<]{3,160})</li>', r[r.find('alert-danger'):r.find('alert-danger') + 2000]) if 'alert-danger' in r else []
    if errs:
        print('  registration errors:', errs[:3])
    return c


def checkout(c, pid):  # result checked through the orders table
    c.get('/cart.php?a=add&pid=%d&billingcycle=monthly' % pid)
    page = c.get('/cart.php?a=checkout')
    data = {'token': token(page), 'submit': 'true', 'custtype': 'existing', 'paymentmethod': 'banktransfer',
            'accepttos': 'on', 'notes': ''}
    r = c.post('/cart.php?a=checkout', data)
    return 'complete' in r or 'Order Confirmation' in r or 'Order Number' in r


def rows_since(n):
    return srv('rows', str(n))


def events(pw_file, e):
    num = e['KWTSMS_TEST_NUMBER']
    pid = srv('prepare')['pid']
    stamp = str(int(time.time()))
    b = admin_login(pw_file)

    # 1. Client registration (ClientAdd) through the client area, phone entered with the country dropdown
    base = srv('prepare')['maxlog']
    email1 = 'e2e.reg.%s@example.com' % stamp
    c = register(email1, e)
    cl = srv('client', email1)
    rows = rows_since(base)
    ok = [r for r in rows if r['event'] == 'client_registration' and r['result'] == 'OK' and r['phone'] == num]
    check('client registration SMS (ClientAdd)', cl and len(ok) == 1,
          'client %s, stored phone format %s, rows %d' % (cl.get('id') if cl else None,
                                                            re.sub(r'\d', '9', (cl or {}).get('phonenumber', '')), len(rows)))

    # 2. Order through the cart (AfterShoppingCartCheckout) -> admin alert to the admin phone
    base = srv('prepare')['maxlog']
    checkout(c, int(pid))
    placed = srv('orders', str(cl['id']))['orders'] if cl else 0
    rows = rows_since(base)
    ok = [r for r in rows if r['event'] == 'admin_new_order' and r['result'] == 'OK' and r['phone'] == num
          and 'Guest' not in r['message'] and 'E2E' in r['message']]
    check('new order admin alert names the client (AfterShoppingCartCheckout)', placed == 1 and len(ok) == 1,
          'orders %s, rows %d' % (placed, len(rows)))

    # 3. Invoice paid (InvoicePaid): pay the order's invoice
    base = srv('prepare')['maxlog']
    inv = srv('lastinvoice', str(cl['id']))['invoiceid'] if cl else None
    paid = srv('pay', str(inv)) if inv else {}
    rows = rows_since(base)
    ok = [r for r in rows if r['event'] == 'invoice_paid' and r['result'] == 'OK' and r['phone'] == num
          and '10.00' in r['message']]
    check('invoice paid SMS (InvoicePaid)', paid.get('status') == 'Paid' and len(ok) == 1,
          'invoice %s %s, rows %d' % (inv, paid.get('status'), len(rows)))

    # 4. Arabic template on registration: Unicode path
    ar = 'مرحبا {firstname}، تم إنشاء حسابك في {companyname}.'
    r = module_ajax(b, 'gateway_action', {'action': 'save_templates', 'evt_client_registration': '1', 'tpl_client_registration': ar})
    base = srv('prepare')['maxlog']
    register('e2e.ar.%s@example.com' % stamp, e, firstname='Ali')
    rows = rows_since(base)
    ok = [r for r in rows if r['event'] == 'client_registration' and r['result'] == 'OK' and 'مرحبا' in r['message']]
    check('Arabic registration SMS', r.get('success') and len(ok) == 1, 'rows %d' % len(rows))

    # 5. Event switched off: no SMS
    module_ajax(b, 'gateway_action', {'action': 'save_templates', 'evt_client_registration': '0'})
    base = srv('prepare')['maxlog']
    register('e2e.off.%s@example.com' % stamp, e)
    rows = rows_since(base)
    check('registration with the event off sends nothing', len(rows) == 0, 'rows %d' % len(rows))
    module_ajax(b, 'gateway_action', {'action': 'save_templates', 'evt_client_registration': '1'})

    # 6. Client without a phone (created through the API): skipped, no SMS
    base = srv('prepare')['maxlog']
    a = srv('addclient', 'e2e.nophone.%s@example.com' % stamp)
    rows = rows_since(base)
    check('client without a phone is skipped', a.get('result') == 'success' and len(rows) == 0,
          'addclient %s, rows %d' % (a.get('result'), len(rows)))

    # 7. Gateway disabled: no SMS for any event
    module_ajax(b, 'gateway_action', {'action': 'save_settings', 'gateway_enabled': '0'})
    base = srv('prepare')['maxlog']
    register('e2e.gwoff.%s@example.com' % stamp, e)
    rows = rows_since(base)
    check('gateway off sends nothing', len(rows) == 0, 'rows %d' % len(rows))
    module_ajax(b, 'gateway_action', {'action': 'save_settings', 'gateway_enabled': '1'})

    # 8. Admin test SMS from the settings page
    base = srv('prepare')['maxlog']
    r = module_ajax(b, 'test_sms', {'phone': num})
    rows = rows_since(base)
    check('admin test SMS', r.get('success') and len(rows) == 1 and rows[0]['result'] == 'OK', 'rows %d' % len(rows))

    sent = sum(1 for r in rows_since(0) if r['result'] == 'OK')
    failed = [n for n, ok in RESULTS if not ok]
    print('%d/%d cases passed; %d test=1 messages sent (each holds credits until deleted from the Sending Queue)'
          % (len(RESULTS) - len(failed), len(RESULTS), sent))
    sys.exit(1 if failed else 0)


def setup_old(pw_file, e):
    """Old release: activate through the admin, configure through GatewayManager (its screens may not run here)."""
    setup(pw_file, e, server_side=True)


def snapshot(pw_file, e):
    print(json.dumps(srv('settings'), sort_keys=True, indent=1, ensure_ascii=False))


def upgrade(pw_file, e):
    b = admin_login(pw_file)
    b.get('/admin/configaddonmods.php')
    b.get(ML)
    v = srv('version').get('version')
    want = os.environ.get('E2E_EXPECT_VERSION', '')
    check('upgrade: WHMCS records the new module version', bool(want) and v == want, 'version %s, expected %s' % (v, want))


if __name__ == '__main__':
    if len(sys.argv) < 2 or sys.argv[1] in ('-h', '--help'):
        print(__doc__)
        sys.exit(0)
    if len(sys.argv) != 4 or sys.argv[1] not in ('setup', 'setup_old', 'snapshot', 'upgrade', 'events'):
        print('usage: admin.py setup|setup_old|snapshot|upgrade|events <admin_pass_file> <env_file>')
        sys.exit(2)
    globals()[sys.argv[1]](sys.argv[2], env(sys.argv[3]))
