<?php
$scriptname = "index.php";
$scriptversion = $scriptname." 100226";

class	systeminfo {
	var	$dbpath = "./master";
	var	$variable;
	var	$linkid = 0;
	var	$debug = 0;
	var	$linecount = 0;
	var	$php_self;
	var	$func_shutdown;
	function	systeminfo() {
		global	$scriptversion;
		
		if (($this->php_self = @$_SERVER["ORIG_PATH_INFO"]) == "")
			$this->php_self = @$_SERVER["PHP_SELF"];
		$this->func_shutdown = array();
		$this->variable = array(
			"VERSION" => $scriptversion, 
			"BASEURL" => "", 
			"LINKEXTENSION" => "", 
			"SEARCH" => "(&amp;SEARCH;)", 
			"TITLE" => "(&amp;TITLE;)", 
			"HEADER" => "(&amp;HEADER;)", 
			"FOOTER" => "(&amp;FOOTER;)", 
			"MENULEFT" => "", 
			"MENUSPACE" => " | ", 
			"MENURIGHT" => "", 
			"OP_H1" => "<H1>| ", 
			"OP_TABLE" => '<TABLE border align=center>', 
			"nodata" => "Sorry, no content found.<BR>&nbsp;", 
			"br" => "<BR>", 
			"hr" => "<HR>", 
			"amp" => "&amp;", 
			"tab" => "\t", 
			"null" => "", 
			"CM_err_noinput" => "", 
			"CM_err_noname" => "Name required.", 
			"CM_err_nobody" => "Body required.", 
			"CM_err_toolong" => "Message too long.", 
			"CM_err_manypost" => "Too many posts. Please wait.", 
			"" => ""
		);
	}
	function	get($code) {
		return @$this->variable[$code.""]."";
	}
	function	getkeylist() {
		return array_keys($this->variable);
	}
	function	getscripturl() {
		global	$scriptname;
		
		return $this->get("BASEURL").$scriptname;
	}
	function	getdirectorylist($linkextension = "") {
		$base = $this->getscripturl();
		if (ereg("^[a-zA-Z0-9]+://[-a-zA-Z0-9.:@]+(/.*)$", $base, $array))
			$base = $array[1];
		if (!ereg("/$", $base))
			$base .= "/";
		$url = $this->php_self;
		if (strpos($url, $base) !== 0)
			return array();
		if ($linkextension == "")
			;
		else if (substr($url, -strlen($linkextension)) == $linkextension)
			$url = substr($url, 0, strlen($url) - strlen($linkextension));
		return explode("/", substr($url, strlen($base)));
	}
	function	getlinkid($withurl = 0) {
		$val = "";
		if (($withurl)) {
			$val .= "http://".$_SERVER["HTTP_HOST"];
			if (($port = $_SERVER["SERVER_PORT"]) != 80)
				$val .= ":".$port;
			$val .= $this->php_self;
		}
		return $val."#".$this->linkid++;
	}
	function	shutdown() {
		foreach ($this->func_shutdown as $key => $val)
			$this->func_shutdown[$key]->shutdown();
	}
	function	debugprint() {
	}
	function	debugadd() {
		return "";
	}
	function	debugline() {
	}
}
class	debugsysteminfo extends systeminfo {
	var	$linecount = 0;
	function	getdirectorylist($linkextension = "") {
		$list = parent::getdirectorylist($linkextension);
		$this->debugprint("directorylist:".implode(", ", $list));
		return $list;
	}
	function	debugprint($string = "") {
		global	$filter;
		
		$filter->add(new debugline(htmlspecialchars($string)));
	}
	function	debugadd($section = "(null)", $string = "") {
		$section .= "#".(++$this->linecount);
		$this->debugprint($section.":".$string);
		return $section;
	}
	function	debugline($section, &$line, $string = "") {
		$this->debugprint($section."-".$string.":".$line->getdebug());
	}
}


class	encode	{
	var	$key = "encodecheck";
	var	$string = "2バ イ ト 文 字 のtestです。";
	var	$list = "EUC-JP, SJIS, UTF-8";
	var	$host;
	var	$form = "";
	function	encode() {
		$this->host = mb_detect_encoding($this->string, $this->list);
		if (strlen(@$_POST[$this->key]."") > 2)
			$this->form = mb_detect_encoding($_POST[$this->key], $this->list);
	}
	function	sethost($encode) {
		$this->string = mb_convert_encoding($this->string, $this->host = $encode, $this->list);
	}
	function	putheader($type = "text/html") {
		header('Content-Type: '.$type.'; charset="'.$this->host.'"');
	}
	function	gethtml($code) {
		switch ($code) {
			case	"hiddenstring":
				return <<<EOO
<INPUT type=hidden name={$this->key} value="{$this->string}">
EOO;
		}
		die("gethtml");
	}
	function	getpost($name) {
		$val = @$_POST[$name]."";
		if ($this->form == "")
			die("encode_form null.");
		if (get_magic_quotes_gpc())
			$val = stripslashes($val);
		return mb_convert_encoding($val, $this->host, $this->form);
	}
	function	encodesearch($string, $case = 0) {
		if (($case)) {
			$string = mb_convert_kana($string, "ash", $this->host);
			$string = mb_convert_kana($string, "HV", $this->host);
			$string = mb_convert_kana($string, "ask", $this->host);
		} else {
			$string = strtolower(mb_convert_kana($string, "askh", $this->host));
			$string = ereg_replace("[- \t\r\n+_%*$#@\"|,<>]", " ", $string);
		}
		$string = mb_convert_kana($string, "KV", $this->host);
#		$string = implode(" ", split("[- \t\r\n+_%*$#@\"|,<>]+", $string));
		return mb_convert_encoding($string, "UTF-8", $this->host);
	}
}


class	commentinfo	{
	function	checkcommentpost() {
		global	$systeminfo;
		
		if (@$_POST["body"] === null)
			return;
		$id = @$_GET["comment"] + 0;
		header("Location: ".$systeminfo->getscripturl());
		die();
	}
	# 'CM_' will add
	function	getcommentlist($id = -1, $order = -10) {
		return array();
		return array(array("id" => 1, "entry" => 11, "date" => "2009/01/01", "name" => "anonymous", "body" => "- Hello."), array("id" => 2, "entry" => 16, "date" => "2009/01/02", "name" => "anonymous2", "body" => "#contents\nBye."));
	}
	# 'CM_' will add
	function	getformvariable() {
		return array(
			"name" => '<INPUT type=text name=name size=20>', 
			"body" => '<TEXTAREA cols=40 rows=10 name=body></TEXTAREA>', 
			"submit" => '<INPUT type=submit>', 
			"" => '', 
			"formopen" => '<FORM method=POST style="margin: 0em;">', 
			"formclose" => '</FORM>'
		);
	}
}


$systeminfo =& new systeminfo();
$encode =& new encode();
$commentinfo =& new commentinfo();

include("env.php");
$scripturl = $systeminfo->getscripturl();

class	database	{
	var	$sid = null;
	function	database($path) {
		$this->sid = sqlite_open($path, 0666, $str) or die("sqlite_open failed : ".htmlspecialchars($str));
	}
	function	gethtmlerror() {
		return htmlspecialchars(sqlite_error_string(sqlite_last_error($this->sid)));
	}
	function	close() {
		if ($this->sid !== null) {
			sqlite_close($this->sid);
			$this->sid = null;
		}
	}
	function	query($sql, $array) {
		$sql = ereg_replace("[\t\r\n ]+", " ", $sql);
		$remain = $sql;
		$sql = "";
		while (($pos = strpos($remain, "?")) !== FALSE) {
			$sql .= substr($remain, 0, $pos);	# before '?'
			$remain = substr($remain, $pos + 1);	# after '?'
			if (($val = array_shift($array)) === null)
				$sql .= "null";
			else if ($val === "")
				$sql .= "''";
			else if (is_string($val))
				$sql .= "'".sqlite_escape_string($val)."'";
			else
				$sql .= ($val + 0);
		}
		$sql .= $remain;
# print "<PRE>".gettype($val)."(".htmlspecialchars($val).") : ".htmlspecialchars($sql)."</PRE>";
		return $this->directquery($sql);
	}
	function	directquery($sql) {
		return sqlite_array_query($this->sid, $sql, SQLITE_ASSOC);
	}
	function	getupdates() {
		return sqlite_changes($this->sid) + 0;
	}
	function	getlastid() {
		return sqlite_last_insert_rowid($this->sid) + 0;
	}
	function	lock($mode) {
		if ($mode < 0)
			return $this->directquery("rollback;");
		if ($mode > 0)
			return $this->directquery("begin;");
		return $this->directquery("commit;");
	}
}


