<?php
/*
  script to check disk space on server and send an email if usage is above a certain level
  will send a test email if script is called with test parameter either via url:
    http://host/check_disk_space.php?test
  or through commane line:
    php check_disk_space.php test
*/

$FROM_EMAIL = 'robot@example.com';
$ALERT_EMAIL = 'alerts@example.com';
$EMAIL_SUBJECT = '[WARNING] - Low disk space alert on SERVER_NAME server: ' . gethostname();
$WARNING_LEVEL = 0.90; // alerts when disk usage is greater than 90% where 1.0 is 100%
$CHECK_PATHS = [ //Path of volumes to check
  '/',
  '/boot',
  '/mnt/datadisk',
  '/mnt/resource',
];


$email_headers = "From: <$FROM_EMAIL>\r\n";

function showMb($number)
{
    return number_format($number/1024/1024/1024, 2) . "Gb";
}

function getPercentageDSUsed($path)
{
    $disk_free = disk_free_space($path);
    $disk_total = disk_total_space($path);
    $percentage = 1 - $disk_free / $disk_total;
    return $percentage;
}

function checkSpace($path)
{
    global $WARNING_LEVEL;
    $percentUsed = getPercentageDSUsed($path);
    if ($percentUsed > $WARNING_LEVEL) {
        return "Paritition $path using: " . number_format($percentUsed*100, 2) . "%,  " .
            showMb(disk_free_space($path)) . " free,  " .
            showMb(disk_total_space($path)) . " Total\n";
    }
    return '';
}

function listSpace($path)
{
    $percentUsed = getPercentageDSUsed($path);
    return "Paritition $path using: " . number_format($percentUsed*100, 2) . "%,  " .
        showMb(disk_free_space($path)) . " free,  " .
        showMb(disk_total_space($path)) . " Total\n";
}

$low_space_errors = '';
$list_space = '';

for ($i=0; $i < count($CHECK_PATHS); $i++) {
    $low_space_errors .= checkSpace($CHECK_PATHS[$i]);
    $list_space .= listSpace($CHECK_PATHS[$i]);
}

if ($low_space_errors != '') {
    mail($ALERT_EMAIL, $EMAIL_SUBJECT, $low_space_errors, $email_headers);
}

if ((count($argv) == 2 && $argv[1]=="test") || isset($_GET["test"])) {
  mail(
    $ALERT_EMAIL,
    "[TEST] - Disk space usage on " . gethostname(),
    "Current disk space usage on monitored partitions:\n\n" . $list_space,
    $email_headers
  );
}
