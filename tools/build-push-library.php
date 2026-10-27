<?php
// Run after composer install: php -d phar.readonly=0 tools/build-push-library.php
declare(strict_types=1);
$root=dirname(__DIR__);$target=$root.'/backend/vendor/webpush.phar';
if(is_file($target))unlink($target);
$archive=new Phar($target);$archive->startBuffering();$archive->buildFromDirectory($root.'/vendor');$archive->setStub("<?php __HALT_COMPILER();");$archive->setSignatureAlgorithm(Phar::SHA256);$archive->stopBuffering();
echo "Built Web Push dependency archive.\n";