class	variable {
	var	$value;
	var	$lock = 0;
	function	variable() {
		$this->value = array();
	}
	function	import(&$variable) {
		foreach ($variable->value as $key => $val)
			$this->value[$key] =& $variable->value[$key];
		$this->dirty = 1;
	}
	function	set($name, &$line) {
		global	$systeminfo, $directorylist;
		
		$this->value[$name] =& $line;
		switch ($name) {
			case	"LINKEXTENSION":
				$directorylist = $systeminfo->getdirectorylist($this->get($name));
				$line =& new stringline(@$directorylist[0]);
				$this->set("DB_genre", $line);
				$systeminfo->debugline("(linkextension)", $line, "set[DB_genre]");
				break;
		}
	}
	function	erase($name) {
		$null = null;
		$this->value[$name] =& $null;
	}
	function	&getline($name) {
		if (@$this->value[$name.""] === null) {
			$null = null;
			return $null;
		}
		return $this->value[$name.""];
	}
	function	get($name) {
		$line =& $this->getline($name);
		if ($line === null)
			return null;
		return $line->gethtml();
	}
	function	getfgcol($subcode, $ext = "") {
		$code = "FG_".$subcode.$ext;
		if (($val = $this->get($code)) !== null)
			return $val."";
		if ($ext != "")
			return $this->getfgcol($subcode);
		return "#000000";
	}
	function	getbgcol($subcode, $ext = "") {
		$code = "BG_".$subcode.$ext;
		if (($val = $this->get($code)) !== null)
			return $val."";
		if ($ext != "")
			return $this->getbgcol($subcode);
		if ($code == "bgcol_bar")
			return "#c0c0c0";
		return "#ffffff";
	}
	function	open($tag, $attribute = "") {
		if (($val = $this->get("OP_".$tag)) === null) {
			if ($attribute == "")
				return "<{$tag}>";
			return "<{$tag} {$attribute}>";
		}
		if ($attribute == "")
			return $val."";
		if (!ereg("^<[A-Za-z_]+", $val, $array))
			return $val."";
		$pos = strlen($array[0]);
		return substr($val, 0, $pos)." ".$attribute.substr($val, $pos);
	}
	function	close($tag) {
		if (($val = $this->get("CL_".$tag)) === null)
			$val = "</".$tag.">";
		return $val."";
	}
}
class	dbvariable extends variable {
	var	$db;
	var	$dirty = 1;
	var	$fieldlist;
	var	$dbvariablelist;
	function	dbvariable(&$db) {
		parent::variable();
		$this->db =& $db;
		$this->fieldlist = array("id", "genre", "title", "type", "version", "date", "par0", "par1", "par2", "par3", "lead", "body");
		$this->dbvariablelist = array();
		foreach ($this->fieldlist as $val)
			$this->dbvariablelist["DB_".$val] = 1;
	}
	function	checkdbvariable($name) {
		return @$this->dbvariablelist[$name.""] + 0;
	}
	function	set($name, &$line) {
		if (($this->dirty == 0)&&($this->checkdbvariable($name)))
			$this->dirty = 1;
		parent::set($name, $line);
	}
	function	erase($name) {
		if (($this->dirty == 0)&&($this->checkdbvariable($name)))
			$this->dirty = 1;
		parent::erase($name);
	}
	function	scan($name, $order) {
		$sql = "select {$name} from content where show > ?";
		$value = array(0);
		foreach ($this->fieldlist as $key) {
			if (@$this->value["DB_".$key] === null)
				continue;
			$line =& $this->value["DB_".$key];
			if (($line->variabletype))
				continue;
			$sql .= " and {$key} = ?";
			$value[] = $line->gettext();
		}
		$sql .= " group by {$name}";
		$sql .= " order by {$name}";
		if ($order < 0)
			$sql .= " desc limit ".(-$order);
		else if ($order > 0)
			$sql .= " limit {$order}";
		$result = $this->db->query($sql.";", $value);
		$array = array();
		foreach ($result as $val)
			$array[] = str_replace(array("\r\n", "\r"), array("\n", "\n"), $val[$name]);
		return $array;
	}
	function	refresh() {
		global	$command;
		
		if (($this->dirty == 0)||($this->lock > 0))
			return;
		$this->dirty = 0;
		
		$fields = array();
		$where = "where show > ?";
		$value = array(0);
		foreach ($this->fieldlist as $key) {
			if (@$this->value["DB_".$key] === null) {
				$fields[] = $key;
				continue;
			}
			$line =& $this->value["DB_".$key];
			if (($line->variabletype)) {
				$fields[] = $key;
				continue;
			}
			$where .= " and {$key} = ?";
			$value[] = $line->gettext();
		}
		if (count($fields) == 0)
			return;
		
		$result = $this->db->query("select ".implode(", ", $fields)." from content {$where} limit 1;", $value);
		foreach ($fields as $key)
			$this->erase("DB_".$key);
		if (count($result) != 1) {
			$this->dirty = 0;
			return;
		}
		
		$this->lock++;
		foreach ($result[0] as $key => $val) {
			$obj =& new setcommand($command, "DB_".$key);
			$obj->debugsection = "(DB)";
			$obj->parse(str_replace(array("\r\n", "\r"), array("\n", "\n"), $val), "(DB)", 1);
			$obj->output($this, 1);
		}
		$this->lock--;
		$this->dirty = 0;
	}
	function	&getline($name) {
		if (($this->dirty)&&($this->checkdbvariable($name)))
			$this->refresh();
		return parent::getline($name);
	}
}


