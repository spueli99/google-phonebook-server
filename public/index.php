<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';

use Phonebook\Security\Csrf;
use Phonebook\View;

$app->admin->requireAuthenticated();
$state = $app->state->all();
$devices = $app->devices->all();
$blockCount = count($app->blocklist->active());
$lastRun = $app->history->latest(1)[0] ?? null;
View::header('Status');
?>
<div class="section-head">
  <div><div class="eyebrow">Systemübersicht</div><h1>Phonebook Sync</h1><p class="muted">Google-Kontakte zentral synchronisieren und gerätespezifisch bereitstellen.</p></div>
  <span class="stat-pill">v<?= View::VERSION ?></span>
</div>
<div class="grid">
  <section class="card status-card glass"><h2>Google OAuth</h2>
    <?php if (!$app->config->isGoogleConfigured()): ?>
      <div class="big badtext">Nicht konfiguriert</div><p class="muted">Client-ID und Client-Secret fehlen in <code>config/local.php</code>.</p>
    <?php else: ?>
      <div class="big <?= $app->oauth->isConnected() ? 'ok' : 'badtext' ?>"><?= $app->oauth->isConnected() ? 'Verbunden' : 'Nicht verbunden' ?></div>
      <p><?php if ($app->oauth->isConnected()): ?><span class="status-badge ok">Token vorhanden</span><?php else: ?><a class="btn" href="/oauth/start.php">Google verbinden</a><?php endif; ?></p>
    <?php endif; ?>
  </section>
  <section class="card status-card glass"><h2>Kontakte</h2><div class="big"><?= (int)$state['contacts'] ?></div><p class="muted"><?= (int)$state['numbers'] ?> Rufnummern · Sync <?= View::e(View::formatTime($state['last_sync'])) ?></p></section>
  <section class="card status-card glass"><h2>Geräte</h2><div class="big"><?= count($devices) ?></div><p class="muted"><?= count(array_filter($devices, static fn(array $d): bool => !empty($d['enabled']))) ?> aktiv</p></section>
  <section class="card status-card glass"><h2>Sperrliste</h2><div class="big"><?= $blockCount ?></div><p class="muted">aktive Einträge</p></section>
</div>
<?php if (!empty($state['last_error'])): ?><div class="alert bad"><strong>Letzter Fehler:</strong> <?= View::e($state['last_error']) ?></div><?php endif; ?>
<section class="card glass">
  <div class="section-head"><div><div class="eyebrow">Aktionen</div><h2>Synchronisierung & Verwaltung</h2></div><?php if ($lastRun): ?><span class="status-badge <?= !empty($lastRun['success']) ? 'ok' : 'bad' ?>">Letzter Lauf: <?= !empty($lastRun['success']) ? 'OK' : 'Fehler' ?></span><?php endif; ?></div>
  <form method="post" action="/sync.php" class="toolbar">
    <input type="hidden" name="csrf" value="<?= View::e(Csrf::token()) ?>">
    <button class="btn" type="submit">Jetzt synchronisieren</button>
    <a class="btn secondary" href="/devices.php">Geräte verwalten</a>
    <a class="btn secondary" href="/blocklist.php">Sperrliste</a>
    <a class="btn secondary" href="/history.php">Protokoll</a>
  </form>
</section>
<?php View::footer(); ?>
