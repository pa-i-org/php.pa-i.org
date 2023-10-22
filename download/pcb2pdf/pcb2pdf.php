<?php
$version = "pcb2pdf 130923";

$zoom = 1;

class	systeminfo {
	var	$pdffontname = "KozMinPro-Regular-Acro";
	var	$pdflicense = "";
	var	$fontsize = 2;
	var	$op_body;
	var	$cl_body = "<HR></BODY></HTML>";
	var	$defaultwidth = 0.2;
	function	systeminfo() {
		global	$version;
		$this->op_body = "<HTML><HEAD><TITLE>{$version}</TITLE></HEAD><BODY><H1>{$version}</H1>";
	}
}
$systeminfo =& new systeminfo();
include("env.php");

$fn = @$_FILES["pcb"]["tmp_name"];
if (is_uploaded_file($fn) !== TRUE) {
	print $systeminfo->op_body;
	print <<<EOO

<FORM enctype="multipart/form-data" method=POST>
<INPUT type=file name=pcb>
<SELECT name=type size=0>
	<OPTION value=0 selected>duplex</OPTION>
	<OPTION value=10>outline only</OPTION>
	<OPTION value=11>outline with length(L10)</OPTION>
	<OPTION value=12>outline with length(for FAX)</OPTION>
	<OPTION value=13>outline with length(L10x10)</OPTION>
	<OPTION value=14>outline with length(L10x100)</OPTION>
	<OPTION value=15>pattern only</OPTION>
	<OPTION value=20>folded</OPTION>
	<OPTION value=21>fold - silk and pattern</OPTION>
	<OPTION value=100>[DXF]outline</OPTION>
</SELECT>
<INPUT type=submit>
</P>

<P>compare:
<INPUT type=file name=pcb2>
<SELECT name=typec size=0>
	<OPTION value=0 selected>pattern-duplex</OPTION>
	<OPTION value=1>pattern-folded</OPTION>
	<OPTION value=10>silk-duplex</OPTION>
	<OPTION value=11>silk-folded</OPTION>
	<OPTION value=20>outline</OPTION>
</SELECT>
<INPUT type=submit>
</P>

<P>zoom: 
<SELECT name=zoom size=0>
	<OPTION value=0.5>1/2</OPTION>
	<OPTION value=0.75>3/4</OPTION>
	<OPTION value=1 selected>1</OPTION>
	<OPTION value=1.5>1.5</OPTION>
	<OPTION value=2>2</OPTION>
	<OPTION value=2.5>2.5</OPTION>
	<OPTION value=3>3</OPTION>
	<OPTION value=4>4</OPTION>
	<OPTION value=5>5</OPTION>
	<OPTION value=6>6</OPTION>
	<OPTION value=7>7</OPTION>
	<OPTION value=8>8</OPTION>
	<OPTION value=9>9</OPTION>
	<OPTION value=10>10</OPTION>
</SELECT>
</P>
</FORM>

EOO;
	print $systeminfo->cl_body;
	return;
}


class	parts {
	function	parts() {
	}
	function	min_x() {
		die("parts::min_x() called.");
	}
	function	max_x() {
		die("parts::max_x() called.");
	}
	function	min_y() {
		die("parts::min_y() called.");
	}
	function	max_y() {
		die("parts::max_y() called.");
	}
	function	draw() {
		die("parts::draw() called.");
	}
}


class	land extends parts {
	var	$r;
	var	$x, $y;
	function	land($size, $x, $y) {
		global	$zoom;
		
		$this->r = $size / 2.0 * $zoom;
		$this->x = ($x + 0.0) * $zoom;
		$this->y = ($y + 0.0) * $zoom;
	}
	function	min_x() {
		return $this->x - $this->r;
	}
	function	max_x() {
		return $this->x + $this->r;
	}
	function	min_y() {
		return $this->y - $this->r;
	}
	function	max_y() {
		return $this->y + $this->r;
	}
	function	draw($pid) {
		pdf_circle($pid, $this->x, $this->y, $this->r);
	}
	function	drawdxf($z = 0, $layer = "0") {
		return <<<EOO
  0
CIRCLE
  8
{$layer}
  10
{$this->x}
  20
{$this->y}
  30
{$z}
  40
{$this->r}

EOO;
	}
}