class	command {
	var	$specialtype = 0;
	var	$parent;
	var	$variable;
	var	$debugsection = "(null)";
	var	$debugname = "(null)";
	function	command(&$parent) {
		$this->parent =& $parent;
		$this->variable =& $parent->variable;
	}
	function	&parse($string, $debugsection = "(null)") {
		return $this->parent;
	}
	function	close() {
	}
	function	output(&$filter) {
	}
}
class	rootcommand extends command {
	var	$forwarding;
	var	$list;
	var	$searchkeylist;
	var	$searchdigestlist;
	var	$commentdigestlist;
	var	$debugname = "root";
	function	rootcommand() {
		global	$variable;
		
		$this->parent =& $this;
		$this->variable =& $variable;
		$this->forwarding =& $this;
		$this->list = array();
		$this->searchkeylist = array();
		$this->searchdigestlist = array();
		$this->commentdigestlist = array();
	}
	function	addsearchdigest(&$searchdigestcommand) {
		$this->searchdigestlist[] =& $searchdigestcommand;
	}
	function	addcommand(&$command) {
		$this->list[] =& $command;
	}
	function	add($string, $scangenre = null, $debugsection = "(null)") {
		global	$systeminfo;
		
		$debugsection = $systeminfo->debugadd($debugsection, $string);
		if (substr($string, 0, 10) == "#searchkey") {
			if (@$this->searchkeylist[$scangenre] !== null) {
				$systeminfo->debugprint("=== ignored : 2nd. searchkey ===");
				return;
			}
			$this->forwarding->close();
			$array = split("[ \t]+", $string);
			array_shift($array);
			$obj =& new searchkeycommand($this, $array);
			$obj->debugsection = $debugsection;
			$this->searchkeylist[$scangenre] =& $obj;
			$this->forwarding =& $obj;
			return;
		}
		if (substr($string, 0, 14) == "#commentdigest") {
			if (@$this->commentdigstlist[$scangenre] !== null) {
				$systeminfo->debugprint("=== ignored : 2nd. commentdigest ===");
				return;
			}
			$this->forwarding->close();
			$obj =& new commentdigestcommand($this);
			$obj->debugsection = $debugsection;
			$this->commentdigestlist[$scangenre] =& $obj;
			$this->forwarding =& $obj;
			return;
		}
		if (substr($string, 0, 7) == "#geturl") {
			if ($scangenre !== null)
				return;
			$this->forwarding->close();
			$array = split("[ \t]+", $string, 2);
			if (@$array[1] == "") {
				$this->forwarding =& $this;
				return;
			}
			$obj =& new geturlcommand($this, @$array[1]."", ($array[0] == "#geturlid")? 1 : 0);
			$obj->debugsection = $debugsection;
			$this->addcommand($obj);
			$this->forwarding =& $obj;
			return;
		}
		if (substr($string, 0, 13) == "#subdirectory") {
			if ($scangenre !== null)
				return;
			$this->forwarding->close();
			$array = split("[ \t]+", $string, 2);
			if (@$array[1] == "") {
				$this->forwarding =& $this;
				return;
			}
			$obj =& new geturlcommand($this, @$array[1]."", ($array[0] == "#subdirectoryid")? 1 : 0);
			$obj->debugsection = $debugsection;
			$this->addcommand($obj);
			$this->forwarding =& $obj;
			return;
		}
		if (($scangenre !== null)&&($this->forwarding->specialtype == 0))
			return;
		$this->forwarding =& $this->forwarding->parse($string, $debugsection);
	}
	function	&parse($string, $debugsection = "(null)") {
		if (substr($string, 0, 4) == "#end") {
			$this->close();
			return $this->parent;
		}
		if (substr($string, 0, 8) == "#foreach") {
			$array = split("[ \t]+", $string, 3);
			if (($this->variable->checkdbvariable($array[1]))) {
				$obj =& new foreachcommand($this, $array[1], @$array[2] + 0);
				$obj->debugsection = $debugsection;
				$this->addcommand($obj);
				return $obj;
			}
		}
		if (substr($string, 0, 18) == "#showcommentdigest") {
			$array = split("[ \t]+", $string, 2);
			$obj =& new showcommentdigestcommand($this, @$array[1] + 0);
			$obj->debugsection = $debugsection;
			$this->addcommand($obj);
			return $this;
		}
		if (substr($string, 0, 13) == "#searchresult") {
			$obj =& new searchresultcommand($this);
			$obj->debugsection = $debugsection;
			$this->addcommand($obj);
			return $this;
		}
		if (substr($string, 0, 12) == "#showcomment") {
			$array = split("[ \t]+", $string, 2);
			$obj =& new showcommentcommand($this, @$array[1] + 0);
			$obj->debugsection = $debugsection;
			$this->addcommand($obj);
			return $obj;
		}
		if (substr($string, 0, 7) == "#plugin") {
			$array = split("[ \t]+", $string, 3);
			$obj =& new plugincommand($this, @$array[1], @$array[2]);
			$obj->debugsection = $debugsection;
			$this->addcommand($obj);
			return $this;
		}
		if (substr($string, 0, 9) == "#contents") {
			$obj =& new contentscommand($this);
			$obj->debugsection = $debugsection;
			$this->addcommand($obj);
			return $this;
		}
		if (substr($string, 0, 4) == "#set") {
			$array = split("[ \t]+", $string, 3);
			switch ($array[0]) {
				case	"#set":
					$obj =& new setcommand($this, $array[1]);
					$obj->debugsection = $debugsection;
					$this->addcommand($obj);
					if (count($array) == 3)
						$obj->parse($array[2], $debugsection, 1);
					return $this;
				case	"#sethtml":
					$obj =& new setcommand($this, $array[1], 1);
					$obj->debugsection = $debugsection;
					$this->addcommand($obj);
					if (count($array) == 3)
						$obj->parse($array[2], $debugsection, 1);
					return $this;
				case	"#setstart":
					$obj =& new setcommand($this, $array[1]);
					$obj->debugsection = $debugsection;
					$this->addcommand($obj);
					return $obj;
				case	"#sethtmlstart":
					$obj =& new setcommand($this, $array[1], 1);
					$obj->debugsection = $debugsection;
					$this->addcommand($obj);
					return $obj;
			}
		}
		if (substr($string, 0, 3) == "#vr") {
			$obj =& new vrcommand($this);
			$obj->debugsection = $debugsection;
			$this->addcommand($obj);
			return $this;
		}
		$obj =& new linecommand($this);
		$obj->debugsection = $debugsection;
		$this->addcommand($obj);
		return $obj->parse($string);
	}
	function	output(&$filter) {
		for ($i=0; $i<count($this->list); $i++)
			$this->list[$i]->output($filter);
	}
}
class	geturlcommand extends rootcommand {
	var	$name;
	var	$value;
	var	$skip = 1;
	var	$debugname = "geturl";
	function	geturlcommand(&$parent, $name, $idmode = 0) {
		global	$systeminfo, $directorylist;
		
		parent::command($parent);
		$this->name = $name;
		$this->skip = (count($directorylist) == 1)? 0 : 1;
		$this->value = array_shift($directorylist);
		if (($idmode)&&($this->variable->checkdbvariable($this->name))) {
			if (count($result = $this->variable->db->query("select ".substr($this->name, 3)." from content where id = ?;", array($this->value + 0))) == 1)
				$this->value = $result[0][substr($this->name, 3)];
			else
				$this->value = "";
		}
		$this->debugname .= "[{$name}]";
		$systeminfo->debugprint(($this->skip)? "=== skip ===" : "=== ok ===");
	}
	function	&parse($string, $debugsection = "(null)") {
		if (($this->skip))
			return $this;
		return parent::parse($string, $debugsection);
	}
	function	output(&$filter) {
		global	$systeminfo;
		
		$line =& new stringline($this->value);
		$this->variable->set($this->name, $line);
		$systeminfo->debugline($this->debugsection, $line, $this->debugname);
		parent::output($filter);
	}
}
class	searchkeycommand extends rootcommand {
	var	$specialtype = 1;
	var	$fieldlist;
	var	$debugname = "searchkey";
	function	searchkeycommand(&$parent, $fieldlist) {
		parent::command($parent);
		$this->fieldlist = array();
		foreach ($fieldlist as $key)
			if (($this->variable->checkdbvariable($key)))
				$this->fieldlist[] = $key;
	}
}
class	searchresultcommand extends rootcommand {
	var	$order;
	var	$debugname = "searchresult";
	function	searchresultcommand(&$parent) {
		parent::command($parent);
	}
	function	output(&$filter) {
		usort($this->parent->searchdigestlist, create_function('&$a, &$b', '$val = $b->getscore() - $a->getscore();return ($val > 0)? 1 : (($val < 0)? -1 : 0);'));
		for ($i=0; $i<count($this->parent->searchdigestlist); $i++)
			$this->parent->searchdigestlist[$i]->output($filter);
	}
}
class	foreachcommand extends rootcommand {
	var	$name;
	var	$order;
	var	$debugname = "foreach";
	function	foreachcommand(&$parent, $name, $order) {
		parent::command($parent);
		$this->name = $name;
		$this->order = $order;
		$this->debugname .= "[{$name}]";
	}
	function	output(&$filter) {
		global	$systeminfo;
		
		$oldvalue =& $this->variable->getline($this->name);
		$list = $this->variable->scan(substr($this->name, 3), $this->order);
		$systeminfo->debugprint($this->debugsection."-foreach order:".$this->order." list:".implode(", ", $list));
		foreach ($list as $val) {
			$line =& new stringline($val);
			$this->variable->set($this->name, $line);
			$systeminfo->debugline($this->debugsection, $line, $this->debugname);
			parent::output($filter);
		}
		$systeminfo->debugprint($this->debugsection."-".$this->debugname.":end");
		if ($oldvalue === null) {
			$this->variable->erase($this->name);
			$systeminfo->debugprint($this->debugsection."-".$this->debugname.":erased");
		} else {
			$this->variable->set($this->name, $oldvalue);
			$systeminfo->debugline($this->debugsection, $oldvalue, $this->debugname.":restored");
		}
	}
}
class	commentdigestcommand extends rootcommand {
	var	$specialtype = 1;
	var	$debugname = "commentdigest";
}
class	showcommentcommand extends rootcommand {
	var	$order;
	var	$debugname = "showcomment";
	function	showcommentcommand(&$parent, $order = -10) {
		parent::command($parent);
		$this->order = $order;
	}
	function	output(&$filter) {
		global	$systeminfo, $commentinfo;
		
		$line =& $this->variable->getline("DB_id");
		$id = 0;
		if ($line !== null)
			$id = $line->gettext() + 0;
		$systeminfo->debugprint($this->debugsection."-showcomment order:".$this->order." id:".$id);
		
		foreach ($commentinfo->getcommentlist($id, $this->order) as $array) {
			foreach ($array as $key => $val) {
				switch ($key) {
					case	"entry":
					case	"date":
						$line =& new stringline($val);
						break;
					default:
						$line =& new fixedstringline($val);
						break;
				}
				$this->variable->set("CM_".$key, $line);
				$systeminfo->debugline($this->debugsection, $line, $this->debugname);
			}
			parent::output($filter);
		}
		$systeminfo->debugprint($this->debugsection."-".$this->debugname.":end");
	}
}
class	showcommentdigestcommand extends rootcommand {
	var	$order;
	var	$debugname = "showcommentdigest";
	function	showcommentdigestcommand(&$parent, $order = -10) {
		global	$db;
		
		parent::rootcommand();
		$this->order = $order;
		
		$array = $db->query("select key, body from genre where key != ?;", array(""));
		foreach ($array as $record) {
			foreach (split("(\r)|(\n)|(\r\n)", @$record["body"]."") as $line)
				$this->add($line, $record["key"]."", $record["key"]."");
		}
	}
	function	output(&$filter) {
		global	$systeminfo, $commentinfo;
		
		$oldvalue = array();
		foreach ($this->variable->fieldlist as $name) {
			$oldvalue[$name] =& $this->variable->getline("DB_".$name);
			$this->variable->erase("DB_".$name);
		}
		
		foreach ($commentinfo->getcommentlist(-1, $this->order) as $array) {
			foreach ($array as $key => $val) {
				switch ($key) {
					case	"entry":
					case	"date":
						$line =& new stringline($val);
						break;
					default:
						$line =& new fixedstringline($val);
						break;
				}
				$this->variable->set("CM_".$key, $line);
				$systeminfo->debugline($this->debugsection, $line, $this->debugname);
			}
			$id = @$array["entry"] + 0;
			
			$line =& new stringline($id."");
			$this->variable->set("DB_id", $line);
			$systeminfo->debugline($this->debugsection, $line, $this->debugname);
			
			$obj =& $this->variable->getline("DB_genre");
			if (($obj !== null)&&(@$this->commentdigestlist[$obj->gettext()] !== null))
				$this->commentdigestlist[$obj->gettext()]->output($filter);
		}
		$systeminfo->debugprint($this->debugsection."-".$this->debugname.":end");
		foreach ($this->variable->fieldlist as $name) {
			if ($oldvalue[$name] === null) {
				$this->variable->erase("DB_".$name);
				$systeminfo->debugprint($this->debugsection."-".$this->debugname.":erased ".$name);
			} else {
				$this->variable->set("DB_".$name, $oldvalue[$name]);
				$systeminfo->debugline($this->debugsection, $oldvalue[$name], $this->debugname.":restored" .$name);
			}
		}
	}
}
class	plugincommand extends rootcommand {
	var	$debugname = "plugin";
	var	$fn, $arg;
	function	plugincommand(&$parent, $name, $arg) {
		parent::command($parent);
		ereg('^[-_0-9A-Za-z]+$', $name) or die("plugin load failed.");
		$fn = "plugin/{$name}.php";
		is_file($fn) or die("plugin load failed.".$fn);
		$this->fn = $fn;
		$this->arg = $arg;
	}
	function	output(&$filter) {
		global	$systeminfo;
		global	$config;
		
		$line =& new htmlline(include($this->fn));
		$systeminfo->debugline($this->debugsection, $line, $this->debugname);
		$filter->add($line);
	}
}
class	linecommand extends command {
	var	$string = "";
	var	$debugname = "line";
	function	&parse($string, $debugsection = "(null)") {
		$this->string = $string;
		return $this->parent;
	}
	function	output(&$filter) {
		global	$systeminfo;
		
 		$string = $this->string;
		if ($string == "") {
			$line =& new httpparsingline($string);
			$systeminfo->debugline($this->debugsection, $line, $this->debugname);
			$filter->add($line);
			return;
		}
		$line =& new line();
		$buffer = "";
		while (($start = strpos($string, "&")) !== FALSE) {
			if (($end = strpos($string, ";", $start)) === FALSE)
				break;
			$buffer .= substr($string, 0, $start);
			$obj =& $this->variable->getline($key = substr($string, $start + 1, $end - $start - 1));
			if ($obj === null) {
				if ($buffer != "") {
					$line->add(new httpparsingline($buffer));
					$buffer = "";
				}
				$line->add(new stringline(substr($string, $start, $end - $start + 1)));
			} else if (($obj->getstringtype())) {
				$systeminfo->debugline($this->debugsection, $obj, $this->debugname."-variable[{$key}]");
				$buffer .= $obj->gettext();
			} else {
				$systeminfo->debugline($this->debugsection, $obj, $this->debugname."-variable[{$key}]");
				if ($buffer != "") {
					$line->add(new httpparsingline($buffer));
					$buffer = "";
				}
				$line->add($obj);
			}
			$string = substr($string, $end + 1);
		}
		$buffer .= $string;
		if ($buffer != "") {
			$line->add(new httpparsingline($buffer));
			$buffer = "";
		}
		$systeminfo->debugline($this->debugsection, $line, $this->debugname);
		$filter->add($line);
	}
}
class	setcommand extends linecommand {
	var	$name;
	var	$htmlmode = 0;
	var	$string = null;
	var	$debugname = "set";
	function	setcommand(&$parent, $name, $htmlmode = 0) {
		parent::command($parent);
		$this->name = $name;
		$this->htmlmode = $htmlmode;
		$this->debugname .= "[{$name}]";
	}
	function	&parse($string, $debugsection = "(null)", $single = 0) {
		if (($single))
			;
		else if (substr($string, 0, 1) == "#") {
			$this->close();
			return $this->parent;
		}
		
		if ($this->string === null)
			$this->string = $string;
		else
			$this->string .= "\n".$string;
		if (($single)) {
			$this->close();
			return $this->parent;
		}
		return $this;
	}
	function	outputhtml(&$filter) {
		global	$systeminfo;
		
		$string = $this->string;
		$result = "";
		while (($start = strpos($string, "&")) !== FALSE) {
			if (($end = strpos($string, ";", $start)) === FALSE)
				break;
			$obj =& $this->variable->getline($key = substr($string, $start + 1, $end - $start - 1));
			if ($obj !== null) {
				$systeminfo->debugline($this->debugsection, $obj, $this->debugname."-variable[{$key}]");
				if ($start > 0)
					$result .= substr($string, 0, $start);
				$result .= $obj->gethtml();
			} else
				$result .= substr($string, 0, $end + 1);
			$string = substr($string, $end + 1);
		}
		$result .= $string;
		$line =& new htmlline($result);
		$systeminfo->debugline($this->debugsection, $line, $this->debugname);
		$filter->add($line);
	}
	function	output(&$filter, $variabletype = 0) {
		$obj =& new variablefilter($this->variable, $this->name);
		if ($this->string === null)
			return;
		if (($this->htmlmode))
			$this->outputhtml($obj);
		else
			parent::output($obj);
		$this->variable->value[$this->name]->variabletype = $variabletype;
	}
}
class	contentscommand extends command {
	var	$debugname = "contents";
	function	output(&$filter) {
		global	$systeminfo;
		
		$systeminfo->debugprint($this->debugsection.":".$this->debugname);
		$filter->add(new contentsline($filter));
	}
}
class	vrcommand extends command {
	var	$debugname = "vr";
	function	output(&$filter) {
		global	$systeminfo;
		
		$systeminfo->debugprint($this->debugsection.":".$this->debugname);
		$filter->setlistid(1);
	}
}


