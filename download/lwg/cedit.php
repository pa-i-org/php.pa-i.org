<?php
$scriptname = "cedit.php";
$scriptversion = $scriptname." 090930";

class	systeminfo {
	var	$dbpath = "./master";
	var	$variable;
	function	systeminfo() {
		global	$scriptversion;
		
		$this->variable = array(
			"VERSION" => $scriptversion, 
			"BASEURL" => "", 
			"DEFAULTDATE" => ""
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
}
class	debugsysteminfo extends systeminfo {
}


class	encode	{
	var	$key = "encodecheck";
	var	$string = "“ú–{Œê •¶ Žš —ñ‚Ìtest‚Å‚·B";
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
	function	encodesearch($string) {
		$string = strtolower(mb_convert_kana($string, "askh", $this->host));
		$string = mb_convert_kana($string, "KV", $this->host);
		$string = implode(" ", split("[- \t\r\n+_%*$#@\"|,]+", $string));
		return mb_convert_encoding($string, "UTF-8", $this->host);
	}
}


class	commentinfo {
	function	attach() {
	}
}


$systeminfo =& new systeminfo();
$encode =& new encode();
$commentinfo =& new commentinfo();

include("env.php");
$scripturl = $systeminfo->getscripturl();

$welcomemessage = $systeminfo->get("welcomemessage");

$html_header = <<<EOO
<HTMl><HEAD><TITLE>{$scriptversion}</TITLE></HEAD><BODY>
<H1>{$scriptversion}</H1>

{$welcomemessage}

<HR>
<P><A href="?mode=comment">edit comment table</A>
/ <A href="?mode=sql">manage database</A>
</P>

EOO;


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


class	table {
	var	$db;
	var	$tablename = null;
	function	table(&$db) {
		$this->db =& $db;
	}
	function	create($sql = "") {
		$sql = <<<EOO
create table {$this->tablename} (
	{$sql}
	id	integer primary key
);
EOO;
		return $this->db->directquery($sql);
	}
	function	gethtml($code) {
		switch ($code) {
			case	"tablename":
				return htmlspecialchars($this->tablename);
			case	"errorstring":
				return ($this->gethtml("tablename"))." : ".($this->db->gethtmlerror());
		}
		return "unknown key.";
	}
	function	&getnewrecord() {
		return new record($this);
	}
	function	&getrecord($id) {
		$result = $this->db->query("select * from {$this->tablename} as this where this.id = ?;", array($id + 0));
		if ($result === FALSE)
			die("get record(".($id + 0).") failed.");
		$record =& $this->getnewrecord();
		$record->fields = $result[0];
		return $record;
	}
	function	getrecordlist($command = "", $array = null) {
		if ($array === null)
			$array = array();
		$result = $this->db->query("select this.id from {$this->tablename} as this {$command};", $array);
		if ($result === FALSE)
			return array();
		$list = array();
		foreach ($result as $val)
			$list[] = $val["this.id"] + 0;
		return $list;
	}
	function	insertrecord(&$record) {
		$sql = "insert into {$this->tablename}";
		$sql2 = "";
		$array = array();
		foreach ($record->fields as $key => $val) {
			if ($key == "id")
				continue;
			if (count($array) == 0) {
				$sql .= " (";
				$sql2 .= ") values (";
			} else {
				$sql .= ", ";
				$sql2 .= ", ";
			}
			$sql .= "{$key}";
			$sql2 .= "?";
			$array[] = $val;
		}
		if (count($array) > 0)
			$sql2 .= ")";
		$ret = $this->db->query($sql.$sql2.";", $array);
		if ($ret !== FALSE)
			$record->fields["id"] = $this->db->getlastid();
		return $ret;
	}
	function	updaterecord(&$record) {
		$sql = "update {$this->tablename} set ";
		$array = array();
		foreach ($record->fields as $key => $val) {
			if ($key == "id")
				continue;
			if (count($array) > 0)
				$sql .= ", ";
			$sql .= "{$key} = ?";
			$array[] = $val;
		}
		if (count($array) == 0)
			return TRUE;
		$sql .= " where id = ?;";
		$array[] = $record->fields["id"] + 0;
		return $this->db->query($sql, $array);
	}
	function	deleterecord(&$record) {
		$ret = $this->db->query("delete from {$this->tablename} where id = ?;", array($record->fields["id"] + 0));
		if ($ret === TRUE)
			$record->fields["id"] = -1;
		return $ret;
	}
}


class	record {
	var	$table;
	var	$fields;
	function	record(&$table) {
		$this->table =& $table;
		$this->fields = array("id" => -1);
	}
	function	gethtml($code) {
		$id = htmlspecialchars($this->fields["id"] + 0);
		switch ($code) {
			case	"id":
				return $id;
			case	"hiddenid":
				return "<INPUT type=hidden name=id value={$id}>";
		}
		die("gethtml : unknown code(".htmlspecialchars($code).")");
	}
	function	setquery($code) {
		die("setquery : unknown code(".htmlspecialchars($code).")");
	}
	function	update() {
		if ($this->fields["id"] + 0 < 0)
			return $this->table->insertrecord($this);
		return $this->table->updaterecord($this);
	}
	function	delete() {
		return $this->table->deleterecord($this);
	}
}


class	commenttable	extends	table {
	var	$tablename = "comment";
	function	create($sql = "") {
		$sql .= <<<EOO
	confirm	int, 
	entry	int, 
	date	int, 
	body	text, 
	referer	text, 
	info	text, 
EOO;
		if (($ret = parent::create($sql)) === FALSE)
			return $ret;
		
		$sql = <<<EOO
create index {$this->tablename}_index1 on {$this->tablename} (referer);
EOO;
		if (($ret = $this->db->directquery($sql)) === FALSE)
			return $ret;
		
		$sql = <<<EOO
create index {$this->tablename}_index2 on {$this->tablename} (date, confirm);
EOO;
		if (($ret = $this->db->directquery($sql)) === FALSE)
			return $ret;
		
		$sql = <<<EOO
create index {$this->tablename}_index3 on {$this->tablename} (entry, date, confirm);
EOO;
		return $this->db->directquery($sql);
	}
	function	&getnewrecord() {
		$obj =& new commentrecord($this);
		return $obj;
	}
}


class	commentrecord extends record {
	function	gethtml($code, $option = "") {
		$id = htmlspecialchars(@$this->fields["id"] + 0, ENT_QUOTES);
		$entry = htmlspecialchars(@$this->fields["entry"] + 0, ENT_QUOTES);
		$i = @$this->fields["date"] + 0;
		$date = htmlspecialchars(date("Y/m/d H:i:s", $i), ENT_QUOTES);
		$i = @$this->fields["confirm"] + 0;
		$confirm = htmlspecialchars(sprintf("%d.%d.%d.0", ($i >> 16) & 0xff, ($i >> 8) & 0xff, $i & 0xff), ENT_QUOTES);
		$array = unserialize(@$this->fields["body"]."");
		$name = nl2br(htmlspecialchars(@$array["name"].""));
		$body = nl2br(htmlspecialchars(@$array["body"].""));
		$referer = htmlspecialchars(@$this->fields["referer"]."");
		$info = nl2br(htmlspecialchars(@$this->fields["info"].""));
		switch ($code) {
			case	"tablehead":
				return <<<EOO
<TR><TH>id
	<TH>#
		<TH>date / confirm
			<TH width="30%">name / body
				<TH width="40%">info / referer
EOO;
			case	"table":
				return <<<EOO
<TR><TH>{$id}
	<TD>{$entry}
		<TD>{$date}
		<BR>{$confirm}{$option}
			<TD><B>{$name}</B>
			<BR>{$body}
				<TD>{$info}
				<BR>{$referer}
EOO;
		}
		return parent::gethtml($code);
	}
	function	setquery($code) {
		global	$encode;
		switch ($code) {
		}
		parent::setquery($code);
	}
}


$db =& new database($commentinfo->dbpath);
$commenttable =& new commenttable($db);


switch (@$_GET["mode"]) {
	case	"comment":
		$table =& $commenttable;
		$mode = "comment";
		$html_header .= <<<EOO
<H2>edit {$mode} table</H2>
EOO;
		
		$sql = "";
		$array = array();
		
		if (($filter = @$_GET["filter"] + 0) > 0) {
			$record =& $table->getrecord($filter);
			
			$sql .= " where confirm = ?";
			$array[] = $record->fields["confirm"] + 0;
		} else if ($filter != -1) {
			$sql .= " where confirm > ?";
			$array[] = 1;
		}
		if ($filter == -2) {
			$sql .= " and referer < ? or referer > ?";
			$array[] = "h";
			$array[] = "i";
		}
		
		foreach (@$_POST as $key => $val) {
			if (strlen($val) <= 2)
				continue;
			$s = $sql;
			$a = $array;
			if (($id = substr($key, 1) + 0) > 0) {
				$s = " where id = ?";
				$a = array($id);
			}
			switch (substr($key, 0, 1)) {
				case	"a":
					$db->query("update comment set confirm = 0{$s};", $a);
foreach ($table->getrecordlist($s, $a) as $id) {
	$record =& $table->getrecord($id);
	$a2 = unserialize($record->fields["body"]."");
	mail("kimoto@pa-i.jp", "cedit comment", mb_convert_encoding(mb_convert_kana($a2["name"]."\n\n".$a2["body"], "KV", "EUC-JP"), "ISO-2022-JP", "EUC-JP"), 'Content-Type: text/plain; charset="iso-2022-jp"');
}
					header("Location: {$scripturl}?mode={$mode}");
					die();
				case	"d":
					$db->query("update comment set confirm = 1{$s};", $a);
					header("Location: {$scripturl}?mode={$mode}");
					die();
				case	"r":
					$db->query("delete from comment{$s};", $a);
					header("Location: {$scripturl}?mode={$mode}");
					die();
			}
		}
		$record =& $table->getnewrecord();
		$tablehtml = $record->gethtml("tablehead");
		
		if ($filter >= -2)
			$sql .= " order by this.date desc, this.confirm desc";
		else if ($filter == -3)
			$sql .= " order by referer";
		$sql .= " limit ?";
		$array[] = 1000;
		foreach ($table->getrecordlist($sql, $array) as $id) {
			$record =& $table->getrecord($id);
			$option = <<<EOO
 <A href="?mode={$mode}&filter={$id}">IP filter</A>
<BR><INPUT type=submit name=a{$id} value=allow>
<INPUT type=submit name=d{$id} value=deny>
<INPUT type=submit name=r{$id} value=remove>
EOO;
			$tablehtml .= $record->gethtml("table", $option);
		}
		print <<<EOO
{$html_header}
<FORM method=POST>
<INPUT type=submit name=a0 value="allow all">
<INPUT type=submit name=d0 value="deny all">
<INPUT type=submit name=r0 value="remove all">
<A href="?mode={$mode}&filter=-1">show all comments</A>
<A href="?mode={$mode}&filter=-2">no referer only</A>
<A href="?mode={$mode}&filter=-3">order by referer</A>
<TABLE border width="100%">
{$tablehtml}
</TABLE>
</FORM>
EOO;
		break;
	case	"sql":
		print <<<EOO
{$html_header}
<P>---- <!-- A href="{$commentinfo->dbpath}" download sqlite /A -->
/ <A href="?mode=create">create tables</A>
</P>
EOO;
		if (strlen(@$_POST["sql"]."") > 0) {
			$sql = ereg_replace("[\t\r\n]+", " ", $encode->getpost("sql"));
			$result = $db->directquery($sql);
			if ($result === FALSE)
				print "<P>query : ".($db->gethtmlerror())."</P>\n";
			else if (count($result) <= 0)
				print "<P>updated : ".($db->getupdates())."</P>\n";
			else {
				$first = 1;
				print "<TABLE border>\n";
				foreach ($result as $line) {
					if (($first)) {
						print "<TR>";
						foreach ($line as $key => $val)
							print "<TH>".htmlspecialchars($key);
						print "\n";
						$first = 0;
					}
					print "<TR>";
					foreach ($line as $key => $val)
						print "<TD>".htmlspecialchars($val);
					print "\n";
				}
				print "</TABLE>\n";
			}
		}
		$encodecheck = $encode->gethtml("hiddenstring");
		print <<<EOO
<HR>
<H2>direct query</H2>
<FORM method=post>
{$encodecheck}
<P><TEXTAREA cols=40 rows=10 name=sql>
</TEXTAREA>
<BR><INPUT type=submit></P>
</FORM>
EOO;
		break;
	case	"create":
		print <<<EOO
{$html_header}
<H2>create tables</H2>
EOO;
		$table =& $commenttable;
		$table->create();
		print "<P>".$table->gethtml("errorstring")."</P>\n";
		break;
	default:
		print $html_header;
		break;
}

?>
<HR>
</BODY></HTML>