class	line extends parts {
	var	$r;
	var	$sx, $sy;
	var	$ex, $ey;
	var	$v;
	function	line($size, $sx, $sy, $ex, $ey) {
		global	$zoom;
		
		$this->r = $size / 2.0 * $zoom;;
		$this->sx = ($sx + 0.0) * $zoom;
		$this->sy = ($sy + 0.0) * $zoom;
		$this->ex = ($ex + 0.0) * $zoom;
		$this->ey = ($ey + 0.0) * $zoom;
		$this->v = (pow(2, 0.5) - 1.0) * 4 / 3;
	}
	function	min_x() {
		return min($this->sx, $this->ex) - $this->r;
	}
	function	max_x() {
		return max($this->sx, $this->ex) + $this->r;
	}
	function	min_y() {
		return min($this->sy, $this->ey) - $this->r;
	}
	function	max_y() {
		return max($this->sy, $this->ey) + $this->r;
	}
	function	draw($pid) {
		$sx = $this->sx;
		$sy = $this->sy;
		$ex = $this->ex;
		$ey = $this->ey;
		$rx = $ex - $sx;
		$ry = $ey - $sy;
		$len = pow(pow($rx, 2) + pow($ry, 2), 0.5);
		if ($len < 0.1) {
			$rx = 0;
			$ry = $this->r;
		} else {
			$rx *= $this->r / $len;
			$ry *= $this->r / $len;
		}
		$vx = $rx * $this->v;
		$vy = $ry * $this->v;
		pdf_moveto($pid, $sx - $ry, $sy + $rx);
		pdf_curveto($pid, $sx - $ry - $vx, $sy + $rx - $vy, $sx - $rx - $vy, $sy - $ry + $vx, $sx - $rx, $sy - $ry);
		pdf_curveto($pid, $sx - $rx + $vy, $sy - $ry - $vx, $sx + $ry - $vx, $sy - $rx - $vy, $sx + $ry, $sy - $rx);
		pdf_lineto($pid, $ex + $ry, $ey - $rx);
		pdf_curveto($pid, $ex + $ry + $vx, $ey - $rx + $vy, $ex + $rx + $vy, $ey + $ry - $vx, $ex + $rx, $ey + $ry);
		pdf_curveto($pid, $ex + $rx - $vy, $ey + $ry + $vx, $ex - $ry + $vx, $ey + $rx + $vy, $ex - $ry, $ey + $rx);
		pdf_closepath($pid);
	}
	function	drawdxf($z = 0, $layer = "0") {
		return <<<EOO
  0
LINE
  8
{$layer}
  10
{$this->sx}
  20
{$this->sy}
  30
{$z}
  11
{$this->ex}
  21
{$this->ey}
  31
{$z}

EOO;
	}
}


class	partslist {
	var	$list;
	var	$pos = 0;
	function	partslist() {
		$this->list = array();
	}
	function	add(&$parts) {
		$this->list[] =& $parts;
	}
	function	count() {
		return count($this->list);
	}
	function	rewind() {
		$this->pos = 0;
	}
	function	&get() {
		if ($this->pos < count($this->list))
			return $this->list[$this->pos++];
		$null = null;
		return $null;
	}
	function	min_x() {
		$ret = null;
		for ($i=0; $i<count($this->list); $i++) {
			if (($val = $this->list[$i]->min_x()) === null)
				continue;
			if (($ret > $val)||($ret === null))
				$ret = $val;
		}
		return $ret;
	}
	function	max_x() {
		$ret = null;
		for ($i=0; $i<count($this->list); $i++) {
			if (($val = $this->list[$i]->max_x()) === null)
				continue;
			if (($ret < $val)||($ret === null))
				$ret = $val;
		}
		return $ret;
	}
	function	min_y() {
		$ret = null;
		for ($i=0; $i<count($this->list); $i++) {
			if (($val = $this->list[$i]->min_y()) === null)
				continue;
			if (($ret > $val)||($ret === null))
				$ret = $val;
		}
		return $ret;
	}
	function	max_y() {
		$ret = null;
		for ($i=0; $i<count($this->list); $i++) {
			if (($val = $this->list[$i]->max_y()) === null)
				continue;
			if (($ret < $val)||($ret === null))
				$ret = $val;
		}
		return $ret;
	}
	function	draw($pdf) {
		for ($i=0; $i<count($this->list); $i++)
			$this->list[$i]->draw($pdf);
	}
	function	drawdxf($z = 0, $layer = "0") {
		$s = "";
		for ($i=0; $i<count($this->list); $i++)
			$s .= $this->list[$i]->drawdxf($z, $layer);
		return $s;
	}
}


