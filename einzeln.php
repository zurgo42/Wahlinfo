<?php // Erste Seite Vorstandsfrageboten: Einzeldarstellung eines Kandidaten
 $dieseseite = "einzeln";
 $daten = 'textewahl'; // Hier stehen etliche für mehrere Seiten zu verwendende Texte
 $naechsteseite = "einzeln";
 $bildquelle = "../neuwahl/";

 include ("kopf.php"); // Seitenaufbau, Funktionen nur für diese Programmfamilie, Initialisierungen
 if (!ubeide('ohnemenu') AND ($mobil<2)) include("menuleiste.php"); // Kandidatenleiste im Kopf
// Den Kandidaten aufrufen
 $zeige = ubeide('zeige'); 
 $amt = ubeide('amt'); //Zu zeigender Kandidat (MNr) und sein Amt
 
 $ks = 'SELECT * FROM '.$kandidatendb.' WHERE mnummer="'.$zeige.'"'; // In der Kandidatenliste nachschauen
 $kx = mysqli_query($link,$ks); 
 if (!$ks) abbruch('Das M mit dieser Mitgliedsnummer kandidiert nicht.');

 $kand = mysqli_fetch_array($kx); // Die Daten dieses Kandidaten
 $w0 = 140+($mobil>1)*100; $w1 = $w0+10; // Steuerung der Breite für Smartphone-Seiten


// Linke Spalte bzw. Wahlausschuss-Seite
 echo '<tr><td colspan="2"><table><tr><td colspan="2" valign="top" align="left" class="nurunten">';
 //if (!$privat) {echo '<h3 style="padding-top: 20px;">Der offizielle Kandidatentext';
 if ($spielwiese) echo '<span class="text3b rot">Dies ist die "Spielwiesen-Version", die echten Kandidaten kommen sp&auml;ter</span>';
 //echo '</h3>';}
 echo '<div style="float: left; width: '.$w1.'px; margin: 10 10 10 0;">';// Foto-Box
 //echo '<IMG SRC=" '.$bildquelle.'img/'.$kand['bildfile'].'" width="'.$w0.'">';
 if (laenge($kand['bildfile'])) {echo '<IMG SRC="'.$bildquelle.'img/'.$kand['bildfile'].'" width="'.$w0.'">';} else {echo '<IMG SRC="leer.jpg" width="'.$w0.'">';}
 echo '</div>';

 echo '<div style="padding-left: 1px; padding-top: 10px;"><h1>'.umlaute($kand['vorname']).' '.umlaute($kand['name']).'</h1><span class="text1n">MNr: '.substr($kand['mnummer'],3);
 echo '<span class="text1b"><br><br>Kandidatur f&uuml;r '; $q=0;
 echo welchesamt($kand);

 /*if ($privat) {echo '<br><br><br><a href="einzeln.php?ohnemenu=1&zeige='.$zeige;
 if ($spielwiese) echo '&spielwiese=1';
 echo '" target="Kandidat">Link zum offiziellen Kandidatentext<br>(erscheint in einem neuen Fenster)</a>';
 } else {
 echo '<br><br></span></div>';
 echo '<div style="clear: both;"><span class="text1n">'.umlaute($kand['text']);
 echo '<br><br>';}*/
 
 echo '</span></div>';

 $einzelkand = 'key='.$key.$linkerg.'&zeige='.$kand['mnummer'];
 //if ($privat) { // Die rechte Spalte in der Strategieteam-Ansicht

 $erg = 0; // Hiermit werden die eingegebenen Elemente gezählt
 echo '<td width="55%" class="nurlinks backp" style="vertical-align: top;"><h3>Erg&auml;nzende Informationen</h3><span class="text2n">Soweit hier Informationen der Kandidierenden verlinkt sind, sind sie nicht Teil der offziellen Wahl-Ank&uuml;ndigung des Vereins.';
 //if (time() < dzut($editieren_bis)) echo '<span class="text3n fa55"><br><br>Hinweis: Dieser Teil wird erst nach Beendigung der Editierfrist ('.$editieren_bis.') gezeigt, nachdem alle Kandidaten Gelegenheit hatten, ihre Informationen einzugeben!<br></span>';	
 echo '</span><p class="text1n">';

