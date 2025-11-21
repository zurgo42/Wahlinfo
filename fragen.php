<?php // Erste Seite Vorstandsfrageboten
$dieseseite = "fragen";
$daten = 'textewahl';
$naechsteseite = "fragen";
$privat=2;
include ("kopf.php"); 
$MNr = $_SERVER['REMOTE_USER'];
$bildquelle ="../neuwahl/"; // Das Verzeichnis, in dem der Wahlausschuss die Bilder abgelegt hat

if (time()<dzut($editieren_bis)) {$kandidatendb = "spielwiesewahl";}
$kandidatendb = "spielwiesewahl"; $spielwiese=1; // Nur für Admin vor dem Termin


$key = schluessel($MNr,"antwortenwahl");
$zeigemir = ubeide('zeigemir'); 
if ($kandidatendb == 'kandidatenwahl') {
$ks = 'SELECT * FROM '.$kandidatendb;;
if ($zeigemir==0) $ks .= ' WHERE (amt1=1 OR amt2=1 OR amt3=1)';
if ($zeigemir==2) $ks .= ' WHERE (amt4=1 OR amt5=1)';
$ks .= ' ORDER BY ';
if ($zeigemir==2) $ks .= 'amt4,amt5,';
$ks .= 'name, vorname';} //die zu zeigenden Kandidaten
else {$ks = 'SELECT * FROM '.$kandidatendb.' ORDER BY name, vorname';} //alle zu zeigenden Kandidaten}
$ks = mysqli_query($link,$ks); //alle Kandidaten
$kanz = mysqli_num_rows($ks); // Anzahl Kandidaten
$aendern = ubeide('aendern');
// Im Gegensatz zu den anderen Seiten kann hier von allen Teilnehmern jederzeit ausgefüllt werden, solange ein Key existiert ($eingabe=1).
// Der Vergleich wird erst ab Editier-Ende gezeigt ($vergleich=1)
// Auch die Kandidaten können - anders als bisher - jederzeit ihre Meinung ändern 

if (time()<dzut($editieren_bis)) $vergleich = 0;  // nicht für die Spielwiese,


$kand = 0; $vergleich = 1; // ***********************Spielwiese!******
$eingabe = 1; $spalten = 5;
 
for ($i=0;$i<$kanz;$i++) {
if (db_result($ks,$i,'mnummer') == $MNr) {// Ja, das ist ein Kandidat.
	 $kand = 1; 
	 continue;} 
}


$vergleich = 1;  $spalten = $kanz+5; $eingabe = 0; 
if ($aendern) {$eingabe = 1; $vergleich=0;}  
if (!$key) $eingabe = 0; // Bei gelöschtem Key keine eigene Eingabe, kein Vergleichswert
if ($kand) $spalten = $spalten - 1;

$spielwiese=1; 
//if ($MNr == "0495018") {$kandidatendb = "kandidatenwahl"; $spielwiese = 0;} // Nur für Admin 

if ($MNr == '049X5018') echo "50 MNr=$MNr Key=$key speichern=$speichern kand=$kand $kandidatendb &auml;ndern=$aendern eingabe=$eingabe kanz=$kanz spielwiese=$spielwiese spalten=$spalten vergleich=$vergleich *******************************<br>"; // ******

echo '<tr><td colspan="'.$spalten.'" valign="top" align="left">';


//if ($MNr == "0495018") {include("spielwiese.php");} // Nur für Admin zum Testen




//hackermeldung(...
echo '<span class="text1b fa55"><br>Teilweise noch Spielwiese: Das ist noch das Muster, wie es sp&auml;ter mit den echten Kandidaten aussehen soll.<br>Aber die Eingabe ist schon "scharf": Deine Antworten kannst du schon eintragen - sie werden gespeichert: <a href="fragen.php?aendern=1&privat=2&spielwiese='.$spielwiese.'">Hier klicken.</a><br>Der Vergleich mit den echten Kandidaten kommt am 29. Februar.</span><br>';
echo '<h1>Der M-Wahl-O-Mat</h1>';
echo '<table><tr><td valign="top">';

