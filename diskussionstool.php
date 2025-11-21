<?php 
// Dieser Teil ist die individuelle Einstiegsseite in die jeweilige Diskussion.
// Die spezifischen Daten für das eigentliche Skript stehen im Programmteil ***daten.php
// Die eigentliche Arbeit leistet diskussion.php 
// Für eine neue Diskussion muss man nur diesen Teil und die Datei ***daten.php anpassen

include("../fstandard.php"); // Die Standardfunktionen und -initialisierungen
include("textewahl.php"); // Die spezifischen Daten für diese Umfrage
include("header.php"); // Der einheitliche Header für alle Seiten


//Der Eingangstext für diese spezielle Diskussion
echo '<b>Dies ist eine Plattform zur Diskussion &uuml;ber Vereinsthemen:<br><br></b></span><h3><center>Wahl '.$jahr.'</center></h3>';
echo '<span class="text2">'.umlaute('Vielen Ms erschien es in der Vergangenheit unbefriedigend, dass man die zur Wahl stehenden Kandidaten kaum kannte. <br>
Um diese Situation zu verbessern, gibt es diese - <b><u>nicht</u> vom Wahlausschuss organisierte</b> - Seiten mit weiteren Informationen.');
echo '<br><br></span></td></tr><form action="index.php" method="post">';
echo '<tr><td colspan="5"><span class="text2"><center><input type="submit"  class="button" value="Zur Vorstellung der Kandidaten"></center></span></form></td></tr></table></td></tr>';

// echo <center><h2>Vorstellung der Kandidaten: </h2> <b><a href=https://aktive.mensa.de/wahl>aktive.mensa.de/wahl</a></b></center>;
echo '<span class="text2">'.umlaute('Hier im Diskussionstool kann man den Kandidaten konkrete Fragen stellen und die Kandidaten können dazu Stellung nehmen. <br>
<b>Das beiderseits natürlich auf freiwilliger Basis. </b>
<br>Wir werden allerdings darauf achten, dass die Regeln der Höflichkeit eingehalten werden - nicht zur Sache Gehörendes bis hin zu etwaigen Hass-Postings werden gelöscht, sobald wir sie bemerkt haben.');

echo '<br><br></span></td></tr><form action="diskussion.php" method="post">';
echo '<tr><td colspan="5"><span class="text2"><center><input type="submit"  class="button" value="Zur Diskussionsplattform"></center></span></form></td></tr></table></td></tr>';

echo umlaute('<b>Diese Diskussion ist <u>nicht anonym</u>.<br> Wenn du dich aktiv an der Diskussion beteiligst, erscheinen zu deinem Beitrag dein Vorname, Name und die Mitgliedsnummer.<br><br>');
//echo umlaute('<b>Eine Bitte: </b>Anders als bei den vereinsinternen Umfragen ist diese Diskussion nicht anonym. Wenn du dich aktiv an der Diskussion beteiligen willst, trage bitte beim ersten Beitrag deinen Namen ein. Er wird dann mit der beim Einloggen verwendeten M-Nummer verknüpft und automatisch in allen deinen Beiträgen angezeigt.<br><br>');
echo umlaute('Euer Admin
<br>Werner Kelnhofer<br>#12113<br><br>');

include ("disclaimer.php"); 
/*
if ((time() > dzut($kandidatenzeigen_ab)) OR ($admin)) {include ("diskfooter.php");} 
else {echo umlaute('<font color = "red"><br><br><b>Die Diskussion wird eröffnet, sobald der Wahlausschuss die Kandidaten bekanntgegeben hat und sie hier eingetragen werden konnten.</font>');include ("disclaimer.php");}
*/
echo '</table>';
?>
</body>
</html>