class	line {
	var	$variabletype = 0;	# 1: set from database
	var	$blocktype = 0;		# 1: root-tag 2: don't change tag
	var	$list;
	function	line() {
		$this->list = array();
	}
	function	getstringtype() {
		for ($i=0; $i<count($this->list); $i++)
			if ($this->list[$i]->getstringtype() == 0)
				return 0;
		return 1;
	}
	function	gethtml($s = 0, $l = -1) {
		return $this->gettext($s, $l, 1);
	}
	function	gettext($s = 0, $l = -1, $html = 0) {
		$val = "";
		$end = 0;
		for ($i=0; $i<count($this->list); $i++) {
			$start = $end;
			$end = $start + $this->list[$i]->strlen();
			if ($end <= $s)
				continue;
			$s0 = max(0, $s - $start);
			$l0 = -1;
			if ($l >= 0) {
				if ($s + $l <= $start)
					break;
				if ($s + $l < $end)
					$l0 = $s + $l - $start;
			}
			if (($html))
				$val .= $this->list[$i]->gethtml($s0, $l0);
			else
				$val .= $this->list[$i]->gettext($s0, $l0);
		}
		return $val;
	}
	function	getdebug() {
		$val = "";
		for ($i=0; $i<count($this->list); $i++)
			$val .= $this->list[$i]->getdebug();
		return $val;
	}
	function	&explode($key) {
		$array = array();
		$array[] =& new line();
		for ($i=0; $i<count($this->list); $i++) {
			$array2 =& $this->list[$i]->explode($key);
			$array[count($array) - 1]->add($array2[0]);
			for ($j=1; $j<count($array2); $j++) {
				$array[] =& new line();
				$array[count($array) - 1]->add($array2[$j]);
			}
		}
		return $array;
	}
	function	strlen() {
		$len = 0;
		for ($i=0; $i<count($this->list); $i++)
			$len += $this->list[$i]->strlen();
		return $len;
	}
	function	ishead($key) {
		if (count($this->list) <= 0)
			return FALSE;
		return $this->list[0]->ishead($key);
	}
	function	istail($key) {
		if (count($this->list) <= 0)
			return FALSE;
		return $this->list[count($this->list) - 1]->istail($key);
	}
	function	add(&$obj) {
		$this->list[] =& $obj;
	}
	function	addstring($string) {
		$this->list[] =& new stringline($string);
	}
}
class	stringline extends line {
	var	$string;
	function	stringline($string = "") {
		$this->string = $string;
	}
	function	getstringtype() {
		return 1;
	}
	function	gethtml($s = 0, $l = -1) {
		return htmlspecialchars($this->gettext($s, $l));
	}
	function	gettext($s = 0, $l = -1) {
		if ($l < 0)
			return substr($this->string, $s)."";
		return substr($this->string, $s, $l)."";
	}
	function	getdebug() {
		return $this->string;
	}
	function	&explode($key) {
		$array = array();
		foreach (explode($key, $this->string) as $val)
			$array[] =& new stringline($val);
		return $array;
	}
	function	strlen() {
		return strlen($this->string);
	}
	function	ishead($key) {
		return (substr($this->string, 0, strlen($key)) == $key);
	}
	function	istail($key) {
		return (substr($this->string, -strlen($key)) == $key);
	}
	function	add($string) {
		$this->string .= $string;
	}
}
class	htmlline extends stringline {
	function	getstringtype() {
		return 0;
	}
	function	gethtml($s = 0, $l = -1) {
		if (($s > 0)||($l == 0))
			return "";
		return $this->string;
	}
	function	gettext($s = 0, $l = -1) {
		return "";
	}
	function	getdebug() {
		return "(HTML)";
	}
	function	&explode($key) {
		$val = array($this);
		return $val;
	}
	function	strlen() {
		return 1;
	}
	function	ishead($key) {
		return FALSE;
	}
	function	istail($key) {
		return FALSE;
	}
	function	add($string) {
		$this->string .= $string;
	}
}
class	fixedstringline extends stringline {
	function	getstringtype() {
		return 0;
	}
	function	gethtml($s = 0, $l = -1) {
		if (($s > 0)||($l == 0))
			return "";
		return nl2br(htmlspecialchars($this->string));
	}
	function	gettext($s = 0, $l = -1) {
		return "";
	}
	function	getdebug() {
		return "(FIXED)";
	}
	function	&explode($key) {
		$val = array($this);
		return $val;
	}
	function	strlen() {
		return 1;
	}
	function	ishead($key) {
		return FALSE;
	}
	function	istail($key) {
		return FALSE;
	}
	function	add($string) {
		$this->string .= $string;
	}
}
class	debugline extends htmlline {
	var	$blocktype = 2;		# 1: root-tag 2: don't change tag
	function	gethtml($s = 0, $l = -1) {
		if (($s > 0)||($l == 0))
			return "";
		return "<!-- \n".$this->string." \t\t\t -->";
	}
	function	getdebug() {
		return "(DEBUG)";
	}
}
class	attrparsingline extends line {
	function	attrparsingline($string = "") {
		parent::line();
		$this->addstring($string);
	}
	function	addstring($string) {
		global	$scripturl, $systeminfo, $variable;
		
		while (($start = strpos($string, "'''")) !== FALSE) {
			if (($end = strpos($string, "'''", $start + 3)) === FALSE)
				break;
			if ($start > 0)
				parent::addstring(substr($string, 0, $start));
			$this->add(new htmlline("<B>"));
			parent::addstring(substr($string, $start + 3, $end - $start - 3));
			$this->add(new htmlline("</B>"));
			$string = substr($string, $end + 3);
		}
		if ($string != "")
			parent::addstring($string);
	}
}
class	linkparsingline extends attrparsingline {
	function	linkparsingline($string = "") {
		parent::attrparsingline();
		$this->addstring($string);
	}
	function	addstring($string) {
		global	$scripturl, $systeminfo, $variable;
		
		while (($start = strpos($string, "[[")) !== FALSE) {
			if (($end = strpos($string, "]]", $start)) === FALSE)
				break;
			if (($separator = strpos($string, ">", $start)) === FALSE)
				$separator = $end;
			else if ($separator > $end)
				$separator = $end;
			if ($start > 0)
				parent::addstring(substr($string, 0, $start));
			$linkname = substr($string, $start + 2, $separator - $start - 2);
			$linknamehtml = htmlspecialchars($linkname, ENT_QUOTES);
			if ($separator < $end)
				$url = substr($string, $separator + 1, $end - $separator - 1);
			else
				$url = "";
			$string = substr($string, $end + 2);
			
			$baseurl = $systeminfo->get("BASEURL");
			$linkextension = $variable->get("LINKEXTENSION");
			$extension = "";
			if (substr($url, 0, 1) != ">")
				;
			else if (substr($url, 1, 1) != ">") {
				$url = substr($url, 1);
				$extension .= "?".urlencode($linkname);
			} else {
				global	$systeminfo;
				
				$url = substr($url, 2);
				$extension .= "?".urlencode($systeminfo->getlinkid(1));
			}
			if ($url == "") {
				$html = <<<EOO
<A href="{$scripturl}">{$linknamehtml}</A>
EOO;
				$this->add(new htmlline($html));
			} else if (substr($url, 0, 1) != "/") {
				$html = <<<EOO
<A href="{$scripturl}/{$url}{$linkextension}">{$linknamehtml}</A>
EOO;
				$this->add(new htmlline($html));
			} else if (eregi('\.(png|jpeg)$', $url)) {
				$url = substr($url, 1);
				$html = <<<EOO
<IMG alt="{$linknamehtml}" src="{$baseurl}{$url}{$extension}">
EOO;
				$this->add(new htmlline($html));
			} else {
				$url = substr($url, 1);
				$html = <<<EOO
<A href="{$baseurl}{$url}{$extension}">{$linknamehtml}</A>
EOO;
				$this->add(new htmlline($html));
			}
		}
		if ($string != "")
			parent::addstring($string);
	}
}
class	httpparsingline extends linkparsingline {
	function	addstring($string) {
		while (($start = strpos($string, "[[http://")) !== FALSE) {
			if (($end = strpos($string, "]]", $start)) === FALSE)
				break;
			if ($start > 0)
				parent::addstring(substr($string, 0, $start));
			$linkname = $url = substr($string, $start + 2, $end - $start - 2);
			if (function_exists("convertglobalurl"))
				$url = convertglobalurl($url);
			$urlhtml = htmlspecialchars($url, ENT_QUOTES);
			$linknamehtml = htmlspecialchars($linkname, ENT_QUOTES);
			$html = <<<EOO
<A href="{$urlhtml}">{$linknamehtml}</A>
EOO;
			$this->add(new htmlline($html));
			$string = substr($string, $end + 2);
		}
		while (($start = strpos($string, "[[mailto:")) !== FALSE) {
			if (($end = strpos($string, "]]", $start)) === FALSE)
				break;
			if ($start > 0)
				parent::addstring(substr($string, 0, $start));
			$linkname = $url = substr($string, $start + 2, $end - $start - 2);
			$urlhtml = htmlspecialchars($url, ENT_QUOTES);
			$linknamehtml = htmlspecialchars(substr($linkname, 7), ENT_QUOTES);
			$html = <<<EOO
<A href="{$urlhtml}">{$linknamehtml}</A>
EOO;
			$this->add(new htmlline($html));
			$string = substr($string, $end + 2);
		}
		while (($start = strpos($string, "[[tel:")) !== FALSE) {
			if (($end = strpos($string, "]]", $start)) === FALSE)
				break;
			if ($start > 0)
				parent::addstring(substr($string, 0, $start));
			$linkname = $url = substr($string, $start + 2, $end - $start - 2);
			$urlhtml = htmlspecialchars($url, ENT_QUOTES);
			$linknamehtml = htmlspecialchars(substr($linkname, 4), ENT_QUOTES);
			$html = <<<EOO
<A href="{$urlhtml}">{$linknamehtml}</A>
EOO;
			$this->add(new htmlline($html));
			$string = substr($string, $end + 2);
		}
		if ($string != "")
			parent::addstring($string);
	}
}
class	contentsline extends htmlline {
	var	$blocktype = 1;		# 1: no <P>...</P>
	var	$filter;
	function	contentsline(&$filter) {
		$this->filter =& $filter;
	}
	function	getstringtype() {
		return 0;
	}
	function	gethtml($s = 0, $l = -1) {
		if (($s > 0)||($l == 0))
			return "";
		return $this->filter->contentsstring;
	}
	function	getdebug() {
		return "(CONTENTS)";
	}
	function	&gethtmlref() {
		return $this->filter->contentsstring;
	}
}


