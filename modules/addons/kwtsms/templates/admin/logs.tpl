<?php
declare(strict_types=1);
use WHMCS\Database\Capsule;

// Sub-tab: sms | security | debug
$allowed  = ['sms', 'security', 'debug'];
$logtab   = isset($_GET['logtab']) && in_array($_GET['logtab'], $allowed, true) ? $_GET['logtab'] : 'sms';
$page     = max(1, (int) ($_GET['page'] ?? 1));
$perPage  = 25;
$offset   = ($page - 1) * $perPage;

$tableMap = [
    'sms'      => 'mod_kwtsms_log',
    'security' => 'mod_kwtsms_attempts',
    'debug'    => 'mod_kwtsms_debug_log',
];
$currentTable = $tableMap[$logtab];

$rows  = [];
$total = 0;
try {
    $total = Capsule::table($currentTable)->count();
    $rows  = Capsule::table($currentTable)
        ->orderBy('created_at', 'desc')
        ->offset($offset)
        ->limit($perPage)
        ->get()
        ->toArray();
} catch (\Exception $e) {
    // Table might not exist
}

$totalPages = $total > 0 ? (int) ceil($total / $perPage) : 1;

// Build a URL helper
$baseUrl = htmlspecialchars($modulelink) . '&tab=logs&logtab=' . $logtab;
$pageUrl = function(int $p) use ($modulelink, $logtab): string {
    return htmlspecialchars($modulelink) . '&tab=logs&logtab=' . $logtab . '&page=' . $p;
};
?>
<?php include __DIR__ . '/_style.php'; ?>
<div class="kwt-wrap">
<?php include __DIR__ . '/_nav.php'; ?>

