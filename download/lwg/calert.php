<?php
$scriptname = "calert.php";

#$mailcmd = 'Mail -s "localhost: comment" root@localhost.localnet';
$mailcmd = 'cat -';
$dblist = array(
	"main" => "comment.sq2"
);



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


$message = array();
foreach ($dblist as $key => $dbpath) {
	$db =& new database($dbpath);
	$result = $db->query("select * from comment where confirm > ? order by date desc, confirm desc limit ?;", array(1, 50));
	foreach ($result as $record) {
#		$id = htmlspecialchars(@$record["id"] + 0, ENT_QUOTES);
#		$entry = htmlspecialchars(@$record["entry"] + 0, ENT_QUOTES);
#		$i = @$record["date"] + 0;
#		$date = htmlspecialchars(date("Y/m/d H:i:s", $i), ENT_QUOTES);
#		$i = @$record["confirm"] + 0;
#		$confirm = htmlspecialchars(sprintf("%d.%d.%d.0", ($i >> 16) & 0xff, ($i >> 8) & 0xff, $i & 0xff), ENT_QUOTES);
#		$array = unserialize(@$record["body"]."");
#		$name = nl2br(htmlspecialchars(@$array["name"].""));
#		$body = nl2br(htmlspecialchars(@$array["body"].""));
#		$referer = htmlspecialchars(@$record["referer"]."");
#		$info = nl2br(htmlspecialchars(@$record["info"].""));
		
		$array = unserialize(@$record["body"]."");
		
		$s = trim(@$array["body"]."");
		$body = "";
		for ($i=0; $i<strlen($s); $i++) {
			if (($code = ord($c = substr($s, $i, 1))) < 0x20)
				continue;
			if ($code < 0x80)
				$body .= $c;
			else if (ord($c2 = substr($s, ++$i, 1)) < 0x80)
				;
			else
				$body .= $c.$c2;
			if (strlen($body) > 60)
				break;
		}
		@$message[$key] .= "- ".mb_convert_encoding($body, "ISO-2022-JP", "EUC-JP")."\n";
	}
}

$body = "";
foreach ($message as $key => $val)
	$body .= "** ".$key."\n".$val."\n";

if ($body != "") {
	$fp = popen($mailcmd, "w");
	fputs($fp, $body);
	pclose($fp);
}

?>
