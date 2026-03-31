<?php
require_once __DIR__ . '/../../../env_loader.php';

define('SMTP_HOST', getenv('SMTP_HOST') ?: 'smtp.gmail.com');
define('SMTP_PORT', getenv('SMTP_PORT') ?: 465);
define('SMTP_USER', getenv('SMTP_USER') ?: 'terrencekwa50@gmail.com');
define('SMTP_PASS', getenv('SMTP_PASS') ?: 'lhjfcmcxxiardvfy');
define('SMTP_FROM', getenv('SMTP_FROM') ?: 'terrencekwa50@gmail.com');
define('SMTP_FROM_NAME', getenv('SMTP_FROM_NAME') ?: 'Priority Horizon');
