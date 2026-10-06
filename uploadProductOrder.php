<?php
/**
 * Receives the bill PDFs that item/punchorder (and customer/uploadbill) post
 * before sending a WhatsApp bill, and keeps them in whatapporder/, where the
 * link sent to the customer points (POS_BILL_PDF_URL).
 *
 * The store's own server has its own copy of this script, writing to
 * E:/xampp/htdocs/pos/whatapporder/. This one is for the server the
 * repository is deployed on, so that a sale billed here no longer uploads to
 * the store's server. Point the senders at it with, in .env:
 *
 *   POS_UPLOAD_URL=http://127.0.0.1/uploadProductOrder.php
 *   POS_BILL_PDF_URL=http://<this host>/whatapporder/
 *   POS_UPLOAD_TOKEN=<the token both senders post>
 *
 * Differences from the store's copy, on purpose: the token comes from .env,
 * not the source; the request is not echoed back (it carried the token); and
 * the file name is reduced to a plain name ending in .pdf, so a name like
 * ../index.php cannot be written outside whatapporder/.
 */

// The same .env loader as common.php, for the one value this script needs.
$envFile = __DIR__ . '/.env';
if (getenv('POS_UPLOAD_TOKEN') === false && is_file($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
            continue;
        }
        list($k, $v) = explode('=', $line, 2);
        if (trim($k) !== 'POS_UPLOAD_TOKEN') {
            continue;
        }
        $v = trim($v);
        if (strlen($v) >= 2 && ($v[0] === '"' || $v[0] === "'") && substr($v, -1) === $v[0]) {
            $v = substr($v, 1, -1);
        }
        putenv('POS_UPLOAD_TOKEN=' . $v);
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['file'])) {
    http_response_code(400);
    die('Invalid request.');
}

$token = (string)getenv('POS_UPLOAD_TOKEN');
if ($token === '' || !isset($_POST['token']) || !hash_equals($token, (string)$_POST['token'])) {
    http_response_code(403);
    die('Unauthorized request.');
}

if ($_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    die('File upload failed! (error ' . (int)$_FILES['file']['error'] . ')');
}

if ($_FILES['file']['size'] > 24 * 1024 * 1024) {
    http_response_code(413);
    die('File size exceeds the limit of 24MB.');
}

// Bill names are the bill number, sometimes with _Reprint / -Refund.
$fileName = preg_replace('/[^A-Za-z0-9._-]/', '', basename((string)$_FILES['file']['name']));
if ($fileName === '' || $fileName[0] === '.' || strtolower(pathinfo($fileName, PATHINFO_EXTENSION)) !== 'pdf') {
    http_response_code(400);
    die('Invalid file type. Only .pdf files are allowed.');
}

$uploadDir = __DIR__ . '/whatapporder/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0775, true);
}
if (!is_writable($uploadDir)) {
    http_response_code(500);
    die('Upload directory is not writable.');
}

if (move_uploaded_file($_FILES['file']['tmp_name'], $uploadDir . $fileName)) {
    echo 'File successfully uploaded: ' . $fileName;
} else {
    http_response_code(500);
    echo 'File upload failed!';
}
