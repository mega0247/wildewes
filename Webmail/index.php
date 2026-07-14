<?php
$config = include 'config/mail_config.php';

// Load email counter
define('MAX_EMAILS_PER_DAY', 1000);
$counter_file = __DIR__ . '/email_counter.json';
$today = date('Y-m-d');

if (file_exists($counter_file)) {
    $data = json_decode(file_get_contents($counter_file), true);
} else {
    $data = ['date' => $today, 'count' => 0];
}

// Reset count if new day
if ($data['date'] !== $today) {
    $data = ['date' => $today, 'count' => 0];
    file_put_contents($counter_file, json_encode($data));
}

// Show usage status
echo "<div style='padding: 10px; background: #f0f0f0; margin-bottom: 10px;'>
    📤 Sent today: <b>{$data['count']} / " . MAX_EMAILS_PER_DAY . "</b><br>";
if ($data['count'] >= MAX_EMAILS_PER_DAY) {
    echo "<span style='color:red;'>🚫 Daily limit reached. Come back tomorrow.</span>";
}
echo "</div>";

// Connect to IMAP
$imap = imap_open("{".$config['imap_host'].":".$config['imap_port']."/imap/ssl}INBOX", $config['email'], $config['smtp_pass']);

if (!$imap) {
    echo "Failed to connect to the inbox. Please check your IMAP settings.";
    exit;
}

// Search for all emails
$mails = imap_search($imap, 'ALL');

if ($mails) {
    rsort($mails); // Newest to oldest
    foreach ($mails as $mail_id) {
        $header = imap_headerinfo($imap, $mail_id);
        $subject = htmlspecialchars($header->subject ?? '(No Subject)');
        $from = htmlspecialchars($header->fromaddress);
        $date = htmlspecialchars($header->date);

        echo "<a href='view.php?id=$mail_id'>" . $from . ": " . $subject . "</a><br>";
        echo "<small>Date: $date</small><br><hr>";
    }
} else {
    echo "No emails found in your inbox.";
}

imap_close($imap);
?>
