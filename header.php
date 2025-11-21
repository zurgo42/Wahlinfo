<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<meta name="Author" content="Dr. Hermann Meier, Mensa in Deutschland e.V.">
<meta name="robots" content="noindex,nofollow">
<meta name="description" content="Interne Seite">
<link rel="shortcut icon" href="./favicon.ico" type="image/x-icon">
<link href="disk.css" rel="stylesheet" type="text/css">

<!-- Diskussionstool 
Zweck des Tools ist die Diskussion von einzelnen Thesen - die Teilnehmer können sie kommentieren und auch Beiträge von anderen beantworten oder mit Kommentaren versehen -->

<?php
// Mit diesem Programmteil wird bei externem Aufruf das Diskussionstool initialisiert. 
// Bei der Wahldiskussion wird direkt auf diskussion.php verlinkt

include ("config.php");
$link = mysqli_connect(MYSQL_HOST,MYSQL_USER, MYSQL_PASS) OR die("Keine Verbindung zur Datenbank. Fehlermeldung:".mysqli_error());
    mysqli_select_db($link,'wahl') OR die("Konnte Datenbank nicht benutzen, Fehlermeldung: ".mysqli_error());
$datum = date("Y-m-d H:i:s",time()+3600);
$ddatum = date("d.m.Y H:i",time()+3600);
$ipad = getenv("REMOTE_ADDR");
$ipad = substr($ipad, 0,14);

function posneg ($Knr,$p,$n){ // Zustimmung/Ablehnung äußern - dieses Feature ist noch nicht aktiv!
 global $farbe,$Mnr,$Anz,$Anzkomm;
 echo ' <a class="tooltip" href="diskussion.php?Knr='.$Knr.'&s=11#'.$Knr.'">';
 echo '<img src="smpos.ico" border="';
 if ($p) {echo "1";} else {echo "0";}
 echo '" ><span style="left: 0px; width: 150px;"><i>Dem stimme ich zu!</i></span></a>';
 echo ' <a class="tooltip" href="diskussion.php?Knr='.$Knr.'&s=12">';
 echo '<img src="smneg.ico"><span style="left: 0px; width: 150px;"><i>Das sehe ich anders!</i></span></a>';
 }

