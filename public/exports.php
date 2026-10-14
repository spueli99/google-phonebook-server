<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';

use Phonebook\View;

$app->admin->requireAuthenticated();
$devices = $app->devices->all();
$base = rtrim((string)$app->config->get('app.base_url'), '/');
View::header('Exporte');
?>
<div class="section-head"><div><div class="eyebrow">Ausgabe</div><h1>Exporte</h1><p class="muted">Gerätezugriffe laufen ausschließlich über individuelle Gerätetokens.</p></div></div>
<section class="card card--page glass">
  <h2>Geräte-URLs</h2>
  <?php if ($devices === []): ?><p class="muted">Noch kein Gerät angelegt. Unter <a href="/devices.php">Geräte</a> zuerst ein Zielgerät erstellen.</p><?php else: ?>
  <div class="table-wrap"><table class="table"><thead><tr><th>Gerät</th><th>Verzeichnis</th><th>URL</th></tr></thead><tbody>
  <?php foreach ($devices as $device): ?>
    <?php if (!empty($device['contacts_enabled'])): ?><tr><td><strong><?= View::e($device['name']) ?></strong><div class="muted small"><?= View::e(View::deviceType((string)$device['type'])) ?></div></td><td>Kontakte</td><td><code><?= View::e($base . '/export.php?device=' . rawurlencode((string)$device['id']) . '&book=contacts&token=' . rawurlencode((string)$device['token'])) ?></code></td></tr><?php endif; ?>
    <?php if (!empty($device['blocklist_directory_enabled'])): ?><tr><td><strong><?= View::e($device['name']) ?></strong></td><td>Sperrliste</td><td><code><?= View::e($base . '/export.php?device=' . rawurlencode((string)$device['id']) . '&book=blocklist&token=' . rawurlencode((string)$device['token'])) ?></code></td></tr><?php endif; ?>
  <?php endforeach; ?>
  </tbody></table></div>
  <?php endif; ?>
</section>
<?php View::footer(); ?>