class	filter {
	var	$list;
	function	filter() {
		$this->list = array();
	}
	function	add(&$line) {
		$this->list[] = $line->gettext();
	}
	function	get() {
		return implode("", $this->list);
	}
}
class	nullfilter extends filter {
	function	add(&$line) {
		return;
	}
}
class	variablefilter extends filter {
	var	$variable;
	var	$name;
	function	variablefilter(&$variable, $name) {
		$this->variable =& $variable;
		$this->name = $name;
		$this->variable->erase($this->name);
	}
	function	add(&$line) {
		$this->variable->set($this->name, $line);
	}
}
$wikilistcommand = array(
	"***" => array("H3"), 
	"**" => array("H2"), 
	"*" => array("H1"), 
	"+++" => array("OL", "OL", "OL", "LI"), 
	"++" => array("OL", "OL", "LI"), 
	"+" => array("OL", "LI"), 
	"---" => array("UL", "UL", "UL", "LI"), 
	"--" => array("UL", "UL", "LI"), 
	"-" => array("UL", "LI"), 
);
$wikicommand = array(
	">" => array("BLOCKQUOTE", ""), 
	" " => array("BLOCKQUOTE", "PRE", ""), 
	"" => array("P", ""), 
	"|" => array("TABLE")
);
class	wikifilter extends filter {
	var	$listid = 0;
	var	$currentcommand = null;
	var	$currentlisttag;
	var	$currenttable = null;
	var	$contentslevel = 0;
	var	$contentsstring = "";
	var	$headnumber = array(0);
	function	wikifilter() {
		$this->list = array(array(), array(), array());
		$this->currentlisttag = array();
	}
	function	puthtml($html, $id = -1) {
		if ($id < 0)
			$id = $this->listid;
		$this->list[$id][] = $html;
	}
	function	puthtmlref(&$html, $id = -1) {
		if ($id < 0)
			$id = $this->listid;
		$this->list[$id][] =& $html;
	}
	function	putcontents($html = "", $level = 0) {
		global	$variable;
		
		$tag = "UL";
		while ($this->contentslevel > $level) {
			$this->contentsstring .= $variable->close($tag)."\n";
			$this->contentslevel--;
		}
		while ($this->contentslevel < $level) {
			$this->contentsstring .= $variable->open($tag)."\n";
			$this->contentslevel++;
		}
		if ($html != "")
			$this->contentsstring .= $variable->open("LI").'<A href="#'.implode(".", $this->headnumber).'">'."{$html}</A>".$variable->close("LI")."\n";
	}
	function	puttable() {
		global	$variable;
		
		if ($this->currenttable === null)
			return;
		$rowspancount = array();
		for ($y=0; $y<count($this->currenttable); $y++) {
			$string = "";
			$colspan = 1;
			$array =& $this->currenttable[$y];
			for ($x=1; $x<count($array)-1; $x++) {
				if (@$rowspancount[$x] > 0)
					continue;
#[070729:pai]for |>[[link]]|string|
#				if ($array[$x]->gettext() == ">") {
				if ($array[$x]->gethtml() == "&gt;") {
					$colspan++;
					continue;
				}
				$attribute = "";
				if ($colspan > 1)
					$attribute .= " colspan=".$colspan;
				$rowspan = 1;
				for (;;) {
					if (@$this->currenttable[$y + $rowspan][$x] === null)
						break;
#[070729:pai]for |~[[link]]|
#					if ($this->currenttable[$y + $rowspan][$x]->gettext() != "~")
					if ($this->currenttable[$y + $rowspan][$x]->gethtml() != "~")
						break;
					$rowspan++;
				}
				if ($rowspan > 1) {
					$attribute .= " rowspan=".$rowspan;
					for ($i=0; $i<$colspan; $i++)
						$rowspancount[$x - $i] = $rowspan;
				}
				if ($array[$x]->ishead("~")) {
					$tag = "TH";
					$html = $array[$x]->gethtml(1);
				} else {
					$tag = "TD";
					$html = $array[$x]->gethtml();
				}
				if (substr($html, 0, 1) != " ")
					;
				else if (substr($html, -1) == " ")
					$attribute .= " align=center";
				else
					$attribute .= " align=right";
				$string .= $variable->open($tag, $attribute).$html.$variable->close($tag);
				$colspan = 1;
			}
			$this->puthtml($variable->open("TR").$string.$variable->close("TR")."\n");
			foreach ($rowspancount as $key => $val)
				if ($val > 0)
					$rowspancount[$key]--;
		}
		$this->currenttable = null;
	}
	function	changecommand($command = null) {
		global	$wikicommand;
		global	$variable;
		
		$array = $wikicommand[$command];
		$ret = $array[count($array) - 1];
		if ($this->currentcommand === $command)
			return $ret;
		$this->puttable();
		$html = "";
		if ($this->currentcommand !== null) {
			foreach (array_reverse($wikicommand[$this->currentcommand]) as $tag)
				if ($tag != "")
					$html .= $variable->close($tag);
		}
		$this->currentcommand = $command;
		if ($this->currentcommand !== null) {
			foreach ($wikicommand[$this->currentcommand] as $tag)
				if ($tag != "")
					$html .= $variable->open($tag);
		}
		if ($html != "")
			$this->puthtml($html."\n");
		return $ret;
	}
	function	changelisttag($tag = null) {
		global	$variable;
		
		if ($tag === null)
			$tag = array();
		$this->changecommand();
		$html = "";
		while (count($this->currentlisttag) > count($tag))
			$html .= $variable->close(array_pop($this->currentlisttag));
		while (($i = count($this->currentlisttag)) > 0) {
			if (($x = $this->currentlisttag[$i - 1]) == ($y = $tag[$i - 1]))
				break;
			if ($i >= count($tag))
				;
			else if (($x == "OL")&&($y == "UL"))
				break;
			else if (($x == "UL")&&($y == "OL"))
				break;
			$html .= $variable->close(array_pop($this->currentlisttag));
		}
		while (($i = count($this->currentlisttag)) < count($tag)) {
			$this->currentlisttag[] = $tag[$i];
			$html .= $variable->open($tag[$i]);
		}
		if ($html != "")
			$this->puthtml($html."\n");
	}
	function	addline(&$line) {
		global	$wikicommand;
		global	$wikilistcommand;
		global	$variable;
		
		switch ($line->blocktype) {
			case	0:
				break;
			case	1:
				$this->changelisttag();
				$this->puthtmlref($line->gethtmlref());
				return;
			case	2:
				$this->puthtml($line->gethtml());
				return;
		}
		if ($line->ishead("//"))
			return;
		if ($line->strlen() <= 0) {
			$this->changelisttag();
			return;
		}
		if ($line->ishead("|") && $line->istail("|")) {
			$this->changecommand("|");
			if ($this->currenttable === null)
				$this->currenttable = array();
			$this->currenttable[] =& $line->explode("|");
			return;
		}
		foreach ($wikicommand as $key => $val) {
			if ($key === "")
				break;
			if ($line->ishead($key)) {
				$tag = $this->changecommand($key);
				$html = $line->gethtml(strlen($key));
				if ($tag != "")
					$this->puthtml($variable->open($tag).$html.$variable->close($tag)."\n");
				else
					$this->puthtml($html."\n");
				return;
			}
		}
		foreach ($wikilistcommand as $key => $val)
			if ($line->ishead($key)) {
				$tag = array_pop($val);
				$this->changelisttag($val);
				$html = $line->gethtml(strlen($key));
				if (substr($key, 0, 1) == "*") {
					$this->headnumber = array_slice($this->headnumber, 0, strlen($key));
					if (count($this->headnumber) == strlen($key))
						$this->headnumber[count($this->headnumber) - 1]++;
					while (count($this->headnumber) < strlen($key))
						$this->headnumber[] = 1;
					if (ereg("((.)?(.))?(.)$", $variable->get("HEADNUMBER"), $array))
						$html = $array[2].implode($array[3], $this->headnumber).$array[4].$html;
					$this->puthtml($variable->open($tag).'<A name="'.implode(".", $this->headnumber).'"></A>'.$html.$variable->close($tag)."\n");
					$this->putcontents($html, strlen($key));
					return;
				}
				if ($tag != "")
					$this->puthtml($variable->open($tag).$html.$variable->close($tag)."\n");
				else
					$this->puthtml($html."\n");
				return;
			}
		$tag = $this->changecommand("");
		$html = $line->gethtml();
		if ($tag != "")
			$this->puthtml($variable->open($tag).$html.$variable->close($tag)."\n");
		else
			$this->puthtml($html."\n");
	}
	function	add(&$line) {
		$array =& $line->explode("\n");
		for ($i=0; $i<count($array); $i++)
			$this->addline($array[$i]);
	}
	function	setlistid($id) {
		$this->changelisttag();
		$this->putcontents();
		$this->listid = $id;
	}
	function	getlistid() {
		return $this->listid;
	}
	function	get($id = 0) {
		$this->setlistid($id);
		return implode("", $this->list[$id]);
	}
}


