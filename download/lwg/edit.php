<?php
$scriptname = "edit.php";
$scriptversion = $scriptname." 090915";

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
	function        putheader($type = "text/html") {
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
<P><A href="{$scripturl}?mode=genre">edit genre table</A>
/ <A href="{$scripturl}?mode=content">edit content table</A>
/ <A href="{$scripturl}?mode=sql">manage database</A>
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
		$obj =& new record($this);
		return $obj;
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


class	genretable	extends	table {
	var	$tablename = "genre";
	function	create($sql = "") {
		$sql .= <<<EOO
	key	varchar(16) unique, 
	title	varchar(64), 
	pos	int, 
	body	text, 
	config	text, 
EOO;
		return parent::create($sql);
	}
	function	&getnewrecord() {
		$obj =& new genrerecord($this);
		return $obj;
	}
}


class	genrerecord extends record {
	function	gethtml($code) {
		$id = htmlspecialchars(@$this->fields["id"] + 0, ENT_QUOTES);
		$key = htmlspecialchars(@$this->fields["key"]."", ENT_QUOTES);
		$title = htmlspecialchars(@$this->fields["title"]."", ENT_QUOTES);
		$pos = htmlspecialchars(@$this->fields["pos"] + 0, ENT_QUOTES);
		$body = htmlspecialchars(@$this->fields["body"]."");
		$config = htmlspecialchars(@$this->fields["config"]."");
		switch ($code) {
			case	"option_key":
				return <<<EOO
<OPTION value="{$key}">{$key} : {$title}</OPTION>
EOO;
			case	"helpmessage":
				return nl2br($config);
			case	"tablehead":
				return <<<EOO
<TR><TH>id
	<TH>key
		<TH>title
			<TH>pos
EOO;
			case	"table":
				return <<<EOO
<TR><TD>{$id}
	<TD>{$key}
		<TD>{$title}
			<TD>{$pos}
EOO;
			case	"editform":
				return <<<EOO
<TR><TD align=right>
	key : <INPUT type=text name=key value="{$key}" size=60>
	<BR>title : <INPUT type=text name=title value="{$title}" size=60>
	<BR>pos : <INPUT type=text name=pos value="{$pos}" size=60>
<TR><TD>body :
	<BR><TEXTAREA cols=64 rows=20 name=body wrap=soft>{$body}</TEXTAREA>
<TR><TD>config :
	<BR><TEXTAREA cols=64 rows=20 name=config wrap=soft>{$config}</TEXTAREA>
EOO;
			case	"createform":
				return <<<EOO
<TR><TD>
	key : <INPUT type=text name=key value="{$key}" size=60>
EOO;
		}
		return parent::gethtml($code);
	}
	function	setquery($code) {
		global	$encode;
		switch ($code) {
			case	"editform":
			case	"createform":
				$this->fields["key"] = $encode->getpost("key");
				$this->fields["title"] = $encode->getpost("title");
				$this->fields["pos"] = $encode->getpost("pos");
				$this->fields["body"] = $encode->getpost("body");
				$this->fields["config"] = $encode->getpost("config");
				return;
		}
		parent::setquery($code);
	}
	function	getsearchkeylist() {
		if (!ereg("(^|\r|\n)#searchkey[ \t]+([a-zA-Z0-9_ \t]*)(\r|\n|$)", $this->fields["body"], $array))
			return array();
		return split("[ \t]+", $array[2]);
	}
}


class	contenttable	extends	table {
	var	$tablename = "content";
	function	create($sql = "") {
		$sql .= <<<EOO
	genre	varchar(64), 
	title	varchar(256), 
	type	varchar(256), 
	version	varchar(256), 
	date	varchar(32), 
	show	int, 
	par0	varchar(256), 
	par1	varchar(256), 
	par2	varchar(256), 
	par3	varchar(256), 
	lead	text, 
	body	text, 
	searchbody	text, 
EOO;
		if (($ret = parent::create($sql)) === FALSE)
			return $ret;
		
		$sql = <<<EOO
create index {$this->tablename}_index1 on {$this->tablename} (genre);
EOO;
		return $this->db->directquery($sql);
	}
	function	&getnewrecord() {
		$obj =& new contentrecord($this);
		return $obj;
	}
	function	uploadfile($fn) {
		global	$encode;
		
		$text = file_get_contents($fn) or die("file_get_contents failed.");
		$text = mb_convert_encoding($text, $encode->host, $encode->list);
		foreach (explode("\014", $text) as $page) {
			$fields = array();
			$mode = "";
			foreach (split("\r|\n|\r\n", $page) as $line) {
				$cmd = split("\t| +", $line, 2);
				switch ($cmd[0]) {
					case	"*":
						if ($mode != "") {
							@$fields[$mode] .= $line."\n";
							continue 2;
						}
						$cmd[0] = "#title";
					case	"#genre":
					case	"#type":
					case	"#version":
					case	"#date":
					case	"#show":
					case	"#par0":
					case	"#par1":
					case	"#par2":
					case	"#par3":
						$fields[substr($cmd[0], 1)] = @$cmd[1]."";
						$mode = "";
						continue 2;
					case	"#lead":
					case	"#body":
						$mode = substr($cmd[0], 1);
						continue 2;
					case	"#end":
						if (!ereg('^(// tagid=[0-9a-zA-Z]+;)', @$fields["body"], $array))
							die("no '// tagid=0000ffff;' in top of body : ".htmlspecialchars($fields["title"]));
						
						$result = $this->db->query("select this.id from {$this->tablename} as this where this.body like ?;", array($array[1]."%"));
						if (count($result) > 0)
							$record =& $this->getrecord($result[0]["this.id"]);
						else
							$record =& $this->getnewrecord();
						foreach ($fields as $key => $val)
							$record->fields[$key] = $val;
#						print_r($record->fields);
						$record->updatesearchbody();
						$record->update();
						break;
					default:
						if ($mode != "")
							@$fields[$mode] .= $line."\n";
						continue 2;
				}
				break;
			}
		}
	}
}


class	contentrecord extends record {
	function	gethtml($code) {
		global	$genretable, $systeminfo;
		
		$id = htmlspecialchars(@$this->fields["id"] + 0, ENT_QUOTES);
		$genre = htmlspecialchars(@$this->fields["genre"]."", ENT_QUOTES);
		$title = htmlspecialchars(@$this->fields["title"]."", ENT_QUOTES);
		$type = htmlspecialchars(@$this->fields["type"]."", ENT_QUOTES);
		$version = htmlspecialchars(@$this->fields["version"]."", ENT_QUOTES);
		$date = htmlspecialchars(@$this->fields["date"]."", ENT_QUOTES);
		$show = (@$this->fields["show"] + 0 > 0)? "on" : "off";
		$show_checked = (@$this->fields["show"] + 0 > 0)? "checked" : "";
		$par0 = htmlspecialchars(@$this->fields["par0"]."", ENT_QUOTES);
		$par1 = htmlspecialchars(@$this->fields["par1"]."", ENT_QUOTES);
		$par2 = htmlspecialchars(@$this->fields["par2"]."", ENT_QUOTES);
		$par3 = htmlspecialchars(@$this->fields["par3"]."", ENT_QUOTES);
		$lead = htmlspecialchars(@$this->fields["lead"]."");
		$body = htmlspecialchars(@$this->fields["body"]."");
		switch ($code) {
			case	"tablehead":
				return <<<EOO
<TR><TH>id
	<TH>genre
		<TH>title
			<TH>type
				<TH>version
					<TH>date
						<TH>show
							<TH>par0
								<TH>par1
									<TH>par2
										<TH>par3
EOO;
			case	"table":
				return <<<EOO
<TR><TD>{$id}
	<TD>{$genre}
		<TD>{$title}
			<TD>{$type}
				<TD>{$version}
					<TD>{$date}
						<TD>{$show}
							<TD>{$par0}
								<TD>{$par1}
									<TD>{$par2}
										<TD>{$par3}
EOO;
			case	"editform":
				return <<<EOO
<TR><TD align=right>
	genre : <INPUT type=text name=genre value="{$genre}" size=30>
	<BR>title : <INPUT type=text name=title value="{$title}" size=30>
	<BR>type : <INPUT type=text name=type value="{$type}" size=30>
	<BR>version : <INPUT type=text name=version value="{$version}" size=30>
	<BR>date : <INPUT type=text name=date value="{$date}" size=30>
<TD align=right>
	<INPUT type=checkbox name=show value="1" {$show_checked}> show
	<BR>par0 : <INPUT type=text name=par0 value="{$par0}" size=30>
	<BR>par1 : <INPUT type=text name=par1 value="{$par1}" size=30>
	<BR>par2 : <INPUT type=text name=par2 value="{$par2}" size=30>
	<BR>par3 : <INPUT type=text name=par3 value="{$par3}" size=30>
<TR><TD colspan=2>lead :
	<BR><TEXTAREA cols=64 rows=20 name=lead wrap=soft>{$lead}</TEXTAREA>
<TR><TD colspan=2>body :
	<BR><TEXTAREA cols=64 rows=20 name=body wrap=soft>{$body}</TEXTAREA>
EOO;
			case	"createform":
				$optionlist = "";
				foreach ($genretable->getrecordlist("where this.key != ? order by this.pos", array("")) as $id) {
					$record =& $genretable->getrecord($id);
					$optionlist .= $record->gethtml("option_key");
				}
				$date = htmlspecialchars($systeminfo->variable["DEFAULTDATE"], ENT_QUOTES);
				return <<<EOO
<TR><TD>
	genre : <SELECT name=genre size=0 value="{$genre}">{$optionlist}</SELECT>
<INPUT type=hidden name=date value="{$date}">
EOO;
		}
		return parent::gethtml($code);
	}
	function	setquery($code) {
		global	$encode;
		switch ($code) {
			case	"editform":
			case	"createform":
				$this->fields["genre"] = $encode->getpost("genre");
				$this->fields["title"] = $encode->getpost("title");
				$this->fields["type"] = $encode->getpost("type");
				$this->fields["version"] = $encode->getpost("version");
				$this->fields["date"] = $encode->getpost("date");
				$this->fields["show"] = @$_POST["show"] + 0;
				$this->fields["par0"] = $encode->getpost("par0");
				$this->fields["par1"] = $encode->getpost("par1");
				$this->fields["par2"] = $encode->getpost("par2");
				$this->fields["par3"] = $encode->getpost("par3");
				$this->fields["lead"] = $encode->getpost("lead");
				$this->fields["body"] = $encode->getpost("body");
				$this->updatesearchbody();
				return;
		}
		parent::setquery($code);
	}
	function	updatesearchbody() {
		global	$genretable, $encode;
		
		$array = $genretable->getrecordlist("where this.key = ?", array($this->fields["genre"]));
		if (count($array) != 1)
			return "";
		$genrerecord =& $genretable->getrecord($array[0]);
		$this->fields["searchbody"] = "";
		foreach ($genrerecord->getsearchkeylist() as $key) {
			if (!ereg("^DB_(.*)$", $key, $array))
				continue;
			$this->fields["searchbody"] .= $encode->encodesearch(@$this->fields[$array[1]]." ");
		}
	}
}


$db =& new database($systeminfo->dbpath);
$commentinfo->attach();
$genretable =& new genretable($db);
$contenttable =& new contenttable($db);

$encode->putheader();

switch (@$_GET["mode"]) {
	case	"genre":
		$table =& $genretable;
		$mode = "genre";
		$html_header .= <<<EOO
<H2>edit genre table</H2>
EOO;
		if (($id = @$_REQUEST["id"]) === null)
			$id = -1;
		$id += 0;
		switch (@$_POST["action"]) {
			case	"delete":
				$record =& $table->getrecord($id);
				$record->delete();
				header("Location: {$scripturl}?mode={$mode}");
				die();
			case	"update":
				$record =& $table->getrecord($id);
				$record->setquery("editform");
				$record->update();
				header("Location: {$scripturl}?mode={$mode}");
				die();
			case	"saveas":
				$record =& $table->getnewrecord();
				$record->setquery("editform");
				$record->update();
				header("Location: {$scripturl}?mode={$mode}");
				die();
			case	"new":
				$record =& $table->getnewrecord();
				$record->setquery("createform");
				$record->update();
				$id = $record->gethtml("id");
				header("Location: {$scripturl}?mode={$mode}&id={$id}");
				die();
		}
		if ($id >= 0) {
			$record =& $table->getrecord($id);
			$hiddenid = $record->gethtml("hiddenid");
			$encodecheck = $encode->gethtml("hiddenstring");
			$editform = $record->gethtml("editform");
			print <<<EOO
{$html_header}
<FORM method=POST action="{$scripturl}?mode={$mode}">
{$hiddenid}
{$encodecheck}
<TABLE border>
{$editform}
</TABLE>
<P><INPUT type=radio name=action value=update checked>update this record
<INPUT type=radio name=action value=saveas>create new record
---- <INPUT type=submit></P>
</FORM>
<HR>
<FORM method=POST action="{$scripturl}?mode={$mode}">
{$hiddenid}
<P><INPUT type=checkbox name=action value=delete>delete
---- <INPUT type=submit></P>
</FORM>
EOO;
			break;
		}
		$record =& $table->getnewrecord();
		$createform = $record->gethtml("createform");
		$tablehtml = $record->gethtml("tablehead");
		foreach ($table->getrecordlist("order by this.pos, this.key") as $id) {
			$record =& $table->getrecord($id);
			$tablehtml .= $record->gethtml("table");
			$tablehtml .= <<<EOO
<TD><A href="{$scripturl}?mode={$mode}&id={$id}">edit</A>
EOO;
		}
		$encodecheck = $encode->gethtml("hiddenstring");
		print <<<EOO
{$html_header}
<FORM method=POST action="{$scripturl}?mode={$mode}">
{$encodecheck}
<INPUT type=hidden name=action value=new>
<TABLE border>
{$createform}
<TD><INPUT type=submit value=create>
</TABLE>
</FORM>
<TABLE border>
{$tablehtml}
</TABLE>
EOO;
		break;
	case	"content":
		$table =& $contenttable;
		$mode = "content";
		$html_header .= <<<EOO
<H2>edit content table</H2>
EOO;
		if (($id = @$_REQUEST["id"]) === null)
			$id = -1;
		$id += 0;
		switch (@$_POST["action"]) {
			case	"delete":
				$record =& $table->getrecord($id);
				$record->delete();
				header("Location: {$scripturl}?mode={$mode}");
				die();
			case	"update":
				$record =& $table->getrecord($id);
				$record->setquery("editform");
				$record->update();
				header("Location: {$scripturl}?mode={$mode}");
				die();
			case	"saveas":
				$record =& $table->getnewrecord();
				$record->setquery("editform");
				$record->update();
				header("Location: {$scripturl}?mode={$mode}");
				die();
			case	"new":
				$record =& $table->getnewrecord();
				$record->setquery("createform");
				$record->update();
				$id = $record->gethtml("id");
				header("Location: {$scripturl}?mode={$mode}&id={$id}");
				die();
			case	"upload":
				$fn = @$_FILES["fl"]["tmp_name"];
				if (is_uploaded_file($fn) === TRUE)
					$table->uploadfile($fn);
				header("Location: {$scripturl}?mode={$mode}");
				die();
		}
		if ($id >= 0) {
			$array = $genretable->getrecordlist(", content where this.key = content.genre and content.id = ?", array($id));
			$helpmessage = "";
			if (count($array) == 1) {
				$genrerecord =& $genretable->getrecord($array[0]);
				$helpmessage = $genrerecord->gethtml("helpmessage");
			}
			$record =& $table->getrecord($id);
			$encodecheck = $encode->gethtml("hiddenstring");
			$hiddenid = $record->gethtml("hiddenid");
			$editform = $record->gethtml("editform");
			print <<<EOO
{$html_header}
<FORM method=POST action="{$scripturl}?mode={$mode}">
{$hiddenid}
{$encodecheck}
<TABLE border>
<TR><TD colspan=2>{$helpmessage}
{$editform}
</TABLE>
<P><INPUT type=radio name=action value=update checked>update this record
<INPUT type=radio name=action value=saveas>create new record
---- <INPUT type=submit></P>
</FORM>
<HR>
<FORM method=POST action="{$scripturl}?mode={$mode}">
{$hiddenid}
<P><INPUT type=checkbox name=action value=delete>delete
---- <INPUT type=submit></P>
EOO;
			break;
		}
		
		$record =& $table->getnewrecord();
		$createform = $record->gethtml("createform");
		$tablehtml = $record->gethtml("tablehead");
		foreach ($table->getrecordlist("order by this.genre, this.id") as $id) {
			$record =& $table->getrecord($id);
			$tablehtml .= $record->gethtml("table");
			$tablehtml .= <<<EOO
<TD><A href="{$scripturl}?mode={$mode}&id={$id}">edit</A>
EOO;
		}
		$encodecheck = $encode->gethtml("hiddenstring");
		print <<<EOO
{$html_header}

<FORM method=POST action="{$scripturl}?mode={$mode}" enctype="multipart/form-data">
<INPUT type=hidden name=action value=upload>
update from textfile: <INPUT type=file name=fl>
<INPUT type=submit value=upload>
</FORM>

<FORM method=POST action="{$scripturl}?mode={$mode}">
{$encodecheck}
<INPUT type=hidden name=action value=new>
<TABLE border>
{$createform}
<TD><INPUT type=submit value=create>
</TABLE>
</FORM>
<TABLE border>
{$tablehtml}
</TABLE>
EOO;
		break;
	case	"sql":
		print <<<EOO
{$html_header}
<P>---- <A href="{$systeminfo->dbpath}">download sqlite</A>
/ <A href="{$scripturl}?mode=updatesearch">update all search-key</A>
/ <A href="{$scripturl}?mode=create">create tables</A>
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
		$table =& $genretable;
		$table->create();
		print "<P>".$table->gethtml("errorstring")."</P>\n";
		$table =& $contenttable;
		$table->create();
		print "<P>".$table->gethtml("errorstring")."</P>\n";
		break;
	case	"updatesearch":
		print <<<EOO
{$html_header}
<H2>update all search-key</H2>
EOO;
		$count = 0;
		$db->lock(1);
		foreach ($contenttable->getrecordlist() as $id) {
			$record =& $contenttable->getrecord($id);
			$record->updatesearchbody();
			$record->update();
			$count++;
		}
		$db->lock(0);
		print "<P>update {$count} records.</P>";
		break;
	default:
		print $html_header;
		break;
}

?>
<HR>
</BODY></HTML>
