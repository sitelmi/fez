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
// | Authors: Lachlan Kuhn <l.kuhn@library.uq.edu.au>                     |
// +----------------------------------------------------------------------+

include_once(APP_INC_PATH . "class.error_handler.php");
include_once(APP_INC_PATH . "class.setup.php");
include_once(APP_INC_PATH . "class.misc.php");
require_once(APP_INC_PATH . "nusoap.php");
include_once(APP_PEAR_PATH . "/HTTP/Request.php");
require_once(APP_INC_PATH . "class.fedora_direct_access.php");
include_once(APP_INC_PATH . "class.dsresource.php");

class Fedora_API {

	/**
	 * If we can produce a non-Fedora equivalent of all the functions in the Fedora class,
	 * we'll be at least some way towards removing Fedora, without having to impose too
	 * many heinous changes in the rest of the codebase.
	 */

	/**
	 *
	 * @access  public
	 * @return  void (Logs an error message)
	 */
	function checkFedoraServer()
	{
		$log = FezLog::get();

		$ch = curl_init(APP_BASE_FEDORA_APIA_DOMAIN."/describe");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		if (APP_HTTPS_CURL_CHECK_CERT == "OFF" && APP_FEDORA_APIA_PROTOCOL_TYPE == 'https://')  {
			curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);
		}
		$results = curl_exec($ch);
		$info = curl_getinfo($ch);
		curl_close ($ch);
		if ($info['http_code'] == '200') {
			//proceed!!
		} else {
			$errMsg = 'It appears that the content repository server is down, Please try again later.';
			$log->err(array($errMsg, $info, __FILE__, __LINE__));
		}
	}

	/**
	 * Opens a given URL and reads it into a variable and returns the string variable.
	 *
	 * @access  public
	 * @param string $ur1 The URL of the website to read
	 * @return  string $result The URL in text
	 */
	function URLopen($url)
	{
		// Fake the browser type
		ini_set('user_agent','MSIE 4\.0b2;');
		$i = 0;
		do {
			$dh = fopen("$url",'r');
			$i++;
		} while ($i < 2 && $dh !== FALSE); // RCA - give up after three attempts
		if (!$dh) return;
		$result = "";
		$temp_result = "";
		while ($temp_result = fread($dh,8192)) {
			$result .= $temp_result;
		}
		fclose($dh);
		return $result;
	}

	/**
	 * REPLACED
	 * Gets the next available persistent identifier.
	 *
	 * @access  public
	 * @return  string $pid The next avaiable PID in from the PID handler
	 */
	function getNextPID($fedoraBypass = true)
	{
		$log = FezLog::get();
		$db = DB_API::get();

		if ($fedoraBypass) {

			$stmt = "UPDATE " . APP_TABLE_PREFIX . "pid_index SET pid_number = pid_number + 1;";
			$db->exec($stmt);

			$stmt = "SELECT pid_number FROM " . APP_TABLE_PREFIX . "pid_index;";
			try {
				$res = $db->fetchCol($stmt);
			}
			catch(Exception $ex) {
				$log->err("Problem generating new PID :: " . $ex);
				return false;
			}
			$pid = APP_PID_NAMESPACE . ":" . $res[0];

		} else {

			$pid = false;
			$getString = APP_SIMPLE_FEDORA_APIM_DOMAIN."/objects/nextPID?format=xml";
			$ch = curl_init($getString);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			if (APP_HTTPS_CURL_CHECK_CERT == "OFF" && APP_FEDORA_APIA_PROTOCOL_TYPE == 'https://')  {
				curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);
				curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);
			}

			curl_setopt($ch, CURLOPT_USERPWD, APP_FEDORA_USERNAME.":".APP_FEDORA_PWD);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

			curl_setopt($ch, CURLOPT_POSTFIELDS, array('format' => "xml"));
			$results = curl_exec($ch);

			if ($results) {
				$info = curl_getinfo($ch);
				curl_close ($ch);
				$xml = $results;
				$dom = @DomDocument::loadXML($xml);
				if (!$dom) {
					$log->err(array("Problem getting PID from fedora.",$xml,$info,__FILE__,__LINE__));
					return false;
				}
				$result = $dom->getElementsByTagName("pid");
				foreach($result as $item) {
					$pid = $item->nodeValue;
					break;
				}
			} else {
				$log->err(array(curl_error($ch),__FILE__,__LINE__));
				curl_close ($ch);
			}
		}

		return $pid;
	}

	/**
	 * Gets the XML of a given object by PID.
	 *
	 * Developer Note: This function is not actually used for anything just yet, but might be in future releases.
	 *
	 * @access  public
	 * @param string $pid The persistent identifier
	 * @return  string $result The XML of the object
	 */
	function getObjectXMLByPID($pid)
	{
		if (APP_FEDORA_APIA_DIRECT == "ON") {
			$fda = new Fedora_Direct_Access();
			return $fda->getObjectXML($pid);
		}

                $getString = APP_BASE_FEDORA_APIM_DOMAIN."/objects/".$pid."/objectXML";
                $ch = curl_init($getString);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                if (APP_HTTPS_CURL_CHECK_CERT == "OFF" && APP_FEDORA_APIA_PROTOCOL_TYPE == 'https://')  {
                        curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);
                        curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);
                }
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
                $results = curl_exec($ch);
                if ($results) {
                        curl_close ($ch);
		        return $results;
                } else {
                        $log->err(array(curl_error($ch),__FILE__,__LINE__));
                        curl_close ($ch);
                        return false;
                }
	}


	/**
	 * Gets the audit trail for an object.
	 *
	 * @access  public
	 * @param string $pid The persistent identifier
	 * @return  array of audit trail
	 */
	function getAuditTrail($pid)
	{
		$auditTrail = array();

		$obj_xml = Fedora_API::getObjectXMLByPID($pid);

		$xmldoc= new DomDocument();
		$xmldoc->preserveWhiteSpace = false;
		$xmldoc->loadXML($obj_xml);

		$xpath = new DOMXPath($xmldoc);
		$dsStmt = "/foxml:digitalObject/foxml:datastream";
		$ds = $xpath->query($dsStmt); // returns nodeList
		foreach ($ds as $dsNode) {
			$ID = $dsNode->getAttribute('ID');
			if( $ID != 'AUDIT' )
			continue;

			$dvStmt = "./foxml:datastreamVersion[@ID='AUDIT.0']/foxml:xmlContent/audit:auditTrail";
			$dv = $xpath->query($dvStmt, $dsNode);
			foreach ($dv as $dvNode) {
				$daStmt = "./audit:record";
				$da = $xpath->query($daStmt,$dvNode);
				foreach ($da as $daNode) {
					$auditID = $daNode->getAttribute('ID');

					$dpStmt = "./audit:process";
					$dp = $xpath->query($dpStmt,$daNode);
					foreach ($dp as $dpNode) {
						$process = $dpNode->getAttribute('type');
						break;
					}

					$dpStmt = "./audit:action";
					$dp = $xpath->query($dpStmt,$daNode);
					foreach ($dp as $dpNode) {
						$action = $dpNode->nodeValue;
						break;
					}

					$dpStmt = "./audit:componentID";
					$dp = $xpath->query($dpStmt,$daNode);
					foreach ($dp as $dpNode) {
						$componentID = $dpNode->nodeValue;
						break;
					}

					$dpStmt = "./audit:responsibility";
					$dp = $xpath->query($dpStmt,$daNode);
					foreach ($dp as $dpNode) {
						$responsibility = $dpNode->nodeValue;
						break;
					}

					$dpStmt = "./audit:date";
					$dp = $xpath->query($dpStmt,$daNode);
					foreach ($dp as $dpNode) {
						$actionDate = $dpNode->nodeValue;
						break;
					}

					$dpStmt = "./audit:justification";
					$dp = $xpath->query($dpStmt,$daNode);
					foreach ($dp as $dpNode) {
						$justification = $dpNode->nodeValue;
						break;
					}

					array_push($auditTrail, array("ID" => $auditID,
                                                  "process" => $process,
                                                  "action" => $action,
                                                  "componentID" => $componentID,
                                                  "responsibility" => $responsibility,
                                                  "date" => $actionDate,
                                                  "justification" => $justification));
				}
			}
			break;
		}
		return $auditTrail;
	}



	/**
	 * This function ingests a FOXML object and base64 encodes it
	 *
	 * @access  public
	 * @param string $foxml The XML object itself in FOXML format
	 * @return  void
	 */
	function callIngestObject($foxml, $pid="")
	{
                $log = FezLog::get();

                $getString = APP_BASE_FEDORA_APIM_DOMAIN."/objects/".$pid."?format=info:fedora/fedora-system:FOXML-1.0";

                $tempFile = APP_TEMP_DIR.str_replace(":", "_", $pid).".xml";
//				$tempFile = "php://temp";

                $fp = fopen($tempFile, "w"); //@@@ CK - 28/7/2005 - Trying to make the file name in /tmp the uploaded file name

				if (fwrite($fp, $foxml) === FALSE) {
				        echo "Cannot write to file ($tempFile)";
				        exit;
				}
                $ch = curl_init($getString);
				curl_setopt($ch, CURLOPT_HEADER, true);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                if (APP_HTTPS_CURL_CHECK_CERT == "OFF" && APP_FEDORA_APIA_PROTOCOL_TYPE == 'https://')  {
                        curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);
                        curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);
                }

                curl_setopt($ch, CURLOPT_POST, 1);

