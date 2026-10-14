<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';

use Phonebook\Contacts\ContactNormalizer;
use Phonebook\View;

$app->admin->requireAuthenticated();
$contacts = $app->contacts->all();
$q = trim((string)($_GET['q'] ?? ''));
if ($q !== '') {
    $needle = mb_strtolower($q);
    $contacts = array_values(array_filter($contacts, static function (array $contact) use ($needle): bool {
        $parts = [(string)($contact['name'] ?? '')];
        foreach (ContactNormalizer::phoneEntries($contact) as $entry) {
            $parts[] = (string)$entry['label'];
            $parts[] = (string)$entry['number'];
        }
        return str_contains(mb_strtolower(implode(' ', $parts)), $needle);
    }));
}
View::header('Kontakte');
?>
<section class="card card--page glass">
  <div class="section-head">
    <div><div class="eyebrow">Google Contacts</div><h1>Kontakte</h1><p class="muted">Synchronisierte Kontakte mit Rufnummerntypen für die Telefon-Exporte.</p></div>
    <span class="stat-pill"><?= count($contacts) ?> Treffer</span>
  </div>
  <form method="get" class="toolbar toolbar--search">
    <input type="search" name="q" value="<?= View::e($q) ?>" placeholder="Name, Nummer oder Typ suchen" aria-label="Kontakte durchsuchen">
    <button class="btn" type="submit">Suchen</button>
    <?php if ($q !== ''): ?><a class="btn secondary" href="/contacts.php">Zurücksetzen</a><?php endif; ?>
  </form>
  <div class="table-wrap"><table class="table contacts-table"><thead><tr><th>Name</th><th>Rufnummern</th></tr></thead><tbody>
  <?php foreach ($contacts as $contact): ?><tr><td class="contact-name"><?= View::e($contact['name'] ?? '') ?></td><td><?php foreach (ContactNormalizer::phoneEntries($contact) as $entry): ?><div class="phone-row"><span class="phone-label"><?= View::e($entry['label']) ?></span><span class="phone-number"><?= View::e($entry['number']) ?></span></div><?php endforeach; ?></td></tr><?php endforeach; ?>
  <?php if (!$contacts): ?><tr><td colspan="2" class="muted">Keine Kontakte gefunden.</td></tr><?php endif; ?>
  </tbody></table></div>
</section>
<?php View::footer(); ?>
