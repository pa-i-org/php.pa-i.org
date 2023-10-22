<?php

class	db_commentinfo	extends	commentinfo {
	var	$dbpath = null;
	var	$db = null;
	var	$name = null;
	var	$body = null;
	var	$open_name = '<INPUT type=text name=name size=20 value="';
	var	$close_name = '">';
	var	$open_body = '<TEXTAREA cols=40 rows=10 name=body>';
	var	$close_body = '</TEXTAREA>';
	var	$open_submit = '<INPUT type=submit name="post">';
	var	$close_submit = "";
	function	attach() {
		if ($this->dbpath === null)
			return;
		if ($this->db === null)
			$this->db =& new database($this->dbpath);
	}
	function	query($sql, $array) {
		$this->attach();
		return $this->db->query($sql, $array);
	}
	function	getconfirm() {
		$confirm = 0;
		if (ereg('^([0-9]+)\.([0-9]+)\.([0-9]+)\.([0-9]+)$', @$_SERVER["REMOTE_ADDR"]."", $array)) {
			$confirm = (($array[1] + 0) << 16);
			$confirm |= (($array[2] + 0) << 8);
			$confirm |= ($array[3] + 0);
			if (($confirm & 0x800000) == 0)
				$confirm &= 0xff0000;
			else if (($confirm & 0x400000) == 0)
				$confirm &= 0xffff00;
		}
		if ($confirm == 0)
			$confirm = 1;
		return $confirm;
	}
	function	checkcommentpost() {
		global	$systeminfo;
		global	$encode;
		global	$accesslog;
		
		$entry = @$_GET["comment"] + 0;
		if (@$accesslog !== null)
			$accesslog->msg .= "comment".$entry;
		if (@$_POST["post"] === null)
			return "noinput";
		if (trim(@$_POST["name"]."") == "")
			return "noname";
		if (trim(@$_POST["body"]."") == "")
			return "nobody";
		
		$confirm = $this->getconfirm();
		$date = time();
		$body = serialize(array(
			"name" => trim($encode->getpost("name")),
			"body" => trim($encode->getpost("body"))
		));
		
		$info = @$_SERVER["REMOTE_ADDR"]."";
		$info .= ":".(@$_SERVER["REMOTE_PORT"]."");
		$info .= "\n".(@$_SERVER["HTTP_USER_AGENT"]."");
		
		$referer = @$_SERVER["HTTP_REFERER"]."";
		
		if (strlen($body.$info.$referer) > 2048)
			return "toolong";
		
		$array = $this->query("select count(*) from comment where confirm = ?;", array($confirm));
		if (@$array[0]["count(*)"] >= 100)
			return "manypost";
		
		$this->query("insert into comment(confirm, entry, date, body, referer, info) values(?, ?, ?, ?, ?, ?);", array($confirm, $entry, $date, $body, $referer, $info));
		
		header("Location: ".$systeminfo->getscripturl()."#{$entry}");
		die();
	}
	# 'CM_' will add
	function	getcommentlist($entry = -1, $order = -10) {
		
		$sql = "select * from comment where ((confirm = ?) or (confirm = 0))";
		$array = array($this->getconfirm());
		if ($entry >= 0) {
			$sql .= " and entry = ?";
			$array[] = $entry + 0;
		}
		$sql .= " order by date";
		if ($order < 0) {
			$sql .= " desc limit ?";
			$array[] = (0 - $order);
		} else if ($order > 0) {
			$sql .= " limit ?";
			$array[] = $order + 0;
		}
		$sql .= ";";
		
		$list = $this->query($sql, $array);
		if (count($list) <= 0)
			return array();
		$result = array();
		foreach ($list as $array) {
			$array2 = unserialize(@$array["body"]."");
			$result[] = array(
				"entry" => @$array["entry"] + 0, 
				"date" => date("m/d H:i", @$array["date"] + 0), 
				"name" => @$array2["name"]."", 
				"body" => @$array2["body"].""
			);
		}
		return $result;
	}
	# 'CM_' will add
	function	getformvariable() {
		global	$encode;
		
		$name = $body = "";
		if (@$_POST["body"] !== null) {
			$name = htmlspecialchars($encode->getpost("name"), ENT_QUOTES);
			$body = htmlspecialchars($encode->getpost("body"), ENT_QUOTES);
		}
		return array(
			"entry" => @$_GET["comment"] + 0,
			"name" => $this->open_name.$name.$this->close_name, 
			"body" => $this->open_body.$body.$this->close_body, 
			"submit" => $this->open_submit.$this->close_submit, 
			"" => '', 
			"formopen" => '<FORM method=POST style="margin: 0em;">'.$encode->gethtml("hiddenstring"), 
			"formclose" => '</FORM>'
		);
	}
}

$commentinfo =& new db_commentinfo();

?>
