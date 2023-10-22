<?php
$scriptname = "syntaxdiagram.php";
$version = $scriptname." 061125";

class	systeminfo {
	var	$fontpath = "";
	var	$font;
	var	$hostencode = "EUC-JP";
	var	$fontencode = "UTF-8";
	var	$fontsize = 12;
	function	systeminfo() {
		$this->font = array();
	}
	function	pos($mul = 1, $div = 1) {
		return $this->fontsize * $mul / $div;
	}
}
$systeminfo =& new systeminfo();

include("env.php");

class	holder {
	var	$parent = null;
	var	$itemlist;
	var	$factory = null;
	var	$factorylist;
	function	holder(&$factory) {
		$itemlist = array();
		$this->factory =& $factory;
		$this->factorylist = array();
	}
	function	gethtml($type = "") {
$val = "<OL>";
for ($i=0; $i<count($this->itemlist); $i++)
	$val .= "<LI>".$this->itemlist[$i]->gethtml($type);
return $val."</OL>";
		$val = "";
		for ($i=0; $i<count($this->itemlist); $i++)
			$val .= $this->itemlist[$i]->gethtml($type);
		return $val;
	}
	function	draw($left, $top, &$right, &$bottom, $gid = -1, $pixel = -1) {
		$bottom = $top;
		for ($i=0; $i<count($this->itemlist); $i++) {
			$this->itemlist[$i]->draw($left, $top, $r, $b, $gid, $pixel);
			$left = $r;
			$bottom = max($bottom, $b);
		}
		$right = $left;
		return 0;
	}
	function	additem(&$item) {
		$item->parent =& $this;
		$this->itemlist[] =& $item;
	}
	function	&accept($command) {
		die("accept called.");
		$null = null;
		return $null;
	}
	function	acceptablepos($command) {
		return $this->factory->creatablepos($command, $this->parent);
	}
	function	discardcommand($command) {
# print "<BR>unknown command: ".htmlspecialchars($command)."(".bin2hex($command).")";
		return;
	}
	function	&parsecommand($command) {
		$minpos = 0x7fffffff;
		$mintarget = null;
		$minitem = null;
		$item =& $this;
		do {
			$factorylist =& $item->factory->factorylist;
			for ($i=0; $i<count($factorylist); $i++)
				if ($minpos > ($pos = $factorylist[$i]->creatablepos($command, $item))) {
					$minpos = $pos;
					$mintarget =& $factorylist[$i];
					$minitem =& $item;
					if ($pos == 0)
						break 2;
				}
			if ($minpos > ($pos = $item->acceptablepos($command))) {
				$minpos = $pos;
				$null = null;
				$mintarget =& $null;
				$minitem =& $item;
				if ($pos == 0)
					break;
			}
		} while (($item =& $item->parent) !== null);
		if ($minitem === null) {
			$this->discardcommand($command);
			return $this;
		}
		if ($minpos > 0) {
			$this->discardcommand(substr($command, 0, $minpos));
			$command = substr($command, $minpos);
		}
		if ($mintarget === null)
			return $minitem->accept($command);
		return $mintarget->create($command, $minitem);
	}
	function	&createholder() {
		return new holder($this);
	}
	function	addfactory(&$factory) {
		$this->factorylist[] =& $factory;
	}
	function	&create($command, &$parent) {
		$target =& $this->createholder();
		$parent->additem($target);
		return $target->accept($command);
	}
	function	creatablepos($command, &$parent) {
		die("creatablepos called.");
	}
}


