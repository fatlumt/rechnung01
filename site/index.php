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
  'mobileNote'=>'An internet connection is required.',
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

$t['headline']=$lang==='de'?'Ihr Büro. Einfacher.':'Your business. Simpler.';
$t['headline2']=$lang==='de'?'Mehr Zeit für Sie.':'More time for you.';
function icon(string $name):string{return '<img class="icon" src="icon-'.$name.'.svg" width="24" height="24" alt="" aria-hidden="true">';}
?><!doctype html>
<html lang="<?=e($lang)?>"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=e($t['title'])?></title><meta name="description" content="<?=e($t['description'])?>"><meta name="theme-color" content="#ffffff"><meta property="og:title" content="<?=e($t['title'])?>"><meta property="og:description" content="<?=e($t['description'])?>"><meta property="og:type" content="website"><link rel="alternate" hreflang="de" href="?lang=de"><link rel="alternate" hreflang="en" href="?lang=en"><link rel="icon" href="../favicon.svg"><link rel="stylesheet" href="style.css?v=brand-blue2"></head>
<body id="top"><a class="skip" href="#main"><?=e($t['skip'])?></a>
<header class="header"><div class="container header-inner"><a class="logo" href="?lang=<?=e($lang)?>"><img src="../assets/digitalrechnung-logo.png" width="780" height="152" alt="DigitalRechnung"></a><nav class="desktop-nav" aria-label="<?=e($lang==='de'?'Hauptnavigation':'Main navigation')?>"><?php foreach(['features','how','faq'] as $i=>$id):?><a href="#<?=$id?>"><?=e($t['nav'][$i])?></a><?php endforeach?></nav><div class="header-actions"><nav class="languages" aria-label="<?=e($t['language'])?>"><a lang="de" href="?lang=de" <?=$lang==='de'?'aria-current="page"':''?>>DE</a><a lang="en" href="?lang=en" <?=$lang==='en'?'aria-current="page"':''?>>EN</a></nav><a class="button black login" href="../"><?=e($t['login'])?><?=icon('arrow')?></a></div></div></header>
<main id="main"><section class="container hero"><div class="hero-copy"><p class="eyebrow"><?=e($t['eyebrow'])?></p><h1><?=e($t['headline'])?><br><em><?=e($t['headline2'])?></em></h1><p class="lead"><?=e($t['intro'])?></p><a class="button black" href="<?=e($contact)?>"><?=e($t['access'])?><?=icon('arrow')?></a><div class="hero-note"><?=icon('check')?><span><?=e($t['heroNotes'][2])?></span></div></div><figure class="hero-art"><img src="hero-studio.webp" width="1402" height="1122" alt="<?=e($lang==='de'?'DigitalRechnung auf Smartphone und Laptop mit schwebenden Belegen':'DigitalRechnung on a phone and laptop with floating invoices')?>" fetchpriority="high"><figcaption><?=e($t['example'])?></figcaption></figure></section>
<div class="container feature-strip"><?php foreach($t['strip'] as $i=>$label):?><span><?=icon(['document','document','wallet','folder','clock'][$i])?><?=e($label)?></span><?php endforeach?></div>
<section class="container section" id="features"><div class="section-heading"><h2><mark><?=e($t['nav'][0])?></mark></h2><p><?=e($t['featureIntro'])?></p></div><div class="feature-grid"><?php foreach([0,1,3,4] as $j=>$i):$f=$t['features'][$i];?><article class="feature-card <?=in_array($j,[1,2])?'dark':'light'?>"><div class="feature-content"><h3><span><?=e($f[1])?></span></h3><p><?=e($f[2])?></p><a class="learn" href="#how"><span><?=icon('arrow')?></span><?=e($lang==='de'?'So funktioniert’s':'How it works')?></a></div><div class="feature-preview" aria-hidden="true"><?php if($j===0):?><div class="mini-document"><?=icon('document')?><small><?=e($lang==='de'?'Offerte':'Quote')?></small><strong>OF-2026-001</strong><hr><span>CHF 1’250.00</span><b><?=e($lang==='de'?'Als Rechnung':'To invoice')?> <?=icon('arrow')?></b></div><?php elseif($j===1):?><div class="mini-payments"><strong><?=e($lang==='de'?'Zahlungen':'Payments')?></strong><?php foreach(['paid','unpaid','draft'] as $n=>$status):?><div><?=icon('wallet')?><span><?=e($t[$status])?></span><b><?=['840','250','160'][$n]?>.–</b></div><?php endforeach?></div><?php elseif($j===2):?><div class="mini-customer"><?=icon('folder')?><strong><?=e($t['client'])?></strong><small><?=e($lang==='de'?'Alles an einem Ort':'Everything in one place')?></small><div><?=e($lang==='de'?'Belege · Notizen · Fotos':'Documents · Notes · Photos')?></div></div><?php else:?><div class="mini-hours"><?=icon('clock')?><small><?=e($lang==='de'?'Diese Woche':'This week')?></small><strong>32 <span>h</span></strong><div><?=e($lang==='de'?'Stunden im Überblick':'Hours at a glance')?></div></div><?php endif?></div></article><?php endforeach?></div></section>
<section class="container"><div class="mobile-banner"><div><p class="eyebrow"><?=e($t['mobileEyebrow'])?></p><h2><?=e($t['mobileTitle'])?></h2><p><?=e($t['mobileText'])?></p><a class="button black" href="<?=e($contact)?>"><?=e($t['access'])?><?=icon('arrow')?></a><small><?=e($t['mobileNote'])?></small></div><?php $phoneOnly=true; include __DIR__.'/devices.php'; ?></div></section>
<section class="container section"><div class="section-heading"><h2><mark><?=e($lang==='de'?'Ihr Unternehmen. Ihr Stil.':'Your business. Your style.')?></mark></h2><p><?=e($lang==='de'?'Von den Firmendaten bis zum fertigen PDF: ein Auftritt, der zu Ihnen passt.':'From company details to the finished PDF: a look that feels like your business.')?></p></div><div class="benefits"><?php foreach([2,5] as $i):?><article><?=icon($i===2?'document':'arrow')?><h3><?=e($t['features'][$i][1])?></h3><p><?=e($t['features'][$i][2])?></p></article><?php endforeach?><article><?=icon('check')?><h3><?=e($t['heroNotes'][0])?></h3><p><?=e($lang==='de'?'Firmenprofile für CHF und EUR. Die App spricht Deutsch, Englisch und Albanisch.':'Company profiles for CHF and EUR. The app speaks German, English and Albanian.')?></p></article></div></section>
<section class="container section how-section" id="how"><div class="section-heading"><h2><mark><?=e($lang==='de'?'In drei Schritten startklar.':'Ready in three steps.')?></mark></h2><p><?=e($t['stepsTitle'])?></p></div><div class="steps"><?php foreach($t['steps'] as $i=>[$title,$body]):?><article><span class="step-number">0<?=$i+1?></span><div><h3><?=e($title)?></h3><p><?=e($body)?></p></div></article><?php endforeach?></div></section>
<section class="container section faq-grid" id="faq"><div><p class="eyebrow"><?=e($t['faqEyebrow'])?></p><h2><?=e($t['faqTitle'])?></h2><p><?=e($lang==='de'?'Wir helfen Ihnen gerne weiter.':'We’re happy to help.')?></p><a class="text-link" href="mailto:info@krasniqi-plattenleger.ch"><?=e($t['contact'])?><?=icon('arrow')?></a></div><div class="faqs"><?php foreach($t['faqs'] as [$question,$answer]):?><details><summary><?=e($question)?><?=icon('plus')?></summary><p><?=e($answer)?></p></details><?php endforeach?></div></section>
<section class="container cta-wrap"><div class="cta"><div><p class="eyebrow"><?=e($t['ctaEyebrow'])?></p><h2><?=e($t['ctaTitle'])?></h2><p><?=e($t['ctaText'])?></p></div><a class="button ice" href="<?=e($contact)?>"><?=e($t['access'])?><?=icon('arrow')?></a></div></section></main>
<footer class="container footer"><div class="footer-brand"><a class="logo" href="#top"><img src="../assets/digitalrechnung-logo.png" width="780" height="152" alt="DigitalRechnung"></a><p><?=e($t['footer'])?></p></div><div class="footer-links"><a href="#features"><?=e($t['nav'][0])?></a><a href="mailto:info@krasniqi-plattenleger.ch"><?=e($t['contact'])?></a><a href="../"><?=e($t['login'])?></a></div><div class="footer-bottom"><small>© <?=date('Y')?> DigitalRechnung</small><a href="#top"><?=e($t['back'])?><?=icon('arrow')?></a></div></footer><script src="transitions.js?v=brand-blue2" defer></script></body></html>
