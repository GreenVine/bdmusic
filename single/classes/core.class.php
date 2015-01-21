<?php

/*
	@desc	Core class
*/
class BaiduMusic{

	// Core Functions //
	static private $bdapi_url = "http://musicmini2014.baidu.com/app/link/getLinks.php";
	static public $bdapi_open = 1;

	/*
        @desc Initialize the class
    */
	public function __construct($str){
		
		// Validate String
		$param = $this->chkInputStr(array_change_key_case($str, CASE_LOWER));
		
		// Validate API Status
		if(!self::$bdapi_open) $this->retError(1001);
		
		// Process Data
		$this->procData($this->execQuery($this->buildQuery()));
	}

	/*
        @desc	Validate the input string
        @param	$str: $_REQUEST array
        @return array
    */
	private function chkInputStr($str)
	{
		$filter_positive_num = array("options" => array("min_range" => 1)); // Option used in filter_var() to check whether is a positive number
		$filter_boolean_num = array("options" => array("min_range" => 0, "max_range" => 1)); // Option used in filter_var() to check whether is a boolean number

		// Validation Template
		$paramChk = array( /* $_REQUEST parameters pending to check: When force = 1, will throw fatal error and suspend the following actions; When force = 0 and didn't specify a value, default will be used
								  filter array used to validate data, when func = 1 filter_var() will be used, as well as acceptable values set in include; When func = 0 in_array() will be used */
			"opt" => array(
				"force" => 1,
				"error" => "Output Format",
				"filter" => array(
					"func" => 0,
					"include" => array("xml", "json")
					)
				),
			"id" => array("force" => 1,
				"error" => "Song ID",
				"filter" => array(
					"func" => 1,
					"type" => FILTER_VALIDATE_INT,
					"option" => $filter_positive_num)
				),
			"rate" => array("force" => 0,
				"default" => 9999, // Assume the best rate
				"filter" => array(
					"func" => 1,
					"type" => FILTER_VALIDATE_INT,
					"option" => $filter_positive_num,
					"include" => array("best", "flac")
					)
				),
			"type" => array(
				"force" => 1,
				"error" => "Action Type",
				"filter" => array(
					"func" => 1,
					"type" => FILTER_VALIDATE_INT,
					"option" => $filter_boolean_num)
				),
			"skim" => array("force" => 0,
				"default" => 0,
				"filter" => array(
					"func" => 1,
					"type" => FILTER_VALIDATE_INT,
					"option" => $filter_boolean_num)
				),
			"html" => array(
				"force" => 0,
				"default" => 0,
				"filter" => array(
					"func" => 1,
					"type" => FILTER_VALIDATE_INT,
					"option" => $filter_boolean_num)
				),
			"fetch" => array(
				"force" => 0,
				"default" => 0,
				"filter" => array(
					"func" => 1,
					"type" => FILTER_VALIDATE_INT,
					"option" => $filter_boolean_num)
				),
			"callback" => array(
				"force" => 0, 
				"default" => null,
				"nolowercase" => 1
				)
		);

		// Anti-humanity code BEGIN: DEEP BREATH REQUIRED!!
		foreach ($paramChk as $paramName => $paramCfg) {
			if ($paramChk[$paramName]["force"]) { // A required parameter
				if (!isset($str[$paramName])) $this->retError(2002, $paramChk[$paramName]["error"]); // Really you didn't set it?
				if (isset($paramChk[$paramName]["filter"]) && (isset($paramChk[$paramName]["filter"]) && (!$filter_ret = $paramChk[$paramName]["filter"]["func"] /* Filter Type */ ? ($this->filterVar($paramChk[$paramName]["filter"]["type"] == FILTER_VALIDATE_INT ? (int)$str[$paramName] : $str[$paramName] /* Force convert string to integer when validating integer */, $paramChk[$paramName]["filter"]["type"], isset($paramChk[$paramName]["filter"]["option"]) /* If additional filter options defined */ ? $paramChk[$paramName]["filter"]["option"] : null) || (isset($paramChk[$paramName]["filter"]["include"]) /* If additional acceptable values array defined */ && in_array($str[$paramName], $paramChk[$paramName]["filter"]["include"]))) : in_array($str[$paramName], $paramChk[$paramName]["filter"]["include"]) /* We do not need filterVar(), in_array() only */))) $this->retError(2003, $paramChk[$paramName]["error"], 1);
			} else { // An optional parameter
				if (!isset($str[$paramName]) || (isset($paramChk[$paramName]["filter"]) && (!$filter_ret = $paramChk[$paramName]["filter"]["func"] ? ($this->filterVar($paramChk[$paramName]["filter"]["type"] == FILTER_VALIDATE_INT ? (int)$str[$paramName] : $str[$paramName], $paramChk[$paramName]["filter"]["type"], isset($paramChk[$paramName]["filter"]["option"]) ? $paramChk[$paramName]["filter"]["option"] : null) || (isset($paramChk[$paramName]["filter"]["include"]) && in_array($str[$paramName], $paramChk[$paramName]["filter"]["include"]))) : in_array($str[$paramName], $paramChk[$paramName]["filter"]["include"])))) $str[$paramName] = $paramChk[$paramName]["default"]; // Oooh you have put an invalid value, I won't blame you just overwrite it, sorry -.-
			}
			$this->querydata[$paramName] = isset($paramChk[$paramName]["nolowercase"]) && $paramChk[$paramName]["nolowercase"] ? htmlspecialchars($str[$paramName]) : strtolower(htmlspecialchars($str[$paramName])); // I knew you are a good man but I still need to purify the string
		}
		// Anti-humanity code END: FEEL RELAX (I don't know how I could write these codes but actually I did it, without knowing how to present them more beauitful)

		unset($paramChk); // Free resource
		unset($paramName);
		unset($paramCfg);
		unset($filter_boolean_num);
		unset($filter_positive_num);
		
		return 1; // Oooh HIGH FIVE everything done!!
	}

