<?php
declare(strict_types=1);
use KwtSMS\WHMCS\GatewayManager;

// Build sender ID list for dropdown
$senderIdList = [];
if ($senderids_cache !== '') {
    $senderIdList = array_filter(array_map('trim', explode(',', $senderids_cache)));
}
?>
<?php include __DIR__ . '/_style.php'; ?>
<div class="kwt-wrap">
<?php include __DIR__ . '/_nav.php'; ?>

<div class="kwt-row">
<div class="kwt-col" style="max-width:620px">

<!-- Gateway Credentials -->
<div class="kwt-card" id="kwt-cred-card">
    <div class="kwt-card-title">Gateway Credentials</div>
    <?php if (!$isConfigured): ?>
    <!-- Login form -->
    <div id="kwt-login-form">
        <div class="kwt-form-group">
            <label for="kwt-username">kwtSMS API Username</label>
            <input type="text" id="kwt-username" name="username" placeholder="Your API username" autocomplete="off">
        </div>
        <div class="kwt-form-group">
            <label for="kwt-password">kwtSMS API Password</label>
            <input type="password" id="kwt-password" name="password" placeholder="Your API password" autocomplete="off">
            <div class="kwt-notice">Login using your kwtSMS API credentials, not your account mobile number.</div>
        </div>
        <button class="btn-kwt" id="kwt-login-btn" onclick="kwtLogin()">Connect to kwtSMS</button>
        <div id="kwt-login-msg" class="kwt-msg"></div>
    </div>
    <?php else: ?>
    <!-- Connected state -->
    <div id="kwt-connected-state">
        <table style="font-size:13px;border-collapse:collapse">
            <tr>
                <td style="padding:4px 0;color:#888;width:140px;font-family:'Montserrat',sans-serif;font-size:11px;text-transform:uppercase;letter-spacing:.3px">Status</td>
                <td><span class="kwt-badge kwt-badge-on">Connected</span></td>
            </tr>
            <tr>
                <td style="padding:4px 0;color:#888;font-family:'Montserrat',sans-serif;font-size:11px;text-transform:uppercase;letter-spacing:.3px">Username</td>
                <td style="font-weight:700"><?= htmlspecialchars($api_username) ?></td>
            </tr>
            <tr>
                <td style="padding:4px 0;color:#888;font-family:'Montserrat',sans-serif;font-size:11px;text-transform:uppercase;letter-spacing:.3px">Balance</td>
                <td style="font-weight:700"><?= $last_balance !== '' ? number_format((float) $last_balance, 2) . ' credits' : '-' ?></td>
            </tr>
            <tr>
                <td style="padding:4px 0;color:#888;font-family:'Montserrat',sans-serif;font-size:11px;text-transform:uppercase;letter-spacing:.3px">Last Sync</td>
                <td><?= htmlspecialchars($last_sync ?: 'Never') ?></td>
            </tr>
        </table>
        <div class="kwt-actions-row" style="margin-top:16px">
            <button class="btn-kwt-outline" id="kwt-sync-btn" onclick="kwtReload()">Sync Balance &amp; Sender IDs</button>
            <button class="btn-kwt-danger" onclick="kwtLogout()">Disconnect</button>
        </div>
        <div id="kwt-cred-msg" class="kwt-msg"></div>
    </div>
    <?php endif; ?>
</div>