#
# main
#

function	mm2pnt($val) {
	return $val / 25.4 * 72;
}


function	&readpartslist($fn = "")
{
	$ap = array();
	for ($i=0; $i<18; $i++)
		$ap[$i] = $i * 0.05 + 0.1;
	for ($i=18; $i<256; $i++)
		$ap[$i] = $i * 0.1 - 0.8;
	$sq = $ap;
	
	$le =& new partslist();
	for ($i=0; $i<64; $i++)
		$le->add(new partslist());
	
	$fp = fopen($fn, "r") or die("fopen failed.");
	$mode = "";
	while (($line = fgets($fp, 65536)) !== FALSE) {
		$line = trim($line);
		if (ereg('^\[(.+)\]$', $line, $array))
			$mode = $array[1];
		
		if (count($array = explode(":", $line)) <= 1)
			continue;
		$cmd = $array[0];
		
		$array = explode(",", $array[1]);
		$arg = array();
		foreach ($array as $val) {
			$array0 = explode("=", $val);
			$arg[$array0[0]] = @$array0[1]."";
		}
		
		switch ($mode) {
			case	"FlshDef":
				if (ereg('^[0-9]+$', $cmd))
					$sq[$cmd + 0] = $array[0] + 0;
				break;
			case	"LineDef":
				if (ereg('^[0-9]+$', $cmd))
					$ap[$cmd + 0] = $array[0] + 0;
				break;
			case	"DrawItems":
				switch ($cmd) {
					case	"LINE":
						if (($val = @$arg["AP"]) !== null)
							$le->list[$arg["LE"] + 0]->add(new line($ap[$val + 0], $arg["XS"], $arg["YS"], $arg["XE"], $arg["YE"]));
						break;
					case	"LAND":
						if (($val = @$arg["AP"]) !== null)
							$le->list[$arg["LE"] + 0]->add(new land($ap[$val + 0], $arg["X"], $arg["Y"]));
						break;
				}
				break;
		}
	}
	fclose($fp);
	
	return $le;
}

if (($zoom = @$_REQUEST["zoom"] + 0) <= 0)
	$zoom = 1;

$le =& readpartslist($fn);

$left = $le->min_x();
$top = $le->max_y();
$right = $le->max_x();
$bottom = $le->min_y();

$type = @$_REQUEST["type"] + 0;
$typec = @$_REQUEST["typec"] + 0;

if ($type == 12) {
	$systeminfo->fontsize *= 2;
	$systeminfo->defaultwidth = 0.8;
	
	foreach (array(3 => 0.2, 7 => $systeminfo->defaultwidth, 10 => 0.2) as $layer => $width) {
		$le->list[$layer]->rewind();
		for (;;) {
			$p =& $le->list[$layer]->get();
			if ($p === null)
				break;
			$p->r = $width / 2.0;
		}
	}
}

$le2 = null;
$fn2 = @$_FILES["pcb2"]["tmp_name"];
if (is_uploaded_file($fn2) === TRUE) {
	$le2 =& readpartslist($fn2);
	$left = min($left, $le2->min_x());
	$top = max($top, $le2->max_y());
	$right = max($right, $le2->max_x());
	$bottom = min($bottom, $le2->min_y());
	switch ($typec) {
		default:
		case	0:
			$type = 0;		# duplex
			break;
		case	1:
			$type = 20;		# folded
			break;
		case	10:
			$type = 0;		# duplex
			break;
		case	11:
			$type = 20;		# folded
			break;
		case	20:
			$type = 10;		# single
			break;
	}
}

switch ($type) {
	case	100:
		$content = <<<EOO
  0
SECTION
  2
ENTITIES

EOO;
		$content .= $le->list[3]->drawdxf(0, "1");
		$content .= $le->list[7]->drawdxf(0);
		$content .= $le->list[8]->drawdxf(0);
		$content .= <<<EOO
  0
ENDSEC
  0
EOF

EOO;
		header("Content-Type: application/octet-stream");
		header('Content-Disposition: attachment; filename="pcb2pdf.dxf"');
		header("Content-Length: ".strlen($content));
#header("Content-Type: text/plain");
		print $content;
		die();
}