function kaus ($Knr,$Off,$M,$F) { // Hier werden die einzelnen Beiträge zeilenweise angezeigt.
 // $Knr ist die Kommentarnummer $Off ist der Offset dieses Beitrags 
 // $M ist die MNr des Kommentierenden, $F ist die Farbe des Beitrags
 global $hinweis,$farbe,$Mnr,$Anz,$Anzkomm,$Teilnehmer,$Kommentar,$Sperre,$link,$admin;
 $komm = mysqli_query($link,'SELECT * FROM '.$Kommentar.' WHERE Bezug = "'.$Knr.'" ORDER BY Knr');
 while($row = mysqli_fetch_array($komm)) {
  if ($row['Bezug'] < "2000") $F = MIN(35,$F+1); // Das ist ein Kommentar direkt zum Hauptbeitrag
  if ($row['Verbergen'] < 9) { // >8 = Beiträge, die nicht erscheinen sollen; =7 mit indiv. Hinweis
   $teiln = mysqli_query($link,'SELECT * FROM '.$Teilnehmer.' WHERE Mnr = "'.$row['Mnr'].'"');
   echo '<a name="'.$Knr.'"></a>';
   if (!laenge($Off)) echo '<br>';
   echo '<font color="#'.$farbe[$F].'"><font size="-3">';
   $x= $Anzkomm+1990;
   if ($row['Knr'] > max(2001,$x)) echo '<font color="red"><b>neu: </b></font>'; // testweise eingeführt - beibehalten???
   //if ($row['Knr'] > max(2021,$x)) {$zeile = substr($row['Knr'],0,1); $zeile .= '<font color="red">'.substr($row['Knr'],1).'</font>';}
   //else {$zeile = $row['Knr'].'</font>';}
   $zeile = $row['Knr'].'</font>';

   if (($row['Mnr'] == '0490') OR ($row['Mnr'] == '0491') OR ($row['Mnr'] == '0492')) {$zeile="";}
   $zeile .= ' '.$Off." <b> ".db_result($teiln,0,'Vorname')." ".db_result($teiln,0,'Name')."</b> ";
   $M0 = $M = db_result($teiln,0,'Mnr');
   if (substr($M,0,3) == "049") $M = substr($M,3);
   if ($M > "0491") $zeile .= $M;
   if ($Anz) $zeile .= ' am '.substr($row['Datum'],8,2).'.'.substr($row['Datum'],5,2).'.';
          // $row['Hinweis'] = 'X_X'; //Diese Zeile habe ich temporär eingefügt um die Anzeige der Fehlermeldung zu unterdrücken - - Werner Kelnhofer am 24.07.2024
   if (laenge($row['Hinweis']) AND $Anz) $zeile .= ' ('.$row['Hinweis'].')';
   $zeile .=": ";


   $Len=laenge($zeile); echo $zeile.'</font>'; // echo "$Mnr $M0";
   if (!$Sperre AND ($Mnr == $M0)) echo '<a href="diskussion.php?Knr='.$row['Knr'].'&s=2&Anz='.$Anz.'"><img src="edit.gif" title="bearbeiten / l&ouml;schen"</a>';
   if ($row['Verbergen']) {
	   echo '<a class="tooltip" href="diskussion.php?'.$row['Knr'].'"><span><i>';
	   if ($row['Verbergen'] < 7) {echo '</span><font color="red">Admin: '.$hinweis[$row['Verbergen']].' Der Beitrag: </font><br>'.$row['These'].'</a>';} 
	   if ($row['Verbergen'] == 7) {echo '</span><font color="red">Admin: '.$row['Hinweis'].'<br>Beitrag: </font>'.$row['These'].'</a>';} 
	   if ($row['Verbergen'] > 7) {echo '</span><font color="red">Admin: '.$row['Hinweis'];
	   echo '<br></span>(Beitrag wird nicht mehr angezeigt)</a>';}
   } else {
   if ($Anz > 0) {
   if (!$Sperre) {
	   echo '<a href="diskussion.php?Knr='.$row['Knr'].'&Mnr='.$Mnr.'&s=1&Anz='.$Anz.'">';  }
	   echo nl2br($row['These']).'</a><br>';
	   //echo '<a href="#">'.nl2br($row['These']).'</a><br>';
	   } else {
	   //if (!$Sperre) 
	   echo '<a class="tooltip" href="diskussion.php?Knr='.$row['Knr'].'&Mnr='.$Mnr.'&s=1&Anz='.$Anz.'">';
	   //echo '<a class="tooltip" href="#">';
	   $Len2 = $Len+laenge($row['These']); $Maxlen = 95;
	   if (substr($row['These'],95-$Len,1) == ">") $Maxlen++;
	   if ($Len2 > $Maxlen) {
		   echo substr($row['These'],0,$Maxlen-$Len).' ...';} else {echo nl2br($row['These']);}
	   echo '<span><i>Zum Text Nr. '.$row['Bezug'].' schreibt '.db_result($teiln,0,'Vorname');
	   echo ' am '.substr($row['Datum'],8,2).'.'.substr($row['Datum'],5,2).'.: ';
	   echo '<br>(KNr. '.$row['Knr'];
	   if (laenge($row['Hinweis'])) echo ' - '.$row['Hinweis'].' ';
	   echo '): </i><br>'.nl2br($row['These']);
	   if (!$Sperre) {
		   echo '<br><br><br><i>Zum Antworten bzw. Kommentieren klicken.</i><br>';
		   } else {
		   echo '<br><br><i>Zeit abgelaufen: Keine Eingabe mehr m&ouml;glich.</i><br>';}
		   echo '</span></a>';
		   //}
	   }
	   //	posneg($row['Knr'],strpos(db_result($teiln,0,'pos'),$Mnr),strpos(db_result($teiln,0,'pos'),$Mnr));
   }
   echo "<br>";
   if ($row['Kommentar']) {kaus($row['Knr'],$Off."-",$M,$F);}
  }}
}