<!-- SMS Settings -->
<div class="kwt-card">
    <div class="kwt-card-title">SMS Settings</div>

    <div class="kwt-form-group">
        <label for="kwt-country-code">Default Country Code</label>
        <input type="text" id="kwt-country-code" value="<?= htmlspecialchars($default_country_code ?: '965') ?>" placeholder="965" maxlength="10" style="max-width:120px">
        <div class="kwt-notice">Digits only. Used when a phone number has fewer than 10 digits. Default: 965 (Kuwait).</div>
    </div>

    <?php if (!empty($senderIdList)): ?>
    <div class="kwt-form-group">
        <label for="kwt-senderid">Sender ID</label>
        <select id="kwt-senderid">
            <?php foreach ($senderIdList as $sid): ?>
            <option value="<?= htmlspecialchars($sid) ?>" <?= $selected_senderid === $sid ? 'selected' : '' ?>><?= htmlspecialchars($sid) ?></option>
            <?php endforeach; ?>
        </select>
        <div class="kwt-notice">Production sender ID: never use KWT-SMS (blocked on Virgin Kuwait). Register a private sender ID at kwtsms.com.</div>
    </div>
    <?php elseif ($isConfigured): ?>
    <div class="kwt-form-group">
        <label>Sender ID</label>
        <input type="text" id="kwt-senderid" value="<?= htmlspecialchars($selected_senderid) ?>" placeholder="e.g. MYCOMPANY" maxlength="11">
        <div class="kwt-notice">Sync sender IDs by clicking "Sync Balance &amp; Sender IDs" above, or enter manually.</div>
    </div>
    <?php endif; ?>

    <hr class="kwt-separator">

    <div class="kwt-toggle-row">
        <label class="kwt-switch">
            <input type="checkbox" id="kwt-gateway-enabled" <?= $gateway_enabled === '1' ? 'checked' : '' ?>>
            <span class="kwt-slider"></span>
        </label>
        <div class="kwt-toggle-info">
            <h4>Enable Gateway</h4>
            <p>When off, all SMS sends are silently skipped.</p>
        </div>
    </div>

    <div class="kwt-toggle-row">
        <label class="kwt-switch">
            <input type="checkbox" id="kwt-test-mode" <?= $test_mode === '1' ? 'checked' : '' ?>>
            <span class="kwt-slider"></span>
        </label>
        <div class="kwt-toggle-info">
            <h4>Test Mode</h4>
            <?php /* 2026-10-22 15:38 +03: test mode holds credits until the queued test is deleted; old text said no credits were consumed. */ ?>
            <p>Messages are queued but not delivered. Credits are held until you delete them from the Sending Queue. Disable before going live.</p>
        </div>
    </div>

    <div style="margin-top:16px">
        <button class="btn-kwt" onclick="kwtSaveSmsSettings()">Save SMS Settings</button>
        <div id="kwt-sms-msg" class="kwt-msg"></div>
    </div>
</div>

<!-- Admin Alerts -->
<div class="kwt-card">
    <div class="kwt-card-title">Admin Alert Settings</div>

    <div class="kwt-form-group">
        <label for="kwt-admin-phones">Admin Phone Numbers</label>
        <?php /* 2026-11-05 11:20 +03: phone placeholders and examples in this file now use 965XXXXXXXX instead of a sample mobile number (sample numbers belong to real people), int-testnumber */ ?>
        <textarea id="kwt-admin-phones" rows="4" placeholder="One phone number per line&#10;e.g. 965XXXXXXXX"><?= htmlspecialchars($admin_phones ?? '') ?></textarea>
        <div class="kwt-notice">One number per line, international format (e.g. 965XXXXXXXX). These receive admin alert SMS.</div>
    </div>

    <div class="kwt-toggle-row">
        <label class="kwt-switch">
            <input type="checkbox" id="kwt-admin-evt-order" <?= ($admin_evt_new_order ?? '') === '1' ? 'checked' : '' ?>>
            <span class="kwt-slider"></span>
        </label>
        <div class="kwt-toggle-info">
            <h4>New Order Alert</h4>
            <p>Send SMS to admin phones when a new order is placed via shopping cart.</p>
        </div>
    </div>

    <div style="margin-top:16px">
        <button class="btn-kwt" onclick="kwtSaveAdminSettings()">Save Admin Settings</button>
        <div id="kwt-admin-msg" class="kwt-msg"></div>
    </div>
</div>

