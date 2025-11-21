<?php // Erste Seite 
 $dieseseite = "index";
 $daten = 'textewahl'; // Hier stehen alle Daten zur konkreten Wahl
 $naechsteseite = "index";
 include ("kopf.php"); // Die Einstiegsseite mit Funktionen etc.

 $key = schluessel($MNr,"");
 $privat=1;


// Hauptteil Ergänzende Wahlinformation
 echo '<tr><td valign="top" align="left" style="padding: 8 8 0 5;">';
  echo '<h2><br>'.$title.': Erg&auml;nzende Wahlinformation</h2>';
 // if ((time() < dzut($editieren_bis)) AND (!$spielwiese)) echo '<h1><font color="#FF0000">Die Kandidierenden haben nun bis zum 18.02.2025 Zeit ihre Profil-Angaben zu editieren <br>(Klick nach eigener M-Nummer)</font color="#FF0000"></h1></td>';


 if ((time() < dzut($editieren_bis)) AND (!$spielwiese)) echo '<h1><font color="#FF0000">Die Kandidierenden haben nun bis zum '.$editieren_bis.' Zeit ihre Profil-Angaben zu editieren <br>(Klick nach eigener M-Nummer)</font color="#FF0000"></h1></td>';




  // Umschaltung Browser - Desktop/Mobil
  echo '<td valign="top" align="center" width="10%" class="hell" style="padding: 8 5 5 5;">';
  $q = 1; $qs = 'Desktop'; 
  if ($mobil < 2) {$q = 3; $qs = 'mobile&nbsp;Browser';}
  echo '<span class="text3n style="color: #b9b8bf;">Zur&nbsp;Version&nbsp;f&uuml;r<br><a href="index.php?mobil='.$q.'"><span class="text3n style="color: #b9b8bf;">'.$qs.'</span></a><br>wechseln.</span></td>';

  echo '</tr>';

// Kandidaten zeigen
 echo '<tr><td colspan="3" class="rh hell rechtsohne linksohne"><h1>Hier kann man diskutieren mit:</h1>';
 