class	functionholder extends holder {
	var	$selecter = 0;
	function	&functionholder(&$factory) {
		parent::holder($factory);
		$this->itemlist[0] = array();
		$this->itemlist[1] = array();
	}
	function	gethtml($type = "") {
		return parent::gethtml($type);
	}
	function	draw($left, $top, &$right, &$bottom, $gid = -1, $pixel = -1) {
		global	$systeminfo;
		
		if (count($this->itemlist[1]) == 0)
			return $this->draw_inner($left, $top, $right, $bottom, $gid, $pixel);
		
		$this->draw_inner($left, $top, $right, $bottom);
		$bodywidth = $right - $left;
		$widthlist = array();
		for ($i=0; $i<count($this->itemlist[1]); $i++) {
			$t = $bottom;
			$this->itemlist[1][$i]->draw($left, $t, $r, $bottom);
			$widthlist[$i] = $r - $left;
			$right = max($right, $r + $systeminfo->pos());
		}
		$right += $systeminfo->pos(2);
		if ($gid < 0)
			return 3;
		
		imagearc($gid, $left + $systeminfo->pos(), $top + $systeminfo->pos(7, 4), $systeminfo->pos() + 1, $systeminfo->pos() + 1, 180, 270, $pixel);
		imagearc($gid, $right - $systeminfo->pos(), $top + $systeminfo->pos(7, 4), $systeminfo->pos() + 1, $systeminfo->pos() + 1, 270, 0, $pixel);
		imageline($gid, $left + $systeminfo->pos(1, 2), $top + $systeminfo->pos(7, 4), $left + $systeminfo->pos(1, 2), $t + $systeminfo->pos(3, 4), $pixel);
		imageline($gid, $right - $systeminfo->pos(1, 2), $top + $systeminfo->pos(7, 4), $right - $systeminfo->pos(1, 2), $t + $systeminfo->pos(3, 4), $pixel);
		$width = $right - $left;
		$offset = ($width - $bodywidth) / 2;
		$linepos = $top + $systeminfo->pos(5, 4);
		imageline($gid, $left, $linepos, $left + $offset, $linepos, $pixel);
		imageline($gid, $right - $offset, $linepos, $right, $linepos, $pixel);
		$this->draw_inner($left + $offset, $top, $r, $bottom, $gid, $pixel);
		for ($i=0; $i<count($this->itemlist[1]); $i++) {
			$offset = ($width - $widthlist[$i]) / 2;
			$linepos = $bottom + $systeminfo->pos(5, 4);
			imageline($gid, $left + $systeminfo->pos(), $linepos, $left + $offset, $linepos, $pixel);
			imageline($gid, $right - $offset, $linepos, $right - $systeminfo->pos(), $linepos, $pixel);
			imageline($gid, $right - $offset, $linepos, $right - $offset + $systeminfo->pos(1, 2), $linepos - $systeminfo->pos(1, 4), $pixel);
			imageline($gid, $right - $offset, $linepos, $right - $offset + $systeminfo->pos(1, 2), $linepos + $systeminfo->pos(1, 4), $pixel);
			imagearc($gid, $left + $systeminfo->pos(), $bottom + $systeminfo->pos(3, 4), $systeminfo->pos() + 1, $systeminfo->pos() + 1, 90, 180, $pixel);
			imagearc($gid, $right - $systeminfo->pos(), $bottom + $systeminfo->pos(3, 4), $systeminfo->pos() + 1, $systeminfo->pos() + 1, 0, 90, $pixel);
			$t = $bottom;
			$this->itemlist[1][$i]->draw($left + $offset, $t, $r, $bottom, $gid, $pixel);
		}
		return 3;
	}
	function	draw_inner($left, $top, &$right, &$bottom, $gid = -1, $pixel = -1) {
		global	$systeminfo;
		
		$right = $left;
		$bottom = $top;
		switch (count($this->itemlist[0])) {
			case	0:
				return 0;
			case	1:
				return $this->itemlist[0][0]->draw($left, $top, $right, $bottom, $gid, $pixel);
		}
		$widthlist = array();
		for ($i=0; $i<count($this->itemlist[0]); $i++) {
			$t = $bottom;
			$this->itemlist[0][$i]->draw($left, $t, $r, $bottom);
			$widthlist[$i] = $r - $left;
			$right = max($right, $r);
		}
		$right += $systeminfo->pos(2);
		if ($gid < 0)
			return 3;
		
		imagearc($gid, $left, $top + $systeminfo->pos(7, 4), $systeminfo->pos() + 1, $systeminfo->pos() + 1, 270, 0, $pixel);
		imagearc($gid, $right, $top + $systeminfo->pos(7, 4), $systeminfo->pos() + 1, $systeminfo->pos() + 1, 180, 270, $pixel);
		imageline($gid, $left + $systeminfo->pos(1, 2), $top + $systeminfo->pos(7, 4), $left + $systeminfo->pos(1, 2), $t + $systeminfo->pos(3, 4), $pixel);
		imageline($gid, $right - $systeminfo->pos(1, 2), $top + $systeminfo->pos(7, 4), $right - $systeminfo->pos(1, 2), $t + $systeminfo->pos(3, 4), $pixel);
		$width = $right - $left;
		$bottom = $top;
		for ($i=0; $i<count($this->itemlist[0]); $i++) {
			$offset = ($width - $widthlist[$i]) / 2;
			$linepos = $bottom + $systeminfo->pos(5, 4);
			if ($i == 0) {
				imageline($gid, $left, $linepos, $left + $offset, $linepos, $pixel);
				imageline($gid, $right - $offset, $linepos, $right, $linepos, $pixel);
			} else {
				imageline($gid, $left + $systeminfo->pos(), $linepos, $left + $offset, $linepos, $pixel);
				imageline($gid, $right - $offset, $linepos, $right - $systeminfo->pos(), $linepos, $pixel);
				imagearc($gid, $left + $systeminfo->pos(), $bottom + $systeminfo->pos(3, 4), $systeminfo->pos() + 1, $systeminfo->pos() + 1, 90, 180, $pixel);
				imagearc($gid, $right - $systeminfo->pos(), $bottom + $systeminfo->pos(3, 4), $systeminfo->pos() + 1, $systeminfo->pos() + 1, 0, 90, $pixel);
			}
			$t = $bottom;
			$this->itemlist[0][$i]->draw($left + $offset, $t, $r, $bottom, $gid, $pixel);
		}
		return 3;
	}
	function	additem(&$item) {
		$item->parent =& $this;
		$this->itemlist[$this->selecter][] =& $item;
	}
	function	&accept($command) {
		if (strlen($command) > 0)
			return $this->parsecommand(substr($command, 1));
		return $this;
	}
	function	&createholder() {
		return new functionholder($this);
	}
	function	creatablepos($command, &$parent) {
		if (($pos = strpos($command, "{")) === FALSE)
			return 0x7fffffff;
		return $pos;
	}
}


