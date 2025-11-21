<?php // Eingabeseite für die Kandidaten
$daten = 'textewahl'; // in dem File texteXXX.php stehen die für die jeweilige Umfrage erforderlichen Daten und Texte
$naechsteseite = "kandeingabe"; // bei mehreren Seiten steht hier jeweils die als n�chste aufzurufende

include ("kopf.php"); // Der Kopf und die Formatierung
include("einstiegsprozedur.php"); // Nur für die erste Seite
include ("starten.php"); // Dateninitialisierung und Start

// Prüfung, ob der Aufrufende Kandidat ist.
$kschl = mysqli_query($link,'SELECT adressenwahl16.schl, adressenwahl16.MNr
FROM adressenwahl16
INNER JOIN kandidatenwahl ON adressenwahl16.MNr = kandidatenwahl.mnummer');
if (!mysqli_num_rows($kschl)) die("Auf diese Seite d&uuml;rfen nur Kandidaten zugreifen!");
$mnummer = db_result($kschl,0,'MNr');
// Daten des Kandidaten aufrufen
$tx = mysqli_query($link,'SELECT * FROM aemterwahl ORDER BY id');
$kx = mysqli_query($link,'SELECT * FROM kandidatenwahl WHERE mnummer="'.$mnummer.'"');
$kand = mysqli_fetch_array($kx);


echo '<tr><td colspan="2" valign="top" align="left"><h1>Eingabeseite ';
echo $kand['mnummer'].' '.$kand['vorname'].' '.$kand['name'].'</h1></td></tr>';

echo '<tr><td colspan="2" class="text1b fa55">Hier kommen die Eingabemasken für <br>f
Bild hochladen, Text, Links auf ein Video oder andere externe Quellen, Ressortwunsch,<br>die Anforderungsliste<br>und der Link auf die Eingabeseite für die Fragen.';

echo '</td></tr>';

echo '<tr><td colspan="2" class="fa55"><br><br><br>XXXSpeichern funktioniert noch nichtXXX</td></tr>';


 
$weiter = "Absenden ...";
echo '</table></td></tr>';
echo '<tr class="rahmen">';
echo '<td valign="bottom" align="right" style="padding-top: 25px; padding-right: 10px;">';

echo '<input class="button red" type="submit" value="'.$weiter.'">';

echo '<br><br></form></td></tr>';
include ("disclaimer.php");
echo '</table>';
//echo '</table>';
?>        
</body>
</html>

