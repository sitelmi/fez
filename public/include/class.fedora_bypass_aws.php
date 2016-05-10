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
include_once(APP_INC_PATH . "class.aws.php");
include_once(APP_INC_PATH . "class.fedora_api_interface.php");

class Fedora_API implements FedoraApiInterface {

	/**
	 * Gets the next available persistent identifier.
	 *
	 * @return string $pid The next available PID in from the PID handler
	 */
	public function getNextPID()
	{
		$digObj = new DigitalObject();
		$pid = $digObj->save(array());
		return $pid;
	}

	/**
	 * Gets the XML of a given object by PID.
	 *
	 * @param string $pid The persistent identifier
	 * @return string $result The XML of the object
	 */
	public function getObjectXML($pid)
	{

	}

	/**
	 * Gets the audit trail for an object.
	 *
	 * @param string $pid The persistent identifier
	 * @return array of audit trail
	 */
	public function getAuditTrail($pid)
	{

	}

	/**
	 * This function ingests a FOXML object and base64 encodes it
	 *
	 * @access  public
	 * @param string $foxml The XML object itself in FOXML format
	 * @param string $pid The persistent identifier
	 * @return bool
	 */
	public function callIngestObject($foxml, $pid = "")
	{

	}

	/**
	 * Exports an associative array
	 *
	 * @param string $pid
	 * @param string $format
	 * @param string $context
	 * @return array
	 */
	public function export($pid, $format = "info:fedora/fedora-system:FOXML-1.0", $context = "migrate")
	{

	}

	/**
	 * Returns an associative array
	 *
	 * @param array $resultFields
	 * @param int $maxResults
	 * @param string $query_terms
	 * @return array
	 */
	public function callFindObjects($resultFields = array(
		'pid',
		'title',
		'identifier',
		'description',
		'state'
	), $maxResults = 10, $query_terms = "")
	{

	}

	/**
	 * Resumes a find
	 *
	 * @param string $token
	 * @return array
	 */
	public function callResumeFindObjects($token)
	{

	}

	/**
	 * This function uses Fedora's simple search service which only really works against Dublin Core records.
	 * @param string $query The query by which the search will be carried out.
	 *		See http://www.fedora.info/wiki/index.php/API-A-Lite_findObjects#Parameters: for
	 *		documentation of the syntax of the query.
	 * @param array $fields The list of DC and Fedora basic fields to search against.
	 * @return array $resultList The search results.
	 */
	// Deprecate this function and replace calls to it
	//public function searchQuery($query, $fields = array('pid', 'title'));

	/**
	 * This function removes an object and all its datastreams from Fedora
	 *
	 * @param string $pid The persistent identifier of the object to be purged
	 * @return bool
	 */
	public function callPurgeObject($pid)
	{

	}

	/**
	 * This function uses curl to upload a file into the fedora upload manager and calls the addDatastream or modifyDatastream as needed.
	 *
	 * @access  public
	 * @param string $pid The persistent identifier of the object to be purged
	 * @param string $dsIDName The datastream name
	 * @param string $file The file name
	 * @param string $dsLabel The datastream label
	 * @param string $mimetype The mimetype of the datastream
	 * @param string $controlGroup The control group of the datastream
	 * @param string $dsID The ID of the datastream
	 * @param bool|string $versionable Whether to version control this datastream or not
	 * @return string
	 */
	public function getUploadLocation($pid, $dsIDName, $file, $dsLabel, $mimetype = 'text/xml', $controlGroup = 'M', $dsID = NULL, $versionable = FALSE)
	{
		$file_full = $file;

		$versionable = $versionable === true ? 'true' : $versionable === false ? 'false' : $versionable;
		$dsExists = Fedora_API::datastreamExists($pid, $dsIDName, true);

		if ($dsExists !== true) {
			Fedora_API::callAddDatastream($pid, $dsIDName, $file_full, $dsLabel, "A", $mimetype, $controlGroup, $versionable, '');
		}

		if (is_file($file_full)) {
			unlink($file_full);
		}
		return $dsIDName;
	}

