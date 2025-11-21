<?php //Individuelle, fest verdrahtete Texte fÃ¼r die jeweilige Umfrage in einem Wahljahr

 $einstiegsseite = "index.php";// Damit startet das Skript und lädt dann kopf.php und alles Weitere

// ***** Hier stehen die jedes Jahr neu festzulegenden Daten *****
 $jahr = "2025";

 // Zeitsteuerung (jeweils bis ca. 12 Uhr mittags!):
 $kandidatenzeigen_ab = '08.02.2025'; // Ab diesem Datum kann die Übersichtsseite von allen Ms abgerufen werden; bis dahin läuft die Spielwiese, aus der M sehen kann, was die Seite erwarten lässt
 
 $editieren_bis = '17.02.2025'; // Bis zu diesem Datum können die Kandidaten ihre Angaben machen, danach nicht mehr - dann sind die Angaben für alle Ms sichtbar; zugleich sind ab diesem Datum die Angaben der Kandidaten für alle Ms sichtbar

 // Diskussionstool:
 $Enddatum = '04.04.2025'; // Ab dann ist das Diskussionstool für Beiträge von Ms gesperrt, aber nicht für Antworten der Kandidaten

 if (time() > dzut($kandidatenzeigen_ab)) {$spielwiese = 0;} else {$spielwiese = 1;} // Bis zum Bewerbungsende für Kandidaten wird die Spielwiese angezeigt

// echo 'meine Texte';


 if (time() > dzut($editieren_bis)) {$allesoffen = 1;} else {$allesoffen = 0;}
 
 //Damit die Uhrzeit exakt eingehalten wird, manuell auf Allesoffen setzen um 12:00 Uhr am Tage des Edidieren_bis, am nächsten Tag wieder auskommentieren:
	// $allesoffen = 1;
 
 $speichern = 1;

// ***** Etwaige Admin-Eingriff *****
$dbzeigen = 0;
 $admin = 0; $testserver = 0;
 if ($admin) {$dbzeigen = 1; // Programmentwicklung: =1 zeigt die DB-Anweisungen
 echo 'Adminfunktionen geschaltet!<br>'; $dbzeigen = 1;}

 $Sperre = 0; // Manuell sperren = 1
 // Zum Testen kann man hier eine beliebige Mitgliedsnummer eintragen, indem man $testserver auf 1 setzt.
 if ($testserver OR !isset($_SERVER['REMOTE_USER'])) {
	 $MNr = "0490000000"; 
	 setcookie('testserver',$MNr,time()+3600);
	 } else {
	$MNr = $_SERVER['REMOTE_USER'];}
	if ($MNr=="04912113") {//Testumgebung Authorisierung 
//$dbzeigen = 1;
//die("38 $MNr");

	}




 if ($MNr=="04900000000") $editieren_bis = '01.04.2025'; // Ausnahmsweise für einen Nachzügler öffnen => MNummer einsetzen und Datum festlegen 

 // ***** Alles Weitere nur bei Bedarf ändern *****

// Datenbanktabellen, Dateipfade
 $kandidatendb = "kandidatenwahl";   // die echten Kandidatenseiten...
 if ($spielwiese) $kandidatendb = "spielwiesewahl";
 $bildquelle =""; // Im  Verzeichnis /IMG werden die Bilder der Kandidaten ablegt

 $adressfile = "adressenwahl"; // Adressdaten der Angeschriebenen
 $antwortenfile = "antwortenwahl"; // DB-Tabelle der Antworten



