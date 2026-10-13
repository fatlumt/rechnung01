<?php
// Save ON HOSTINGER as ../klarrechnung-config.php above public_html.
// Never commit database credentials to GitHub.
return [
  'db_host' => 'localhost',
  'db_port' => 3306,
  'db_name' => 'YOUR_FULL_HOSTINGER_DATABASE_NAME',
  'db_user' => 'YOUR_FULL_HOSTINGER_DATABASE_USER',
  'db_password' => 'YOUR_DATABASE_PASSWORD',
  'app_url' => 'https://digital.krasniqi-plattenleger.ch',
  // Choose a NEW random setup code of at least 32 characters; used only once.
  'setup_key' => 'REPLACE_WITH_YOUR_OWN_RANDOM_SETUP_CODE',
];
