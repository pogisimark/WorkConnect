# Manual PHPMailer Setup Instructions

Since Composer is not installed on your system, please follow these manual steps to set up PHPMailer:

## Option 1: Download PHPMailer Manually (Recommended)

1. **Download PHPMailer:**
   - Go to: https://github.com/PHPMailer/PHPMailer/releases
   - Download the latest release (ZIP file)
   - Extract the ZIP file

2. **Copy PHPMailer files:**
   - Create the directory: `vendor/phpmailer/phpmailer/src/`
   - Copy all files from the extracted `src/` folder to `vendor/phpmailer/phpmailer/src/`

3. **Verify the structure:**
   Your directory structure should look like:
   ```
   vendor/
   ├── autoload.php (already created)
   └── phpmailer/
       └── phpmailer/
           └── src/
               ├── PHPMailer.php
               ├── SMTP.php
               ├── Exception.php
               └── ... (other files)
   ```

## Option 2: Install Composer (Alternative)

If you prefer to use Composer:

1. **Download Composer:**
   - Go to: https://getcomposer.org/download/
   - Download and run the Windows installer
   - Or download `composer.phar` and place it in your project root

2. **Run Composer:**
   ```bash
   php composer.phar install
   ```
   Or if Composer is globally installed:
   ```bash
   composer install
   ```

## Option 3: Use Basic PHP Mail (Fallback)

If you can't install PHPMailer, you can modify the email script to use PHP's basic `mail()` function. However, this is less reliable and may not work with Gmail SMTP.

## Testing the Installation

Once PHPMailer is installed, you can test it by:

1. **Configure your Gmail credentials** in `Employer/email_config.php`
2. **Test the email functionality** by clicking the Accept button in your WorkConnect application

## Files Created

- `vendor/autoload.php` - Autoloader for PHPMailer
- `Employer/send_email_with_phpmailer.php` - New email handler
- `Employer/email_config.php` - Updated with Gmail settings
- `Employer/job.php` - Fixed bugs and updated references

## Next Steps

1. Follow the Gmail setup instructions in `EMAIL_SETUP_GUIDE.md`
2. Configure your Gmail App Password
3. Test the email functionality

## Troubleshooting

If you encounter issues:
- Make sure PHPMailer files are in the correct directory structure
- Check that your Gmail App Password is correct
- Verify that 2-Factor Authentication is enabled on your Gmail account
