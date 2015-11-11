<?
	/*
		02-11-2012	rasmus@3kings.dk	draft
		24-03-2014	rasmus@3kings.dk	s+f can change roles
	*/
	if ($_SERVER['REQUEST_URI'] == $_SERVER['PHP_SELF']) header("location: /");
		
	content_plugin_register('uid', 'content_handle_user', 'Medlem');

	function show_user($uid)
	{
		header("location: ?uid={$uid}");
		die("<script>document.location.href='?uid={$uid}';</script>");
	}

	function content_handle_edit_user($user)
	{
		if (isset($_REQUEST['data']))
		{
			$data = $_REQUEST['data'];
			if (isset($_REQUEST['password']) && $_REQUEST['password']!='')
			{
				$data['password'] = md5($_REQUEST['password']);
			}
		}
		else
		{
			$data = array();
		}
		
		
		$user['businesses_list'] = addslashes(json_encode(logic_get_business_list()));


		if (logic_is_admin())
		{
			
			if (isset($_FILES['profile_image'])) logic_upload_profile_image($user['uid'], $_FILES['profile_image']);
			if (isset($_REQUEST['data'])) 
			{
				logic_update_member_expiration($user['uid'], $data['profile_birthdate'], $data['profile_started']);
				$user = logic_save_user($user['uid'], $data);
				$user['businesses_list'] = addslashes(json_encode(logic_get_business_list()));
				
				show_user($user['uid']);
			}
			
			return term_unwrap('user_profile_edit_admin', $user);
		}
		else if ($user['uid'] == $_SESSION['user']['uid'])
		{
			if (isset($_FILES['profile_image'])) logic_upload_profile_image($user['uid'], $_FILES['profile_image']);
			if (isset($_REQUEST['data'])) 
			{
				$user = logic_save_user($user['uid'], $data);
				$user['businesses_list'] = addslashes(json_encode(logic_get_business_list()));
				$_SESSION['user'] = array_merge($_SESSION['user'], $user);
				show_user($user['uid']);
			}
			return term_unwrap('user_profile_edit_user', $user);
		}
		else if (logic_is_club_secretary($user['cid']))
		{
			if (isset($_FILES['profile_image'])) logic_upload_profile_image($user['uid'], $_FILES['profile_image']);
			if (isset($_REQUEST['data'])) 
			{
				if (isset($data['profile_birthdate']))
				{
					logic_update_member_expiration($user['uid'], $data['profile_birthdate'], $data['profile_started']);
				}
				$user = logic_save_user($user['uid'], $data);
				show_user($user['uid']);
			}
			if ($user['uid'] == $_SESSION['user']['uid']) $_SESSION['user'] = array_merge($_SESSION['user'], $user);
			$user['businesses_list'] = addslashes(json_encode(logic_get_business_list()));
			return term_unwrap('user_profile_edit_secretary', $user);
		}
		else return term('no_access');
	}
	
	function content_handle_show_user($user,$user_roles,$may_edit_profile)
	{
  
		if (isset($_REQUEST['message']))
		{
			logic_save_mail($user['private_email'], MASS_MAILER_REPLY_WHO, $_REQUEST['message'],0,$_SESSION['user']['uid']);
		}
		logic_update_user_view_tracker($user['uid']);
		$club = logic_get_club($user['cid']);		
		$html = "";
		if ($may_edit_profile)
		{
			$html .= term_unwrap('user_profile_edit_link',$user);
			
			if (isset($_REQUEST['leave']) && $_REQUEST['leave']==true)
			{
				logic_user_on_leave($user);
			}
			
			if (isset($_REQUEST['honorary']))
			{
				if (logic_is_club_secretary($user['cid']))
				{
					logic_nominate_role($user['uid'], logic_get_club_year_end(), logic_get_club_year_end(1), HONORARY_RID, $_REQUEST['honorary']);
					$html .= term('user_nominated_ok');
				}
				else
				{
					$html .= term('user_nominated_fail');
				}
			}
		}

		$html .= term_unwrap('user_profile_1', $user);
    	$html .= term_unwrap('user_profile_club', $club);
		$html .= term('user_role_pre');
		
		for ($i=0;$i<sizeof($user_roles);$i++)
		{ 
			if (logic_is_admin())
			{			     
				$html .= term_unwrap('user_role_item_admin', $user_roles[$i]);                
			}
			else
			{
				$html .= term_unwrap('user_role_item', $user_roles[$i]);
			}
		}
		$html .= term('user_role_post');
		$html .= term_unwrap('user_profile_2', $user);
	
		
		if (logic_is_admin())
		{
			$user['roles_json'] = json_encode( fetch_system_roles() );
			$html .= term_unwrap('user_role_add',$user);
		}
		
		$stats = logic_get_user_stats($user['uid'], $user['cid']);
		
		$html .= term_unwrap('user_stats', array('data'=>addslashes(json_encode($stats))));
		
		if ($user['view_tracker'])
		{
			$peek = logic_get_user_tracker($user['uid']);
			if (!empty($peek)) $html .= term_unwrap('user_viewed', $peek, true);
		}
		
		return $html;
	}
	
	function content_handle_new_user()
	{
		$html = "";
		
		if (isset($_REQUEST['data'])) $data = $_REQUEST['data'];
		else $data=array(
		"profile_firstname" => "",
		"profile_lastname" => "",
		"profile_birthdate" => "",
		"profile_started" => "", 
		"private_address" => "",
		"private_houseno" => "",
		"private_houseletter" => "",
		"private_housefloor" => "",
		"private_houseplacement" => "",
		"private_zipno" => "",
		"private_city" => "",
		"private_phone" => "",
		"private_mobile" => "",
		"private_email" => ""
		);
		
		if (isset($_REQUEST['data']))
		{
			$uid = logic_create_user($_REQUEST['data'], $_SESSION['user']['cid']);
			if ($uid>0)
			{			
				cache_invalidate("stats");
				header("location: ?uid=$uid");
				die();
			}
			else
			{
				$html .= logic_get_error_msg();
			}
		}
		$html .= term_unwrap('user_create',$data);
		return $html;
	}
		
	/**
	 *	nomination: 	source club nominates a user for destination club
	 *	approval:   	destination club chairman approves nomination
	 *	information: 	user, national secretary and national president, destination and source chariman is informed
	 */
	function content_handle_move_user($user)
	{
		$destination = $_REQUEST['move'];
		
		if ($destination=="")
		{
			$clubs = logic_get_club_names();
			$data = array_merge($user, array('clubs'=>json_encode($clubs)));
			return term_unwrap('move_user_nominate', $data);
		}
		else if (!isset($_REQUEST['approval']))
		{
			$comment = $_REQUEST['comment'];
			
			$source_club = logic_get_club($user['cid']);
			$source_chairman = logic_get_club_chairman($user['cid']);
			$source_secretary = logic_get_club_secretary($user['cid']);
			
			$destination_club = logic_get_club($destination);
			$destination_chairman = logic_get_club_chairman($destination);
			$destination_secretary = logic_get_club_secretary($destination);
			
			$nomination_data = array(
				'member_uid' => $user['uid'],
				'member_name' => "{$user['profile_firstname']} {$user['profile_lastname']}",
				'comment' => $comment,
				'source_club_name' => $source_club['name'],
				'source_club_id' => $source_club['cid'],
				'target_club_name' => $destination_club['name'],
				'target_club_id' => $destination_club['cid']				
			);
			
			// die(print_r($nomination_data));
			
			$subj = term_unwrap('move_user_nomination_subj', $nomination_data);
			$body = term_unwrap('move_user_nomination_body', $nomination_data);
			
			$recv = array(NATIONAL_SECRETARY_MAIL);
			logic_save_mail($recv, $subj, $body);
			
			return term_unwrap('move_user_nominated', $user);	
		}
		else
		{
			if (logic_is_club_secretary($destination) || logic_is_club_chairman($destination) || logic_is_admin())
			{
				logic_move_user_to_new_club($user['uid'], $destination);
				
				$source_club = logic_get_club($user['cid']);
				$source_chairman = logic_get_club_chairman($user['cid']);
				$source_secretary = logic_get_club_secretary($user['cid']);
				$source_df = logic_get_district_chairman($source_club['district_did']);
				
				
				$destination_club = logic_get_club($destination);
				$destination_chairman = logic_get_club_chairman($destination);
				$destination_secretary = logic_get_club_secretary($destination);
				$destination_df = logic_get_district_chairman($destination_club['district_did']);
				
				$nomination_data = array(
					'member_uid' => $user['uid'],
					'member_name' => "{$user['profile_firstname']} {$user['profile_lastname']}",
					'source_club_name' => $source_club['name'],
					'source_club_id' => $source_club['cid'],
					'target_club_name' => $destination_club['name'],
					'target_club_id' => $destination_club['cid']				
				);
				
				$subj = term_unwrap('move_user_nomination_done_subj', $nomination_data);
				$body = term_unwrap('move_user_nomination_done_body', $nomination_data);
				$recv = array(
					$source_df['private_email'],
					$destination_df['private_email'],
					$destination_chairman['private_email'], $destination_secretary['private_email'],
					$source_chairman['private_email'], $source_secretary['private_email'],
					$user['private_email']
				);
				logic_save_mail($recv, $subj, $body);
				
				return term_unwrap('move_user_nominated_done', $user);	
			}
		}
	}
	
	
	function content_handle_user()
	{
		$uid = $_REQUEST['uid'];
		if (!logic_is_member()) return term('article_must_be_logged_in');
		    
    if (logic_is_admin() && isset($_REQUEST['permanent_delete']))
    {
      logic_delete_user($uid);
      header("location:/");
      die();    
    }
        
        
		if (isset($_REQUEST['vcard']))
		{
			
			header('Content-type: text/x-vcard');
			header('Content-Disposition: attachment; filename="info.vcard"');
			die(logic_get_vcard($_REQUEST['uid']));
		}
		if (isset($_REQUEST['delete_role']) && logic_is_admin())
		{
			logic_delete_role($_REQUEST['uid'], $_REQUEST['delete_role']);
		}
		if ($uid!=-1) 
		{
			 $user = logic_get_user_by_id($uid);
			if (isset($_REQUEST['end_role']) && (logic_is_admin() ))
			{
				logic_end_role($_REQUEST['uid'], $_REQUEST['end_role']);
			}
			if (isset($_REQUEST['newrole']) && (logic_is_admin()))
			{
				add_role($_REQUEST['uid'], $_REQUEST['newrole']['rid'], $_REQUEST['newrole']['start_date'], $_REQUEST['newrole']['end_date']);
			}
		}
		
		
		
		$html = "";
		$uid = $_REQUEST['uid'];
		
		if ($uid==-1)
		{
			if (logic_is_secretary())
			{
				$html .= content_handle_new_user();
			}
			else
			{
				$html .= term('no_access');
			}
		}
		else
		{
			$user = logic_get_user_by_id($uid);
			$user_roles = logic_get_roles($uid);
			$user_roles = array_merge($user_roles, logic_get_old_roles($uid));
			$may_edit_profile = logic_may_edit_profile($user);

      if ($may_edit_profile && isset($_REQUEST['resign']))
      {
      	if (isset($_REQUEST['approve']) && logic_is_admin())
      	{
      		logic_resign_user($user['uid'],$_REQUEST['resign']);
      		die(term("resignation_approved"));
      	}
      	else if (!isset($_REQUEST['approve']))
      	{
      		
      		event_nominate_resignation($user,$_REQUEST['resign']);
      		die(term("resignation_nominated"));
      	}
        // 
      }

			if (isset($_REQUEST['move']) && (logic_is_secretary()  || logic_is_admin()))
			{
				$html .= content_handle_move_user($user);
			}
			else if (isset($_REQUEST['edit']) && $may_edit_profile) 
			{
				$html .= content_handle_edit_user($user);
			}
			else
			{		
				$html = content_handle_show_user($user,$user_roles,$may_edit_profile);
			}	
            
		set_title(utf8_decode($user['profile_firstname']).' '.utf8_decode($user['profile_lastname']));
    }		
		
		return $html;
	}
?>