<?php // Erste Seite Vorstandsfrageboten
$dieseseite = "anforderungen";
$daten = 'textewahl';
$naechsteseite = "ressorts";
$privat=1;

include ("kopf.php"); 
include("textewahl.php");
$spielwiese = ubeide('spielwiese');
if ($spielwiese) $kandidatendb = "spielwiesewahl";

$ks = mysqli_query($link,'SELECT * FROM '.$kandidatendb.' WHERE (amt1=1 OR amt2=1 OR amt3) ORDER BY amt1 DESC,amt2 DESC,amt3 DESC,amt4 DESC,amt5 DESC,name, vorname'); //Nur Vorstandskandidaten


$kanz = mysqli_num_rows($ks);
$spalten = $kanz+2;

$rsa = mysqli_query($link,'SELECT * FROM anforderungenwahl ORDER BY Nr');
$ranz = mysqli_num_rows($rsa);
$ks1 = mysqli_query($link,'SELECT * FROM '.$kandidatendb.' ORDER BY amt1 DESC,amt2 DESC,amt3 DESC,amt4 DESC,amt5 DESC,name, vorname'); //für alle Kandidaten zeigen
$kanz1 = mysqli_num_rows($ks1);
for ($i=1;$i<=$kanz1;$i++) {$knd[$i] = mysqli_fetch_array($ks1);} // Datensatz je Kandidat

echo '<tr><td colspan="'.$spalten.'" valign="top" align="left">';
//hackermeldung(...
echo '<h1>Zeit, Motivation, Pr&auml;ferenzen</h1>';
echo '<span class="text2b">Vor ein paar Jahren wurde eine Liste von Anforderungen erstellt, die f&uuml;r die Vorstandsarbeit im Verein hilfreich sein k&ouml;nnten.<br>Die Antworten der einzelnen Kandidaten k&ouml;nnten das Bild aus den Vorstellungstexten etwas abrunden und dir deine Entscheidung erleichtern.<br></span>';

if ($spielwiese) {echo '<h3>Dies ist die "Spielwiesen-Version", die echten Kandidaten kommen sp&auml;ter</h3>'; 
} else {

if (time()<dzut($editieren_bis)) { //Ab jetzt stehen die Kandidaten fest - die dürfen noch nicht angezeigt werden!
echo '<p class="text1b">Der Zugriff auf die Angaben der Kandidaten ist erst m&ouml;glich, wenn alle ihre Eingaben get&auml;tigt haben,  nach dem '.$editieren_bis;
echo '<br><br><a href="index.php">Zur&uuml;ck zur &Uuml;bersichtsseite mit den Kandidaten</a><br><br></p>';
$weiter=""; include ("schwanz.php"); die;
}
}

echo '<p class="text1b">Die Einzelseite der Kandidaten mit ihren jeweiligen Antworten bekommst du durch Klick auf das jeweilige Foto<br></p>';
echo '</td></tr>';
//echo 'An dieser Seite wird gerade gebastelt.'; //if ($MNr <> "0495018") die; // ********************

$q1 = $spalten-2; $q=0;
echo '<tr><td colspan="2"><h2>Allgemeine Fragen</h2><span class="text3b">(Alle Kandidaten)</span></td>';

echo '<td colspan="'.$q1.'" class="text3n">';
echo '<table><tr><td class="text3n">';
$rsa2 = mysqli_query($link,'SELECT * FROM anforderungenwahl ORDER BY Nr LIMIT 8');
while ($frage = mysqli_fetch_array($rsa2)) {
	$q++;echo '<a href=#goto'.$q.'>'.$q.':</a> '.$frage['Anforderung'].'<br>';
	if ($q == 5) echo '</td><td class="text3n">';
	}
echo '<a href=#erf>9:</a> Kompetenzen/Erfahrungen'; 
echo '</td></tr></table></td></tr>';

