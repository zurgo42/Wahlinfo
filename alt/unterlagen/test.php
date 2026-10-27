<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
    <link rel="icon" href="M1.ico" type="image/x-icon">
    <link rel="stylesheet" type="text/css" href="../umfrage.css">
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <meta charset="UTF-8">
    <meta name="Author" content="JW @ Wahlausschuss, Mensa in Deutschland e.V.">
    <meta name="robots" content="nofollow">
    <meta name="description" content="Wahlinfo-Seite von Mensa in Deutschland e.V.">

</head>

<body>
<table>
    <?php
    echo '<tr class="rahmen" style="border-top: 1px #0a1f6e solid;"><td>
<div id="header"><div id="headerBG"';
    if ($privat==1) echo ' style="background: #336600;"';
    if ($privat==2) echo ' style="background: #0a1f6e;"';
    echo '><div id="headerR1">';
    if (!$privat) echo '<div id="headerR2">';
    echo '<div id="headerR3">';
    if ($privat<2) echo '<div id="headerTITEL">';
    if ($privat == 2) echo '<div id="headerTITEL" style="margin:0; padding: 0; top:8px; left: 10px; background-image:url('."fotohq.jpg".');">';
    echo '<div id="headerSLOGAN">';
    echo $slogan;
    if ($privat==1) echo '<br>(Strategieteam-Seite)';
    if ($privat==2) echo '<br>(private Initiative)';
    echo '</div></div></div></div></div></div></div></td></tr>';

    echo "Hallo !";

    ?>
</table>
</body>

