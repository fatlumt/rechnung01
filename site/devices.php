<?php
if (!isset($lang)) { return; }
$phoneOnly = $phoneOnly ?? false;
$de = $lang === 'de';
?>
<svg viewBox="<?= $phoneOnly ? '368 142 240 265' : '0 0 620 438' ?>" role="img" aria-label="<?= $phoneOnly ? ($de ? 'Mobile Rechnung' : 'Mobile invoice') : ($de ? 'DigitalRechnung auf Laptop und Smartphone' : 'DigitalRechnung on laptop and phone') ?>" xmlns="http://www.w3.org/2000/svg">
  <?php if (!$phoneOnly): ?>
  <rect x="8" y="10" width="604" height="416" rx="42" fill="#eff7ff"/>
  <circle cx="381" cy="181" r="156" fill="#dceeff"/>
  <ellipse cx="311" cy="360" rx="239" ry="16" fill="#cbdff2"/>
  <g transform="rotate(-3 280 200)" font-family="Arial,sans-serif">
    <rect x="65" y="81" width="437" height="267" rx="16" fill="#132844"/>
    <rect x="74" y="91" width="419" height="242" rx="8" fill="#f7faff"/>
    <circle cx="282" cy="86" r="2" fill="#607792"/>
    <image href="../assets/digitalrechnung-logo.png" x="91" y="105" width="117" height="23"/>
    <path d="M74 138h419" stroke="#d9e6f2"/>
    <text x="93" y="164" fill="#183454" font-size="16" font-weight="bold"><?= $de ? 'Zahlungsübersicht' : 'Payment overview' ?></text>
    <rect x="92" y="178" width="179" height="126" rx="12" fill="#143860"/>
    <text x="105" y="201" font-size="11" fill="#deedff"><?= $de ? 'Zahlungseingänge' : 'Payments received' ?></text>
    <text x="105" y="225" font-size="20" font-weight="bold" fill="white">CHF 8’420</text>
    <path d="M107 279l22-9 17 4 20-20 18 6 22-18 15 4 28-12" stroke="#6bc5ff" stroke-width="4" fill="none" stroke-linecap="round"/>
    <rect x="281" y="178" width="188" height="126" rx="12" fill="white" stroke="#d5e5f2"/>
    <text x="294" y="200" fill="#183454" font-size="12" font-weight="bold"><?= $de ? 'Letzte Rechnungen' : 'Recent invoices' ?></text>
    <?php foreach ([1,2,3] as $row): ?>
      <circle cx="301" cy="<?= 198+$row*25 ?>" r="6" fill="#b5ddff"/>
      <text x="315" y="<?= 202+$row*25 ?>" fill="#455d77" font-size="11">RE-000<?= $row ?></text>
    <?php endforeach ?>
    <path d="M65 341h437l43 15q1 9-20 10H44q-19-1-18-10z" fill="#9db1c9"/>
    <path d="M26 356h519q-1 10-20 10H44q-18-1-18-10" fill="#506883"/>
  </g>
  <?php endif ?>
  <g transform="rotate(7 491 278)" font-family="Arial,sans-serif">
    <rect x="424" y="155" width="128" height="235" rx="21" fill="#112540"/>
    <rect x="429" y="160" width="118" height="225" rx="17" fill="#fff"/>
    <rect x="469" y="165" width="37" height="6" rx="3" fill="#142a46"/>
    <image href="../assets/digitalrechnung-logo.png" x="438" y="184" width="96" height="19"/>
    <rect x="438" y="215" width="99" height="64" rx="9" fill="#0966d6"/>
    <text x="446" y="236" font-size="10" fill="#edf7ff"><?= $de ? 'Rechnung' : 'Invoice' ?></text>
    <text x="446" y="258" font-size="16" font-weight="bold" fill="white">CHF 840</text>
    <path d="M444 299h67m-67 11h83m-83 11h58" stroke="#d6e4f4" stroke-width="4" stroke-linecap="round"/>
    <rect x="439" y="340" width="97" height="27" rx="13" fill="#dcedff"/>
    <text x="488" y="357" font-size="11" font-weight="bold" text-anchor="middle" fill="#134c88"><?= $de ? 'PDF teilen' : 'Share PDF' ?></text>
  </g>
</svg>