<!-- Debug Settings -->
<div class="kwt-card">
    <div class="kwt-card-title">Debug Settings</div>
    <div class="kwt-toggle-row">
        <label class="kwt-switch">
            <input type="checkbox" id="kwt-debug-log" <?= $debug_log_enabled === '1' ? 'checked' : '' ?>>
            <span class="kwt-slider"></span>
        </label>
        <div class="kwt-toggle-info">
            <h4>Debug Log</h4>
            <p>Write verbose debug entries to the Debug Log tab. Disable in production to reduce DB writes.</p>
        </div>
    </div>
    <div style="margin-top:16px">
        <button class="btn-kwt" onclick="kwtSaveDebugSettings()">Save Debug Settings</button>
        <div id="kwt-debug-msg" class="kwt-msg"></div>
    </div>
</div>

</div><!-- .kwt-col -->

<!-- Test SMS sidebar -->
<div class="kwt-col" style="max-width:320px">
<div class="kwt-card">
    <div class="kwt-card-title">Send Test SMS</div>
    <?php if (!$isConfigured): ?>
    <p style="font-size:13px;color:#888;margin:0">Configure gateway credentials first.</p>
    <?php else: ?>
    <div class="kwt-form-group">
        <label for="kwt-test-phone">Phone Number</label>
        <input type="text" id="kwt-test-phone" placeholder="e.g. 965XXXXXXXX" style="max-width:100%">
        <div class="kwt-notice">International format, digits only.</div>
    </div>
    <button class="btn-kwt" style="width:100%" onclick="kwtSendTestSms()">Send Test SMS</button>
    <div id="kwt-test-msg" class="kwt-msg" style="margin-top:10px"></div>
    <div style="margin-top:14px;padding:10px;background:#f8f8f8;border-radius:4px;font-size:12px;color:#888;line-height:1.5">
        Test mode is currently <strong style="color:<?= $test_mode === '1' ? '#27ae60' : '#e74c3c' ?>"><?= $test_mode === '1' ? 'ON' : 'OFF' ?></strong>.
        <?php if ($test_mode === '1'): ?>
        The test SMS will queue but not be delivered. Check your <a href="https://www.kwtsms.com/account/" target="_blank" style="color:#FFA200">kwtSMS dashboard queue</a> to confirm receipt.
        <?php else: ?>
        Test mode is off: this message will be delivered and credits will be consumed.
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>
</div><!-- .kwt-col -->

</div><!-- .kwt-row -->
</div><!-- .kwt-wrap -->