$width = $right - $left + 6;
$height = $top - $bottom + 6;
$papersizelist = array(420, 297, 210, 148);
$paperwidth = $width;
$paperheight = $height;

if ($width > $height) {
	for ($i=0; $i<count($papersizelist)-1; $i++) {
		if ($width > $papersizelist[$i])
			break;
		if ($height > $papersizelist[$i + 1])
			break;
		$paperwidth = $papersizelist[$i];
		$paperheight = $papersizelist[$i + 1];
	}
} else {
	for ($i=0; $i<count($papersizelist)-1; $i++) {
		if ($height > $papersizelist[$i])
			break;
		if ($width > $papersizelist[$i + 1])
			break;
		$paperheight = $papersizelist[$i];
		$paperwidth = $papersizelist[$i + 1];
	}
}


$font = null;

function	drawstring($pid, $x = 0, $y = 0, $s = "", $draw = 1)
{
	global	$systeminfo;
	global	$font;
	
	if ($draw == 0)
		return $systeminfo->fontsize * strlen($s) / 2;
#		return pdf_stringwidth($pid, mb_convert_encoding($s, "UCS-2", "EUC-JP"), $font, $systeminfo->fontsize);
	pdf_set_text_pos($pid, $x, $y);
	while ($s != "") {
		$c = substr($s, 0, 1);
		$s = substr($s, 1);
		if ((ord($c) & 0x80)) {
			$c .= substr($s, 0, 1);
			$s = substr($s, 1);
			$font = pdf_findfont($pid, $systeminfo->pdffontname, "UniJIS-UCS2-HW-H", 0) or die("pdf_findfont failed.");
			pdf_setfont($pid, $font, $systeminfo->fontsize);
			pdf_show($pid, mb_convert_encoding($c, "UCS-2", "EUC-JP"));
		} else {
			$font = pdf_findfont($pid, "Courier", "winansi", 0) or die("pdf_findfont failed.");
			pdf_setfont($pid, $font, $systeminfo->fontsize);
			pdf_show($pid, $c);
		}
	}
	return 0;
}


$pid = pdf_new() or die("pdf_new failed.");
if (strlen($systeminfo->pdflicense) > 0)
	pdf_set_parameter($pid, "license", $systeminfo->pdflicense);
pdf_open_file($pid, "");


$foldh = $foldv = 1;
switch ($type) {
	case	20:
	case	21:
		if ($paperwidth < $paperheight)
			$foldh = 2;
		else
			$foldv = 2;
		break;
}

pdf_begin_page($pid, mm2pnt($paperwidth * $foldh), mm2pnt($paperheight * $foldv));
$page = 0;

