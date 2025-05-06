<?php
	function getRoleObjIDfromRefID($a_crs_refid) {
		global $ilDB;
		$query = "SELECT obj_id FROM object_reference WHERE ref_id = '" . $a_crs_refid . "'";
		$set = $ilDB->query($query);
		$rec = $ilDB->fetchAssoc($set);
		$objid = $rec['obj_id'];

		$ts = "Member of crs obj_no.".strval($objid);
		$query = "SELECT obj_id FROM object_data WHERE description = '" . $ts . "'";
		$set = $ilDB->query($query);
		$rec = $ilDB->fetchAssoc($set);
		$role_objid = $rec['obj_id'];

		return strval($role_objid);
	}

	function getRoleObjID($a_crs_objid) {
		global $ilDB;

		$ts = "Member of crs obj_no.".strval($a_crs_objid);
		$query = "SELECT obj_id FROM object_data WHERE description = '" . $ts . "'";
		$set = $ilDB->query($query);
		$rec = $ilDB->fetchAssoc($set);
		$role_objid = $rec['obj_id'];

		return strval($role_objid);
	}

	function getCourseObjID($a_title) {
		global $ilDB;
		include_once './Services/Search/classes/class.ilQueryParser.php';

		$query_parser = new ilQueryParser($a_title);
		$query_parser->setMinWordLength(0,true);
		$query_parser->setCombination(QP_COMBINATION_AND);
		$query_parser->parse();
		if(!$query_parser->validate())
		{
			return "-10";
		}

		include_once './Services/Search/classes/class.ilObjectSearchFactory.php';

		include_once 'Services/Search/classes/Like/class.ilLikeObjectSearch.php';
		$object_search = new ilLikeObjectSearch($query_parser);

		#$object_search =& ilObjectSearchFactory::_getObjectSearchInstance($query_parser);
		$object_search->setFields(array('title'));
		$object_search->appendToFilter('role');
		$object_search->appendToFilter('rolt');
		$res =& $object_search->performSearch();
		$res->filter(ROOT_FOLDER_ID,true);
		
		$objs = array();
		foreach($res->getUniqueResults() as $entry)
		{
			if($entry['type'] == 'role' or $entry['type'] == 'rolt')
			{
				if($tmp = ilObjectFactory::getInstanceByObjId($entry['obj_id'],false)) {
					$objs[] = $tmp;
				}
				continue;
			}
			if($tmp = ilObjectFactory::getInstanceByRefId($entry['ref_id'],false))
			{
				if((ilObject::_lookupType($entry['obj_id'])) == 'crs') {
					$objs[] = $tmp;
				}
			}
		}
		if(!count($objs))
		{
			return '';
		}
		if(count($objs) > 1)
		{
			return "-11";
		}

//		define('ILIAS_HTTP_PATH', 'http://ws5.bfe-elearning.de/il5_bfe');
		include_once './webservice/soap/classes/class.ilObjectXMLWriter.php';

		$xml_writer = new ilObjectXMLWriter();
		$xml_writer->enablePermissionCheck(true);
		$xml_writer->setObjects($objs);
		if($xml_writer->start())
		{
			$objIDs = new SimpleXMLElement($xml_writer->getXML());
			$a_objIDs = json_decode(json_encode($objIDs), true);
			//return $a_objIDs['Object']['References']['@attributes']['ref_id'];
			$crs_refid =  $a_objIDs['Object']['References']['@attributes']['ref_id'];
			//return $a_objIDs;
		}

		$query = "SELECT obj_id FROM object_reference WHERE ref_id = " . $crs_refid;
		$set = $ilDB->query($query);
		$rec = $ilDB->fetchAssoc($set);
		$crs_objid = $rec['obj_id'];

		$ts = "Member of crs obj_no.".strval($crs_objid);
		$query = "SELECT obj_id FROM object_data WHERE description = '" . $ts . "'";
		$set = $ilDB->query($query);
		$rec = $ilDB->fetchAssoc($set);
		$role_objid = $rec['obj_id'];

		return strval($role_objid);
	}

	function getUserHandle($user_name) {
		global $rbacsystem, $ilUser;
		$user_name = trim($user_name);
		if(strcasecmp($ilUser->getLogin(), $user_name) != 0 && !$rbacsystem->checkAccess('read',USER_FOLDER_ID))
		{
			return "-12";
		}

		$user_id = ilObjUser::getUserIdByLogin($user_name);
		return $user_id ? $user_id : "0";
	}

	/**
	*
	* define ("IL_FAIL_ON_CONFLICT", 1);
	* define ("IL_UPDATE_ON_CONFLICT", 2);
	* define ("IL_IGNORE_ON_CONFLICT", 3);
	* SOAP-Klasse-Aufruf: importUsers($this->soapsid,0,$usr_xml,3,0);
	*/
	function createIliasUser($folder_id, $usr_xml, $conflict_rule, $send_account_mail) {
		include_once './Services/User/classes/class.ilUserImportParser.php';
		include_once './Services/AccessControl/classes/class.ilObjRole.php';
		include_once './Services/Object/classes/class.ilObjectFactory.php';
		global $rbacreview, $rbacsystem, $tree, $lng,$ilUser,$ilLog;

    		// this takes time but is nescessary
   		$error = false;

		// validate to prevent wrong XMLs
		$dom = @domxml_open_mem($usr_xml, DOMXML_LOAD_VALIDATING, $error);
   		if ($error)
   		{
		    $msg = array();
		    if (is_array($error))
		    {
	        	foreach ($error as $err) {
					$msg []= "(".$err["line"].",".$err["col"]."): ".$err["errormessage"];
		    	}
		    }
		    else 
		    {
		   		$msg[] = $error;
		   	}
		   	$msg = join("\n",$msg);
		   	return "-13";  //$this->__raiseError($msg, "Client");
   		}

		switch ($conflict_rule)
		{
			case 2:
				$conflict_rule = IL_UPDATE_ON_CONFLICT;
				break;
			case 3:
				$conflict_rule = IL_IGNORE_ON_CONFLICT;
				break;
			default:
				$conflict_rule = IL_FAIL_ON_CONFLICT;
		}


		// folder id 0, means to check permission on user basis!
		// must have create user right in time_limit_owner property (which is ref_id of container)
		if ($folder_id != 0)
		{
    		// determine where to import
    		if ($folder_id == -1)
    			$folder_id = USER_FOLDER_ID;

    			// get folder
    		$import_folder = ilObjectFactory::getInstanceByRefId($folder_id, false);
    		// id does not exist
    		if (!$import_folder)
    				return "-14";  //$this->__raiseError('Wrong reference id.','Server');

    		// folder is not a folder, can also be a category
    		if ($import_folder->getType() != "usrf" && $import_folder->getType() != "cat")
    		        return "-15";  //$this->__raiseError('Folder must be a usr folder or a category.','Server');

    		// check access to folder
    		if(!$rbacsystem->checkAccess('create_usr',$folder_id))
    		{
    			return "-16";  //$this->__raiseError('Missing permission for creating users within '.$import_folder->getTitle(),'Server');
    		}
		}

		// first verify


		$importParser = new ilUserImportParser("", IL_VERIFY, $conflict_rule);
	        $importParser->setUserMappingMode(IL_USER_MAPPING_ID);
		$importParser->setXMLContent($usr_xml);
		$importParser->startParsing();

		switch ($importParser->getErrorLevel())
		{
			case IL_IMPORT_SUCCESS :
				break;
			case IL_IMPORT_WARNING :
				return "-17";  //$this->__getImportProtocolAsXML ($importParser->getProtocol("User Import Log - Warning"));
				break;
			case IL_IMPORT_FAILURE :
				return "-18";  //$this->__getImportProtocolAsXML ($importParser->getProtocol("User Import Log - Failure"));
		}

		// verify is ok, so get role assignments

		$importParser = new ilUserImportParser("", IL_EXTRACT_ROLES, $conflict_rule);
		$importParser->setXMLContent($usr_xml);
	        $importParser->setUserMappingMode(IL_USER_MAPPING_ID);
		$importParser->startParsing();

		$roles = $importParser->getCollectedRoles();

		//print_r($roles);



		// roles to be assigned, skip if one is not allowed!
		$permitted_roles = array();
		foreach ($roles as $role_id => $role)
		{
			if (!is_numeric ($role_id))
			{
				// check if internal id
				$internalId = ilUtil::__extractId($role_id, IL_INST_ID);
				
				if (is_numeric($internalId))
				{
					$role_id = $internalId;
					$role_name = $role_id;
				}
/*				else // perhaps it is a rolename
				{
					$role  = ilSoapUserAdministration::__getRoleForRolename ($role_id);
					$role_name = $role->title;
					$role_id = $role->role_id;
				}*/
			}
			
			if(isPermittedRole($folder_id,$role_id))
			{
				$permitted_roles[$role_id] = $role_id;
			}
			else
			{
				$role_name = ilObject::_lookupTitle($role_id);
				return "-19";  //$this->__raiseError("Could not find role ".$role_name.". Either you use an invalid/deleted role ". 
						// "or you try to assign a local role into the non-standard user folder and this role is not in its subtree.",'Server');				
			}
		}

		$global_roles = $rbacreview->getGlobalRoles();
		//print_r ($global_roles);

		foreach ($permitted_roles as $role_id => $role_name)
		{
		    if ($role_id != "")
				{
					if (in_array($role_id, $global_roles))
					{
						if ($role_id == SYSTEM_ROLE_ID && ! in_array(SYSTEM_ROLE_ID,$rbacreview->assignedRoles($ilUser->getId()))
						|| ($folder_id != USER_FOLDER_ID && $folder_id != 0 && ! ilObjRole::_getAssignUsersStatus($role_id))
						)
						{
							return "-20";  //$this->__raiseError($lng->txt("usrimport_with_specified_role_not_permitted")." $role_name ($role_id)",'Server');
						}
					}
					else
					{
						$rolf = $rbacreview->getFoldersAssignedToRole($role_id,true);
						if ($rbacreview->isDeleted($rolf[0])
								|| ! $rbacsystem->checkAccess('write',$rolf[0]))
						{

							return "-21";  //$this->__raiseError($lng->txt("usrimport_with_specified_role_not_permitted")." $role_name ($role_id)","Server");
						}
					}
				}
		}

		//print_r ($permitted_roles);

		$importParser = new ilUserImportParser("", IL_USER_IMPORT, $conflict_rule);
		$importParser->setSendMail($send_account_mail);
		$importParser->setUserMappingMode(IL_USER_MAPPING_ID);
		$importParser->setFolderId($folder_id);
		$importParser->setXMLContent($usr_xml);

		$importParser->setRoleAssignment($permitted_roles);

		$importParser->startParsing();

		if ($importParser->getErrorLevel() != IL_IMPORT_FAILURE)
		{
			  return "-22";  //$this->__getUserMappingAsXML ($importParser->getUserMapping());
		}
		return "-23";  //$this->__getImportProtocolAsXML ($importParser->getProtocol());
	}

	function isCourseMember($course_id,$user_id) {
		global $rbacsystem;

		if(($obj_type = ilObject::_lookupType(ilObject::_lookupObjId($course_id))) != 'crs')
		{
			$course_id = end($ref_ids = ilObject::_getAllReferences($course_id));
			if(ilObject::_lookupType(ilObject::_lookupObjId($course_id)) != 'crs')
			{
				return "-24";  //$this->__raiseError('Invalid course id. Object with id "'. $course_id.'" is not of type "course"','Client');
			}
		}

		if(ilObject::_lookupType($user_id) != 'usr')
		{
			return "-25";  //$this->__raiseError('Invalid user id. User with id "'. $user_id.' does not exist','Client');
		}

		if(!$tmp_course = ilObjectFactory::getInstanceByRefId($course_id,false))
		{
			return "-26";  //$this->__raiseError('Cannot create course instance!','Server');
		}

		if(!$rbacsystem->checkAccess('manage_members',$course_id))
		{
			return "-27";  //$this->__raiseError('Check access failed. No permission to write to course','Server');
		}

		include_once './Modules/Course/classes/class.ilCourseParticipants.php';
		$crs_members = ilCourseParticipants::_getInstanceByObjId($tmp_course->getId());
		
		if($crs_members->isAdmin($user_id))
		{
			return IL_CRS_ADMIN;
		}
		if($crs_members->isTutor($user_id))
		{
			return IL_CRS_TUTOR;
		}
		if($crs_members->isMember($user_id))
		{
			return IL_CRS_MEMBER;
		}

		return "0";
		// Returns 0 => not assigned, 1 => course admin, 2 => course member or 3 => course tutor
	}

	function addCourseMember($course_id,$user_id,$type) {  // $type -> "Tutor" or "Member"
		global $rbacsystem;

		if(($obj_type = ilObject::_lookupType(ilObject::_lookupObjId($course_id))) != 'crs')
		{
			$course_id = end($ref_ids = ilObject::_getAllReferences($course_id));
			if(ilObject::_lookupType(ilObject::_lookupObjId($course_id)) != 'crs')
			{
				return "-28";  //$this->__raiseError('Invalid course id. Object with id "'. $course_id.'" is not of type "course"','Client');
			}
		}

		if(!$rbacsystem->checkAccess('manage_members',$course_id))
		{
			return "-29";  //$this->__raiseError('Check access failed. No permission to write to course','Server');
		}

		
		if(ilObject::_lookupType($user_id) != 'usr')
		{
			return "-30";  //$this->__raiseError('Invalid user id. User with id "'. $user_id.' does not exist','Client');
		}
		if($type != 'Admin' and
		   $type != 'Tutor' and
		   $type != 'Member')
		{
			return "-31";  //$this->__raiseError('Invalid type given. Parameter "type" must be "Admin", "Tutor" or "Member"','Client');
		}

		if(!$tmp_course = ilObjectFactory::getInstanceByRefId($course_id,false))
		{
			return "-32";  //$this->__raiseError('Cannot create course instance!','Server');
		}

		if(!$tmp_user = ilObjectFactory::getInstanceByObjId($user_id,false))
		{
			return "-33";  //$this->__raiseError('Cannot create user instance!','Server');
		}

		include_once 'Modules/Course/classes/class.ilCourseParticipants.php';
		
		$course_members = ilCourseParticipants::_getInstanceByObjId($tmp_course->getId());

		switch($type)
		{
			case 'Admin':
				require_once("Services/Administration/classes/class.ilSetting.php");
				$settings = new ilSetting();
				$course_members->add($tmp_user->getId(),IL_CRS_ADMIN);
				$course_members->updateNotification($tmp_user->getId(),$settings->get('mail_crs_admin_notification', true));
				break;

			case 'Tutor':
				$course_members->add($tmp_user->getId(),IL_CRS_TUTOR);
				break;

			case 'Member':
				$course_members->add($tmp_user->getId(),IL_CRS_MEMBER);
				break;
		}

		return true;
	}

	function removeCourseMember($course_id,$user_id) {
		global $rbacsystem;

		if(($obj_type = ilObject::_lookupType(ilObject::_lookupObjId($course_id))) != 'crs')
		{
			$course_id = end($ref_ids = ilObject::_getAllReferences($course_id));
			if(ilObject::_lookupType(ilObject::_lookupObjId($course_id)) != 'crs')
			{
				return "-46";  //return $this->__raiseError('Invalid course id. Object with id "'. $course_id.'" is not of type "course"','Client');
			}
		}

		if(ilObject::_lookupType($user_id) != 'usr')
		{
			return "-47";  //return $this->__raiseError('Invalid user id. User with id "'. $user_id.' does not exist','Client');
		}

		if(!$tmp_course = ilObjectFactory::getInstanceByRefId($course_id,false))
		{
			return "-48";  //return $this->__raiseError('Cannot create course instance!','Server');
		}

		if(!$rbacsystem->checkAccess('manage_members',$course_id))
		{
			return "-49";  //return $this->__raiseError('Check access failed. No permission to write to course','Server');
		}

		include_once 'Modules/Course/classes/class.ilCourseParticipants.php';
		
		$course_members = ilCourseParticipants::_getInstanceByObjId($tmp_course->getId());
		if(!$course_members->checkLastAdmin(array($user_id)))
		{
			return "-50";  //return $this->__raiseError('Cannot deassign last administrator from course','Server');
		}

		$course_members->delete($user_id);

		return true;
	}

	function isPermittedRole($a_folder,$a_role)
	{
		static $checked_roles = array();
		static $global_roles = null;
		
		
		if(isset($checked_roles[$a_role]))
		{
			return $checked_roles[$a_role];
		}
		
		global $rbacsystem,$rbacreview,$ilUser,$tree,$ilLog;
		
		$locations = $rbacreview->getFoldersAssignedToRole($a_role,true);
		$location = $locations[0];
		
		// global role
		if($location == ROLE_FOLDER_ID)
		{
			$ilLog->write(__METHOD__.': Check global role');
			// check assignment permission if called from local admin
			
			
			if($a_folder != USER_FOLDER_ID and $a_folder != 0)
			{
				$ilLog->write(__METHOD__.': '.$a_folder);
				include_once './Services/AccessControl/classes/class.ilObjRole.php';
				if(!ilObjRole::_getAssignUsersStatus($a_role))
				{
  				    $ilLog->write(__METHOD__.': No assignment allowed');
				    $checked_roles[$a_role] = false;
				    return false;
				}
			}
			// exclude anonymous role from list
			if ($a_role == ANONYMOUS_ROLE_ID)
			{
				$ilLog->write(__METHOD__.': Anonymous role chosen.');
			    $checked_roles[$a_role] = false;
				return false;
			}
			// do not allow to assign users to administrator role if current user does not has SYSTEM_ROLE_ID
			if($a_role == SYSTEM_ROLE_ID and !in_array(SYSTEM_ROLE_ID,$rbacreview->assignedRoles($ilUser->getId())))
			{
				$ilLog->write(__METHOD__.': System role assignment forbidden.');
			    $checked_roles[$a_role] = false;
				return false;
			}
			
			// Global role assignment ok
			$ilLog->write(__METHOD__.': Assignment allowed.');
		    $checked_roles[$a_role] = true;
			return true;
		}
		elseif($location)
		{
			$ilLog->write(__METHOD__.': Check local role.');

			// It's a local role
			$rolfs = $rbacreview->getFoldersAssignedToRole($a_role,true);
			$rolf = $rolfs[0];


			// only process role folders that are not set to status "deleted"
			// and for which the user has write permissions.
			// We also don't show the roles which are in the ROLE_FOLDER_ID folder.
			// (The ROLE_FOLDER_ID folder contains the global roles).
			if($rbacreview->isDeleted($rolf)
				|| !$rbacsystem->checkAccess('edit_permission',$rolf))
			{
				$ilLog->write(__METHOD__.': Role deleted or no permission.');
			    $checked_roles[$a_role] = false;
				return false;
			}
			// A local role is only displayed, if it is contained in the subtree of
			// the localy administrated category. If the import function has been
			// invoked from the user folder object, we show all local roles, because
			// the user folder object is considered the parent of all local roles.
			// Thus, if we start from the user folder object, we initializ$isInSubtree = $folder_id == USER_FOLDER_ID || $folder_id == 0;e the
			// isInSubtree variable with true. In all other cases it is initialized
			// with false, and only set to true if we find the object id of the
			// locally administrated category in the tree path to the local role.
			if($a_folder != USER_FOLDER_ID and $a_folder != 0 and !$tree->isGrandChild($a_folder,$rolf))
			{
				$ilLog->write(__METHOD__.': Not in path of category.');
			    $checked_roles[$a_role] = false;
			    return false;
			}
			$ilLog->write(__METHOD__.': Assignment allowed.');
		    $checked_roles[$a_role] = true;
		    return true;
		}
	}

	function addGroupMember($group_id,$user_id,$type)
	{

		global $rbacsystem;

		if(($obj_type = ilObject::_lookupType(ilObject::_lookupObjId($group_id))) != 'grp')
		{
			$group_id = end($ref_ids = ilObject::_getAllReferences($group_id));
			if(ilObject::_lookupType(ilObject::_lookupObjId($group_id)) != 'grp')
			{
				return "-40";  //$this->__raiseError('Invalid group id. Object with id "'. $group_id.'" is not of type "group"','Client');
			}
		}

		if(!$rbacsystem->checkAccess('manage_members',$group_id))
		{
			return "-41";  //$this->__raiseError('Check access failed. No permission to write to group','Server');
		}


		if(ilObject::_lookupType($user_id) != 'usr')
		{
			return "-42";  //$this->__raiseError('Invalid user id. User with id "'. $user_id.' does not exist','Client');
		}
		if($type != 'Admin' and
		   $type != 'Member')
		{
			return "-43";  //$this->__raiseError('Invalid type '.$type.' given. Parameter "type" must be "Admin","Member"','Client');
		}

		if(!$tmp_group = ilObjectFactory::getInstanceByRefId($group_id,false))
		{
			return "-44";  //$this->__raiseError('Cannot create group instance!','Server');
		}

		if(!$tmp_user = ilObjectFactory::getInstanceByObjId($user_id,false))
		{
			return "-45";  //$this->__raiseError('Cannot create user instance!','Server');
		}


		include_once 'Modules/Group/classes/class.ilGroupParticipants.php';
		$group_members = ilGroupParticipants::_getInstanceByObjId($tmp_group->getId());

		switch($type)
		{
			case 'Admin':
				$group_members->add($tmp_user->getId(),IL_GRP_ADMIN);
				break;

			case 'Member':
				$group_members->add($tmp_user->getId(),IL_GRP_MEMBER);
				break;
		}
		return true;
	}

?>