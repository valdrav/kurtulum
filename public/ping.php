<?php
header('Content-Type: text/plain; charset=utf-8');
echo "OK — public klasörü erişilebilir\n";
echo 'Host: ' . ($_SERVER['HTTP_HOST'] ?? '-') . "\n";
