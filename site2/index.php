<?php
declare(strict_types=1);
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
<html lang="<?=e($lang)?>"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=e($t['title'])?></title><meta name="description" content="<?=e($t['description'])?>"><meta name="theme-color" content="#101d2c"><link rel="icon" href="../favicon.svg?v=logo2"><link rel="alternate" hreflang="de" href="?lang=de"><link rel="alternate" hreflang="en" href="?lang=en"><link rel="stylesheet" href="style.css?v=3d1"><link rel="stylesheet" href="depth.css?v=1"><link rel="stylesheet" href="footer.css?v=1"><script src="depth.js?v=1" defer></script></head>
<body><a class="skip" href="#main"><?=e($t['skip'])?></a><header><div class="wrap nav"><a class="brand" href="?lang=<?=e($lang)?>"><img src="../assets/digitalrechnung-logo.png" width="780" height="152" alt="DigitalRechnung"></a><nav class="links" aria-label="<?=e($lang==='de'?'Navigation':'Navigation')?>"><a href="#features"><?=e($t['nav'][0])?></a><a href="#how"><?=e($t['nav'][1])?></a><a href="#faq"><?=e($t['nav'][2])?></a></nav><div class="nav-end"><nav class="lang" aria-label="<?=e($t['language'])?>"><a href="?lang=de" lang="de" <?=$lang==='de'?'aria-current="page"':''?>>DE</a><a href="?lang=en" lang="en" <?=$lang==='en'?'aria-current="page"':''?>>EN</a></nav><a class="login" href="../"><?=e($t['login'])?> ↗</a></div></div></header>
<main id="main"><section class="hero"><div class="wrap"><div class="hero-top"><span class="kicker"><i></i><?=e($t['eyebrow'])?></span><span class="market">CH / DE</span></div><div class="hero-layout"><div class="hero-copy"><h1><?=e($t['headline'])?><br><span><?=e($t['headline2'])?></span></h1><div class="hero-aside"><p><?=e($t['intro'])?></p><a class="button lime" href="<?=e($contact)?>"><?=e($t['access'])?> <b>↗</b></a><a class="quiet" href="#features"><?=e($t['explore'])?> ↓</a></div></div><div class="scene" aria-label="<?=e($t['preview'])?>"><div class="scene-halo" aria-hidden="true"></div><div class="scene-ring" aria-hidden="true"></div><div class="scene-stage"><div class="float-card float-paid"><span class="float-check" aria-hidden="true">✓</span><div><small><?=e($t['paid'])?></small><strong>CHF 1’250.00</strong></div></div><div class="float-card float-pdf"><span aria-hidden="true">↓</span><div><strong>PDF</strong><small><?=e($t['strip'][1])?></small></div></div><div class="workspace-demo"><aside class="demo-side"><img src="../assets/icons/app-192.png?v=logo2" width="192" height="192" alt=""><span class="side-active">▦ <i><?=e($lang==='de'?'Übersicht':'Overview')?></i></span><span>▤ <i><?=e($t['strip'][1])?></i></span><span>▱ <i><?=e($t['strip'][0])?></i></span><span>♙ <i><?=e($t['strip'][3])?></i></span><span>◷ <i><?=e($t['strip'][4])?></i></span><small>DigitalRechnung</small></aside><div class="demo-main"><div class="demo-title"><div><small><?=e($t['welcome'])?></small><h2><?=e($t['overview'])?></h2></div><span class="demo-avatar">M</span></div><div class="demo-grid"><div class="demo-stats"><div class="stat"><span><?=e($t['open'])?></span><strong>4’280<span>.00</span></strong><small>CHF</small><div class="bars" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i></div></div><div class="stat mint"><span><?=e($t['received'])?></span><strong>8’640<span>.00</span></strong><small>CHF</small><b class="stat-arrow" aria-hidden="true">↗</b></div></div><div class="demo-doc"><div class="doc-top"><span>▤</span><b>RE-2026-001</b><small class="paid"><?=e($t['paid'])?></small></div><div class="doc-business"><?=e($t['company'])?><small><?=e($t['client'])?></small></div><div class="doc-row"><span><?=e($lang==='de'?'Leistungen':'Services')?></span><span>CHF</span></div><div class="doc-line"><i></i><b>980.00</b></div><div class="doc-line"><i></i><b>270.00</b></div><div class="doc-total"><span>Total</span><strong>1’250.00</strong></div><div class="doc-button"><?=e($t['phoneButton'])?> ↓</div></div></div></div></div></div><p class="caption"><?=e($t['example'])?></p></div></div></div></section>
<section class="promise-strip"><div class="wrap"><?php foreach($t['heroNotes'] as $note):?><span><b>✓</b><?=e($note)?></span><?php endforeach?></div></section>
<section class="section wrap" id="features"><div class="section-title"><span class="kicker">01 / <?=e($t['nav'][0])?></span><h2><?=e($t['featureTitle'])?></h2><p><?=e($t['featureIntro'])?></p></div><div class="bento"><?php foreach($t['features'] as $i=>[$n,$title,$body,$color]):?><article class="feature f<?=$i?>"><span class="feature-id"><?=$n?> <b><?=['↗','◷','✦','♙','◴','↓'][$i]?></b></span><h3><?=e($title)?></h3><p><?=e($body)?></p><?php if($i===0):?><div class="flow"><span><?=e($t['strip'][0])?></span><b>→</b><span><?=e($t['strip'][1])?></span></div><?php endif?></article><?php endforeach?></div></section>
<section class="mobile"><div class="wrap mobile-grid"><div><span class="kicker"><?=e($t['mobileEyebrow'])?></span><h2><?=e($t['mobileTitle'])?></h2><p><?=e($t['mobileText'])?></p><ul><?php foreach($t['mobilePoints'] as $point):?><li><b>↗</b><?=e($point)?></li><?php endforeach?></ul><small><?=e($t['mobileNote'])?></small></div><div class="mobile-art"><span class="orbit one" aria-hidden="true"></span><span class="orbit two" aria-hidden="true"></span><div class="phone"><div class="speaker"></div><img src="../assets/digitalrechnung-logo.png" width="780" height="152" alt="DigitalRechnung"><small><?=e($t['welcome'])?></small><h3><?=e($t['phoneTitle'])?></h3><div class="phone-stat"><small><?=e($t['received'])?></small><strong>CHF 8’640.00</strong><span>↗</span></div><div class="phone-card"><b>RE-2026-001</b><small><?=e($t['client'])?></small><div class="phone-lines"><i></i><i></i><i></i></div><strong>CHF 1’250.00</strong></div><div class="phone-button"><?=e($t['phoneButton'])?> ↓</div><div class="home-line"></div></div><p class="caption"><?=e($t['example'])?></p></div></div></section>
<section class="section wrap" id="how"><div class="section-title"><span class="kicker">02 / <?=e($t['nav'][1])?></span><h2><?=e($t['stepsTitle'])?></h2></div><div class="steps"><?php foreach($t['steps'] as $i=>[$title,$body]):?><article><span class="step-no">0<?=$i+1?></span><h3><?=e($title)?></h3><p><?=e($body)?></p></article><?php endforeach?></div></section>
<section class="faq-section" id="faq"><div class="wrap faq-grid"><div><span class="kicker">03 / <?=e($t['nav'][2])?></span><h2><?=e($t['faqTitle'])?></h2><a href="mailto:info@krasniqi-plattenleger.ch" class="quiet dark"><?=e($t['contact'])?> ↗</a></div><div><?php foreach($t['faqs'] as [$question,$answer]):?><details><summary><?=e($question)?><b>+</b></summary><p><?=e($answer)?></p></details><?php endforeach?></div></div></section>
<section class="wrap final-wrap"><div class="final"><span class="kicker"><?=e($t['ctaEyebrow'])?></span><div><h2><?=e($t['ctaTitle'])?></h2><a class="button navy" href="<?=e($contact)?>"><?=e($t['access'])?> ↗</a></div><p><?=e($t['ctaText'])?></p></div></section></main>
<footer class="site-footer" id="footer"><div class="wrap"><div class="footer-grid"><div class="footer-identity"><a class="brand footer-logo" href="?lang=<?=e($lang)?>"><img src="../assets/digitalrechnung-logo.png" width="780" height="152" alt="DigitalRechnung"></a><p><?=e($t['footer'])?></p><span class="footer-market">CH / DE <span>·</span> <?=e($t['heroNotes'][0])?></span></div><nav class="footer-column" aria-label="<?=e($lang==='de'?'Produktnavigation':'Product navigation')?>"><h3><?=e($lang==='de'?'Entdecken':'Explore')?></h3><a href="#features"><?=e($t['nav'][0])?></a><a href="#how"><?=e($t['nav'][1])?></a><a href="#faq"><?=e($t['nav'][2])?></a><a href="../"><?=e($t['login'])?> ↗</a></nav><div class="footer-column footer-contact"><h3><?=e($t['contact'])?></h3><p><?=e($lang==='de'?'Fragen oder Interesse an DigitalRechnung? Schreiben Sie uns.':'Questions or interested in DigitalRechnung? Get in touch.')?></p><a class="footer-email" href="mailto:info@krasniqi-plattenleger.ch">info@krasniqi-plattenleger.ch ↗</a><a class="footer-access" href="<?=e($contact)?>"><?=e($t['access'])?> <span>→</span></a></div></div><div class="footer-base"><small>© <?=date('Y')?> DigitalRechnung</small><nav class="footer-languages" aria-label="<?=e($t['language'])?>"><a href="?lang=de#footer" lang="de" <?=$lang==='de'?'aria-current="page"':''?>>Deutsch</a><a href="?lang=en#footer" lang="en" <?=$lang==='en'?'aria-current="page"':''?>>English</a></nav><a class="footer-up" href="#main"><?=e($t['back'])?> <span>↑</span></a></div></div></footer></body></html>
