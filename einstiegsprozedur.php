<?php // Ermittlung der M-Nummer, Erzeugung Zufalls-Schlüssel, Einrichtung Datensätze
// Variante ohne Einladungsliste

$Mnr = '04912113'; //substr($_SERVER['REMOTE_USER'],3);// die übergebene M-Nummer
$Mnr = $_SERVER['REMOTE_USER']; // die übergebene M-Nummer





$filter = 'SELECT schl,MNr FROM '.$adressfile.' where MNr = "'.$Mnr.'"';
$result = mysqli_query($link,$filter); 
if (!mysqli_num_rows($result)) {//Diese MNr gibt es in der DB noch nicht: Anlegen
	srand((double)microtime()*100000000000);
	$key = rand(10000000000,89999999999);

$eintrag = 'INSERT INTO '.$adressfile.' SET MNr="'.$Mnr.'", schl="'.$key.'", ersteintrag="'.$datum.'"';
		$aendern = mysqli_query($link,$eintrag);
		if ($dbzeigen) echo '<br>'.$aendern . $eintrag.'<br>';
		$eintrag = 'INSERT INTO '.$antwortenfile.' SET schl="'.$key.'", ersteintrag="'.$datum.'"';
		$aendern = mysqli_query($link,$eintrag);
		if ($dbzeigen) echo '<br>'.$aendern . $eintrag.'<br>';
} else {
		$eintrag = 'UPDATE '.$adressfile.' SET letzteintrag="'.$datum.'" WHERE MNr="'.$Mnr.'"';
		$aendern = mysqli_query($link,$eintrag); 
		if ($dbzeigen) echo '<br>'.$aendern . $eintrag.'<br>';
}
$result = mysqli_query($link,$filter); 
$row1 = mysqli_fetch_array($result); //Datensatz zu dieser MNr. aufrufen
$key = $row1['schl'];
//echo $key;
?>