	/**
	 * This function uses curl to get a file from a local file location and upload it into the fedora upload manager and calls the addDatastream or modifyDatastream as needed.
	 *
	 * Developer Note: Mainly used by batch import of a SAN directory
	 *
	 * @param string $pid The persistent identifier of the object to be purged
	 * @param string $dsIDName The datastream name
	 * @param string $dsLocation The location of the file on a local server directory
	 * @param string $dsLabel The datastream label
	 * @param string $mimetype The mimetype of the datastream
	 * @param string $controlGroup The control group of the datastream
	 * @param string $dsID The ID of the datastream
	 * @param bool|string $versionable Whether to version control this datastream or not
	 * @return integer
	 */
	public function getUploadLocationByLocalRef($pid, $dsIDName, $dsLocation, $dsLabel, $mimetype, $controlGroup = 'M', $dsID = NULL, $versionable = FALSE)
	{
		$success = 0;
		if (! Zend_Registry::isRegistered('version')) {
			Zend_Registry::set('version', Date_API::getCurrentDateGMT());
		}

		$now = Zend_Registry::get('version');

		$resourceDataLocation = $dsLocation;
		$filesDataSize = filesize($dsLocation);

		$aws = new AWS();
		$dataPath = Fedora_API::getDataPath($pid);
		if ($aws->postFile($dataPath, $dsLocation)) {
			$success = 1;
		}

		return $success;
	}

	/**
	 * This function returns what the path should be for a PID in S3
	 *
	 * @param string @pid
	 * @return string
	 */
	private function getDataPath($pid) {
		return "data/".str_replace(":", "_", $pid);
	}

	/**
	 * This function adds datastreams to object $pid.
	 *
	 * @param string $pid The persistent identifier of the object to be purged
	 * @param string $dsID The ID of the datastream
	 * @param string $dsLocation The location of the file to add
	 * @param string $dsLabel The datastream label
	 * @param string $dsState The datastream state
	 * @param string $mimetype The mimetype of the datastream
	 * @param string $controlGroup The control group of the datastream
	 * @param bool|string $versionable Whether to version control this datastream or not
	 * @param string $xmlContent If it an X based xml content file then it uses a var rather than a file location
	 * @param int $current_tries A counter of how many times this function has retried the addition of a datastream
	 * @return void
	 */
	public function callAddDatastream($pid, $dsID, $dsLocation, $dsLabel, $dsState, $mimetype, $controlGroup = 'M', $versionable = FALSE, $xmlContent = "", $current_tries = 0)
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

		if (! Zend_Registry::isRegistered('version')) {
			Zend_Registry::set('version', Date_API::getCurrentDateGMT());
		}

		$now = Zend_Registry::get('version');

//		$resourceDataLocation = $dsLocation;
//		$filesDataSize = filesize($dsLocation);
//		$meta = array('mimetype' => $mimetype,
//			'filename' => $dsIDName,
//			'label' => $dsLabel,
//			'controlgroup' => 'M',
//			'state' => 'A',
//			'size' => $filesDataSize,
//			'updateTS' => $now,
//			'pid' => $pid);
//		$dsr = new DSResource(APP_DSTREE_PATH, $resourceDataLocation, $meta);
//		$dsr->save();

