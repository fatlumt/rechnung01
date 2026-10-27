<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require __DIR__.'/../backend/core.php';
require __DIR__.'/../backend/features.php';
require __DIR__.'/../backend/mail.php';
require __DIR__.'/../backend/translations.php';
require __DIR__.'/../backend/trusted-device.php';
require __DIR__.'/../backend/push.php';
try{translations_schema();push_scan();echo "Push worker completed.\n";}catch(Throwable $e){fwrite(STDERR,'Push worker failed: '.get_class($e)."\n");exit(1);}
