<?php
session_start();
$config = include 'config/mail_config.php';

// IMAP configuration
$imap_server = '{' . $config['imap_host'] . ':993/imap/ssl}INBOX';
$imap_user = $config['smtp_user'];  // Your email address (same as SMTP user)
$imap_pass = $config['smtp_pass'];  // Your SMTP password (IMAP password is often the same)

// Connect to the IMAP server
$inbox = imap_open($imap_server, $imap_user, $imap_pass);

if ($inbox === false) {
    echo "Failed to connect to the inbox. Please check your IMAP settings.";
    exit;
}

// Fetch emails
$emails = imap_search($inbox, 'ALL');  // You can change the search criteria (e.g. 'UNSEEN' for unread emails)

// If emails are found
if ($emails) {
    rsort($emails);  // Sort emails from newest to oldest
    
    foreach ($emails as $email_id) {
        $overview = imap_fetch_overview($inbox, $email_id, 0);  // Get email overview
        $message = imap_fetchbody($inbox, $email_id, 2);  // Get email body
        
        $subject = $overview[0]->subject;
        $from = $overview[0]->from;
        $date = $overview[0]->date;

        // Display email details
        echo "<div class='email'>";
        echo "<strong>Subject:</strong> " . htmlspecialchars($subject) . "<br>";
        echo "<strong>From:</strong> " . htmlspecialchars($from) . "<br>";
        echo "<strong>Date:</strong> " . htmlspecialchars($date) . "<br>";
        echo "<strong>Message:</strong> <br>" . nl2br(htmlspecialchars($message)) . "<br>";
        echo "<hr>";
        echo "</div>";
    }
} else {
    echo "No emails found.";
}

// Close the IMAP connection
imap_close($inbox);
?>