echo '<span class="text1n f455">'.umlaute('<b>Das Prinzip:</b><br>Alle Ms sind eingeladen, zu diversen Vereinsthemen ihre Meinung zu &auml;u&szlig;ern und au&szlig;erdem anzugeben, f&uuml;r wie wichtig sie das jeweilige Thema halten. Kann oder will man zu einem Thema nichts sagen, lässt man die Felder einfach frei.<br>Besonders die Meinung der Kandidaten interessiert hier: Deine und deren Antworten sind dann hier sichtbar, so dass du vergleichen kannst.<br><br>Am Ende der Tabelle bekommst du - gewichtet nach deinen Angaben - eine "&Uuml;bereinstimmungsquote" zu jedem Kandidaten, der mitgemacht hat. Wie genau das gerechnet wird, steht unten beschrieben.<br><br>Zusätzlich kannst du auch neue Themen der Form "Derzeitiger Zustand - Wo soll es hingehen?" eintragen, wenn du den Eindruck hast, das hier noch Wichtiges fehlt. <br><br>');
echo '<a href="fragen.php?aendern=1&privat=2">Ausprobieren und Meinungen eintragen? Dann klicke hier. </a></span>';
echo '<br><br></td><td style="padding-left: 10px;">';
echo '<span class="text1n f455">'.umlaute('<b>Liebe MitMs,<br>dies ist eine "private" Initiative, unabhängig von Vereinsgremien und -funktion:</b><br>Die Thesen und Antwortvorschläge sind subjektiv (und keineswegs alles meine); sie sind nicht mit dem Vorstand abgestimmt und vielleicht fehlen auch noch Aspekte; die können übrigens gern nachgetragen werden.<br><b>Nicht jeder findet die Initiative gut; mir kam es darauf an, dass alle Wählerinnen und Wähler die Chance bekommen, vor ihrer Stimmabgabe die Haltung der Kandidierenden zu diversen Vereinsthemen zu erfahren. </b><br>Und: Amtsträger im Verein haben eher die Gelegenheit, sich zu präsentieren, als neue Bewerber. Auch dieses Ungleichgewicht soll diese Seite zumindest teilweise ausgleichen helfen.<br><b>Niemand muss mitmachen, alles ist freiwillig, es ist bloß ein Angebot ...</b><br>Viele Grüße<br><i>Hermann Meier (M5018)</i><br>PS: Jetzt schon ein Dankeschön an die Kandidaten, die sich den Fragen gestellt und ihre Positionen in ihren Kommentaren erläutert haben.<br>PPS: Bisher haben sich ');
echo mysqli_num_rows(mysqli_query($link,'SELECT id FROM antwortenwahl'));
echo ' Ms diese Seite angeschaut';
$akt = mysqli_num_rows(mysqli_query($link,'SELECT id FROM antwortenwahl WHERE ((anz > 5) AND (schl>200))'));
if (($MNr == "0495018") OR ($akt > 100)) echo ' und '.$akt.' (nicht kandidierende) Ms haben zum Vergleichen ihre eigene Meinung eingetragen';
echo '.</span><br><br></td></tr></table>';
echo '</span></td></tr>';



//echo "Hier wird gerade gebastelt! Kann sein, dass zwischendurch etwas merkw&uuml;rdig aussieht. <br>";


//************Datenaufbereitung ***************************
// Um die DB-Zugriffe zu minimieren, werden zunächst alle Antworten in ein Feld eingelesen: 0 ist die Antwort des Teilnehmers, 1...$kanz die der Kandidaten
$rs = mysqli_query($link,'SELECT * FROM fragenwahl'); // Fragentexte
$ranz = mysqli_num_rows($rs);


$as = mysqli_query($link,'SELECT * FROM antwortenwahl WHERE schl=99');  //Leersatz mit voreingestellten Werten
$a[0] = mysqli_fetch_array($as); 

