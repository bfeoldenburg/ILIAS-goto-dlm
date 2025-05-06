<?php
# ilias class - Kapselung der ILIAS-Zugriffe
#
# Hinweis zur Nutzung: Bei Fehler werfen die meisten Funktionen eine Exception.

class IliasConnection {
	### Einstellungen für die ILIAS-Plattform:
	# 
	private $soapurl = "https://goto.bfe-elearning.de/webservice/soap/server.php?wsdl";
	private $soapdb = "c-il-goto";
	private $soapuser = "soap-admin";
	private $soappass = "!bfe123!";
	private $createuserDefaultRoleID = 713; //ID der Rolle BBS-User
		# Benutzer-standard: ist die obj_id aus der URL von Admin->Rollen->"Benutzername"
		# wird beim Anlegen von Personen als Default-Rolle genutzt.
	private $globalUserRoleID = "il_0_role_713";
	private $createuserDefaultReferrer = "SOAP";
	private $LogFILENAME = "/var/log/ilias/il_goto/il_goto_soap.log"; # z.B. "/tmp/T41-ILIASLOG.txt", um diese Funktion zu nutzen

	### Interne Variablen:
	private $soapsession = NULL;
	private $soapsid = NULL;

	/**************************************************************************
	 * __construct - keine Aktion nötig.
	 * Verbindung wird ggf. erst bei Bedarf aufgebaut!
	 */
	public function __construct()
	{
		#$this->ILIASDEBUG("Constructor called",0);
		#error_reporting( E_ALL );
		return	TRUE;
	}
	public function __destruct() {
		#$this->ILIASDEBUG("Destructor called",0);
		$this->soap_disconnect();
		return	TRUE;
        }
	/**************************************************************************
	 * ILIASDEBUG
	 * Schreibe eine Zeichenkette mit Zeitstempel in die Logdatei
	 * KEINERLEI Rückmeldung bei Misserfolg.
	 */
	private function ILIASDEBUG($text, $severity = 5) {
		try {
			if ( ! is_string($this->LogFILENAME) ) { return; }
			if ( strlen($this->LogFILENAME) < 1 ) { return; }
			if ( ! file_exists($this->LogFILENAME) ) { return; }
			if ( $severity < 1 ) { return; } # Grösser setzen für weniger Debug.
			if ( (!isset($text)) || (NULL === $text) )	{ return; }
			$text = trim(str_replace(array("\r","\n","\t")," ",$text));
			if ( strlen($text) < 1 )	{ return; }
			$text = strftime("%Y-%m-%d %H:%M:%S   ").$text."\n";
			if ( NULL == ($pf = @fopen($this->LogFILENAME,"a")) )	{ return; }
			@fwrite($pf,$text);
			@fclose($pf);
		} catch ( Exception $e ) { ; }
		return;
	}
	/**************************************************************************
	 * soap_connect
	 * Verbinde mit ILIAS (per SOAP) oder wirf eine Exception
	 * Rückgabewert bei Erfolg oder bestehender Verbindung: SOAP-Session-ID
	 */
	public function soap_connect() {
		if ( NULL !== $this->soapsession ) {
			if ( NULL !== $this->soapsid ) {
				return $this->soapsid;
			}
			$this->soapsession = NULL;
		}
		$this->ILIASDEBUG("soap_connect called",0);
		try {
			//$this->soapsession = new SoapClient($this->soapurl);
			$this->soapsession = new SoapClient($this->soapurl,array('trace' => 1, 'encoding' => 'UTF-8', 'cache_wsdl' => WSDL_CACHE_MEMORY));
			$sid=$this->soapsession->login($this->soapdb,$this->soapuser,$this->soappass);
		} catch(SoapFault $a) {
			$this->ILIASDEBUG("ERR:soap_connect [SoapFault:".print_r($a,TRUE)."]");
			throw new Exception("SOAP connection - login");
		} catch(Expection $e) {
			$this->ILIASDEBUG("ERR:soap_connect [genericFault:".print_r($e,TRUE)."]");
			$sid = NULL;
		}
		if ( ( NULL === $sid ) || ( strlen($sid) < 2 ) ) {
			$this->ILIASDEBUG("ERR:soap_connect [ConnectionID returned was (".$sid.")]");
			throw new Exception("SOAP session ID");
		}
		$this->soapsid = $sid;
		return($sid);
	}
	/**************************************************************************
	 * soap_disconnect
	 * Falls eine SOAP-Verbindung besteht, sauber beenden.
	 * In jedem Fall ohne Fehlermeldung zurückkehren.
	 */
	function soap_disconnect() {
		if ( NULL === $this->soapsession )	{ return; }
		try {
			$this->soapsession->logout($this->soapsid);
			$this->ILIASDEBUG("soap_disconnect OK",0);
		} catch ( SoapFault $a ) {
			$this->ILIASDEBUG("ERR:soap_disconnect failed",2);
		} catch ( Exception $e ) { ; }
		$this->soapsession = NULL;
		$this->soapsid = NULL;
		return;
	}
	function getProgressInfo($refID, $lp_filter) {
		try {
			$this->soap_connect();
		} catch ( Exception $e ) {
			return 0;
		}
		try {
			$res = $this->soapsession->getProgressInfo($this->soapsid,$refID,$lp_filter);
		} catch(SoapFault $a) {
			$this->ILIASDEBUG("ERR: getProgressInfo(".$refID.") failed with SoapFault{".
			 print_r($a, TRUE)."}");
			return	0;
		}
		return $res;
	}