<div class="kwt-card">
    <!-- Sub-tab nav -->
    <div class="kwt-subtabs">
        <a href="<?= htmlspecialchars($modulelink) ?>&tab=logs&logtab=sms"
           class="<?= $logtab === 'sms' ? 'active' : '' ?>">SMS Log</a>
        <a href="<?= htmlspecialchars($modulelink) ?>&tab=logs&logtab=security"
           class="<?= $logtab === 'security' ? 'active' : '' ?>">Security Log</a>
        <a href="<?= htmlspecialchars($modulelink) ?>&tab=logs&logtab=debug"
           class="<?= $logtab === 'debug' ? 'active' : '' ?>">Debug Log</a>
    </div>

    <!-- Header row -->
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:16px">
        <div style="font-size:13px;color:#888">
            <?= number_format($total) ?> record<?= $total !== 1 ? 's' : '' ?>
            <?php if ($total > $perPage): ?>
            &nbsp;|&nbsp; Page <?= $page ?> of <?= $totalPages ?>
            <?php endif; ?>
        </div>
        <button class="btn-kwt-danger" onclick="kwtClearLog('<?= $currentTable ?>')">
            Clear All
        </button>
    </div>

    <?php if (empty($rows)): ?>
    <p style="color:#888;font-size:13px;margin:20px 0">No records found.</p>

    <?php elseif ($logtab === 'sms'): ?>
    <div style="overflow-x:auto">
    <table class="kwt-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Time</th>
                <th>Event</th>
                <th>Phone</th>
                <th>Type</th>
                <th>Result</th>
                <th>Error</th>
                <th>Msg ID</th>
                <th>Balance After</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td style="color:#aaa;font-size:11px"><?= $row->id ?></td>
                <td style="white-space:nowrap;font-size:12px;color:#888"><?= htmlspecialchars(date('M j, H:i', strtotime($row->created_at))) ?></td>
                <td><code style="font-size:11px;color:#666;white-space:nowrap"><?= htmlspecialchars($row->event) ?></code></td>
                <td style="font-family:monospace;font-size:12px;white-space:nowrap"><?= htmlspecialchars($row->phone) ?></td>
                <td>
                    <span style="font-size:11px;color:#888"><?= htmlspecialchars($row->recipient_type) ?></span>
                </td>
                <td>
                    <?php if ($row->result === 'OK'): ?>
                        <span class="kwt-result-ok">OK</span>
                    <?php else: ?>
                        <span class="kwt-result-err">FAIL</span>
                    <?php endif; ?>
                </td>
                <td style="font-size:11px;color:#aaa"><?= htmlspecialchars($row->error_code ?? '-') ?></td>
                <td style="font-family:monospace;font-size:10px;color:#aaa;max-width:140px;overflow:hidden;text-overflow:ellipsis" title="<?= htmlspecialchars($row->msgid ?? '') ?>"><?= $row->msgid ? htmlspecialchars(substr($row->msgid, 0, 16)) . '...' : '-' ?></td>
                <td style="font-size:12px;color:#888"><?= $row->balance_after !== null ? number_format((float)$row->balance_after, 2) : '-' ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>

    <?php elseif ($logtab === 'security'): ?>
    <div style="overflow-x:auto">
    <table class="kwt-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Time</th>
                <th>Action</th>
                <th>Event</th>
                <th>Phone</th>
                <th>IP</th>
                <th>Client</th>
                <th>Detail</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td style="color:#aaa;font-size:11px"><?= $row->id ?></td>
                <td style="white-space:nowrap;font-size:12px;color:#888"><?= htmlspecialchars(date('M j, H:i', strtotime($row->created_at))) ?></td>
                <td><code style="font-size:11px;color:#e74c3c;white-space:nowrap"><?= htmlspecialchars($row->action) ?></code></td>
                <td style="font-size:11px;color:#666"><?= htmlspecialchars($row->event) ?></td>
                <td style="font-family:monospace;font-size:12px"><?= htmlspecialchars($row->phone) ?></td>
                <td style="font-family:monospace;font-size:11px;color:#888"><?= htmlspecialchars($row->ip) ?></td>
                <td style="font-size:12px;color:#888"><?= $row->clientid ? '#' . $row->clientid : '-' ?></td>
                <td style="font-size:12px;color:#666;max-width:200px" title="<?= htmlspecialchars($row->detail ?? '') ?>"><?= htmlspecialchars(mb_substr($row->detail ?? '', 0, 60)) ?><?= mb_strlen($row->detail ?? '') > 60 ? '...' : '' ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>

    <?php elseif ($logtab === 'debug'): ?>
    <div style="overflow-x:auto">
    <table class="kwt-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Time</th>
                <th>Level</th>
                <th>Function</th>
                <th>Message</th>
                <th>Context</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $levelColors = ['info' => '#79CCF2', 'warning' => '#f39c12', 'error' => '#e74c3c'];
        foreach ($rows as $row):
            $lvlColor = $levelColors[$row->level] ?? '#888';
        ?>
            <tr>
                <td style="color:#aaa;font-size:11px"><?= $row->id ?></td>
                <td style="white-space:nowrap;font-size:12px;color:#888"><?= htmlspecialchars(date('M j, H:i:s', strtotime($row->created_at))) ?></td>
                <td><span class="kwt-badge" style="background:<?= $lvlColor ?>;color:#fff;font-size:10px"><?= strtoupper(htmlspecialchars($row->level)) ?></span></td>
                <td style="font-family:monospace;font-size:11px;color:#666;max-width:180px;word-break:break-all"><?= htmlspecialchars($row->function) ?></td>
                <td style="font-size:12px"><?= htmlspecialchars($row->message) ?></td>
                <td style="font-size:11px;font-family:monospace;color:#aaa;max-width:200px">
                    <?php if ($row->context): ?>
                    <span title="<?= htmlspecialchars($row->context) ?>"><?= htmlspecialchars(mb_substr($row->context, 0, 60)) ?><?= mb_strlen($row->context) > 60 ? '...' : '' ?></span>
                    <?php else: ?>-<?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="kwt-pagination">
        <?php if ($page > 1): ?>
        <a href="<?= $pageUrl($page - 1) ?>">&laquo; Prev</a>
        <?php endif; ?>

        <?php
        $start = max(1, $page - 2);
        $end   = min($totalPages, $page + 2);
        for ($p = $start; $p <= $end; $p++):
        ?>
            <?php if ($p === $page): ?>
            <span class="current"><?= $p ?></span>
            <?php else: ?>
            <a href="<?= $pageUrl($p) ?>"><?= $p ?></a>
            <?php endif; ?>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
        <a href="<?= $pageUrl($page + 1) ?>">Next &raquo;</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div id="kwt-log-msg" class="kwt-msg"></div>
</div>

</div>

<script>
(function(){
const kwtML    = <?= json_encode($modulelink) ?>;
const kwtToken = <?= json_encode($whmcs_token) ?>;
const kwtClear = kwtML + '&ajax=clear_log';

window.kwtClearLog = async function(table) {
    if (!confirm('Clear all records from this log? This cannot be undone.')) return;
    const fd = new FormData();
    fd.append('token', kwtToken);
    fd.append('log', table);
    try {
        const r = await fetch(kwtClear, {method: 'POST', body: fd});
        const d = await r.json();
        if (d.success) {
            location.reload();
        } else {
            const el = document.getElementById('kwt-log-msg');
            el.style.display = 'block';
            el.className = 'kwt-msg kwt-alert-danger';
            el.textContent = d.error || 'Failed to clear log.';
        }
    } catch(e) {
        const el = document.getElementById('kwt-log-msg');
        el.style.display = 'block';
        el.className = 'kwt-msg kwt-alert-danger';
        el.textContent = 'Request failed.';
    }
};
})();
</script>