class	functionselecter extends holder {
	function	&create($command, &$parent) {
		$parent->selecter = 0;
		if (strlen($command) > 1) {
			$factorylist =& $parent->factory->factorylist;
			$target =& $factorylist[count($factorylist) - 1]->createholder();
			$parent->additem($target);
			return $target->parsecommand(substr($command, 1));
		}
		return $parent;
	}
	function	creatablepos($command, &$parent) {
		if (($pos = strpos($command, "|")) === FALSE)
			return 0x7fffffff;
		return $pos;
	}
}


class	functionrepeater extends holder {
	function	&create($command, &$parent) {
		$parent->selecter = 1;
		if (strlen($command) > 1) {
			$factorylist =& $parent->factory->factorylist;
			$target =& $factorylist[count($factorylist) - 1]->createholder();
			$parent->additem($target);
			return $target->parsecommand(substr($command, 1));
		}
		return $parent;
	}
	function	creatablepos($command, &$parent) {
		if (($pos = strpos($command, "r")) === FALSE)
			return 0x7fffffff;
		return $pos;
	}
}


class	functioncloser extends holder {
	function	&create($command, &$parent) {
		if (strlen($command) > 1)
			return $parent->parent->parsecommand(substr($command, 1));
		return $parent->parent;
	}
	function	creatablepos($command, &$parent) {
		if (($pos = strpos($command, "}")) === FALSE)
			return 0x7fffffff;
		return $pos;
	}
}