echo '<tr><td colspan="'.$spalten.'"><table width="99%">'; // Tabelle Allg. Fragen
for ($j=0;$j<8;$j++) {$j1 = $j+1; 
	echo '<tr><td><a name="goto'.$j1.'"></td></tr>';
	echo '<tr><td align="center" class="text4n mit hell">'.$j1.'</td><td colspan="3" class="mit hell"><span class="text2b">'.umlaute(db_result($rsa,$j,'Anforderung')).'</td></tr>';
	$q=0;
	for ($i=1;$i<=$kanz1;$i++) {// Datensatz je Kandidat
	if ($q>=5) {echo '<tr><td class="ohne"> </td><td colspan="3" class="mit hell"><span class="text2b"> ... '.umlaute(db_result($rsa,$j,'Anforderung')).'</td></tr>'; $q=0;}
	$q++;
	echo '<tr><td> </td><td width="35" align="center" class="text4n st ohnerechts"><a href="einzeln.php?key='.$key.$linkerg.'&zeige='.$knd[$i]['mnummer'].'"><IMG SRC="'.$bildquelle.'img/'.$knd[$i]['bildfile'].'" width="32"></a></td>';
	echo '<td width="100" class="text3n st ohnelinks"><b>'.$knd[$i]['vorname'].' '.$knd[$i]['name'];
	echo '</b><br>Amt: '.welchesamt($knd[$i]);
	echo '</td>';
	$bem=""; if ($knd[$i]["a$j1"]) $bem = db_result(mysqli_query($link,'SELECT bem FROM bemerkungenwahl WHERE id='.$knd[$i]["a$j1"]),0,'bem');
	echo '<td align="center" class="text2n st"><span class="text1n">'.$bem.'</td></tr>';}
}

echo '</table></td></tr>';

echo '<tr><td><a name="erf"></td></tr>';

echo '<tr><td colspan="'.$spalten.'"><span class="text2b"><br><br>Je nach Ressortzust&auml;ndigkeit sind f&uuml;r  Vorstandsmitglieder bestimmte Kompetenzen und Erfahrungen mehr oder weniger wichtig. Wir haben die Vorstandskandidaten danach gefragt - hier die Antworten:<br><br></span></td></tr>';


echo '<tr><td colspan="'.$kanz.'"><span class="text1b">Kompetenzen, Erfahrungen</a><br></span>';

echo '<span class="text4b"><br>Legende: ';
for ($j=1;$j<5;$j++) {
	echo $skala5[$j];
	echo ' = '.$skala5a[$j].'; ';
//if ($j==3) echo '<br>';
} $j=5; echo $skala5[$j].' = '.$skala5a[$j]; 
echo '</span></td></tr><tr><td colspan="2" rowspan="2"><span class="text4n"><br>Wenn du die hell get&ouml;nten Felder mit der Maus ber&uuml;hrst, siehst du die jeweiligen Kommentare der Kandidaten vollst&auml;ndig.';
echo '<br><br>Die Einzelseiten der Kandidaten bekommst du durch Klick auf das jeweilige Foto.';
echo '</span></td>';



for ($i=0;$i<$kanz;$i++) {$knd[$i] = mysqli_fetch_array($ks); // Datensatz je Kandidat
echo '<td '; //if ($eingabe) echo 'colspan="2" ';
echo 'align="center" valign="top"><a href="einzeln.php?key='.$key.$linkerg.'&zeige='.$knd[$i]['mnummer'].'"><IMG SRC="'.$bildquelle.'img/'.$knd[$i]['bildfile'].'" width="40"></a><br></td>';} echo '</tr><tr>';
for ($i=0;$i<$kanz;$i++) {$offset = -$i*20;
	echo '<td><span class="text3n">'.umlaute($knd[$i]['vorname']).'<br>'.umlaute($knd[$i]['name']);
echo '</span></td>';} 
echo '</tr>';

for ($j=8;$j<15;$j++) {$j1=$j+1;$j9=$j-8;
echo '<tr><td align="center" class="text4n st">'.$j1.'</td><td align="left" class="text2b st hell">'.umlaute(db_result($rsa,$j,'Anforderung')).'</td>';
$q=0;
for ($i=0;$i<$kanz;$i++) {
$k=0; $b=$btext="";
if ($knd[$i]["a$j1"]) {$q++; $k = round($knd[$i]["a$j1"]/10000,0); $b=$knd[$i]["a$j1"]-$k*10000; }
If ($b) $btext = db_result(mysqli_query($link,'SELECT bem FROM bemerkungenwahl WHERE id='.$b),0,'bem');
skala5($knd[$i]["a$j1"],$skala5,1,1);
} 


echo '</tr>';

} // Ende Schleife


weiter(1,1,1,0,0);

echo '</table></td></tr>';

$weiter = ""; //"weiter ...";
include ("schwanz.php");
?>
