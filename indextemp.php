<?php // Einstiegsseite
$daten = 'textewahl'; // in dem File texteXXX.php stehen die für die jeweilige Umfrage erforderlichen Daten und Texte
$naechsteseite = "index"; // bei mehreren Seiten steht hier jeweils die als nächste aufzurufende
$i0=$i8=0; // Das sind bei Verwendung einer DB für die Fragentexte die auf der Folgeseite zu lesenden Fragen
$privat=1;
include ("kopf.php"); // Der Kopf und die Formatierung
//include("einstiegsprozedur.php"); // Nur für die erste Seite
$MNr = $_SERVER['REMOTE_USER'];
$key = schluessel($MNr,"");


echo '<tr><td valign="top" align="left"><h1>Willkommen ...</h1><h2>... zur Wahl-Information 2016.</h2></td>';
if ($mobil < 2) {echo '<td class="text4r" valign="top"><br><a class="text4n" href="'.$einstiegsseite.'?mobil=3"><span class="text4r">Smartphone-<br>Darstellung</span></a><br></td>';} else {echo '<td class="text4r" valign="top"><br><a class="text4n" href="'.$einstiegsseite.'?mobil=1"><span class="text4r">Desktop-<br>Darstellung</span></a><br></td>';}
echo '</tr>';
echo '<tr><td colspan="2" class="text1n"><b>Liebes MitM,</b> <br><br>';
echo umlaute('geht es dir auch so?<br>Es ist Wahl und es gibt Kandidaten, das ist schon mal gut. Aber eigentlich kennen wir die meisten Bewerber nicht.<br>Man weiss nicht, wofür sie stehen, was sie vorhaben und was sie zu wesentlichen Vereinsthemen meinen.<br><br>
Die Kandidatentexte geben da auch nicht immer vollständig Auskunft. Und bisher erfuhren wir Ms meist erst recht kurzfristig, wer zur Wahl steht; da war es kaum möglich, mal bei anderen nachzufragen.<br>Kein Wunder, wenn viele Ms nicht gewählt haben.<br><br>
Aus dem Strategieteam gab es Anregungen, das zu verbessern, aber einigen Aktiven ging das zu weit - man befürchtete, darunter könne die Chancengleichheit der Kandidaten leiden und es bestehe die Gefahr einer Beeinflussung der Wähler.<br><br>
Deshalb gibt es einerseits die "offizielle" Wahlankündigung des Wahlausschusses (<a href="http://wahl.mensa.de"><span class="text1n">wahl.mensa.de</span></a>) mit Name, Foto und Text der Kandidierenden und andererseits bzw. ergänzend auf den hier folgenden Seiten eine "Privatinititive": Unabhängig von Vorstand und Vereinsgremien haben wir Fragen formuliert und Seiten gebastelt, mit deren Hilfe jedM sich ein eigenes, vertiefendes Bild von den Kandidaten machen kann. <br>
Der Vorteil einer solchen "Privatinitiative": Sowas muss man dann nicht langwierig abstimmen und glattschleifen lassen, die Fragen dürfen subjektiv sein, ihre Auswahl auch, und man darf vielleicht auch mal etwas polarisierend fragen.<br>
Aber wir meinen, Ms können selber denken und entscheiden ...<br><br>
So danken wir dem Vorstand für seine freundliche Unterstützung vor allem dafür, dass wir zur einfachen Sicherstellung des Datenschutzes diesen Webspace auf dem Mensa-Server nutzen dürfen, dem Wahlausschuss für den Link hierher und erst recht den Kandidierenden selbst, die sich auf das Spiel eingelassen und ihre Antworten eingetragen haben.<br><br>
Und wünschen uns, dass du mit Hilfe dieser Initiative - früher und besser informiert - überlegter entscheiden kannst.<br><br>
Auf den folgenden Seiten findest du<br>
<ul><li>Die ');
echo '<a href="kandidaten.php?key='.$key.$linkerg.'">';
echo umlaute('Liste der Kandidaten</a> - so wie vom Wahlausschuss übermittelt mit den Links auf die Texte und Fotos</a>, wie beim Wahlausschuss eingereicht. Hinzu kommen - "inoffiziell" Links auf etwaige weitere, von den Kandidaten zugefügte Informationen (z.B. deren eigene Homepage, Blog etc.).</li>');
echo umlaute('<li>Bei den Vorstandskandidaten gibt es eine Übersicht über deren ');
echo '<a href="ressorts.php?key='.$key.$linkerg.'">';
echo umlaute('Ressort-Präferenzen</a>, damit man sieht, ob diesmal alle Ressorts gut abgedeckt werden können, und es gibt Elemente aus dem vom Strategieteam entwickelten ');
echo '<a href="anforderungen.php?key='.$key.$linkerg.'">';
echo umlaute('Anforderungsprofil</a> und die entsprechenden Selbsteinschätzungen.</li>');
echo umlaute('<li>Zu allen Kandidierenden gibt es außerdem eine ');
echo '<a href="fragen.php?key='.$key.$linkerg.'">';
echo umlaute('Liste der Antworten</a> auf diverse Fragen, die wir allen Kandidaten zu Vereinsthemen gestellt haben:<br>Wenn du diese Fragen auch selbst beantwortest, bekommst du eine Vergleichstabelle, aus der du leicht ersehen kannst, wie gut deine Meinung zu den Antworten der Kandidaten passt. </li>
</ul>
<b>Zuviel Information?</b><br>Mag sein. Aber du selbst wei&szlig;t am besten, was du für deine Wahlentscheidung wissen möchtest - <b><i>du hast die Wahl.</i></b><br>Eine durchdachte Entscheidung zu ermöglichen, war unser Anliegen.
<br><br>
Viel Spaß beim Lesen, Ausfüllen und Bewerten!
<br><br>');
//echo $unter;
echo '<font color="red"><br>Wer unterschreibt hier (mit)? Eigentlich war es ja eine Initiative des Strategieteams insgesamt. Aber es gab halt auch das Argumgent, es sei besser, wenn es nicht von einem "offiziellen" Vereinsgremium kommt.</font><br>';
echo umlaute('<p class="text2n"><b>PS:</b><br>Damit du deine Antworten variieren und ggf. später nochmal anschauen kannst, werden sie unter einer individuellen langen Zufallszahl gespeichert. In einer separaten Tabelle wird diese Zufallszahl mit deiner Mitgliedsnummer verknüpft.<br>Die Zufallszahl in der Adressdatei kannst Du nach dem Ausfüllen des Fragebogens sofort löschen lassen. Dann allerdings wirst Du Deinen Eintrag nicht nochmal aufrufen können. <br>
Mit Beendigung der Wahl werden die Zufallszahlen in der Adressdatei gelöscht, so dass die Einträge zuverlässig anonymisiert bleiben. Es können anschließend (über diesen Link) alle Teilnehmenden die Auswertung aller Antworten abrufen.<br>
</p>
</td></tr>');

$weiter = "Zur &Uuml;bersichtsseite";
echo '</table></td></tr>';
echo '<tr class="rahmen">';
echo '<td valign="bottom" align="right" style="padding-top: 25px; padding-right: 10px;">';


if ($key) {echo '<input class="button red" type="submit" value="'.$weiter.'">';}
else {echo '<p class="text3b">Unter dieser Mitgliedsnummer wurde bereits ein Fragebogen ausgef&uuml;llt und die Verkn&uuml;pfung gel&ouml;scht.<br>Ein Editieren ist daher nicht mehr m&ouml;glich.<br></p>';}
echo '<br><br></form></td></tr>';
include ("disclaimer.php");
echo '</table>';
//echo '</table>';
?>        
</body>
</html>

