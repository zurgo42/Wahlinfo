<?php // Erste Seite Vorstandsfrageboten
 $dieseseite = "ressort";
 $daten = 'textewahl';
 $naechsteseite = "anforderungen";

 include ("kopf.php");		// Den Aufbau und die wesentlichen Funktionen laden

 //$ks = mysqli_query($link,'SELECT * FROM '.$kandidatendb.' WHERE amt1=1 ORDER BY name, vorname'); //dies nur für Vorstandskandidaten zeigen
 //include("zeitsteuerung.php"); // Einstellen, ob das schon/noch/wem gezeigt werden soll
 $ks = mysqli_query($link,'SELECT * FROM '.$kandidatendb.' WHERE amt1=1 OR amt2=1 OR amt3=1 ORDER BY amt1,amt2,amt3,name, vorname'); //Vorstandskandidaten zeigen
 $spielwiese = ubeide('spielwiese');

 echo '<tr><td valign="top" align="left">';
//hackermeldung(...
 if (iget('spielwiese')) $kandidatendb='spielwiesewahl';

echo '<h1>Die Ressort-Pr&auml;ferenzen der Vorstandskandidaten</h1>';
 echo '<p class="text1b">Mit Inkrafttreten der neuen, in 2019 beschlossenen Satzung wird der Vorstand in drei Vorstandsbereichen gew&auml;lt. Die Kandidaten k&ouml;nnen hier ihre Pr&auml;ferenzen angeben, welches Ressorts sie bevorzugen w&uuml;rden - und warum. ';
 if ($spielwiese) {
	 echo '<h3>Ressorts/Arbeitsgebiete - dies ist die "Spielwiesen-Version", die echten Kandidaten kommen sp&auml;ter</h3><br>';} 
	else {
		if (time()<dzut($editieren_bis)) { //Ab jetzt stehen die Kandidaten fest - die dürfen noch nicht angezeigt werden!
		 echo '<p class="text1b">Der Zugriff auf die Angaben der Kandidaten ist erst nach dem '.$editieren_bis.' m&ouml;glich.';
		 echo '<br><br><a href="index.php">Zur&uuml;ck zur &Uuml;bersichtsseite mit den Kandidaten</a><br><br></p>';
		 $weiter=""; include ("schwanz.php"); die;}
	 }
 echo '<p class="text3b">F&uuml;r die Ressorts in der MinD Stiftung (Testbetrieb, KiJu-Veranstaltungen, Bildung und Wissenschaft und Forschung) gibt es keine festgelegte Zuordnung.<br>Nach der Wahl wird der Vorstand eine entsprechende Ressortverteilung vornehmen. Es ist schon wichtig, dass die wesentlichen Ressorts im Vorstand gut vertreten sind. Die Pr&auml;ferenzen der Kandidaten k&ouml;nnten also f&uuml;r die Wahlentscheidung Bedeutung haben.</p>';
 
 echo '</td></tr>';
// Ämter durchgehen - nur Vorstandskandidaten
 for ($amtv=1;$amtv<4;$amtv++) { // die drei Vorstandsbereiche; die Ressorts stehen in textewahl
	echo '<tr><td><table><tr><td colspan="2" rowspan="2">';
	echo '<h3>'.$rs[$amtv*30].'</h3>'; // So heißt der Vorstandsbereich
	 echo '<span class="text4n">'; // Skala darstellen
	 for ($j=1;$j<5;$j++) {
		echo $skala5[$j];
		echo ' = '.$skala5r[$j].'; ';
		if ($j==3) echo '<br>';
		} 
	 $j=5; echo $skala5[$j].' = '.$skala5r[$j]; 

	 echo '<br><br>Wenn du die hell get&ouml;nten Felder mit der Maus ber&uuml;hrst, siehst du die Kommentare vollst&auml;ndig.';
	 echo '<br>Die Einzelseiten der Kandidaten bekommst du durch Klick auf das jeweilige Foto.';	
	 echo '</span></td>';

	$ks = mysqli_query($link,'SELECT * FROM '.$kandidatendb.' WHERE amt'.$amtv.'=1 ORDER BY name, vorname'); //Vorstandskandidaten zeigen
	$kanz = mysqli_num_rows($ks);
	for ($i=0;$i<$kanz;$i++) { //Kandidaten zeigen
		$knd[$i] = mysqli_fetch_array($ks); // Datensatz je Kandidat
		$offset = -$i*20;
		echo '<td align="center" valign="top" class="st"><a href="einzeln.php?key='.$key.$linkerg.'&zeige='.$knd[$i]['mnummer'].'"><IMG SRC="'.$bildquelle.'img/'.$knd[$i]['bildfile'].'" width="45"></a><br></td>';
		} 
	echo '</tr><tr>';
	for ($i=0;$i<$kanz;$i++) {//$knd[$i] = mysqli_fetch_array($ks); // Datensatz je Kandidat
		echo '<td class="st"><span class="text3n">'.umlaute($knd[$i]['vorname']).'<br>'.umlaute($knd[$i]['name']);
		echo '</span></td>';
		} 
	echo '<td align="center" valign="bottom" class="text2b center"><center>je<br>Ressort</center></td>';
	echo '</tr>';

	// Die einzelnen Ressorts durchgehen
	$av0=$amtv*30+1;
	for ($av=$av0;$av<=$rnr[$amtv*30];$av++) { 
	 $ress = "r".$rnr[$av];
	 echo '<tr><td align="center" class="text2b st">'.$av.'</td><td align="left" class="text2b st">'.$rs[$av].'</td>';//Name des Ressorts
	$q=$q1=$q2=$q3=0;
	for ($i=0;$i<$kanz;$i++) { // die Kandidaten nacheinanander durchgehen
		$k=0; $b=$btext=""; 
		if ($knd[$i][$ress]>0) {
			$k = round($knd[$i][$ress]/10000,0); $b=$knd[$i][$ress]-$k*10000; 
			if ($k==3) $q++; if ($k==4) $q1++; if ($k==5) $q2++; if ($k>2) $q3++; 
			}
If ($b) {$btext = db_result(mysqli_query($link,'SELECT bem FROM bemerkungenwahl WHERE id='.$b),0,'bem');}
skala5($knd[$i][$ress],$skala5,1,1);
} 

$farbe = "fa51"; if ($q3<2) $farbe = "fa57"; if ($q3<1) $farbe = "fa55"; // letzte Spalte
echo '<td class="st" align="center"><span class="text2b center '.$farbe.'">';
echo "$q2&nbsp;++<br>$q1&nbsp;&nbsp;&nbsp;+<br>$q&nbsp;&nbsp;&nbsp;&plusmn;</span></td>";
echo '</tr>';
} // das Ende der Ressortliste
echo '</table></td></tr>';
}// Ende Schleife Vorstgandsbereiche
 weiter(1,1,0,1,0) ;//weiter(1,1,0,1,1);

echo '</table></td></tr>';

$weiter = ""; //"weiter ...";
include ("schwanz.php");
?>
