<?php
$version = "hexpatch.php 180213";

if (!is_uploaded_file($fn = @$_FILES["f0"]["tmp_name"])) {
	print <<<EOO
<HTML><HEAD><TITLE>{$version}</TITLE></HEAD><BODY>
<H1>{$version}</H1>

<FORM enctype="multipart/form-data" method=POST>
<UL>
	<LI>source .hex file: <INPUT type=file name=f0>
	<LI>address: 0x<INPUT type=text name=a0 size=5> data: 0x<INPUT type=text name=d0 size=3>
	<LI>address: 0x<INPUT type=text name=a1 size=5> data: 0x<INPUT type=text name=d1 size=3>
	<LI>address: 0x<INPUT type=text name=a2 size=5> data: 0x<INPUT type=text name=d2 size=3>
	<LI>address: 0x<INPUT type=text name=a3 size=5> data: 0x<INPUT type=text name=d3 size=3>
	<LI>address: 0x<INPUT type=text name=a4 size=5> data: 0x<INPUT type=text name=d4 size=3>
	<LI>address: 0x<INPUT type=text name=a5 size=5> data: 0x<INPUT type=text name=d5 size=3>
	<LI>address: 0x<INPUT type=text name=a6 size=5> data: 0x<INPUT type=text name=d6 size=3>
	<LI>address: 0x<INPUT type=text name=a7 size=5> data: 0x<INPUT type=text name=d7 size=3>
	<LI><LABEL><INPUT type=checkbox name=download value=1 checked>download</LABEL> <INPUT type=submit>
</UL>
</FORM>

<HR>
</BODY></HTML>
EOO;
	return;
}

if (eregi('([-_0-9A-Za-z]+)\.[-_.0-9A-Za-z]+$', @$_FILES["f0"]["name"]."", $a))
	$outputfn = $a[1];
else
	$outputfn = date("Ymd_His");

$patchlist = array();
for ($i=0; $i<8; $i++) {
	if (($a = @$_POST["a{$i}"]."") == "")
		continue;
	if (($d = @$_POST["d{$i}"]."") == "")
		continue;
	$patchlist[("0x".$a) - 0] = ("0x".$d) - 0;
}

$output = "";
foreach (split("\r|\n|\r\n", file_get_contents($fn)) as $line) {
	if (strlen($line = trim($line)) < 11)
		continue;
	if (substr($line, $pos = 0, 1) != ":")
		continue;
	$sum= 0;
	$sum += $len = ("0x".substr($line, $pos += 1, 2)) - 0;
	if ($len > 0x20)
		die("<H1>format error: len({$len})</H1>");
	$sum += $addrh = ("0x".substr($line, $pos += 2, 2)) - 0;
	$sum += $addrl = ("0x".substr($line, $pos += 2, 2)) - 0;
	$sum += $type = ("0x".substr($line, $pos += 2, 2)) - 0;
	if ($type != 0) {
		$output .= $line."\r\n";
		continue;
	}
	$output .= sprintf(":%02X%02X%02X%02X", $len, $addrh, $addrl, $type);
	$addr = ($addrh << 8) | $addrl;
	for ($i=0; $i<$len; $i++) {
		$data = ("0x".substr($line, $pos += 2, 2)) - 0;
		if (($val = @$patchlist[$addr]) !== null)
			$data = $val;
		$output .= sprintf("%02X", $data);
		$sum += $data;
		$addr++;
	}
	$output .= sprintf("%02X\r\n", (0x10000 - $sum) & 0xff);
}

foreach ($patchlist as $addr => $data)
	$outputfn .= sprintf("_%02xon%04x", $data, $addr);

header("Content-Type: text/plain");
if ((@$_POST["download"] - 0) == 1)
	header('Content-Disposition: attachment; filename="'.$outputfn.'.hex"');
header("Content-Length: ".strlen($output));
print $output;

?>