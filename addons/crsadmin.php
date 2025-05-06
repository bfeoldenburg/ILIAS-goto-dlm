<?php

	chdir(dirname(__FILE__));
	chdir('..');

	global $ilUser, $ilDB, $rbacreview;

	##----- !! Hier sind Anpassungen erforderlich !! ----##
	define('ILIAS_HTTP_PATH', 'https://goto.bfe-elearning.de');
	$globalUserRoleID = "il_0_role_1340"; //Rolle: bfeshopUser
	$defaultPWD = "goto1234";
	$instructorGroupRefID = '7790';
	$adminRoleID = 2;
	##---------------------------------------------------##

	require_once './libs/composer/vendor/autoload.php';
	include_once "./addons/ilfunctions.php";

	$cron = new ilCronStartUp("c-il-goto", "soap-admin");
	$cron->authenticate();

	# functions ----------------------------------------------------
	function print_r2($val){
		echo '<pre>';
		print_r($val);
		echo  '</pre>';
	}

	function createLoginName($p_fn, $p_ln, $p_ix = 1) {
		$s_ch = array("ä","Ä","ö","Ö","ü","Ü","ß","à","á","â","ç","ë","é","è","ê","í","ñ","ć","č","ó","ò","ô","ú","ù","û","ý","'"," ");
		$r_ch = array("ae","Ae","oe","Oe","ue","Ue","ss","a","a","a","c","e","e","e","e","i","n","c","c","o","o","o","u","u","u","y","","");

		if ($p_ix == 1) {
			return substr(strtolower(str_replace($s_ch,$r_ch,(explode(' ',trim($p_fn))[0]))),0,1) . "." . strtolower(str_replace($s_ch,$r_ch,$p_ln));
		} elseif ($p_ix == 2) {
			return substr(strtolower(str_replace($s_ch,$r_ch,(explode(' ',trim($p_fn))[0]))),0,1) . "." . strtolower(str_replace($s_ch,$r_ch,$p_ln)) . "1";
		} elseif ($p_ix == 3) {
			return substr(strtolower(str_replace($s_ch,$r_ch,(explode(' ',trim($p_fn))[0]))),0,1) . "." . strtolower(str_replace($s_ch,$r_ch,$p_ln)) . "2";
		} elseif ($p_ix == 4) {
			return substr(strtolower(str_replace($s_ch,$r_ch,(explode(' ',trim($p_fn))[0]))),0,1) . "." . strtolower(str_replace($s_ch,$r_ch,$p_ln)) . "3";
		} elseif ($p_ix == 5) {
			return substr(strtolower(str_replace($s_ch,$r_ch,(explode(' ',trim($p_fn))[0]))),0,1) . "." . strtolower(str_replace($s_ch,$r_ch,$p_ln)) . "4";
		} elseif ($p_ix == 6) {
			return substr(strtolower(str_replace($s_ch,$r_ch,(explode(' ',trim($p_fn))[0]))),0,1) . "." . strtolower(str_replace($s_ch,$r_ch,$p_ln)) . "5";
		} else {
			return substr(strtolower(str_replace($s_ch,$r_ch,(explode(' ',trim($p_fn))[0]))),0,1) . "." . strtolower(str_replace($s_ch,$r_ch,$p_ln));
		}
	}

	function cI($data) {
		$data = trim($data);
		$data = stripslashes($data);
		// $data = htmlspecialchars($data,ENT_QUOTES,"UTF-8");
		return $data;
	}

	function getLoginName($p_fn, $p_ln) {
		$tln = createLoginName($p_fn, $p_ln, 1);
		if (getUserHandle($tln) > 0) {
			$tln = createLoginName($p_fn, $p_ln, 2);
			if (getUserHandle($tln) > 0) {
				$tln = createLoginName($p_fn, $p_ln, 3);
				if (getUserHandle($tln) > 0) {
					$tln = createLoginName($p_fn, $p_ln, 4);
					if (getUserHandle($tln) > 0) {
						$tln = createLoginName($p_fn, $p_ln, 5);
						if (getUserHandle($tln) > 0) {
							$tln = createLoginName($p_fn, $p_ln, 6);
							if (getUserHandle($tln) > 0) {
								$tln = "-4";
							}
						}
					}
				}
			}
		}
		return $tln;
	}

	function vE($data) {
		if( isset($data) && strlen($data) > 0 )	{
			return TRUE;
		} else {
			return FALSE;
		}
	}

	function addIliasUser($p_UsrItemsRow, $p_gUsrRolID, $p_pwd) {
		if ($p_UsrItemsRow[1] == 'checked') {$gender = 'm';} elseif ($p_UsrItemsRow[2] == 'checked') {$gender = 'f';} else {$gender = 'n';}
		$time_limit_unlimited = "0";
		$date = new DateTime();
		$time_limit_from = $date->format('Y-m-d');
		$date->add(new DateInterval("P6M"));
		$time_limit_until = $date->format('Y-m-d');

		$usr_xml = '<?xml version="1.0" encoding="UTF-8"?>'.
		'<Users>'.
			'<User Id="'.$p_UsrItemsRow[0].'" Language="de" Action="Insert">'.
				'<Active><![CDATA[true]]></Active>'.
				'<Role Id="'.$p_gUsrRolID.'" Type="Global" Action="Assign"><![CDATA['.$p_gUsrRolID.']]></Role>'.
				'<Login><![CDATA['.$p_UsrItemsRow[0].']]></Login>'.
				'<Password Type="PLAIN"><![CDATA['.$p_pwd.']]></Password>'.
				'<Gender><![CDATA['.$gender.']]></Gender>'.
				'<Firstname><![CDATA['.$p_UsrItemsRow[4].']]></Firstname>'.
				'<Lastname><![CDATA['.$p_UsrItemsRow[5].']]></Lastname>'.
				'<Email><![CDATA['.$p_UsrItemsRow[6].']]></Email>'.
				'<UserDefinedField Id="_1" Name="blok_compID"><![CDATA['.$p_UsrItemsRow[7].']]></UserDefinedField>'.
				'<TimeLimitUnlimited><![CDATA['.$time_limit_unlimited.']]></TimeLimitUnlimited>'.
				'<TimeLimitFrom><![CDATA['.$time_limit_from.']]></TimeLimitFrom>'.
				'<TimeLimitUntil><![CDATA['.$time_limit_until.']]></TimeLimitUntil>'.
			'</User>'.
		'</Users>';

		// print_r2($p_UsrItemsRow);
		return createIliasUser(0,$usr_xml,3,0);
	}

	function fillPostVarsArray($apv) {
		$apv[0] = array('','','','','','','','','','');
		if (vE($_POST['login_0'])) { $apv[0][0] = cI($_POST['login_0']); }
		if (vE($_POST['gender_0'])) { if ($_POST['gender_0'] == 'male') {$apv[0][1] = 'checked'; } elseif ($_POST['gender_0'] == 'female') { $apv[0][2] = 'checked'; } else { $apv[0][3] = 'checked'; } }
		if (vE($_POST['first_0'])) { $apv[0][4] = cI($_POST['first_0']); }
		if (vE($_POST['last_0'])) { $apv[0][5] = cI($_POST['last_0']); }
		if (vE($_POST['email_0'])) { $apv[0][6] = cI($_POST['email_0']); }
		if (vE($_POST['compID_0'])) { $apv[0][7] = cI($_POST['compID_0']); }
		if (vE($_POST['crsrole_0'])) { if ($_POST['crsrole_0'] == 'tutor') { $apv[0][8] = 'checked'; } else { $apv[0][9] = 'checked'; } }

		$apv[1] = array('','','','','','','','','','');
		if (vE($_POST['login_1'])) { $apv[1][0] = cI($_POST['login_1']); }
		if (vE($_POST['gender_1'])) { if ($_POST['gender_1'] == 'male') {$apv[1][1] = 'checked'; } elseif ($_POST['gender_1'] == 'female') { $apv[1][2] = 'checked'; } else { $apv[1][3] = 'checked'; } }
		if (vE($_POST['first_1'])) { $apv[1][4] = cI($_POST['first_1']); }
		if (vE($_POST['last_1'])) { $apv[1][5] = cI($_POST['last_1']); }
		if (vE($_POST['email_1'])) { $apv[1][6] = cI($_POST['email_1']); }
		if (vE($_POST['compID_1'])) { $apv[1][7] = cI($_POST['compID_1']); }
		if (vE($_POST['crsrole_1'])) { if ($_POST['crsrole_1'] == 'tutor') { $apv[1][8] = 'checked'; } else { $apv[1][9] = 'checked'; } }

		$apv[2] = array('','','','','','','','','','');
		if (vE($_POST['login_2'])) { $apv[2][0] = cI($_POST['login_2']); }
		if (vE($_POST['gender_2'])) { if ($_POST['gender_2'] == 'male') {$apv[2][1] = 'checked'; } elseif ($_POST['gender_2'] == 'female') { $apv[2][2] = 'checked'; } else { $apv[2][3] = 'checked'; } }
		if (vE($_POST['first_2'])) { $apv[2][4] = cI($_POST['first_2']); }
		if (vE($_POST['last_2'])) { $apv[2][5] = cI($_POST['last_2']); }
		if (vE($_POST['email_2'])) { $apv[2][6] = cI($_POST['email_2']); }
		if (vE($_POST['compID_2'])) { $apv[2][7] = cI($_POST['compID_2']); }
		if (vE($_POST['crsrole_2'])) { if ($_POST['crsrole_2'] == 'tutor') { $apv[2][8] = 'checked'; } else { $apv[2][9] = 'checked'; } }

		$apv[3] = array('','','','','','','','','','');
		if (vE($_POST['login_3'])) { $apv[3][0] = cI($_POST['login_3']); }
		if (vE($_POST['gender_3'])) { if ($_POST['gender_3'] == 'male') {$apv[3][1] = 'checked'; } elseif ($_POST['gender_3'] == 'female') { $apv[3][2] = 'checked'; } else { $apv[3][3] = 'checked'; } }
		if (vE($_POST['first_3'])) { $apv[3][4] = cI($_POST['first_3']); }
		if (vE($_POST['last_3'])) { $apv[3][5] = cI($_POST['last_3']); }
		if (vE($_POST['email_3'])) { $apv[3][6] = cI($_POST['email_3']); }
		if (vE($_POST['compID_3'])) { $apv[3][7] = cI($_POST['compID_3']); }
		if (vE($_POST['crsrole_3'])) { if ($_POST['crsrole_3'] == 'tutor') { $apv[3][8] = 'checked'; } else { $apv[3][9] = 'checked'; } }

		$apv[4] = array('','','','','','','','','','');
		if (vE($_POST['login_4'])) { $apv[4][0] = cI($_POST['login_4']); }
		if (vE($_POST['gender_4'])) { if ($_POST['gender_4'] == 'male') {$apv[4][1] = 'checked'; } elseif ($_POST['gender_4'] == 'female') { $apv[4][2] = 'checked'; } else { $apv[4][3] = 'checked'; } }
		if (vE($_POST['first_4'])) { $apv[4][4] = cI($_POST['first_4']); }
		if (vE($_POST['last_4'])) { $apv[4][5] = cI($_POST['last_4']); }
		if (vE($_POST['email_4'])) { $apv[4][6] = cI($_POST['email_4']); }
		if (vE($_POST['compID_4'])) { $apv[4][7] = cI($_POST['compID_4']); }
		if (vE($_POST['crsrole_4'])) { if ($_POST['crsrole_4'] == 'tutor') { $apv[4][8] = 'checked'; } else { $apv[4][9] = 'checked'; } }

		$apv[5] = array('','','','','','','','','','');
		if (vE($_POST['login_5'])) { $apv[5][0] = cI($_POST['login_5']); }
		if (vE($_POST['gender_5'])) { if ($_POST['gender_5'] == 'male') {$apv[5][1] = 'checked'; } elseif ($_POST['gender_5'] == 'female') { $apv[5][2] = 'checked'; } else { $apv[5][3] = 'checked'; } }
		if (vE($_POST['first_5'])) { $apv[5][4] = cI($_POST['first_5']); }
		if (vE($_POST['last_5'])) { $apv[5][5] = cI($_POST['last_5']); }
		if (vE($_POST['email_5'])) { $apv[5][6] = cI($_POST['email_5']); }
		if (vE($_POST['compID_5'])) { $apv[5][7] = cI($_POST['compID_5']); }
		if (vE($_POST['crsrole_5'])) { if ($_POST['crsrole_5'] == 'tutor') { $apv[5][8] = 'checked'; } else { $apv[5][9] = 'checked'; } }

		$apv[6] = array('','','','','','','','','','');
		if (vE($_POST['login_6'])) { $apv[6][0] = cI($_POST['login_6']); }
		if (vE($_POST['gender_6'])) { if ($_POST['gender_6'] == 'male') {$apv[6][1] = 'checked'; } elseif ($_POST['gender_6'] == 'female') { $apv[6][2] = 'checked'; } else { $apv[6][3] = 'checked'; } }
		if (vE($_POST['first_6'])) { $apv[6][4] = cI($_POST['first_6']); }
		if (vE($_POST['last_6'])) { $apv[6][5] = cI($_POST['last_6']); }
		if (vE($_POST['email_6'])) { $apv[6][6] = cI($_POST['email_6']); }
		if (vE($_POST['compID_6'])) { $apv[6][7] = cI($_POST['compID_6']); }
		if (vE($_POST['crsrole_6'])) { if ($_POST['crsrole_6'] == 'tutor') { $apv[6][8] = 'checked'; } else { $apv[6][9] = 'checked'; } }

		$apv[7] = array('','','','','','','','','','');
		if (vE($_POST['login_7'])) { $apv[7][0] = cI($_POST['login_7']); }
		if (vE($_POST['gender_7'])) { if ($_POST['gender_7'] == 'male') {$apv[7][1] = 'checked'; } elseif ($_POST['gender_7'] == 'female') { $apv[7][2] = 'checked'; } else { $apv[7][3] = 'checked'; } }
		if (vE($_POST['first_7'])) { $apv[7][4] = cI($_POST['first_7']); }
		if (vE($_POST['last_7'])) { $apv[7][5] = cI($_POST['last_7']); }
		if (vE($_POST['email_7'])) { $apv[7][6] = cI($_POST['email_7']); }
		if (vE($_POST['compID_7'])) { $apv[7][7] = cI($_POST['compID_7']); }
		if (vE($_POST['crsrole_7'])) { if ($_POST['crsrole_7'] == 'tutor') { $apv[7][8] = 'checked'; } else { $apv[7][9] = 'checked'; } }

		return $apv;
	}

	# end functions ----------------------------------------------------
	
	# main -------------------------------------------------------------

	$error = 0;
	$smBtnStatus = "disabled";

	# erst alle POST-vars prüfen, cleanen und einlesen
	if (vE($_POST['submit'])) { $smBtn = TRUE; } else { $smBtn = FALSE; }
	if (vE($_POST['createLoginNames'])) { $clnBtn = TRUE; } else { $clnBtn = FALSE; }

	$crsitems = array('',$defaultPWD);
	if (vE($_POST['course'])) { $crsitems[0] = cI($_POST['course']); }
	if (vE($_POST['pwd'])) { $crsitems[1] = cI($_POST['pwd']); }

	$a_tmp = array();
	$apv = fillPostVarsArray($a_tmp);

	# Submit -> keine Loginnamen
	if ($smBtn == TRUE && !vE($crsitems[0])) {
		$error = "-1";
	} elseif ($smBtn == TRUE && !vE($crsitems[1])) {
		$error = "-2";
	} elseif ( ($smBtn == TRUE) && !vE($apv[0][0]) && !vE($apv[1][0]) && !vE($apv[2][0]) && !vE($apv[3][0]) && !vE($apv[4][0]) && !vE($apv[5][0]) && !vE($apv[6][0]) && !vE($apv[7][0]) ) {
		$error = "-3";
	}  

	# Submit -> Aktion, wenn Loginname gesetzt
	$info = "";
	$usritems = array();
	if ($smBtn == TRUE && vE($crsitems[0])) {
		$crsRefID = getCourseRefID($crsitems[0]);
		if ($crsRefID == '') { $error = "-5"; }
		if ($crsRefID < 0) { $error = $crsRefID; }

		$usritems[0] = $apv[0];
		$usritems[1] = $apv[1];
		$usritems[2] = $apv[2];
		$usritems[3] = $apv[3];
		$usritems[4] = $apv[4];
		$usritems[5] = $apv[5];
		$usritems[6] = $apv[6];
		$usritems[7] = $apv[7];

		if ($error == 0) {
			if ($apv[0][0] != '') { 
				if (getUserHandle($usritems[0][0]) == 0) {
					$error = addIliasUser($usritems[0], $globalUserRoleID, $crsitems[1]);
				}
				$usr_id = getUserHandle($usritems[0][0]);
				if ($usritems[0][8] == 'checked') {$tmpRole = 'Tutor';} else {$tmpRole = 'Member';}
				if (isCourseMember($crsRefID, $usr_id) == "0") {
					$error = addCourseMember($crsRefID, $usr_id,$tmpRole);
					if ($error == TRUE) {
						$info .= $usritems[0][0] . "<br/>";
						$smBtnStatus = "disabled";
					}
				}
				if ($tmpRole == 'Tutor') {
					// $error = addGroupMember($instructorGroupRefID, $usr_id, 'Member');
				}
			}

			if ($apv[1][0] != '') { 
				if (getUserHandle($usritems[1][0]) == 0) {
					$error = addIliasUser($usritems[1], $globalUserRoleID, $crsitems[1]);
				}
				$usr_id = getUserHandle($usritems[1][0]);
				if ($usritems[1][8] == 'checked') {$tmpRole = 'Tutor';} else {$tmpRole = 'Member';}
				if (isCourseMember($crsRefID, $usr_id) == "0") {
					$error = addCourseMember($crsRefID, $usr_id,$tmpRole);
					if ($error == TRUE) {
						$info .= $usritems[1][0] . "<br/>";
						$smBtnStatus = "disabled";
					}
				if ($tmpRole == 'Tutor') {
					// $error = addGroupMember($instructorGroupRefID, $usr_id, 'Member');
				}

				}
			}

			if ($apv[2][0] != '') { 
				if (getUserHandle($usritems[2][0]) == 0) {
					$error = addIliasUser($usritems[2], $globalUserRoleID, $crsitems[1]);
				}
				$usr_id = getUserHandle($usritems[2][0]);
				if ($usritems[2][8] == 'checked') {$tmpRole = 'Tutor';} else {$tmpRole = 'Member';}
				if (isCourseMember($crsRefID, $usr_id) == "0") {
					$error = addCourseMember($crsRefID, $usr_id,$tmpRole);
					if ($error == TRUE) {
						$info .= $usritems[2][0] . "<br/>";
						$smBtnStatus = "disabled";
					}
				}
				if ($tmpRole == 'Tutor') {
					// $error = addGroupMember($instructorGroupRefID, $usr_id, 'Member');
				}
			}

			if ($apv[3][0] != '') { 
				if (getUserHandle($usritems[3][0]) == 0) {
					$error = addIliasUser($usritems[3], $globalUserRoleID, $crsitems[1]);
				}
				$usr_id = getUserHandle($usritems[3][0]);
				if ($usritems[3][8] == 'checked') {$tmpRole = 'Tutor';} else {$tmpRole = 'Member';}
				if (isCourseMember($crsRefID, $usr_id) == "0") {
					$error = addCourseMember($crsRefID, $usr_id,$tmpRole);
					if ($error == TRUE) {
						$info .= $usritems[3][0] . "<br/>";
						$smBtnStatus = "disabled";
					}
				}
				if ($tmpRole == 'Tutor') {
					// $error = addGroupMember($instructorGroupRefID, $usr_id, 'Member');
				}
			}

			if ($apv[4][0] != '') { 
				if (getUserHandle($usritems[4][0]) == 0) {
					$error = addIliasUser($usritems[4], $globalUserRoleID, $crsitems[1]);
				}
				$usr_id = getUserHandle($usritems[4][0]);
				if ($usritems[4][8] == 'checked') {$tmpRole = 'Tutor';} else {$tmpRole = 'Member';}
				if (isCourseMember($crsRefID, $usr_id) == "0") {
					$error = addCourseMember($crsRefID, $usr_id,$tmpRole);
					if ($error == TRUE) {
						$info .= $usritems[4][0] . "<br/>";
						$smBtnStatus = "disabled";
					}
				}
				if ($tmpRole == 'Tutor') {
					// $error = addGroupMember($instructorGroupRefID, $usr_id, 'Member');
				}
			}

			if ($apv[5][0] != '') { 
				if (getUserHandle($usritems[5][0]) == 0) {
					$error = addIliasUser($usritems[5], $globalUserRoleID, $crsitems[1]);
				}
				$usr_id = getUserHandle($usritems[5][0]);
				if ($usritems[5][8] == 'checked') {$tmpRole = 'Tutor';} else {$tmpRole = 'Member';}
				if (isCourseMember($crsRefID, $usr_id) == "0") {
					$error = addCourseMember($crsRefID, $usr_id,$tmpRole);
					if ($error == TRUE) {
						$info .= $usritems[5][0] . "<br/>";
						$smBtnStatus = "disabled";
					}
				}
				if ($tmpRole == 'Tutor') {
					// $error = addGroupMember($instructorGroupRefID, $usr_id, 'Member');
				}
			}

			if ($apv[6][0] != '') { 
				if (getUserHandle($usritems[6][0]) == 0) {
					$error = addIliasUser($usritems[6], $globalUserRoleID, $crsitems[1]);
				}
				$usr_id = getUserHandle($usritems[6][0]);
				if ($usritems[6][8] == 'checked') {$tmpRole = 'Tutor';} else {$tmpRole = 'Member';}
				if (isCourseMember($crsRefID, $usr_id) == "0") {
					$error = addCourseMember($crsRefID, $usr_id,$tmpRole);
					if ($error == TRUE) {
						$info .= $usritems[6][0] . "<br/>";
						$smBtnStatus = "disabled";
					}
				}
				if ($tmpRole == 'Tutor') {
					// $error = addGroupMember($instructorGroupRefID, $usr_id, 'Member');
				}
			}

			if ($apv[7][0] != '') { 
				if (getUserHandle($usritems[7][0]) == 0) {
					$error = addIliasUser($usritems[7], $globalUserRoleID, $crsitems[1]);
				}
				$usr_id = getUserHandle($usritems[7][0]);
				if ($usritems[7][8] == 'checked') {$tmpRole = 'Tutor';} else {$tmpRole = 'Member';}
				if (isCourseMember($crsRefID, $usr_id) == "0") {
					$error = addCourseMember($crsRefID, $usr_id,$tmpRole);
					if ($error == TRUE) {
						$info .= $usritems[7][0] . "<br/>";
						$smBtnStatus = "disabled";
					}
				}
				if ($tmpRole == 'Tutor') {
					// $error = addGroupMember($instructorGroupRefID, $usr_id, 'Member');
				}
			}

		}
	} else {
		if (!vE($crsitems[0]) && $error < 0) {
			$usritems[0] = $apv[0];
			$usritems[1] = $apv[1];
			$usritems[2] = $apv[2];
			$usritems[3] = $apv[3];
			$usritems[4] = $apv[4];
			$usritems[5] = $apv[5];
			$usritems[6] = $apv[6];
			$usritems[7] = $apv[7];

		} else {
			$usritems[0] = array('','checked','','','','','','','','checked');
			$usritems[1] = array('','checked','','','','','','','','checked');
			$usritems[2] = array('','checked','','','','','','','','checked');
			$usritems[3] = array('','checked','','','','','','','','checked');
			$usritems[4] = array('','checked','','','','','','','','checked');
			$usritems[5] = array('','checked','','','','','','','','checked');
			$usritems[6] = array('','checked','','','','','','','','checked');
			$usritems[7] = array('','checked','','','','','','','','checked');

		}
	}

	# Benutzernamen erzeugen
	if ($clnBtn == TRUE) {
		if ( vE($apv[0][4]) && vE($apv[0][5]) ) {
			$usritems[0] = $apv[0];
			$usritems[0][0] = getLoginName($apv[0][4], $apv[0][5]);
		}
		if ( vE($apv[1][4]) && vE($apv[1][5]) ) {
			$usritems[1] = $apv[1];
			$usritems[1][0] = getLoginName($apv[1][4], $apv[1][5]);
		}
		if ( vE($apv[2][4]) && vE($apv[2][5]) ) {
			$usritems[2] = $apv[2];
			$usritems[2][0] = getLoginName($apv[2][4], $apv[2][5]);
		}
		if ( vE($apv[3][4]) && vE($apv[3][5]) ) {
			$usritems[3] = $apv[3];
			$usritems[3][0] = getLoginName($apv[3][4], $apv[3][5]);
		}
		if ( vE($apv[4][4]) && vE($apv[4][5]) ) {
			$usritems[4] = $apv[4];
			$usritems[4][0] = getLoginName($apv[4][4], $apv[4][5]);
		}
		if ( vE($apv[5][4]) && vE($apv[5][5]) ) {
			$usritems[5] = $apv[5];
			$usritems[5][0] = getLoginName($apv[5][4], $apv[5][5]);
		}
		if ( vE($apv[6][4]) && vE($apv[6][5]) ) {
			$usritems[6] = $apv[6];
			$usritems[6][0] = getLoginName($apv[6][4], $apv[6][5]);
		}

		if ( vE($apv[7][4]) && vE($apv[7][5]) ) {
			$usritems[7] = $apv[7];
			$usritems[7][0] = getLoginName($apv[7][4], $apv[7][5]);
		}

		if ( (empty($usritems[0][4]) && empty($usritems[0][5]) && empty($usritems[0][6])) &&
		   (empty($usritems[1][4]) && empty($usritems[1][5]) && empty($usritems[1][6])) &&
		   (empty($usritems[2][4]) && empty($usritems[2][5]) && empty($usritems[2][6])) &&
		   (empty($usritems[3][4]) && empty($usritems[3][5]) && empty($usritems[3][6])) &&
		   (empty($usritems[4][4]) && empty($usritems[4][5]) && empty($usritems[4][6])) &&
		   (empty($usritems[5][4]) && empty($usritems[5][5]) && empty($usritems[5][6])) &&
		   (empty($usritems[6][4]) && empty($usritems[6][5]) && empty($usritems[6][6])) &&
		   (empty($usritems[7][4]) && empty($usritems[7][5]) && empty($usritems[7][6])) )
		{
			$error = "-6";
		}
		//print_r2($usritems);
		if ($error == 0) { $smBtnStatus = "enabled"; }
	}

	# Ausgabe
	$headInfo1 = $ilUser->getLogin();
