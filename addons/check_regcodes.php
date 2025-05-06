<?php
	chdir(dirname(__FILE__));
	chdir('..');

	global $ilUser, $ilDB, $rbacreview, $ilObjUser;
	require_once './libs/composer/vendor/autoload.php';
	include_once "./addons/ilfunctions.php";
	
	$cron = new ilCronStartUp("c-il-goto", "soap-admin");	

	##----- !! Anpassungen bei zusätzlichen Rollen notwendig!! ----##
	$UProleIDs = array(
			 "Elektrotechnisch unterwiesene Person (Erstunterweisung) - 2023" => "4600", //il_crs_member_2194
			 "Elektrotechnisch unterwiesene Person (Jahresunterweisung) - 2024" => "5769", //il_crs_member_2871
			 "Electrically instructed person (Initial instruction) - 2024" => "5631", //il_crs_member_2860
			 "Electrically instructed person (Annual instruction) - 2024" => "5775", //il_crs_member_2874
			 "Elektrotechnisch unterwiesene Person (Jahresunterweisung) - BFE - HTML5" => "3031" //il_crs_member_1609
			);


//	$globalUserRoleID = "il_0_287"; //Rolle: pusr_std
//	$adminRoleID = 2;
##---------------------------------------------------##

	# functions ----------------------------------------------------
	function print_r2($val){
		$ts = '<pre>';
		$ts .= print_r($val, true);
		$ts .= '</pre>';
		return $ts;
	}

	function cI($data) {
		$data = trim($data);
		$data = stripslashes($data);
		// $data = htmlspecialchars($data,ENT_QUOTES,"UTF-8");
		return $data;
	}

	function vE($data) {
		if( isset($data) && strlen($data) > 0 )	{
			return TRUE;
		} else {
			return FALSE;
		}
	}
	# end functions ----------------------------------------------------
	
	// echo (print_r2($_SESSION));
	
	# main -------------------------------------------------------------
	date_default_timezone_set('Europe/Berlin');
	$error = 0;
	$snBtnStatus = "enabled";

	# erst alle POST-vars prüfen, cleanen und einlesen
	if (vE($_POST['submit'])) { $smBtn = TRUE; } else { $smBtn = FALSE; }

	$crsitems = array('','');
	if (vE($_POST['rolenames'])) { $crsitems[0] = cI($_POST['rolenames']); }

	# Submit -> leere Inputs
	if ($smBtn == TRUE && !vE($crsitems[0])) {
		$error = "-1";
	}  
	if (vE($_POST['dtp_input1'])) {
		//$beginTS = strtotime(date("d.m.Y",$_POST['dtp_input1']));
		$beginTS = strtotime($_POST['dtp_input1']);
		$begin = date("d.m.Y",$beginTS);
	} else {
		$begin = date("d.m.Y");
	}
	if (vE($_POST['dtp_input2'])) {
		$endTS = strtotime($_POST['dtp_input2']);
		$end = date("d.m.Y",$endTS);
	} else {
		$end = date("d.m.Y");
	}

	// logic
	//$headInfo1 = $ilUser->getLogin();
	$headInfo1 = "soap-admin";

	//if (!in_array(2, $rbacreview->assignedRoles($ilUser->getID()))) { //2-> Rolle Administrator
	if (1 > 1) {
		$headInfo2 = "-> Nicht genügend Rechte!!";
		$snBtnStatus = "disabled";
	} else {
		$headInfo2 = "";
		$snBtnStatus = "enabled";
	}

	# rendern
	$tpl = new ilTemplate("./addons/templates/tpl.admtool-grc.html", true, true);

	$tpl->setCurrentBlock("mk_intro");
	$tpl->setVariable("HEADINFO1",$headInfo1);
	$tpl->setVariable("HEADINFO2",$headInfo2);
	$tpl->parseCurrentBlock();

	$kn = array_keys($UProleIDs);
	$tpl->setCurrentBlock("mk_crsitems");
	for ($i = 1; $i <= count($UProleIDs); $i++) {
		$s1 = "UPROLES" . $i;
		$tpl->setVariable("$s1",$kn[$i-1]);
		$s2 = "SELECT" . $i;
		if ($crsitems[0] == $kn[$i-1]) {
			$tpl->setVariable($s2,"selected");
		} else {
			$tpl->setVariable($s2,"");
		}
	}

	$tpl->setVariable("ROLENAME",$crsitems[0]);
	// $tpl->setVariable("CRSNAME",$crsitems[1]);
	$tpl->setVariable("DTP1",$begin);
	$tpl->setVariable("DTP2",$end);
	$tpl->parseCurrentBlock();

	$tpl->setCurrentBlock("mk_btnitems");
	$tpl->setVariable("STATUSSUBMIT", $snBtnStatus);
	$tpl->parseCurrentBlock();

	$tpl->setCurrentBlock("mk_info");
	$tpl->setVariable("MKWARN","");
	$tpl->setVariable("MKINFO","");
	if ($error < 0) {
		if ($error == -1) {
			$tpl->setVariable("MKWARN","<br/><b>Kein Kurs ausgewählt</b>");
		} elseif ($error == -2) {
			$tpl->setVariable("MKWARN","<br/><b>Kursname fehlt!</b>");
		} elseif ($error == -3) {
			$tpl->setVariable("MKWARN","<br/><b>Kurs nicht vorhanden!</b>");
		}
	} else {
		if ($smBtn == TRUE) { //isset($_POST['submit'])
			# display
			$query = "SELECT reg_registration_codes.code, reg_registration_codes.used FROM reg_registration_codes WHERE reg_registration_codes.used > '" . $beginTS . "' AND reg_registration_codes.used < '" . $endTS . "' AND reg_registration_codes.role_local = '" . $UProleIDs[$crsitems[0]] . "' ORDER BY reg_registration_codes.used";
			$set = $ilDB->query($query);
			// $rec = $ilDB->fetchAssoc($set);
			$cnt = 0;
			$info = "";
			// $info .= print_r2($_POST) ."<br>";
			// $info .= $beginTS ."<br>";
			// $info .= $endTS ."<br>";
			$info .= "<table border=\"1\" style=\"width:20%; padding:10px;\">";
			$info .= "<tr><th>Code</th><th>Registriert am</th></tr>";
			while($rec = $ilDB->fetchAssoc($set))
			{
				$info .= "<tr>";
				$info .= "<td>" . $rec['code'] . "</td><td>" . date("d.m.Y, H:i:s",$rec['used']) . "</td>";
				$info .= "</tr>";
				$cnt += 1;
			}
			$info .= "</table>";
			if ($cnt > 0) {
				$info .= "<br>Anzahl: <b>" . $cnt . "</b><br>";
			} else {
				$info .= "<br><b>Kein Eintrag</b>";
			}

			$tpl->setVariable("MKINFO",$info);
		}
	}
	$tpl->parseCurrentBlock();	
	$tpl->show();
	
	//$cron->logout();
?>