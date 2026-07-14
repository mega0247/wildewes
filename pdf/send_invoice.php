<?php
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error_log.txt');
error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json');

require __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

try {

    $input = file_get_contents('php://input');
    file_put_contents(__DIR__.'/debug_input.txt', $input . "\n", FILE_APPEND);

    $data = json_decode($input, true);
    if (!$data) {
        echo json_encode(['success' => false, 'error' => 'Invalid JSON input']);
        exit;
    }

    $required = ['clientName', 'clientEmail', 'invoiceNumber', 'pdfBase64', 'fileName'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            echo json_encode(['success' => false, 'error' => "Missing field: $field"]);
            exit;
        }
    }

    $clientName = $data['clientName'];
    $clientEmail = $data['clientEmail'];
    $invoiceNumber = $data['invoiceNumber'];
    $pdfBase64 = $data['pdfBase64'];
    $fileName = $data['fileName'];

    $pdfContent = base64_decode($pdfBase64);
    if ($pdfContent === false) {
        echo json_encode(['success' => false, 'error' => 'Failed to decode PDF base64']);
        exit;
    }

    $mail = new PHPMailer(true);

    $mail->isSMTP();
    $mail->Host = 'mail.wildeweswoolery.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'rob@wildeweswoolery.com';
    $mail->Password = 'Goodman44@'; // Your app password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // SSL encryption
    $mail->Port = 465; // SSL port

    $mail->setFrom('rob@wildeweswoolery.com', 'Wild Ewes Woolery');
    $mail->addAddress($clientEmail, $clientName);
    $mail->addBCC('rob@wildeweswoolery.com', 'Wild Ewes Woolery Accounting');

    $mail->isHTML(true);
    $mail->Subject = "Invoice #$invoiceNumber from Wild Ewes Woolery";
    $mail->Body = "
        <p>Dear $clientName,</p>
        <p>Please find attached your invoice #$invoiceNumber.</p>
        <p>Thank you for your business.</p>
        <p>Wild Ewes Woolery & 3rd Party Logistics</p>
    ";

    $mail->addStringAttachment($pdfContent, $fileName, 'base64', 'application/pdf');

    $mail->send();

    echo json_encode(['success' => true, 'message' => 'Email sent successfully']);
    exit;

} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}