class	recursiveholder extends holder {
	function	&accept($command) {
		$factorylist =& $this->factory->factorylist;
		for ($i=0; $i<count($factorylist); $i++)
			if ($factorylist[$i]->creatablepos($command, $this) === 0)
				return $factorylist[$i]->create($command, $this);
		return $this;
	}
	function	&createholder() {
		return new recursiveholder($this);
	}
	function	creatablepos($command, &$parent) {
		$minpos = 0x7fffffff;
		$null = null;
		$factorylist =& $this->factorylist;
		for ($i=0; $i<count($factorylist); $i++)
			if ($minpos > ($pos = $factorylist[$i]->creatablepos($command, $null))) {
				$minpos = $pos;
				if ($pos == 0)
					break;
			}
		return $minpos;
	}
}


class	linerholder extends recursiveholder {
	function	draw($left, $top, &$right, &$bottom, $gid = -1, $pixel = -1) {
		global	$systeminfo;
		
		$flagleft = 0;
		$flagright = 0;
		$right = $left;
		$bottom = $top + $systeminfo->pos(5, 2);
		for ($i=0; $i<count($this->itemlist); $i++) {
			$left = $right;
			$f = $this->itemlist[$i]->draw($left, $top, $r, $b, -1, -1);
			if ($i == 0)
				$flagleft = $f & 1;
			else if (($f & 1)||($flagright))
				;
			else {
				$left = $right + $systeminfo->pos();
				if ($gid >= 0)
					imageline($gid, $right, $top + $systeminfo->pos(5, 4), $left, $top + $systeminfo->pos(5, 4), $pixel);
			}
			$flagright = $f & 2;
			$this->itemlist[$i]->draw($left, $top, $right, $b, $gid, $pixel);
			$bottom = max($bottom, $b);
		}
		return $flagleft | $flagright;
	}
	function	&createholder() {
		return new linerholder($this);
	}
}


class	constholder extends holder {
	var	$string = "";
	function	gethtml($type = "") {
		return "constholder(".htmlspecialchars($this->string).")";
	}
	function	draw($left, $top, &$right, &$bottom, $gid = -1, $pixel = -1) {
		global	$systeminfo;
		
		$offset = max(4 - mb_strwidth($this->string, "UTF-8"), 2) * $systeminfo->pos(1, 4);
		$bottom = $top + $systeminfo->pos(5, 2);
		$right = $left + mb_strwidth($this->string, $systeminfo->fontencode) * $systeminfo->pos(1, 2) + $offset * 2;
		if ($gid >= 0) {
			imageline($gid, $left + $systeminfo->pos(), $top + $systeminfo->pos(1, 4), $right - $systeminfo->pos(), $top + $systeminfo->pos(1, 4), $pixel);
			imageline($gid, $left + $systeminfo->pos(), $top + $systeminfo->pos(9, 4), $right - $systeminfo->pos(), $top + $systeminfo->pos(9, 4), $pixel);
			imagearc($gid, $left + $systeminfo->pos(), $top + $systeminfo->pos(5, 4), $systeminfo->pos(2) + 1, $systeminfo->pos(2) + 1, 90, 270, $pixel);
			imagearc($gid, $right - $systeminfo->pos(), $top + $systeminfo->pos(5, 4), $systeminfo->pos(2) + 1, $systeminfo->pos(2) + 1, 270, 90, $pixel);
			imagettftext($gid, $systeminfo->pos(72, 96), 0, $left + $offset, $top + $systeminfo->pos(7, 4) - 1, $pixel, $systeminfo->font[0], $this->string);
		}
		return 0;
	}
	function	&accept($command) {
		if (($pos = strpos($command, ")", 2)) === FALSE) {
			$this->string = substr($command, 1);
			return $this;
		}
		$this->string = substr($command, 1, $pos - 1);
		if ($pos + 1 < strlen($command))
			return $this->parent->parsecommand(substr($command, $pos + 1));
		return $this->parent;
	}
	function	&createholder() {
		return new constholder($this);
	}
	function	creatablepos($command, &$parent) {
		if (($pos = strpos($command, "(")) === FALSE)
			return 0x7fffffff;
		return $pos;
	}
}