// Nach dem Eingeben/Editieren die eingelesenen Daten in das Feld $a[0] schreiben
if ($verwerfen) { // es gab kein Speichern - daher die Werte stattdessen aus den POSTs holen	
	$as = mysqli_query($link,'SELECT * FROM antwortenwahl WHERE schl=99'); // Leeren Datensatz lesen, um $a entsprechend den anderen zu benamsen
	$a[0] = mysqli_fetch_array($as); 
	$a[0]["schl"] = $key;
	for ($j=0;$j<$ranz;$j++) {
	$j1=$j+1; $a[0]["f$j1"] = ipost("f$j1")*10000; $a[0]["w$j1"] = ipost("w$j1")*10000; $a[0]["bf$j1"] = ipost("bf$j1")*10000;
	if ($a[0]["f$j1"]) $a[0]['anz']++;
//	echo $j1.': '.$a[0]["f$j1"].', '.$a[0]["w$j1"].', '.$a[0]["bf$j1"].'<br>';
}
} else { // Werte aus der DB laden
$as = mysqli_query($link,'SELECT * FROM antwortenwahl WHERE schl='.$key); 
if (mysqli_num_rows($as)) $a[0] = mysqli_fetch_array($as); // Die Antworten des Teilnehmers werden aus der DB eingelesen.
}

$i=1;
//for ($i=1;$i<=$kanz;$i++) { // die Kandidatenantworten
while ($kkey=db_result($ks,$i-1,'schl')) {
$as = mysqli_query($link,'SELECT * FROM antwortenwahl WHERE schl='.$kkey);
echo '<br>SELECT * FROM antwortenwahl WHERE schl='.$kkey;

$a[$i] = mysqli_fetch_array($as);  // Die Antworten des Teilnehmers
$i++;
//echo '<br>'.$a[$i]['schl'];
} $kanz = $i;
//die;// ***************************************************************************************

// in array $a beginnen die Antworten ab Feld 5
for ($i=0;$i<=$kanz;$i++) {$a[$i]['anz'] = 0; // Anzahl der Antworten
for ($j=1;$j<$ranz;$j++) { if ($a[$i]["f$j"]<>"") $a[$i]['anz']++; 
}}      


if ($MNr=="0495018") {// Testphase
/*
for ($i=0;$i<=$kanz;$i++) {$a[0]['anz'] = 0; // Anzahl der Antworten
for ($j=1;$j<$ranz;$j++) { if ($a[$i]["f$j"]) $a[$i]['anz']++; 
echo "$i $j".$a[$i]["f$j"].'<br>';
} echo $a[$i]['anz'].'<br>';
}      

*/

//echo $a[0]['anz'];


} //Ende Admin

echo '<input type="hidden" name="anz" value="'.$a[0]['anz'].'">';


//************Tabelle zeigen ***************************

function kandidatenleiste() {// für die Zwischenüberschriften
	global $kand,$kanz,$knd,$a,$key,$linkerg,$bildquelle;
//	if (!$kand) {
	echo '<td align="center" class="st text3n"> </td>';
	for ($i=1;$i<=$kanz;$i++) { if ($key == $a[$i]['schl']) continue;
	echo '<td align="center" class="st">';
//	echo '<IMG SRC="'.$bildquelle.'img/'.$knd[$i]['bildfile'].'" width="40">';
echo '<a href="einzeln.php?key='.$key.$linkerg.'&zeige='.$knd[$i]['mnummer'].'"><IMG SRC="'.$bildquelle.'img/'.$knd[$i]['bildfile'].'" width="40"></a><br>';

	echo '<span class="text3n"><br>'.umlaute($knd[$i]['vorname']).'<br>'.umlaute($knd[$i]['name']);
	echo '</span></td>'; }//}
}


$anzahlfragen = mysqli_num_rows(mysqli_query($link,'SELECT id FROM fragenwahl')); // die letzte Ziffer
$zz = $kanz+1;
//echo '<tr><td colspan="3" valign="bottom" class="text1b"> Legende:</td></tr>';
echo '<tr>';

if (($vergleich) AND ($kandidatendb == "kandidatenwahl")) { 
// Teilmenge zeigen: =0 nur die Vorstandskand., =1 alle Kandidaten, =2 nur die Kand. für Schlichter/Finanzpr.
$zg = '<a class="menu" href="fragen.php?privat=2&zeigemir='; 
if  ($zeigemir == 1) {$abstand = "<br><br>";} else {$abstand = "&nbsp;&nbsp;&nbsp;";}
echo '<td colspan="3" valign="top" class="st"><span class="text2b">Aktuelle Ansicht: ';
if ($zeigemir == 0) echo 'Nur die Vorstandskandidaten ';
if ($zeigemir == 1) echo 'Alle Kandidaten ';
if ($zeigemir == 2) echo 'Nur Schlichter und Finanzpr&uuml;fer';
echo '<br><br>Auswahl: Zeige mir im Vergleich ...<br><br>';
if ($zeigemir <> 0) echo $zg.'0">nur die Vorstandskandidaten</a>'.$abstand;
if ($zeigemir <> 1) echo $zg.'1">alle Kandidaten</a>'.$abstand;
if ($zeigemir <> 2) echo $zg.'2">nur Schlichter- und Finanzpr&uuml;feramt</a>'.$abstand;
echo '</span></td>';

} 