// Die Vorstandsressorts 
 //werden hier eingestellt und müssen ggf. späteren Entwicklungen angepasst werden
 $aemter=5; // Anzahl zu in diesem Jahr zu wählenden Ämter; welche das sind, ergibt sich dann automatisch aus der Eintragung in die Kandidatenbank - unter amt1...5
 //amt1
 $rnr[30]= 40; $rs[30] = "Vorstand Vorsitz/Strategie"; // Anzahl Ressorts, Vorstandsbereich
  $rnr[31]= 1; $rs[31] = "Vorsitz"; // rnr gibt die alte Ressortnummer aus der Vorversion an
  $rnr[32]= 2; $rs[32] = "Internationales";
  $rnr[33]= 13; $rs[33] = "Presse und PR";
  $rnr[34]= 14; $rs[34] = "Kooperationen";
  $rnr[35]= 27; $rs[35] = "Marketing";
  $rnr[36]= 18; $rs[36] = "Strategie";
  $rnr[37]= 7; $rs[37] = "Stiftung: Testbetrieb";
  $rnr[38]= 24; $rs[38] = "Stiftung: Kids & Juniors-Camps";
  $rnr[39]= 12; $rs[39] = "Stiftung: Bildung";
  $rnr[40]= 25; $rs[40] = "Stiftung: Wissenschaft und Forschung";

 //amt2
 $rnr[60]= 73; $rs[60] = "Vorstand Vereinsleben"; // Anzahl Ressorts, Vorstandsbereich
  $rnr[61]= 6; $rs[61] = "Regionale Struktur";
  $rnr[62]= 11; $rs[62] = "Mensa Youth (Junge Erwachsene)";
  $rnr[63]= 8; $rs[63] = "Kids & Juniors regional";
  $rnr[64]= 26; $rs[64] = "Ortsbl&auml;tter";
  $rnr[65]= 5; $rs[65] = "Mitgliederbetreuung";
  $rnr[66]= 19; $rs[66] = "SIGs";
  $rnr[67]= 9; $rs[67] = "Gro&szlig;veranstaltungen";
  $rnr[68]= 15; $rs[68] = "Vereinsmedien";
  $rnr[69]= 20; $rs[69] = "Compliance";
  // Zuordnung
  $rnr[70]= 7; $rs[70] = "Stiftung: Testbetrieb";
  $rnr[71]= 24; $rs[71] = "Stiftung: Kids & Juniors-Camps";
  $rnr[72]= 12; $rs[72] = "Stiftung: Bildung";
  $rnr[73]= 25; $rs[73] = "Stiftung: Wissenschaft und Forschung";

 //amt3
 $rnr[90]= 101; $rs[90] = "Vorstand Administration"; // Anzahl Ressorts, Vorstandsbereich
  $rnr[91]= 4; $rs[91] = "Organisation";
  $rnr[92]= 21; $rs[92] = "Personal/Dienstleister";
  $rnr[93]= 3; $rs[93] = "Finanzen";
  $rnr[94]= 17; $rs[94] = "IT";
  $rnr[95]= 22; $rs[95] = "Vorschlagswesen und Beschwerdemanagement";
  $rnr[96]= 16; $rs[96] = "Recht";
  $rnr[97]= 23; $rs[97] = "Datenschutz";
  // Keine feste Zuordnung
  $rnr[98]= 7; $rs[98] = "Stiftung: Testbetrieb";
  $rnr[99]= 24; $rs[99] = "Stiftung: Kids & Juniors-Camps";
  $rnr[100]= 12; $rs[100] = "Stiftung: Bildung";
  $rnr[101]= 25; $rs[101] = "Stiftung: Wissenschaft und Forschung";
 
 $rs[120] = "Finanzpr&uuml;fer"; 
 $rs[150] = "Schlichter"; 

// Texte
 $slogan= "Diskussionstool zur Wahl ".$jahr; // Das steht in der grünen Leiste oben
 $title = "Diskussionstool zur  MinD-Wahl ".$jahr;

 $skala5 = array("","<b>&mdash; &mdash;</b>","&mdash;","&plusmn;","+","<b>+ +</b>"); // Skala 1 bis 5
 //$skala5 = array("","&mdash; &mdash;","&mdash;","&plusmn;","**","***"); // Skala 1 bis 5 Wichtigkeit
 $skala5p = array("","<b>&dArr;&dArr;&dArr;</b>","&dArr;","&hArr;","&uArr;","<b>&uArr;&uArr;&uArr;</b>"); // Skala pro/contra
 $skala5ptext = array("nix","klar dagegen","dagegen","indifferent","daf&uuml;r","unbedingt daf&uuml;r");
 $skala5z = array(0,1,2,3,4,5); // Zahlen zeigen
 $skala5w = array("","- -","-","&hArr;","<b>!</b>","<b>! ! !</b>"); // Skala Wichtigkeit 
 $skala5wtext = array("nix","&uuml;berhaupt nicht","eher nicht","vielleicht","sehr","extrem"); // .. wichtig  Skala Wichtigkeit 
 $skala5r = array("","nein","wenn es sein muss","w&auml;re ok","ja, gern","ja, sehr gern");
 $skala5a = array("","gar nicht meins","eher nein","ja, kriege ich wohl hin","ja, darin bin ich gut","ja, genau das ist eine meiner St&auml;rken");
 $skala5f = array("55","55","55","56","51","51");


