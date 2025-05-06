<?php 
/* ToDo: Error-handling, Zeitlimit/Datum-Uhrzeit, Artikelnummer gültig? (nur bei ILIAS-Produkten aktiv werden)
- crsEnd 23:55
- test relative

errorCode:
	 0: OK
	-1: jsonRequest leer
	-2: hash leer
	-3: hash stimmt nicht überein
	-4: artdata leer
	-5: ref_id aus artdata ungültig
	-6: typ aus artdata leer
	-7: crsDate ungültig 
	-8: crsend ungültig
	-9: duration ungültig
	-10: typ aus artdata ungültig
*/

	chdir(dirname(__FILE__));
	chdir('..');
	require_once './libs/composer/vendor/autoload.php';

	include_once "./Services/Cron/classes/class.ilCronStartUp.php";
	include_once "./shop2ilias_v2/ilfunctions.php";

	require_once("./shop2ilias_v2/PHPMailer-master/src/PHPMailer.php");
	require_once("./shop2ilias_v2/PHPMailer-master/src/SMTP.php");

	define('ILIAS_HTTP_PATH', 'https://goto.bfe-elearning.de');  				//!!Anpassen!!

	function console_log($key, $data){
		echo '<script>';
		echo 'console.log("'. $key . '"+' . json_encode($data) .')';
		echo '</script>';
	}
	
	function object2array($object) { 
		return @json_decode(@json_encode($object),1);
	}

	function print_r2($val){
        	echo '<pre>';
	        print_r($val);
        	echo  '</pre>';
	}

	function vE($data) {
		if( isset($data) && strlen($data) > 0 )	{
			return TRUE;
		} else {
			return FALSE;
		}
	}

	function generateRandomCode() {
		$map = "23456789abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ";
		
		$code = "";
		$max = strlen($map)-1;
		for($loop = 1; $loop <= 10; $loop++)
		{
		  $code .= $map[mt_rand(0, $max)];
		}
		return $code;
	}

	function createRegCode($p_locRole, $p_limit, $p_date) {
		// fest: $role
		// fest: code_types -> $reg_type = 1 oder "1", $ext_type = 2 oder "2"
		include_once './Services/Registration/classes/class.ilRegistrationCode.php';

		$role = "1340";  //Obj_Id bfeShopUser
		$stamp = time();
		$id = ilRegistrationCode::create(
				$role, 
				$stamp, 
				array($p_locRole), 
				$p_limit, 
				$p_date,
				"1",
				"0"
		);
		return $id;
	}

	function requestLog($p_jsonRequest, $p_errNum) {
		$datum = date("d.m.Y");	$uhrzeit = date("H:i");
		$fp = fopen("./shop2ilias_v2/jsonRequest.log","a"); 
		fwrite($fp, "IP: " . $_SERVER['REMOTE_ADDR']. ",  " . $datum . " - " . $uhrzeit . " - " . $p_jsonRequest . "\r\nRückgabe: " . $p_errNum . "\r\n\r\n"); 
		fclose($fp);
	}

	function returnLog($p_data) {
		$datum = date("d.m.Y");	$uhrzeit = date("H:i");
		$fp2 = fopen("./shop2ilias_v2/returnData.log","a"); 
		fwrite($fp2, "IP: " . $_SERVER['REMOTE_ADDR']. ",  " . $datum . " - " . $uhrzeit . " - " . $p_data . "\r\n\r\n"); 
		fclose($fp2);
	}

	function convertDate($sDate) {
		$a = explode(".", $sDate);
		$sDate = $a[2] . '-' . $a[1] . '-' . $a[0];
		return $sDate;
	}
	
	function getPeriod($sDate, $days) {
		$a = explode(".", $sDate);
		$sDate = $a[2] . '-' . $a[1] . '-' . $a[0];
		$date1 = new DateTime($sDate);
		$date2 = new DateTime($sDate);
		$sDiff = 'P' . $days . 'D';
		$date2->add(new DateInterval($sDiff));
		
		$iv = $date2->diff($date1);
		$ra = array(
			"d" => strval(($iv->d)+3),
			"m" => $iv->m,
			"y" => doubleval($iv->y));
	
		return $ra;
	}
