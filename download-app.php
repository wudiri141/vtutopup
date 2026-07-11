<?php
$apkFile = __DIR__ . '/downloads/vtu-topup.apk';
$downloadName = 'vtu-topup.apk';

if (!is_file($apkFile)) {
    http_response_code(404);
    echo 'The VTU TOPUP APK is not available yet.';
    exit;
}

if (!is_readable($apkFile)) {
    http_response_code(403);
    echo 'The VTU TOPUP APK file cannot be read. Please check file permissions.';
    exit;
}

while (ob_get_level()) {
    ob_end_clean();
}

header('Content-Type: application/vnd.android.package-archive');
header('Content-Disposition: attachment; filename="' . $downloadName . '"');
header('Content-Transfer-Encoding: binary');
header('Content-Length: ' . filesize($apkFile));
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

readfile($apkFile);
exit;
