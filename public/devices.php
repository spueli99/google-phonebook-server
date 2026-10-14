<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';

use Phonebook\Http;
use Phonebook\Security\Csrf;
use Phonebook\View;

$app->admin->requireAuthenticated();
$app->admin->startSession();
$error = null;
$message = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::validate($_POST['csrf'] ?? null)) {
        http_response_code(400);
        exit('Invalid CSRF token');
    }
    try {
        $action = (string)($_POST['action'] ?? 'save');
        if ($action === 'delete') {
            $app->devices->delete((string)($_POST['id'] ?? ''));
            Http::redirect('/devices.php?deleted=1');
        }
        $saved = $app->devices->save($_POST);
        Http::redirect('/devices.php?edit=' . rawurlencode((string)$saved['id']) . '&saved=1');
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

if (isset($_GET['saved'])) {
    $message = 'Gerät gespeichert.';
} elseif (isset($_GET['deleted'])) {
    $message = 'Gerät gelöscht.';
}

$editId = trim((string)($_GET['edit'] ?? ''));
$edit = $editId !== '' ? $app->devices->find($editId) : null;
$deleteId = trim((string)($_GET['delete'] ?? ''));
$delete = $deleteId !== '' ? $app->devices->find($deleteId) : null;
$devices = $app->devices->all();
$base = rtrim((string)$app->config->get('app.base_url'), '/');

View::header('Geräte');
?>
<div class="section-head"><div><div class="eyebrow">Zielgeräte</div><h1>Geräte</h1><p class="muted">Jedes Gerät erhält einen eigenen 256-Bit-Zugriffstoken und nur die für seinen Typ vorgesehenen Exportwege.</p></div><a class="btn" href="/devices.php?edit=new">Gerät hinzufügen</a></div>
<?php if ($message): ?><div class="alert ok"><?= View::e($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert bad"><?= View::e($error) ?></div><?php endif; ?>

<?php if ($delete): ?>
<section class="card glass"><h2>Gerät wirklich löschen?</h2><p><strong><?= View::e($delete['name']) ?></strong> wird entfernt. Der bisherige Geräte-Token funktioniert danach sofort nicht mehr.</p>
<form method="post" class="toolbar"><input type="hidden" name="csrf" value="<?= View::e(Csrf::token()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= View::e($delete['id']) ?>"><button class="btn danger" type="submit">Endgültig löschen</button><a class="btn secondary" href="/devices.php">Abbrechen</a></form></section>
<?php endif; ?>

<?php if ($editId !== ''):
    $isNew = $editId === 'new';
    $edit = $isNew ? null : $edit;
    if (!$isNew && $edit === null): ?>
      <div class="alert bad">Gerät nicht gefunden.</div>
    <?php else:
      $type = (string)($edit['type'] ?? 'yealink');
      $enabled = $edit === null ? true : !empty($edit['enabled']);
      $contactsEnabled = $edit === null ? true : !empty($edit['contacts_enabled']);
      $blockEnabled = $edit !== null && !empty($edit['blocklist_directory_enabled']); ?>
<section class="card card--page glass">
  <div class="section-head"><div><div class="eyebrow"><?= $isNew ? 'Neues Gerät' : 'Gerät bearbeiten' ?></div><h2><?= View::e($edit['name'] ?? 'Gerät hinzufügen') ?></h2></div><a class="btn secondary" href="/devices.php">Schließen</a></div>
  <form method="post">
    <input type="hidden" name="csrf" value="<?= View::e(Csrf::token()) ?>"><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= View::e($edit['id'] ?? '') ?>">
    <div class="form-grid">
      <label>Gerätename<input name="name" maxlength="120" required value="<?= View::e($edit['name'] ?? '') ?>" placeholder="z. B. T54W Wohnzimmer"></label>
    </div>
    <div class="check-grid">
      <label class="check"><input type="checkbox" name="enabled" value="1" <?= $enabled ? 'checked' : '' ?>><span>Gerät aktiv</span></label>
      <label class="check"><input type="checkbox" name="contacts_enabled" value="1" <?= $contactsEnabled ? 'checked' : '' ?>><span>Kontaktverzeichnis freigeben</span></label>
      <label class="check"><input type="checkbox" name="blocklist_directory_enabled" value="1" <?= $blockEnabled ? 'checked' : '' ?>><span>Sperrlisten-Verzeichnis (nur Yealink)</span></label>
      <?php if ($edit !== null): ?><label class="check"><input type="checkbox" name="regenerate_token" value="1"><span>Geräte-Token neu erzeugen</span></label><?php endif; ?>
    </div>
    <label>Notiz<textarea name="notes" rows="3" maxlength="500" placeholder="Optional"><?= View::e($edit['notes'] ?? '') ?></textarea></label>
    <div class="toolbar"><button class="btn" type="submit">Speichern</button><a class="btn secondary" href="/devices.php">Abbrechen</a></div>
  </form>
</section>
    <?php endif; ?>
<?php endif; ?>

<section class="card card--page glass">
  <div class="section-head"><div><div class="eyebrow">Konfiguration</div><h2>Vorhandene Geräte</h2></div><span class="stat-pill"><?= count($devices) ?> Geräte</span></div>
  <?php if ($devices === []): ?><p class="muted">Noch kein Gerät angelegt. Lege zuerst dein Yealink T54W an.</p><?php else: ?>
  <div class="table-wrap"><table class="table devices-table"><thead><tr><th>Gerät</th><th>Typ</th><th>Status</th><th>Letzter Abruf</th><th>Verzeichnisse</th><th>Aktionen</th></tr></thead><tbody>
  <?php foreach ($devices as $device):
      $contactUrl = $base . '/export.php?device=' . rawurlencode((string)$device['id']) . '&book=contacts&token=' . rawurlencode((string)$device['token']);
      $blockUrl = $base . '/export.php?device=' . rawurlencode((string)$device['id']) . '&book=blocklist&token=' . rawurlencode((string)$device['token']); ?>
    <tr>
      <td><strong><?= View::e($device['name']) ?></strong><?php if (!empty($device['notes'])): ?><div class="muted small"><?= View::e($device['notes']) ?></div><?php endif; ?></td>
      <td><?= View::e(View::deviceType((string)$device['type'])) ?></td>
      <td><span class="status-badge <?= !empty($device['enabled']) ? 'ok' : 'bad' ?>"><?= !empty($device['enabled']) ? 'aktiv' : 'inaktiv' ?></span></td>
      <td><?= View::e(View::formatTime($device['last_access_at'] ?? null)) ?><div class="muted small"><?= (int)($device['access_count'] ?? 0) ?> Abrufe<?= !empty($device['last_access_book']) ? ' · ' . View::e((string)$device['last_access_book']) : '' ?></div></td>
      <td>
        <?php if (!empty($device['contacts_enabled'])): ?><div><span class="phone-label">Kontakte</span><code><?= View::e($contactUrl) ?></code></div><?php endif; ?>
        <?php if (!empty($device['blocklist_directory_enabled'])): ?><div class="url-row"><span class="phone-label">Sperrliste</span><code><?= View::e($blockUrl) ?></code></div><?php endif; ?>
      </td>
      <td><div class="toolbar"><a class="btn secondary compact" href="/devices.php?edit=<?= rawurlencode((string)$device['id']) ?>">Bearbeiten</a><a class="btn secondary compact" href="/devices.php?delete=<?= rawurlencode((string)$device['id']) ?>">Löschen</a></div></td>
    </tr>
  <?php endforeach; ?>
  </tbody></table></div>
  <?php endif; ?>
</section>
<?php View::footer(); ?>
