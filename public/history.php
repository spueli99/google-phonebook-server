<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';

use Phonebook\View;

$app->admin->requireAuthenticated();
$runs = $app->history->latest(50);
View::header('Protokoll');
?>
<div class="section-head"><div><div class="eyebrow">Synchronisierung</div><h1>Protokoll</h1><p class="muted">Die letzten 50 Synchronisationsläufe. Geräteabrufe werden direkt am jeweiligen Gerät protokolliert.</p></div></div>
<section class="card card--page glass">
<?php if ($runs === []): ?><p class="muted">Noch keine protokollierten Synchronisationsläufe.</p><?php else: ?>
<div class="table-wrap"><table class="table"><thead><tr><th>Zeit</th><th>Quelle</th><th>Status</th><th>Kontakte</th><th>Rufnummern</th><th>Dauer</th><th>Fehler</th></tr></thead><tbody>
<?php foreach ($runs as $run): ?><tr><td><?= View::e(View::formatTime($run['at'] ?? null)) ?></td><td><?= View::e((string)($run['source'] ?? '')) ?></td><td><span class="status-badge <?= !empty($run['success']) ? 'ok' : 'bad' ?>"><?= !empty($run['success']) ? 'OK' : 'Fehler' ?></span></td><td><?= (int)($run['contacts'] ?? 0) ?></td><td><?= (int)($run['numbers'] ?? 0) ?></td><td><?= (int)($run['duration_ms'] ?? 0) ?> ms</td><td><?= View::e((string)($run['error'] ?? '')) ?></td></tr><?php endforeach; ?>
</tbody></table></div>
<?php endif; ?>
</section>
<?php View::footer(); ?>
