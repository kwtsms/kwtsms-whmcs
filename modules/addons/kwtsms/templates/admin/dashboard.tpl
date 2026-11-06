<?php
declare(strict_types=1);
use WHMCS\Database\Capsule;

// Fetch recent SMS activity (last 8 rows)
$recentLogs = [];
try {
    $recentLogs = Capsule::table('mod_kwtsms_log')
        ->orderBy('created_at', 'desc')
        ->limit(8)
        ->get(['id', 'event', 'phone', 'recipient_type', 'result', 'error_code', 'balance_after', 'created_at'])
        ->toArray();
} catch (\Exception $e) {
    // Table may not exist yet
}

// Count sent/failed today
$sentToday   = 0;
$failedToday = 0;
try {
    $today = date('Y-m-d');
    $sentToday   = Capsule::table('mod_kwtsms_log')->where('result', 'OK')->whereDate('created_at', $today)->count();
    $failedToday = Capsule::table('mod_kwtsms_log')->where('result', '!=', 'OK')->whereDate('created_at', $today)->count();
} catch (\Exception $e) {
    // Ignore
}
?>
<?php include __DIR__ . '/_style.php'; ?>
<div class="kwt-wrap">
<?php include __DIR__ . '/_nav.php'; ?>

<?php if (!$isConfigured): ?>
<div class="kwt-alert kwt-alert-info">
    Gateway not configured. <a href="<?= htmlspecialchars($modulelink) ?>&tab=settings" style="color:#FFA200;font-weight:700">Go to Settings</a> to enter your kwtSMS API credentials.
</div>
<?php endif; ?>

<?php if ($gateway_enabled !== '1' && $isConfigured): ?>
<div class="kwt-alert kwt-alert-info">
    Gateway is disabled. SMS notifications will not be sent until you enable it in <a href="<?= htmlspecialchars($modulelink) ?>&tab=settings" style="color:#FFA200;font-weight:700">Settings</a>.
</div>
<?php endif; ?>

<!-- Stat grid -->
<div class="kwt-stat-grid">
    <div class="kwt-stat">
        <div class="kwt-stat-label">Gateway</div>
        <div class="kwt-stat-value" style="font-size:14px;margin-top:4px">
            <?php if ($gateway_enabled === '1'): ?>
                <span class="kwt-badge kwt-badge-on">Active</span>
            <?php else: ?>
                <span class="kwt-badge kwt-badge-off">Disabled</span>
            <?php endif; ?>
        </div>
    </div>
    <div class="kwt-stat">
        <div class="kwt-stat-label">Test Mode</div>
        <div class="kwt-stat-value" style="font-size:14px;margin-top:4px">
            <?php if ($test_mode === '1'): ?>
                <span class="kwt-badge kwt-badge-test">ON</span>
            <?php else: ?>
                <span class="kwt-badge kwt-badge-warn">OFF</span>
            <?php endif; ?>
        </div>
    </div>
    <div class="kwt-stat">
        <div class="kwt-stat-label">Balance</div>
        <div class="kwt-stat-value"><?= $last_balance !== '' ? number_format((float) $last_balance, 2) : '-' ?></div>
        <div style="font-size:11px;color:#aaa;margin-top:4px">credits</div>
    </div>
    <div class="kwt-stat">
        <div class="kwt-stat-label">Sent Today</div>
        <div class="kwt-stat-value"><?= $sentToday ?></div>
    </div>
    <div class="kwt-stat">
        <div class="kwt-stat-label">Failed Today</div>
        <div class="kwt-stat-value" style="<?= $failedToday > 0 ? 'color:#e74c3c' : '' ?>"><?= $failedToday ?></div>
    </div>
    <div class="kwt-stat">
        <div class="kwt-stat-label">Last Sync</div>
        <div class="kwt-stat-value" style="font-size:13px"><?= $last_sync !== '' ? date('M j, H:i', strtotime($last_sync)) : '-' ?></div>
    </div>
</div>

<!-- Quick Actions -->
<div class="kwt-card">
    <div class="kwt-card-title">Quick Actions</div>
    <div class="kwt-actions-row">
        <button class="btn-kwt-outline" id="kwt-btn-sync" onclick="kwtQuickSync()" <?= !$isConfigured ? 'disabled title="Configure gateway first"' : '' ?>>
            Sync Now
        </button>
        <?php if ($gateway_enabled === '1'): ?>
        <button class="btn-kwt-muted" onclick="kwtToggleGateway('0')">Disable Gateway</button>
        <?php else: ?>
        <button class="btn-kwt" onclick="kwtToggleGateway('1')" <?= !$isConfigured ? 'disabled title="Configure gateway first"' : '' ?>>Enable Gateway</button>
        <?php endif; ?>
        <a href="<?= htmlspecialchars($modulelink) ?>&tab=settings" class="btn-kwt-muted">Settings</a>
        <a href="<?= htmlspecialchars($modulelink) ?>&tab=logs" class="btn-kwt-muted">View All Logs</a>
    </div>
    <div id="kwt-dash-msg" class="kwt-msg"></div>