	/*
        @desc Build base64-ed query string
        @return string
    */
	private function buildQuery(){
		return "param=" . base64_encode(json_encode(array("key" => $this->querydata["id"], "linkType" => 1, "rate" => $this->querydata["rate"], "default" => $this->querydata["type"])));
	}

	/*
        @desc	Execute POST action to Baidu Music REST server with generated query string
        @param	$query_str: base64-ed query string
        @return array
    */
	private function execQuery($query_str){
		if(function_exists("curl_init")){
			$ch = curl_init();

			curl_setopt($ch, CURLOPT_URL, self::$bdapi_url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $query_str);

			$ret = curl_exec($ch);

			if($curl_errno = curl_errno($ch)){ // Error found
				$this->retError(1002, curl_strerror($curl_errno));
			}else{
				curl_close($ch);
				return array("isSucc" => 1, "result" => $this->tidyJson($ret)); // No error then output result
			}
		}
		$this->retError(1003);
	}

	/*
		@desc	Process responded data
		@param	$data: data to be processed
	*/
	private function procData($data){
		if($data["isSucc"]){
			if($this->is_json($data["result"]) && ($ret = json_decode($data["result"], 1)) && $ret["song_id"] == $this->querydata["id"]){ // Make sure song is exists
				if($this->querydata["type"]){
					$this->procMetaData($ret); // Process meta information
				}else{
					$this->procFileData($ret); // Process file information
				}
				return 1;
			}else{
				$this->retError(3001);
			}
		}
		$this->retError(1002, "Error Fetch Data from Server");
	}

	/*
		@desc	Process file-relevant data
		@param	$data: data to be processed
	*/
	private function procFileData($data){
		$rewrite["root"] = array( // Extract useful info
			"song_id"	=> "0SongID",
			"song_title"	=> "0SongTitle",
			"artist_id"	=> "ArtistID",
			"song_artist"	=> "ArtistName",
			"album_id"	=> "AlbumID",
			"album_title"	=> "AlbumTitle",
			"album_image_url"	=> "AlbumImg",
			"lyric_url"	=> "0LrcURL",
			"resource_source"	=> "InfoResource",
		);
		$rewrite["files"] = array(
			"url"	=> "0UrlDownload",
			"display_url"	=> "UrlDisplay",
			"format"	=> "0FileFormat",
			"hash"	=> "0FileHash",
			"size"	=> "0FileSize",
			"kbps"	=> "0FileRate",
			"duration"	=> "0FileDuration",
			"is_hq"	=> "IsHQ"
		);
		
		$resp = array("ErrCode" => "0000"); // Tell them we succeed
		foreach($rewrite["root"] as $old => $new){
			if((isset($data[$old]) && !$this->querydata["skim"]) /* If not in skim mode */ || (isset($data[$old]) && $this->querydata["skim"] && substr($new, 0, 1) == "0") /* If in skim mode and found a item */) $resp[substr($new, 0, 1) == "0" ? substr($new, 1, strlen($new) - 1) : $new] = $data[$old]; // Put it into a new array
		}

		foreach($rewrite["files"] as $old => $new){
			if((isset($data["file_list"][0][$old]) && !$this->querydata["skim"]) || (isset($data["file_list"][0][$old]) && $this->querydata["skim"] && substr($new, 0, 1) == "0")) $resp["Files"][substr($new, 0, 1) == "0" ? substr($new, 1, strlen($new) - 1) : $new] = $data["file_list"][0][$old];
		}
		
		unset($rewrite);
		
		$this->optResult($resp); // Then output it!
	}

