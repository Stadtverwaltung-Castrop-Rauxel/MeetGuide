<?php
// Ein einfaches Passwort festlegen
$admin_password = "PasswortMeetGuide";

// Prüfen, ob der Nutzer bereits eingeloggt ist (via PHP Session)
session_start();

if (isset($_POST['password'])) {
    if ($_POST['password'] === $admin_password) {
        $_SESSION['authenticated'] = true;
    } else {
        $error = "Falsches Passwort!";
    }
}

if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true):
?>
<!DOCTYPE html>
<html>
<head><title>Login</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="bg-light d-flex align-items-center" style="height: 100vh;">
    <div class="container card p-4 shadow-sm" style="max-width: 400px;">
        <h4 class="mb-3 text-center">Admin Login</h4>
        <?php if(isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
        <form method="POST">
            <input type="password" name="password" class="form-control mb-3" placeholder="Passwort eingeben" required autofocus>
            <button class="btn btn-primary w-100">Anmelden</button>
        </form>
    </div>
</body>
</html>
<?php 
exit; 
endif; 
?>