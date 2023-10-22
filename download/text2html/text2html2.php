<HTML><HEAD><TITLE>text2html.php</TITLE></HEAD><BODY>
<H1>text2html.php</H1>

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

$fp = fopen($fn, "r") or die("fopen failed.");
while (($s = fgets($fp, 65536)) !== FALSE) {
	$s = ereg_replace("[\r\n]", "", $s);
	print "<P>".htmlspecialchars(htmlspecialchars($s))."</P>\r\n";
}
fclose($fp);
?>
<HR>
</BODY></HTML>
