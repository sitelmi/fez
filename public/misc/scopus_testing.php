<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | Fez - Digital Repository System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2005, 2006, 2007 The University of Queensland,         |
// | Australian Partnership for Sustainable Repositories,                 |
// | eScholarship Project                                                 |
// |                                                                      |
// | Some of the Fez code was derived from Eventum (Copyright 2003, 2004  |
// | MySQL AB - http://dev.mysql.com/downloads/other/eventum/ - GPL)      |
// |                                                                      |
// | This program is free software; you can redistribute it and/or modify |
// | it under the terms of the GNU General Public License as published by |
// | the Free Software Foundation; either version 2 of the License, or    |
// | (at your option) any later version.                                  |
// |                                                                      |
// | This program is distributed in the hope that it will be useful,      |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the        |
// | GNU General Public License for more details.                         |
// |                                                                      |
// | You should have received a copy of the GNU General Public License    |
// | along with this program; if not, write to:                           |
// |                                                                      |
// | Free Software Foundation, Inc.                                       |
// | 59 Temple Place - Suite 330                                          |
// | Boston, MA 02111-1307, USA.                                          |
// +----------------------------------------------------------------------+
// | Authors: Andrew Martlew <a.martlew@library.uq.edu.au>                |
// +----------------------------------------------------------------------+

include_once dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR.'config.inc.php';
include_once(APP_INC_PATH . 'class.scopus.php');
include_once(APP_INC_PATH . "class.record.php");

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<title>Scopus Testing</title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
	<style type="text/css">
	body {
		font:80%/1 sans-serif;
	}
	fieldset {
		padding: 1em;
		font:80%/1 sans-serif;
	}
	label {
		float:left;
		width:100px;
		margin-right:0.5em;
		padding-top:0.2em;
		text-align:right;
		font-weight:bold;
	}	
	</style>
</head>
<body>
<h1>Scopus Testing</h1>

<p>Retrieve's the CitedByCount from Scopus.</p>
<form action="" method="get">

<fieldset>
<legend>Search Options</legend>
<label for="value">Value</label>
<input type="text" size="25" name="value" />
<br /><br />
<label for="type">Meta-Data Type:</label>
<select name="type">
	<option value="doi">DOI</option>
	<option value="eid" selected>EID</option>
	<option value="scopusid">Scopus ID</option>
</select>
<br /><br />
<input type="submit" name="Submit" value="Retrieve" />
</fieldset>

</form>
<pre>
<?php
if(isset($_GET['value'])) {
	$input_keys = array( '1' => array($_GET['type'] => $_GET['value']));
	print_r(Scopus::getCitedByCount($input_keys));
}
?>
</body>
</html>