$db =& new database($systeminfo->dbpath);
$variable =& new dbvariable($db);
$filter =& new wikifilter();
foreach ($systeminfo->getkeylist() as $key) {
	$line =& new htmlline($systeminfo->get($key));
	$variable->set($key, $line);
	$systeminfo->debugline("(system)", $line, "set[{$key}]");
}
$nullfilter =& new nullfilter();
$command =& new rootcommand();
$array = $db->query("select config from genre where key = ?;", array(""));
foreach (split("(\r)|(\n)|(\r\n)", @$array[0]["config"]."") as $line)
	$command->add($line, null, "(config)");
$command->output($nullfilter);
$variable->erase("DB_genre");
$systeminfo->debugprint("(system):erased[DB_genre]");

$command =& new rootcommand();

$linkextension = $variable->get("LINKEXTENSION");
$genrekey = "";
$menulist = array();
foreach ($db->query("select key, pos, title from genre order by pos;", array()) as $val) {
	$name = htmlspecialchars($val["title"]);
	$url = $val["key"];
	if ($url === @$directorylist[0]) {
		$genrekey = $val["key"];
		if ($val["pos"] + 0 <= 0)
			continue;
		if (count($directorylist) == 1) {
			$menulist[] = $name;
			continue;
		}
	}
	if ($val["pos"] + 0 <= 0)
		continue;
	if (strlen($url) > 0)
		$url = "/".$url.$linkextension;
	$menulist[] = <<<EOO
<A href="{$scripturl}{$url}">{$name}</A>
EOO;
}
if ($genrekey == "")
	$directorylist = array();


