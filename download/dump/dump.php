<HTML><HEAD><TITLE>dump.php</TITLE></HEAD><BODY>
<H1>dump.php</H1>

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
print "<PRE>";
$addr = 0;
while (($c = fgetc($fp)) !== FALSE) {
	if (($addr & 0xf) == 0)
		printf("%08x :", $addr);
	printf(" %02x", ord($c));
	if (($addr & 0xf) == 0xf)
		print "\n";
	if (($addr & 0xff) == 0xff)
		print "\n";
	$addr++;
}
fclose($fp);
?>
</PRE>
<HR>
</BODY></HTML>