		$aws = new AWS();
		$dataPath = Fedora_API::getDataPath($pid);
		if ($aws->postFile($dataPath, $dsLocation)) {
			$success = 1;
		}
		unlink($dsLocation);
	}

	/**
	 *This function creates an array of all the datastreams for a specific object.
	 *
	 * @param string $pid The persistent identifier of the object
	 * @param string $createdDT Fedora timestamp of version to retrieve
	 * @param string $dsState The datastream state
	 * @return array $dsIDListArray The list of datastreams in an array.
	 */
	public function callGetDatastreams($pid, $createdDT = NULL, $dsState = 'A')
	{

		$aws = new AWS();
		$dataPath = Fedora_API::getDataPath($pid);
		$datastreams = array();
		$dsIDListArray = $aws->listObjects($dataPath);

		foreach ($dsIDListArray as $object) {
			$baseKey = basename($object['Key']);
			if ($baseKey != basename($dataPath)) {
				$ds = array();
				//TODO: Add created date and mimetype from custom metadata PUT onto the object by Fez
				$ds['controlGroup'] = "M";
				$ds['ID']           = $baseKey;
				$ds['versionID']    = $object['VersionId'];
//				$ds['altIDs']       = "";
				$ds['label']        = $baseKey;
				$ds['versionable']  = "true";
//				$ds['MIMEType']     = $object['ContentType'];
				$ds['formatURI']    = "";
				$ds['createDate']   = (string)$object['LastModified'];
				$ds['size']         = $object['Size'];
				$ds['state']        = 'A';
//				$ds['location']     = $object['location'];
				$ds['checksumType'] = "MD5";
				$ds['checksum']     = $object['eTag'];
				$datastreams[] = $ds;
			}
		}

		return $datastreams;
	}

	/**
	 * This function creates an array of all the datastreams for a specific object using the API-A-LITE rather than soap
	 *
	 * @param string $pid The persistent identifier of the object
	 * @param bool $refresh Avoid a cached copy
	 * @param int $current_tries A counter of how many times this function has retried the addition of a datastream
	 * @return array $dsIDListArray The list of datastreams in an array.
	 */
	public function callListDatastreamsLite($pid, $refresh = FALSE, $current_tries = 0)
	{
		$log = FezLog::get();
		$db = DB_API::get();

		if (!is_numeric($pid)) {

			$rows = array();

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

	/**
	 * @param string $pid The persistent identifier of the object
	 * @param bool $refresh
	 * @return bool
	 */
	public function objectExists($pid, $refresh = FALSE)
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
	 * @param string $pid The persistent identifier of the object
	 * @param string $dsID The ID of the datastream
	 * @param string $createdDT Date time stamp as a string
	 * @return array The requested of datastream in an array.
	 */
	public function callGetDatastream($pid, $dsID, $createdDT = NULL)
	{
		$aws = new AWS();
		$dataPath = Fedora_API::getDataPath($pid);

		$dsArray = $aws->getObject($dataPath."/".$dsID, $createdDT);
		$dsData = array();

		$dsData['versionID'] = $dsArray['VersionId'];
		$dsData['label'] = ''; //TODO: convert to use PUT'd metadata for label
		$dsData['controlGroup'] = "M";
		$dsData['MIMEType'] = $dsArray['ContentType'];
		$dsData['createDate'] = (string)$dsArray['LastModified']; //TODO: convert to saved meta
		$dsData['location'] = NULL; //TODO Check if this is needed and if so fill with a real value.
		$dsData['formatURI'] = NULL; //TODO Check if this is needed and if so fill with a real value.
		$dsData['checksumType'] = 'MD5';
		$dsData['checksum'] = str_replace('"', '', $dsArray['ETag']);
		$dsData['versionable'] = FALSE; //TODO Check if this is needed and if so fill with a real value.
		$dsData['size'] = $dsArray['ContentLength'];
		$dsData['state'] = 'A';

		return $dsData;
	}

	/**
	 * Does a datastream with a given ID already exist in an object
	 *
	 * @param string $pid The persistent identifier of the object
	 * @param string $dsID The ID of the datastream to be checked
	 * @param bool $refresh Avoid a cached copy
	 * @param bool $pattern a regex pattern to search against if given instead of ==/equivalence
	 * @return boolean
	 */
	public function datastreamExists($pid, $dsID, $refresh = FALSE, $pattern = FALSE)
	{
		if (Misc::isPid($pid) != true) {
			return false;
		}

		$aws = new AWS();
		$dataPath = Fedora_API::getDataPath($pid);
		$dsExists = $aws->checkExistsById($dataPath, $dsID); //TODO: implement $pattern if necessary

		return $dsExists;
	}

	/**
	 * Does a datastream with a given ID already exist in existing list array of datastreams
	 *
	 * @param string $existing_list The existing list of datastreams
	 * @param string $dsID The ID of the datastream to be checked
	 * @return boolean
	 */
	public function datastreamExistsInArray($existing_list, $dsID)
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
	 * @param string $pid The persistent identifier of the object
	 * @param string $dsID The ID of the datastream to be checked
	 * @param string $asofDateTime Gets a specified version at a datetime stamp
	 * @return array The datastream returned in an array
	 */
	public function callGetDatastreamDissemination($pid, $dsID, $asofDateTime = "")
	{
		$return = array();

		$aws = new AWS();
		$dataPath = Fedora_API::getDataPath($pid);
		$return['stream'] = $aws->getFileContent($dataPath, $dsID, $asofDateTime);

		return $return;
	}

	/**
	 * This function creates an array of a specific datastream of a specific object
	 *
	 * @param string $pid The persistent identifier of the object
	 * @param string $dsID The ID of the datastream
	 * @param boolean $getraw Get as xml
	 * @param resource $filehandle
	 * @param int $current_tries A counter of how many times this function has retried
	 * @return array $resultlist The requested of datastream in an array.
	 */
	public function callGetDatastreamContents($pid, $dsID, $getraw = FALSE, $filehandle = NULL, $current_tries = 0)
	{
		// $filehandle is a legacy arg left here to keep the API intact.

		$dsExists = Fedora_API::datastreamExists($pid, $dsID);
		if($dsExists)
		{
			$dsMeta = Fedora_API::callGetDatastream($pid, $dsID);
			if($dsMeta['mimetype'] != 'text/xml' || $getraw)
			{
				$return =  Fedora_API::callGetDatastreamDissemination($pid, $dsID);
				$return = $return['stream'];
			}
			else
			{
				// do not implement until needed - this is probably dead - only happens twice in system, and not relevant for bypass
			}
			return $return;
		}
	}

	/**
	 * This function creates an array of specific fields from a specific datastream of a specific object
	 *
	 * @param string $pid The persistent identifier of the object
	 * @param string $dsID The ID of the datastream
	 * @param array $returnfields
	 * @param string $asOfDateTime Gets a specified version at a datetime stamp
	 * @return array The requested of datastream in an array.
	 */
	public function callGetDatastreamContentsField($pid, $dsID, $returnfields, $asOfDateTime = "")
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
	 * @param string $pid The persistent identifier of the object
	 * @param string $dsID The name of the datastream
	 * @param string $state The datastream state
	 * @param string $label The datastream label
	 * @param string $dsContent The datastream content
	 * @param string $mimetype The mimetype of the datastream
	 * @param bool|string $versionable Whether to version control this datastream or not
	 * @return void
	 */
	public function callModifyDatastreamByValue($pid, $dsID, $state, $label, $dsContent, $mimetype = 'text/xml', $versionable = 'inherit')
	{
		$tempFile = APP_TEMP_DIR . str_replace(":", "_", $pid) . "_" . $dsID . ".xml";
		$fp = fopen($tempFile, "w");
		if (fwrite($fp, $dsContent) === FALSE) {
			$err = "Cannot write to file ($tempFile)";
			$this->log->err(array($err, __FILE__, __LINE__));
			exit;
		}
		fclose($fp);

		self::callAddDatastream($pid, $dsID, $tempFile, $label, "A", $mimetype, "M", $versionable, '');
	}

	/**
	 * This function modifies non-in-line datastreams, either a chunk o'text, a url, or a file.
	 *
	 * @param string $pid The persistent identifier of the object
	 * @param string $dsID The name of the datastream
	 * @param string $dsLabel The datastream label
	 * @param string $dsLocation The location of the datastream
	 * @param string $mimetype The mimetype of the datastream
	 * @param bool|string $versionable Whether to version control this datastream or not
	 * @return void
	 */
	public function callModifyDatastreamByReference($pid, $dsID, $dsLabel, $dsLocation = NULL, $mimetype, $versionable = 'inherit')
	{
			$aws = new AWS();
			$dataPath = Fedora_API::getDataPath($pid);
			$aws->rename($dataPath, $dsID, $dataPath, $dsID, $dsLabel);
	}

	/**
	 * Changes the state and/or label of the object.
	 *
	 * @param string $pid The pid of the object
	 * @param string $state The new state, A, I or D. Null means leave unchanged
	 * @param string $label The new label. Null means leave unchanged
	 * @param string $logMessage A log message
	 */
	public function callModifyObject($pid, $state, $label, $logMessage = 'Deleted by Fez')
	{
		// This does not need to be implemented because we don't store object state in s3, just datastreams
	}

	/**
	 * This function marks a datastream as deleted by setting the state.
	 *
	 * @param string $pid The persistent identifier of the object
	 * @param string $dsID The ID of the datastream
	 * @return bool
	 */
	public function deleteDatastream($pid, $dsID)
	{
		$aws = new AWS();
		$dataPath = Fedora_API::getDataPath($pid);
		return $aws->deleteById($dataPath, $dsID);
	}

	/**
	 * This function deletes a datastream
	 *
	 * @param string $pid The persistent identifier of the object to be purged
	 * @param string $dsID The name of the datastream
	 * @param string $startDT The start datetime of the purge
	 * @param string $endDT The end datetime of the purge
	 * @param string $logMessage
	 * @param bool $force
	 * @return bool
	 */
	public function callPurgeDatastream($pid, $dsID, $startDT = NULL, $endDT = NULL, $logMessage = "Purged Datastream from Fez", $force = FALSE)
	{
		$aws = new AWS();
		$dataPath = Fedora_API::getDataPath($pid);
		return $aws->purgeById($dataPath, $dsID);
	}
}
