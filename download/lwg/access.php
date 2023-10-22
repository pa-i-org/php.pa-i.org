<?php

class	accesslog {
	var	$dbpath = null;
	var	$db = null;
	var	$botagentlist;
	var	$msg = "";
	function	accesslog() {
		$this->botagentlist = array("http://", "^Yandex");
	}
	function	attach() {
		global	$systeminfo;
		
		if ($this->dbpath === null)
			return;
		if ($this->db === null)
			$this->db =& new database($this->dbpath);
	}
	function	query($sql, $array) {
		$this->attach();
		return $this->db->query($sql, $array);
	}
	function	getlogid($entry = "(none)") {
		$this->attach();
		$this->db->query("update access set uaip = ? where uaip not null and updated < ?;", array(null, time() - 900));
#		$this->db->query("update access set uaip = ? where uaip not null and updated < ?;", array(null, time() - 15));
		
		$ip = @$_SERVER["REMOTE_ADDR"]."";
		if (count($array = explode(".", $ip)) != 4)
			$ipg = $ip;
		else if ($array[0] + 0 < 128)
			$ipg = $array[0].".";
		else if ($array[0] + 0 < 192)
			$ipg = $array[0].".".$array[1].".";
		else
			$ipg = $array[0].".".$array[1].".".$array[2].".";
		
		$ua = substr(@$_SERVER["HTTP_USER_AGENT"]."", 0, 96).".";
		$id = -1;
		$updated = time();
		$body = "";
		if (count($result = $this->db->query("select id, updated, body from access where uaip = ?;", array($ua.$ip))) > 0) {
			$id = $result[0]["id"] + 0;
			$updated = $result[0]["updated"] + 0;
			$body = $result[0]["body"]."";
		} else if (count($result = $this->db->query("select id, updated, body from access where uaip = ?;", array($ua.$ipg))) > 0) {
			$id = $result[0]["id"] + 0;
			$updated = $result[0]["updated"] + 0;
			$body = $result[0]["body"]."";
		} else if (count($result = $this->db->query("select id, updated, body from access where uaip like ?;", array($ua.$ipg."%"))) > 0) {
			$id = $result[0]["id"] + 0;
			$updated = $result[0]["updated"] + 0;
			$body = $result[0]["body"]."";
			$this->db->query("update access set uaip = ? where id = ?;", array($ua.$ipg, $id));
		}
		if ($id < 0) {
			$referer = @$_SERVER["HTTP_REFERER"]."";
			$useragent = @$_SERVER["HTTP_USER_AGENT"]."";
			foreach ($this->botagentlist as $s)
				if (ereg($s, $useragent)) {
					$referer = "(robot)";
					break;
				}
			$this->db->query("insert into access(uaip, updated, referer, entry, entrytime, body) values(?, ?, ?, ?, ?, ?);", array($ua.$ip, $updated, $referer, $entry, $updated, ""));
			$id = $this->db->getlastid();
		}
		return array($id, $body, $updated);
	}
	function	shutdown() {
		global	$systeminfo;
		global	$variable;
		
		$directoryarray = $systeminfo->getdirectorylist($variable->get("LINKEXTENSION"));
		$this->attach();
		$this->db->lock(1);
		list($id, $body, $updated) = $this->getlogid(implode("/", $directoryarray));
#		if (($logcode = $variable->get("LOGCODE")) === null) {
#			if (count($directoryarray) < 2)
#				$logcode = "";
#			else
#				$logcode = $directoryarray[0];
#		}
#		if ($logcode != "") {
#			$array = array();
#			foreach (explode(",", $body) as $chunk)
#				if (count($a = explode("=", $chunk)) == 2)
#					$array[$a[0]] = $a[1] + 0;
#			@$array[$logcode]++;
#			$body = "";
#			foreach ($array as $key => $val)
#				$body .= "{$key}={$val},";
#		}
		$newtime = time();
		$t = $newtime - $updated;
		if ($body == "")
			$t = date("Y/m/d H:i:s", $updated);
		if (strlen($body) >= 1000)
			;
		else if ($this->msg != "")
			$body .= "({$t})[".$this->msg."]";
		else if (($s = @$_POST["search"]) !== null)
			$body .= "({$t})[search]";
		else
			$body .= "({$t})".implode("/", $directoryarray);
		
		$this->db->query("update access set updated = ?, body = ? where id = ?;", array($newtime, $body, $id));
		
		$this->db->lock(0);
	}
	function	inputandredirect($name, $pattern = '.+', $nexturl = "") {
		global	$encode;
		
		if (@$_POST[$name] === null)
			return;
		$s = $encode->getpost($name);
		if (!ereg($pattern, $s))
			return;
		$this->attach();
		$this->db->lock(1);
		list($id, $body, $updated) = $this->getlogid("(form)");
		if (strlen($body < 1500)) {
			$newtime = time();
			$body .= "(".($newtime - $updated).")";
			$s = "{$name}:{$s}";
			$body .= "[".implode("_", split('[^-:.@/0-9A-Za-z]+', substr($s, 0, 128)))."]";
			$this->db->query("update access set updated = ?, body = ? where id = ?;", array($newtime, $body, $id));
		}
		$this->db->lock(0);
		header("Location: {$nexturl}");
		die();
	}
}

$accesslog =& new accesslog();
$systeminfo->func_shutdown[] =& $accesslog;

?>