	function getCourseXML($crsID) {
		try {
			$this->soap_connect();
		} catch ( Exception $e ) {
			return 0;
		}
		try {
			$res = $this->soapsession->getCourseXML($this->soapsid,$crsID);
		} catch(SoapFault $a) {
			$this->ILIASDEBUG("ERR: getCourseXML(".$crsID.") failed with SoapFault{".
			 print_r($a, TRUE)."}");
			return	0;
		}
		return $res;
	}

	/**************************************************************************
	 * finduserhandle
	 * Suche zur gegebenen Login-Kennung die numerische ILIAS-Kennung/"handle"
	 * Rückgabe Ganzzahl: 0=Fehler, >0: Handle
	 * Wirft KEINE Exception bei Fehler.
	 */
	function finduserhandle($loginname) {
		try {
			$this->soap_connect();
		} catch ( Exception $e ) {
			return -2;
		}
		try {
			$res = $this->soapsession->lookupUser($this->soapsid,$loginname);
		} catch(SoapFault $a) {
			$this->ILIASDEBUG("ERR: finduserhandle(".$loginname.") failed with SoapFault{".
			 print_r($a, TRUE)."}");
			return	0;
		}
		$res = @intval($res);
		return ( $res < 1 ? 0 : $res );
	}
	/**************************************************************************
	 * getuserinfo
	 * Rufe zu einem bekannten userhandle (numerisch) die Struktur userinfo ab
	 * Rückgabe: Benanntes Array mit ILIAS-Daten.
	 * wirft Exception bei Fehler.
	 */
	function getuserinfo($iliasid) {
		if ( (!isset($iliasid)) || (NULL===$iliasid) || ( 1 > ($iliasid=intval($iliasid)) ) ) {
			throw new Exception("ERR:ILIAS-GETUSER:PARAMS-INVALID");
		}
		try {
			$this->soap_connect();
		} catch ( Exception $e ) {
			throw new Exception ("ERR:ILIASCONNECT:".$e->getMessage());
		}
		try {
			$res = $this->soapsession->getUser($this->soapsid,intval($iliasid?:0));
		} catch ( SoapFault $a ) {
			$this->ILIASDEBUG("ERR: getuserinfo(".intval($iliasid).") failed with ".
				"SoapFault{".print_r($a,TRUE)."}");
			throw new Exception ("ERR:ILIAS-GETUSER:".$a->getMessage());
		}
		if ( (! isset($res)) || (NULL === $res) ) {
			throw new Exception ("ERR:ILIAS-GETUSER:NotArray:".print_r($res,TRUE));
		}
		return $res;
	}
	/**************************************************************************
	 * createuser
	 * Erzeugt einen Eintrag in der ILIAS-Benutzerdatenbank
	 * Rückgabe: Numerische ILIAS-Kennung/"handle"
	 * wirft Exception bei Fehler.
	 */
	function createuser($daten = array("vorname" => NULL, "nachname" => NULL,
			"titel" => NULL, "passwort" => NULL, "email" => NULL, "geschlecht" => NULL,
			"loginname" => NULL, "gueltigtage" => 0, "referrer" => NULL,
			"agbok" => TRUE, "bbsname" => NULL ) )
	{
		$this->soap_connect();
		if ( !isset($daten) || !is_array($daten) ) {
			throw new Exception("createuser: noparams");
		}
		$uinfo = new stdClass;
		if ( isset($daten["referrer"]) && is_string($daten["referrer"]) && (0<strlen($daten["referrer"]))){
			$uinfo->referral_comment = trim($daten["referrer"]);
		} else {
			$uinfo->referral_comment = $this->createuserDefaultReferrer;
			# Unbekannt, ob der Nutzer selbst das in ILIAS sehen kann. Es kann nicht schaden, dort
			# etwas sinnvolles einzutragen
		}
		$uinfo->usr_id = 0;
		if ( (!isset($daten["vorname"])) || ( NULL === $daten["vorname"] ) ) {
			throw new Exception("createuser: vorname NULL");
		}
		$uinfo->firstname = trim($daten["vorname"]);
		if ( (!isset($daten["nachname"])) || ( NULL === $daten["nachname"] ) ) {
			throw new Exception("createuser: nachname NULL");
		}
		$uinfo->lastname = trim($daten["nachname"]);
		if ( (!isset($daten["passwort"])) || ( NULL === $daten["passwort"] ) ) {
			throw new Exception("createuser: passwort NULL");
		}
		if ( strlen($daten["passwort"]) < 2 ) {
			# Wenn kein Passwort übergeben wird, zufällig setzen (muss dann eben
			# bei Bedarf geändert werden)
			$daten["passwort"] = substr(md5(rand(100000,999999).time()),3,8);
		}
		$uinfo->passwd = md5($daten["passwort"]);
		if ( (!isset($daten["titel"])) || ( NULL === $daten["titel"] ) ) {
			$daten["titel"] = "";
		}
		$uinfo->title = trim($daten["titel"]);
		if ( (!isset($daten["email"])) || ( NULL === $daten["email"] ) ) {
			throw new Exception("createuser: email NULL");
		}
		$uinfo->email = trim($daten["email"]);
		if ( (!isset($daten["loginname"])) || ( NULL === $daten["loginname"] ) ) {
			$daten["loginname"] = trim($daten["email"]);
		}
		# Ist die gewünschte Kennung überhaupt noch frei?
		if ( 0 != $this->finduserhandle($daten["loginname"]) ) {
			throw new Exception("createuser_handle_exists");
		}
		$uinfo->login = $daten["loginname"];
		if ( (!isset($daten["geschlecht"])) || ( NULL === $daten["geschlecht"] ) ) {
			throw new Exception("createuser: geschlecht NULL");
		}
		$uinfo->gender = ( strstr("_fFwW", $daten["geschlecht"]) ? "f" : "m" ) ;
		//$uinfo->gender = trim($daten["geschlecht"]);
		$uinfo->street = $uinfo->city = $uinfo->zipcode =
		$uinfo->country = $uinfo->phone_office = $uinfo->hobby =
		$uinfo->phone_home = $uinfo->phone_mobile = $uinfo->fax = $uinfo->matriculation = "";
		$uinfo->last_login = $uinfo->last_update = $uinfo->create_date = time();
		$uinfo->import_id = NULL;
		$uinfo->time_limit_owner = 7;
		$uinfo->time_limit_message = 0;
		$uinfo->active = 1; # Sonst taucht Account nicht auf
		$uinfo->accepted_agreement = @($daten["agbok"]) ? TRUE : FALSE;
		$uinfo->approve_date = strftime("%Y-%m-%d %H:%M:%S", time());
		if ( isset($daten["gueltigtage"]) &&
		 ( 0 < ( $tlimit = intval($daten["gueltigtage"]) ) ) ) {
			$uinfo->time_limit_unlimited = 0;
			$uinfo->time_limit_from = time(); //0;
			$uinfo->time_limit_until = time() + ($tlimit * 24*3600) + 86400;
		} else {
			$uinfo->time_limit_unlimited = 1;
			$uinfo->time_limit_from = 0;
			$uinfo->time_limit_until = 0;
		}
		$uinfo->user_skin = "";
		$uinfo->user_style = "";
		$uinfo->user_language = "de";
		if ( (!isset($daten["bbsname"])) || ( NULL === $daten["bbsname"] ) ) {
			$uinfo->institution = "";
		} else {
			$daten["bbsname"] = trim($daten["bbsname"]);
			$uinfo->institution = $daten["bbsname"];
		}
		if ( (!isset($daten["beruf"])) || ( NULL === $daten["beruf"] ) ) {
			$uinfo->department = "";
		} else {
			$daten["beruf"] = trim($daten["beruf"]);
			$uinfo->department = $daten["beruf"];
		}

		$usr_xml = '<?xml version="1.0" encoding="UTF-8"?>'.
		'<Users>'.
			'<User Id="'.$uinfo->login.'" Language="de" Action="Insert">'.
  				'<Active><![CDATA[true]]></Active>'.
				'<Role Id="'.$this->globalUserRoleID.'" Type="Global" Action="Assign"><![CDATA['.$this->globalUserRoleID.']]></Role>'.
  				'<Login><![CDATA['.$uinfo->login.']]></Login>'.
  				'<Password Type="PLAIN"><![CDATA['.$daten["passwort"].']]></Password>'.
  				'<Gender><![CDATA['.$uinfo->gender.']]></Gender>'.
  				'<Firstname><![CDATA['.$uinfo->firstname.']]></Firstname>'.
  				'<Lastname><![CDATA['.$uinfo->lastname.']]></Lastname>'.
  				'<Email><![CDATA['.$uinfo->email.']]></Email>'.
				'<Institution><![CDATA['.$uinfo->institution.']]></Institution>'.  
				'<Department><![CDATA['.$uinfo->department.']]></Department>'.  
				'<TimeLimitUnlimited><![CDATA['.$uinfo->time_limit_unlimited.']]></TimeLimitUnlimited>'.
  				'<TimeLimitFrom><![CDATA['.$uinfo->time_limit_from.']]></TimeLimitFrom>'.
  				'<TimeLimitUntil><![CDATA['.$uinfo->time_limit_until.']]></TimeLimitUntil>'.
 			'</User>'.
		'</Users>';

		try {
			//$res = $this->soapsession->addUser($this->soapsid,$uinfo,$this->createuserDefaultRoleID);
			 $res = $this->soapsession->importUsers($this->soapsid,0,$usr_xml,3,0);
		} catch(SoapFault $a) {
			$this->ILIASDEBUG("createuser: SoapFailed {".print_r($a,TRUE)."}",5);
			throw new Exception("createuser: SoapFailure{".print_r($a,TRUE)."}");
		}
		//echo $res; exit(0);
		//$res = intval($res);
		//if ( $res < 1 ) {
		//	throw new Exception("createuser: SilentFailure");
		//}
		return	$res;
	}
	/**************************************************************************
	 * removeuser
	 * lösche einen Benutzer aus ILIAS
	 * Parameter: User-Handle (numerisch)
	 * Rückgabe: TRUE (OK) oder FALSE (Fehler)
	 */
	public function removeuser($userhandle) {
		if ( ( NULL === $userhandle ) || ( ! is_int($userhandle) ) ) {
			return FALSE;
		}
		if ( $userhandle < 1 ) {
			return FALSE;
		}
		try {
			$this->soap_connect();
		} catch ( Exception $e ) {
			return FALSE;
		}
		try {
			$res = $this->soapsession->deleteUser($this->soapsid,$userhandle);
			//Todo: DEPRECATED: Use importUsers() for deletion of user data in ILIAS 8 and up.
		} catch ( SoapFault $a) {
			$this->ILIASDEBUG("ERR: soapdeleteuser($userhandle): SoapFault{".
				print_r($a,TRUE)."}");
			return	FALSE;
		}
		return ( $res < 1 ? FALSE : TRUE );
	}
	/**************************************************************************
	 * removeuser2
	 * lösche einen Benutzer aus ILIAS
	 * Parameter: User-Login (string)
	 * Rückgabe: TRUE (OK) oder FALSE (Fehler)
	 */
	public function removeuser2($uLogin) {
//		if ( ( NULL === $userhandle ) || ( ! is_int($userhandle) ) ) {
//			return FALSE;
//		}
//		if ( $userhandle < 1 ) {
//			return FALSE;
//		}
		try {
			$this->soap_connect();
		} catch ( Exception $e ) {
			return FALSE;
		}
		try {
			//$res = $this->soapsession->deleteUser($this->soapsid,$userhandle);
			$usr_xml = '<?xml version="1.0" encoding="UTF-8"?>'.
			'<Users>'.
				'<User Id="'.$uLogin.'" Action="Delete">'.
					'<Login><![CDATA['.$uLogin.']]></Login>'.
				'</User>'.
			'</Users>';
			$res = $this->soapsession->importUsers($this->soapsid,0,$usr_xml,3,0);

		} catch ( SoapFault $a) {
			$this->ILIASDEBUG("ERR: removeuser2(uLogin): SoapFault{".
				print_r($a,TRUE)."}",5);
			return	FALSE;
		}
		//return ( $res < 1 ? FALSE : TRUE );
		return TRUE;
	}

