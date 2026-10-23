<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/backend/core.php';
security_headers();
$lang=($_GET['lang']??'de')==='en'?'en':'de';
header('Content-Type: text/html; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Content-Security-Policy: default-src 'self'; style-src 'self'; img-src 'self' data:; script-src 'self'; object-src 'none'; base-uri 'self'; frame-ancestors 'none'; form-action 'self'");
$copy=[
 'de'=>[
  'title'=>'DigitalRechnung – Weniger Büro. Mehr Zeit fürs Geschäft.',
  'description'=>'Rechnungen, Offerten, Zahlungserinnerungen und Arbeitszeiten an einem Ort. Entdecken Sie DigitalRechnung für kleine Unternehmen in der Schweiz und Deutschland.',
  'nav'=>['Funktionen','So funktioniert’s','Fragen'],'login'=>'Anmelden','access'=>'Zugang anfragen','explore'=>'Funktionen entdecken',
  'eyebrow'=>'FÜR UNTERNEHMEN, DIE ANPACKEN',
  'headline'=>'Weniger Büro.','headline2'=>'Mehr Zeit fürs Geschäft.',
  'intro'=>'Von der ersten Offerte bis zur bezahlten Rechnung: Belege, Kunden und Arbeitszeiten an einem Ort. Auf dem Handy genauso wie am Schreibtisch.',
  'heroNotes'=>['Schweiz & Deutschland','Ihre Firma. Ihr Design.','Im Browser, ohne Installation'],
  'preview'=>'Ein Blick in Ihren Arbeitsalltag','example'=>'Illustrative Vorschau · Beispieldaten',
  'welcome'=>'Willkommen zurück','company'=>'Ihr Unternehmen','overview'=>'Alles im Überblick.','newInvoice'=>'+ Neue Rechnung',
  'open'=>'Offene Rechnungen','received'=>'Zahlungseingänge','latest'=>'Letzte Belege','client'=>'Musterkunde','paid'=>'Bezahlt','unpaid'=>'Offen','draft'=>'Entwurf',
  'phoneTitle'=>'Auch unterwegs.','phoneLine'=>'Eine Rechnung. Wenige Schritte.','phoneButton'=>'Speichern & PDF',
  'strip'=>['Offerten','Rechnungen','Mahnwesen','Kunden','Arbeitszeiten'],
  'featureEyebrow'=>'ALLES, WAS ZUSAMMENGEHÖRT','featureTitle'=>'Ihr Büro. Ein bisschen einfacher.',
  'featureIntro'=>'Weniger zwischen Listen und Dokumenten wechseln. Mehr Überblick über das, was Ihr Unternehmen voranbringt.',
  'features'=>[
   ['01','Von der Offerte zur Rechnung','Erstellen Sie eine Offerte und übernehmen Sie sie mit einem Klick in einen Rechnungsentwurf. Prüfen, anpassen, speichern.','blue'],
   ['02','Offene Zahlungen im Blick','Sehen Sie, was offen, bezahlt oder überfällig ist. Erstellen Sie Zahlungserinnerungen und Mahnungen aus der passenden Rechnung.','amber'],
   ['03','Ein Auftritt, der zu Ihnen passt','Firmenlogo, Farben, Belegdesigns und eigene E-Mail-Texte. Ihre Unterlagen tragen die Handschrift Ihres Unternehmens.','purple'],
   ['04','Kunden an einem Ort','Speichern Sie Kontaktdaten einmal und nutzen Sie sie für weitere Belege. Neue Kunden können Sie direkt beim Erstellen ergänzen.','blue'],
   ['05','Arbeitszeiten festhalten','Erfassen Sie als verantwortliche Person die Stunden Ihrer Mitarbeitenden und behalten Sie die Einträge im Überblick.','green'],
   ['06','PDF fertig. Nächster Schritt einfach.','Laden Sie Belege als PDF herunter, versenden Sie sie per E-Mail oder teilen Sie sie über das Teilen-Menü Ihres Geräts.','purple'],
  ],
  'mobileEyebrow'=>'FÜR DEN ALLTAG GEMACHT','mobileTitle'=>'Ihr Büro ist dort, wo Sie sind.',
  'mobileText'=>'Auf der Baustelle, beim Kunden oder zu Hause: Öffnen Sie DigitalRechnung im Browser und erledigen Sie die nächsten Schritte direkt auf Ihrem Handy.',
  'mobilePoints'=>['Auf dem Home Screen ablegen und direkt öffnen','Kunden und Belege auch unterwegs aufrufen','Firmenlogo und Farben in den Einstellungen anpassen'],
  'mobileNote'=>'Eine Internetverbindung ist erforderlich.',
  'stepsEyebrow'=>'SO STARTEN SIE','stepsTitle'=>'Von Ihren Firmendaten zum ersten Beleg.',
  'steps'=>[['Firma einrichten','Hinterlegen Sie Adresse, Logo, Firmenfarbe und Zahlungsdaten.'],['Kunde & Beleg erstellen','Wählen Sie einen Kunden und ergänzen Sie Leistungen, Preise und bei Bedarf Abschnitte.'],['Prüfen & weitergeben','Prüfen Sie die Vorschau, speichern Sie den Beleg und laden oder versenden Sie das PDF.']],
  'faqEyebrow'=>'GUT ZU WISSEN','faqTitle'=>'Noch eine Frage?',
  'faqs'=>[
   ['Für wen ist DigitalRechnung gedacht?','Für kleine Unternehmen und Selbstständige, die Offerten, Rechnungen, Kunden und Arbeitszeiten übersichtlich verwalten möchten – insbesondere im Handwerk und im Dienstleistungsbereich.'],
   ['Kann ich die App auf dem iPhone nutzen?','Ja. Sie können die App im Browser öffnen und auf dem Home Screen ablegen. Sie benötigen dafür keine App-Store-Installation. Eine Internetverbindung ist erforderlich.'],
   ['Unterstützt die App die Schweiz und Deutschland?','Es gibt Firmenprofile für die Schweiz und Deutschland mit CHF beziehungsweise EUR. Aktuell ist die Belegerstellung auf Inlandsgeschäfte ausgelegt. Deutsche Rechnungen werden als PDF erstellt; ein strukturiertes E-Rechnungsformat ist damit nicht gemeint.'],
   ['Wie bekomme ich Zugang?','Fragen Sie einen Zugang per E-Mail an. Wir melden uns mit den nächsten Schritten. Bestehende Nutzer können sich über „Anmelden“ direkt in der App einloggen.'],
   ['Sind alle Funktionen in jedem Konto verfügbar?','Die verfügbaren Funktionen werden pro Firmenkonto freigeschaltet. Welche Funktionen und Konditionen für Ihr Unternehmen passen, klären wir bei der Zugangsanfrage.'],
   ['Kann ich Belege direkt per E-Mail senden?','Ja, wenn der E-Mail-Versand für Ihr Konto eingerichtet und freigeschaltet ist. Sie können Empfänger, Betreff und Nachricht vor jedem Versand prüfen. Alternativ können Sie das PDF herunterladen oder über Ihr Gerät teilen.'],
  ],
  'ctaEyebrow'=>'BEREIT FÜR MEHR ÜBERBLICK?','ctaTitle'=>'Machen Sie Platz für Ihr Geschäft.','ctaText'=>'Lernen Sie DigitalRechnung kennen und fragen Sie einen Zugang für Ihr Unternehmen an.',
  'footer'=>'Belege, Kunden und Arbeitszeiten. Einfach zusammen.','contact'=>'Kontakt','mailSubject'=>'Zugang zu DigitalRechnung anfragen','mailBody'=>"Guten Tag\n\nich interessiere mich für DigitalRechnung.\n\nFirma:\nLand:\nGewünschte Funktionen:\n\nFreundliche Grüsse",
  'skip'=>'Zum Inhalt','language'=>'Sprache wählen','back'=>'Nach oben',
 ],
 'en'=>[
  'title'=>'DigitalRechnung – Less paperwork. More time for your business.',
  'description'=>'Invoices, quotes, payment reminders and employee hours in one place. Discover DigitalRechnung for small businesses in Switzerland and Germany.',
  'nav'=>['Features','How it works','Questions'],'login'=>'Log in','access'=>'Request access','explore'=>'Explore features',
  'eyebrow'=>'FOR BUSINESSES THAT GET THINGS DONE',
  'headline'=>'Less paperwork.','headline2'=>'More time for your business.',
  'intro'=>'From your first quote to a paid invoice: documents, customers and working hours in one place. On your phone or at your desk.',
  'heroNotes'=>['Switzerland & Germany','Your business. Your style.','In your browser, no installation'],
  'preview'=>'A glimpse of your working day','example'=>'Illustrative preview · Sample data',
  'welcome'=>'Welcome back','company'=>'Your company','overview'=>'Everything at a glance.','newInvoice'=>'+ New invoice',
  'open'=>'Open invoices','received'=>'Payments received','latest'=>'Recent documents','client'=>'Sample customer','paid'=>'Paid','unpaid'=>'Open','draft'=>'Draft',
  'phoneTitle'=>'On the go, too.','phoneLine'=>'One invoice. A few simple steps.','phoneButton'=>'Save & PDF',
  'strip'=>['Quotes','Invoices','Payment reminders','Customers','Working hours'],
  'featureEyebrow'=>'BRING YOUR WORK TOGETHER','featureTitle'=>'Your admin. A little simpler.',
  'featureIntro'=>'Spend less time switching between lists and documents. Get a clearer picture of what keeps your business moving.',
  'features'=>[
   ['01','From quote to invoice','Create a quote and turn it into an invoice draft with one click. Review, adjust and save.','blue'],
   ['02','Keep track of unpaid invoices','See what is open, paid or overdue. Create payment reminders and dunning notices from the relevant invoice.','amber'],
   ['03','Make it look like your business','Your logo, colours, document designs and email templates. Give your documents a consistent company identity.','purple'],
   ['04','Your customers, in one place','Save contact details once and reuse them for future documents. Add new customers while creating an invoice.','blue'],
   ['05','Keep a record of working hours','As the account manager, record your employees’ hours and review the entries in one place.','green'],
   ['06','PDF ready. Easy to share.','Download documents as PDFs, send them by email or share them using your device’s sharing menu.','purple'],
  ],
  'mobileEyebrow'=>'MADE FOR EVERYDAY WORK','mobileTitle'=>'Your office goes where you go.',
  'mobileText'=>'At a job site, with a customer or back at home: open DigitalRechnung in your browser and take the next step from your phone.',
  'mobilePoints'=>['Add it to your Home Screen for quick access','Open customer details and documents on the go','Adjust your company logo and colours in Settings'],
  'mobileNote'=>'An internet connection is required. The app interface is currently in German.',
  'stepsEyebrow'=>'GETTING STARTED','stepsTitle'=>'From company details to your first document.',
  'steps'=>[['Set up your company','Add your address, logo, company colour and payment details.'],['Create a customer & document','Choose a customer and add services, prices and optional sections.'],['Review & share','Check the preview, save the document and download or email the PDF.']],
  'faqEyebrow'=>'GOOD TO KNOW','faqTitle'=>'Anything else on your mind?',
  'faqs'=>[
   ['Who is DigitalRechnung for?','Small businesses and self-employed people who want to organise quotes, invoices, customers and employee hours – especially trades and service businesses.'],
   ['Can I use it on my iPhone?','Yes. Open the app in your browser and add it to your Home Screen. No App Store installation is needed. An internet connection is required.'],
   ['Does it support Switzerland and Germany?','Company profiles support Switzerland and Germany with CHF and EUR respectively. Document creation currently covers domestic transactions. German invoices are generated as PDFs, not as structured electronic invoices.'],
   ['How do I get access?','Request access by email and we will get back to you with the next steps. Existing users can open the app using the Log in button.'],
   ['Is every feature available in every account?','Features are enabled for each company account. We will discuss the features and terms that suit your business when you request access.'],
   ['Can I send documents directly by email?','Yes, if email sending is configured and enabled for your account. Review the recipient, subject and message before sending. You can also download the PDF or share it from your device.'],
  ],
  'ctaEyebrow'=>'READY FOR A CLEARER PICTURE?','ctaTitle'=>'Make room for your business.','ctaText'=>'Discover DigitalRechnung and request access for your company.',
  'footer'=>'Documents, customers and working hours. Together.','contact'=>'Contact','mailSubject'=>'Request access to DigitalRechnung','mailBody'=>"Hello\n\nI am interested in DigitalRechnung.\n\nCompany:\nCountry:\nFeatures of interest:\n\nKind regards",
  'skip'=>'Skip to content','language'=>'Choose language','back'=>'Back to top',
 ]
];
$t=$copy[$lang];
function e(string $v):string{return htmlspecialchars($v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
$contact='mailto:info@krasniqi-plattenleger.ch?subject='.rawurlencode($t['mailSubject']).'&body='.rawurlencode($t['mailBody']);
?><!doctype html>
<html lang="<?=e($lang)?>"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title><?=e($t['title'])?></title><meta name="description" content="<?=e($t['description'])?>"><meta name="theme-color" content="#071a37"><meta property="og:title" content="<?=e($t['title'])?>"><meta property="og:description" content="<?=e($t['description'])?>"><meta property="og:type" content="website"><link rel="alternate" hreflang="de" href="?lang=de"><link rel="alternate" hreflang="en" href="?lang=en"><link rel="icon" href="../favicon.svg?v=logo2"><link rel="stylesheet" href="style.css?v=2"></head>
<body id="top"><a class="skip" href="#main"><?=e($t['skip'])?></a>
<header class="header"><div class="container header-inner"><a class="logo" href="?lang=<?=e($lang)?>" aria-label="DigitalRechnung"><img src="../assets/digitalrechnung-logo.png" width="780" height="152" alt="DigitalRechnung"></a><nav class="desktop-nav" aria-label="<?=e($lang==='de'?'Hauptnavigation':'Main navigation')?>"><?php foreach(['features','how','faq'] as $i=>$id):?><a href="#<?=$id?>"><?=e($t['nav'][$i])?></a><?php endforeach?></nav><div class="header-actions"><nav class="languages" aria-label="<?=e($t['language'])?>"><a lang="de" href="?lang=de" <?=$lang==='de'?'aria-current="page"':''?>>DE</a><a lang="en" href="?lang=en" <?=$lang==='en'?'aria-current="page"':''?>>EN</a></nav><a class="login" href="../"><?=e($t['login'])?> <span aria-hidden="true">↗</span></a></div></div><progress class="reading-progress" max="100" value="0" aria-hidden="true" hidden></progress></header>
<main id="main">
<section class="hero"><div class="container hero-grid"><div class="hero-copy"><p class="eyebrow"><span></span><?=e($t['eyebrow'])?></p><h1><?=e($t['headline'])?> <em><?=e($t['headline2'])?></em></h1><p class="lead"><?=e($t['intro'])?></p><div class="actions"><a class="button primary" href="<?=e($contact)?>"><?=e($t['access'])?> <span aria-hidden="true">↗</span></a><a class="button secondary" href="#features"><?=e($t['explore'])?> <span aria-hidden="true">↓</span></a></div><ul class="hero-notes"><?php foreach($t['heroNotes'] as $note):?><li><span aria-hidden="true">✓</span><?=e($note)?></li><?php endforeach?></ul></div>
<div class="product-scene"><div class="scene-orbit" aria-hidden="true"></div><div class="dashboard"><div class="dashboard-top"><img src="../assets/digitalrechnung-logo.png" width="780" height="152" alt="DigitalRechnung"><span class="sample-avatar">M</span></div><div class="dashboard-content"><div class="sample-company"><span class="company-mark">M.</span><div><small><?=e($t['welcome'])?></small><strong><?=e($t['company'])?></strong></div></div><h2><?=e($t['overview'])?></h2><div class="sample-create"><?=e($t['newInvoice'])?><span>↗</span></div><div class="sample-metrics"><div><small><?=e($t['open'])?></small><strong><span>CHF</span> 4’280.00</strong><i class="metric-line blue"></i></div><div><small><?=e($t['received'])?></small><strong><span>CHF</span> 8’640.00</strong><i class="metric-line green"></i></div></div><div class="sample-table"><h3><?=e($t['latest'])?></h3><?php foreach(['paid','unpaid','draft'] as $i=>$status):?><div class="sample-row"><span class="document-symbol" aria-hidden="true">▤</span><span><strong>RE-2026-00<?=$i+1?></strong><small><?=e($t['client'])?></small></span><b class="status <?=$status?>"><?=e($t[$status])?></b></div><?php endforeach?></div></div></div><div class="floating-note"><span aria-hidden="true">✓</span><div><strong>PDF</strong><small><?=e($t['phoneButton'])?></small></div></div><p class="preview-caption"><?=e($t['example'])?></p></div></div></section>
<div class="feature-strip"><div class="container"><?php foreach($t['strip'] as $item):?><span><b aria-hidden="true">✦</b><?=e($item)?></span><?php endforeach?></div></div>
<section class="section container" id="features"><div class="section-heading"><p class="eyebrow"><?=e($t['featureEyebrow'])?></p><h2><?=e($t['featureTitle'])?></h2><p><?=e($t['featureIntro'])?></p></div><div class="feature-grid"><?php foreach($t['features'] as [$n,$title,$body,$color]):?><article class="feature-card <?=$color?>"><span class="feature-number"><?=$n?></span><h3><?=e($title)?></h3><p><?=e($body)?></p></article><?php endforeach?></div></section>
<section class="mobile-section"><div class="container mobile-grid"><div class="phone-scene"><div class="phone"><div class="phone-speaker" aria-hidden="true"></div><img class="phone-logo" src="../assets/digitalrechnung-logo.png" width="780" height="152" alt="DigitalRechnung"><p class="phone-greeting"><?=e($t['company'])?></p><h3><?=e($t['phoneTitle'])?></h3><p><?=e($t['phoneLine'])?></p><div class="phone-doc"><span>▤</span><strong>RE-2026-001</strong><small><?=e($t['client'])?></small><div class="paper-lines"><i></i><i></i><i></i></div><b>CHF 1’250.00</b></div><div class="phone-save"><?=e($t['phoneButton'])?> <span>↓</span></div><div class="phone-home" aria-hidden="true"></div></div><p class="preview-caption"><?=e($t['example'])?></p></div><div class="mobile-copy"><p class="eyebrow"><?=e($t['mobileEyebrow'])?></p><h2><?=e($t['mobileTitle'])?></h2><p><?=e($t['mobileText'])?></p><ul class="check-list"><?php foreach($t['mobilePoints'] as $point):?><li><span>✓</span><?=e($point)?></li><?php endforeach?></ul><small class="mobile-note"><?=e($t['mobileNote'])?></small></div></div></section>
<section class="section container" id="how"><div class="section-heading"><p class="eyebrow"><?=e($t['stepsEyebrow'])?></p><h2><?=e($t['stepsTitle'])?></h2></div><div class="steps"><?php foreach($t['steps'] as $i=>[$title,$body]):?><article><span>0<?=$i+1?></span><h3><?=e($title)?></h3><p><?=e($body)?></p></article><?php endforeach?></div></section>
<section class="faq-section" id="faq"><div class="container faq-grid"><div><p class="eyebrow"><?=e($t['faqEyebrow'])?></p><h2><?=e($t['faqTitle'])?></h2><a class="text-link" href="mailto:info@krasniqi-plattenleger.ch"><?=e($t['contact'])?> ↗</a></div><div class="faqs"><?php foreach($t['faqs'] as [$question,$answer]):?><details><summary><?=e($question)?><span aria-hidden="true">+</span></summary><p><?=e($answer)?></p></details><?php endforeach?></div></div></section>
<section class="container cta-wrap"><div class="cta"><p class="eyebrow"><?=e($t['ctaEyebrow'])?></p><h2><?=e($t['ctaTitle'])?></h2><p><?=e($t['ctaText'])?></p><a class="button white" href="<?=e($contact)?>"><?=e($t['access'])?> <span aria-hidden="true">↗</span></a></div></section>
</main><footer class="container footer"><div><a class="logo" href="#top"><img src="../assets/digitalrechnung-logo.png" width="780" height="152" alt="DigitalRechnung"></a><p><?=e($t['footer'])?></p></div><div class="footer-links"><a href="mailto:info@krasniqi-plattenleger.ch"><?=e($t['contact'])?></a><a href="../"><?=e($t['login'])?></a><a href="#top"><?=e($t['back'])?> ↑</a></div><small>© <?=date('Y')?> DigitalRechnung</small></footer><script defer src="transitions.js?v=3"></script></body></html>
