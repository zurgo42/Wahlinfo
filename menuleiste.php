<?php
//Menuleiste in der Einzeldarstellung
 echo '<tr style="margin: 0px; padding: 0; "><td valign="top" align="left" style="margin: 0; padding:0; background-color: white;"><br>'; //Kandidatenleiste
 $maxanzahl = 5-($mobil>1)*3; // mehr als diese nicht in eine Zeile
 $umbruch = 5+3*$privat-($mobil>1); // wenn mehr als $maxanzahl, dann wird in diesen Schritten der Kandidenkopf umgebrochen
 $bild = 50+($mobil>1)*30; // Bildgröße
 $ks = mysqli_query($link,'SELECT vorname, name,mnummer,bildfile,amt1,amt2,amt3,amt4,amt5 FROM '.$kandidatendb.' ORDER BY amt1 DESC, amt2 DESC, amt3 DESC, amt4 DESC, amt5 DESC, name, vorname');
 $sp = mysqli_num_rows($ks); $wsp = 90/$sp;
 echo '<table width="100%" style="margin: 0; padding:0; border: 1px solid #E8E8E8;"><tr>'; 
 $j=0; // steuert den Umbruch
 for ($i=0;$i<$sp;$i++) {
	$j=$j+1; $knd = mysqli_fetch_array($ks);
	echo '<td align="center" valign="top" style="border-left: 1px solid #E8E8E8; padding: 10px;"><a href="einzeln.php?key='.$linkerg.'&zeige='.$knd['mnummer'].'">';
	if (laenge($knd['bildfile'])) {	
		echo '<IMG SRC="'.$bildquelle.'img/'.$knd['bildfile'].'" width="'.$bild.'">';}
		else {echo '<IMG SRC="leer.jpg" width="'.$bild.'">';}
	echo '</a></td>';
	echo '<td align="left" width="100" valign="top" style="border-right: 1px solid #E8E8E8; padding-top: 10px;"><a href="einzeln.php?key='.$linkerg.'&zeige='.$knd['mnummer'].'"><span class="text3n">'.umlaute($knd['vorname']).'<br>'.umlaute($knd['name']); echo '<br>';
	echo '('.welchesamt($knd).')';
	echo '</span></a></td>';
	if ($j>=$maxanzahl) {echo '</tr><tr>';$j=0;}
 } 
 echo '</tr></table>';
 echo '</td></tr>';
?>