// Inhalte

 $ueberschrift = "Was die Kandidaten meinen, ...<br>... und wie das zu meinen Ansichten passt."; 
 $menupunkte = array("Alle Kandidaten","Ressort&uuml;bersicht","Fragen & Antworten");
 $menulink = array("index","ressorts","antworten","vergleich");

 $unter = 'Viele Gr&uuml;&szlig;e<br><br>Euer Admin Werner Kelnhofer'; // Unterschrift unter Texte
 $endtext1 = '';


// Diskussionstool
 $Texte = 'Wahl'.$jahr; // die Thesen/Fragen, über die diskutiert wird
 $Teilnehmer = 'Wahl'.$jahr.'Teilnehmer'; // die registrierten Teilnehmer
 $Kommentar = 'Wahl'.$jahr.'Kommentare'; // die Kommentare die Kommentare
 $Slogan = 'Mit den Kandidaten ins Gespr&auml;ch kommen'; // Titel und Überschrift im Kopf der Seite
 $Inhalt = 'Frage'; $Inhalte = 'Kandidaten'; // Worum es geht
 $Einstieg = 'wahl'.$jahr.'.php'; // Die Einstiegsseite

 if (time() < dzut($Enddatum)) {$Anfangstext = 'Hier kann M den Kandidaten konkrete Fragen stellen und die Kandidaten können dazu Stellung nehmen, dies natürlich auf freiwilliger Basis. <br>(*) ausgenommen sind jene Kandidaten, die sich gegen eine Teilnahme an der Diskussion ausgesprochen haben.<br><br>Es wird allerdings darauf geachtet, dass die Regeln der Höflichkeit eingehalten werden. <br>Beitr&auml;ge mit beleidigendem oder rechtswidrigen Inhalten werden gel&ouml;scht, sobald wir sie bemerken.<br><br>Bitte habe Verständnis, wenn etwas dauert, bis die Kandidaten antworten: Viele sind beruflich stark engagiert und/oder arbeiten ehrenamtlich - nicht zuletzt für Mensa.<br><font color="crimson">Das Tool ist bis zum<b> '.$Enddatum.' </b>Mittag für Fragen offen, so dass alle Kandidaten für Ihre letzten Antworten bis zum Abschluss der Wahl ausreichend Zeit haben.</font><br>';}
 else 
 {$Anfangstext = 'Hier konnte M bis kurz vor Abschluss der Wahl den Kandidaten konkrete Fragen stellen, und die Kandidaten haben dazu Stellung genommen. <br>';}

 $Links = '-> <a style="font-size: 12px;" href="https://aktive.mensa.de/docs/MinD_Satzung.pdf" target="MinD">MinD: Satzung (PDF)</a> 
 <br>-> <a style="font-size: 12px;" href="https://mindwahl.de/wahl-info/" target="MinD">Wahl-Info + Bewerbungstexte der Kandidaten </a>
 <br>-> <a style="font-size: 12px;" href="https://wahl.mensa.de/extra/anforderungen.php" target="MinD">Tabelle der Eigenbeschreibung der Kandidaten </a> 
 <br>-> <a style="font-size: 12px;" href="https://wahl.mensa.de/extra/ressorts.php" target="MinD">Ressort-Präferenzen der Vorstandskandidaten</a> <br>
<br>-> <a style="font-size: 12px;" href="https://confluence.mensa.de/pages/viewpage.action?pageId=76972118" target="MinD">Confluence-Diskussion der MV-Anträge</a> <br><br>';




 $Vorspann = '';

?>