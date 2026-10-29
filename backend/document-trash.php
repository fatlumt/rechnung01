<?php
declare(strict_types=1);
function document_trash_schema():void {
 static $done=false;if($done)return;
 query('CREATE TABLE IF NOT EXISTS kr_document_trash (owner CHAR(36) NOT NULL,document_id CHAR(36) NOT NULL,created DATETIME NOT NULL,PRIMARY KEY(owner,document_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');$done=true;
}
function document_is_trashed(string $owner,string $id):bool {
 return (bool)one('SELECT document_id FROM kr_document_trash WHERE owner=? AND document_id=?',[$owner,$id]);
}
function document_require_active(string $owner,string $id):void {
 if(document_is_trashed($owner,$id))fail('Dieser Beleg ist im Papierkorb. Bitte zuerst wiederherstellen.',409);
}