class	area {
	var	$poslist;
	function	area() {
		$this->poslist = array();
	}
	function	add($start, $end, $mingap = 0) {
		if ($start < 0)
			$start = 0;
		if ($start >= $end)
			return;
		for ($i=0; $i<count($this->poslist); $i+=2) {
			if ($end + $mingap < $this->poslist[$i]) {
					# [i-2] [i-1] start end [i] [i+1]
				$this->poslist[] = $start;
				$this->poslist[] = $end;
				sort($this->poslist);
				return;
			}
			if ($start - $mingap <= $this->poslist[$i + 1]) {
				$this->poslist[$i] = min($this->poslist[$i], $start);
				while ($i + 2 < count($this->poslist)) {
					if ($end + $mingap < $this->poslist[$i + 2])
						break;
					array_splice($this->poslist, $i + 1, 2);
				}
				$this->poslist[$i + 1] = max($this->poslist[$i + 1], $end);
				return;
			}
		}
			# [....poslist] start end
		$this->poslist[] = $start;
		$this->poslist[] = $end;
	}
	function	getposlist($start = 0, $end = -1) {
		$pos = 0;
		for (;;) {
			if ($pos >= count($this->poslist))
				return array();
			if ($this->poslist[$pos + 1] > $start)
				break;

				# [pos] [pos+1] start
			$pos += 2;
		}
		if ($end < 0) {
			$array = array_slice($this->poslist, $pos);
			if ($array[0] < $start)
				$array[0] = $start;
			return $array;
		}
		$len = 0;
		for (;;) {
			if ($pos + $len >= count($this->poslist))
				break;
			if ($this->poslist[$pos + $len] >= $end)
				break;
			
				# [len] end [len+1]
				# [len] [len+1] end
			$len += 2;
		}
		$array = array_slice($this->poslist, $pos, $len);
		if ($array[0] < $start)
			$array[0] = $start;
		if ($array[$len - 1] > $end)
			$array[$len - 1] = $end;
		return $array;
	}
	function	getlength() {
		$length = 0;
		for ($i=0; $i<count($this->poslist); $i+=2)
			$length += $this->poslist[$i + 1] - $this->poslist[$i];
		return $length;
	}
}
class	searchdigestcommand extends command {
	var	$id;
	var	$command;
	var	$area;
	var	$area_b;
	var	$body;
	var	$digest;
	var	$posstring;
	var	$score;
	var	$scorelist;
	var	$keylenlist;
	function	searchdigestcommand(&$parent, $id, &$command) {
		global	$db, $encode;
		
		parent::command($parent);
		
		$this->id = $id + 0;
		$this->command =& $command;
		$this->area =& new area();
		$this->area_b =& new area();
		$this->body = "";
		$this->digest = "";
		$this->score = 0;
		$this->scorelist = array();
		$this->keylenlist = array();
		$result = $db->query("select * from content where id = ?;", array($id + 0));
		foreach ($command->fieldlist as $key) {
			if (($val = @$result[0][substr($key, 3)]) === null)
				continue;
			if (strlen($this->body) > 0) {
				$this->body .= str_repeat(" ", 100);
				$this->digest .= str_repeat(" ", 100);
			}
			$val = implode(" ", split("[ \t\r\n]+", $val));
			$this->body .= $encode->encodesearch($val, 1);
			$this->digest .= $encode->encodesearch($val);
		}
		$this->posstring = str_repeat(" ", mb_strlen($this->body, "UTF-8"));
	}
	function	addkey($key) {
		$index = count($this->keylenlist);
		if ($index == 1)
			$this->scorelist = array();
		$this->keylenlist[$index] = mb_strlen($key, "UTF-8");
		$max = array();
		$pos = 0;
		while (($pos = mb_strpos($this->digest, $key, $pos, "UTF-8")) !== FALSE) {
#			$this->area->add($pos - 16, $pos + mb_strlen($key, "UTF-8") + 16, 4);
			$this->area_b->add($pos, $pos + mb_strlen($key, "UTF-8"));
			
			$left = substr($this->posstring, 0, $pos);
			$right = substr($this->posstring, $pos + 1);
			$this->posstring = $left.chr(0x21 + $index).$right;
			
			if (($index))
				;
			else if (($val = strrpos($left, chr(0x21))) !== FALSE) {
				$score = 100 / min(strlen($left) - $val, 100);
				@$this->scorelist[$pos] += $score;
				@$this->scorelist[$val] += $score;
			} else
				$this->scorelist[$pos] = 0.1;
			for ($i=0; $i<$index; $i++) {
				if (($val = strpos($right, chr(0x21 + $i))) !== FALSE) {
					$score = ($this->keylenlist[$i] + $this->keylenlist[$index]) * 100 / min($val + 1, 100);
					$max[$i] = max(@$max[$i] + 0, $score);
					@$this->scorelist[$pos] += $score;
					@$this->scorelist[$pos + 1 + $val] += $score;
				}
				if (($val = strrpos($left, chr(0x21 + $i))) !== FALSE) {
					$score = ($this->keylenlist[$i] + $this->keylenlist[$index]) * 100 / min(strlen($left) - $val, 100);
					$max[$i] = max(@$max[$i] + 0, $score);
					@$this->scorelist[$pos] += $score;
					@$this->scorelist[$val] += $score;
				}
			}
			$pos++;
		}
# print "<BR>".$this->id;
# print_r($this->scorelist);
# print "<PRE>".$this->id.htmlspecialchars($this->posstring);
		for ($i=0; $i<$index; $i++) {
			$this->score += $max[$i];
# printf(" / %d",($this->keylenlist[$i] + $this->keylenlist[$index]) * 100 / $min[$i]);
		}
# printf(" [%d]</PRE>\n", $this->score);
	}
	function	getscore() {
		if (($score = $this->score) == 0)
			return substr_count($this->posstring, chr(0x21));
		return $score;
	}
	function	getdigeststring($start, $end) {
		global	$encode;
		
		$string = htmlspecialchars(mb_substr($this->body, $start, $end - $start, "UTF-8"), ENT_QUOTES);
		return mb_convert_encoding($string, $encode->host, "UTF-8");
	}
	function	output(&$filter) {
		global	$systeminfo;
		
		arsort($this->scorelist);
		foreach ($this->scorelist as $key => $val) {
			$this->area->add($key - 16, $key + 32, 4);
			if ($this->area->getlength() > 128)
				break;
		}
		$digest = "";
		$poslist = $this->area->getposlist();
		for ($i=0; $i<count($poslist); $i+=2) {
			$start = $poslist[$i];
			$end = $poslist[$i + 1];
			$poslist_b = $this->area_b->getposlist($start, $end);
			$digest .= ".. ";
			for ($j=0; $j<count($poslist_b); $j+=2) {
				$pos = $poslist_b[$j];
				$digest .= $this->getdigeststring($start, $pos);
				$digest .= "<B>";
				$start = $poslist_b[$j + 1];
				$digest .= $this->getdigeststring($pos, $start);
				$digest .= "</B>";
			}
			$digest .= $this->getdigeststring($start, $end);
			$digest .= " ..";
		}
#print "<PRE>".htmlspecialchars($digest)."</PRE>\n";
		$line =& new stringline($this->id."");
		$this->command->variable->set("DB_id", $line);
		$systeminfo->debugline("(search)", $line, "set[DB_ID]");
		$line =& new htmlline($digest);
		$this->command->variable->set("SEARCHDIGEST", $line);
		$systeminfo->debugline("(search)", $line, "set[SEARCHDIGEST]");
		$this->command->output($filter);
	}
}


