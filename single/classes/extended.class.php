<?php
/*
	@desc	General usage
*/
class ExtendedFunc{

	/*
		@desc	Tidy JSON and remove unwanted characters which may prevent json_decode() from decoding successfully
		@param	$str: string to tidy
		@return	string
	*/
	public function tidyJson($str){
		if (0 === strpos(bin2hex($str), 'efbbbf')) { // Fix JSON malformation in binary level
		   return substr($str, 3);
		}
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
					if(!$this->querydata["html"]) header('Content-Type: application/json; charset=utf-8');
					echo is_null($this->querydata["callback"]) || $this->querydata["callback"] == "" ? json_encode($data) : $this->querydata["callback"] . "(" . json_encode($data) . ")";
					break;
				case "xml":
					$xml = Array2XML::createXML('BaiduMusicResp', $data);
					if(!$this->querydata["html"]) header("Content-type: text/xml; charset=utf-8");
					echo $xml->saveXML();
					break;
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