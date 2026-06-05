<?php
$pk_encoded = 'y1GTtU3hku0fOEHr7xjQMIHjbIm5IcikKHMVgZ9ym+BrUvhCYSEOW6Zp0Mq4Jty7THUE51sOO0XTVLjkbQxlhg==';
$pk = base64_decode($pk_encoded);
$pub = substr($pk, 32);
echo base64_encode($pub);
