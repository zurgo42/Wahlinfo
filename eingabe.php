<?php // Eingabeseite Kandidaten
$dieseseite = "eingabe";
$daten = 'textewahl';
$naechsteseite = "einzeln";

include ("kopf.php");		// Den Aufbau und die wesentlichen Funktionen laden
$ks = mysqli_query($link,'SELECT * FROM '.$kandidatendb.' ORDER BY name, vorname'); //alle Kandidaten
//$dbzeigen=1; echo $kandidatendb; //****************************************************
include("zeitsteuerung.php"); // Editierende steuern
$eingabe = 1;
if ($eingabe) { // Es darf eingegeben werden.
$ks = mysqli_query($link,'SELECT * FROM '.$kandidatendb.' WHERE mnummer="'.$MNr.'"');
$knd = mysqli_fetch_array($ks);

// Hinweis: Das Speichern läuft über den key - also nicht über die Mitgliedsnummer. Daran muss man beim Testen denken!
$einzelkand = 'key='.$key.$linkerg.'&zeige='.$MNr;
echo '</form><form action="einzeln.php?key='.$key.$linkerg.'&zeige='.$MNr.'" method="post" enctype="multipart/form-data">';

echo '<tr><td colspan="'.$spalten.'" valign="top" align="left">';
echo '<h1>'.$knd['vorname'].' '.$knd['name'].':<br>Erg&auml;nzende pers&ouml;nliche Angaben:</h1>';
echo '<tr>'; $colspan=2;
textfeld('hplink',$knd['hplink'],64,1,2,'<span class="text1b">Falls du eine eigene Homepage oder SozialMediaseite<br>(z.B. facebook) hast, gib bitte hier den Link an<br>(Form: "http://www.deinlink.de" bzw. "https://www.facebook.com/maike.muster.5201")</span>');  // Textfeld-Eingabe
textfeld('videolink',$knd['videolink'],64,1,2,'<span class="text1b">Falls du den Link auf ein Video von dir zeigen m&ouml;chtest,<br>gib bitte hier den Link an<br>(Form: "https://www.youtube.com/watch?v=xyz")<br></span>');  // Textfeld-Eingabe
echo '</tr>';

if ($knd['amt1'] OR $knd['amt2'] OR $knd['amt3']) { // Vorstand

echo '<tr><td colspan="4"><h1>Dein Dream-Team</h1></td></tr>';
$ks0 = 'SELECT vorname, name, mnummer,bildfile FROM '.$kandidatendb.' WHERE ((amt1=1 OR amt2=1 OR amt3=1) AND mnummer<>"'.$MNr.'") ORDER BY name';
echo '<tr><td colspan="4"><p class="text1b">Mit welchen Kandidaten w&uuml;rdest du im Vorstand am liebsten zusammenarbeiten?</p></td></tr><tr><td colspan="4"><table><tr>';
for ($i=1;$i<6;$i++) {$ks = mysqli_query($link,$ks0); $kanz= mysqli_num_rows($ks); 
echo '<td><select size="1" name="team'.$i.'"> '; 
echo '<option value="0"> wei&szlig; ich noch nicht <option>';
for ($k=0;$k<$kanz;$k++) {$kx = mysqli_fetch_array($ks);
echo ' <option '; if ($knd["team$i"] == $kx['mnummer']) echo 'selected ';
echo 'value="'.$kx['mnummer'].'">'.$kx['vorname'].' '.$kx['name'].'<option> ';} echo '</select></td>'; }
echo '</tr></table></td></tr>';




echo '<tr><td colspan="4"><h1>Deine Ressort-Pr&auml;ferenzen</h1></td></tr>';
echo '<tr><td colspan="4"><p class="text1b">Ressorts/Arbeitsgebiete:<br>Welche w&uuml;rdest du anstreben, welche eher nicht?</p>';
echo '<span class="text2b">';
echo '<br><b>Trage deine Wahl in das kleine K&auml;stchen ein; wenn du magst, kannst du sie rechts daneben kurz begr&uuml;nden.</b><br><br>';
echo 'Verwende bitte die Skala:<br>';
for ($j=1;$j<5;$j++) {
echo '<b>'.$j.'</b>';
echo '= '.$skala5r[$j].'; ';
echo '<br>';
} 
$j=5; echo '<b>'.$j.'</b>';
echo ' = '.$skala5r[$j].'; ';

//$ks = mysqli_query($link,'SELECT * FROM '.$kandidatendb.' WHERE mnummer="'.$MNr.'"');
echo '</span></td>';
echo '</tr>';

for ($i=1;$i<4;$i++) {// Die Vorstandsbereiche
	if ($knd["amt$i"]) {
	$amtv=$i*30;
	echo '<tr><td colspan="4"><h3>'.$rs[$amtv].'</h3></td></tr>';
	// Die einzelnen Ressorts durchgehen
	$av0=$amtv+1;
	for ($av=$av0;$av<=$rnr[$amtv];$av++) { 
 $ress = "r".$rnr[$av];
 
echo '<tr><td colspan="2" align="left" class="text2b nurunten nurrechts">'.$rs[$av].'</td>';
$q=0;
$k=0; $b=$btext="";
if ($knd[$ress]) {
$k = round($knd[$ress]/10000,0); $b=$knd[$ress]-$k*10000; if ($k>2) $q++; 
}
if ($b) $btext = db_result(mysqli_query($link,'SELECT bem FROM bemerkungenwahl WHERE id='.$b),0,'bem');
if ($k==0) $k=" "; 
echo '<td class="nurunten">';
echo '<select name="'.$ress.'" size="1">';
for ($jy=1;$jy<=5;$jy++) {
echo '<option';
if ($k == $jy) {echo ' selected';}
echo ' value="'.$jy.'">&nbsp;'.$skala5z[$jy].'&nbsp;</option>';}
echo '</select>';
//skalaselect ("a".$j1,$k,5,$skala5z); 
echo '</td>';//textfeld ($ress,$k,1,1,0,""); 
echo '<span class="3n">';textfeld ("b$ress",$btext,60,2,0,"");
echo '</span>';
echo '</tr>';

} // Ende Schleife Ressorts
}} // Ende Vorstandsbereiche
} // Ende nur Vorstand


$rs = mysqli_query($link,'SELECT * FROM anforderungenwahl ORDER BY Nr');
$ranz = mysqli_num_rows($rs);

echo '<tr><td colspan="4" valign="top" align="left">';
echo '<h1>Zeit, Motivation, Pr&auml;ferenzen</h1>';
echo '<span class="text3b">Auf Wunsch des Vorstands hat das Strategieteam eine Liste von Anforderungen erstellt, die f&uuml;r die Vorstandsarbeit im Verein hilfreich sein k&ouml;nnten. Das Strategietean hat einige davon ausgew&auml;hlt und legt sie allen Kandidaten vor.<br>Das k&ouml;nnte das Bild aus den Vorstellungstexten etwas abrunden und den Ms ihre Entscheidung erleichtern.<br><br></span>';
echo '</td></tr>';

echo '<tr><td colspan="4" class="nurunten"><h2>Allgemeine Fragen</h2><span class="text3b">(Alle Kandidaten)</span></td></tr>';
echo '<tr><td colspan="4">';
for ($j=0;$j<8;$j++) {$j1=$j+1;
	echo '<tr><td align="center" class="text4n nurunten nurrechts">'.$j1.'</td><td colspan="2" class="nurunten nurrechts"><span class="text2b">'.umlaute(db_result($rs,$j,'Anforderung')).'</td>';

	$bem=""; if ($knd["a$j1"]) $bem = db_result(mysqli_query($link,'SELECT bem FROM bemerkungenwahl WHERE id='.$knd["a$j1"]),0,'bem');
	textfeld ("ba$j1",$bem,60,3,0,"");
//	echo '<td align="center" class="text2n mit"><span class="text1n">'.$bem.'</td></tr>';}
}


if ($knd['amt1'] OR $knd['amt2'] OR $knd['amt3']) { // Nur Vorstand

echo '<tr><td colspan="4"><span class="text2b"><br><br>Je nach Ressortzust&auml;ndigkeit sind f&uuml;r  Vorstandsmitglieder bestimmte Kompetenzen und Erfahrungen mehr oder weniger wichtig. Wie ist das bei dir?<br><br></span></td></tr>';
echo '<tr><td colspan="4"><span class="text2b"><h2>Kompetenzen, Erfahrungen</h2></span>';
echo '<span class="text2b">';
echo '<b>Trage deine Wahl in das kleine K&auml;stchen ein; wenn du magst, kannst du sie rechts daneben kurz begr&uuml;nden.</b><br><br>';
echo 'Verwende bitte die Skala:<br>';
for ($j=1;$j<5;$j++) {
echo '<b>'.$j.'</b>';
echo ' = '.$skala5a[$j].'; ';
echo '<br>';
} 
$j=5; echo '<b>'.$j.'</b>'; echo ' = '.$skala5a[$j]; 
echo '</span></td>';
echo '<br></tr>';
echo ' ';
echo '<br>';


for ($j=8;$j<15;$j++) {$j1=$j+1;$j9=$j-8;
echo '<tr><td align="center" class="text4n nurrechts nurunten">'.$j1.'</td><td align="left" class="text2b nurrechts nurunten">'.umlaute(db_result($rs,$j,'Anforderung')).'</td>';
$q=0;
$k=0; $b=$btext="";
if ($knd["a$j1"]) {$q++; $k = round($knd["a$j1"]/10000,0); $b=$knd["a$j1"]-$k*10000; }
If ($b) $btext = db_result(mysqli_query($link,'SELECT bem FROM bemerkungenwahl WHERE id='.$b),0,'bem');
if ($k==0) $k=" "; 
echo '<td class="nurunten">';
echo '<select name="a'.$j1.'" size="1">';
for ($jy=1;$jy<=5;$jy++) {
echo '<option';
if ($k == $jy) {echo ' selected';}
echo ' value="'.$jy.'">&nbsp;'.$skala5z[$jy].'&nbsp;</option>';}
echo '</select>';
//skalaselect ("a".$j1,$k,5,$skala5z); 
echo '</td>'; 
//textfeld ("a$j1",$k,1,1,0,""); 
textfeld ("ba$j1",$btext,60,2,0,"");



echo '</tr>';
} // Ende Schleife
} // Ende nur Vorstand
} // Ende Eingaba

echo '<tr><td><br></td></tr>';




if ($eingabe) echo '<tr><td colspan="2" class="text1b mit center">Mit dem Absenden wird deine Eingabe gespeichert:</td><td colspan="2" class="text1b mit center"><input type="submit" name="eingabe" value="absenden"></form></td></tr>';
// gespeichert wird das in kopf.php


//weiter(0,0,0,1,1);

//echo '</table></td></tr>';

$weiter = ""; //"weiter ...";
include ("schwanz.php");
?>
