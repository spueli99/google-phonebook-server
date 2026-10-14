<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';

use Phonebook\Http;
use Phonebook\Security\Csrf;
use Phonebook\View;

$app->admin->requireAuthenticated();
$app->admin->startSession();
$error = null;
$message = isset($_GET['saved']) ? 'Sperrlisteneintrag gespeichert.' : (isset($_GET['deleted']) ? 'Sperrlisteneintrag gelöscht.' : null);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::validate($_POST['csrf'] ?? null)) {
        http_response_code(400);
        exit('Invalid CSRF token');
    }
    try {
        if ((string)($_POST['action'] ?? 'save') === 'delete') {
            $app->blocklist->delete((string)($_POST['id'] ?? ''));
            Http::redirect('/blocklist.php?deleted=1');
        }
        $app->blocklist->save($_POST);
        Http::redirect('/blocklist.php?saved=1');
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$entries = $app->blocklist->all();
$editId = trim((string)($_GET['edit'] ?? ''));
$edit = null;
foreach ($entries as $entry) {
    if ((string)$entry['id'] === $editId) {
        $edit = $entry;
        break;
    }
}
$deleteId = trim((string)($_GET['delete'] ?? ''));
$delete = null;
foreach ($entries as $entry) {
    if ((string)$entry['id'] === $deleteId) {
        $delete = $entry;
        break;
    }
}
View::header('Sperrliste');
?>
<div class="section-head"><div><div class="eyebrow">Zentral verwaltet</div><h1>Sperrliste</h1><p class="muted">Exakte Rufnummern zentral pflegen. Yealink kann diese Liste als separates Remote-Verzeichnis anzeigen; die eigentliche T54W-Blockierung bleibt lokal.</p></div><a class="btn" href="/blocklist.php?edit=new">Nummer hinzufügen</a></div>
<?php if ($message): ?><div class="alert ok"><?= View::e($message) ?></div><?php endif; ?><?php if ($error): ?><div class="alert bad"><?= View::e($error) ?></div><?php endif; ?>

<?php if ($delete): ?><section class="card glass"><h2>Eintrag wirklich löschen?</h2><p><strong><?= View::e($delete['label']) ?></strong> · <?= View::e($delete['number']) ?></p><form method="post" class="toolbar"><input type="hidden" name="csrf" value="<?= View::e(Csrf::token()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= View::e($delete['id']) ?>"><button class="btn danger" type="submit">Endgültig löschen</button><a class="btn secondary" href="/blocklist.php">Abbrechen</a></form></section><?php endif; ?>

<?php if ($editId !== ''):
  $isNew = $editId === 'new';
  if ($isNew) { $edit = null; }
  if (!$isNew && $edit === null): ?><div class="alert bad">Eintrag nicht gefunden.</div><?php else: ?>
<section class="card card--page glass"><div class="section-head"><div><div class="eyebrow"><?= $isNew ? 'Neuer Eintrag' : 'Eintrag bearbeiten' ?></div><h2><?= View::e($edit['label'] ?? 'Rufnummer sperren') ?></h2></div><a class="btn secondary" href="/blocklist.php">Schließen</a></div>
<form method="post"><input type="hidden" name="csrf" value="<?= View::e(Csrf::token()) ?>"><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= View::e($edit['id'] ?? '') ?>">
<div class="form-grid"><label>Rufnummer<input name="number" required value="<?= View::e($edit['number'] ?? '') ?>" placeholder="z. B. 02159 123456"></label><label>Bezeichnung<input name="label" maxlength="120" required value="<?= View::e($edit['label'] ?? '') ?>" placeholder="z. B. Werbeanruf"></label></div>
<label>Kommentar<textarea name="comment" rows="3" maxlength="500"><?= View::e($edit['comment'] ?? '') ?></textarea></label><label class="check"><input type="checkbox" name="enabled" value="1" <?= $edit === null || !empty($edit['enabled']) ? 'checked' : '' ?>><span>Eintrag aktiv</span></label><div class="toolbar"><button class="btn" type="submit">Speichern</button><a class="btn secondary" href="/blocklist.php">Abbrechen</a></div></form></section>
<?php endif; endif; ?>

<section class="card card--page glass"><div class="section-head"><div><div class="eyebrow">Einträge</div><h2>Aktuelle Sperrliste</h2></div><span class="stat-pill"><?= count($app->blocklist->active()) ?> aktiv</span></div>
<?php if ($entries === []): ?><p class="muted">Die Sperrliste ist leer.</p><?php else: ?><div class="table-wrap"><table class="table"><thead><tr><th>Bezeichnung</th><th>Rufnummer</th><th>Kommentar</th><th>Status</th><th>Aktionen</th></tr></thead><tbody><?php foreach ($entries as $entry): ?><tr><td><strong><?= View::e($entry['label']) ?></strong></td><td><code><?= View::e($entry['number']) ?></code></td><td><?= View::e($entry['comment']) ?></td><td><span class="status-badge <?= !empty($entry['enabled']) ? 'ok' : 'bad' ?>"><?= !empty($entry['enabled']) ? 'aktiv' : 'inaktiv' ?></span></td><td><div class="toolbar"><a class="btn secondary compact" href="/blocklist.php?edit=<?= rawurlencode((string)$entry['id']) ?>">Bearbeiten</a><a class="btn secondary compact" href="/blocklist.php?delete=<?= rawurlencode((string)$entry['id']) ?>">Löschen</a></div></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
</section>
<?php View::footer(); ?>