	/*
		@desc	Process meta-relevant data
		@param	$data: data to be processed
	*/
	private function procMetaData($data){
		$rewrite["root"] = array(
			"song_id"	=> "0SongID",
			"song_title"	=> "0SongTitle",
			"artist_id"	=> "ArtistID",
			"song_artist"	=> "ArtistName",
			"album_id"	=> "AlbumID",
			"album_title"	=> "AlbumTitle",
			"album_image_url"	=> "AlbumImg",
			"resource_source"	=> "InfoResource",
		);
		$rewrite["files"] = array(
			"format"	=> "0FileFormat",
			"hash"	=> "0FileHash",
			"size"	=> "0FileSize",
			"kbps"	=> "0FileRate",
			"duration"	=> "FileDuration",
			"is_hq"	=> "IsHQ"
		);
		
		$resp = array("ErrCode" => "0000");
		foreach($rewrite["root"] as $old => $new){
			if((isset($data[$old]) && !$this->querydata["skim"]) || (isset($data[$old]) && $this->querydata["skim"] && substr($new, 0, 1) == "0")) $resp[substr($new, 0, 1) == "0" ? substr($new, 1, strlen($new) - 1) : $new] = $data[$old];
		}
		
		for($i = 0; $i < count($data["file_list"]); $i++){
			foreach($rewrite["files"] as $old => $new){
				if((isset($data["file_list"][0][$old]) && !$this->querydata["skim"]) || (isset($data["file_list"][0][$old]) && $this->querydata["skim"] && substr($new, 0, 1) == "0")) $resp["Files"][$i][substr($new, 0, 1) == "0" ? substr($new, 1, strlen($new) - 1) : $new] = $data["file_list"][$i][$old];
			}
		}
		
		unset($rewrite);
		
		$this->optResult($resp);
	}


	// Extended Functions //

	/*
		@desc	Tidy JSON and remove unwanted characters which may prevent json_decode() from decoding successfully
		@param	$str: string to tidy
		@return	string
	*/
	public function tidyJson($str){
		return 0 === strpos(bin2hex($str), 'efbbbf') ? substr($str, 3) : $str; // Fix JSON malformation in binary level
	}
	
	/*
        @desc	Rewrite function for filter_var(), made it simpler
        @param	$var: variable to filter; $filter: the ID of the filter to apply; $opt: associative array of options or bitwise disjunction of flags
        @return	boolean
    */
	public function filterVar($var, $filter, $opt = null){
		return filter_var($var, $filter, $opt) === $var ? 1 : 0;
	}

	/*
		@desc	Check whether a string is JSON
		@param	$str: string to check
		@return boolean
	*/
	public function is_json($str){
		json_decode($str);
		return (json_last_error() == JSON_ERROR_NONE);
	}
	
	/*
		@desc	Output result with varities of format
		@param	$data: array to output
	*/
	public function optResult($data){
		if(isset($this->querydata["opt"])){
			switch($this->querydata["opt"]){
				case "json":
					if(isset($this->querydata["html"]) && $this->querydata["html"]) header('Content-Type: text/html; charset=utf-8');
					else header("Content-Type: application/json; charset=utf-8"); // Control output content type

					echo !isset($this->querydata["callback"]) || is_null($this->querydata["callback"]) || $this->querydata["callback"] == "" ? json_encode($data) : $this->querydata["callback"] . "(" . json_encode($data) . ")";
					exit;
				case "xml":
					$xml = Array2XML::createXML('BaiduMusicResp', $data);
					if(isset($this->querydata["html"]) && $this->querydata["html"]) header('Content-Type: text/html; charset=utf-8');
					else header("Content-Type: application/json; charset=utf-8");

					echo $xml->saveXML();
					exit;
			}
		}else{
			die("ERR2001: Invalid Output Format");
		}
	}
	
	/*
        @desc	Display error information
        @param	$code: error code; $detail: optional detailed information (for debug); $die: terminate the script
    */
	public function retError($code, $detail = null, $die = 1){
		$this->errList = array(
			/* Internal Layer */
			0000	=> "Success",
			0001	=> "Internal Error",
			0002	=> "Version Expired (Upgrade Required)",
			/* API Self & Baidu Server Layer */
			1001	=> "API Service Unavailable",
			1002	=> "Fetch Error",
			1003	=> "Server Misconfigured",
			/* User Input Layer */
			2001	=> "Invalid Output Format",
			2002	=> "Missing Parameter",
			2003	=> "Invalid Parameter",
			/* Response Layer */
			3001	=> "Song Not Exists",
			/* Storage Layer */
			8001	=> "I/O Error",
			/* Security Layer */
			9000	=> "Request Denied",
			9001	=> "IP Blocked",
			9002	=> "Connections Beyond Limit"
		);
		
		$errOpt = array("ErrCode" => $code, "ErrMsg" => $this->errList[$code] . (is_null($detail) ? null : " ($detail)"));
		
		self::optResult($errOpt);
		if($die) die;
	}
}
?>
