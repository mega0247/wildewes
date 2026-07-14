<?php 
session_start();
$config = include 'config/mail_config.php';

// Optionally load signature from session (or from database if needed)
$signature = isset($_SESSION['signature']) ? $_SESSION['signature'] : '';

?>
<form action="send_mail.php" method="post">
    Subject: <input type="text" name="subject" required><br>
    To (BCC): <textarea name="bcc" placeholder="Separate emails by commas" required></textarea><br>
    Message: <textarea name="body" required></textarea><br>

    <b>Signature:</b><br>
    <textarea name="signature"><?php echo htmlspecialchars($signature); ?></textarea><br>
    
    <input type="submit" value="Send">
</form>
