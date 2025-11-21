<?php // Einstiegsseite
 $dieseseite= "admin";
 $daten = 'textewahl'; // in dem File texteXXX.php stehen die für die jeweilige Umfrage erforderlichen Daten und Texte
 $naechsteseite = "admin"; // bei mehreren Seiten steht hier jeweils die als nächste aufzurufende
 error_reporting(E_ALL & ~E_WARNING);
 ini_set('display_errors', 1);
 include ("kopf.php"); // Der Kopf und die Formatierung
 include("einstiegsprozedur.php"); // Nur für die erste Seite
 include ("starten.php"); // Dateninitialisierung und Start

 if (!(($MNr == "04912113") OR ($MNr == "0495018XXXX"))) die("Kein Zugang.");

 $ks0 = 'SELECT Knr,vorname, name, mnummer, email,bildfile,mw,text,amt1,amt2,amt3,amt4,amt5,schl FROM kandidatenwahl';
 //echo $ks0;
 echo '<tr><td colspan="2" valign="top" align="left"><h1>Die Adminseite für die Extra-Seiten<br>Das sind die echten Kandidaten, nicht die Spielwiese!!!</h1></td></tr>';

 $steuer = ipost('weiter');
 $bearbeiten = ipost('bearbeiten');
 $delete = ipost('delete');
 $todo = iget('todo');

 //echo "steuer=$steuer bearbeiten=$bearbeiten delete=$delete<br>";
 
if ($todo == "124") {// Für das Diskussionstool die Tabelle mit den zu befragenden Kandidaten löschen und sogleich neu anlegen
	 $db = mysqli_query($link,"TRUNCATE TABLE Wahl".$jahr);
	 $db = mysqli_query($link,"DROP TABLE Wahl".$jahr);
	 echo '<br>DROP TABLE Wahl'.$jahr;
	 $todo = "123";}
