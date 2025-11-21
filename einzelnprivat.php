<?php // Erste Seite Vorstandsfrageboten
$daten = 'textewahl';
$naechsteseite = "einzeln";

include ("kopf.php"); 
include ("starten.php");
echo '<form action="'.$naechsteseite.'.php?key='.$key.$linkerg.'" method="post" enctype="multipart/form-data">';


// Festlegungen f?r diese Umfrage:
// Weil so kurz und Struktur relativ einfach, individuell programmiert
// Fragennummern 2stellig ab f11 - Vorstandsbeurteilungen dreistellig f1xx, f2xx etc

include("menuleiste.php");

$zeige = ubeide('zeige'); $amt = ubeide('amt');
$tx = mysqli_query($link,'SELECT * FROM aemterwahl ORDER BY id');
$ks = 'SELECT * FROM kandidatenwahl WHERE mnummer="'.$zeige.'"';
// echo $ks;
$kx = mysqli_query($link,$ks); 
$kand = mysqli_fetch_array($kx);
echo '<tr><td colspan="2"><table><tr><td width="68%" valign="top" align="left" class="nurunten">';
echo '<h3>Der offizielle Kandidatentext</h3>';
echo '<div style="float: left; width: 120px; margin: 10px;">';// Foto-Box
echo '<IMG SRC="img/'.$kand['bildfile'].'" width="110"></div>';
echo '<div style="padding-left: 1px;"><h1>'.umlaute($kand['vorname']).' '.umlaute($kand['name']).'</h1><span class="text1n">MNr: '.substr($kand['mnummer'],3);
echo '<span class="text1b"><br><br>Kandidatur f&uuml;r '; $q=0;
for ($i=1;$i<6;$i++) {if ($kand["amt$i"]) {
if ($q>0) echo ', ';
echo db_result($tx,$i,'Amt').' '; $q++;}}
echo '<br><br></span>';
echo '<br>'.umlaute($kand['text']);

echo '</div>';

$erg = 0;
echo '<td valign="top" width="30%" class="nurlinks nurunten backp"><h3>Erg&auml;nzende Informationen</h3><span class="text2n">Soweit hier Informationen der Kandidierenden verlinkt sind, sind sie nicht Teil der offziellen Wahl-Ank&uuml;ndigung des Vereins.</span><ul class="text1n">';
if (laenge($kand['hplink'])) {echo '<li>Der <a href="'.$kand['hplink'].'">Link auf die Hompage</a>  von '.$kand['vorname'].'</li>'; $erg++;}
if (laenge($kand['videolink'])) {echo '<li>Der <a href="'.$kand['videolink'].'">Link auf das Vorstellungsvideo</a><br>von '.$kand['vorname'].'</li>';  $erg++;}
$a = $r = 0; for ($i=1;$i<15;$i++) {$a = $a + ($kand["a$i"]>9999); $r = $r + ($kand["r$i"]>9999);} //echo "$a $r";
if ($kand['amt1'] AND $a>3) {echo '<li>Die Liste der ';
echo '<a href="anforderungen.php">';
echo 'Aufgaben/Ressortzust&auml;ndigkeiten</a><br>und die entsprechenden Pr&auml;ferenzen von '.$kand['vorname'].' und der &uuml;brigen Vorstands-Kandidaten.</li>'; $erg++;}
if ($kand['amt1'] AND $r>1) {
echo '<li>Ein Auszug aus der vom Strategieteam entwickelten ';
echo '<a href="anforderungen.php">';
echo 'Anforderungsliste</a><br>und die entsprechenden Antworten aller Kandidaten.</li>'; $erg++;}
echo '<li>Der erstmals 2005 erstellte, h&ouml;chst subjektive und jetzt von Hermann aktualisierte und erg&auml;nzte <a href="fragen.php">';
echo 'Fragenkatalog</a><br>Hier kannst du deine eigenen Vorstellungen zum Vereinsleben mit denen der Kandidaten vergleichen.</li>';
echo '</ul>';
if ($erg < 2) echo '<p class="text2n">Ansonsten liegen von '.$kand['vorname'].' keine erg&auml;nzenden Informationen vor.</p>';
/*
if ($kand['amt1']) {$erg = 0;
for ($i=1;$i<18;$i++) {$erg = $erg + $kand["r$i"];}
if ($erg) {
echo '<h3>Ressort-Pr&auml;ferenzen</h3><p class="text1n">Im Falle meiner Wahl w&uuml;rde ich mich bevorzugt f&uuml;r die folgenden Vorstandsressorts interessieren:<ul>';
for ($i=1;$i<18;$i++) {
	if ($kand["r$i"]) echo '<li class="text1n">'.db_result(mysqli_query($link,'SELECT ressort FROM ressortswahl WHERE id='.$i),0,'ressort').'</li>';
} echo '</ul></p>';
} else {echo '<p class="text1n">'.$kand['vorname'].' hat keine Ressortpr&auml;ferenzen angegeben';}
}*/
echo '</ul><span class="text2b">Wir glauben, dass du dir mit diesen Informationen im Vergleich zu den Vorjahren ein besseres Bild von den Kandidaten machen kannst und w&uuml;nschen dir eine erfolgreiche Wahlentscheidung.</span></td></tr>';

/*
echo '<tr><td colspan="2" class="nurunten text1b backp">';
echo 'weiteres?';
echo '</td></tr>';//</table></td></tr>';


echo '<tr style="margin:0; padding:0;"><td colspan="2" valign="top" align="left" style="margin:0; padding:0; background-color: white;">'; //Menuleiste
echo '<br><table width="100%" border="0" style="margin:0; padding:0;"><tr><td><h2>Weiter:</h2></td>';

echo '<td class="text1b fa55">Weitere Einzelseiten:<br>Klick oben auf das Foto<br>des Kandidaten!</td>';
echo '<td align="center"><a class="menu" style="width:100px;" href="kandidaten.php" target="MinD"><span class=" fa55 center">Kandidaten-&Uuml;bersicht</span></a></td>';
echo '<td align="center"><a class="menu" style="width:100px;" href="ressorts.php" target="MinD"><span class=" fa55 center">Ressort&uuml;bersicht</span></a></td>';
echo '<td align="center"><a class="menu" style="width:100px;" href="fragen.php" target="MinD"><span class=" fa55 center">Fragen & Antworten</span></a></td></tr>';

echo '</table></td></tr>';
*/
$spalten = 7; weiter(1,1,1,1,1);

echo '</table></td></tr>';



$schwanztext = '... in alphabetischer Reihenfolge XXXgeht noch nicht!XXX';
$weiter = "";//"n&auml;chste/r Kandidat/in ...";
include ("schwanz.php");
?>
