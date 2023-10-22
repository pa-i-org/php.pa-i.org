<?php

# dl("../sqlite.so");			# for dynamic loading
# $systeminfo =& new debugsysteminfo();	# enable debug information in HTML-comment
# $scriptname = "index.php";		# enable and change if you use like "index.php5"

$systeminfo->variable["welcomemessage"] = <<<EOO
 This is local version.
EOO;
					# variables setting

$systeminfo->dbpath = "master.sq2";	# "master.sq2" means "./master.sq2"
$systeminfo->variable["BASEURL"] = "http://localhost/pai_org/php/";
					# where "index.php" exists
$systeminfo->variable["IMAGEURL"] = $systeminfo->variable["BASEURL"]."image/";
$systeminfo->variable["DEFAULTDATE"] = date("Y/m/d");
					# default date field in edit.php

$encode->sethost("EUC-JP");		# encoding

					# if redirector exist
#function	convertglobalurl($href) {
#	if ((preg_match('%^https?://localhost\.localnet/%', $href)))
#		return $href;
#	return "../redirect.php?".urlencode($href);
#}

					# if use comment function
# include("comment.php");
# $commentinfo->dbpath = 'comment.sq2';

					# if use access-log function
# include("access.php");
# $accesslog->dbpath = 'access.sq2';

die('<H1>please setup "env.php" file.</H1>');	# delete this line after setup.

?>
