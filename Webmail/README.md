# Simple Webmail Client

## Description
A lightweight PHP webmail interface with BCC bulk sending and SMTP support. Built using PHPMailer.

## Requirements
- PHP 7.2+
- IMAP (optional, for inbox)
- cPanel hosting or VPS (e.g., HawkHost, XetHost)
- SMTP provider (e.g., NitroMail, SendGrid, Mailgun)

## Setup Instructions

1. Upload the `webmail/` folder to your hosting public_html or subdirectory.
2. Edit `config/mail_config.php` with your SMTP details.
3. Upload the PHPMailer library inside `lib/PHPMailer/`
4. Navigate to `/webmail/index.php` in your browser.
5. Login, compose emails, and set signature via `signature.php`.

## Features
- BCC support for bulk sending (e.g., 70 emails at a time)
- Email signature support
- Login/logout session system
- Optional inbox (IMAP)
- Clean IP SMTP delivery via trusted relay (if configured)

## Security Tips
- Add `.htaccess` to protect sensitive files
- Use strong SMTP credentials