echo '<span class="text2n">(Ausgenommen jene Kandidaten, die sich gegen eine Teilnahme an der Diskussion ausgesprochen haben.)<br><br></span>';
 
 if ($spielwiese) {
	 echo '<h3>Dies ist die "Spielwiesen-Version", die echten Kandidaten kommen, sobald der Wahlausschuss das freigegeben hat.</h3>';
	}
 if (time() > dzut($editieren_bis)) {
	 
	 echo '<span class="text2n">Klicke auf das jeweilige Foto, um einzelne Kandidatenseiten zu sehen!</span>';
	 } //else {
	 //echo '<span class="text2b">Ab dem '.$editieren_bis.' k&ouml;nnen hier erg&auml;nzende Angaben zu den einzelne Kandidaten abgerufen werden!</span>';}
 if (!$key) {
	 echo $row['schl'].'<p class="text1b">Leider gibt es diesen Schl&uuml;ssel nicht!<br><br><br>'; include("disclaimer.php"); sleep(10); 
	 die("<br>Der Admin sagt dir: So hast du keine Chance, per brute force eine Kennung zu finden ...<br><br>");}
 echo '</td></tr>';


 $tx = mysqli_query($link,'SELECT * FROM aemterwahl ORDER BY id');
 $ks0 = 'SELECT vorname, name, mnummer,email,bildfile,text,amt1,amt2,amt3,amt4,amt5,ja,nein,enth FROM '.$kandidatendb;
 echo '<tr><td colspan="3"><table width="100%">';
 for ($i=1;$i<=$aemter;$i++) { $anzpos = db_result($tx,$i,'anzpos');
	$ks =  mysqli_query($link,$ks0.' WHERE amt'.$i.'=1 ORDER BY name ASC'); // Die Kandidaten in diesem Amt
	$kanz = mysqli_num_rows($ks); // Anzahl Datensätze in diesem Amt
	
	$cols = 6-2*($mobil>1);
	if ($kanz) {echo '<tr><td align="left" colspan="'.$cols.'"><br><h1><u>'.$rs[$i*30].' ';
	if ($mobil>1) echo '<br>';
	echo '('.$anzpos.' Position';
	if ($anzpos>1) echo 'en';
	echo '):</u></h1></td>';}

	$spalte=0;
	$breite = 75+100*($mobil>1);
	for ($k=0;$k<$kanz;$k++) { // Die einzelnen Kandidaten werden dargestellt
		$spalte++;
		$kx = mysqli_fetch_array($ks);
		if ($spalte==1) echo '<tr>'; 
		echo '<td class="rh rechtsohne text1b" style="min-height: 110px; text-align: right;"><a href="einzeln.php?key='.$key.$linkerg.'&zeige='.$kx['mnummer'].'&amt='.$i.'">';
		if (laenge($kx['bildfile'])) {
			echo '<IMG SRC="'.$bildquelle.'img/'.$kx['bildfile'].'" width="'.$breite.'">';} else {echo '<IMG SRC="leer.jpg" width="'.$breite.'">';}
		echo '</a></td><td class="rh linksohne" style="min-width: 150px;"><a href="einzeln.php?key='.$key.$linkerg.'&zeige='.$kx['mnummer'].'&amt='.$i.'"><span class="text1">'.umlaute($kx['vorname']).'<br>'.umlaute($kx['name']).'</a><p class="text2">M-Nr. '.substr($kx['mnummer'],3).'</p>';
 
		//$ja = db_result($kx,$j,'ja');
		//if ($ja) {$nein = db_result($kx,$j,'nein');$enth = db_result($kx,$j,'enth');$diff=$ja-$nein;
		//  echo '<br><br>'.$ja.' Ja<br>'.$nein.' Nein<br>'.$enth.' Enth.<br><b>Differenz = '.$diff.'</b>';}
		//  echo '<td class="nurlinks nurunten text1b"><a href="einzeln.php?key='.$key.$linkerg.'&zeige='.db_result($kx,$j,'mnummer').'&amt='.$i.'"><IMG SRC="img/'.db_result($kx,$j,'bildfile').'" width="80"></a></td><td class="nurunten text2n"><a href="einzeln.php?key='.$key.$linkerg.'&zeige='.db_result($kx,$j,'mnummer').'&amt='.$i.'"><b>'.umlaute(db_result($kx,$j,'vorname')).' '.umlaute(db_result($kx,$j,'name')).'</span></a><br></b>M-Nr. '.substr(db_result($kx,$j,'mnummer'),3);
		
		
 
		
		
		
		if ((time()<dzut($editieren_bis)) AND ($kx['mnummer'] == $MNr)) {
			echo '<br><br><br><span class="text2 fa51">Individuelle Nachricht&nbsp;f&uuml;r&nbsp;M-Nr.&nbsp;'.substr($MNr,3).':<br><a href="eingabe.php';
			if ($spielwiese) {echo '?spielwiese=1';}
			//if ($wahlausschuss==42781) echo '?wahlausschuss=42781'; 
			echo '">Hier kommst du zu deiner Eingabeseite<br>f&uuml;r die Erg&auml;nzenden Informationen.</a></span>';} 
		// <br>Wie das dann aussehen k&ouml;nnte, siehst du auf der <a href="index.php?privat=1&spielwiese=1">Spielwiese
		echo '</td>';
		if (($spalte==4) OR (($mobil>1) AND ($spalte/2 == round($spalte/2)))) {$spalte=0; echo '</tr>';}
		} // Ende Schleife Kandidaten
	//  else {echo '<td class="rh rechtsohne"> </td><td class="rh linksohne"> </td>';}
	} // Ende Schleife Ämter 
 echo '</table></td></tr>'; 
 //echo "<br>k $k j $j"; 
 //$j++; }

 $spalten=6; 
 if ($privat) {weiter(1,0,1,1,0);} else {weiter(1,0,1,1,0);}


 if ($MNr == '04912113xxx') { //Admin-Sektor
 echo '<tr><td colspan="6">';
 echo 'XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX';
 include('inc_mailversand.php');  // Dort steht der Text
 echo '</td></tr>';
 }
 echo '</table></td></tr>';




 $weiter = ""; //"weiter ...";
 include ("schwanz.php");
?>
