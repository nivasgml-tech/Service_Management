
<?php
echo '<h2>PHP Excel Export Check</h2>';

if (class_exists('ZipArchive')) {
    echo '<p style="color:green;">ZipArchive is ENABLED.</p>';
} else {
    echo '<p style="color:red;">ZipArchive is NOT ENABLED.</p>';
}

echo '<p>PHP Version: ' . PHP_VERSION . '</p>';
