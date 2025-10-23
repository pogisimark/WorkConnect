# Gmail Email Setup Guide for WorkConnect

This guide will help you configure Gmail SMTP to send jobseeker details via email when the Accept button is clicked.

## Prerequisites

1. A Gmail account
2. Composer installed on your system
3. Access to your WorkConnect project files

## Step 1: Install PHPMailer

Run the following command in your WorkConnect project root directory:

```bash
composer install
```

This will create a `vendor/` directory with PHPMailer library.

## Step 2: Enable 2-Factor Authentication on Gmail

1. Go to [Google Account Security](https://myaccount.google.com/security)
2. Sign in to your Gmail account
3. Under "Signing in to Google", click **2-Step Verification**
4. Follow the prompts to enable 2-Factor Authentication

## Step 3: Generate Gmail App Password

1. Go to [Google Account Security](https://myaccount.google.com/security)
2. Under "Signing in to Google", click **App passwords**
3. Select **Mail** as the app
4. Select **Other (Custom name)** as the device
5. Enter "WorkConnect" as the name
6. Click **Generate**
7. **Copy the 16-character password** (you won't see it again!)

## Step 4: Configure Email Settings

1. Open `Employer/email_config.php`
2. Replace the placeholder values:

```php
define('SMTP_USERNAME', 'your-actual-email@gmail.com'); // Your Gmail address
define('SMTP_PASSWORD', 'your-16-character-app-password'); // The App Password from Step 3
define('SMTP_FROM_EMAIL', 'your-actual-email@gmail.com'); // Same as SMTP_USERNAME
```

## Step 5: Test Email Functionality

1. Start your XAMPP server
2. Go to your WorkConnect employer dashboard
3. Click on a jobseeker's "Accept" button
4. Enter a test email address
5. Check if the email is received

## Troubleshooting

### Common Issues

**"Authentication failed" error:**
- Verify your Gmail App Password is correct
- Ensure 2-Factor Authentication is enabled
- Check that you're using the App Password, not your regular Gmail password

**"Connection refused" error:**
- Check your internet connection
- Verify SMTP settings in `email_config.php`
- Ensure your server allows outbound SMTP connections

**"Email not received":**
- Check spam/junk folder
- Verify the recipient email address is correct
- Check Gmail's "Less secure app access" settings (though App Passwords should work)

### Testing Email Configuration

Create a test file `test_email_config.php` in your project root:

```php
<?php
require_once 'vendor/autoload.php';
require_once 'Employer/email_config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host       = SMTP_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = SMTP_USERNAME;
    $mail->Password   = SMTP_PASSWORD;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = SMTP_PORT;
    
    $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
    $mail->addAddress('test@example.com'); // Replace with your email
    
    $mail->isHTML(true);
    $mail->Subject = 'Test Email from WorkConnect';
    $mail->Body    = 'This is a test email to verify Gmail SMTP configuration.';
    
    $mail->send();
    echo 'Test email sent successfully!';
} catch (Exception $e) {
    echo 'Error: ' . $mail->ErrorInfo;
}
?>
```

## Security Notes

- Never commit your actual email credentials to version control
- The `.gitignore` file has been created to protect sensitive files
- Use App Passwords instead of your main Gmail password
- Regularly rotate your App Passwords for security

## Support

If you encounter issues:

1. Check the error messages in your browser's developer console
2. Verify all configuration steps were completed correctly
3. Test with the provided test script
4. Check your server's error logs for detailed error messages

## Files Modified

- `composer.json` - Added PHPMailer dependency
- `Employer/email_config.php` - Updated with Gmail SMTP settings
- `Employer/send_email_with_phpmailer.php` - New PHPMailer email handler
- `Employer/job.php` - Fixed parameter bug and updated email script reference
- `.gitignore` - Added to protect email credentials