// -------------------- main --------------------
// ToDo: wichtige JSON-Werte prüfen -> error

	try {
		//$cron = new ilCronStartUp("c-il-goto", "soap-admin", "!bfe123!");
		$cron = new ilCronStartUp("c-il-goto", "soap-admin");
		global $ilDB, $rbacreview;

		//$cron->initIlias();
		$cron->authenticate();

		$thesecret = "bfeShop2ilias5";

		if (!vE($_REQUEST['jsonRequest'])) {
			throw new Exception('-1');
		}
		if (!vE($_REQUEST['hash'])) {
			throw new Exception('-2');
		}

		$jsonData = $_REQUEST['jsonRequest'];
		$hash = strtolower(@trim($_REQUEST['hash']));
		$checkhash = sha1($jsonData.$thesecret, FALSE);
		if ( $checkhash != $hash ) {
			throw new Exception('-3');
		}

// ToDo: limit, date, error-handling (auch in ilFunctions)

		$orderObj = json_decode($jsonData, true);

		$tmpArr2 = array();
		$retTemp = array();
		$articles = $orderObj['artikel'];
		foreach($articles as $article) {
			// console_log("article: ", $article['artData']);
			$reg_codes = array();
			if (vE($article['artData'])) {
				$aData = explode("###", $article['artData']);
				if (!vE($aData[0])) {
					throw new Exception('-5');	
				}
				if (!vE($aData[1])) {
					throw new Exception('-6');	
				}
				$aTyp = array(2, 3, 4);
				if (!in_array($aData[1], $aTyp)) {
					throw new Exception('-10');	
				}
				$rol_oid = getRoleObjIDfromRefID(substr($aData[0], 3));
				//$rol_oid = "" -> error
				$tmpArr2[] = $rol_oid;
			} else {
				//error
				throw new Exception('-4');
			}

			$tmpArr = array();
			for($i=1; $i<=$article['numOfCodes']; $i++) {	
				switch($aData[1])
				{
					case "1":
						break;
					case "2":
						// console_log("aData[1]: ", $aData[1]);
						if (!vE($article['crsEnd'])) {
							throw new Exception('-8');
						}
						if ($orderObj['test'] == 0) {
							$cE = convertDate($article['crsEnd']);
							//$abs = date_parse($article['crsEnd']);
							//$access_limit = mktime(23, 59, 59, $abs['month'], $abs['day'], $abs['year']);
							$tmp_ci = createRegCode($rol_oid, "absolute", $cE);
							$query = "SELECT code FROM reg_registration_codes WHERE code_id = " . $tmp_ci;
							$set = $ilDB->query($query);
							$rec = $ilDB->fetchAssoc($set);
							// ToDo:   error-handling
							$code = $rec['code'];
							$reg_codes[''.$i] = $code;
						} else {
							$code = "&".generateRandomCode();
							$reg_codes[''.$i] = $code;
						}		
						break;
					case "3":
						// console_log("aData[1]: ", $aData[1]);
						if (!vE($article['crsDuration'])) {
							throw new Exception('-9');
						}
						if ($orderObj['test'] == 0) {
							$tmpDate = date("d.m.Y",time());
							$aIv = serialize(getPeriod($tmpDate, $article['crsDuration']));
							$tmp_ci = createRegCode($rol_oid, "relative", $aIv);
							$query = "SELECT code FROM reg_registration_codes WHERE code_id = " . $tmp_ci;
							$set = $ilDB->query($query);
							$rec = $ilDB->fetchAssoc($set);
							// ToDo:   error-handling
							$code = $rec['code'];
							$reg_codes[''.$i] = $code;
						} else {
							$code = "!".generateRandomCode();
							$reg_codes[''.$i] = $code;
						}		
						break;
					case "4":
						// console_log("aData[1]: ", $aData[1]);
						if (!vE($article['crsEnd'])) {
							throw new Exception('-8');
						}
						if ($orderObj['test'] == 0) {
							$cE = convertDate($article['crsEnd']);
							$tmp_ci = createRegCode($rol_oid, "absolute", $cE);
							$query = "SELECT code FROM reg_registration_codes WHERE code_id = " . $tmp_ci;
							$set = $ilDB->query($query);
							$rec = $ilDB->fetchAssoc($set);
							// ToDo:   error-handling
							$code = $rec['code'];
							$reg_codes[''.$i] = $code;
						} else {
							$code = "%".generateRandomCode();
							$reg_codes[''.$i] = $code;
						}		
						break;
				}
			}
			$tmpArr['artNumber'] = $article['artNumber'];
			$tmpArr['type'] = "code";
			$tmpArr['items'] = $reg_codes;
			$retTemp[] = $tmpArr;
		}

		//var_dump($tmpArr2);
		$retArr = array();
		$retArr['errorCode'] = "0";
		$retArr['bestellnummer'] = $orderObj['bestellnummer'];
		$retArr['artikel'] = $retTemp;

		returnLog(json_encode($retArr, true));
		requestLog($jsonData, $retArr['errorCode']);
		
		$cron->logout();
		echo json_encode($retArr);
	}
	catch(Exception $e)
	{
		$retArr = array();
		$retArr['errorCode'] = $e->getMessage();
		$retArr['bestellnummer'] = $orderObj['bestellnummer'];

		returnLog(json_encode($retArr, true));
		requestLog($jsonData, $retArr['errorCode']);
		$cron->logout();
		echo json_encode($retArr);
		exit(1);
	}
?>