	/**************************************************************************
	 * joincourse
	 * Benutzer zu einem Kurs hinzufügen (userhandle(num), kurshandle(num), "Member"/"Admin"/"Tutor"
	 * Return TRUE oder Exception
	 */
	public function joincourse ( $userhandle, $coursehandle, $booktype = "Member" ) {
		$this->soap_connect();
		if ( (!is_int($userhandle)) || ( $userhandle < 1 ) ) {
			throw new Exception("joincourse: no userhandle");
		}
		if ( (!is_int($coursehandle)) || ( $coursehandle < 1 ) ) {
			if ( is_string($coursehandle) && (substr($coursehandle,0,1)=="R") ) {
				try {
					$refids = $this->soapsession->getObjIdsByRefIds(
						$this->soapsid, array(@intval(substr($coursehandle,1))));
				} catch ( SoapFault $a ) {
					throw new Exception("joincourse: R-value invalid: ".
					$coursehandle. " (ILIAS err: ".$a->getMessage().")");
				}
				if ( (!is_array($refids)) || (!isset($refids[0])) || (!is_int($refids[0]))) {
					throw new Exception("joincourse: R-value not resolvable: ".$coursehandle);
				}
				$coursehandle = $refids[0];
			} else {
				throw new Exception("joincourse: no coursehandle");
			}
		}
		if ( $this->soapsession->isAssignedToCourse($this->soapsid,$coursehandle,$userhandle) > 0) {
			throw new Exception("joincourse: already a course member");
		}
		try {
			$res = $this->soapsession->assignCourseMember($this->soapsid,
				$coursehandle,$userhandle,$booktype);
		} catch(SoapFault $a) {
			$this->ILIASDEBUG("joincourse(): Exception SoapFault{".print_r($a,TRUE)."}");
			throw($a);
		}
		if ( $res != TRUE ) {
			throw new Exception ("joincourse: Silent Fault");
		}
		return TRUE;
	}
	/**************************************************************************
	 * leavecourse
	 * Benutzer aus Kurs ausbuchen (userhandle, kurshandle)
	 * Return TRUE oder Exception
	 */
	public function leavecourse($userhandle, $coursehandle) {
		$this->soap_connect();
		if ( (!is_int($userhandle)) || ( $userhandle < 1 ) ) {
			throw new Exception("leavecourse: no userhandle");
		}
		if ( (!is_int($coursehandle)) || ( $coursehandle < 1 ) ) {
			throw new Exception("leavecourse: no coursehandle");
		}
		try {
			$res = $this->soapsession->excludeCourseMember($this->soapsid,
				$coursehandle,$userhandle);
		} catch(SoapFault $a) {
			$this->ILIASDEBUG("leavecourse(): Exception SoapFault{".print_r($a,TRUE)."}");
			throw($a);
		}
		if ( $res != TRUE ) {
			throw new Exception ("leavecourse: Silent Fault");
		}
		return TRUE;
	}
}