echo '<td colspan="'.$zz.'" valign="top" class="text1b st">';
echo 'Legende:';
skalalegende(5,'Zustimmung',$skala5f,$skala5p,$skala5ptext);
skalalegende(5,'Wichtigkeit',$skala5f,$skala5w,$skala5wtext); 
echo ' ... wichtig<br><br>Wenn du die hell get&ouml;nten Felder mit der Maus ber&uuml;hrst, siehst du die jeweiligen Kommentare der Kandidaten  vollst&auml;ndig.</td>';


echo '</td></tr>';
$info = '<br><span class="text3n"><i>In der linken Spalte steht eine Art Zustandsbeschreibung,<br>in der rechten wird eine Art Entwicklungsrichtung angeboten.<br>Sag dazu, ob du dieser Richtung zustimmst oder sie ablehnst und (Zeile darunter) wie wichtig du diesen Aspekt f&uuml;r den Verein findest.</i></span>';
echo '<tr><td colspan="3" align="center" class="text1b st">Der Verein und seine Ziele'.$info;
echo '</td>';

if (!$vergleich) {
	echo '<td ';
if (!$kand) echo 'colspan="2"'; echo ' class="text2b st center">Meine Meinung<br>und wie wichtig das Thema f&uuml;r den Verein ist.<br>'.$MNr;
if ($kand) echo '<br><IMG SRC="'.$bildquelle.'img/'.db_result(mysqli_query($link,'SELECT bildfile FROM '.$kandidatendb.' WHERE mnummer="'.$MNr.'"'),0,'bildfile').'" width="60"></td><td class="st"><span class=text3n><br><br><i>Hinweis:<br>Du kannst hier bis zum '.$editieren_bis.' deine Meinung zu dem Thema und zur Wichtigkeit eintragen. Ab dem '.$editieren_bis.' sind in dieser Spalte dann im Vergleich die Antworten aller Kandidaten sichtbar.</i><br></span>';
echo '</td>'; $kanz=0;$q=2;
if ($aendern) {echo '<td class="text3n st" colspan="'.$q.'">jeweils obere Zeile: linkes Feld Ablehnung/Zustimmung (Skala 1-5) und rechtes Feld f&uuml;r etwaige Kommentare;<br>untere Zeile: linkes Feld Wichtigkeit (Skala 1-5) und rechtes Feld f&uuml;r etwaige Kommentare</td>';}
else {echo '<td class="text3n st" colspan="'.$q.'">jeweils obere Zeile: Ablehnung/Zustimmung,<br><br>untere Zeile: Wichtigkeit</td>';}
} else {
$zw = '<td class="text2b st center">Meine<br>Meinung';
echo $zw; 
if ($kand)  echo '<br><IMG SRC="'.$bildquelle.'img/'.db_result(mysqli_query($link,'SELECT bildfile FROM '.$kandidatendb.' WHERE mnummer="'.$MNr.'"'),0,'bildfile').'" width="60">';
if (($a[0]['anz']) AND (!$aendern) ) echo '<br><input type="submit" name="aendern" value="&auml;ndern">'; // Wenn schon eigene Antworten vorliegen
else {echo '<input type="hidden" name="aendern" value="1"><br><input type="submit" name="aendern" value="eintragen">';}
echo '</td>'; $zw .= '</td>';
for ($i=1;$i<=$kanz;$i++) {//$offset = -100-$i*30+ ($kanz>13)*300- ($kanz<8)*60;

	if ($vergleich) $knd[$i] = mysqli_fetch_array(mysqli_query($link,'SELECT * FROM '.$kandidatendb.' WHERE schl='.$a[$i]['schl'])); // Datensatz je Kandidat
if ($key <> $a[$i]['schl']) { // Den Kandidaten nicht doppelt erscheinen lassen
echo '<td align="center" valign="top" class="st">';
if ($zeigemir) echo '<span class="text3n">'.welchesamt($knd[$i]).'<br><br></span>';

echo '<span class="text3n">'.umlaute($knd[$i]['vorname']).'<br>'.umlaute($knd[$i]['name']).'<br>'; 

echo '<a href="einzeln.php?key='.$key.$linkerg.'&zeige='.$knd[$i]['mnummer'].'"><IMG SRC="'.$bildquelle.'img/'.$knd[$i]['bildfile'].'" width="40"></a><br>';
$zwx = '<span class="text3n">'.umlaute($knd[$i]['vorname']).'<br>'.umlaute($knd[$i]['name']); 
//if ($knd[$i]['amt1']) $zwx .= '<br>(K: Vorstand)'; if ($knd[$i]['amt2']) $zwx .= '<br>(K: Finanzpr.)'; if ($knd[$i]['amt3']) $zwx .= '<br>(K: Schlichter)'; 
 
//echo $zwx; $zw .= '<td class="text2b st center">'.$zwx.'</td>';
echo '</span></td>';$q = $spalten+1;
}}
//echo '<td> </td>';
//kandidatenleiste(); */
}
echo '</tr>';

