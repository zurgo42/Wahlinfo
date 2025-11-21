<?php
die("Mailversand deaktiviert");


// Versand von Mails an die Kandidaten - Einfachstversion Einbinden in index.php



$text = 'mit dem Versenden des Wahlaufrufs ist die Wahl nun in die aktive Phase gegangen.<br>
Sehr viele M-Wähler haben sich schon im Vorfeld die Kandidatentexte und die ergänzenden Informationen angeschaut, bzw. auf der Diskussionsseite selbst Fragen gestellt oder mitdiskutiert. Erfahrungsgemäß wird das Interesse noch steigen, wenn die Unterlagen im Briefkasten liegen.<br><br>
Die Adresse:  https://wahl.mensa.de<br><br>
Wer das nicht ohnehin regelmäßig tut, ist gut beraten, mal im Diskussionstool nachzuschauen, ob es dort noch Unbeantwortetes gibt. Das gilt auch für die Kandidaten, die für die eMail-Benachrichtigung optiert haben - die weist nämlich nur auf die direkten Beiträge hin, nicht auf "Fragen an alle Kandidaten".<br>
Und im M-Wahl-O-Mat wurden zwischenzeitlich drei weitere Themen angesprochen. Wer das noch nicht gesehen hat, möge mal wieder reinschauen und kann gern noch Antworten nachtragen.<br><br>
Ich jedenfalls bin sehr froh, wie konstruktiv und weit überwiegend sachlich die Vor-Wahl-Diskussion bislang gelaufen ist und hoffe, dass das so bleibt und dass wir alle mit einer guten Wahlbeteiligung belohnt werden.<br>
';

$text .= "\n\nViele Grüße\n|-|€®µ@ññ";
//$kandidatendb = 'spielwiesewahl';
$ks = mysqli_query($link,'SELECT
kandidatenwahl.vorname,
kandidatenwahl.email,
antwortenwahl.anz,
kandidatenwahl.mw
FROM
antwortenwahl
INNER JOIN kandidatenwahl ON kandidatenwahl.schl = antwortenwahl.schl
');
//WHERE antwortenwahl.anz IS NULL OR antwortenwahl.anz < 5'); // Alle Kandidaten ohne oder mit weniger als 5 Antworten

$kanz = mysqli_num_rows($ks); $k=0;
while ($k<$kanz) { 
$email = db_result($ks,$k,'email'); //echo $kanz.$email;
if (laenge($email)) {
$anrede = 'Liebe'; if (db_result($ks,$k,'mw') == "m") $anrede .="r";
$anrede .= ' '.db_result($ks,$k,'vorname').",\n\n";
//echo "<br> ".umlaute($anrede.$text); $email = 'dr.hm@gmx.de'; if ($k>0) die; // Test
multipartmail ('MinD-Wahl',$anrede.$text,$email,1,'mensa@zurgo.de');
$k++;
}
}

?>