if ($todo == "123") {// Prüfen, ob es für das Diskussionstool schon die Tabellen gibt, sonst anlegen
	 // Da das Diskussionstool auch für andere Zwecke verwendet werden kann, werden hier jeweils in der "Fragetabelle" Wahlxxxx (Jahreszahl) als "Frage" die Kandidaten angelegt; die Diskussionsbeiträge werden dann in den entsprechenden Tabellen WahlxxxxKommentare und die Teilnehmer in WahlxxxxTeilnehmer abgelegt

	 $result = mysqli_query($link,'SHOW TABLES LIKE "Wahl'.$jahr.'"');
	 if (mysqli_num_rows($result) > 0) {
	   echo "Die Tabelle Wahl$jahr gibt es schon - wenn du sie neu erstellen willst, dann musst du sie erst <a href=admin.php?todo=124>löschen - sie wird dann neu erstellt.</a>.";
	 } else {
	   echo "<br>Tabelle Wahl$jahr wurde erstellt.<br>";
	   $result = mysqli_query($link,'CREATE TABLE Wahl'.$jahr.' (Knr INT PRIMARY KEY,Leer INT,These varchar(256),Kommentar varchar(64),pos varchar(8),neg varchar(8),mnummer VARCHAR(8),email varchar(64),nachricht TINYINT,lfdnr TINYINT)');

	   $db = mysqli_query($link,$ks0); $q=1;
	   while ($kand = mysqli_fetch_array($db)) {
		   if ($kand['Knr']<99) {
		   $result = 'INSERT INTO Wahl'.$jahr.' SET Knr='.$kand['Knr'].', These="'.$kand['vorname'].' '.$kand['name'].'<br>kandidiert als ';
			 $n=1;
		   	 for ($i=1;$i<=$aemter;$i++) {
			 if ($kand["amt$i"]) { 
				 if ($n>1) $result .= ', ';
				 $result .= $rs[30*$i]; $n++;}
		   } 


		   $result .= '", mnummer="'.$kand['mnummer'].'", email="'.$kand['email'].'", lfdnr='.$q;
		   $q++;
		   $eintrag = mysqli_query($link,$result);
		   if ($dbzeigen) echo "<br>$eintrag $result";
	   }}
	 }  
	   $result = mysqli_query($link,'SHOW TABLES LIKE "Wahl'.$jahr.'Teilnehmer"');
	   if (mysqli_num_rows($result) > 0) {
		 echo "<br>Die Tabelle Wahl".$jahr."Teilnehmer gibt es schon.";
	    } else {
		 echo "<br>Tabelle Wahl".$jahr."Teilnehmer wurde erstellt.<br>";
		 $result = mysqli_query($link,'CREATE TABLE Wahl'.$jahr.'Teilnehmer (Mnr varchar(8),Vorname varchar(64),Name varchar(64),Nachricht tinyint,Email varchar(64),IP varchar(32),Erstzugriff datetime,Letzter datetime)');
		}
		// Etwas Doku dazu: Mit dem erstmaligen Aufruf wird der Datensatz erzeugt. Erst wenn jemand was schreibt, wird er nach seinen Daten gefragt.
	   $result = mysqli_query($link,'SHOW TABLES LIKE "Wahl'.$jahr.'Kommentare"');
	   if (mysqli_num_rows($result) > 0) {
		 echo "<br>Die Tabelle Wahl".$jahr."Kommentare gibt es schon.";
		} else {
		 echo "<br>Tabelle Wahl".$jahr."Kommentare wurde erstellt.<br>";
		 $result = mysqli_query($link,'CREATE TABLE Wahl'.$jahr.'Kommentare (Knr INT,These text,Kommentar text,Bezug int,IP varchar(32),Datum datetime,Medium varchar(8),Mnr varchar(8),Verbergen varchar(4),Hinweis text,pos text, neg text)');
		 $result = mysqli_query($link,'INSERT INTO  Wahl'.$jahr.'Kommentare  SET Knr=2000,These="Dummy-Text wegen Nummerierung"');
		}
		// Etwas Doku dazu: Die Kommentare werden fortlaufend gespeichert. Knr ist die ID des Kommentars, These der Inhalt. "Kommentar" gibt die ID des hierzu gehörenden Beitrags an und Bezug ist die ID des Postings, auf das sich der Kommentar bezieht
 } // Ende Erstellung Datenbanktabellen

 if ($steuer == 'Speichern') { // Datensatz anlegen bzw. speichern bzw. löschen
  if (laenge($delete)) {
	$eintrag = 'DELETE FROM kandidatenwahl WHERE  mnummer="'.ipost('mnummer').'"';}
	else { // Neuen Datensatz für diese MNummer anlegen
	$schl = schluessel(ipost('mnummer'),$adressfile);
	// Es wird nachgeschaut, ob diese MNummer schon einen Schlüssel hat, sonst ein neuer erzeugt
	$bildfile = strtolower(ipost('vorname')).ipost('name').'_150.jpg';
	$eintrag = 'kandidatenwahl SET Knr='.ipost('Knr').', vorname="'.ipost('vorname').'", name="'.ipost('name').'", mnummer="'.ipost('mnummer').'", email="'.ipost('email').'", amt1='.ubeide('amt1').', amt2='.ubeide('amt2').', amt3='.ubeide('amt3').', amt4='.ubeide('amt4').', amt5='.ubeide('amt5').', bildfile="'.$bildfile.'", schl='.$schl;
	if ($bearbeiten =='neu') {
		$eintrag = 'INSERT INTO '.$eintrag;}
		else {$eintrag = 'UPDATE '.$eintrag.' WHERE mnummer="'.ipost('mnummer').'"';}
	}
  $ausgabe = mysqli_query($link,$eintrag);
  echo "$ausgabe $eintrag";

  $bearbeiten = "";
  }

 if (laenge($bearbeiten)) { // Es wurde ein Eingabemodus angewählt.
  //	echo $bearbeiten;
  if ($bearbeiten == 'neu') {
	  $kand['Knr']=99;$kand['vorname']=""; $kand['name']=""; $kand['mnummer']=""; $kand['email']=""; $kand['mw']=""; $kand['vorname']=""; $kand['amt1']="1"; $kand['amt2']=""; $kand['amt3']=""; $kand['amt4']="";$kand['amt5']="";} 
	  else { // bestehenden Datensatz bearbeiten
	  $kx = mysqli_query($link,$ks0.' WHERE mnummer="'.$bearbeiten.'"');
	  $kand = mysqli_fetch_array($kx);
	  }


// Eingabeteil
 $bildfile="";
 echo '<tr><td colspan="2"><h3>Kandidat eingeben/editieren:</h3></td></tr>';
 echo '<tr><td class="text1b">Lfd. Nr. entsprechend Wahlausschuss: ';textfeld('Knr',$kand['Knr'],10,1,1,""); 
 echo '</tr>'; 
 echo '<tr><td class="text1b">M-Nummer</td>'; 
 textfeld('mnummer',$kand['mnummer'],10,1,1,'049.....'); 
 echo '</tr>'; 
 echo '<tr><td class="text1b">Name</td>'; 
 textfeld('name',$kand['name'],48,1,1,''); echo '</tr>'; 
 echo '<tr><td class="text1b">Vorname</td>'; 
 textfeld('vorname',$kand['vorname'],48,1,1,''); echo '</tr>'; 
 //echo '<tr><td class="text1b">m&auml;nnlich/weiblich</td>'; 
 //textfeld('mw',$kand['mw'],4,1,1,'m oder w'); echo '</tr>'; 
 echo '<tr><td class="text1b">eMail-Adresse</td>'; 
 textfeld('email',$kand['email'],48,1,1,''); echo '</tr>'; 
 echo '<tr><td class="text1b">Bilddatei</td>'; 
 echo '<td class="text1b"> wird von der Wahlausschuss-Seite &uuml;bernommen</td>';
 //echo '<td><input type="file" size="16" class="text" name="bildfile" value="'.$bildfile.'"></td>';
 //echo '</tr><tr><td class="text1b">Kandidatentext</td>'; 
 //textfeld('text',$kand['text'],66,18,1,'Reine Texteingabe! Auf keinen Fall per copy&paste aus Word &uuml;bernehmen!'); echo '</tr>'; 
 echo '<tr><td class="text1b">kandidiert für</td><td class="text1n">'; 

 for ($i=1;$i<=$aemter;$i++) {
    echo '<input type="checkbox" name="amt'.$i.'"';
	if ($kand["amt$i"]) echo ' checked="checked"';
	echo ' value="1"> ';
    echo $rs[30*$i];
 } 
 echo '</td></tr>';

 echo '<tr><td> </td><td class="text1b"><input type="radio" name="delete" value="delete"> Diesen Datensatz l&ouml;schen</td></tr>';

 echo '<input type="hidden" name="bearbeiten" value="'.$bearbeiten.'">';
 $weiter = "Speichern";



 } else {

// Steuerteil
  echo '<tr><td><h3>Bereits eingegeben:</h3></tr>';
  //for ($i=1;$i<=$aemter;$i++) {
	  $ks = $ks0.' WHERE amt1=1 OR amt2=1 OR amt3=1 OR amt4=1 OR amt5=1 ORDER BY Knr';

//	$ks = $ks0.' WHERE amt'.$i.'=1 OR amt'.$i.'=1 OR amt'.$i.'=1 OR amt'.$i.'=1 OR amt'.$i.'=1 OR ORDER BY Knr';
	$kx = mysqli_query($link,$ks); $kn = mysqli_num_rows($kx);
	if ($kn) {
		echo '<tr><td class="text1b">Kandidaten:<td></tr>';
		for ($j=0;$j<$kn;$j++) {
			$kand = mysqli_fetch_array($kx);
			if ($kand['mnummer']) {
			 echo '<tr><td class="text1b" width="500">Lfd.Nr. '.$kand['Knr'].': '.$kand['mnummer'].' - '.$kand['vorname'].' '.$kand['name'];
			 echo '</td><td class="text1n"><input type="radio" name="bearbeiten" value="'.$kand['mnummer'].'"> bearbeiten <br>';
			 echo '</td></tr>';}
			}
		}
	
	echo '<tr><td class="text1n"><br><br> <input type="radio" name="bearbeiten" value="neu"> Nächsten Kandidaten eingeben<br>';
 //echo '<input type="radio" name="bearbeiten" value="mail"> Eingabe fertig - Mail an alle Kandidaten senden';





 
 $weiter = "Absenden ...";
 }

 echo '<tr><td  class="text1n" colspan="2"><br>Information: <br>Was nach Eingabe aller Kandidaten geschehen muss:<br><ul>';
 echo '<li>Falls noch nicht geschehen: Manuell das Editier-Ende für die Kandidaten in textewahl.php eintragen</li><li>Die Kandidaten anmailen (Text siehe Vorjahr) und sie zum Eintragen einladen. Das Programm sorgt dafür, dass die Kandidaten mit ihrer M-Nummer auf die eigenen Inhalte zugreifen können, nicht aber auf die der anderen.</li><li>Kurz vor Schluss nochmal die erinnern, die das verschlampt haben.</li>';
 echo '<li>Das Erzeugen der Diskussions-Datenbanktabellen ausl&ouml;sen - <a href=admin.php?todo=123> hier klicken</a></li>';
 echo '</ul></td></tr>';

 echo '</table></td></tr>';
 echo '<tr class="rahmen">';
 echo '<td valign="bottom" align="right" style="padding-top: 25px; padding-right: 10px;">';

 echo '<input class="button red" type="submit" name = "weiter" value="'.$weiter.'">';

 echo '<br><br></form></td></tr><tr><td>';
 include ("disclaimer.php");
 echo '</table>';
 //echo '</table>';
?>        
</body>
</html>