for ($j=0;$j<$anzahlfragen;$j++) {$j1=$j+1;
if ($j==4) {echo '<tr><td colspan="3" align="center" class="text1b st">Der Verein und seine Mitglieder'.$info.'</td>';
if ($eingabe) {echo '<td colspan="2" class="nurunten">';
skalalegende(5,'Zustimmung',$skala5f,$skala5p,$skala5ptext);
skalalegende(5,'Wichtigkeit',$skala5f,$skala5w,$skala5wtext);  echo ' ... wichtig</td>';} else {kandidatenleiste();}
echo '</tr>';}
if ($j==9) {echo '<tr><td colspan="3" align="center" class="text1b st">Der Verein und seine Organisation'.$info.'</td>';
if ($eingabe) {echo '<td colspan="2" class="nurunten">';
skalalegende(5,'Zustimmung',$skala5f,$skala5p,$skala5ptext);
skalalegende(5,'Wichtigkeit',$skala5f,$skala5w,$skala5wtext); echo ' ... wichtig</td>';} else {kandidatenleiste();}
echo '</tr>';}
if ($j==13) {echo '<tr><td colspan="3" align="center" class="text1b st">Neue Themen'.$info.'</td>';
if ($eingabe) {echo '<td colspan="2" class="nurunten">';
skalalegende(5,'Zustimmung',$skala5f,$skala5p,$skala5ptext);
skalalegende(5,'Wichtigkeit',$skala5f,$skala5w,$skala5wtext); echo ' ... wichtig</td>';} else {kandidatenleiste();}
echo '</tr>';}



echo '<tr><td rowspan="2" align="center" class="text4n st">'.$j1.'</td><td rowspan="2" valign="top" align="left" class="text2n st"><b>';
echo umlaute(db_result($rs,$j,'Stichwort')).'</b><br><br>';
echo umlaute(db_result($rs,$j,'Status')).'</td>';
echo '<td rowspan="2" valign="top" align="left" class="text2n st">';
echo '<span class="text-align: right;"><b>Ja oder nein?</b></span><br><br>'.umlaute(db_result($rs,$j,'Ziel')).'</td>';
$b=$k=0;$btext="";

if ($eingabe) { // Änderungsmodus bzw. Ersteingabe; 
//echo '<span class="text5n">Zustimmung/Kommentar</span>';
if ($a[0]["f$j1"]) {$q++; $k = round($a[0]["f$j1"]/10000,0); $b=$a[0]["f$j1"]-$k*10000; }
if ($b) $btext = db_result(mysqli_query($link,'SELECT bem FROM bemerkungenwahl WHERE id='.$b),0,'bem');
$drueber = "Skala<br>1...5"; textfeld ("f$j1",$k,1,1,0,"Meinung dazu<br>Skala 1...5"); 
$drueber = "<br>ggf. Kommentar";textfeld ("bf$j1",$btext,50,2,0,"<br>ggf. Kommentar (maximale L&auml;nge: 1024 Zeichen)");
echo '</tr><tr>';$b=$k=0;$btext="";
if ($a[0]["w$j1"]) {$q++; $k = round($a[0]["w$j1"]/10000,0); $b=$a[0]["w$j1"]-$k*10000; }
if ($b) $btext = db_result(mysqli_query($link,'SELECT bem FROM bemerkungenwahl WHERE id='.$b),0,'bem');
$drueber = "Skala<br>1...5"; textfeld ("w$j1",$k,1,1,0,"Wichtigkeit<br>Skala 1...5"); 
echo '<td class="nurunten"> </td>';
//$drueber = "<br>ggf. Kommentar"; textfeld ("bw$j1",$btext,50,2,0,"<br>ggf. Kommentar");
} 
else {
$q=0;
for ($i=0;$i<=$kanz;$i++) {
if (($i==0) OR ($key <> $a[$i]['schl'])) skala5($a[$i]["f$j1"],$skala5p,1,1);  
} 

echo '</tr><tr>';
$q=0;
for ($i=0;$i<=$kanz;$i++) {
if (($i==0) OR ($key <> $a[$i]['schl'])) skala5($a[$i]["w$j1"],$skala5w,1,0); 
//if (($i==0) OR ($key <> $a[$i]['schl'])) skala5($a[$i]["w$j1"],$skala5wtext,0,0); 

} 
}
echo '</tr>';

//echo '<td class="mit" align="center"><span class="text2b center '.rand(1,5).'">'.$q.'</span></td></tr>';


} // Ende Schleife
echo '<input type="hidden" name="zeigemir" value="'.$zeigemir.'">';