</div>

<!-- Recent Activity -->
<div class="kwt-card">
    <div class="kwt-card-title">Recent SMS Activity</div>
    <?php if (empty($recentLogs)): ?>
    <p style="color:#888;font-size:13px;margin:0">No SMS activity yet. Logs will appear here once your first message is sent.</p>
    <?php else: ?>
    <table class="kwt-table">
        <thead>
            <tr>
                <th>Time</th>
                <th>Event</th>
                <th>Phone</th>
                <th>Type</th>
                <th>Result</th>
                <th>Balance After</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($recentLogs as $row): ?>
            <tr>
                <td style="color:#888;white-space:nowrap"><?= htmlspecialchars(date('M j H:i', strtotime($row->created_at))) ?></td>
                <td><code style="font-size:11px;color:#666"><?= htmlspecialchars($row->event) ?></code></td>
                <td style="font-family:monospace;font-size:12px"><?= htmlspecialchars($row->phone) ?></td>
                <td>
                    <?php if ($row->recipient_type === 'admin'): ?>
                        <span class="kwt-badge kwt-badge-test" style="font-size:10px">Admin</span>
                    <?php else: ?>
                        <span style="font-size:11px;color:#888">Client</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($row->result === 'OK'): ?>
                        <span class="kwt-result-ok">OK</span>
                    <?php else: ?>
                        <span class="kwt-result-err" title="<?= htmlspecialchars($row->error_code ?? '') ?>">FAIL</span>
                        <?php if ($row->error_code): ?>
                            <span style="font-size:11px;color:#aaa;margin-left:4px"><?= htmlspecialchars($row->error_code) ?></span>
                        <?php endif; ?>
                    <?php endif; ?>
                </td>
                <td style="font-size:12px;color:#888"><?= $row->balance_after !== null ? number_format((float) $row->balance_after, 2) : '-' ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<!-- Gateway info footer -->
<?php if ($isConfigured): ?>
<div style="font-size:12px;color:#aaa;padding:0 4px">
    Connected as <strong style="color:#434345"><?= htmlspecialchars($api_username) ?></strong>
    &nbsp;|&nbsp; Sender: <strong style="color:#434345"><?= htmlspecialchars($selected_senderid ?: 'None selected') ?></strong>
    &nbsp;|&nbsp; Country code: <strong style="color:#434345">+<?= htmlspecialchars($default_country_code ?: '965') ?></strong>
</div>
<?php endif; ?>

</div>

<script>
(function(){
const kwtML    = <?= json_encode($modulelink) ?>;
const kwtToken = <?= json_encode($whmcs_token) ?>;
const kwtAjax  = kwtML + '&ajax=gateway_action';

function showMsg(el, msg, type) {
    el.style.display = 'block';
    el.className = 'kwt-msg kwt-alert-' + type;
    el.textContent = msg;
}

window.kwtQuickSync = async function() {
    const btn = document.getElementById('kwt-btn-sync');
    const msg = document.getElementById('kwt-dash-msg');
    btn.disabled = true;
    btn.textContent = 'Syncing...';
    try {
        const fd = new FormData();
        fd.append('action', 'reload');
        fd.append('token', kwtToken);
        const r = await fetch(kwtAjax, {method:'POST', body:fd});
        const d = await r.json();
        if (d.success) {
            showMsg(msg, 'Sync complete. Balance: ' + (d.balance !== undefined ? d.balance + ' credits' : '?'), 'success');
            setTimeout(() => location.reload(), 1200);
        } else {
            showMsg(msg, 'Sync failed: ' + (d.error || 'Unknown error'), 'danger');
        }
    } catch(e) {
        showMsg(msg, 'Request failed.', 'danger');
    }
    btn.disabled = false;
    btn.textContent = 'Sync Now';
};

window.kwtToggleGateway = async function(val) {
    const msg = document.getElementById('kwt-dash-msg');
    try {
        const fd = new FormData();
        fd.append('action', 'save_settings');
        fd.append('token', kwtToken);
        fd.append('gateway_enabled', val);
        const r = await fetch(kwtAjax, {method:'POST', body:fd});
        const d = await r.json();
        if (d.success) {
            location.reload();
        } else {
            showMsg(msg, 'Failed: ' + (d.error || 'Unknown error'), 'danger');
        }
    } catch(e) {
        showMsg(msg, 'Request failed.', 'danger');
    }
};
})();
</script>
