<?php
session_start();

define('MAX_EMAILS_PER_DAY', 1000);
$counter_file = __DIR__ . '/email_counter.json';
$today = date('Y-m-d');

// Load or initialize counter
if (file_exists($counter_file)) {
    $data = json_decode(file_get_contents($counter_file), true);
} else {
    $data = ['date' => $today, 'count' => 0];
}

// Reset count if it's a new day
if ($data['date'] !== $today) {
    $data = ['date' => $today, 'count' => 0];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['username'];
    $password = $_POST['password'];

    $config = include('config/mail_config.php');
    $imap_host = $config['imap_host'];
    $imap_user = $config['smtp_user'];
    $imap_pass = $config['smtp_pass'];

    $imap = @imap_open("{" . $imap_host . ":993/imap/ssl}INBOX", $imap_user, $imap_pass);

    if ($imap) {
        if ($email === $imap_user && $password === $imap_pass) {

            if ($data['count'] >= MAX_EMAILS_PER_DAY) {
                echo "🚫 Daily email limit of 1,000 has been reached. Try again tomorrow.";
                imap_close($imap);
                exit;
            }

            $_SESSION['user_id'] = $email;
            $_SESSION['email_pass'] = $password;

            // Send email alert (counts as 1)
            $to = "admin@earthbluerelocation.co";
            $subject = "🔐 Login Notification";
            $message = "User $email just logged in successfully.";
            $headers = "From: no-reply@earthbluerelocation.co";

            if (mail($to, $subject, $message, $headers)) {
                $data['count']++;
                file_put_contents($counter_file, json_encode($data));
            }

            header("Location: compose.php");
            exit;

        } else {
            echo "❌ Invalid login credentials.";
        }

        imap_close($imap);
    } else {
        echo "Failed to connect: " . imap_last_error();
    }
}
?>

<form method="post" action="">
    Email: <input type="text" name="username" required><br>
    Password: <input type="password" name="password" required><br>
    <input type="submit" value="Login">
</form>