if ($eingabe) {
echo '<tr><td colspan="5" align="left" class="text2b st">Hier kann M gern ein wichtiges neues Thema erg&auml;nzen:</td></tr>';
echo '<tr><td class="st"> </td><td colspan="2" align="left" class="text2n st"><b>Stichwort: <input type="text" size="40" name="neust"><br><br>Derzeit ist ...<br>';
echo '<textarea class="text2n" name="neufr" cols="60" rows="4"></textarea>';
echo '</td><td colspan ="2" align="left" class="text2b st"><br>Sollte der Verein ... <br>(Wohin sich das Thema entwickeln k&ouml;nnte/sollte - bitte als Frage so formulieren, dass man Ablehnung/Zustimmung klar ausdr&uuml;cken kann)<br>';
echo '<textarea class="text2n" name="neuth" cols="60" rows="4"></textarea>';
echo'</td>';

echo '<tr><td colspan="3" class="text1b st center">Mit dem Absenden wird deine Eingabe gespeichert.';
echo '<span class="text3n">'.umlaute('<br>Damit du deine Antworten variieren und sie später nochmal anschauen/ändern und bei neuen Themen um weitere Antworten ergänzen kannst, werden sie unter einer individuellen langen Zufallszahl gespeichert und in einer separaten Tabelle mit den Mitgliedsnummern verknüpft. Mit Beendigung der Wahl wird diese Verknüpfung gelöscht, so dass alle Einträge zuverlässig anonymisiert sind; daraus kann anschließend eine summarische Auswertung aller Antworten erstellt und abgerufen werden - als so eine Art unrepräsentatives Meinungsbild.<br>Wenn du das nicht möchtest, verwende den zweiten Button - dann siehst du einmalig deine Auswertung und deine Antworten werden nicht gespeichert; wurden für dich Eingabewerte aufgehoben, kannst du sie mit dem dritten Button löschen lassen.</span>');
echo '</td><td colspan="2" class="text1b st center">';
// Noch einbauen: Kandidaten sehen nur den Speichern-Knopf;
if ($kand) {
echo '<input type="hidden" name="privat" value="2"><input style="color: red;" type="submit" name="fragen" value="Eingabewerte speichern">';}
else {
echo '<input type="hidden" name="privat" value="2"><input style="color: red;" type="submit" name="fragen" value="Auswertung zeigen und die Eingabewerte speichern">';
echo '<br>oder<br><input style="color: red;" type="submit" name="fragen" value="Auswertung zeigen und meine aktuellen Eingabewerte verwerfen">';
// Noch einbauen: Wenn Werte gespeichert sind, löschen anbieten;
echo '<br>oder<br><input style="color: red;" type="submit" name="fragen" value="Alle f&uuml;r mich aufgehobenen Eingabewerte endg&uuml;ltig verwerfen">';
}
echo '</form></td></tr>';}
else 
{echo '<tr class="st"><td colspan="3"  align="left" class="text1b">Im Editiermodus kann man &uuml;brigens auch selbst ein Thema vorschlagen ...</td>';
if (($a[0]['anz']) AND (!$aendern) ) {echo '<td><input type="submit" name="aendern" value="editieren"></td>';} // Wenn schon eigene Antworten vorliegen</tr>';
else {echo '<td> </td>';}
for ($i=0;$i<=$kanz;$i++) {$a[$i]['anz'] = 0; // Anzahl der Antworten
for ($j=1;$j<=$ranz;$j++) { if ($a[$i]["f$j"]) $a[$i]['anz']++; 
}}  


if ($vergleich) {
for ($i=1;$i<=$kanz;$i++) {	if ($key == $a[$i]['schl']) continue;
	echo '<td class="st" align="center" valign="bottom"><span class="text1n">'.$a[$i]['anz'].'<br>Antw.</td>';}

echo '</tr><tr class="st"><td colspan="4"  align="left" class="text1b" style="padding: 0 5 0 5;">Die "gewichtete &Uuml;bereinstimmungsquote"<br>';
echo '<span class="text2n">'.umlaute('Formel: Summe aller Zeilen mit der Formel <br>(4 minus der absoluten Differenzen zwischen deiner Meinung und der der jeweiligen Kandidaten) mal der von dir eingesch&auml;tzten Wichtigkeit (oberer Wert) und darunter die Summe der Differenzen mal Wichtigkeit.<br><b>Je höher also der obere Wert, desto größer ist die Überein&shy;stim&shy;mung in den dir wichtigen Themen, je negativer der untere Wert, desto mehr Konflikte gibt es.</b><br>Ja, etwas primitiv. Aber nachvollziehbar, oder? Wenn unter uns ein in solchen Bewertungsmetoden Kundiger ist, freue ich mich auf Anregungen zum Bessermachen.<br>Bis dahin ist bestimmt auch interessant, mal durch die Themen zu gehen. ');
//if ($verwerfen) echo '<br><br>XXXNoch nicht realisiert: <br><b>Wunschgem&auml;&szlig; wurden deine Eingabewerte verworfen.</b>';

echo '</span></td>';


for ($i=1;$i<=$kanz;$i++) {
if ($key <> $a[$i]['schl']) {echo '<td class="st" align="center" valign="bottom"><span class="text1n">';
$x=$y=0; for ($j=1;$j<=$anzahlfragen;$j++) { 
	$k = round($a[0]["f$j"]/10000); $w = round($a[0]["w$j"]/10000); 
	$kx = round($a[$i]["f$j"]/10000,0); 
//	echo "w$w k$k kx$kx = ".$w*(4-ABS($kx-$k)).'<br>';
	if ($k*$kx) {// beide Bewertungen müssen > 0 sein
		$x = $x + $w*(4-ABS($k-$kx)); // Übereinstimmungswert
		$y = $y + $w*ABS($k-$kx); // Kontroversvert
	}

// beide voll dafür oder dagegen:4-(5-5) = 4*Wichtigkeit; einer voll dafür, der andere voll dagegen:4-(5-1) = 0*Wichtigkeit
} $z = $x-$y;
//echo '<IMG SRC="img/'.$knd[$i]['bildfile'].'" width="40"><br>';
echo '<a href="einzeln.php?key='.$key.$linkerg.'&zeige='.$knd[$i]['mnummer'].'"><IMG SRC="'.$bildquelle.'img/'.$knd[$i]['bildfile'].'" width="40"></a><br>';

echo '<span class="text3n">'.umlaute($knd[$i]['vorname']).'<br>'.umlaute($knd[$i]['name']);
echo '<br><br></span><br>'; 
if ($a[$i]['anz']>5) {
	if ($a[$i]['anz']>10) {echo '+ '.$x.'<br>- '.$y.'<br><b>= '.$z.'</b>';} 
	else {echo '(<i>+ '.$x.'<br>- '.$y.'<br><b>= '.$z.'</b></i>)';} 
	} else {echo '<br><br><br>';}

echo '</span></td>';}
} 
echo '</tr>';
}}

/*
*/

weiter(1,1,1,1,0);

echo '</table></td></tr>';

$weiter = ""; //"weiter ...";
include ("schwanz.php");
?>
