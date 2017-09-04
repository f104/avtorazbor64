<?php

ini_set('display_errors', 1);
ini_set('error_reporting', -1);

$database_dsn = 'mysql:host=127.0.0.1;dbname=s1889;charset=utf8';
$database_user = 'username';
$database_password = 'password';

$mail_name_from = 'MailRobot';              // name for "mail from"
$mail_address_to = 'your@mail.com';         // address to
$mail_feedback_to = $mail_address_to;       // feedback address to
$mail_subject = 'Mail subject';
$mail_admin = 'admin@domain.com';           // administrator email

$mail_host = 'smtp.example.com';            // Specify main and backup SMTP servers
$mail_username = 'user@example.com';        // SMTP username
$mail_password = 'secret';                  // SMTP password
$mail_SMTPSecure = 'ssl';                   // Enable TLS encryption, `ssl` also accepted
$mail_port = 465;

$user_remember_time = 86400 * 14;           // время запоминания на сайте для пользователей
$generate_password_lenght = 8;              // длина автоматически генерируемого пароля

$orders_day_wait = 5; // через сколько дней удалится неоплаченный заказ

$default_query_limit = 30;
$useMunee = false;
$useStat = false;

$merchantPercent = 3.9;                     // процент платежного агрегатора

/*
    location ~ \.(css|js)$ {
        rewrite ^/(.*)$ /munee.php?files=$1&minify=true;
    }
    #### Munee .htaccess Code Start ####
    RewriteRule ^(.*\.(?:css|js))$ munee.php?files=/$1 [L,QSA,NC]
    #### Munee .htaccess Code End ####
 */

if (!defined('PROJECT_NAME')) {
	define('PROJECT_NAME', 'Brevis');
	define('PROJECT_NAME_LOWER', 'brevis');
}

if (!defined('PROJECT_SITE_URL')) {
	define('PROJECT_SITE_URL', 'http://test.com/');
}

if (!defined('PROJECT_BASE_URL')) {
	define('PROJECT_BASE_URL', '/');
}

if (!defined('PROJECT_BASE_PATH')) {
	define('PROJECT_BASE_PATH', strtr(realpath(dirname(dirname(dirname(__FILE__)))), '\\', '/') . '/');
}

if (!defined('PROJECT_CORE_PATH')) {
	define('PROJECT_CORE_PATH', PROJECT_BASE_PATH . 'core/');
}

if (!defined('PROJECT_MODEL_PATH')) {
	define('PROJECT_MODEL_PATH', PROJECT_CORE_PATH . 'Model/');
}

if (!defined('PROJECT_TEMPLATES_PATH')) {
	define('PROJECT_TEMPLATES_PATH', PROJECT_CORE_PATH . 'Templates/');
}

if (!defined('PROJECT_CACHE_PATH')) {
	define('PROJECT_CACHE_PATH', PROJECT_CORE_PATH . 'Cache/');
}

if (!defined('PROJECT_LOG_PATH')) {
	define('PROJECT_LOG_PATH', PROJECT_CACHE_PATH . 'logs/');
}

if (!defined('PROJECT_ASSETS_PATH')) {
	define('PROJECT_ASSETS_PATH', PROJECT_BASE_PATH . 'assets/');
}

if (!defined('PROJECT_ASSETS_URL')) {
	define('PROJECT_ASSETS_URL', PROJECT_BASE_URL . 'assets/');
}

if (!defined('PROJECT_FENOM_OPTIONS')) {
	define('PROJECT_FENOM_OPTIONS', \Fenom::AUTO_RELOAD | \Fenom::FORCE_VERIFY);
}

if (!defined('PROJECT_LOG_LEVEL')) {
	define('PROJECT_LOG_LEVEL', \Monolog\Logger::DEBUG);
}

if (!defined('PROJECT_LOG_TARGET')) {
	define('PROJECT_LOG_TARGET', 'FILE'); // ECHO || FILE
}

if (!defined('PROJECT_MAIL_HOST')) {
    define('PROJECT_MAIL_HOST', $mail_host);
}

if (!defined('PROJECT_MAIL_USERNAME')) {
    define('PROJECT_MAIL_USERNAME', $mail_username);
}

if (!defined('PROJECT_MAIL_PASSWORD')) {
    define('PROJECT_MAIL_PASSWORD', $mail_password);
}

if (!defined('PROJECT_MAIL_SMTPSecure')) {
    define('PROJECT_MAIL_SMTPSecure', $mail_SMTPSecure);
}

if (!defined('PROJECT_MAIL_PORT')) {
    define('PROJECT_MAIL_PORT', $mail_port);
}

if (!defined('PROJECT_MAIL_NAME_FROM')) {
    define('PROJECT_MAIL_NAME_FROM', $mail_name_from);
}

if (!defined('PROJECT_MAIL_ADDRESS_TO')) {
    define('PROJECT_MAIL_ADDRESS_TO', $mail_address_to);
}

if (!defined('PROJECT_MAIL_FEEDBACK_TO')) {
    define('PROJECT_MAIL_FEEDBACK_TO', $mail_feedback_to);
}

if (!defined('PROJECT_MAIL_SUBJECT')) {
    define('PROJECT_MAIL_SUBJECT', $mail_subject);
}

if (!defined('PROJECT_MAIL_ADMIN')) {
    define('PROJECT_MAIL_ADMIN', $mail_admin);
}

if (!defined('PROJECT_USER_REMEMBER_TIME')) {
    define('PROJECT_USER_REMEMBER_TIME', $user_remember_time);
}

if (!defined('PROJECT_GENERATE_PASSWORD_LENGTH')) {
    define('PROJECT_GENERATE_PASSWORD_LENGTH', $generate_password_lenght);
}

if (!defined('PROJECT_DEFAULT_QUERY_LIMIT')) {
    define('PROJECT_DEFAULT_QUERY_LIMIT', $default_query_limit);
}

if (!defined('PROJECT_USE_MUNEE')) {
    define('PROJECT_USE_MUNEE', $useMunee);
}

if (!defined('PROJECT_USE_STAT')) {
    define('PROJECT_USE_STAT', $useStat);
}

if (!defined('PROJECT_ORDERS_DAY_WAIT')) {
    define('PROJECT_ORDERS_DAY_WAIT', $orders_day_wait);
}

if (!defined('PROJECT_MERCHANT_PERCENT')) {
    define('PROJECT_MERCHANT_PERCENT', $merchantPercent);
}

$database_options = array(
	\xPDO\xPDO::OPT_CACHE_PATH => PROJECT_CACHE_PATH,
	\xPDO\xPDO::OPT_HYDRATE_FIELDS => true,
	\xPDO\xPDO::OPT_HYDRATE_RELATED_OBJECTS => true,
	\xPDO\xPDO::OPT_HYDRATE_ADHOC_FIELDS => true,
);