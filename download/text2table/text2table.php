<HTML><HEAD><TITLE>text2table.php</TITLE></HEAD><BODY>
<H1>text2table.php</H1>

<?php
$fn = @$_FILES["fl"]["tmp_name"];
if (is_uploaded_file($fn) !== TRUE) {
	print <<<eoo
<FORM enctype="multipart/form-data" method=POST>
<P>
<INPUT type=file name=fl><INPUT type=submit>
</P>
</FORM>

<HR>
</BODY></HTML>
eoo;
	return;
}

print "<TABLE border>\r\n";
$fp = fopen($fn, "r") or die("fopen failed.");
while (($s = fgets($fp, 65536)) !== FALSE) {
	$list = split("\t", ereg_replace("[\r\n]", "", $s));
	print "<TR>";
	foreach ($list as $val)
		print "<TD>".htmlspecialchars($val)."</TD>";
	print "</TR>\r\n";
}
fclose($fp);
?>
</TABLE>
<HR>
</BODY></HTML>
