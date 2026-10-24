<?php
declare(strict_types=1);
require __DIR__.'/../backend/core.php';
require __DIR__.'/../backend/translations.php';
$checks=0;
function check(bool $value):void {global $checks;if(!$value)throw new RuntimeException('Language validation failed');$checks++;}
foreach(['de','en','sq'] as $language)check(document_language($language)===$language);
foreach(['fr','',null,[],123] as $bad){try{document_language($bad);throw new RuntimeException('Invalid language accepted');}catch(AppError $e){check($e->status===400);}}
check(translation_text('Page {page} of {pages}','Seite {page} von {pages}')==='Page {page} of {pages}');
foreach(['Missing','{page} {page} {pages}','<b>{page} {pages}</b>',"{page}\0{pages}"] as $bad){try{translation_text($bad,'Seite {page} von {pages}');throw new RuntimeException('Invalid translation accepted');}catch(AppError $e){check($e->status===400);}}
$defaults=email_templates(null);check($defaults['invoice']['isDefault']);
$legacy=email_templates(['invoice'=>default_email_template()]);check($legacy['invoice']['isDefault']);
$custom=email_templates(['invoice'=>['subject'=>'Custom {firma}','message'=>'Custom body','isDefault'=>false]]);check(!$custom['invoice']['isDefault']);check($custom['invoice']['message']==='Custom body');
$catalog=translation_catalog();check($catalog['languages']===['de','en','sq']);
foreach($catalog['messages'] as $source=>$translations)foreach(['en','sq'] as $language)check(translation_placeholders($source)===translation_placeholders($translations[$language]));
echo "PASS: $checks language, default/custom email and translation-placeholder validation checks. No database or emails used.\n";
