<?php
session_start();
$config = include 'config/mail_config.php';

// Open IMAP connection
$imap = imap_open("{" . $config['imap_host'] . ":" . $config['imap_port'] . "/imap/ssl}INBOX", $config['email'], $config['smtp_pass']);

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    die("Invalid email ID.");
}

// Fetch headers and structure
$header = imap_headerinfo($imap, $id);
$structure = imap_fetchstructure($imap, $id);

// Function to extract email body
function get_body($imap, $id, $structure) {
    if (!isset($structure->parts)) {
        return decode_part(imap_fetchbody($imap, $id, 1), $structure->encoding);
    }

    foreach ($structure->parts as $i => $part) {
        $encoding = $part->encoding;
        $subtype = strtolower($part->subtype);
        $bodyPart = $i + 1;

        // Prefer HTML, fallback to plain text
        if ($subtype == 'html') {
            return decode_part(imap_fetchbody($imap, $id, $bodyPart), $encoding);
        } elseif ($subtype == 'plain' && empty($plainText)) {
            $plainText = decode_part(imap_fetchbody($imap, $id, $bodyPart), $encoding);
        }
    }

    return $plainText ?? '(No readable body found)';
}

// Decoding helper
function decode_part($data, $encoding) {
    switch ($encoding) {
        case 3: return imap_base64($data);
        case 4: return imap_qprint($data);
        default: return $data;
    }
}

$body = get_body($imap, $id, $structure);

// Display email
echo "<h1>" . htmlspecialchars($header->subject ?? '(No Subject)') . "</h1>";
echo "<p><strong>From:</strong> " . htmlspecialchars($header->fromaddress) . "</p>";
echo "<p><strong>Date:</strong> " . htmlspecialchars($header->date) . "</p>";

echo "<h3>Email Content:</h3>";
if (stripos($body, '<html') !== false) {
    echo $body; // Contains HTML, so render it
} else {
    echo nl2br(htmlspecialchars($body)); // Plain text
}

// Create "Reply" and "Forward" buttons
echo "<br><br>";
echo "<form action='compose.php' method='get'>
    <input type='hidden' name='subject' value='Re: " . htmlspecialchars($header->subject) . "'>
    <input type='hidden' name='body' value='> " . htmlspecialchars($body) . "\n\n\n--Reply Above--'>
    <input type='submit' value='Reply'>
</form>";

echo "<form action='compose.php' method='get'>
    <input type='hidden' name='subject' value='Fwd: " . htmlspecialchars($header->subject) . "'>
    <input type='hidden' name='body' value='> " . htmlspecialchars($body) . "\n\n\n--Forwarded Message--'>
    <input type='submit' value='Forward'>
</form>";

// Close the IMAP connection
imap_close($imap);
?>
