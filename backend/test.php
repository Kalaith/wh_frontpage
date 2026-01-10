<?php
echo "PHP is working.\n";
$f = __DIR__ . '/vendor/autoload.php';
if (file_exists($f)) {
    echo "Autoload found.\n";
} else {
    echo "Autoload NOT found at $f.\n";
}
