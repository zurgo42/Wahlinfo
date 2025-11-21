<?php // Sammlung der gängigen Funktionen
// Startwerte einheitlich
 date_default_timezone_set('Europe/Berlin');
 $datum = date("Y.m.d - H:i"); $timetag = 24*60*60;

 $ipad = getenv("REMOTE_ADDR"); $ipad = substr($ipad, 0,14);
 if(getenv("HTTP_REFERER")) {$ref = substr(getenv("HTTP_REFERER"),7);} else {$ref = "direkt";}
 $dns = @gethostbyaddr($ipad);

 srand((double)microtime()*1000000); // Anfangswert für Zufallszahlen setzen

// Steuerung Mobilgeräte: Die css-Datei ist die im obersten Verzeichnis - nicht die hier lokale!!!

 if (isset($_GET['mobil'])) {
	$mobil = $_GET['mobil'];} else { 
	$mobil = 2*(preg_match("/(alcatel|amoi|android|avantgo|blackberry|benq|cell|cricket|docomo|elaine
			|htc|iemobile|iphone|ipad|ipaq|ipod|j2me|java|midp|mini|mmp|mobi|motorola|nec-|nokia|palm|
			panasonic|philips|phone|playbook|sagem|SamsungBrowser|sharp|sie-|silk|smartphone|sony|symbian|t-mobile|telus
			|up\.browser|up\.link|vodafone|wap|webos|wireless|xda|xoom|zte)/i", $_SERVER['HTTP_USER_AGENT']));}
  if ($mobil > 1) {
	 echo '<link href="../mobil.css" rel="stylesheet" type="text/css">'; 
	 $boxgr =  'style="height:25px; width:25px;"'; // boxgr in alle radio und checkboxen einfügen
	 } else {
	 echo '<link href="../aktiv.css" rel="stylesheet" type="text/css">'; $boxgr = "";
			}
  if (($mobil == 1) OR ($mobil == 3)) {
	  $linkerg = "?mobil=$mobil";
	  } else {
	  $linkerg = "";}
	  // Wenn Version manuell gesetzt, wird an alle entsprechenden Links dieser Zusatz angehängt

//Funktionen
//Funktionen: Umlaute kodieren
function umlaute($v) {//global $a1,$a2; //$v = utf8_decode($v);  
$a1 = array("Ä","Ö","Ü","ä","á","ö","ü","ß","§","é","è","[",'"',"®","©","€","µ","ñ","&#8230;","'","\n","€");
$a2 = array("&Auml;","&Ouml;","&Uuml;","&auml;","&auml;","&ouml;","&uuml;","&szlig;","&sect;","e","e","","&#x27;","&reg;","&copy;","&euro;","&micro;","&ntilde;",'...',"'","<br>","EUR");
//echo "Fehlersuche 37";
   if (laenge($v)) {
	   for($x=0;$x<15;$x++){$v = str_replace($a1[$x],$a2[$x],$v);}
	   //$v = mb_convert_encoding($v, 'UTF-8', 'ISO-8859-1');
	   } 
   return $v;}

function reumlaute($v) { // Umlaute und Sonderzeichen werden umkodiert; " wird zu '
		global $a1,$a2;
		for($x=0;$x<15;$x++){$v = str_replace($a2[$x],$a1[$x],$v);} 
		return $v;}
function nzubr ($text) { // mit \n formatierte Texte in html umwandeln
	return str_replace("\n",'<br>',$text);}

function dwandeln ($e,$uhrzeit) { // Wandelt Uhrzeit in DB-Form in deutsches Format
	$zeit = substr($e,8,2).'.'.substr($e,5,2).'.'.substr($e,0,4);
	if ($uhrzeit) $zeit .= ' - '.substr($e,13,5). ' Uhr';
	return $zeit;}
function dw($dw) { // macht aus yyyy.mm.dd -> dd.mm.yyyy 
		 if ((laenge($dw) < 10) OR (substr($dw,2,1) == ".")) {return $dw;} else { // hat schon das richtige Format
		 $q = substr($dw,8,2).'.'.substr($dw,5,2).'.'.substr($dw,0,4);
		 if (laenge(substr($dw,11))>4) $q .= ' '.substr($dw,11);
		 return $q;}}
function dv($dw) { // macht aus dd.mm.yyyy -> yyyy.mm.dd 
	$dw = ltrim($dw); 
	$q = substr($dw,6,4).'.'.substr($dw,3,2).'.'.substr($dw,0,2);
	if (laenge(substr($dw,11))>4) $q .= ' '.substr($dw,11);
	return $q;}
function ds($dw) { // macht aus dd.mm.yyyy -> yyyy-mm-dd 
  if (laenge($dw) < 6) {return $dw;}
  else {$dw = ltrim($dw); 
  $q = substr($dw,6,4).'-'.substr($dw,3,2).'-'.substr($dw,0,2);
  if (laenge(substr($dw,11))>4) $q .= ' '.substr($dw,11);
  return $q;}}
function dd($dw) { // macht aus Xyymmdd -> dd.mm.yyyy 
  $q = substr($dw,5,2).'.'.substr($dw,3,2).'.'.substr($dw,1,2);
  return $q;}
function dz($dw) { // macht aus einer beliebig formatierten Uhrzeit ein 0x:yy
  if (strpos($dw,".")) $dw = str_replace('.',':',$dw); // Punkt statt : eingegeben
  $x = strpos($dw,":"); if ($x) return date("H:i",mktime(substr($dw,0,$x),substr($dw,$x+1))); // schon richtig eingegeben
  if ($dw > 23) {
	  if (laenge($dw) == 3) {return date("H:i",mktime(substr($dw,0,1),substr($dw,1)));}
	  else {return date("H:i",mktime(substr($dw,0,2),substr($dw,2)));}
	  } // Eingabe ohne Punkt
  return date("H:i",mktime($dw,0));
 }
function dzut ($e) { // wandelt Datum in t
	$m = abs(substr($e,3,2)); $t = abs(substr($e,0,2)); $j = abs(substr($e,6,4)); return mktime(12,0,0,$m,$t,$j);}
function dintzud ($e) {// wandelt internationales Datum in deutsches
	$m = abs(substr($e,5,2))+100; $t = abs(substr($e,8,2))+100; $j = abs(substr($e,0,4)); return substr($t,1,2).'.'.substr($m,1,2).'.'.$j;}
function tzud ($t) {// wandelt t in deutsches Format
	return strftime("%d.%m.%Y",$t);}

function datumpruefe ($e) { //Datum auf Gültigkeit prüfen
 $bem ="";
 global $tstmp, $saison, $fehler,$timetag;
 if ($e == "") {$bem = "Datum erforderlich! "; $fehler = $fehler + 101;} else
 {$tstmp = dzut($e); $heute = time()+$timetag;
 if ($tstmp < $heute) {$bem = "Ungültiges Datum! "; $fehler = $fehler + 101; return $bem;}
 } //Saison
 return $bem;}

function imzeitraum($von,$bis) {//Prüft, ob heute in dem genannten Zeitraum (tt.mm.jjjj) liegt
	global $timetag; $x = dzut($von)-$timetag;
 return (time()<=dzut($bis)) AND (time()>=$x);
 }

function tw($text,$tttext) { // druckt den Text und zeigt tttext als tooltip
	// Dafür muss es im css eine entsprechende class geben
    echo '<a class="tooltip" href="#">'.$text.'<span>'.$tttext.'</span></a>'; } // Ende tw

function highlightWords($text, $word){ // Wort in Text markieren
		// Dafür muss es im css eine entsprechende class geben
		if (laenge($word) > 0) $text = preg_replace('#'. preg_quote($word) .'#i', '<span class="mbl">\\0</span>', $text);
		return $text;}
function datumformat ($e,$jahr) { // Eingegebenes Datum ergänzen/formatieren TT.MM.JJJJ
 // Wenn das Jahr fehlt, wird ein passender Wert eingesetzt
 // $jahr> 0, wird um eins erhöht, wenn die Eingabe vor dem aktuellen Datum liegt
 global $timetag;
 $e = str_replace(",",".",$e); $q = strpos($e,"."); if (!$q) return; // 1. Punkt
 $t = substr($e,0,$q); $r = strpos($e,".",$q+1); if (!$r) {$r=laenge($e); $e .=".";} // 2. Punkt
 $m = substr($e,$q+1); $m = substr($m,0,strpos($m,"."));
 $j = substr($e,$r+1); $s = laenge($j); 
 if ((!$s) OR (laenge($s) > 4)) {$j=date("Y");}
 else {if (laenge($j) == 2) $j = "20$j";} 
 $ti = intval($t); $mi = intval($m); $ji = intval($j);  
 //echo "$e q$q t$t r$r m$m j$j $ji $schaltjahr";
 if (laenge($t)<2) $t = "0$t"; //Tag
 if (laenge($m)<2) $m = "0$m"; //Monat
 $d = "$t.$m.$j"; $heute = time()-$timetag;
 if (($jahr) AND ($heute > dzut($d))) {$ji++; $d = "$t.$m.$ji";
 //echo '<span class="text3b rot">Jahr auf '.$ji.' gesetzt.<br></span>';
 }
 $schaltjahr = ($ji/4 == round($ji/4));
 $bem = '<span class="text1b rot">'.$d.' ist ein ung&uuml;ltiges Datum!<br></span>';
 if (($mi > 12) OR ($ti > 31)) {echo $bem; return;}
 if (($ti>30) AND (($mi == 4) OR ($mi == 6) OR ($mi == 9) OR ($mi == 11))) {echo $bem; return;}
 if ($schaltjahr AND ($ti>29) AND ($mi == 2)) {echo $bem; return;}
 if (!$schaltjahr AND ($ti>28) AND ($mi == 2)) {echo $bem; return;}
 return $d;}

function ipost($x){if (isset($_POST[$x])) {return str_replace('"',"'",$_POST[$x]);} else {return "";} 
  } // POST-Werte übernehmen
function iget($x){if (isset($_GET[$x])) {return str_replace('"',"'",$_GET[$x]);} else {return "";} 
 } // GET-Werte übernehmen
function ubeide ($x){
  if (isset($_POST[$x])) {$erg = str_replace('"',"'",$_POST[$x]);} else {$erg = "0";}
  if (isset($_GET[$x])) {$erg = str_replace('"',"'",$_GET[$x]);}
  return $erg;}  //POST und GET nacheinander abfragen
  function hackermeldung($schluessel) { // Fehlermeldung, wenn jemand in der Browserzeile den Schlüssel ändert
	  if (!$schluessel) {echo '<p class="text1b">Leider gibt es diesen Schl&uuml;ssel nicht!<br><br><br>'; include("disclaimer.php"); sleep(10); die("<br>Der Admin sagt dir: So hast du keine Chance, per brute force eine Kennung zu finden ...<br><br>");}
  }

function db_result($result, $iRow, $field = 0) { // löst unter mysqli den db_result-Befehl ab
    if(!mysqli_data_seek($result, $iRow)) return false;
    if(!($row = mysqli_fetch_array($result))) return false;
    if(!array_key_exists($field, $row)) return false;
    return $row[$field]; }

function dbspeichern ($eintrag) { //Datenbank-Query senden und Fehlermeldung ausgeben
 global $dbzeigen,$link;
 $aendern = mysqli_query($link,$eintrag); 
// if (!$aendern) {echo '<font color="red"><br>Achtung: Dein Text wurde nicht gespeichert! Wende Dich bitte an den <a href="mailto:admin@ms4ms.de">Admin(admin@ms4ms.de)</a> und gib die folgende Zeile an:<br></font>'.$aendern . $eintrag.'<br>';}
 if ($dbzeigen) echo '<br>'.$aendern . $eintrag.'<br>';}

function mobilumschaltung ($ja) { // Falls eine Mobilversion gesondert programmiert ist, wird hier die Umschaltung organisiert:
 // $mobil = 0: Desktop =1: manuell Desktop =2; automatisch Mobil =3: manuell auf Mobil gestellt
	global $mobil,$linkerg;
	$linkerg = ""; if (!$ja) {$mobil = 0;} 
	 if (isset($_GET['mobil'])) {$mobil = $_GET['mobil']; // wenn ein Wert für $mobil übergeben wird, hat der Vorrang
	 } else { 
	 $mobil = 2*((strstr(getenv("HTTP_USER_AGENT"),'Mobile')) OR (strstr(getenv("HTTP_USER_AGENT"),'Android')) OR (strstr(getenv("HTTP_USER_AGENT"),'iPhone')));}
	 if ($mobil > 1) {// Mobilversion anzeigen
	 echo '<link href="umfragemobil.css" rel="stylesheet" type="text/css">';} 
	 else {echo '<link href="umfrage.css" rel="stylesheet" type="text/css">';}
	 if (($mobil == 1) OR ($mobil == 3)) {//wenn manuell gesetzt, im GET-Link weitergeben
	 $linkerg = "&mobil=$mobil";} // Wenn Version mobil/Desktop manuell gesetzt, wird an alle entsprechenden Links dieser Zusatz angehängt
	/* Auf der jeweiligen Seite sowas wie folgt einbinden:
	echo '<tr><td width="380" align="right"><a class="text3n" style="color: #b9b8bf;" href="index.php?mobil=';
	if ($mobil < 2) {echo '3">Zur Version f&uuml;r mobile Browser';} 
	else {echo '1">Zur Version f&uuml;r Desktop';}
	echo ' wechseln.</a></td></tr>';
	*/
 }

function Mailbodytextumwandeln ($MailBody) {
 // Aus einem entsprechend gestalteten Text (Links mit -> Link <- maskiert, Zeilenvorschübe, <b> und <i>  die alternative Textversion erstellen
 $emailtext = str_replace("<br>","\n",$MailBody);
 $emailtext = str_replace("<a href=","",$emailtext);
 $emailtext = str_replace("</a>","",$emailtext);
 $emailtext = str_replace("<b>","",$emailtext);
 $emailtext = str_replace("</b>","",$emailtext);
 $emailtext = str_replace("<i>","",$emailtext);
 $emailtext = str_replace("</i>","",$emailtext);
 return $emailtext;}

function Mailbodyhtmlumwandeln ($MailBody) {
 // Header für html-Mail: Links einrichten; der html-header, Schrift etc. müssen vor dem Aufruf von mailen davor und dahinter montiert werden
 $emailhtml = str_replace("-> ",'<a href="',$MailBody);
 $emailhtml = str_replace(" <-",'">-> Link</a>',$emailhtml);
 $emailtext = str_replace("\n","<br>",$emailhtml);
 return $emailhtml;}

function VorNachspann ($MailBody) {
	return '<html><body><img src="http://ms4ms.de/ms4ms-text1.png" alt="Logo Ms4Ms" width="220" border="0"><font face="verdana" size="2"><p>'.$MailBody.'</p></body></html>';
 }

/* require '../PHPMailer/PHPMailerAutoload.php';
 $Host = 'smtps.mensa.de'; 
 $Username = '0495018';
 $Password = '0Toleranz!';
 $From1 = 'hermann.meier@mensa.de'; $From2 = 'Admin Vorstandstool';
 $ReplyTo1 = 'hermann.meier@mensa.de'; $ReplyTo2 = 'Admin Vorstandstool';

 Das muss wegen Absender und so auf den jeweiligen Seiten geregelt werden!!!
 */

function mailen($MailTo, $MailSubject, $MailBody,$MailBodyNurText, $Bcc,$Anhang1,$Anhang2,$Meldung) {
 global $Host, $Username, $Password, $From1, $From2, $ReplyTo1,$ReplyTo2;

 $mail = new PHPMailer;
 // Achtung: Funktioniert nur, wenn das Autoload ausgeführt wurde!!!
 //$mail->SMTPDebug = 3;                               // Enable verbose debug output

 $mail->isSMTP();                    // Set mailer to use SMTP
 $mail->Host = $Host;                // Specify main and backup SMTP servers
 $mail->SMTPAuth = true;             // Enable SMTP authentication
 $mail->Username = $Username;        // SMTP username
 $mail->Password = $Password;        // SMTP password
 $mail->SMTPSecure = 'tls';        // Enable TLS encryption, `ssl` also accepted
 $mail->Port = 465;                   // TCP port to connect to
 $mail->setLanguage('de', '../PHPMailer/language/');
 $mail->setFrom($From1, $From2);
 $mail->addAddress($MailTo);     
 //$mail->addAddress('ellen@example.com');  // Name is optional
 $mail->addReplyTo($MailTo,$ReplyTo2);
 //$mail->addCC('cc@example.com');
 if (laenge($Bcc)>0) $mail->addBCC($Bcc);

 if (laenge($Anhang1)>0) $mail->addAttachment($Anhang1); // Add attachments
 if (laenge($Anhang2)>0) $mail->addAttachment($Anhang2); // Add attachments
 $mail->isHTML(true);                               // Set email format to HTML

 $mail->Subject = $MailSubject;
 if (laenge($MailBody)>0) $mail->Body    = $MailBody;
 if (laenge($MailBodyNurText)>0) $mail->AltBody = $MailBodyNurText;

 if(!$mail->send()) {
	
    echo 'Die Nachricht konnte nicht versandt werden.';
    echo 'Fehlermeldung: ' . $mail->ErrorInfo;
 } else {if ($Meldung) echo 'Die Nachricht wurde verschickt.';
 }
 }

function multipartmail ($betreff,$text,$email,$kopie,$reply) { // $text ohne Umlaute-Codierung
	$text = str_replace("\n","<br>",$text);
	$id = strtoupper(md5(uniqid(time())));
	$header = "From: Hermann Meier <hermann.meier@mensa.de>\n";
	if (laenge($reply) > 5) {$header .= "Reply-To: ".$reply."\n";} else {$header .= "Reply-To: hermann.meier@mensa.de\n";} 
	$header .= "X-Mailer: PHP/" . phpversion(). "\n";          
	$header .= "X-Sender-IP: ".$_SERVER['REMOTE_ADDR']." \n"; 

	$header .= "MIME-Version: 1.0\nContent-Type: multipart/alternative; boundary=$id";
	$body = "--$id\nContent-Type: text/plain; charset=UTF-8\n\n".utf8_encode(strip_tags(str_replace("<br>","\n",$text)));
	$body .= "\n--$id\nContent-Type: text/html; charset=UTF-8\n\n <html>\n".umlaute($text)."\n</html>";
	if ($kopie) mail("dr.hm@gmx.de",$betreff,$body,$header);
	return mail($email,$betreff,$body,$header);}

function loggen ($aktivitaet,$name,$indb) {
 global $db,$logfile,$loggen,$dbzeigen,$link;
 if(getenv ("REMOTE_ADDR")) {$ipad = getenv("REMOTE_ADDR");} else {$ipad = "direkt";}
 if(getenv("HTTP_REFERER")) {$ref = substr(getenv("HTTP_REFERER"),7);} else {$ref = "direkt";}
 $zeit = date("Y.m.d - H:i:s ");
 $dns = @gethostbyaddr($ipad);
 $browser = getenv("HTTP_USER_AGENT");
 $inhalt = ""; if ((laenge($ipad) > 6) AND (substr($_SERVER['SERVER_NAME'],0,5) <> "treff")) {
 $url="http://api.ip-adress.com/?u=f2745906dfd2e6f4f6f66c991ce09ac655b0a&h=".$ipad;
 $inhalt = "";//file_get_contents($url); 
 $inhalt = substr($inhalt,strpos($inhalt,",")+1,999);
 $inhalt = substr($inhalt,strpos($inhalt,",")+1,999);
 $inhalt = str_replace (','," ; ",$inhalt);
 $inhalt = str_replace ('"',"",$inhalt);}
 $Bem = ""; if (strpos($inhalt,"eleos-web")) $Bem = 'FHU';
 if ((strpos($dns,"pools.arcor-ip")) AND (strpos($browser,"pera/9.80"))) $Bem = 'ich?';
 //if ($Bem == "ich?") $loggen = 0;
 if ($indb) {
 $s = "INSERT INTO $logfile SET zeit='$zeit', ip='$ipad', ref='$ref', aktivitaet='$aktivitaet', name='$name', log='$inhalt', dns='$dns', browser='$browser'";
 if ($loggen) {$ausgabe = mysqli_query($link,$s);} else {$ausgabe='-';}
 if ($dbzeigen) echo "<br>$ausgabe $s";
 } else {
 $zeile= "zeit='$zeit', ip='$ipad', ref='$ref', aktivitaet='$aktivitaet', name='$name', log='$inhalt', dns='$dns', browser='$browser'\r\n";
 $file = fopen($logfile.".txt", "a");
       fputs($file, $zeile);
	   fclose($file);}
 }

 function laenge ($v) { // ersetzt laenge, das bei NULL eine Warnung ausgibt
   if (!$v OR $v=="") {return 0;} else {return laenge($v);}
   }



?>