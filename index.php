<?php
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; connect-src 'self'; object-src 'none'; base-uri 'none'; frame-ancestors 'none'; form-action 'self'");
?><!doctype html><html lang="de-CH"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><meta name="robots" content="noindex,nofollow"><title>digitalrechnung · Ihr Arbeitsplatz</title><link rel="icon" href="favicon.svg"><link rel="stylesheet" href="assets/app.css?v=<?=filemtime(__DIR__.'/assets/app.css')?>"></head><body><div id="root"></div><script defer src="assets/app.js?v=<?=filemtime(__DIR__.'/assets/app.js')?>"></script><noscript>Bitte JavaScript aktivieren.</noscript></body></html>
