<?php 
    /*
    ### POST-Data
    $postData = json_encode(array(
        "cudata"  => $cuData,
        "hash" => $hash
    ));

    ### JSON-Input
    $cuData = array(
        "email" => "h.mustermann@muster-gmbh.org",
        "username" => "hmustermann", // keine UMLAUTE
        "gender" => "m", // m, w, d  
        "first_name" => "hans",
        "last_name" => "Mustermann",
        "password", => "PW-Gehasht",  SHA1 aus PASSWORDUTILS.crypt
        "art_name", => "aus Caruso",
        "crs_data" => "RID1609###3",  //Orbis-Formularfeld : artData (Ilias Export) (Veröffentlichungsdaten/Zusatzfelder2)
        "crs_days" => 183,
        "crs_begin" => "01.01.2020",
        "crs_end" => "31.12.2055",
        "test" => 1 // 0 bei prod
    );

    ### Error-Response
    $pData = array(
        "error_number" => 0 bis - ?, //
        "error_message" => "MESSAGE", //
    );

    ### Rückgabewerte
    0: OK
    -1: jsonRequest leer
    -2: ILIAS-SoapConnect-Fehler
    -3: PostData ungueltig (Hash)
    -4: Feld crs_data leer
    -5: ref_id aus crs_data ungültig
    -6: typ aus crs_data ungültig
    -7: crs_begin ungültig
    -8: crs_end ungültig
    -9: crs_days ungültig
    -12: Kurszuweisung des Users fehlgeschlagen
    -14: User schon Kursmitglied
    -15: User bereits in ILIAS vorhanden
    -16: User bereits in ILIAS vorhanden

    -101: Feld email leer
    -102: Feld username leer
    -103: Feld gender leer
    -104: Feld first_name leer
    -105: Feld last_name leer
    -106: Feld password leer
    -107: Feld art_number leer
    -108: Feld art_name leer
    -109: Feld crs_days leer
    -110: Fehler beim Erzeugen des ILIAS-Objekts
    */

    chdir(dirname(__FILE__));
    chdir('..');
    require_once("./orbis2ilias/ilias.class.php");
    $testMode = 0;
    $DEBUG = 1;

    $retObj = (object)[
        'error_number' => 0,
        'error_message' => "",
        'username' => "",
        'password' => ""
    ];

    function isData($data) {
	    $rV = 0;
	    if (is_null($data)) $rV = 0;
		else if ((isset($data)) && (strlen($data) != 0)) $rV = 1;
		return $rV;
	}

    function console_log($data, $s = "") {
        global $testMode;
        if ($DEBUG == 1) { // < 2
            if ($s === "")
                $consoleOutput = '<script>console.log('.json_encode(gettype($data),JSON_HEX_TAG).','.json_encode($data,JSON_HEX_TAG).');</script>';
            else
                $consoleOutput = '<script>console.log('.json_encode($s,JSON_HEX_TAG).','.json_encode(gettype($data),JSON_HEX_TAG).','.json_encode($data,JSON_HEX_TAG).');</script>';
            echo $consoleOutput;
        }
    }

    function rLogCR() {
        $fp = fopen("./orbis2ilias/orbis2ilias.log","a"); 
        fwrite($fp, "\r\n"); 
        fclose($fp);
    }

    function rLog($data, $errNum, $errMsg) {
        $datum = date("d.m.Y");	$uhrzeit = date("H:i");
        $fp = fopen("./orbis2ilias/orbis2ilias.log","a"); 
        fwrite($fp, "errNum: " . $errNum . ", errMsg: " . $errMsg . " --- " . $_SERVER['REMOTE_ADDR']. ", " . $datum . "," . $uhrzeit . ", " . $data . "\r\n"); 
        fclose($fp);
    }

    rLogCR();
    $jsonData = file_get_contents("php://input"); //$jsonRawData
    $retObj->error_message = "jsonData";
    //rLog($jsonData, $retObj->error_number, $retObj->error_message);

    //$jsonData = $jsonRawData;  //urldecode($jsonRawData);
    //$jsonData = mb_convert_encoding($jsonData, 'UTF-8', "ISO-8859-15");
    
    //$retObj->error_message = "jsonData";
    //rLog($jsonData, $retObj->error_number, $retObj->error_message);

    if (!empty($jsonData)) {
        $pdata = json_decode($jsonData,false);
        //$pdata = json_decode(urldecode($jsonData,false));
        //$pdata = utf8_encode(json_decode($jsonData,false));
        $testMode = $pdata->cudata->test;
        //$testMode = 0;
        $secret = "BFEorbis2ilias";
        $hash = sha1(json_encode($pdata->cudata,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).$secret, FALSE);
        //$hash = sha1(json_encode($pdata->cudata), FALSE);
        //echo json_encode($pdata->cudata).$secret; exit;
        
        $retObj->error_message = "  pData";
        rLog(json_encode($pdata,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES), $retObj->error_number, $retObj->error_message);

        if ($hash === $pdata->hash) {
            //Felder prüfen
            try {
                if (!isData($pdata->cudata->email)) {
                    throw new Exception("-101");
                }
                if (!isData($pdata->cudata->username)) {
                    //throw new Exception("-102");
                }
                if (!isData($pdata->cudata->gender)) {
                    throw new Exception("-103");
                }
                if (!isData($pdata->cudata->first_name)) {
                    throw new Exception("-104");
                }
                if (!isData($pdata->cudata->last_name)) {
                    throw new Exception("-105");
                }
                if (!isData($pdata->cudata->password)) {
                    //throw new Exception("-106");
                }
                if (!isData($pdata->cudata->art_number)) {
                    throw new Exception("-107");
                }
                if (!isData($pdata->cudata->art_name)) {
                    throw new Exception("-108");
                }
                if (!isData($pdata->cudata->crs_data)) {
                    throw new Exception("-4");
                }
                if (!isData($pdata->cudata->crs_days)) {
                    throw new Exception("-109");
                }

            } catch ( Exception $e ) {
                if ($e->getMessage() === "-101") {
                    $retObj->error_number = -101;
                    $retObj->error_message = "cudata->email empty";
                }
                if ($e->getMessage() === "-102") {
                    $retObj->error_number = -102;
                    $retObj->error_message = "cudata->username empty";
                }
                if ($e->getMessage() === "-103") {
                    $retObj->error_number = -103;
                    $retObj->error_message = "cudata->gender empty";
                }
                if ($e->getMessage() === "-104") {
                    $retObj->error_number = -104;
                    $retObj->error_message = "cudata->first_name empty";
                }
                if ($e->getMessage() === "-105") {
                    $retObj->error_number = -105;
                    $retObj->error_message = "cudata->last_name empty";
                }
                if ($e->getMessage() === "-106") {
                    $retObj->error_number = -106;
                    $retObj->error_message = "cudata->password empty";
                }
                if ($e->getMessage() === "-107") {
                    $retObj->error_number = -107;
                    $retObj->error_message = "cudata->art_number empty";
                }
                if ($e->getMessage() === "-108") {
                    $retObj->error_number = -108;
                    $retObj->error_message = "cudata->art_name empty";
                }
                if ($e->getMessage() === "-4") {
                    $retObj->error_number = -4;
                    $retObj->error_message = "cudata->crs_data empty";
                }
                if ($e->getMessage() === "-109") {
                    $retObj->error_number = -109;
                    $retObj->error_message = "cudata->crs_days empty";
                }

                rLog(json_encode($pdata->cudata,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES), $retObj->error_number, $retObj->error_message);
                //console_log($retObj, 'PHP(o2i.php) $retObj: ');
                echo json_encode($retObj);
                exit(0);
            }

            if (!($ILIAS = new ILIASconnection())) {
                $retObj->error_number = -110;
                $retObj->error_message = "ILIASconnection fehlgeschlagen";
                rLog(json_encode($pdata->cudata,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES), $retObj->error_number, $retObj->error_message);  //, $retObj->isUser, $retObj->isCrsMember
                //console_log($retObj, 'PHP(o2i.php) $retObj: ');
                echo json_encode($retObj);
                exit(0);
            }
            try {
                $bbs = "";
                $beruf = "";
                //$cid = "R2846"; //Todo: $cid aus crs_data ermitteln
                $cid = 'R'. substr($pdata->cudata->crs_data, 3, strpos($pdata->cudata->crs_data,'#')-3);
                $sid = $ILIAS->soap_connect(); //var_dump($sid);
                $password = "P" . substr(md5(rand(100000,999999).time()),3,8) . "#"; //"bfebfe";
		$password = str_replace("0","g",$password);
		$password = str_replace("1","h",$password);
                $usrPrefix = "BBS-";
                $username = $usrPrefix . substr(md5(rand(100000,999999).time()),3,6);
		$username = str_replace("0","g",$username);
		$username = str_replace("1","h",$username);

                //console_log($pdata, 'PHP(o2i.php) $pdata->cudata: ');
                $uhandle = $ILIAS->finduserhandle($username);
                if ($testMode == 0) {
                    if ( $uhandle < 1 ) {
                        // createUser
                        try {
                            $cuRes = $ILIAS->createuser( array(
                                "vorname" => $pdata->cudata->first_name, "nachname" => $pdata->cudata->last_name, "titel" => "",
                                "passwort" => $password, "email" => $pdata->cudata->email, "geschlecht" => $pdata->cudata->gender,
                                "loginname" => $username, "gueltigtage" => $pdata->cudata->crs_days, "agbok" => TRUE, "bbsname" => $bbs ) );
                            // echo $cuRes;
                            // Falls die AGB separat bestätigt werden sollen, hier auf FALSE ändern.

                            // assignUser
                            $uhandle = $ILIAS->finduserhandle($username);
                            if ($uhandle > 0) {
                                try {
                                    if ( ! $ILIAS->joincourse ( $uhandle, $cid, "Member" ) ) {
                                        //$retObj->error_number = -12;
                                        //$retObj->error_message = "Kurszuweisung fehlgeschlagen 1";
                                        //rLog(json_encode($pdata->cudata,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES), $retObj->error_number, $retObj->error_message);        
                                        //console_log($retObj, 'PHP(o2i.php) $retObj: ');
                                        //echo json_encode($retObj);
                                        //exit(0);
                                        throw new Exception("Kurszuweisung fehlgeschlagen 1");
                                    }
                                } catch ( Exception $e ) {
                                    $retObj->error_number = -12;
                                    $retObj->error_message = $e->getMessage(); //"Kurszuweisung fehlgeschlagen 2";
                                    rLog(json_encode($pdata->cudata,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES), $retObj->error_number, $retObj->error_message);        
                                    console_log($retObj, 'PHP(o2i.php) $retObj: ');
                                    echo json_encode($retObj);
                                    exit(0);
                                }
                            }
                        } catch ( Exception $e ) {
                            $retObj->error_number = -15;
                            $retObj->error_message = "Benutzer existiert bereits";
                            rLog(json_encode($pdata->cudata,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES), $retObj->error_number, $retObj->error_message);
                            console_log($retObj, 'PHP(o2i.php) $retObj: ');
                            echo json_encode($retObj);
                            //echo "ILIAS-account-Aktion fehlgeschlagen - createuser...: ". $e->getMessage();
                            exit(0);
                        }
                    } else {
                        // sollte nie passieren, da vorher durch isIliasUser.php geprüft, falls doch, welche Reaktion?
                        $retObj->error_number = -16;
                        $retObj->error_message = "Benutzer existiert bereits";
                        rLog(json_encode($pdata->cudata,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES), $retObj->error_number, $retObj->error_message);
                        console_log($retObj, 'PHP(o2i.php) $retObj: ');
                        echo json_encode($retObj);
                        exit(0);
                    }
                }//testMode
                
                $retObj->error_number = 0;
                $retObj->error_message = "";
                $retObj->username = $username;
                $retObj->password = $password;

                rLog(json_encode($retObj,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES), $retObj->error_number, " retObj");
                //console_log($retObj, 'PHP(o2i.php) $retObj: ');
                echo json_encode($retObj);
                exit(0);
            } catch ( Exception $e ) {
                $retObj->error_number = -2;
                $retObj->error_message = "ILIAS soap_connect fehlgeschlagen";
                rLog(json_encode($pdata->cudata,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES), $retObj->error_number, $retObj->error_message);
                //console_log($retObj, 'PHP(o2i.php) $retObj: ');
                echo json_encode($retObj);
                exit(0);
            }
	    } else {
            $retObj->error_number = -3;
            $retObj->error_message = "PostData ungueltig";
            rLog(json_encode($pdata->cudata,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES), $retObj->error_number, $retObj->error_message);
            //console_log($retObj, 'PHP(o2i.php) $retObj: ');
            echo json_encode($retObj);
            exit(0);
	    }
    } else {
        $retObj->error_number = -1;
        $retObj->error_message = "jsonRequest leer";
        rLog("", $retObj->error_number, $retObj->error_message);
        //console_log($retObj, 'PHP(o2i.php) $retObj: ');
        echo json_encode($retObj);
        exit(0);
    }

    // ToDo: cron: removeCrsMember
    // Wann werden die Accounts wieder gelöscht?
    // Felder prüfen: crs_begin, crs_end
?>