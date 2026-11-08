<?php declare(strict_types=1); ?>
<?php include __DIR__ . '/_style.php'; ?>
<div class="kwt-wrap">
<?php include __DIR__ . '/_nav.php'; ?>

<div class="kwt-row">
<div class="kwt-col" style="max-width:640px">

<!-- Setup Guide -->
<div class="kwt-card">
    <div class="kwt-card-title">Quick Setup Guide</div>

    <div class="kwt-setup-step">
        <div class="kwt-step-num">1</div>
        <div class="kwt-step-content">
            <h4>Get your API credentials</h4>
            <p>Log in to your kwtSMS account at <a href="https://www.kwtsms.com/account/" target="_blank" style="color:#FFA200">kwtsms.com</a>. Go to Account &gt; API Settings. Note your API username and API password (different from your mobile login).</p>
        </div>
    </div>

    <div class="kwt-setup-step">
        <div class="kwt-step-num">2</div>
        <div class="kwt-step-content">
            <h4>Connect your gateway</h4>
            <p>Go to the <a href="<?= htmlspecialchars($modulelink) ?>&tab=settings" style="color:#FFA200">Settings tab</a>. Enter your API username and password, then click <strong>Connect to kwtSMS</strong>. Your balance and sender IDs will be loaded automatically.</p>
        </div>
    </div>

    <div class="kwt-setup-step">
        <div class="kwt-step-num">3</div>
        <div class="kwt-step-content">
            <h4>Select a Sender ID</h4>
            <p>Choose your registered sender ID from the dropdown. <strong>Never use KWT-SMS in production</strong>: it may be blocked on some networks and is for testing only. Register a private sender ID at kwtsms.com if you do not have one.</p>
        </div>
    </div>

    <div class="kwt-setup-step">
        <div class="kwt-step-num">4</div>
        <div class="kwt-step-content">
            <h4>Test the connection</h4>
            <p>Keep Test Mode <strong>ON</strong>. Enter your phone number in the Test SMS box and click Send. Check your <a href="https://www.kwtsms.com/account/" target="_blank" style="color:#FFA200">kwtSMS dashboard queue</a> to confirm the message arrived. Delete it from the queue to recover any held credits.</p>
        </div>
    </div>

    <div class="kwt-setup-step">
        <div class="kwt-step-num">5</div>
        <div class="kwt-step-content">
            <h4>Enable the gateway</h4>
            <p>Once satisfied with the test, enable the gateway and turn off Test Mode. SMS notifications will now be sent on real WHMCS events (invoice paid, new client, new order).</p>
        </div>
    </div>

    <div class="kwt-setup-step">
        <div class="kwt-step-num">6</div>
        <div class="kwt-step-content">
            <h4>Customize templates (optional)</h4>
            <p>Visit the <a href="<?= htmlspecialchars($modulelink) ?>&tab=templates" style="color:#FFA200">Templates tab</a> to edit the SMS text for each event. Use <code style="background:#f0f0f0;padding:1px 4px;border-radius:3px;font-size:12px">{placeholders}</code> for dynamic content like client names and invoice amounts.</p>
        </div>
    </div>
</div>

<!-- Placeholder Reference -->
<div class="kwt-card">
    <div class="kwt-card-title">Template Placeholders</div>
    <table class="kwt-ph-table">
        <thead>
            <tr><th>Placeholder</th><th>Value</th><th>Available in</th></tr>
        </thead>
        <tbody>
            <tr><td><code class="kwt-ph">{firstname}</code></td><td>Client first name</td><td>All client events</td></tr>
            <tr><td><code class="kwt-ph">{fullname}</code></td><td>Client full name</td><td>All events</td></tr>
            <tr><td><code class="kwt-ph">{invoiceid}</code></td><td>Invoice number</td><td>Invoice Paid</td></tr>
            <tr><td><code class="kwt-ph">{invoiceamount}</code></td><td>Total with currency</td><td>Invoice Paid</td></tr>
            <tr><td><code class="kwt-ph">{invoiceduedate}</code></td><td>Due date</td><td>Invoice Paid</td></tr>
            <tr><td><code class="kwt-ph">{orderid}</code></td><td>Order ID</td><td>New Order (Admin)</td></tr>
        </tbody>
    </table>
</div>

</div><!-- .kwt-col -->

<!-- Sidebar: links + tips -->
<div class="kwt-col" style="max-width:280px">

<div class="kwt-card">
    <div class="kwt-card-title">Resources</div>
    <ul style="list-style:none;padding:0;margin:0;font-size:13px">
        <li style="padding:8px 0;border-bottom:1px solid #f0f0f0"><a href="https://www.kwtsms.com/account/" target="_blank" style="color:#FFA200;text-decoration:none">kwtSMS Dashboard</a></li>
        <li style="padding:8px 0;border-bottom:1px solid #f0f0f0"><a href="https://www.kwtsms.com/developers.html" target="_blank" style="color:#FFA200;text-decoration:none">API Documentation</a></li>
        <li style="padding:8px 0;border-bottom:1px solid #f0f0f0"><a href="https://www.kwtsms.com/sender-id-help.html" target="_blank" style="color:#FFA200;text-decoration:none">Sender ID Help</a></li>
        <li style="padding:8px 0;border-bottom:1px solid #f0f0f0"><a href="https://www.kwtsms.com/faq_all.php" target="_blank" style="color:#FFA200;text-decoration:none">FAQ</a></li>
        <li style="padding:8px 0"><a href="https://www.kwtsms.com/support.html" target="_blank" style="color:#FFA200;text-decoration:none">Contact Support</a></li>
    </ul>
</div>

<div class="kwt-card">
    <div class="kwt-card-title">Important Notes</div>
    <ul style="font-size:12px;color:#666;padding-left:16px;margin:0;line-height:1.8">
        <li>Always use Test Mode during development.</li>
        <li>KWT-SMS sender is for testing only: blocked on Virgin Kuwait.</li>
        <li>For OTP delivery, use a Transactional sender ID.</li>
        <li>Delete test messages from the kwtSMS queue to recover credits.</li>
        <li>Arabic SMS: 70 chars per page (not 160). Costs more per message.</li>
        <li>Balance auto-syncs daily via WHMCS cron.</li>
    </ul>
</div>

<div class="kwt-card">
    <div class="kwt-card-title">Module Info</div>
    <table style="font-size:12px;width:100%;border-collapse:collapse">
        <tr>
            <td style="padding:4px 0;color:#888">Module</td>
            <td style="font-weight:700;text-align:right">kwtSMS</td>
        </tr>
        <tr>
            <td style="padding:4px 0;color:#888">Version</td>
            <?php /* 2026-11-08 08:26 +03: version from GatewayManager::VERSION, not hard-coded (whmcs-101-release) */ ?><td style="font-weight:700;text-align:right"><?= htmlspecialchars(\KwtSMS\WHMCS\GatewayManager::VERSION) ?></td>
        </tr>
        <tr>
            <td style="padding:4px 0;color:#888">WHMCS</td>
            <td style="font-weight:700;text-align:right">8.12+ / 9.x</td>
        </tr>
        <tr>
            <td style="padding:4px 0;color:#888">Gateway</td>
            <td style="font-weight:700;text-align:right">kwtsms.com</td>
        </tr>
        <tr>
            <td style="padding:4px 0;color:#888">Support</td>
            <td style="font-weight:700;text-align:right"><a href="mailto:support@kwtsms.com" style="color:#FFA200">Email</a></td>
        </tr>
    </table>
</div>

</div><!-- .kwt-col -->
</div><!-- .kwt-row -->
</div><!-- .kwt-wrap -->