//	if (!in_array(2, $rbacreview->assignedRoles($_SESSION["_authsession_user_id"]))) { //2-> Rolle Administrator
	if (!in_array(2, $rbacreview->assignedRoles($ilUser->getID()))) { //2-> Rolle Administrator
		$headInfo2 = "-> Nicht genügend Rechte!!";
		$smBtnStatus = "disabled";
	} else {
		$headInfo2 = "";
		//$smBtnStatus = "enabled";
	}

	$tpl = new ilTemplate("./addons/templates/tpl.crsadmin.html", true, true);

	$tpl->setCurrentBlock("mk_header");
	$tpl->setVariable("HEADINFO1",$headInfo1);
	$tpl->setVariable("HEADINFO2",$headInfo2);
	$tpl->parseCurrentBlock();

	$tpl->setCurrentBlock("mk_crsitems");
	$tpl->setVariable("CRSNAME",$crsitems[0]);
	$tpl->setVariable("PWD",$crsitems[1]);
	//$tpl->setVariable("WITHB",$crsitems[1]);
	//$tpl->setVariable("WITHOUTB",$crsitems[2]);
	$tpl->parseCurrentBlock();

	$tpl->setCurrentBlock("mk_usritems");

	$tpl->setVariable("LOGIN_0",$usritems[0][0]);
	$tpl->setVariable("GENDER_01",$usritems[0][1]);
	$tpl->setVariable("GENDER_02",$usritems[0][2]);
	$tpl->setVariable("GENDER_03",$usritems[0][3]);
	$tpl->setVariable("FIRST_0",$usritems[0][4]);
	$tpl->setVariable("LAST_0",$usritems[0][5]);
	$tpl->setVariable("EMAIL_0",$usritems[0][6]);
	$tpl->setVariable("COMPID_0",$usritems[0][7]);
	$tpl->setVariable("CRSROLE_01",$usritems[0][8]);
	$tpl->setVariable("CRSROLE_02",$usritems[0][9]);

	$tpl->setVariable("LOGIN_1",$usritems[1][0]);
	$tpl->setVariable("GENDER_11",$usritems[1][1]);
	$tpl->setVariable("GENDER_12",$usritems[1][2]);
	$tpl->setVariable("GENDER_13",$usritems[1][3]);
	$tpl->setVariable("FIRST_1",$usritems[1][4]);
	$tpl->setVariable("LAST_1",$usritems[1][5]);
	$tpl->setVariable("EMAIL_1",$usritems[1][6]);
	$tpl->setVariable("COMPID_1",$usritems[1][7]);
	$tpl->setVariable("CRSROLE_11",$usritems[1][8]);
	$tpl->setVariable("CRSROLE_12",$usritems[1][9]);

	$tpl->setVariable("LOGIN_2",$usritems[2][0]);
	$tpl->setVariable("GENDER_21",$usritems[2][1]);
	$tpl->setVariable("GENDER_22",$usritems[2][2]);
	$tpl->setVariable("GENDER_23",$usritems[2][3]);
	$tpl->setVariable("FIRST_2",$usritems[2][4]);
	$tpl->setVariable("LAST_2",$usritems[2][5]);
	$tpl->setVariable("EMAIL_2",$usritems[2][6]);
	$tpl->setVariable("COMPID_2",$usritems[2][7]);
	$tpl->setVariable("CRSROLE_21",$usritems[2][8]);
	$tpl->setVariable("CRSROLE_22",$usritems[2][9]);

	$tpl->setVariable("LOGIN_3",$usritems[3][0]);
	$tpl->setVariable("GENDER_31",$usritems[3][1]);
	$tpl->setVariable("GENDER_32",$usritems[3][2]);
	$tpl->setVariable("GENDER_33",$usritems[3][3]);
	$tpl->setVariable("FIRST_3",$usritems[3][4]);
	$tpl->setVariable("LAST_3",$usritems[3][5]);
	$tpl->setVariable("EMAIL_3",$usritems[3][6]);
	$tpl->setVariable("COMPID_3",$usritems[3][7]);
	$tpl->setVariable("CRSROLE_31",$usritems[3][8]);
	$tpl->setVariable("CRSROLE_32",$usritems[3][9]);

	$tpl->setVariable("LOGIN_4",$usritems[4][0]);
	$tpl->setVariable("GENDER_41",$usritems[4][1]);
	$tpl->setVariable("GENDER_42",$usritems[4][2]);
	$tpl->setVariable("GENDER_43",$usritems[4][3]);
	$tpl->setVariable("FIRST_4",$usritems[4][4]);
	$tpl->setVariable("LAST_4",$usritems[4][5]);
	$tpl->setVariable("EMAIL_4",$usritems[4][6]);
	$tpl->setVariable("COMPID_4",$usritems[4][7]);
	$tpl->setVariable("CRSROLE_41",$usritems[4][8]);
	$tpl->setVariable("CRSROLE_42",$usritems[4][9]);

	$tpl->setVariable("LOGIN_5",$usritems[5][0]);
	$tpl->setVariable("GENDER_51",$usritems[5][1]);
	$tpl->setVariable("GENDER_52",$usritems[5][2]);
	$tpl->setVariable("GENDER_53",$usritems[5][3]);
	$tpl->setVariable("FIRST_5",$usritems[5][4]);
	$tpl->setVariable("LAST_5",$usritems[5][5]);
	$tpl->setVariable("EMAIL_5",$usritems[5][6]);
	$tpl->setVariable("COMPID_5",$usritems[5][7]);
	$tpl->setVariable("CRSROLE_51",$usritems[5][8]);
	$tpl->setVariable("CRSROLE_52",$usritems[5][9]);

	$tpl->setVariable("LOGIN_6",$usritems[6][0]);
	$tpl->setVariable("GENDER_61",$usritems[6][1]);
	$tpl->setVariable("GENDER_62",$usritems[6][2]);
	$tpl->setVariable("GENDER_63",$usritems[6][3]);
	$tpl->setVariable("FIRST_6",$usritems[6][4]);
	$tpl->setVariable("LAST_6",$usritems[6][5]);
	$tpl->setVariable("EMAIL_6",$usritems[6][6]);
	$tpl->setVariable("COMPID_6",$usritems[6][7]);
	$tpl->setVariable("CRSROLE_61",$usritems[6][8]);
	$tpl->setVariable("CRSROLE_62",$usritems[6][9]);

	$tpl->setVariable("LOGIN_7",$usritems[7][0]);
	$tpl->setVariable("GENDER_71",$usritems[7][1]);
	$tpl->setVariable("GENDER_72",$usritems[7][2]);
	$tpl->setVariable("GENDER_73",$usritems[7][3]);
	$tpl->setVariable("FIRST_7",$usritems[7][4]);
	$tpl->setVariable("LAST_7",$usritems[7][5]);
	$tpl->setVariable("EMAIL_7",$usritems[7][6]);
	$tpl->setVariable("COMPID_7",$usritems[7][7]);
	$tpl->setVariable("CRSROLE_71",$usritems[7][8]);
	$tpl->setVariable("CRSROLE_72",$usritems[7][9]);

	$tpl->parseCurrentBlock();

	$tpl->setCurrentBlock("mk_btnitems");
	$tpl->setVariable("STATUSSUBMIT", $smBtnStatus);
	$tpl->parseCurrentBlock();

	$tpl->setCurrentBlock("mk_info");
	$tpl->setVariable("MKWARN","");
	$tpl->setVariable("MKINFO","");
	if ($error < 0) {
		if ($error == -1) {
			$tpl->setVariable("MKWARN","<br/><b>Kursname fehlt!</b>");
		} elseif ($error == -2) {
			$tpl->setVariable("MKWARN","<br/><b>Passwort fehlt!</b>");
		} elseif ($error == -3) {
			$tpl->setVariable("MKWARN","<br/><b>Loginnamen fehlen!</b>");
		} elseif ($error == -4) {
			$tpl->setVariable("MKWARN","<br/><b>$error: Fehler in getLoginName!</b>");
		} elseif ($error == -5) {
			$tpl->setVariable("MKWARN","<br/><b>Kurs nicht vorhanden!</b>");
		} elseif ($error == -6) {
			$tpl->setVariable("MKWARN","<br/><b>Benutzerdaten (Vorname, Nachname, Email) fehlen!</b>");
		} elseif ($error == -10) {
			$tpl->setVariable("MKWARN","<br/><b>$error: Fehler in getCourseRefID!</b>");
		} elseif ($error == -11) {
			$tpl->setVariable("MKWARN","<br/><b>$error: Fehler in getCourseRefID!</b>");
		} elseif ($error < -11)  {
			$tpl->setVariable("MKWARN","<br/><b>$error: Fehler in ilFunctions</b>");
		}
	} else {
		if (isset($_POST['submit'])) {
			$info = "Folgende Benutzer wurden angelegt und dem Kurs zugewiesen:<br/><br/>";
			$tpl->setVariable("MKINFO",$info);

			$tpl->setVariable("TH_LOGIN","Login");
			$tpl->setVariable("TH_PWD","Passwort");
			$tpl->setVariable("TH_FIRST","Vorname");
			$tpl->setVariable("TH_LAST","Nachname");
			$tpl->setVariable("TH_ROLE","Rolle");

			if (vE($usritems[0][0])) {
				$tpl->setVariable("TLOGIN_0",$usritems[0][0]);
				$tpl->setVariable("TPWD_0",$crsitems[1]);
				$tpl->setVariable("TFIRST_0",$usritems[0][4]);
				$tpl->setVariable("TLAST_0",$usritems[0][5]);
				if ($usritems[0][8] == 'checked') {$tmpRole = 'Ausbilder';} else {$tmpRole = 'Auszubildender';}
				$tpl->setVariable("TROLE_0",$tmpRole);
			}

			if (vE($usritems[1][0])) {
				$tpl->setVariable("TLOGIN_1",$usritems[1][0]);
				$tpl->setVariable("TPWD_1",$crsitems[1]);
				$tpl->setVariable("TFIRST_1",$usritems[1][4]);
				$tpl->setVariable("TLAST_1",$usritems[1][5]);
				if ($usritems[1][8] == 'checked') {$tmpRole = 'Ausbilder';} else {$tmpRole = 'Auszubildender';}
				$tpl->setVariable("TROLE_1",$tmpRole);
			}

			if (vE($usritems[2][0])) {
				$tpl->setVariable("TLOGIN_2",$usritems[2][0]);
				$tpl->setVariable("TPWD_2",$crsitems[1]);
				$tpl->setVariable("TFIRST_2",$usritems[2][4]);
				$tpl->setVariable("TLAST_2",$usritems[2][5]);
				if ($usritems[2][8] == 'checked') {$tmpRole = 'Ausbilder';} else {$tmpRole = 'Auszubildender';}
				$tpl->setVariable("TROLE_2",$tmpRole);
			}

			if (vE($usritems[3][0])) {
				$tpl->setVariable("TLOGIN_3",$usritems[3][0]);
				$tpl->setVariable("TPWD_3",$crsitems[1]);
				$tpl->setVariable("TFIRST_3",$usritems[3][4]);
				$tpl->setVariable("TLAST_3",$usritems[3][5]);
				if ($usritems[3][8] == 'checked') {$tmpRole = 'Ausbilder';} else {$tmpRole = 'Auszubildender';}
				$tpl->setVariable("TROLE_3",$tmpRole);
			}

			if (vE($usritems[4][0])) {
				$tpl->setVariable("TLOGIN_4",$usritems[4][0]);
				$tpl->setVariable("TPWD_4",$crsitems[1]);
				$tpl->setVariable("TFIRST_4",$usritems[4][4]);
				$tpl->setVariable("TLAST_4",$usritems[4][5]);
				if ($usritems[4][8] == 'checked') {$tmpRole = 'Ausbilder';} else {$tmpRole = 'Auszubildender';}
				$tpl->setVariable("TROLE_4",$tmpRole);
			}

			if (vE($usritems[5][0])) {
				$tpl->setVariable("TLOGIN_5",$usritems[5][0]);
				$tpl->setVariable("TPWD_5",$crsitems[1]);
				$tpl->setVariable("TFIRST_5",$usritems[5][4]);
				$tpl->setVariable("TLAST_5",$usritems[5][5]);
				if ($usritems[5][8] == 'checked') {$tmpRole = 'Ausbilder';} else {$tmpRole = 'Auszubildender';}
				$tpl->setVariable("TROLE_5",$tmpRole);
			}

			if (vE($usritems[6][0])) {
				$tpl->setVariable("TLOGIN_6",$usritems[6][0]);
				$tpl->setVariable("TPWD_6",$crsitems[1]);
				$tpl->setVariable("TFIRST_6",$usritems[6][4]);
				$tpl->setVariable("TLAST_6",$usritems[6][5]);
				if ($usritems[6][8] == 'checked') {$tmpRole = 'Ausbilder';} else {$tmpRole = 'Auszubildender';}
				$tpl->setVariable("TROLE_6",$tmpRole);
			}

			if (vE($usritems[7][0])) {
				$tpl->setVariable("TLOGIN_7",$usritems[7][0]);
				$tpl->setVariable("TPWD_7",$crsitems[1]);
				$tpl->setVariable("TFIRST_7",$usritems[7][4]);
				$tpl->setVariable("TLAST_7",$usritems[7][5]);
				if ($usritems[7][8] == 'checked') {$tmpRole = 'Ausbilder';} else {$tmpRole = 'Auszubildender';}
				$tpl->setVariable("TROLE_7",$tmpRole);
			}
		}
	}
	$tpl->parseCurrentBlock();	
	$tpl->show();
	//$cron->logout();

	# Testausgaben
//	echo getUserHandle('h.hansen2') . "<br/>";
//	echo getCourseRefID('BFE Software - Test extern') . "<br/>";
//	echo getCourseRefID2('BFE Software - Test extern') . "<br/>";
//	echo isCourseMember(getCourseRefID2('BFE Software - Test extern'), getUserHandle('h.hansen2')) . "<br/>";
//	echo deleteIliasUser(6585);

//	echo addCourseMember(getCourseRefID('Testkurs 43'),getUserHandle('mktest'),'Member') . "<br/>";
//	print_r2($usritems[0]);
//	echo $error . "<br/>";

	// Todo:
	// neues Template, Rollenauswahl, Zeitdauer
?>