// Vor Ende der Editierfrist sollen das nur die Kandidaten sehen und zwar nur deren eigene Seite

 if (!iget('spielwiese') AND ((time()<dzut($editieren_bis)) AND ($kand['mnummer'] <> $MNr))) { // nach Ende der Editierfrist
	echo '<p class="text1b">Ab Ende der Editierfrist, also nach dem '.$editieren_bis.' sind hier zu allen Kandidaten weitere Informationen abrufbar.</p>';
 } else {
	if ((time()<dzut($editieren_bis)) AND ($kand['mnummer'] == $MNr)) echo '<b>Nur du siehst hier derzeit deine eigenen Angaben!</b><br>';
	if (laenge($kand['hplink'])) {echo 'Der <a href="'.$kand['hplink'].'">Link auf die Homepage/Mediaseite</a> von '.$kand['vorname'].'</li>'; $erg++;}
	if (laenge($kand['videolink'])) {echo '<br>Der <a href="'.$kand['videolink'].'">Link auf das Vorstellungsvideo</a> von '.$kand['vorname'].'</li>';  $erg++;}

 if (laenge($kand["team1"]) > 3) { // Die Mitkandidaten-Präferenzen des Gezeigten zeigen
	echo '<br><br><b>Am liebsten w&uuml;rde '.$kand['vorname'].' mit folgenden Mitkandidaten zusammenarbeiten:</b><br>';
	for ($i=1;$i<6;$i++) {$t = $kand["team$i"];
		if (laenge($t)>2) {
		$team1 = mysqli_query($link,'SELECT name, vorname FROM '.$kandidatendb.' WHERE mnummer="'.$t.'"');
		echo db_result($team1,0,'vorname').' '.db_result($team1,0,'name').'<br>';}
	}
 }

 // Alle diejenigen zeigen, die diesen Kandidaten präferieren
 $team2= mysqli_query($link,'SELECT vorname, name FROM '.$kandidatendb.' WHERE (team1="'.$kand['mnummer'].'" OR team2="'.$kand['mnummer'].'" OR team3="'.$kand['mnummer'].'" OR team4="'.$kand['mnummer'].'" OR team5="'.$kand['mnummer'].'") ORDER BY name');
 $tanz = mysqli_num_rows($team2);
 if ($tanz) {
 echo '<br><b>'.$kand['vorname'].' wird von folgenden Kandidaten pr&auml;feriert:</b><br>';
 for ($i=0;$i<$tanz;$i++) {
	echo db_result($team2,$i,'vorname').' '.db_result($team2,$i,'name').'<br>';}
 }


 $anf1 = $anf2 = $r = 0; 	
 for ($i=1;$i<9;$i++) {$anf1 = $anf1 + ($kand["a$i"]>999); } // Erster Teil Anforderungen
 for ($i=10;$i<17;$i++) {$anf2 = $anf2 + ($kand["a$i"]>9999); } // Zweiter Teil Anforderungen
 for ($i=1;$i<18;$i++) {$r = $r + ($kand["r$i"]>9999);} // Ressortwünsche ausgefüllt

 // Haben die die Vorstandskandidaten welche Angaben gemacht?
 if (($kand['amt1'] OR $kand['amt2'] OR $kand['amt3']) AND (!$r)) { 
	echo '<b> <p class="text1n"><br>'.$kand['vorname'].' hat auf Anfrage keine bevorzugten Aufgaben/Ressortzust&auml;ndigkeiten eingetragen.</p></b>';}
//if (($kand['amt1'] OR $kand['amt2'] OR $kand['amt3']) AND (!$a)) {
	//echo '<b> <p class="text1n"><br>Auf die Fragen des Strategieteams zu Zeit, Motivation und Erfahrungen gingen von '.$kand['vorname'].' keine Antworten ein.</p></b>';}
	echo '</td></tr>';

if (($kand['amt1'] OR $kand['amt2'] OR $kand['amt3']) AND ($r>0)) {
	echo '<tr><td colspan=3" class="backp"><h3>Ressort-Pr&auml;ferenzen</h3><p class="text1n">Im Falle meiner Wahl w&uuml;rde ich mich wie folgt f&uuml;r die folgenden Vorstandsressorts interessieren <br>(Prio <b>5</b> ist h&ouml;chste Priorit&auml;t):<ul>';
	$r = 0;
	for ($i=1;$i<4;$i++) {// Die Vorstandsbereiche
		if ($kand["amt$i"]) {
		$amtv=$i*30;
		echo '<h3>'.$rs[$amtv].'</h3><ul>';
		// Die einzelnen Ressorts durchgehen
		$av0=$amtv+1;
		for ($av=$av0;$av<=$rnr[$amtv];$av++) { 
		$ress = "r".$rnr[$av];
	
		//if ($kand[$ress] > 9000) { 
			$r++;
			echo '<li class="text1n"><b>'.$rs[$av];
		$k = round($kand[$ress]/10000,0); 
		$b=$kand[$ress]-$k*10000;
		$k = MAX($k,1);
		echo ' mit Prio '.$k.'</b>';
		if ($b) {$btext = db_result(mysqli_query($link,'SELECT bem FROM bemerkungenwahl WHERE id='.$b),0,'bem');
		echo '<br>'.$btext;}
		echo '</li>';//}
		 	 

	} // Ende Schleife Ressorts
	
	echo '</ul>';
	}
	} // Ende Vorstandsbereiche
	echo '</p>';
	if (!$r) echo '<p class="text2b">Es liegen von '.$kand['vorname'].' keine entsprechenden Angaben vor.</p>';
	} // Ende nur Vorstand

	echo '</td></tr>';




 //if ($a) { // Es liegen Angaben zu den Anforderungen vor
	echo '<tr><td colspan="3" valign="top" align="left" class="backp" style="margin-bottom:50px;">';
	//echo '<span class="text1b">Zeit, Motivation<br></span>';
	echo '<span class="text1b">Es gibt einige Anforderungen, die f&uuml;r die ehrenamtliche Arbeit im Verein hilfreich sein k&ouml;nnten. Einige davon wurden den Kandidaten vorgelegt.<br>Das k&ouml;nnte das Bild aus den Vorstellungstexten etwas abrunden und den W&auml;hlerinnen und W&auml;hlern ihre Entscheidung erleichtern.<br><br></span>';
	
	echo '<table width="99%">';
	$erg=0;
	echo '<tr><td colspan="4"><span class="text1b"><b>Allgemeine Fragen:<br></span></td></tr>';
	$rs = mysqli_query($link,'SELECT * FROM anforderungenwahl ORDER BY Nr');
	$ranz = mysqli_num_rows($rs);
	for ($j=0;$j<8;$j++) {$j1 = $j+1; $j0=$j-7; $rsx = mysqli_fetch_array($rs);
		//$bem = db_result(mysqli_query($link,'SELECT bem FROM bemerkungenwahl WHERE id='.$kand["a$j1"]),0,'bem');
		echo '<tr><td align="center" class="text4n rh">'.$j1.'</td><td class="rh"><span class="text2b">'.$rsx['Anforderung'].'</td>';

		//echo '<tr><td align="center" class="text4n rh">'.$j1.'</td><td class="rh"><span class="text2b">'.umlaute(db_result($rs,$j,'Anforderung')).'</td>';
		$bem = ""; if ($kand["a$j1"]) {$erg++; $bem = db_result(mysqli_query($link,'SELECT bem FROM bemerkungenwahl WHERE id='.$kand["a$j1"]),0,'bem');}
		echo '<td colspan="2" align="center" class="text2n rh"><span class="text1n">'.$bem.'</td></tr>';
	}

	if ($erg<1) { echo '<tr><td colspan="4"><span class="text2b"><br><b>Von '.$kand['vorname'].' liegen hierzu keine Antworten vor.<br><br></span></td></tr>';}
	$erg = 0;
	echo '<tr><td><br></td></tr>';
  

 if (($kand['amt1'] OR $kand['amt2'] OR $kand['amt3'])) {// Kompetenzen - nur bei den Vorstandskandidaten relevant
	echo '<tr><td colspan="2"><span class="text1b">Je nach Ressortzust&auml;ndigkeit sind f&uuml;r  Vorstandsmitglieder bestimmte Kompetenzen und Erfahrungen wichtig. Wir haben danach gefragt';
//	if ($a1) {
	echo ' - hier die Antworten:<br><br></span><span class="text1b"><b>Kompetenzen/Erfahrungen:<br></span></span></td>';
	echo '<td colspan="2"><span class="text4n"><br>';
	$eingabe = 0; echo 'Die Skala:<br>';
	for ($j=1;$j<5;$j++) {
		if ($eingabe) {echo '<b>'.$j.'</b>';} else {echo $skala5[$j];}
		echo ' = '.$skala5a[$j].'; ';
		if ($j==3) echo '<br>';
		} 
	$j=5; if ($eingabe) {
		echo '<b>'.$j.'</b>';
		} else {
		echo $skala5[$j];} echo ' = '.$skala5a[$j]; 
		if ($eingabe) {echo '<br><b>Trage deine Bewertung ein; wenn du magst, kannst du sie kurz begr&uuml;nden.</b>';
		$ks = mysqli_query($link,'SELECT * FROM '.$kandidatendb.' WHERE mnummer="'.$MNr.'"'); //nur dieser Kandidat
		}
	echo '</td></tr>';

	for ($j=8;$j<15;$j++) {$j1=$j+1; $rsx = mysqli_fetch_array($rs);
		echo '<tr><td align="center" class="text4n rh">'.$j1.'</td>';
		echo '<td class="rh"><span class="text2b">'.umlaute($rsx['Anforderung']).'</td>';
		echo '<td align="center" class="text2n rh"><span class="text1n center">';
		if ($kand["a$j1"]>0) {
			$erg++; $k = round($kand["a$j1"]/10000,0); $b=$kand["a$j1"]-$k*10000;
			echo $skala5[$k].'</td>';
			if ($b) {
				$btext = db_result(mysqli_query($link,'SELECT bem FROM bemerkungenwahl WHERE id='.$b),0,'bem');
				echo '<td align="left" class="text2n rh">'.$btext.'</td>';}
			echo '</tr>';
		} else {
		echo ' </td><td> </td>';}
	}
  if ($erg<1) {echo '<tr><td colspan="4"><span class="text2b"><br><b>Von '.$kand['vorname'].' liegen hierzu keine Antworten vor.<br><br></span></td></tr>';}

	echo '<tr><td><br></td></tr>';

 }



 echo '</table></td></tr>';
 //echo $MNr.$kand['mnummer'];
 if (($MNr == $kand['mnummer']) AND (time()<dzut($editieren_bis))) { // Angebot Bearbeiten während der Editierfrist
 echo '<tr><td colspan="4" class="rh"><a href="eingabe.php?key='.$key.$linkerg.'&zeige='.$MNr.'"><span class="text2b">Wenn du noch etwas &auml;ndern/bearbeiten willst, klicke hier</span></a>.</td></tr>';} 


 } // Ende Spielwiese

 if (!ubeide('ohnemenu')) { // Nur die Kandidatenseite soll gezeigt werden
 $spalten = 7; 
 weiter(0,1,0,0,0);
 } else {weiter(1,1,1,1,0);}
 echo '</table></td></tr>';



 $schwanztext = '... in alphabetischer Reihenfolge XXXgeht noch nicht!XXX';
 $weiter = "";//"n&auml;chste/r Kandidat/in ...";
 include ("schwanz.php");
?>
