<?php
require_once __DIR__ . '/config.php';

echo "<pre>";
echo "BASE_PATH: " . BASE_PATH . "\n";
echo "DATA_DIR:  " . DATA_DIR . "\n";
echo "UPLOAD_DIR:" . UPLOAD_DIR . "\n\n";

echo "Does DATA_DIR exist? ";
var_dump(is_dir(DATA_DIR));

echo "Is DATA_DIR writable? ";
var_dump(is_writable(DATA_DIR));

$testFile = DATA_DIR . '/test.txt';
$result = @file_put_contents($testFile, "Hello from test_write.php at " . date('Y-m-d H:i:s'));

echo "\nTrying to write to: $testFile\n";
echo "file_put_contents result: ";
var_dump($result);

if ($result === false) {
    echo "\n❌ file_put_contents failed. This means PHP does not have permission to write to DATA_DIR.\n";
} else {
    echo "\n✅ Success! test.txt was written. Open it in your file manager to confirm.\n";
}

echo "</pre>";