for (;;) {
	if ($page == 0)
		pdf_setmatrix($pid, mm2pnt(1), 0, 0, mm2pnt(1), -mm2pnt($left + $right - $paperwidth) / 2, -mm2pnt($top + $bottom - $paperheight) / 2);
	else if ($foldv <= 1)
		pdf_setmatrix($pid, mm2pnt(-1), 0, 0, mm2pnt(1), mm2pnt($left + $right + $paperwidth * ($foldh * 2 - 1)) / 2, -mm2pnt($top + $bottom - $paperheight * ($foldv * 2 - 1)) / 2);
	else
		pdf_setmatrix($pid, mm2pnt(1), 0, 0, mm2pnt(-1), -mm2pnt($left + $right - $paperwidth * ($foldh * 2 - 1)) / 2, mm2pnt($top + $bottom + $paperheight * ($foldv * 2 - 1)) / 2);
	
	if ($le2 !== null) {
		if ($typec == 20) {
			if ($le->list[3 + $page]->count() > 0) {
				pdf_setcolor($pid, "fill", "rgb", 1, 0, 0, 0);
				$le->list[3 + $page]->draw($pid);
				pdf_fill($pid);
			}
			if ($le2->list[3 + $page]->count() > 0) {
				pdf_save($pid);
				$le2->list[3 + $page]->draw($pid);
				pdf_clip($pid);
				pdf_setcolor($pid, "fill", "rgb", 0, 0, 1, 0);
				pdf_rect($pid, $left, $bottom, $right - $left, $top - $bottom);
				pdf_fill($pid);
				if ($le->list[3 + $page]->count() > 0) {
					pdf_setcolor($pid, "fill", "rgb", 1, 1, 0, 0);
					$le->list[3 + $page]->draw($pid);
					pdf_fill($pid);
				}
				pdf_restore($pid);
			}
			if ($le->list[7 + $page]->count() > 0) {
				pdf_setcolor($pid, "fill", "rgb", 1, 0, 0, 0);
				$le->list[7 + $page]->draw($pid);
				pdf_fill($pid);
			}
			if ($le2->list[7 + $page]->count() > 0) {
				pdf_save($pid);
				$le2->list[7 + $page]->draw($pid);
				pdf_clip($pid);
				pdf_setcolor($pid, "fill", "rgb", 0, 0, 1, 0);
				pdf_rect($pid, $left, $bottom, $right - $left, $top - $bottom);
				pdf_fill($pid);
				if ($le->list[7 + $page]->count() > 0) {
					pdf_setcolor($pid, "fill", "rgb", 1, 1, 0, 0);
					$le->list[7 + $page]->draw($pid);
					pdf_fill($pid);
				}
				pdf_restore($pid);
			}
			if ($le->list[7 + $page]->count() > 0) {
				pdf_setcolor($pid, "fill", "rgb", 1, 0, 0, 0);
				$le->list[7 + $page]->draw($pid);
				pdf_fill($pid);
			}
			if ($le2->list[7 + $page]->count() > 0) {
				pdf_save($pid);
				$le2->list[7 + $page]->draw($pid);
				pdf_clip($pid);
				pdf_setcolor($pid, "fill", "rgb", 0, 0, 1, 0);
				pdf_rect($pid, $left, $bottom, $right - $left, $top - $bottom);
				pdf_fill($pid);
				if ($le->list[7 + $page]->count() > 0) {
					pdf_setcolor($pid, "fill", "rgb", 1, 1, 0, 0);
					$le->list[7 + $page]->draw($pid);
					pdf_fill($pid);
				}
				pdf_restore($pid);
			}
			if ($le->list[8 + $page]->count() > 0) {
				pdf_setcolor($pid, "fill", "rgb", 1, 0, 0, 0);
				$le->list[8 + $page]->draw($pid);
				pdf_fill($pid);
			}
			if ($le2->list[8 + $page]->count() > 0) {
				pdf_save($pid);
				$le2->list[8 + $page]->draw($pid);
				pdf_clip($pid);
				pdf_setcolor($pid, "fill", "rgb", 0, 0, 1, 0);
				pdf_rect($pid, $left, $bottom, $right - $left, $top - $bottom);
				pdf_fill($pid);
				if ($le->list[8 + $page]->count() > 0) {
					pdf_setcolor($pid, "fill", "rgb", 1, 1, 0, 0);
					$le->list[8 + $page]->draw($pid);
					pdf_fill($pid);
				}
				pdf_restore($pid);
			}
			break;
		}
		$layer = (floor($typec / 10) == 0)? 1 : 3;
		if ($le->list[$layer + $page]->count() > 0) {
			pdf_setcolor($pid, "fill", "rgb", 1, 0, 0, 0);
			$le->list[$layer + $page]->draw($pid);
			pdf_fill($pid);
		}
		if ($le2->list[$layer + $page]->count() > 0) {
			pdf_save($pid);
			$le2->list[$layer + $page]->draw($pid);
			pdf_clip($pid);
			pdf_setcolor($pid, "fill", "rgb", 0, 0, 1, 0);
			pdf_rect($pid, $left, $bottom, $right - $left, $top - $bottom);
			pdf_fill($pid);
			if ($le->list[$layer + $page]->count() > 0) {
				pdf_setcolor($pid, "fill", "rgb", 1, 1, 0, 0);
				$le->list[1 + $page]->draw($pid);
				pdf_fill($pid);
			}
			pdf_restore($pid);
		}
		if ($le->list[7 + $page]->count() > 0) {
			pdf_setcolor($pid, "fill", "rgb", 1, 0, 0, 0);
			$le->list[7 + $page]->draw($pid);
			pdf_fill($pid);
		}
		if ($le2->list[7]->count() > 0) {
			pdf_save($pid);
			$le2->list[7]->draw($pid);
			pdf_clip($pid);
			pdf_setcolor($pid, "fill", "rgb", 0, 0, 1, 0);
			pdf_rect($pid, $left, $bottom, $right - $left, $top - $bottom);
			pdf_fill($pid);
			if ($le->list[7]->count() > 0) {
				pdf_setcolor($pid, "fill", "rgb", 0.5, 0.5, 0.5, 0);
				$le->list[7]->draw($pid);
				pdf_fill($pid);
			}
			pdf_restore($pid);
		}
		if ($le->list[8 + $page]->count() > 0) {
			pdf_setcolor($pid, "fill", "rgb", 1, 0, 0, 0);
			$le->list[8 + $page]->draw($pid);
			pdf_fill($pid);
		}
		if ($le->list[8]->count() > 0) {
			pdf_save($pid);
			$le2->list[8]->draw($pid);
			pdf_clip($pid);
			pdf_setcolor($pid, "fill", "rgb", 0, 0, 1, 0);
			pdf_rect($pid, $left, $bottom, $right - $left, $top - $bottom);
			pdf_fill($pid);
			if ($le->list[8]->count() > 0) {
				pdf_setcolor($pid, "fill", "rgb", 0.8, 0.8, 0.8, 0);
				$le->list[8]->draw($pid);
				pdf_fill($pid);
			}
			pdf_restore($pid);
		}
		
		if ($page > 0)
			break;
		$page++;
		if (($typec % 10) == 0) {
			pdf_end_page($pid);
			pdf_begin_page($pid, mm2pnt($paperwidth * $foldh), mm2pnt($paperheight * $foldv));
		}
		continue;
	}
	
	switch ($type) {
		default:
		case	0:
		case	20:
			pdf_setcolor($pid, "fill", "rgb", 1, 1, 0, 0);
			pdf_rect($pid, $left, $bottom, $right - $left, $top - $bottom);
			pdf_fill($pid);
			if ($le->list[5 + $page]->count() > 0) {
				pdf_setcolor($pid, "fill", "rgb", 1, 1, 1, 0);
				$le->list[5 + $page]->draw($pid);
				pdf_fill($pid);
			}
			if ($le->list[3 + $page]->count() > 0) {
				pdf_setcolor($pid, "fill", "rgb", 1, 0, 1, 0);
				$le->list[3 + $page]->draw($pid);
				pdf_fill($pid);
			}
			if ($le->list[1 + $page]->count() > 0) {
				pdf_save($pid);
				$le->list[1 + $page]->draw($pid);
				pdf_clip($pid);
				pdf_setcolor($pid, "fill", "rgb", 0, 1, 0, 0);
				pdf_rect($pid, $left, $bottom, $right - $left, $top - $bottom);
				pdf_fill($pid);
				if ($le->list[3 + $page]->count() > 0) {
					pdf_setcolor($pid, "fill", "rgb", 0, 0, 1, 0);
					$le->list[3 + $page]->draw($pid);
					pdf_fill($pid);
				}
				if ($le->list[5 + $page]->count() > 0) {
					pdf_setcolor($pid, "fill", "rgb", 0.6, 1, 1, 0);
					$le->list[5 + $page]->draw($pid);
					pdf_fill($pid);
				}
				pdf_restore($pid);
			}
			break;
		case	21:
			if (($page)) {
				if ($le->list[3]->count() > 0) {
					pdf_setcolor($pid, "fill", "rgb", 1, 1, 0, 0);
					$le->list[3]->draw($pid);
					pdf_fill($pid);
				}
				if ($le->list[1]->count() > 0) {
					pdf_setcolor($pid, "fill", "rgb", 1, 0, 1, 0);
					$le->list[1]->draw($pid);
					pdf_fill($pid);
				}
				if ($le->list[2]->count() > 0) {
					pdf_save($pid);
					$le->list[2]->draw($pid);
					pdf_clip($pid);
					pdf_setcolor($pid, "fill", "rgb", 0, 1, 1, 0);
					pdf_rect($pid, $left, $bottom, $right - $left, $top - $bottom);
					pdf_fill($pid);
					if ($le->list[1]->count() > 0) {
						pdf_setcolor($pid, "fill", "rgb", 0, 0, 1, 0);
						$le->list[1]->draw($pid);
						pdf_fill($pid);
					}
					pdf_restore($pid);
				}
				break;
			}
		case	10:
			if ($le->list[3 + $page]->count() > 0) {
				pdf_setcolor($pid, "fill", "rgb", 1, 0, 1, 0);
				$le->list[3 + $page]->draw($pid);
				pdf_fill($pid);
			}
			break;
		case	11:
		case	12:
		case	13:
		case	14:
			$mul = 1;
			switch ($type) {
				case	13:
					$mul = 10;
					break;
				case	14:
					$mul = 100;
					break;
			}
			
			if ($le->list[3 + $page]->count() > 0) {
				if ($type != 12)
					pdf_setcolor($pid, "fill", "rgb", 1, 0, 1, 0);
				else
					pdf_setcolor($pid, "fill", "rgb", 0, 0, 0, 0);
				$le->list[3 + $page]->draw($pid);
				pdf_fill($pid);
			}
			if ($le->list[7]->count() > 0) {
				pdf_setcolor($pid, "fill", "rgb", 0, 0, 0, 0);
				$le->list[7]->draw($pid);
				pdf_fill($pid);
			}
			if ($le->list[8]->count() > 0) {
				pdf_setcolor($pid, "stroke", "rgb", 0, 0, 0, 0);
				pdf_setlinewidth($pid, $systeminfo->defaultwidth);
				$le->list[8]->draw($pid);
				pdf_stroke($pid);
			}
			
			$le->list[10]->rewind();
			for (;;) {
				$p =& $le->list[10]->get();
				if ($p === null)
					break;
				if (!is_a($p, "line"))
					continue;
				$sx = $p->sx;
				$sy = $p->sy;
				$ex = $p->ex;
				$ey = $p->ey;
				if ($sy == $ey) {
					if ($sx > $ex) {
						$i = $sx;
						$sx = $ex;
						$ex = $i;
					}
					if ($type != 12)
						pdf_setcolor($pid, "fillstroke", "rgb", 1, 0, 1, 0);
					else
						pdf_setcolor($pid, "fillstroke", "rgb", 0, 0, 0, 0);
					$p->draw($pid);
					$alen = $systeminfo->fontsize / 4;
					$q =& new line($p->r * 2, $sx, $sy, $sx + $alen, $sy + $alen);
					$q->draw($pid);
					$q =& new line($p->r * 2, $sx, $sy, $sx + $alen, $sy - $alen);
					$q->draw($pid);
					$q =& new line($p->r * 2, $ex, $ey, $ex - $alen, $ey + $alen);
					$q->draw($pid);
					$q =& new line($p->r * 2, $ex, $ey, $ex - $alen, $ey - $alen);
					$q->draw($pid);
					pdf_fill($pid);
					
					$s = (($ex - $sx) * $mul)."";
					$len = drawstring($pid, 0, 0, $s, 0);
					pdf_setcolor($pid, "fillstroke", "rgb", 0, 0, 0, 0);
					drawstring($pid, ($sx + $ex - $len) / 2, $sy + $systeminfo->fontsize / 4, $s);
					continue;
				}
				if ($p->sx == $p->ex) {
					if ($sy > $ey) {
						$i = $sy;
						$sy = $ey;
						$ey = $i;
					}
					if ($type != 12)
						pdf_setcolor($pid, "fillstroke", "rgb", 1, 0, 1, 0);
					else
						pdf_setcolor($pid, "fillstroke", "rgb", 0, 0, 0, 0);
					$p->draw($pid);
					$alen = $systeminfo->fontsize / 4;
					$q =& new line($p->r * 2, $sx, $sy, $sx + $alen, $sy + $alen);
					$q->draw($pid);
					$q =& new line($p->r * 2, $sx, $sy, $sx - $alen, $sy + $alen);
					$q->draw($pid);
					$q =& new line($p->r * 2, $ex, $ey, $ex + $alen, $ey - $alen);
					$q->draw($pid);
					$q =& new line($p->r * 2, $ex, $ey, $ex - $alen, $ey - $alen);
					$q->draw($pid);
					pdf_fill($pid);
					
					$s = (($ey - $sy) * $mul)."";
					$len = drawstring($pid, 0, 0, $s, 0);
					$x = $sx - $systeminfo->fontsize / 4;
					$y = ($sy + $ey - $len) / 2;
					pdf_setcolor($pid, "fillstroke", "rgb", 0, 0, 0, 0);
					pdf_save($pid);
					pdf_concat($pid, 0, 1, -1, 0, $x, $y);
					drawstring($pid, 0, 0, $s);
					pdf_restore($pid);
					continue;
				}
				
				$le->list[8]->rewind();
				for (;;) {
					$q =& $le->list[8]->get();
					if ($q === null)
						break;
					if (!is_a($q, "land"))
						continue;
					
					if (($q->x == $sx)&&($q->y == $sy))
						;
					else if (($q->x == $ex)&&($q->y == $ey)) {
						$i = $sx;
						$sx = $ex;
						$ex = $i;
						$i = $sy;
						$sy = $ey;
						$ey = $i;
					} else
						continue;
					
					$s = chr(0xa6).chr(0xd5).($q->r * 2 * $mul);
					$len = drawstring($pid, 0, 0, $s, 0);
					
					if ($sx < $ex)
						$sx -= $q->r / 1.4 + $systeminfo->fontsize / 2;
					else
						$sx += $q->r / 1.4 + $systeminfo->fontsize / 2;
					if ($sy < $ey)
						$sy -= $q->r / 1.4 + $systeminfo->fontsize / 2;
					else
						$sy += $q->r / 1.4 + $systeminfo->fontsize / 2;
					
					if ($type != 12)
						pdf_setcolor($pid, "fillstroke", "rgb", 1, 0, 1, 0);
					else
						pdf_setcolor($pid, "fillstroke", "rgb", 0, 0, 0, 0);
					$q =& new line($p->r * 2, $sx, $sy, $ex, $ey);
					$q->draw($pid);
					if ($sx < $ex) {
						$q =& new line($p->r * 2, $ex, $ey, $ex + $len, $ey);
						$q->draw($pid);
						pdf_fill($pid);
						pdf_setcolor($pid, "fillstroke", "rgb", 0, 0, 0, 0);
						drawstring($pid, $ex, $ey + $systeminfo->fontsize / 4, $s);
					} else {
						$q =& new line($p->r * 2, $ex, $ey, $ex - $len, $ey);
						$q->draw($pid);
						pdf_fill($pid);
						pdf_setcolor($pid, "fillstroke", "rgb", 0, 0, 0, 0);
						drawstring($pid, $ex - $len, $ey + $systeminfo->fontsize / 4, $s);
					}
					
					break;
				}
			}
			break 2;
		case	15:
			if ($le->list[3]->count() > 0) {
				pdf_setcolor($pid, "fill", "rgb", 1, 1, 0, 0);
				$le->list[3]->draw($pid);
				pdf_fill($pid);
			}
			if ($le->list[1]->count() > 0) {
				pdf_setcolor($pid, "fill", "rgb", 1, 0, 1, 0);
				$le->list[1]->draw($pid);
				pdf_fill($pid);
			}
			if ($le->list[2]->count() > 0) {
				pdf_save($pid);
				$le->list[2]->draw($pid);
				pdf_clip($pid);
				pdf_setcolor($pid, "fill", "rgb", 0, 1, 1, 0);
				pdf_rect($pid, $left, $bottom, $right - $left, $top - $bottom);
				pdf_fill($pid);
				if ($le->list[1]->count() > 0) {
					pdf_setcolor($pid, "fill", "rgb", 0, 0, 1, 0);
					$le->list[1]->draw($pid);
					pdf_fill($pid);
				}
				pdf_restore($pid);
			}
			break;
	}
	if ($le->list[7]->count() > 0) {
		pdf_setcolor($pid, "fill", "rgb", 0, 0, 0, 0);
		$le->list[7]->draw($pid);
		pdf_fill($pid);
	}
	if ($le->list[8]->count() > 0) {
		pdf_setcolor($pid, "fill", "rgb", 0, 0, 0, 0);
		$le->list[8]->draw($pid);
		pdf_fill($pid);
	}
	if ($page > 0)
		break;
	$page++;
	switch ($type) {
		default:
		case	0:
			if (($foldh > 1)||($foldv > 1))
				continue 2;
			pdf_end_page($pid);
			pdf_begin_page($pid, mm2pnt($paperwidth * $foldh), mm2pnt($paperheight * $foldv));
			continue 2;
		case	10:
		case	15:
			break;
	}
	break;
}
pdf_end_page($pid);
pdf_close($pid);
$content = pdf_get_buffer($pid);
pdf_delete($pid);
$pid = null;
header("Content-Type: application/pdf");
header('Content-Disposition: inline; filename="pcb2pdf.pdf"');
header("Content-Length: ".strlen($content));
print $content;
?>