<script>
(function(){
const kwtML    = <?= json_encode($modulelink) ?>;
const kwtToken = <?= json_encode($whmcs_token) ?>;
const kwtAjax  = kwtML + '&ajax=gateway_action';
const kwtTest  = kwtML + '&ajax=test_sms';

function showMsg(id, msg, type) {
    const el = document.getElementById(id);
    if (!el) return;
    el.style.display = 'block';
    el.className = 'kwt-msg kwt-alert-' + type;
    el.textContent = msg;
}

async function post(url, data) {
    const fd = new FormData();
    fd.append('token', kwtToken);
    for (const [k, v] of Object.entries(data)) fd.append(k, v);
    const r = await fetch(url, {method: 'POST', body: fd});
    return r.json();
}

window.kwtLogin = async function() {
    const btn = document.getElementById('kwt-login-btn');
    btn.disabled = true;
    btn.textContent = 'Connecting...';
    try {
        const d = await post(kwtAjax, {
            action:   'login',
            username: document.getElementById('kwt-username').value.trim(),
            password: document.getElementById('kwt-password').value,
        });
        if (d.success) {
            showMsg('kwt-login-msg', 'Connected. Balance: ' + (d.balance !== undefined ? d.balance + ' credits' : '?'), 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showMsg('kwt-login-msg', d.error || 'Login failed.', 'danger');
        }
    } catch(e) {
        showMsg('kwt-login-msg', 'Request failed.', 'danger');
    }
    btn.disabled = false;
    btn.textContent = 'Connect to kwtSMS';
};

window.kwtLogout = async function() {
    if (!confirm('Disconnect kwtSMS gateway? This will clear your credentials.')) return;
    try {
        await post(kwtAjax, {action: 'logout'});
        location.reload();
    } catch(e) {
        showMsg('kwt-cred-msg', 'Request failed.', 'danger');
    }
};

window.kwtReload = async function() {
    const btn = document.getElementById('kwt-sync-btn');
    btn.disabled = true;
    btn.textContent = 'Syncing...';
    try {
        const d = await post(kwtAjax, {action: 'reload'});
        if (d.success) {
            showMsg('kwt-cred-msg', 'Sync complete. Balance: ' + (d.balance !== undefined ? d.balance + ' credits' : '?'), 'success');
            setTimeout(() => location.reload(), 1200);
        } else {
            showMsg('kwt-cred-msg', d.error || 'Sync failed.', 'danger');
        }
    } catch(e) {
        showMsg('kwt-cred-msg', 'Request failed.', 'danger');
    }
    btn.disabled = false;
    btn.textContent = 'Sync Balance & Sender IDs';
};

window.kwtSaveSmsSettings = async function() {
    const senderEl = document.getElementById('kwt-senderid');
    const payload  = {
        action:           'save_settings',
        gateway_enabled:  document.getElementById('kwt-gateway-enabled').checked ? '1' : '0',
        test_mode:        document.getElementById('kwt-test-mode').checked        ? '1' : '0',
        default_country_code: document.getElementById('kwt-country-code').value.trim(),
    };
    if (senderEl) {
        const senderVal = senderEl.value.trim();
        // Save sender ID as a separate action so it goes through validation
        await post(kwtAjax, {action: 'save_senderid', senderid: senderVal});
    }
    try {
        const d = await post(kwtAjax, payload);
        showMsg('kwt-sms-msg', d.success ? 'Settings saved.' : (d.error || 'Save failed.'), d.success ? 'success' : 'danger');
    } catch(e) {
        showMsg('kwt-sms-msg', 'Request failed.', 'danger');
    }
};

window.kwtSaveAdminSettings = async function() {
    try {
        const d = await post(kwtAjax, {
            action:                   'save_settings',
            admin_phones:             document.getElementById('kwt-admin-phones').value,
            admin_evt_admin_new_order: document.getElementById('kwt-admin-evt-order').checked ? '1' : '0',
        });
        showMsg('kwt-admin-msg', d.success ? 'Settings saved.' : (d.error || 'Save failed.'), d.success ? 'success' : 'danger');
    } catch(e) {
        showMsg('kwt-admin-msg', 'Request failed.', 'danger');
    }
};

window.kwtSaveDebugSettings = async function() {
    try {
        const d = await post(kwtAjax, {
            action:            'save_settings',
            debug_log_enabled: document.getElementById('kwt-debug-log').checked ? '1' : '0',
        });
        showMsg('kwt-debug-msg', d.success ? 'Settings saved.' : (d.error || 'Save failed.'), d.success ? 'success' : 'danger');
    } catch(e) {
        showMsg('kwt-debug-msg', 'Request failed.', 'danger');
    }
};

window.kwtSendTestSms = async function() {
    const phone = document.getElementById('kwt-test-phone').value.trim();
    if (!phone) { showMsg('kwt-test-msg', 'Enter a phone number.', 'danger'); return; }
    const fd = new FormData();
    fd.append('token', kwtToken);
    fd.append('phone', phone);
    try {
        const r = await fetch(kwtTest, {method: 'POST', body: fd});
        const d = await r.json();
        if (d.success) {
            showMsg('kwt-test-msg', 'Test SMS sent successfully. Sent: ' + (d.sent || 1) + ' message(s).', 'success');
        } else {
            showMsg('kwt-test-msg', 'Failed: ' + (d.error || 'Unknown error'), 'danger');
        }
    } catch(e) {
        showMsg('kwt-test-msg', 'Request failed.', 'danger');
    }
};
})();
</script>
