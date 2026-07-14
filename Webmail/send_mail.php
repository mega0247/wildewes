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

// Block if limit reached
if ($data['count'] >= MAX_EMAILS_PER_DAY) {
    die("🚫 Daily email limit of 1,000 reached. Try again tomorrow.");
}

// Include PHPMailer library
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Autoload the PHPMailer classes
require 'lib/PHPMailer/Exception.php';
require 'lib/PHPMailer/PHPMailer.php';
require 'lib/PHPMailer/SMTP.php';

$config = include 'config/mail_config.php';

// Function to generate replacements (similar to Python code)
function generate_tag_replacements($email, $name, $link, $company, $domain, $tag) {
    $email64 = base64_encode($email);
    $date = date("Y-m-d");
    $random_text = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 10);
    $replacements = [
        "##Email##" => $email,
        "##Email64##" => $email64,
        "##Name##" => $name ?: "Customer",
        "##Date##" => $date,
        "##RANDOM_TEXT##" => $random_text,
        "##LINK##" => $link,
        "##RANDOM_COLOR##" => "#".dechex(rand(0, 0xFFFFFF)),
        "##Company##" => strtolower(trim($company)) ?: explode('.', $domain)[0],
        "##Domain##" => $domain,
        "##Data_tag##" => $tag,
    ];
    for ($i = 1; $i <= 10; $i++) {
        $replacements["##RANDOM_NUMBER$i##"] = rand(pow(10, $i - 1), pow(10, $i) - 1);
        $replacements["##RANDOM_TEXT$i##"] = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, $i);
    }
    return $replacements;
}

// Fetch form data
$subject = $_POST['subject'];
$bcc = $_POST['bcc'];
$body = $_POST['body'];
$signature = isset($_POST['signature']) ? $_POST['signature'] : '';
$email = $_POST['email']; // Assuming email from form
$name = $_POST['name'];   // Assuming name from form
$link = $_POST['link'];   // Assuming link from form
$company = $_POST['company']; // Assuming company from form
$domain = $_POST['domain']; // Assuming domain from form
$tag = $_POST['tag']; // Assuming tag from form

// Generate dynamic replacements
$replacements = generate_tag_replacements($email, $name, $link, $company, $domain, $tag);

// Replace placeholders in the subject and body content
foreach ($replacements as $placeholder => $value) {
    $subject = str_replace($placeholder, $value, $subject); // Replace in subject
    $body = str_replace($placeholder, $value, $body); // Replace in body
}

// Append the signature to the message body
$body .= "<br><br>" . nl2br($signature); // use HTML line breaks

// Set up the PHPMailer instance
$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host       = $config['smtp_host'];
    $mail->SMTPAuth   = true;
    $mail->Username   = $config['smtp_user'];
    $mail->Password   = $config['smtp_pass'];
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = $config['smtp_port'];

    $mail->setFrom($config['smtp_user'], $config['name']);
    $mail->addReplyTo($config['smtp_user'], $config['name']);

    // BCC recipients
    $bccEmails = explode(',', $bcc);
    foreach ($bccEmails as $bccEmail) {
        $bccEmail = trim($bccEmail);
        if (!empty($bccEmail)) {
            $mail->addBCC($bccEmail);
        }
    }

    // Add attachments if any
    if (isset($_FILES['attachment'])) {
        foreach ($_FILES['attachment']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['attachment']['error'][$key] === UPLOAD_ERR_OK) {
                $mail->addAttachment($tmp_name, $_FILES['attachment']['name'][$key]);
            }
        }
    }

    $mail->isHTML(true); // Enable HTML email
    $mail->Subject = $subject;
    $mail->Body    = $body;
    $mail->AltBody = strip_tags($body); // Plain-text fallback

    $mail->send();

    // Increment counter only after successful send
    $data['count']++;
    file_put_contents($counter_file, json_encode($data));

    echo '✅ Message has been sent successfully.';
} catch (Exception $e) {
    echo "❌ Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
}
?>