function ldapident_alt($Mnr) {// Ein M per MinD-LDAP-Server über die MNr identifizieren
   global $vorname,$name,$email;
   $ldapserver = 'localhost';
   $ldapuser      = '0495018'; 
   $ldappass     = 'ieyii3ainiiDeev,';
   $ldaptree    = "cn=aktive,ou=applications,dc=mensa,dc=de";

   $ldapconn = ldap_connect($ldapserver) or die("Keine Verbindung zum LDAP server.");
   ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3);

   if(!$ldapconn) die ("Verbindung funzt nicht: ".ldap_error($ldapconn));
	   $ldapbind = ldap_bind($ldapconn, $ldaptree, $ldappass) or die ("bind funzt nicht: ".ldap_error($ldapconn));
	   //if ($ldapbind) {echo "LDAP bind erfolgreich...<br /><br />";

   $dn = "ou=members,dc=mensa,dc=de";// Das sind alle in IQPlus Registrierten
   // Gesucht wird im Ordner 'members' der Domain mensa.de
   $filter = "(uid=$Mnr)";
   $justthese = array( "ou", "sn", "uid", "sn", "displayName", "cn", "givenName", "mail","objectClass","x-de-mensa-url");
   $sr=ldap_search($ldapconn, $dn, $filter, $justthese);
   if (ldap_count_entries($ldapconn, $sr)){
   $mdaten = ldap_get_entries($ldapconn, $sr);
	$vorname = $mdaten[0]['givenname'][0];
	$name = $mdaten[0]['sn'][0];
	$email = $mdaten[0]['mail'][0];}
	else {$vorname = "nicht gefunden";$name=$email="";}
   $stop = ldap_close($ldapconn);
   } 

//Die einzelnen Kommentarstränge haben jeweils eine einheitliche Farbe, damit man die Diskussion zu einem Beitrag besser verfolgen kann:
   $farbe = array("300000","0000ff","008080","ff00ff","800000","808000", "ff6500","800080","0A0868","008000","cb0000","0000ff", "008080","ff00ff","800000","808000","ff6500","800080", "0A0868","008080","cb0000","0000ff","008080","ff00ff", "800000","808000","ff6500","800080","0A0868","008080", "cb0000","0000ff","008080","ff00ff","800000","808000","ff6500","800080","0A0868","008080","cb0000");

// ADMIN-Funktion fehlt - etwaiger Missbrauch muss in der Datenbank unter "Verbergen" eingetragen werden!

   // Hinweise
	//Verbergen = 7: Hinweis unter Hinweis speichern; Hinweis wird gezeigt und Beitrag wird gezeigt
	//Verbergen = 8: Hinweis zeigen, Beitrag nicht

	$hinweis = array("","","",//0-2
	"Wir wollen bitte alle darauf achten, dass pers&ouml;nliche Angriffe unterbleiben!",//3
	"",
	"Dieser Beitrag wurde auf Wunsch des angegriffenen Mitglieds gel&ouml;scht",//5
	"Dieser Beitrag wurde als problematisch gemeldet; er befindet sich in der Kl&auml;rung",//6
	"",//7
	"Dieser Beitrag enth&auml;lt ggf. als rechtswidrig einzustufende Inhalte und k&ouml;nnte daher den Betreiber in Schwierigkeiten bringen. Er wird daher nicht angezeigt, bis das Problem gel&ouml;st ist."
	//8
	);
	// =7 individueller Hinweis; =8 ähnlich 3: Auf Wunsch des Ms überhaupt nicht anzeigen; =9 Wirre Zeichenfolge oder leer, daher nicht anzeigen;


// Kopf ausgeben und Tabelle vorbereiten
echo '<title>'.$Slogan.'</title></head>';
/*
echo '<body bgcolor="#ffffff"><center><table width="100%" align="center" valign="top" class="header"><tr class="header"><td class="header">
<div id="header"><div id="headerBG';
echo '" style="background: #336600;';
echo '"><div id="headerR1"><div id="headerR2"><div id="headerR3">';
echo '<div id="headerTITEL"><div id="headerSLOGAN">'.umlaute($slogan).'</div></div></div></div></div></div></div>';
*/

echo '<body id="main"><center><div id="header"><div id="headerBGNeu">';
//echo '<div id="headerRLogo">'; 
echo '<div id="headerTITEL">';
//if ($mobil<2) {
	echo '<a href="diskussionstool.php" style="text-decoration: none;"><div id="headerSLOGAN">'.$slogan.'</div></a>';
	//}
//  else {echo '<div id="headerSLOGAN">Vorstandstool</div>';}
//else {echo '<div id="headerSLOGAN"></div>';}
echo '</div></div></div></div></div>';


echo '</td></tr>';
echo '<tr class="header"><td align="center" class="header">';
echo '<table width="99.3%"><tr><td valign="top" align="left" colspan="5"><span class="text2"><br>';
?>
