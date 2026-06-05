<?php
require 'vendor/autoload.php';
$priv = base64_decode("y1GTtU3hku0fOEHr7xjQMIHjbIm5IcikKHMVgZ9ym+BrUvhCYSEOW6Zp0Mq4Jty7THUE51sOO0XTVLjkbQxlhg==");
$pub = sodium_crypto_sign_publickey_from_secretkey($priv);
echo base64_encode($pub) . "\n";