class	variableholder extends holder {
	var	$string = "";
	function	gethtml($type = "") {
		return "variableholder(".htmlspecialchars($this->string).")";
	}
	function	draw($left, $top, &$right, &$bottom, $gid = -1, $pixel = -1) {
		global	$systeminfo;
		
		$bottom = $top + $systeminfo->pos(5, 2);
		$right = $left + mb_strwidth($this->string, $systeminfo->fontencode) * $systeminfo->pos(1, 2) + $systeminfo->pos();
		if ($gid >= 0) {
			imagerectangle($gid, $left, $top + $systeminfo->pos(1, 4), $right, $top + $systeminfo->pos(9, 4), $pixel);
			imagettftext($gid, $systeminfo->pos(72, 96), 0, $left + $systeminfo->pos(1, 2), $top + $systeminfo->pos(7, 4) - 1, $pixel, $systeminfo->font[0], $this->string);
		}
		return 0;
	}
	function	&accept($command) {
		if (($pos = strpos($command, "]", 2)) === FALSE) {
			$this->string = substr($command, 1);
			return $this;
		}
		$this->string = substr($command, 1, $pos - 1);
		if ($pos + 1 < strlen($command))
			return $this->parent->parsecommand(substr($command, $pos + 1));
		return $this->parent;
	}
	function	&createholder() {
		return new variableholder($this);
	}
	function	creatablepos($command, &$parent) {
		if (($pos = strpos($command, "[")) === FALSE)
			return 0x7fffffff;
		return $pos;
	}
}


$null = null;
$functionfactory =& new functionholder($null);
$functionselecter =& new functionselecter($null);
$functionrepeater =& new functionrepeater($null);
$functioncloser =& new functioncloser($null);
$linerfactory =& new linerholder($null);
$constfactory =& new constholder($null);
$variablefactory =& new variableholder($null);

$functionfactory->addfactory($functionselecter);
$functionfactory->addfactory($functionrepeater);
$functionfactory->addfactory($functioncloser);
$functionfactory->addfactory($linerfactory);
$linerfactory->addfactory($functionfactory);
$linerfactory->addfactory($constfactory);
$linerfactory->addfactory($variablefactory);


function	puttesthtml($string) {
	global	$scriptname;
	
	$url = $scriptname."?".urlencode($string);
	$htmlstring = htmlspecialchars($string);
	return <<<EOO
<IMG alt="{$htmlstring}" src="{$url}">
EOO;
}

if (($command = urldecode($_SERVER["QUERY_STRING"])) == "") {
	$string1 = urlencode("");
	print <<<EOO
<HTML><HEAD><TITLE>{$version}</TITLE></HEAD><BODY>
<H1>{$version}</H1>

<UL>
EOO;
	print "<P>".puttesthtml("(for)((){[expr1]|}(;){[expr2]|}(;){[expr3]|}())({){[expr4]|}(})")."</P>";
	print "<P>".puttesthtml("{(-)|}{[1-9]{[0-9]|r}|(0)}{(.){[0-9]|r}[1-9]|}")."</P>";
	print "<P>".puttesthtml("(array)((){{{[key](=>)|}[value]r(,)}|}())")."</P>";
	print "<P>".puttesthtml("(select){{[field]r(,)}|(*)}(from){[database]r(,)}{(where){[field](=)[value]r(and)r(or)}|}")."</P>";
	print <<<EOO
</UL>

<HR>
</BODY></HTML>
EOO;
	return;
}
$command = mb_convert_encoding($command, $systeminfo->fontencode, $systeminfo->hostencode);

$rootholder =& $functionfactory->createholder();
$currentholder =& $rootholder;
$currentholder =& $currentholder->parsecommand($command);
$rootholder->draw(4, 0, $size_x, $size_y);
$gid = imagecreate($size_x, $size_y) or die("imagecreate failed.");
imagesetthickness($gid, 2);
$pixel0 = imagecolorresolve($gid, 255, 255, 255);
imagefilledrectangle($gid, 0, 0, $size_x - 1, $size_y - 1, $pixel0);

$pixel1 = imagecolorresolve($gid, 0, 0, 0);
$rootholder->draw(2, 0, $size_x, $size_y, $gid, $pixel1);

header("Content-Type: image/png");
imagepng($gid);
imagedestroy($gid);
die();

?>
