<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';

use Phonebook\Security\Csrf;
use Phonebook\View;

$app->admin->requireAuthenticated();
$app->admin->startSession();
$message = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::validate($_POST['csrf'] ?? null)) {
        http_response_code(400);
        exit('Invalid CSRF token');
    }
    $app->settings->save($_POST);
    $message = 'Einstellungen gespeichert. Für geänderte Rufnummern-Normalisierung bitte anschließend synchronisieren.';
}
$settings = $app->settings->all();
$base = rtrim((string)$app->config->get('app.base_url'), '/');
View::header('Einstellungen');
?>
<div class="section-head"><div><div class="eyebrow">Konfiguration</div><h1>Einstellungen</h1><p class="muted">Nicht-sensitive Laufzeitwerte werden hier gepflegt. Secrets bleiben in <code>config/local.php</code>.</p></div></div>
<?php if ($message): ?><div class="alert ok"><?= View::e($message) ?></div><?php endif; ?>
<div class="grid">
<section class="card glass"><h2>Telefonbuch</h2><form method="post"><input type="hidden" name="csrf" value="<?= View::e(Csrf::token()) ?>"><label>Standard-Landesvorwahl<input name="country_code" inputmode="numeric" value="<?= View::e($settings['country_code']) ?>" required></label><label>Anzeigename<input name="phonebook_title" value="<?= View::e($settings['phonebook_title']) ?>" maxlength="100" required></label><button class="btn" type="submit">Speichern</button></form></section>
<section class="card glass"><h2>Sicherheitsstatus</h2><?php if ($app->config->isGoogleConfigured()): ?><p>Google OAuth <span class="status-badge ok">konfiguriert</span></p><p class="muted">Client-ID: <?= View::e(substr((string)$app->config->get('google.client_id'), 0, 10)) ?>… · Secret gesetzt</p><?php else: ?><p>Google OAuth <span class="status-badge bad">nicht konfiguriert</span></p><?php endif; ?><p>Gerätezugriff <span class="status-badge ok">pro Gerät geschützt</span></p><p class="muted">256-Bit-Zufallstoken; keine globalen Telefonbuch-Zugangsdaten.</p></section>
</div>
<section class="card glass"><h2>ALL-INKL Cron</h2><p class="muted">Die URL enthält ein Secret und sollte nicht öffentlich geteilt werden.</p><code><?= View::e($base . '/cron.php?token=DEIN_CRON_TOKEN') ?></code><p class="muted small">Empfehlung: alle 30 Minuten. Der tatsächliche Token bleibt ausschließlich in <code>config/local.php</code>.</p></section>
<?php View::footer(); ?>
