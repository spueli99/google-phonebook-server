<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';

use Phonebook\View;
use Phonebook\Security\Csrf;

$app->admin->startSession();
if ($app->admin->isAuthenticated()) {
    header('Location: /', true, 303);
    exit;
}
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::validate($_POST['csrf'] ?? null)) {
        $error = 'Ungültige Sitzung. Bitte erneut versuchen.';
    } elseif ($app->admin->login((string)($_POST['password'] ?? ''))) {
        header('Location: /', true, 303);
        exit;
    } else {
        $error = 'Anmeldung fehlgeschlagen.';
    }
}
View::header('Anmeldung', false);
?>
<section class="card narrow glass">
  <div class="eyebrow">Administration</div><h1>Phonebook Sync</h1><p class="login-note">Google-Kontakte sicher synchronisieren und für Telefone bereitstellen.</p>
  <?php if ($error): ?><div class="alert bad"><?= View::e($error) ?></div><?php endif; ?>
  <form method="post">
    <input type="hidden" name="csrf" value="<?= View::e(Csrf::token()) ?>">
    <label>Admin-Passwort<input type="password" name="password" required autofocus autocomplete="current-password"></label>
    <button class="btn" type="submit">Anmelden</button>
  </form>
</section>
<?php View::footer(); ?>