//				curl_setopt($ch, CURLOPT_INFILE, $fp);
//				curl_setopt($ch, CURLOPT_INFILESIZE, strlen($foxml));
                curl_setopt($ch, CURLOPT_POSTFIELDS, array("file" => "@".$tempFile.";type=text/xml", "format" => "info:fedora/fedora-system:FOXML-1.0"));

                $results = curl_exec($ch);
                fclose($fp);
                if ($results) {

                        $info = curl_getinfo($ch);
						if ($info['http_code'] != '200' && $info['http_code'] != '201') {
	                        $log->err(array($info, $results),__FILE__,__LINE__);
							curl_close($ch);
							return false;
						}
                        curl_close ($ch);
                  if (is_file($tempFile)) {
						        unlink($tempFile);
                  }
                        return true;
                } else {
                        $log->err(array(curl_error($ch),__FILE__,__LINE__));
                        curl_close ($ch);
                        return false;
                }
	}

	function export($pid, $format="info:fedora/fedora-system:FOXML-1.0", $context="migrate")
	{
		$parms = compact('pid','form at','context');
		$result = Fedora_API::openSoapCall('export', $parms);
		return $result;
	}

	/**
	 * Returns an associative array
	 * listSession - can be used to get the next page of results (see listSession => token, completeListSize, cursor)
	 * The session seems to have a five minute timeout
	 * resultList - the list of items found
	 */
	function callFindObjects($resultFields = array('pid', 'title', 'identifier', 'description', 'state'),
	$maxResults = 10, $query_terms="")
	{
		return Fedora_API::openSoapCallAccess('findObjects', array(
            'resultFields' => $resultFields,
		new soapval('maxResults','nonNegativeInteger', $maxResults),
		new soapval('query','FieldSearchQuery',
		array('terms' => $query_terms),
		false,'http://www.fedora.info/definitions/1/0/types/')
		));
	}

	/**
	 * NOTE: This method doesn't work.  Use  search() instead.
	 * @param array $conditions - of form
	 * 					array(
	 *						array('property' => 'pid','operator' => '=','value' => 'UQ:12345'),
	 *						array('property' => 'title','operator' => '=','value' => 'Hello World'), ...)
	 */
	function callFindObjectsQuery($conditions,
	$resultFields = array('pid', 'title', 'identifier', 'description', 'state'),
	$maxResults = 10)
	{
		// NOTE: This method / function doesn't work. Use  search() instead.
		$conditions_soap = array();
		foreach ($conditions as $condition) {
			$conditions_soap[] = new soapval('fedora-types:property', 'string', $condition['property']);
			$conditions_soap[] = new soapval('fedora-types:operator', 'fedora-types:ComparisonOperator', $condition['operator']);
			$conditions_soap[] = new soapval('fedora-types:value', 'string', $condition['value']);
		}
		$conditions_array_soap = new soapval('fedora-types:conditions','ArrayOfCondition',$conditions_soap /*, false, false, array('SOAP-ENC:arrayType'=>'fedora-types:Condition[]') */);
		return Fedora_API::openSoapCallAccess('findObjects', array(
            'fedora-types:resultFields' => $resultFields,
		new soapval('fedora-types:maxResults','nonNegativeInteger', $maxResults),
		new soapval('fedora-types:query','fedora-types:FieldSearchQuery',array('fedora-types:conditions' => $conditions_array_soap))

		));
	}


	function callResumeFindObjects($token)
	{
		return Fedora_API::openSoapCallAccess('resumeFindObjects', array('sessionToken' => $token));
	}


	/**
	 * This function uses Fedora's simple search service which only really works against Dublin Core records,
	 * so is not heavily used. Searches are mostly carried out against Fez's own (much more powerful) index.
	 *
	 * @access  public
	 * @param string $searchTerms The terms by which the search will be carried out.
	 * @param integer $maxResults The maximum amount of results that will be returned.
	 * @param array $searchTerms The list of DC and Fedora basic fields to search against.
	 * @return  array $resultList The search results.
	 */
	function getListObjectsXML($searchTerms, $maxResults=2147483647, $returnfields=null)
	{
		$searchTerms = urlencode("*".$searchTerms."*"); // encode it for url parsing
		if (empty($returnfields)) {
			$returnfields = array('pid', 'title', 'identifier', 'description', 'type');
		}
		$fieldPhrase = '';
		foreach ($returnfields as $rField) {
			$fieldPhrase .= "&".$rField."=true";
		}
		$searchPhrase = "?xml=true".$fieldPhrase."&terms=".$searchTerms;
		if (is_numeric($maxResults)) {
			$searchPhrase .= "&maxResults=".$maxResults;
		}
		$filename = APP_FEDORA_SEARCH_URL.$searchPhrase;
		list($xml,$info) = Misc::processURL($filename);
		$xml = preg_replace("'<object uri\=\"info\:fedora\/(.*)\"\/>'", "<pid>\\1</pid>", $xml); // fix the pid tags
		return self::resultListXMLtoArray($xml,$returnfields);
	}

	function resultListXMLtoArray($xml,$returnfields)
	{
		$resultlist = array();
		$doc = DOMDocument::loadXML($xml);
		$xpath = new DOMXPath($doc);
		$xpath->registerNamespace('r', 'http://www.fedora.info/definitions/1/0/types/');
		$objectFieldsNodeList = $xpath->query('/r:result/r:resultList/r:objectFields');
		// loop through the objectFields elements
		foreach ($objectFieldsNodeList as $objectFieldsNode) {
			// look for the return fields and group them in an array
			foreach ($returnfields as $rfield) {
				$rFieldNodeList = $objectFieldsNode->getElementsByTagName($rfield);
				if ($rFieldNodeList->length) {
					$rItem[$rfield] = trim($rFieldNodeList->item(0)->nodeValue);
				}
			}
			// add the return fields array to out list of results
			$resultlist[] = $rItem;
		}
		return $resultlist;

	}


	/**
	 * This function removes an object and all its datastreams from Fedora
	 *
	 * @access  public
	 * @param string $pid The persistant identifier of the object to be purged
	 * @return  integer
	 */
	function callPurgeObject($pid)
	{
		$logmsg = 'Fedora Object Purged using Fez';

        $log = FezLog::get();

        $getString = APP_BASE_FEDORA_APIM_DOMAIN."/objects/".$pid;

        $ch = curl_init($getString);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if (APP_HTTPS_CURL_CHECK_CERT == "OFF" && APP_FEDORA_APIA_PROTOCOL_TYPE == 'https://')  {
                curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);
                curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);
        }
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($ch, CURLOPT_POSTFIELDS, array('force' => "false", "logMessage" => $logmsg));
        $results = curl_exec($ch);
        if ($results) {
                //$info = curl_getinfo($ch);
                curl_close ($ch);
                return true;
        } else {
                $log->err(array(curl_error($ch),__FILE__,__LINE__));
                curl_close ($ch);
                return false;
        }

	}

	/**
	 * This function uses curl to upload a file into the fedora upload manager and calls the addDatastream or modifyDatastream as needed.
	 *
	 * @access  public
	 * @param string $pid The persistant identifier of the object to be purged
	 * @param string $dsIDName The datastream name
	 * @param string $dsLabel The datastream label
	 * @param string $mimetype The mimetype of the datastream
	 * @param string $controlGroup The control group of the datastream
	 * @param string $dsID The ID of the datastream
	 * @param boolean $versionable Whether to version control this datastream or not
	 * @return  integer
	 */
	function getUploadLocation ($pid, $dsIDName, $file, $dsLabel, $mimetype='text/xml', $controlGroup='M', $dsID=NULL,$versionable='false')
	{
		$log = FezLog::get();
		if (!is_numeric(strpos($dsIDName, "/"))) {
			$loc_dir = APP_TEMP_DIR;
		}

		if (!empty($file) && (trim($file) != "")) {
			$file_full = $loc_dir.str_replace(":", "_", $pid)."_".$dsIDName.".xml";
			$fp = fopen($file_full, "w"); //@@@ CK - 28/7/2005 - Trying to make the file name in /tmp the uploaded file name
			fwrite($fp, $file);
			fclose($fp);
		}

		$versionable = $versionable === true ? 'true' : $versionable === false ? 'false' : $versionable;
		$dsExists = Fedora_API::datastreamExists($pid, $dsIDName, true);
		if ($dsExists !== true) {
			// Call callAddDatastream
			$dsID = Fedora_API::callAddDatastream($pid, $dsIDName, $file_full, $dsLabel, "A", $mimetype, $controlGroup, $versionable, '');
			if(is_file($file_full))
			{
			    unlink($file_full);
			}
			return $dsID;
		} elseif (!empty($dsIDName)) {
			// Let fedora handle versioning
			Fedora_API::callModifyDatastream($pid, $dsIDName, $file_full, $dsLabel, "A", $mimetype, $versionable, '');
      if(is_file($file_full)) {
        unlink($file_full);
      }
			return $dsIDName;
		}

	}

	/**
	 * This function uses curl to geta file from a local file location and upload it into the fedora upload manager and calls the addDatastream or modifyDatastream as needed.
	 *
	 * Developer Note: Mainly used by batch import of a SAN directory
	 *
	 * @access  public
	 * @param string $pid The persistant identifier of the object to be purged
	 * @param string $dsIDName The datastream name
	 * @param string $local_file_location The location of the file on a local server directory
	 * @param string $dsLabel The datastream label
	 * @param string $mimetype The mimetype of the datastream
	 * @param string $controlGroup The control group of the datastream
	 * @param string $dsID The ID of the datastream
	 * @param boolean $versionable Whether to version control this datastream or not
	 * @return  integer
	 */
	function getUploadLocationByLocalRef ($pid, $dsIDName, $local_file_location, $dsLabel, $mimetype, $controlGroup='M', $dsID=NULL,$versionable='false')
	{
		$log = FezLog::get();

		if (!is_numeric(strpos($local_file_location,"/"))) {
			$local_file_location = APP_TEMP_DIR.$local_file_location;
		}
		if ($mimetype == "") {
			$mimetype = Misc::mime_content_type($local_file_location);
		}


		$local_file_location = trim(str_replace("\n", "", $local_file_location));
		$versionable = $versionable === true ? 'true' : $versionable === false ? 'false' : $versionable;
		$dsExists = Fedora_API::datastreamExists($pid, $dsIDName);
		if ($dsExists !== true) {
			//Call callAddDatastream
			$dsID = Fedora_API::callAddDatastream($pid, $dsIDName, $local_file_location, $dsLabel, "A", $mimetype, $controlGroup, $versionable);
			return $dsID;
		} elseif (!empty($dsIDName)) {
			// Let fedora handle versioning
			Fedora_API::callModifyDatastream($pid, $dsIDName, $local_file_location, $dsLabel, "A", $mimetype, $versionable);
			return $dsIDName;
		}
	}

	function tidyXML($xml) {
		if (!defined('APP_NO_TIDY') || !APP_NO_TIDY) {
			$config = array(
                'indent'        => true,
                'input-xml'     => true,
                'output-xml'    => true,
                'wrap'          => 0
			);
			$tidy = new tidy;
			$tidy->parseString($xml, $config, 'utf8');
			$tidy->cleanRepair();
			$xml = "$tidy";
		}
		return $xml;
	}

	/**
	 * This function adds datastreams to object $pid.
	 *
	 * @access  public
	 * @param string $pid The persistant identifier of the object to be purged
	 * @param string $dsID The ID of the datastream
	 * @param string $uploadLocation The location of the file to add
	 * @param string $dsLabel The datastream label
	 * @param string $dsState The datastream state
	 * @param string $mimetype The mimetype of the datastream
	 * @param string $controlGroup The control group of the datastream
	 * @param boolean $versionable Whether to version control this datastream or not
	 * @param boolean $xmlContent If it an X based xml content file then it uses a var rather than a file location
	 * @return void
	 */
	function callAddDatastream ($pid, $dsID, $dsLocation, $dsLabel, $dsState, $mimetype, $controlGroup='M',$versionable='false', $xmlContent="")
	{

	    if ($mimetype == "") {
			$mimetype = "text/xml";
		}
		$dsIDOld = $dsID;
		if (is_numeric(strpos($dsID, chr(92)))) {
			$dsID = substr($dsID, strrpos($dsID, chr(92))+1);
			if ($dsLabel == $dsIDOld) {
				$dsLabel = $dsID;
			}
		}
		$dsIDName = $dsID;
		if (is_numeric(strpos($dsIDName, "/"))) {
			$dsIDName = substr($dsIDName, strrpos($dsIDName, "/")+1);
		}

		$versionable = $versionable === true ? 'true' : $versionable === false ? 'false' : $versionable;
        $log = FezLog::get();
		$getString = APP_SIMPLE_FEDORA_APIM_DOMAIN."/objects/".$pid."/datastreams/".$dsIDName."?dsLabel=".urlencode($dsLabel)."&versionable=".$versionable."&mimeType=".$mimetype.
			          "&controlGroup=".$controlGroup."&dsState=A&logMessage=Added%20Datastream";

		if ($dsLocation != "" && $controlGroup == "X") {
			$xmlContent = file_get_contents($dsLocation);
		}
		if ($dsLocation != "" && $controlGroup == "R") {
			$getString .= "&dsLocation=".$dsLocation;
			$ch = curl_init($getString);
 		 	curl_setopt($ch, CURLOPT_POST, 1);

			curl_setopt($ch, CURLOPT_POSTFIELDS, array("dsLocation" => $dsLocation,
														"dsLabel" => urlencode($dsLabel),
														"versionable" => $versionable,
														"mimeType" => $mimeType,
														"controlGroup" => $controlGroup,
														"dsState" => "A",
														"logMessage" => "Added Link"
														));


		} elseif ($xmlContent != "") {

		    /* Comment reason: redundant code. This class will only get called when Fedora Bypass is ON. See TODO */
            /*
		    if(APP_FEDORA_BYPASS != 'ON')
		    {
    			$ch = curl_init($getString);
     		 	curl_setopt($ch, CURLOPT_POST, 1);
    			if ($controlGroup == 'X') {
    				$xmlContent = Fedora_API::tidyXML($xmlContent);
    				$tempFile = APP_TEMP_DIR.str_replace(":", "_", $pid)."_".$dsID.".xml";
    			} else {
    				$tempFile = APP_TEMP_DIR.$dsID;
    			}
    			$fp = fopen($tempFile, "w");
    			if (fwrite($fp, $xmlContent) === FALSE) {
    			        echo "Cannot write to file ($tempFile)";
    			        exit;
    			}
    			fclose($fp);
    			curl_setopt($ch, CURLOPT_POSTFIELDS, array("file[]" => "@".$tempFile.";type=".$mimetype,
    											"dsLabel" => urlencode($dsLabel),
    											"versionable" => $versionable,
    											"mimeType" => $mimeType,
    											"controlGroup" => $controlGroup,
    											"dsState" => "A",
    											"logMessage" => "Added Datastream"
    											));
		    }
            */

            // @TODO: Add non-Fedora processing when $xmlContent is empty.


		} elseif ($dsLocation != "" && $controlGroup == "M") {

			$ch = curl_init($getString);
	 		curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, array("file_name" => "@".$dsLocation.";type=".$mimetype,
														"dsLabel" => urlencode($dsLabel),
														"versionable" => $versionable,
														"mimeType" => $mimeType,
														"controlGroup" => $controlGroup,
														"dsState" => "A",
														"logMessage" => "Added Datastream",
														"submit" => "UPLOAD"
														));
		}
		 curl_setopt($ch, CURLOPT_USERPWD, APP_FEDORA_USERNAME.":".APP_FEDORA_PWD);

		 curl_setopt($ch, CURLOPT_VERBOSE, 1);
		 curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		 if (APP_HTTPS_CURL_CHECK_CERT == "OFF" && APP_FEDORA_APIA_PROTOCOL_TYPE == 'https://')  {
		         curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);
		         curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);
		 }

		 $results = curl_exec($ch);
		 if ($results) {
		         curl_close ($ch);
             if (is_file($tempFile)) {
						  unlink($tempFile);
             }
		         return true;
		 } else {
		         $log->err(array(print_r($results, true).print_r(curl_error($ch), true).print_r(curl_getinfo($ch), true),__FILE__,__LINE__).$getString.$tempFile.$xmlContent);
		         curl_close ($ch);
		         return false;
		 }

	}


    function callModifyDatastream ($pid, $dsID, $dsLocation, $dsLabel, $dsState, $mimetype, $versionable='false', $xmlContent="")
    {
        if ($mimetype == "") {
            $mimetype = "text/xml";
        }
        $dsIDOld = $dsID;
        if (is_numeric(strpos($dsID, chr(92)))) {
            $dsID = substr($dsID, strrpos($dsID, chr(92))+1);
            if ($dsLabel == $dsIDOld) {
                $dsLabel = $dsID;
            }
        }
        $versionable = $versionable === true ? 'true' : $versionable === false ? 'false' : 'false';
        $log = FezLog::get();
        $getString = APP_SIMPLE_FEDORA_APIM_DOMAIN."/objects/".$pid."/datastreams/".$dsID;

        $ch = curl_init($getString);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_USERPWD, APP_FEDORA_USERNAME.":".APP_FEDORA_PWD);

        $isLink = false;

        if (is_numeric(strpos($dsID, "Link"))) {
          $isLink = true;
        }
        if ($dsLocation != "" && $isLink != true) {
            $xmlContent = file_get_contents($dsLocation);
        }

        if ($dsLocation != "" && $isLink == true) {
            $log->err("sending this as a link => got a location of ".$dsLocation);
            exit;
            $getString .= "&dsLocation=".$dsLocation;
            curl_setopt($ch, CURLOPT_POSTFIELDS, array("dsLocation" => $dsLocation,
                                                        "dsLabel" => urlencode($dsLabel),
                                                        "versionable" => $versionable,
                                                        "mimeType" => $mimetype,
                                                        "formatURI" => $formatURI,
                                                        "dsState" => "A",
                                                        "logMessage" => "Modified Datastream"

            ));
        } elseif ($xmlContent != "") {
            $xmlContent = Fedora_API::tidyXML($xmlContent);
            $tempFile = APP_TEMP_DIR.str_replace(":", "_", $pid)."_".$dsID.".xml";
            $fp = fopen($tempFile, "w");
            if (fwrite($fp, $xmlContent) === FALSE) {
                    echo "Cannot write to file ($tempFile)";
                    exit;
            }
            fclose($fp);
            curl_setopt($ch, CURLOPT_POSTFIELDS, array("file" => "@".$tempFile.";type=".$mimetype,
                                                        "dsLabel" => urlencode($dsLabel),
                                                        "versionable" => $versionable,
                                                        "mimeType" => $mimetype,
                                                        "formatURI" => $formatURI,
                                                        "dsState" => "A",
                                                        "logMessage" => "Modified Datastream"
            ));
        } elseif ($dsLocation != "") {
            curl_setopt($ch, CURLOPT_POSTFIELDS, array("file" => "@".$dsLocation.";type=".$mimetype,
                                                        "dsLabel" => urlencode($dsLabel),
                                                        "versionable" => $versionable,
                                                        "mimeType" => $mimetype,
                                                        "formatURI" => $formatURI,
                                                        "dsState" => "A",
                                                        "logMessage" => "Modified Datastream"
            ));
            $log->err("sending this as a file => got a location of ".$dsLocation);
        }

         if (APP_HTTPS_CURL_CHECK_CERT == "OFF" && APP_FEDORA_APIA_PROTOCOL_TYPE == 'https://')  {
                 curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);
                 curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);
         }
         $results = curl_exec($ch);

         if ($results) {
                $info = curl_getinfo($ch);
                if ($info['http_code'] != '200' && $info['http_code'] != '201') {
                    $log->err(array(curl_error($ch), $info,__FILE__,__LINE__));
                    curl_close($ch);
                    exit;
                    return false;
                }
                 curl_close ($ch);
                 return true;
         } else {
                 $info = curl_getinfo($ch);
                 $log->err(array(curl_error($ch), $info,__FILE__,__LINE__));
                 curl_close ($ch);
                 return false;
         }

    }

	/**
	 * This function adds datastreams to object $pid.
	 *
	 * @access  public
	 * @param string $pid The persistant identifier of the object to be purged
	 * @param string $dsIDName The name of the datastream
	 * @param string $uploadLocation The location of the file to add
	 * @param string $dsLabel The datastream label
	 * @param string $dsState The datastream state
	 * @param string $mimetype The mimetype of the datastream
	 * @param string $controlGroup The control group of the datastream
	 * @param boolean $versionable Whether to version control this datastream or not
	 * @return void
	 */
	function callCreateDatastream($pid, $dsIDName, $uploadLocation, $dsLabel, $mimetype, $controlGroup='M',$versionable='false')
	{
           return Fedora_API::callAddDatastream($pid, $dsIDName, $uploadLocation, $dsLabel, 'A', $mimetype, $controlGroup, $versionable);
	}

	/**
	 *This function creates an array of all the datastreams for a specific object.
	 *
	 * @access  public
	 * @param string $pid The persistant identifier of the object
	 * @param string $createdDT (optional) Fedora timestamp of version to retrieve
	 * @return array $dsIDListArray The list of datastreams in an array.
	 */
	function callGetDatastreams($pid, $createdDT=NULL, $dsState='A')
	{
		if (!is_numeric($pid)) {

			if (APP_FEDORA_APIA_DIRECT == "ON") {
				$fda = new Fedora_Direct_Access();
				$datastreams = $fda->getDatastreams($pid);
				return $datastreams;
			}

			$parms=array('pid' => $pid, 'asOfDateTime' => $createdDT, 'dsState' => $dsState);
			$dsIDListArray = Fedora_API::openSoapCall('getDatastreams', $parms);
			if (empty($dsIDListArray) || (is_array($dsIDListArray) && isset($dsIDListArray['faultcode']))) {
				return false;
			}
			if (!is_array($dsIDListArray[0])){
				// when only one datastream, it returns as a datastream instead of
				// array of datastreams so rewrite as array of datastreams to match
				// multiple datastreams format
				$ds = array();
				$ds[controlGroup] = $dsIDListArray[controlGroup];
				$ds[ID]           = $dsIDListArray[ID];
				$ds[versionID]    = $dsIDListArray[versionID];
				$ds[altIDs]       = $dsIDListArray[altIDs];
				$ds[label]        = $dsIDListArray[label];
				$ds[versionable]  = $dsIDListArray[versionable];
				$ds[MIMEType]     = $dsIDListArray[MIMEType];
				$ds[formatURI]    = $dsIDListArray[formatURI];
				$ds[createDate]   = $dsIDListArray[createDate];
				$ds[size]         = $dsIDListArray[size];
				$ds[state]        = $dsIDListArray[state];
				$ds[location]     = $dsIDListArray[location];
				$ds[checksumType] = $dsIDListArray[checksumType];
				$ds[checksum]     = $dsIDListArray[checksum];

				$dsIDListArray = array();
				$dsIDListArray[0] = $ds;
			}
			sort($dsIDListArray);
			reset($dsIDListArray);
			return $dsIDListArray;
		} else {
			return array();
		}
	}

	/**
	 *This function creates an array of all the datastreams for a specific object.
	 *
	 * @access  public
	 * @param string $pid The persistant identifier of the object
	 * @return array $dsIDListArray The list of datastreams in an array.
	 */
	function callListDatastreams($pid)
	{
		if (!is_numeric($pid)) {
			if (APP_FEDORA_APIA_DIRECT == "ON") {
				$fda = new Fedora_Direct_Access();
				$dsIDListArray = $fda->listDatastreams($pid);
				return $dsIDListArray;
			}
			$parms=array('pid' => $pid, 'asOfDateTime' => NULL);
			$dsIDListArray = Fedora_API::openSoapCallAccess('listDatastreams', $parms);
			sort($dsIDListArray);
			reset($dsIDListArray);
			return $dsIDListArray;
		} else {
			return array();
		}
	}

	/**
	 *This function creates an array of all the datastreams for a specific object using the API-A-LITE rather than soap
	 *
	 * @access  public
	 * @param string $pid The persistant identifier of the object
	 * @return array $dsIDListArray The list of datastreams in an array.
	 */
	function callListDatastreamsLite($pid, $refresh=false)
	{
		$log = FezLog::get();
		$db = DB_API::get();

		if (!is_numeric($pid)) {

            $sql = "SELECT fat_filename, fat_mimetype, fat_version FROM "
                . APP_TABLE_PREFIX . "file_attachments WHERE fat_pid = :pid GROUP BY fat_filename";

            try
            {
                $stmt = $db->query($sql, array(':pid' => $pid));
                $rows = $stmt->fetchAll();
            }
            catch(Exception $e)
            {
                $log->err($e->getMessage());
            }

            $resultlist = array();
            foreach($rows as $row)
            {
                $resultlist[] = array('dsid' => $row['fat_filename'],
                    'label' => $row['fat_filename'],
                    'mimeType' => $row['fat_mimetype']);
            }
			return $resultlist;
		} else {
			return array();
		}
	}



	function objectExists($pid, $refresh = false)
	{
        $log = FezLog::get();
        $db = DB_API::get();
        $stmt = "SELECT rek_pid
                FROM ". APP_TABLE_PREFIX . "record_search_key
                WHERE rek_pid = ".$db->quote($pid);
        try {
                $res = $db->fetchOne($stmt);
            }
        catch(Exception $ex) {
            $log->err($ex);
            return array();
        }
        if ($res == $pid) {
            return true;
        }

        $stmt = "SELECT rek_pid
                FROM ". APP_TABLE_PREFIX . "record_search_key__shadow
                WHERE rek_pid = ".$db->quote($pid);
        try {
            $res = $db->fetchOne($stmt);
        }
        catch(Exception $ex) {
            $log->err($ex);
            return array();
        }
        return ($res == $pid);

	}

	/**
	 * This function creates an array of a specific datastream of a specific object
	 *
	 * @access  public
	 * @param string $pid The persistant identifier of the object
	 * @param string $dsID The ID of the datastream
	 * @return array $dsIDListArray The requested of datastream in an array.
	 */
	function callGetDatastream($pid, $dsID)
	{
        $dsr = new DSResource(APP_DSTREE_PATH);
        $dsr->load($dsID, $pid);
        $dsArray = $dsr->getDSRev($dsID, $pid);
        $dsArray['ID'] = $dsID;
        $vers = $dsr->getDSRevs($dsID, $pid);
        $vers = $vers[$dsArray['version']];
        $dsArray['versionID'] = $vers;
        $dsArray['label'] = $dsID;
        $dsArray['controlGroup'] = $dsArray['controlgroup'];
        $dsArray['MIMEType'] = $dsArray['mimetype'];
        $dsArray['createDate'] = $dsArray['version'];
        $dsArray['location'] = NULL; //TODO Check if this is needed and if so fill with a real value.
        $dsArray['formatURI'] = NULL; //TODO Check if this is needed and if so fill with a real value.
        $dsArray['checksumType'] = 'DISABLED'; //TODO Check if this is needed and if so fill with a real value.
        $dsArray['checksum'] = 'none'; //TODO Check if this is needed and if so fill with a real value.
        $dsArray['versionable'] = FALSE; //TODO Check if this is needed and if so fill with a real value.

        return $dsArray;
	}

	/**
	 * Does a datastream with a given ID already exist in an object
	 *
	 * @access  public
	 * @param string $pid The persistant identifier of the object
	 * @param string $dsID The ID of the datastream to be checked
	 * @param string $pattern a regex pattern to search against if given instead of ==/equivalence
	 * @return boolean
	 */
	function datastreamExists ($pid, $dsID, $refresh=false, $pattern=false)
	{
		if (Misc::isPid($pid) != true) {
			return false;
		}

		$dsExists = false;

		$rs = Fedora_API::callListDatastreamsLite($pid, $refresh);
		if (is_array($rs)) {
			foreach ($rs as $row) {
				if ($pattern != false) {
					if (isset($row['dsid']) && preg_match($pattern, $row['dsid'], $ds_matches)) {
						return $ds_matches[0];
						$dsExists = true;
					}
				} else {
					if (isset($row['dsid']) && ($row['dsid'] == $dsID)) {
						$dsExists = true;
					}
				}
			}
		}
		return $dsExists;
	}

	/**
	 * Does a datastream with a given ID already exist in existing list array of datastreams
	 *
	 * @access  public
	 * @param string $existing_list The existing list of datastreams
	 * @param string $dsID The ID of the datastream to be checked
	 * @return boolean
	 */
	function datastreamExistsInArray ($existing_list, $dsID)
	{
		$dsExists = false;
		$rs = $existing_list;
		foreach ($rs as $row) {
			if (isset($row['ID']) && $row['ID'] == $dsID) {
				$dsExists = true;
			}
		}
		return $dsExists;
	}

	/**
	 * This function creates an array of a specific datastream of a specific object
	 *
	 * @access  public
	 * @param string $pid The persistant identifier of the object
	 * @param string $dsID The ID of the datastream to be checked
	 * @param string $asofDateTime Optional Gets a specified version at a datetime stamp
	 * @return array $dsIDListArray The datastream returned in an array
	 */
	function callGetDatastreamDissemination($pid, $dsID, $asofDateTime="")
	{
		$log = FezLog::get();
		// Redirect all calls to the REST Version for now - CK added 17/7/2009
		return Fedora_API::callGetDatastreamDisseminationLite($pid, $dsID, $asofDateTime);
	}

	/**
	 * This function creates an array of a specific datastream of a specific object
	 *
	 * @access  public
	 * @param string $pid The persistant identifier of the object
	 * @param string $dsID The ID of the datastream to be checked
	 * @param string $asofDateTime Optional Gets a specified version at a datetime stamp
	 * @return array $dsIDListArray The datastream returned in an array
	 */
	function callGetDatastreamDisseminationLite($pid, $dsID, $asofDateTime="")
	{

		$log = FezLog::get();
		$dsIDListArray = array();
		if ($asofDateTime == "") {
			$urldata = APP_FEDORA_GET_URL."/".$pid."/".$dsID;
		} else {
			$urldata = APP_FEDORA_GET_URL."/".$pid."/".$dsID."/".$asofDateTime;
		}
		list($dsIDListArray['stream'],$info) = Misc::processURL($urldata);
		return $dsIDListArray;
	}




	/**
	 * This function creates an array of a specific datastream of a specific object
	 *
	 * @access  public
	 * @param string $pid The persistant identifier of the object
	 * @param string $dsID The ID of the datastream
	 * @param boolean $getxml Get as xml
	 * @return array $resultlist The requested of datastream in an array.
	 */
	function callGetDatastreamContents($pid, $dsID, $getraw = false, $filehandle = null)
	{
		//$filehandle is a legacy arg left here to keep the API intact.
        $dsr = new DSResource(APP_DSTREE_PATH);
        $dsMeta = $dsr->getDSRev($dsID, $pid);

        $dsExists = Fedora_API::datastreamExists($pid, $dsID);
        if($dsExists)
        {
            if($dsMeta['mimetype'] != 'text/xml' || $getraw)
            {
                $return =  $dsr->getDSData($dsMeta['hash']);
            }
            else
            {
                $return = array(
                    'date' => array($dsMeta['version']),
                    'repInfo' => array($dsr->getDSData($dsMeta['hash'])),
                    'uri' => array($dsr->createPath($dsMeta['hash']) . $dsMeta['hash'])
                );
            }

            return $return;
        }
    }

	/**
	 * This function creates an array of specific fields from a specific datastream of a specific object
	 *
	 * @access  public
	 * @param string $pid The persistant identifier of the object
	 * @param string $dsID The ID of the datastream
	 * @param array $returnfields
	 * @return array $dsIDListArray The requested of datastream in an array.
	 */
	function callGetDatastreamContentsField($pid, $dsID, $returnfields, $asOfDateTime="")
	{
		static $counter;
		if (!isset($counter)) {
			$counter = 0;

		}
		$counter++;
		$resultlist = array();
		$dsExists = Fedora_API::datastreamExists($pid, $dsID);
		if ($dsExists == true) {
			$xml = Fedora_API::callGetDatastreamDissemination($pid, $dsID, $asOfDateTime);
			$xml = $xml['stream'];
			if (!empty($xml) && $xml != false) {
				$doc = DOMDocument::loadXML($xml);
				$xpath = new DOMXPath($doc);
				$fieldNodeList = $xpath->query("/$dsID/*");
				foreach ($fieldNodeList as $fieldNode) {
					if (in_array($fieldNode->nodeName, $returnfields)) {
						$resultlist[$fieldNode->nodeName][] = trim($fieldNode->nodeValue);
					}
				}
			}
		}
		return $resultlist;
	}

	/**
	 * This function modifies inline xml datastreams (ByValue)
	 *
	 * @access  public
	 * @param string $pid The persistant identifier of the object
	 * @param string $dsID The name of the datastream
	 * @param string $state The datastream state
	 * @param string $label The datastream label
	 * @param string $dsContent The datastream content
	 * @param string $mimetype The mimetype of the datastream
	 * @param boolean $versionable Whether to version control this datastream or not
	 * @return void
	 */
	function callModifyDatastreamByValue ($pid, $dsID, $state, $label, $dsContent, $mimetype='text/xml', $versionable='inherit')
	{
		Fedora_API::callModifyDatastream($pid, $dsID, "", $label, "A", $mimetype, $versionable, $dsContent);
	}

	/**
	 * This function modifies non-in-line datastreams, either a chunk o'text, a url, or a file.
	 *
	 * @access  public
	 * @param string $pid The persistant identifier of the object
	 * @param string $dsID The name of the datastream
	 * @param string $dsLabel The datastream label
	 * @param string $dsLocation The location of the datastream
	 * @param boolean $versionable Whether to version control this datastream or not
	 * @return void
	 */
	function callModifyDatastreamByReference($pid, $dsID, $dsLabel, $dsLocation=NULL, $mimetype,$versionable='inherit')
	{
		// force state to 'A'; otherwise, if the dsID is the same
		// as a DS that was deleted, then the modify will fail
		Fedora_API::callSetDatastreamState($pid,$dsID,'A');



		$versionable = $versionable === true ? 'true' : $versionable === false ? 'false' : $versionable;
		if( strcasecmp($versionable,'inherit') != 0 ){
			// if 'inherit' then versionable is not being changed
			$logmsg = 'Update versionable';
			$parms= array(
		       'pid'           => $pid,
		       'dsID'  => $dsID,
		       'versionable'  => $versionable,
		       'logMessage'    => $logmsg,
			   'asOfDateTime' => NULL
			);
			Fedora_API::openSoapCall('setDatastreamVersionable', $parms);
		}
		$logmsg = 'Modifying datastream by reference';
		$parms= array(
			'pid'           => $pid,
			'dsID'  => $dsID,
			'altIDs'        => array(),
			'dsLabel'       => $dsLabel,
			'MIMEType'      => $mimetype,
			'formatURI'     => 'unknown',
			'dsLocation'    => $dsLocation,
			'checksumType'  => 'MD5',
			'checksum'      => md5($dsLocation),
			'logMessage'    => $logmsg,
			'force'         => true,
		    'asOfDateTime' => NULL
		);
		Fedora_API::openSoapCall('modifyDatastreamByReference', $parms);
	}

	/**
	 * Changes the state and/or label of the object.
	 * @param string $pid - the pid of the object.
	 * @param string $state - the new state, A, I or D. Null means leave unchanged.
	 * @param string $label - the new label. Null means leave unchanged.
	 * @param string $logMessage - a log message.
	 */
	function callModifyObject($pid, $state, $label, $logMessage = 'Deleted by Fez')
	{
		$ownerId = null; // Fez doesn't use this
		$parms= compact('pid','state','label','ownerId','logMessage');
		return Fedora_API::openSoapCall('modifyObject', $parms);
	}


	/**
	 * This function marks a datastream as deleted by setting the state.
	 *
	 * @access  public
	 * @param string $pid The persistant identifier of the object
	 * @param string $dsID The ID of the datastream
	 * @return boolean
	 */
	function deleteDatastream($pid, $dsID)
	{
		return Fedora_API::callSetDatastreamState($pid,$dsID,'D',"Changed Datastream State to Deleted from Fez");
	}

	/**
	 * Format the version of the a file to conform to Fedora_API
	 * @param <string> $filename
	 * @param <string> $pid
	 */
	function formatVersion($filename, $pid)
	{
	    $versions = array();
	    $ver = 0;
	    $dsr = new DSResource(APP_DSTREE_PATH);
	    $revs = $dsr->getDSRevs($filename, $pid);
	    foreach($revs as $rev)
	    {
	        $versions[$rev['version']] = $filename . "." . $ver;
	        $ver++;
	    }

	    return $versions;
	}

	/**
	 * This function sets the state flag on a datastream
	 *
	 * @access  public
	 * @param string $pid The persistant identifier of the object to be purged
	 * @param string $dsID The name of the datastream
	 * @param string $state The new state of the datastream
	 * @param string $logMessage
	 * @return boolean
	 */
	function callSetDatastreamState ($pid, $dsID, $state='A', $logMessage="Changed Datastream State from Fez")
	{
		$parms= array(
			'pid' => $pid,
			'dsID' => $dsID,
			'dsState' => $state,
			'logMessage' => $logMessage);
		return Fedora_API::openSoapCall('setDatastreamState', $parms);
	}

	/**
	 * This function deletes a datastream
	 *
	 * @access  public
	 * @param string $pid The persistant identifier of the object to be purged
	 * @param string $dsID The name of the datastream
	 * @param string $endDT The end datetime of the purge
	 * @param string $logMessage
	 * @param boolean $force
	 * @return boolean
	 */
	function callPurgeDatastream ($pid, $dsID, $startDT=NULL, $endDT=NULL, $logMessage="Purged Datastream from Fez", $force=false)
	{
		$parms= array('pid' => $pid, 'dsID' => $dsID, 'startDT' => $startDT, 'endDT' => $endDT, 'logMessage' => $logMessage, 'force' => $force);
		return Fedora_API::openSoapCall('purgeDatastream', $parms);
	}

	// This function is not used, as disseminators are not used in Fez, but it will be left in in for now
	function callAddDisseminator ($pid, $dsID, $bDefPID, $bMechPID, $dissLabel, $key)
	{
		//Builds a four level namespaced typed array.
		//$dsBindings[] is used by $bindingMap,
		//which is used by the soap call $parms.
		$dsBindings[0] = array('bindKeyName' => $key,
					'bindLabel' => $dissLabel,
					'datastreamID' => $dsID,
					'seqNo' => '0');
		$bindingMap = array('dsBindMapID'  => 'foo',
					'dsBindMechanismPID' => $bMechPID,
					'dsBindMapLabel' => 'Label for dsBindMapLabel',
					'state' => 'A',
		new soapval('dsBindings', 'DatastreamBinding', $dsBindings[0], '', 'http://www.fedora.info/definitions/1/0/types/'));
		// soap call parms.
		$parms=array('pid' => $pid,
					'bDefPid' => $bDefPID,
					'bMechPid' => $bMechPID,
					'dissLabel' => $dissLabel,
					'bDefLabel' => 'Label for bDef',
					'bMechLabel' => 'Label for bMech',
		new soapval('bindingMap', 'DatastreamBindingMap', $bindingMap, '', 'http://www.fedora.info/definitions/1/0/types/'),
					'dissState' => 'A');
		//Call createDatastream
		$dissID = Fedora_API::openSoapCall('addDisseminator', $parms);
		return $dissID;
	}

	// This function is not used, as disseminators are not used in Fez, but it will be left in in for now
	function callGetDisseminators($pid)
	{
		/********************************************
		 * This call gets a list of disseminators per
		 * pid.
		 ********************************************/
		$parms=array('PID' => $pid);
		return $dissArray = Fedora_API::openSoapCall('getDisseminators', $parms);
	}

	/**
	 * Passes as Soap call to the NUSOAP engine through to the Fedora Management webservice API-M
	 *
	 * @access  public
	 * @param array $call The name of the fedora web service to call
	 * @param array $parms The parameters
	 * @return array $result
	 */
	function openSoapCall ($call, $parms, $debug_error=true)
	{
	    $log = FezLog::get();

	    $setDateTimeTo = array('getDatastream');

        $do = new DigitalObject();
        if(method_exists($do, $call))
        {
            $res = $do->$call($parms);
            /*echo "<pre>";
            var_dump($res);
            echo "</pre>";
            die();*/
            return $res;
        }
	}

	/**
	 * Passes as Soap call to the NUSOAP engine through to the Fedora Access webservice API-A
	 *
	 * @access  public
	 * @param array $call The name of the fedora web service to call
	 * @param array $parms The parameters
	 * @return array $result
	 */
	function openSoapCallAccess ($call, $parms)
	{
		$log = FezLog::get();

		/********************************************
		 * This is a primary function called by all of
		 * the preceding functions.
		 * $call is the api call to the fedora api-a.
		 ********************************************/
		try {
			$client = new SoapClient(APP_FEDORA_ACCESS_WSDL_API, array("login" => APP_FEDORA_USERNAME, "password" => APP_FEDORA_PWD, 'trace' => 1));
			$result = $client->__soapCall($call, array('parameters' => $parms));
			$result = array_values(Misc::obj2array($result));
			$result = $result[0];
		} catch (SoapFault $fault) {
			$fedoraError = "Error when calling ".$call." :".$fault->faultstring."\n\n REQUEST: \n\n".$client->__getLastRequest()."\n\n RESPONSE: \n\n ".$client->__getLastResponse();
			$log->err(array($fedoraError, __FILE__,__LINE__));
			return false;
		}
    return $result;
	}

	/**
	 * This function provides SOAP debug statements.
	 *
	 * @access  public
	 * @param array $client The soap object
	 * @return void (Outputs debug info to screen).
	 */
	function debugInfo($client, $asString=false)
	{
		$str =
            '<hr /><b>Debug Information</b><br /><br />'
            .'Request: <xmp>'.$client->request.'</xmp>'
            .'Response: <xmp>'.$client->response.'</xmp>'
            .'Debug log: <pre>'.$client->debug_str.'</pre>';
            if ($asString) {
            	return $str;
            } else {
            	echo $str;
            }
	}
}
