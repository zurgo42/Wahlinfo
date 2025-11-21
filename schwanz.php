<?php
echo '</table></td></tr>';
if (laenge($weiter)) {echo '<tr>';
echo '<td valign="bottom" align="';
if ($mobil<2) {echo 'right';} else {echo 'center';}
echo '" style="padding-top: 25px; padding-right: 10px;">';
echo '<input class="button red" type="submit" name="weiter" value="'.$weiter.'">';
echo '<span class="text1n"><br><br>'.$schwanztext;
echo '</form><br><br></tr>';} else {echo '<tr><td>';}
include ("disclaimer.php");
echo '</td></tr></table></center></body></html>';
?>