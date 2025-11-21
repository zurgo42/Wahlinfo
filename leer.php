<?php // Leerseite
$daten = 'textewahl'; // in dem File texteXXX.php stehen die f�r die jeweilige Umfrage erforderlichen Daten und Texte
$naechsteseite = "kandidaten"; // bei mehreren Seiten steht hier jeweils die als n�chste aufzurufende
$dbzeigen = 0;
include ("kopf.php"); // Der Kopf und die Formatierung
include("einstiegsprozedur.php"); // Nur für die erste Seite
include ("starten.php"); // Dateninitialisierung und Start
echo '<br><br>leere Seite/Stellvertreterseite<br><br>';
include('disclaimer.php');
?>