$body = "";
$config = "";
$searchkey = "";
if (@$_POST["search"] !== null) {
	@set_time_limit(10);
	$searchkey = $encode->getpost("search");
	
	$array = $db->query("select key, body from genre where key != ?;", array(""));
	foreach ($array as $record) {
		foreach (split("(\r)|(\n)|(\r\n)", @$record["body"]."") as $line)
			$command->add($line, $record["key"]."", $record["key"]."");
	}
	
	$searchkeylist = array();
	$array = explode(" ", $encode->encodesearch($searchkey));
	for ($i=0; $i<count($array); $i++) {
		if (strlen($array[$i]) == 0)
			continue;
		for ($j=0; $j<count($array); $j++) {
			if ($i == $j)
				continue;
			if (strpos($array[$j], $array[$i]) !== FALSE)
				continue 2;
		}
		$searchkeylist[] = $array[$i];
# print htmlspecialchars($array[$i])."<BR>";
	}
	
	$sql = "select id, genre from content where show > ?";
	$array = array(0);
	foreach ($searchkeylist as $key) {
		$sql .= " and searchbody like ?";
		$array[] = "%".$key."%";
	}
	if (count($searchkeylist) > 0)
		foreach ($db->query($sql.";", $array) as $val) {
			if (@$command->searchkeylist[$val["genre"]] === null)
				continue;
			$searchcommand =& $command->searchkeylist[$val["genre"]];
			$searchdigestcommand =& new searchdigestcommand($command, $val["id"] + 0, $searchcommand);
			foreach ($searchkeylist as $key)
				$searchdigestcommand->addkey($key);
			$command->addsearchdigest($searchdigestcommand);
		}
	
	$body = <<<EOO
#searchresult
&nodata;
EOO;
	$array = $db->query("select body from genre where key = ?;", array("?search"));
	if (count($array) > 0)
		$body = $array[0]["body"]."";
} else if (($id = @$_GET["comment"] + 0) > 0) {
	$array = $db->query("select body from genre where key = ?;", array("?comment"));
	if (count($array) > 0) {
		$err = $commentinfo->checkcommentpost();
		if (($body = $array[0]["body"]."") == "") {
			$body = <<<EOO
&CM_err;
|~name|&CM_name;|
|~body|&CM_body;|
|>| &CM_submit; |

EOO;
		}
		$body = <<<EOO
&CM_formopen;
{$body}
&CM_formclose;

EOO;
		$command->variable->set("CM_err", $command->variable->getline("CM_err_".$err));
		foreach ($commentinfo->getformvariable() as $key => $val) {
			if ($key == "entry")
				$command->variable->set("CM_".$key, new stringline($val));
			else
				$command->variable->set("CM_".$key, new htmlline($val));
		}
	}
}

if ($body == "") {
	$array = $db->query("select body, config from genre where key = ?;", array($genrekey));
	$body = @$array[0]["body"]."";
	$config = @$array[0]["config"]."";	# for plugin
}

foreach (split("(\r)|(\n)|(\r\n)", $body) as $line)
	$command->add($line, null, $genrekey);

$command->output($filter);


$searchkey = htmlspecialchars($searchkey, ENT_QUOTES);
$menubar = $variable->get("MENULEFT").implode($variable->get("MENUSPACE"), $menulist).$variable->get("MENURIGHT");

if ($filter->getlistid() == 0) {
	$sidebar = "";
	$body = $filter->get(0);
} else {
	$sidebar = $filter->get(0);
	$body = $filter->get(1);
}

$fgcol_title = $variable->getfgcol("TITLE");
$bgcol_title = $variable->getbgcol("TITLE");
$fgcol_bar = $variable->getfgcol("BAR");
$bgcol_bar = $variable->getbgcol("BAR");
$bgcol_bar_left = $variable->getbgcol("BAR", "LEFT");
$bgcol_bar_right = $variable->getbgcol("BAR", "RIGHT");
$bgcol_side = $variable->getbgcol("SIDE");
$bgcol_side_left = $variable->getbgcol("SIDE", "LEFT");
$bgcol_side_right = $variable->getbgcol("SIDE", "RIGHT");
$bgcol_body = $variable->getbgcol("BODY");
$bgcol_body_left = $variable->getbgcol("BODY", "LEFT");
$bgcol_body_right = $variable->getbgcol("BODY", "RIGHT");

$open_html = $variable->open("HTML");
$close_html = $variable->close("HTML");
$open_head = $variable->open("HEAD");
$close_head = $variable->close("HEAD");
$open_title = $variable->open("TITLE");
$close_title = $variable->close("TITLE");
$open_body = $variable->open("BODY");
$close_body = $variable->close("BODY");
$title = $variable->get("TITLE")."";
$header = $variable->get("HEADER")."";
$encodecheck = $encode->gethtml("hiddenstring");
$search = $variable->get("SEARCH")."";
$footer = $variable->get("FOOTER")."";

if (0) {
	$body .= "<TABLE border>";
	foreach ($variable->value as $key => $val)
		$body .= "<TR><TH>".htmlspecialchars($key)."<TD>".htmlspecialchars($val);
	$body .= "</TABLE>";
}

$output = <<<EOO
{$open_html}{$open_head}{$open_title}{$title}{$close_title}{$close_head}{$open_body}
<TABLE width="100%" cellspacing=0 cellpadding=0>
<TR><TD width="1%"></TD>
	<TD width="25%"></TD>
	<TD width="1%"></TD>
	<TD width="2%"></TD>
	<TD width="40%"></TD>
	<TD width="30%"></TD>
	<TD width="1%"></TD></TR>
<TR><TD colspan=7 bgcolor="{$bgcol_title}" class="headerarea">{$header}</TD></TR>
<TR><TD bgcolor="{$bgcol_bar_left}" class="menuarea" id="menuleft">&nbsp;</TD>
	<TD colspan=4 bgcolor="{$bgcol_bar}" class="menuarea"><FONT color="{$fgcol_bar}">{$menubar}</FONT></TD>
	<TD align=right bgcolor="{$bgcol_bar}" class="menuarea" id="menuform">
<FORM method=POST action="{$scripturl}" style="margin:0em;">
{$encodecheck}
<INPUT type=text name=search size=20 value="{$searchkey}"><INPUT type=submit value="{$search}">
</FORM>
</TD>
	<TD bgcolor="{$bgcol_bar_right}" class="menuarea" id="menuright">&nbsp;</TD></TR>
<TR><TD bgcolor="{$bgcol_side_left}" class="sidearea" id="sideleft">&nbsp;</TD>
	<TD valign=top bgcolor="{$bgcol_side}" class="sidearea"><BR>{$sidebar}<BR></TD>
	<TD bgcolor="{$bgcol_side_right}" class="sidearea" id="sideright">&nbsp;</TD>
	<TD bgcolor="{$bgcol_body_left}" class="mainarea" id="mainleft">&nbsp;</TD>
	<TD colspan=2 valign=top bgcolor="{$bgcol_body}" class="mainarea"><!--body--><BR>{$body}<BR><!--body--></TD>
	<TD bgcolor="{$bgcol_body_right}" class="mainarea" id="mainright">&nbsp;</TD></TR>
<TR><TD bgcolor="{$bgcol_bar_left}" class="footerarea" id="footerleft">&nbsp;</TD>
<TD colspan=5 align=right bgcolor="{$bgcol_bar}" class="footerarea">
<FONT size="-1" color="{$fgcol_bar}">{$footer}</FONT></TD>
	<TD bgcolor="{$bgcol_bar_right}" class="footerarea" id="footerright">&nbsp;</TD></TR>
</TABLE>
{$close_body}{$close_html}
EOO;

$encode->putheader();
header("Content-Length: ".strlen($output));
print $output;
$systeminfo->shutdown();
?>
