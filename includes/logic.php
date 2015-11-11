<?
/*
	logic layer for round table system (c) 3kings.dk 2012-2013
	
		
	28-10-2012	rasmus@3kings.dk	draft
	29-10-2012	rasmus@3kings.dk	meetings logic
	31-10-2012	rasmus@3kings.dk	chesire cat for db interaction
	01-11-2012	rasmus@3kings.dk	added article logic
	02-11-2012	rasmus@3kings.dk	added country logic
	03-11-201i2	rasmus@3kings.dk	stats functionaliy
	05-11-2012	rasmus@3kings.dk	meeting image functionality
	06-11-2012	rasmus@3kings.dk	meeting image folder structure
	07-11-2012	rasmus@3kings.dk	unified date format (logic_date_format)
	13-11-2012	rasmus@3kings.dk	clubs loading for country/district pages
	20-11-2012 	rasmus@3kings.dk 	minutes saving
	25-11-2012 	rasmus@3kings.dk 	finish minutes
	27-11-2012	rasmus@3kings.dk	logic_may_edit_profile added, logic_upload_profile_image
	28-11-2012	rasmus@3kings.dk	logic_get_roles
	29-11-2012	rasmus@3kings.dk	logic_search, logic_create_user, events added
	21-12-2012	rasmus@3kings.dk	logic_end_role
	02-01-2013	rasmus@3kings.dk	logic_get_banners
  13-01-2013  rasmus@3kings.dk  resign membership
  29-01-2013	rasmus@3kings.dk	tablerservice functionality
  13-02-2013	ramsus@3kings.dk	tracker
  21-02-2013	rasmus@3kings.dk	newsletter
  23-02-2013  rasmus@3kings.dk  file migration
  24-02-2013  rasmus@3kings.dk  error in logic_get_meeting_duties fixed (is_numeric)
  25-02-2013	rasmus@3kings.dk	honorary members allowed to login
  26-02-2013  rasmus@3kings.dk  attachment to newsletters
  02-03-2012  rasmus@3kings.dk  new users are automatically signed up for club meetings. resigning users are removed from future meetings
  03-03-2012	rasmus@3kings.dk	copy of newsletters sent to club mail
	05-03-2013	rasmus@3kings.dk	update club, logo upload
	11-03-2013	rasmus@3kings.dk	fixed logic_is_member function
	24-03-2013	rasmus@3kings.dk	logic_fetch_minutes_this_year
	26-03-2013	rasmus@3kings.dk	best club/meetings
	05-04-2013	rasmus@3kings.dk	club chairmen now has same rights as club secretaries (logic_is_club_secretary)
	01-05-2013	rasmus@3kings.dk	random user
	21-05-2013	rasmus@3kings.dk	logic_get_old_roles, logic_update_user_view_tracker
  28-06-2013  rasmus@3kings.dk  logic_delete_role
  06-08-2013	rasmus@3kings.dk	logic_club_board_submission_period
	10-10-2013	rasmus@3kings.dk	fixed issue in logic_save_mail
	15-10-2013	rasmus@3kings.dk	when membership expiration is updated, sign up for future meetings (logic_update_member_expiration)
	13-04-2014	rasmus@3kings.dk	logic_get_duties added
	07-07-2014	rasmus@3kings.dk	logic_login updated with server login
	-- history moved to github ---
  */ 

	$path = realpath('.');
	require_once $path.'/includes/datafetcher.php';
	require_once $path.'/plugins/events.php';
	require_once $path.'/includes/pop.php';
	require_once $path.'/includes/stacktrace.php';
	
	$error_messages = "";
   
	$online_debug_log_msg = array();
	function online_log($msg)
	{
		global $online_debug_log_msg;
		$bt = debug_backtrace();
		$caller = array_shift($bt);
		$online_debug_log_msg[] = "{$caller['file']}@{$caller['function']}:{$caller['line']} - {$msg}";
	}
	
	function get_online_log_html()
	{
		global $online_debug_log_msg;
		$html = "";
		foreach($online_debug_log_msg as $msg)
		{
			$html .= "<li>{$msg}\n";
		}
		return $html;
	}
	
	function logic_get_meeting_gallery($cid)
	{
		return fetch_meeting_gallery($cid);
	}

	function logic_get_sms_balance_country()
	{
		$clubs = fetch_clubs("");
		$data = array();
		
		foreach ($clubs as $c)
		{
			$data[] = array(
				"cid" => $c['cid'],
				"club" => $c['name'],
				"balance" => get_sms_balance($c['cid'])
			);
		}
		
		return $data;
		
	}
	
	
	function logic_update_sms_balance($cid, $change)
	{
		if (logic_is_admin())
		{
			update_sms_balance($cid, $change);
		}
	}
	
	function logic_fix_mobile_phone_number($n)
	{
		if (preg_match("/^\\+\d*$/",$n))
		{
		}
		else
		{	
			$n = preg_replace("/[^\\+0-9]/", "", $n);
			if (strpos($n, "+")===false)
			{
				$n = SMS_DEFAULT_COUNTRY_CODE.$n;
			}
		}
		$n = str_replace('+', '', $n);
		return $n;
	}
	
	function logic_send_sms($message)
	{
		if (logic_may_edit_meeting($_REQUEST['cid']))
		{
			$active_members = fetch_active_club_members($_SESSION['user']['cid']); 
			
			$r = array();
			
			foreach ($active_members as $m)
			{
				if (!empty($m['private_mobile']))
				{
					$r[] = logic_fix_mobile_phone_number($m['private_mobile']);
				}
				else if (!empty($m['private_phone']))
				{
					$r[] = logic_fix_mobile_phone_number($m['private_phone']);
				}
			}
			
			$data = array(
				'apikey' => SMS_API_KEY,
				'username' => SMS_API_USER,
				'password' => md5(SMS_API_PASSWORD),
				'shortcode' => '1919',
				'price' => '0',
				'text' => utf8_decode($message),
				'from' => '+'.logic_fix_mobile_phone_number($_SESSION['user']['private_mobile']),
				'recipient' => implode(",",$r)
			);
			
			$url = 'https://api.sms1919.dk/rpc/push/?'.http_build_query($data);
			
			if (SMS_DISABLE_SENDING)
			{
				echo "SMS send disabled - $url";
			}
			else
			{
				$opts = array('http'=>array('header' => "User-Agent:Mozilla/5.0 (Windows NT 6.2) AppleWebKit/537.1 (KHTML, like Gecko) Chrome/21.0.1180.75 Safari/537.1\r\n"));
				$context = stream_context_create($opts);
				$result = file_get_contents($url,false,$context);
			}
				
			put_sms_history($_SESSION['user']['uid'], $_SESSION['user']['cid'], $message);
			update_sms_balance($_SESSION['user']['cid'], -sizeof($active_members));
		}
	}

  function logic_create_club()
  {
    if (logic_is_admin())
    {
      return create_club();
    }
    else
    {
      return $_SESSION['user']['cid'];
    }
  }	
  function logic_delete_club($cid)
  {
    if (logic_is_admin())
    {
      delete_club($cid);
      logic_log(__FUNCTION__, "Logic delete club CID:{$cid}");
    }
  }
  
	// validate array
	function valarr(&$v,$size=0)
	{
		$error = false;
		
		if (!is_array($v))
		{
			$error = "Not an array";
		}
		else if (empty($v))
		{
			$error = "Empty array";
		}
		else if ($size>0 && sizeof($v)!=$size)
		{
			$error = "Wrong size: not $size - actual ".sizeof($v);
		}
		
		if ($error !== false)
		{
			$trace = stacktrace();
			logic_save_mail(ADMIN_MAIL, $error, $trace);
		}
		return $v;
	}
	
  function logic_delete_user($uid)
  {
    if (logic_is_admin())
    {
      delete_meeting_attendance_for_user($uid);
      delete_user_role_data($uid);
      delete_user_data($uid);
      logic_log('logic_delete_user', "Logic delete user UID:{$uid}");
    }
  }	
	/**
	 *	returns the number of unread mails in the clubmail
	 *	@param mixed[] club data
	 *	@return -1 if an error occurred 0 or positive number otherwise
	 */
	function logic_check_clubmail($club)
	{
		$data = explode(" ", $club['name']);
		$mailbox = strtolower($data[0])."@roundtable.dk";
		$password = trim($club['webmail_password']); // "RT-".substr($data[0], 2)."password";
		// $club['webmail_password']; //	
		$conn = pop3_login("mail.roundtable.dk", "143", $mailbox, $password);
		if ($conn)
		{
			$stat = pop3_stat($conn);
			$stat['mailbox'] = $mailbox;
			if ($stat['Unread']>0) return $stat['Unread'];
		}
		else
		{
			logic_save_mail(ADMIN_MAIL, "Unable to open clubmail $mailbox", "Failed checking clubmail $mailbox on rtd.dk");
			return -1;
		}		
	}
	
	function logic_add_meeting_link($cid,$mid, $source, $link)
	{
		if (logic_may_edit_meeting($cid))
		{
			save_meeting_link($mid,$source,$link);
		}
	}
	
	function logic_get_meeting_links($mid)
	{
		return get_meeting_links($mid);
	}
	
	function logic_delete_meeting_link($mlid,$cid)
	{
		if (logic_may_edit_meeting($cid))
		{
			delete_meeting_link($mlid);
		}
	}
	
	
	function logic_get_random_user()
	{
		return get_random_user();
	}
	
	function  logic_upload_club_logo($data, $cid)
	{
		$fn = $data['name'];
		$ext = "";
		if (stripos($fn,".jpg") || stripos($fn, ".jpeg")) {$ext=".jpg";}
		else if (stripos($fn, ".png")) {$ext=".png";}
		else if (stripos($fn, ".gif")) {$ext=".gif";}
		
		if ($ext!="")
		{
			$newf = $cid.$ext;
			$newfn = CLUB_LOGO_PATH.$newf;
			move_uploaded_file($data['tmp_name'], $newfn);
			return $newf;
		}		
		return false;
	}
    
    function logic_upload_club_chairman($data, $cid)
	{   
		$fn = $data['name'];
		$ext = "";
		if (stripos($fn,".jpg") || stripos($fn, ".jpeg")) {$ext=".jpg";}
		else if (stripos($fn, ".png")) {$ext=".png";}
		else if (stripos($fn, ".gif")) {$ext=".gif";}
		
		if ($ext!="")
		{
			$newf = rand().$cid.$ext;
			$newfn = '/var/www/vhosts/rtd.dk/dev/uploads/chariman_image/'.$newf;
			move_uploaded_file($data['tmp_name'], $newfn);
			return $newf;
		}		
		return false;
	}
	
	function logic_get_club_birthdays($cid,$today=false)
	{
		return get_user_birthday($cid,$today);
	}
	
	function logic_send_newsletter($roles,$districts,$title,$content,$attachment_id=0, $uid=0)
	{
		$uids = array();
		$u = get_users($roles,$districts);
		for($i=0;$i<sizeof($u);$i++)
		{
			$uids[] = $u[$i]['uid'];
			logic_save_mail($u[$i]['private_email'], $title, $content,$attachment_id,$uid);
		}
		if (NB_MAIL_POSTFIX=='@roundtable.dk')
		{
			$clubs = get_clubs_from_uids($uids);
			foreach($clubs as $c)
			{
				$data = explode(" ",$c['name']);
				$mail = strtolower($data[0])."@roundtable.dk";
				logic_save_mail($mail, $title, $content, $attachment_id, $uid);
			}
		}
		return sizeof($u);
	}
	
	function logic_club_mail($cid)
	{
		$c = logic_get_club($cid);
    	$d = explode(" ", $c['name']);
    	return strtolower($d[0])."@roundtable.dk";
	}


	function logic_should_update_details()
	{
		if (logic_is_member())
		{
			$v = get_last_page_view_within_range($_SESSION['user']['uid'],"3 month");
			return $v;
		}
		else
		{
			return false;
		}
	}


  function logic_resign_user($uid,$why,$end_date=false)
  {
	// normal resignation - send reason
	// if $end_date is a real date (i.e. not false) then the reason is not transmitted since this is a MANUAL resignation by admin
	if ($end_date === false) 
	{
		event_user_resign($uid,$why);    
		$end_date = date("Y-m-d");
	}

	   logic_log(__FUNCTION__, "Logic resign user UID:{$uid}, {$why}, {$end_date}");
	
    $user = logic_get_user_by_id($uid);
    $r = fetch_active_roles($uid);
    for($i=0;$i<sizeof($r);$i++)
    {
      if ($r[$i]['rid']==MEMBER_ROLE_RID)
      {
        save_user($uid,array('profile_ended' => $end_date));
        end_role_period($r[$i]['riid'],$end_date);
        break;        
      }
    }
    
    $meetings = logic_fetch_future_meetings_for_club($user['cid'],"asc",100,false);
    foreach ($meetings as $m)
    {
		$meeting_end = strtotime($m['end_time']);
		$termination = strtotime($end_date);
		if ($meeting_end > $termination)
		{
		  logic_log(__FUNCTION__, "Deleting meeting attendance for UID:{$uid}, MID:{$m['mid']}");
		  delete_meeting_attendance_for_uid($m['mid'],$uid);
		 }
    }
    
    
    
  }

	/**
	 * fetch items in tabler service category
	 * @param string $tsid category id
	 * @return mixed[] items
	 */
	function logic_get_tabler_service_items($tsid)
	{
		$items = get_tabler_service_items($tsid);
		for ($i=0;$i<sizeof($items);$i++)
		{
			if ($items[$i]['uid']==$_SESSION['user']['uid'] || logic_is_admin())
			{
				$items[$i]['may_edit']=true;
			}
			else
			{
					$items[$i]['may_edit']=false;
			}
		}
		return $items;
	}
	
	/**
	 *	@param int $tid id of item
	 */
	function logic_delete_tabler_service_item($tid)
	{
		delete_tabler_service_item($tid);
	}
	
	
	
	/**
	 * save item to tabler service catalog
	 */
	function logic_put_tabler_service_item($tsid,$item)
	{
		put_tabler_service_item($tsid, $item['headline'], $item['description'], $item['location'], $item['price'], $item['duration'], $item['contact'], $_SESSION['user']['uid']);
	}
	
	/**
	 * fetch categories
	 * @return mixed[] categories
	 */
	function logic_get_tabler_service_categories()
	{
		return get_tabler_service_categories();
	}
	
	/**
	 * fetch specific category
	 * @param int $tsid id
	 * @return mixed[] categories
	 */
	function logic_get_tabler_service_category($tsid)
	{
		$data = logic_get_tabler_service_categories();
		for ($i=0;$i<sizeof($data);$i++) 
		{
				if ($data[$i]['tsid']==$tsid) 
				{
					return $data[$i];
				}
		}
		return false;
	}

	/** 
	 *	save mail in the output queue
	 *	@param string[] $to	list of mail addresses
	 *	@param string $subj mail subject
	 *	@param string $body mail body
	 *	@param int $attachment_id id of attachment (0 if no attachment)   
	 */
  function logic_save_mail($to, $subj, $body,$attachment_id=0,$uid=0)
  {
    $subj = html_entity_decode($subj);
    $body = html_entity_decode($body);
		
    if (is_array($to))
    {
      for ($i=0;$i<sizeof($to);$i++) 
      {
        save_mail($to[$i],$subj,$body,true,$attachment_id,$uid);
      }
    }
    else if ($to!="")
    {
      if (!is_array($to)) $to = trim($to);
      save_mail($to,$subj, $body, true, $attachment_id,$uid);
    }
  }

	/**
	 *	read last known error
	 *	@return last known error string
	 */
	function logic_error()
	{
		global $error_messages;
		return $error_messages=="";
	}

	function logic_decline_future_meetings($uid,$cid)
	{
    logic_log(__FUNCTION__, "Removing meeting attendance for UID:{$uid}");

		// remove from future meetings in old club
		$meetings = logic_fetch_future_meetings_for_club($cid,"asc",100,false);
		foreach ($meetings as $m)
		{
		  delete_meeting_attendance_for_uid($m['mid'],$uid);
		}
	
	}
	
	/**
	 *	change club affiliation for user
	 *	@param int $uid user id
	 *	@param int $new_cid club id
	 */
	function logic_move_user_to_new_club($uid, $new_cid)
	{
    logic_log(__FUNCTION__, "Moving user to new club UID:{$uid}");
		$user = logic_get_user_by_id($uid);
		
		// remove from future meetings in old club
		$meetings = logic_fetch_future_meetings_for_club($user['cid'],"asc",100,false);
		foreach ($meetings as $m)
		{
      logic_log(__FUNCTION__, "Removing meeting attendance UID:{$uid}, MID:{$m['mid']}");
		  delete_meeting_attendance_for_uid($m['mid'],$uid);
		}

		// add to future meetings in new club
		$meetings = logic_fetch_future_meetings_for_club($new_cid,"asc",100,false);
		foreach ($meetings as $m)
		{
		  logic_save_meeting_attendance($new_cid,$m['mid'],$uid,1,"");
		}


		// end club board roles
		$user = logic_get_user_by_id($uid);
		$r = fetch_active_roles($uid);
		$cbr = logic_get_club_board_roles();
		$end_date = date("Y-m-d");
		for($i=0;$i<sizeof($r);$i++)
		{
			for ($j=0;$j<sizeof($cbr);$j++)
			{
				if ($cbr[$j]['rid'] == $r[$i]['rid'])
				{
					end_role_period($r[$i]['riid'],$end_date);
				}
			}
		}
		
		update_user_club($uid, $new_cid);
	}
	
	/**
	 *	add banner to database
	 *	@param mixed[] form data
	 *	@param mixed[] file data
	 */
	function logic_add_banner($data, $fs)
	{
		$imgdata = file_get_contents($fs['tmp_name']);
		$id = put_banner(addslashes(serialize($imgdata)), $fs['type'], $data['startdate'], $data['enddate'], $data['position'], $data['title']);		
		file_put_contents(BANNER_UPLOAD_PATH.$id.".data", $imgdata);
	}

	/**
	 *	get specific banner from db
	 *	@param int $bid banner id
	 *	@return mixed[] banner data
	 */
	function logic_get_banner($bid)
	{
		$banner = get_banner($bid);
		$banner['image'] = stripslashes(unserialize($banner['image']));//(file_get_contents(BANNER_UPLOAD_PATH.$bid.".data"));
		return $banner;
	}

	/**
	 *	update click counter for banner
	 *	@param int $bid banner id
	 */
	function logic_click_banner($bid)
	{
		put_banner_click($bid, $_SERVER['REMOTE_ADDR']);
	}

	/**
	 *	load banners for a given position - or all positions
	 *	@param int $position banner position (-1 for all positions)
	 *	@param int $limit max number of banners 
	 *	@return mixed[] banners
	 */
	function logic_get_banners($position=-1, $limit=9999)
	{
		return get_banners($position, $limit);
	}
	
	/**
	 *	update page view time stamp for current user - usre must be logged in!
	 */
	function logic_update_last_page_view()
	{
		update_last_page_view($_SESSION['user']['uid'], addslashes(get_title()), $_SERVER['REQUEST_URI']);
		
		if (strpos($_SERVER['REQUEST_URI'], 'stalker')===false)
		{
			put_user_path_tracker($_SESSION['user']['uid'],$_SERVER['REQUEST_URI']);
		}
	}
	
	/**
	 *	get last known error string - formatted
	 */
	function logic_get_error_msg()
	{
		global $error_messages;
		return term('error_pre').$error_messages.term('error_post');
	}

	/**
	 *	get files associated with article
	 *	@param int $aid article id
	 *	@param bool $gallery_only -- not used
	 *	@return mixed[] array of files
	 */
	function logic_get_article_files($aid, $gallery_only)
	{
		return get_article_files($aid, $gallery_only);
	}
	
	/**
	 * return shuffled password
	 * @param mixed[] user record
	 * @return string password
	 */
	function logic_generate_password($user)
	{
		return str_shuffle($user['username']);
	}
	
	
	/**
	 *	translate role id to role name
	 *	@param int $rid	role id
	 *	@return textual representation of role
	 */
	function logic_get_role_name($rid)
	{
		$roles = fetch_system_roles();
		foreach($roles as $r)
		{
			if ($r['rid']==$rid) return $r['shortname'];
		}
		return '';
	}

	/**
	 *	translate role id to description
	 *	@param int $rid	role id
	 *	@return textual representation of role
	 */
	function logic_get_role_description($rid)
	{
		$roles = fetch_system_roles();
		foreach($roles as $r)
		{
			if ($r['rid']==$rid) return $r['description'];
		}
		return '';
	}
	
	/**
	 *	nominate a user for a given role
	 *	@param int $uid user id
	 *	@param date $start activation date of role
	 *	@param date $end termination date of role
	 *	@param int $rid role id
	 *	@param string $comment nomination comment
	 */	
	function logic_nominate_role($uid, $start, $end, $rid, $comment)
	{
		save_role_nomination($uid, $start, $end, $rid, $_SESSION['user']['uid'], date("Y-m-d"), $comment);
    event_role_nomination($uid, $rid, $comment);
	}
	
	
	/**
	 *	set error message
	 *	@param string  $msg error message
	 */
	function logic_set_error($msg)
	{
		global $error_messages;
		$error_messages .= $msg;
	}

	/**
	 *	verify if a username exists or not
	 *	@param string $username username
	 *	@return true/false
	 */
	function logic_username_exists($username)
	{
		if (fetch_user_by_username($username)===false) return false;
		else return true;
	}

	/**
	 *	create user in database - associate with club
	 *	@param mixed[] $data	user form data
	 *	@param int $cid club id
	 *	return new user id
	 */
	function logic_create_user($data,$cid)
	{
		$data['username'] = $data['private_email'];
		$data['password'] = md5(DEFAULT_NEW_USER_PASSWORD);

		$empty_fields = false;
		foreach($data as $key => $value)
		{
			if ($value=="") $empty_fields = true;
		}
		if ($empty_fields)
		{
			logic_set_error(term('error_not_all_fields_filled_in'));
			return -1;
		}
		if (logic_username_exists($data['username'])) 
		{
			logic_set_error(term('error_username_exists')." ".$data['username']);
			return -1;
		}
		
		$uid = create_user($data,$cid);
		logic_update_member_expiration($uid,$data['profile_birthdate'],$data['profile_started']);
		event_new_user($uid);
    
		$meetings = logic_fetch_future_meetings_for_club($cid,"asc",100,false);
		foreach ($meetings as $m)
		{
		  logic_save_meeting_attendance($cid,$m['mid'],$uid,1,"");
		}
    
    
		return $uid;
	}

	/**
	 *	execute free text query in database on users, articles, clubs and meetings
	 *	@param $q query text
	 *	@return mixed[] result set
	 */
	function logic_search($q,$old=false)
	{
		$data = array(
			"users" => array(),
			"articles" => array(),
			"clubs" => array(),
			"meetings" => array()
		);
		if (trim($q)=="") return $data;
		
		put_search_query($q);
		
		
		$search = explode(' ', $q);
    
		$udata = array();
    $udetails = array();
		for($i=0;$i<sizeof($search);$i++)
		{
			$users = fetch_search_users($search[$i],$old);      
      for ($j=0;$j<sizeof($users);$j++)
      {
        $uid = $users[$j]['uid'];
		$points = 1;
		foreach($users[$j] as $key => $value)
		{
			if (strcasecmp($value,$search[$i])==0) $points+=2;
		}
        if (!isset($udata[$uid])) $udata[$uid]=$points;
        else $udata[$uid]+=$points;
        $udetails[$uid] = $users[$j];
      }
                             /*
			for ($j=0;$j<sizeof($users);$j++)
			{
				$data['users'][$users[$j]['uid']] = $users[$j];
			}                    */
			
			$articles = fetch_search_articles($search[$i]);
			for ($j=0;$j<sizeof($articles);$j++)
			{
				$data['articles'][$articles[$j]['aid']] = $articles[$j];
			}
			
			$clubs = fetch_search_clubs($search[$i]);
			for ($j=0;$j<sizeof($clubs);$j++)
			{
				$data['clubs'][$clubs[$j]['cid']] = $clubs[$j];
			}
			
			$meetings = fetch_search_meetings($search[$i]);
			for ($j=0;$j<sizeof($meetings);$j++)
			{
        $club = logic_get_club($meetings[$j]['cid']);
        if (isset($club['name']))
        {
          $meetings[$j]['club'] = $club['name'];
  				$data['meetings'][$meetings[$j]['mid']] = $meetings[$j];
        }
			}
		}

    arsort($udata);
    
    foreach ($udata as $uid => $hits)
    {
      $data['users'][] = $udetails[$uid]; 
    }
    		
		
		return $data;
	}

	/**
	 *	get roles associated with user (both expired and active)
	 *	@param int $uid	user id
	 *	@return mixed[] list of roles
	 */
	function logic_get_roles($uid,$only_active=false)
	{
		if (logic_is_admin() && !$only_active)
		{
			$ur = fetch_user_roles($uid);
		}
		else
		{
			$ur = fetch_active_roles($uid);
		}
		
		for($i=0;$i<sizeof($ur);$i++)
		{
			$ur[$i]['start_date_fixed'] = logic_date_fix($ur[$i]['start_date']);
			$ur[$i]['end_date_fixed'] = logic_date_fix($ur[$i]['end_date']);			
		}
		return $ur;
	}
	
	function logic_get_old_roles($uid)
	{
		$ur=fetch_old_roles($uid);
		for($i=0;$i<sizeof($ur);$i++)
		{
			$ur[$i]['start_date_fixed'] = logic_date_fix($ur[$i]['start_date']);
			$ur[$i]['end_date_fixed'] = logic_date_fix($ur[$i]['end_date']);			
		} 
		return $ur;
	}
	
	
	/**
	 *	terminate role associated - i.e. set new end date for role association
	 *	@param int $uid user id
	 *	@param int $riid role association id
	 */
	function logic_end_role($uid, $riid)
	{
		end_role_period($riid, date("Y-m-d"));
	}

	/**
	 *	delete role from user
	 *	@param int $uid user id
	 *	@param int $riid role association id
	 */
	function logic_delete_role($uid, $riid)
	{
		delete_role_period($riid);
	}

	
	function log_to_console($what)
	{
		/* echo "<script>console.log('{$what}');</script>\n";*/
	}

	/**
	 * 	update membership expiration to the year where the member turns 40
	 *	@param int $uid user id
	 *	@param date $birthdate user birth date
	 *	@param date $charterdate user charter date
	 */
	function logic_update_member_expiration($uid,$birthdate,$charterdate)
	{
		log_to_console('logic_member_expiration');
		$bd = strtotime($birthdate);
		$bm = date("n", $bd);
		$by = date("Y", $bd);

		if (defined('EXPIRE_AGE'))
		{
			if ($bm>6) $expire_year = $by+EXPIRE_AGE+1;
			else $expire_year = $by+EXPIRE_AGE;
			log_to_console("expire: {$expire_year}");
		}
		else
		{
			if ($bm>6) $expire_year = $by+41;
			else $expire_year = $by+40;
			log_to_console("expire: {$expire_year}");
		}
		
		
		$expire_date = "{$expire_year}-06-30";
		
		logic_log(__FUNCTION__, "UID: {$uid}, Birth: {$birthdate}, Expire:{$expire_date}");
		
		// sign up for future meetings
		$ts = strtotime($expire_date);
		if ($ts>time())
		{
			log_to_console("expire is future - signing up for future meetings");
			$u = logic_get_user_by_id($uid);
			$meetings = logic_fetch_future_meetings_for_club($u['cid'],"asc",100,false);
			foreach ($meetings as $m)
			{
				if (get_meeting_attendance_uid($uid,$m['mid'])==0)
				{
					log_to_console("signing up for mid: m:{$m['mid']} c:{$u['cid']} u:{$uid}");
					logic_save_meeting_attendance($u['cid'],$m['mid'],$uid,1,"");
				}
			}
		}
		
//		die("m:$bm d:$expire_date f:$bm-$by");
		
		$data = array('profile_ended' => $expire_date);
		logic_save_user($uid, $data);
		update_role($uid, MEMBER_ROLE_RID, $charterdate, $expire_date);
	}
	
	/**
	 *	delete files associated with article
	 *	@param int $afid article file id
	 */
	function logic_delete_article_file($afid)
	{
		$f = get_article_file($afid);
		$fn = ARTICLE_FILE_UPLOAD_PATH.$f['aid']."/".$f['filename'];
		if (file_exists($fn) && !is_dir($fn)) unlink($fn);
		delete_article_file($afid);		
	}
	
	
	/**
	 *	process uploaded article file
	 *	@param int $aid article id
	 *	@param mixed[] $fs file upload data
	 */
	function logic_upload_article_file($aid, $fs)
	{
		if (!is_dir(ARTICLE_FILE_UPLOAD_PATH.$aid))
		{
			mkdir(ARTICLE_FILE_UPLOAD_PATH.$aid);
			chmod(ARTICLE_FILE_UPLOAD_PATH.$aid, 0777);
		}
		
		$newfn = ARTICLE_FILE_UPLOAD_PATH."$aid/".$fs['name'];
		move_uploaded_file($fs['tmp_name'], $newfn);
		put_article_file($aid, $fs['type'], addslashes($fs['name']), true);
	}
  
  function logic_upload_mail_attachment($fs)
  {
    $aid = put_mail_attachment(addslashes($fs['name']));
    move_uploaded_file($fs['tmp_name'], MAIL_ATTACHMENT_UPLOAD_PATH.$aid);
    return $aid;
  }

  /**
   * when user uploads new profile picture - make sure that the cache is invalidated
   * @param int $uid user id
   */        
  function logic_invalidate_user_image_cache($uid)
  {
		$folder = sys_get_temp_dir();
		$d = dir($folder);
		while (false !== ($entry=$d->read()))
		{
			if (strpos($entry,"rtd-uid-{$uid}-")!==false)
			{
				unlink($folder."/".$entry);
			}
		}
  }

	/**
	 *	process uploaded profile image
	 *	@param int $uid user id
	 *	@param mixed[] $fs upload data
	 */
	function logic_upload_profile_image($uid, $fs)
	{
		$fn = $fs['name'];
		$ext = "";
		if (stripos($fn,".jpg") || stripos($fn, ".jpeg")) {$ext=".jpg";}
		else if (stripos($fn, ".png")) {$ext=".png";}
		else if (stripos($fn, ".gif")) {$ext=".gif";}
		
		if ($ext!="")
		{
			$newfn = USER_IMAGES_UPLOAD_PATH.$uid.$ext;
			move_uploaded_file($fs['tmp_name'], $newfn);
      logic_update_profile_image($uid, $uid.$ext);
		}		
	}
  
  function logic_update_profile_image($uid, $fn)
  {
      $data = array("profile_image" => $fn);
			logic_save_user($uid,$data);
      logic_invalidate_user_image_cache($uid);
  }

	/**
	 *	finish meeting minutes (i.e. when a secretary marks meeting minuts as completed)
	 *	@param int $mid meeting id
	 */
	function logic_finish_meeting_minutes($mid,$mail_to_members)
	{
		update_timestamp('meeting', "mid=$mid", 'minutes_date');
		event_minutes_finished($mid,$mail_to_members);
	}
	
	/**
	 *	unlock meeting minutes 
	 *	@param int $mid meeting id
	 */
	function logic_unlock_meeting_minutes($mid)
	{
		update_timestamp('meeting', "mid=$mid", 'minutes_date', 'null');
	}
	
	/**
	 *	save user data in db
	 *	@param int $uid user id
	 *	@param mixed[] $data updated user profile data
	 *	@return mixed[] user data
	 */
	function logic_save_user($uid,$data)
	{
		logic_log(__FUNCTION__, "UID: {$uid}, ".print_r($data,true));
		if (isset($data['profile_ended']))
		{
			logic_resign_user($uid,'Profile termination',$data['profile_ended']);
		}
		save_user($uid,$data);

		$u = logic_get_user_by_id($uid);
		$privat = "{$u['private_address']} {$u['private_houseno']}, {$u['private_city']}, {$u['private_country']}";
		$loc = logic_get_latlng($privat);
		if ($loc !== false) put_geolocation($uid, $loc->lat, $loc->lng, 'home');

		$work  = "{$u['company_address']}, {$u['company_city']}, {$u['company_country']}";
		$loc = logic_get_latlng($work);
		if ($loc !== false) put_geolocation($uid, $loc->lat, $loc->lng, 'work');


		return $u;
	}

	function logic_get_latlng($what)
	{
		$what = urlencode($what);
		$url = "http://rtd.dk/scripts/rtd/geocodeproxy.php?address={$what}&sensor=false";
		$data_js = file_get_contents($url);
		$data = json_decode($data_js);
		if (empty($data->results))
		{
			return false;
		}
		else
		{
			return $data->results[0]->geometry->location;
		}
	}

	/**
	 *	save meeting minutes
	 *	@param int $mid meeting id
	 *	@param mixed[] $minutes meeting minutes
	 */
	function logic_save_meeting_minutes($mid, $minutes)
	{
		$stats = logic_meeting_stats(false,$mid);
		$minutes['minutes_number_of_participants'] = $stats['accepted'];
		$minutes['minutes_number_of_rejections'] = $stats['rejected'];
	
		save_meeting($minutes, $mid);
		return fetch_meeting($mid);
	}
  
  /**
   *	look up district chairman information
   *	@param int $did district id
   *	@return mixed[] user profile information
   */
  function logic_get_district_chairman($did) 
  {
  	return get_district_chairman_from_district($did);
  	
  }
  
	/**
	* read the district name
	* @param int $did district id
	* @return string name of district 
	*/
  function logic_get_district_name($did)
  {
  	$d = get_district_name($did);
  	return $d['name'];
  }
  
  /**
   *	look up district chairman information
   *	@param int $did district id
   *	@return mixed[] user profile information
   */
  function logic_get_district_chairmain($did)
  {
    return valarr(get_district_chairman_from_district($did));
  }
  
  /**
   *	look up club chairman information
   *	@param int $cid club id
   *	@return mixed[] user profile information
   */
  function logic_get_club_chairman($cid)
  {
    return valarr(get_club_chairman($cid));
  }
  
  /**
   *	look up club secretary information
   *	@param int $cid club id
   *	@return mixed[] user profile information
   */
  function logic_get_club_secretary($cid)
  {
    return valarr(get_club_secretary($cid));
  }
  
  
  /**
   * update view tracker
   */
  function logic_update_user_view_tracker($uid)
  {
	if ($uid != $_SESSION['user']['uid'])
	{
		put_user_view($uid, $_SESSION['user']['uid']);
	}
  }
  
  function logic_get_user_tracker($uid)
  {
	$uv = get_user_view($uid,5);
	$data = array();
	foreach($uv as $k=>$v)
	{
		$data[] = logic_get_user_by_id($v['viewer_uid']);
	}
	return $data;
  }
  
	
  /**
   *	look up district
   *	@param int $uid user id
   *	@return int district id
   */
	function logic_get_district_for_user($uid)
	{
		return get_district_for_user($uid);
	}

	function logic_get_club_names()
	{
		return fetch_club_names();
	}
	
	function logic_get_clubs($did="")
	{
    $clubs = false;
		if (!is_numeric($did) || $did=="")
		{
			$clubs =get_data("select *,TRIM(REPLACE(SUBSTRING(name,7),'-',' ')) as clubname from club order by clubname asc");;
			
			// $clubs = fetch_clubs();
		}
		else
		{
			if ($did == 0)
			{
				$clubs = fetch_clubs("where district_did={$did}");
			}
			else
			{
				$clubs = fetch_clubs("where district_did={$did}");
			}
		}
    for ($i=0;$i<sizeof($clubs);$i++)
    {
      $clubs[$i]['logo'] = logic_get_club_logo($clubs[$i]['cid']);
    }
    return $clubs;
	}

	function logic_send_mail($uid, $subj, $content)
	{
		$user = logic_get_user_by_id($uid);		
		$email = $user['private_email'];
		/*
		$nb = logic_get_national_board();
		foreach($nb as $n)
		{
			if ($n['uid'] == $uid)
			{
				$role = $n['role_short'].NB_MAIL_POSTFIX;
			}
		}*/
		save_mail($email, $subj, $content);
	}
  
  function logic_meeting_rated($mid,$uid)
  {
    $r = get_meeting_rating($mid,$uid);
    return !empty($r);
  }
  
  function logic_get_meeting_files($mid)
  {
    return get_meeting_files($mid);
  }
  
  function logic_import_from_old_site($old_filepath, $new_filepath)
  {
    $conn = ftp_connect(OLD_FTP_SERVER);
    ftp_login($conn, OLD_FTP_USER, OLD_FTP_PASSWORD);       
    if (ftp_get($conn, $new_filepath, $old_filepath, FTP_BINARY))
    {
      chmod($new_filepath,0777);
      ftp_close($conn);
      return true;
    }
    else
    {
      ftp_close($conn);
      return false;
    }
  }


  function logic_migrate_meeting_file($mfid, $f)
  {
    $old_filepath = $f['filepath'];
    $new_folder = MEETING_FILES_UPLOAD_PATH."{$f['mid']}/";
    $new_filepath = $new_folder.$f['filename'];
    
    if (!is_dir($new_folder))
    {
      mkdir($new_folder);
      chmod($new_folder, 0777);
    }
     
    logic_import_from_old_site($old_filepath, $new_filepath);
    update_meeting_file($mfid, $new_filepath, $f['filename']);
    return $new_filepath;      
  }
    
  function logic_get_meeting_file($mfid)
  {
    $f = get_meeting_file($mfid);
    $baseurl = $f['filepath'];
		if (strpos($baseurl, "sites/rtd")!==false)
		{
      return logic_migrate_meeting_file($mfid,$f);
		}
		else
		{
			return $baseurl;
		}
  }
  
  function logic_put_meeting_rating($mid,$uid,$r)
  {     
    if (!logic_meeting_rated($mid,$uid))
    {
      if ($r>10) $r = 10;
      if ($r<0) $r = 0;
      if (!is_numeric($r)) $r = 0;
      put_meeting_rating($mid,$uid,$r);
    }
  }
  
  function logic_get_meeting_rating($mid)
  {
    $r = get_meeting_rating($mid);
    $sum = 0;
    for($i=0;$i<sizeof($r);$i++)
    {
      $sum += $r[$i]['rating'];
    }
    $rating = sizeof($r)?floor($sum/sizeof($r)):"0";
    return array('rating'=>$rating, 'count'=>sizeof($r));
  }
  
  function logic_build_ics($meeting)
  {
	$c = logic_get_club($meeting['cid']);
			$start = strtotime($meeting['start_time']);
			$end = strtotime($meeting['end_time']);
			$title = /*utf8_decode*/($meeting['title']." (".$c['name'].")");
			$description = /*utf8_decode*/($meeting['description']);
						
			return( 

"BEGIN:VCALENDAR
X-WR-CALNAME:".term('organisation_fullname')." Calendar
PRODID:-//".term('organisation_fullname')."//".term('organisation_shortname')." Calendar//EN
VERSION:2.0
CALSCALE:GREGORIAN
METHOD:PUBLISH
BEGIN:VEVENT
DTSTART:".date('Ymd',$start)."T".date('Hi',$start)."00
DTEND:".date('Ymd',$end)."T".date('Hi',$end)."00
SUMMARY:{$title}
DESCRIPTION:{$description}
UID:RTD-{$meeting['mid']}
CLASS:PUBLIC
END:VEVENT
END:VCALENDAR"
);
  }

	function logic_get_duty_text($meeting,$duty)
	{
		$d = $meeting;
		if ($duty=='duty_3min_uid')
		{
			$text = term('duty_3min_uid');
		}
		else if ($duty=='duty_letters1_uid')
		{
			$text = term('duty_letters1_uid');
		}
		else if ($duty=='duty_letters2_uid')
		{
			$text = term('duty_letters2_uid');
		}
		else if ($duty=='duty_meeting_responsible_uid')
		{
			$text = term('duty_meeting_responsible_uid');
		}
		else if ($duty=='duty_ext1_uid')
		{
			$text=$d['duty_ext1_text'];
		}
		else if ($duty=='duty_ext2_uid')
		{
			$text=$d['duty_ext2_text'];
		}
		else if ($duty=='duty_ext3_uid')
		{
			$text=$d['duty_ext3_text'];
		}
		else if ($duty=='duty_ext4_uid')
		{
			$text=$d['duty_ext4_text'];
		}
		else if ($duty=='duty_ext5_uid')
		{
			$text=$d['duty_ext5_text'];
		}
		return $text;
	}
  
	function logic_send_invitations($mid)
	{
		$meeting = logic_get_meeting($mid);
		$duties = logic_get_meeting_duties($mid);
		$members = fetch_meeting_attendance($mid);
				
		clear_minutes_collection_cache($meeting['cid'], $mid);
		clear_minutes_collection_cache($meeting['cid'], $mid."992");
		

		$ics_fn = "{$mid}.ics";
		$ics_data = logic_build_ics($meeting);
		$attachment_id = put_mail_attachment($ics_fn);
		file_put_contents(MAIL_ATTACHMENT_UPLOAD_PATH.$attachment_id, $ics_data);

		$to = "";
	
		$duty_text = "";
		foreach($duties as $d => $v)
		{
			$duty_text .= "- ".logic_get_duty_text($meeting, $d).': '.$v['profile_firstname'].' '.$v['profile_lastname']."\n";		
		}
		$meeting['duty_text'] = $duty_text;
	
	
		
		for($i=0;$i<sizeof($members);$i++)
		{
			$to .= $members[$i]['private_email']."; ";
		}
		
		$meeting['meeting_description'] = htmlentities(strip_tags($meeting['meeting_description']));

		$content = term_unwrap('mail_invitation',$meeting);
		$title = term_unwrap('mail_invitation_subject',$meeting);

		
		logic_save_mail($to, $title, $content, $attachment_id, $_SESSION['user']['uid']);
		
	}
	
	function logic_meeting_with_no_minutes($cid)
	{
		$s = logic_get_club_year_start();
		return fetch_meetings("where (minutes_date='' or minutes_date='0000-00-00' or minutes_date is null) and M.cid=$cid and start_time<now() and start_time>'$s'", "9999");
	}

	function logic_get_member_jubilees($modify=0)
	{
		$years = explode(",",MEMBER_JUBILEES_YEAR);
		$data = array();
		foreach($years as $year)
		{
			$s = logic_get_club_year_start(-$year+$modify);
			$e = logic_get_club_year_end(-$year+$modify);
			$data[$year] = get_jubilees($s,$e);
		}
		return $data;
	}

	function logic_get_club_jubilees($modify=0)
	{
		$years = explode(",",CLUB_JUBILEES_YEAR);
		$data = array();
		foreach($years as $year)
		{
			$s = logic_get_club_year_start(-$year+$modify);
			$e = logic_get_club_year_end(-$year+$modify);
			$data[$year] = get_club_jubilees($s,$e);
		}
		return $data;
	}


	function logic_get_user_year_details_stats($uid)
	{
    $i=0;
		$s = logic_get_club_year_start($i);
		$e = logic_get_club_year_end($i);
    return get_meeting_attendance($uid, $s, $e);
	}
	
	function logic_get_user_stats($uid,$cid)
	{
		$data = array();
		
		for ($i=-3;$i<1;$i++)
		{
			$s = logic_get_club_year_start($i);
			$e = logic_get_club_year_end($i);

			$t = substr($s, 0, 4)."-".substr($e,0,4);



			$accept = count_meeting_attendance($uid, '1', $s, $e, $cid);
			$reject = count_meeting_attendance($uid, '0', $s, $e, $cid);
			$non_home_meetings = count_meeting_attendance($uid, '1', $s, $e, -$cid);
			$total  = count_meetings($cid, $s, $e);
			$data[$t] = array(
				'accepted' => $accept,
				'reject' => $reject,
				'total' => $total,
				'non_home_meeting' => $non_home_meetings
			);	
			if ($total ==0) $data[$t]['attendance']=0;
			else $data[$t]['attendance']= floor(100.0*($accept+$non_home_meetings)/$total);
				
 		}
		
		return $data;
	}
	
	function logic_get_news_comments($nid)
	{
		$c = get_comments($nid);
		for ($i=0;$i<sizeof($c);$i++)
		{
			$c[$i]['content'] = stripslashes(nl2br($c[$i]['content']));
			$c[$i]['user'] = logic_get_user_by_id($c[$i]['uid']);
		}
		return $c;
	}
	
	
	function logic_meeting_stats($meeting,$mid=0)
	{
		if ($mid==0) $mid=$meeting['mid'];
	
		$total_attendees = fetch_meeting_stats($mid,"and MA.accepted=1");
		$club_attendees  = $meeting['minutes_number_of_participants']==''?fetch_meeting_stats($mid,"and M.cid=U.cid and MA.accepted=1"):$meeting['minutes_number_of_participants'];
		$club_rejected   = $meeting['minutes_number_of_rejections']==''?fetch_meeting_stats($mid,"and M.cid=U.cid and MA.accepted=0"):$meeting['minutes_number_of_rejections'];
		$external =  fetch_meeting_stats($mid,"and MA.accepted=1 and U.cid!=M.cid");
		return array(
			"total" => $total_attendees,
			"accepted" => $club_attendees,
			"rejected" => $club_rejected,
			"percentage" => ($club_attendees+$club_rejected)==0?"0":ceil(100.0*($club_attendees)/($club_attendees+$club_rejected)),
			"external" => $external
		);
	}

	function logic_active_user_report()
	{
		return get_user_report();
	}

	function logic_show_attendance_form($meeting)
	{
		return (!logic_is_mummy() && (!logic_meeting_minutes_finished($meeting)));
	}

	function logic_upload_meeting_image($filestruct, $mid)
	{		
		
		
		$folder =MEETING_IMAGES_UPLOAD_PATH.$mid;
		if (!is_array($filestruct['name']))
		{
			if (!is_dir(MEETING_IMAGES_UPLOAD_PATH.$mid))
			{
				assert(mkdir($folder,0777));
			}
			$fn = $folder."/".$filestruct['name'];
			
			if (file_exists($fn))
			{
				move_uploaded_file($filestruct['tmp_name'], $fn);
			}
			else
			{
				move_uploaded_file($filestruct['tmp_name'], $fn);
				save_meeting_image($fn, $filestruct['name'], $mid);
			}
		}
		else
		{
			for($i=0;$i<sizeof($filestruct['name']);$i++)
			{
				if (!is_dir(MEETING_IMAGES_UPLOAD_PATH.$mid))
				{
					assert(mkdir($folder,0777));
				}

				$fn = $folder."/".$filestruct['name'][$i];
				
				if (file_exists($fn))
				{
					move_uploaded_file($filestruct['tmp_name'][$i], $fn);
				}
				else
				{
					move_uploaded_file($filestruct['tmp_name'][$i], $fn);
					save_meeting_image($fn, $filestruct['name'][$i], $mid);
				}
			}
		}
	}

	function logic_upload_meeting_file($filestruct, $mid)
	{
		online_log("start");
		$folder = MEETING_FILES_UPLOAD_PATH.$mid;
		if (!is_dir(MEETING_FILES_UPLOAD_PATH.$mid))
		{
			online_log("Creating folder {$folder}");
			assert(mkdir($folder,0777));
		}
		
		if (!is_array($filestruct['name']))
		{
			online_log("Upload single file");
			$fn = $folder."/".$filestruct['name'];
			
			if (file_exists($fn))
			{
				online_log("Overwrite file");
				move_uploaded_file($filestruct['tmp_name'], $fn);
			}
			else
			{
				online_log("New file");
				move_uploaded_file($filestruct['tmp_name'], $fn);
				save_meeting_file($fn, $filestruct['name'], $mid);
			}
		}
		else
		{
			$n = sizeof($filestruct['name']);
			for($i=0;$i<$n;$i++)
			{
				online_log("Uploading file {$i} of {$n}");
				$fn = $folder."/".$filestruct['name'][$i];
				
				if (file_exists($fn))
				{
					online_log("Overwrite file");
					move_uploaded_file($filestruct['tmp_name'][$i], $fn);
				}
				else
				{
					online_log("New file");
					move_uploaded_file($filestruct['tmp_name'][$i], $fn);
					save_meeting_file(addslashes($fn), addslashes($filestruct['name'][$i]), $mid);
				}
			}
		}
		online_log("Done");
		// die(get_online_log_html());
	}


	function logic_save_meeting_attendance($cid, $mid,$uid,$accept,$comment="")
	{
		//if (logic_is_admin() || (logic_is_secretary() && $cid==$_SESSION['user']['cid']))
		if ($_SESSION['user']['uid']==$uid || logic_may_edit_meeting($cid))
		{
			save_meeting_attendance($mid,$uid,$accept,$comment);
			event_meeting_attendance($mid,$cid,$uid,$comment,$accept);
		}
		else
		{
		}
	}

	function logic_user_on_leave($user)
	{
		$date = new DateTime(date("Y-m-d"));
		$date->add(new DateInterval('P6M'));
		add_role($user['uid'], USER_LEAVE_ROLE_RID, date("Y-m-d"), $date->format('Y-m-d'));
		logic_decline_future_meetings($user['uid'],$user['cid']);
		event_user_on_leave($user);
	}
	
	function logic_add_role($uid, $rid, $start, $end)
	{
		add_role($uid, $rid, $start, $end);
	}

	function logic_approve_nomination($nid)
	{
		$nomination = fetch_nomination($nid);
		add_role($nomination['uid'], $nomination['rid'], $nomination['date_start'], $nomination['date_end']);
		$nomination['approved']=1;
		update_role_nomination($nid, $nomination);
		event_nomination_accepted($nid);
	}
	
	function logic_reject_nomination($nid)
	{
		$nom = fetch_nomination($nid);
		$nom['approved'] = -1;
		update_role_nomination($nid, $nom);
		event_nomination_rejected($nid);
	}
	
	function logic_get_nominations($rid, $pending=true)
	{
		return array(
			"remove" => fetch_nominations(-$rid, $pending),
			"add" => fetch_nominations($rid, $pending)
		);
	}
	
	function logic_mummy_login($clubname, $password)
	{
		$l = mummy_login($clubname, $password);
		if ($l) 
		{
			$_SESSION['mummy'] = $l;
			if (isset($_SESSION['user'])) unset($_SESSION['user']);
		}
		return $l;
	}
	
	function logic_is_mummy()
	{
		return isset($_SESSION['mummy']);
	}

	function logic_is_club_secretary($cid)
	{
		if (isset($_SESSION['user']) && $_SESSION['user']['cid'])
		{
			return logic_is_secretary() || logic_is_chairman();
		}
		return false;
	}

	function logic_may_edit_profile($user)
	{
		if (
				$_SESSION['user']['uid']==$user['uid']
				|| logic_is_admin()
				|| (logic_is_secretary() && $user['cid']==$_SESSION['user']['cid'])
		)
		{
			return true;
		}
		else
		{
			return false;
		}
	}

  function logic_is_special_club($cid)
  {
    $c = logic_get_club($cid);
    return $c['district_did']==0;
  }
	function logic_save_meeting($meeting, $mid, $cid)
	{
		if(logic_may_edit_meeting($cid))
//		if (logic_is_admin() || (logic_is_secretary() && $cid==$_SESSION['user']['cid']))
		{
			$meeting['cid'] = $cid;
			$new_mid = save_meeting($meeting,$mid);

			// create meeting attendance rows for active members when creating meeting (used for stats)			
			if ($mid<0)
			{
				$active_members = fetch_active_club_members($cid);
				for ($i=0;$i<sizeof($active_members);$i++)
				{
					save_meeting_attendance($new_mid, $active_members[$i]['uid']);
				}
			}
			
			return $new_mid;
		}
		else
		{
			return $mid;
		}
	}

	function logic_meeting_minutes_finished(&$meeting)
	{
		if ($meeting['minutes_date']=='0000-00-00' || $meeting['minutes_date']=="")
		{
			return false;
		}
		else
		{
			return true;
		}
	}
	

	function logic_may_edit_meeting($cid=0)
	{
		if (!logic_is_member()) return false;
		
		if (logic_is_club_chairman($cid)) return true;
		
		if (logic_is_group_manager($cid)) return true;
		else if (logic_is_admin()) return true;
		else if (logic_is_secretary())
		{
			return ($cid == $_SESSION['user']['cid']);
		}
		else if (logic_is_ceremony_master())
		{
			return ($cid == $_SESSION['user']['cid']);
		}
		else return false;
	}
	                       
	function logic_fetch_specific_news($nid)
	{
		return fetch_specific_news($nid);
	}
	
  function logic_get_news($did,$count=1)
  {
  	$data = fetch_news($did,false,$count);
    return $data;
  }
  
  function logic_should_show_news()
  {
  	$data = fetch_news(0, $_SESSION['user']['last_page_view']);
  	if ($data) return $data['nid'];
  	else return false;
  }
  
  function logic_save_comment($nid,$comment,$did) 
  {
	if (is_numeric($nid) && $comment != "")
	{
		save_comment($nid,addslashes(strip_tags($comment)),$_SESSION['user']['uid']);
		event_news_comment($nid,$did);
	}
}
  
  function logic_save_news($did,$title,$content)
  {
    save_news($did,$title,$content);
  }
  
	function logic_get_latest_members()
	{
		return fetch_latest_members();
	}

	function logic_get_all_club_members($cid)
	{
		if (!is_numeric($cid)) return false;
		$members = fetch_all_club_members($cid);
		for ($i=0;$i<sizeof($members);$i++)
		{
			$roles = fetch_active_role_names($members[$i]['uid']);
			$role_data = array();
			for($j=0;$j<sizeof($roles);$j++)
			{
				$role_data[] = $roles[$j]['description'];
			}
			$members[$i]['roles'] = implode(', ', $role_data);
		}
		
		return $members;
	}
	
	function logic_send_message_to_active_club_members($club, $message)
	{
		if (logic_is_member())
		{
			$title = term_unwrap('club_message_title', $club);
			$body = term_unwrap('club_message_body', array('uid'=>$_SESSION['user']['uid'],'sender'=>$_SESSION['user']['profile_firstname'].' '.$_SESSION['user']['profile_lastname'], 'message'=>$message));
			
			$members = logic_get_active_club_members($club['cid']);
			$to = "";
			for($i=0;$i<sizeof($members);$i++)
			{
				$to .= $members[$i]['private_email']."; ";
			}
			logic_save_mail($to, $title, $body, 0, $_SESSION['user']['uid']);
		}
	}
	
	function logic_get_active_club_members($cid)
	{
		if (!is_numeric($cid)) return false;

		$members = fetch_active_club_members($cid);
		for ($i=0;$i<sizeof($members);$i++)
		{
			$roles = fetch_active_role_names($members[$i]['uid']);
			$role_data = array();
			for($j=0;$j<sizeof($roles);$j++)
			{
				$role_data[] = $roles[$j]['description'];
			}
			$members[$i]['roles'] = implode(', ', $role_data);
		}
		
		return $members;
	}
	
	function logic_get_club_year_start($year_modify=0)
	{
		if (date('m')<7)
		{
			return (date("Y")-1+$year_modify).'-07-01';
		}
		else
		{
			return (date("Y")+$year_modify).'-07-01';
		}
	}

	
	function logic_get_club_year_end($year_modify=0)
	{
		if (date('m')<7)
		{
			return (date("Y")+$year_modify).'-06-30';
		}
		else
		{
			return (date("Y")+1+$year_modify).'-06-30';
		}
	}
	
	function logic_get_online_users_count()
	{
		return fetch_member_count("and last_page_view>DATE_SUB(now(),interval 10 minute)");	
	}	


	function logic_get_detailed_stats()
	{
		$ys = logic_get_club_year_start();
		$ye = logic_get_club_year_end();
		$ys1= logic_get_club_year_start(1);
    
		$num_clubs = sizeof(fetch_clubs("where district_did!=0"));
		$num_members = fetch_num_active_roles("and R.rid=".MEMBER_ROLE_RID);
		return array(
			"member_count_today" => $num_members,
			
			
			"honour_count_today" => fetch_num_active_roles("and R.rid=".HONORARY_ROLE_RID),
			
			"member_count_year_start" => fetch_num_active_roles("and R.rid=".MEMBER_ROLE_RID,"R.start_date<'$ys' and R.end_date>'$ys'"),
			
			"member_count_year_end" => fetch_num_active_roles("and R.rid=".MEMBER_ROLE_RID,"R.start_date<'$ye' and R.end_date>'$ys1'"),
			"new_member_count" => fetch_num_active_roles("and R.rid=".MEMBER_ROLE_RID,"R.start_date>'$ys'"),
			"normal_exit_count" => fetch_num_active_roles("and R.rid=".MEMBER_ROLE_RID,"R.end_date='$ye'"),
			"exit_count_today" => fetch_member_count("and profile_ended<now() and profile_ended>'$ys'"),
			"club_count_today" => $num_clubs,
			"club_avg_member_count" => round($num_members/$num_clubs,2),
			"member_avg_age" => round(fetch_avg_member_age("and profile_ended>now()"),2),
			"new_member_avg_age" => round(fetch_avg_member_age("and profile_ended>now() and profile_started>'$ys'"),2)
		);
	}
	
	function logic_new_updates($ts)
	{
		$uid = $_SESSION['user']['uid'];
 
		$attendance = get_data("select 
											MA.accepted,MA.mid,MA.comment,MA.uid,MA.response_date as ts,M.title, CONCAT(U.profile_firstname,' ',U.profile_lastname) as who from meeting_attendance MA 
											inner join meeting M on M.mid=MA.mid
											inner join user U on U.uid=MA.uid
											order by response_date desc limit 10");

		$articles = get_data("select title, aid as id, last_update as ts from article where last_update>'$ts' order by last_update desc limit 5");
		$meetings = get_data("select concat_ws(', ',M.title,C.name) as title, M.mid as id, M.start_time, date_format(M.start_time, '%e. %b (%H:%i)') as ts from meeting M inner join club C on M.cid=C.cid where start_time<DATE_ADD(NOW(),INTERVAL 6 HOUR) order by start_time desc limit 15");
		$news = get_data("select title, nid as id, posted, date_format(posted, '%e. %b (%H:%i)') as ts from news where did=0 order by posted desc limit 5");
		$news_comment = get_data("select NC.nid as id ,NC.posted, date_format(NC.posted, '%e. %b (%H:%i)') as ts,N.title as title from news_comment NC inner join news N on N.nid=NC.nid where N.did=0 order by NC.posted desc limit 5");
		$tabler_service = get_data("select tsid as id, headline as title, posted, date_format(posted, '%e. %b (%H:%i)') as ts from tabler_service_item where posted>'$ts' order by posted desc limit 5");
		
		$users = get_data("select concat_ws(' ',profile_firstname, profile_lastname) as title, uid as id, last_page_view, date_format(last_page_view, '%e. %b (%H:%i)') as ts, last_page_title as url_title, last_page_url as url_link from user where last_page_view>DATE_SUB(now(),interval 15 minute) order by ts desc limit 15");
		
		$birthday = get_user_birthday(false,true);


		return array(
			"aid" => $articles,
			"mid" => $meetings,
			"news" => $news,
			"news_comment" => $news_comment,
			"ts" => $tabler_service,
			"uid" => $users,
			"birthday" => $birthday,
			"attendance" => $attendance
		);
	}
	

	function logic_get_stats()
	{
		$ys = logic_get_club_year_start();
		$ye = logic_get_club_year_end();
		return array(
			//"allmembers" => fetch_member_count("and profile_ended>now()")/*fetch_num_active_roles("and rid=".MINIMUM_ROLE_ALLOWED_RID)*/,
			"allmembers" => fetch_num_active_roles("and R.rid=".MEMBER_ROLE_RID),
			"honorary" => fetch_num_active_roles("and R.rid=".HONORARY_ROLE_RID),
			//"newmembers" => fetch_member_count("and profile_started>'$ys'"),
			"newmembers" => fetch_num_active_roles("and R.rid=".MEMBER_ROLE_RID." and R.start_date>='$ys'"),
			"leavingmembers" => fetch_num_active_roles("and R.rid=".MEMBER_ROLE_RID." and (R.end_date>'$ys' and R.end_date='$ye')"),
//			"leavingmembers" => fetch_member_count("and (profile_ended='$ye')"),
			"avgage" => round(fetch_avg_member_age("and profile_ended>now()"),2),
			"online" => fetch_member_count("and last_page_view>DATE_SUB(now(),interval 10 minute)")
		);
	}
	
	function logic_get_club_stats($cid)
	{
		if (!is_numeric($cid)) return false;
		$data = array();
		$ys = logic_get_club_year_start();
		$ye = logic_get_club_year_end();
		$member = MEMBER_ROLE_RID;
		for ($i=0;$i<6;$i++)
		{
		
			$ys = logic_get_club_year_start($i);
			$ye = logic_get_club_year_end($i);
			
			$ys1 = logic_get_club_year_start($i+1);
			$ye1 = logic_get_club_year_end($i+1);
			
			$ys0 = logic_get_club_year_start($i-1);
			$ye0 = logic_get_club_year_end($i-1);
			
			$key = substr($ys, 0, 4)."-".substr($ye,0,4);
			$data[$key] = array(
				"new" => fetch_num_active_roles("and U.cid={$cid} and R.rid={$member} and R.start_date<'$ys1' and R.start_date>'$ye0'"),
				"newmembers" => fetch_num_active_roles("and U.cid={$cid} and R.rid={$member} and R.start_date<'$ys1' and R.start_date>'$ye0'"),
				"exit" => fetch_num_active_roles("and U.cid={$cid} and R.rid={$member} and R.end_date<'$ys1' and R.end_date>'$ye0'"),
				"start" => fetch_num_active_roles("and U.cid={$cid} and R.rid={$member} and R.end_date>'$ys' and R.start_date<'$ys'"),
				"end" => fetch_num_active_roles("and U.cid={$cid} and R.rid={$member} and R.end_date>'$ye' and R.start_date<'$ye'")
			);


		}
		return $data;
	}
	
	function logic_delete_meeting_image($img)
	{
		$i = fetch_meeting_image_data($img);
		@unlink($_SERVER['DOCUMENT_ROOT'].'/'.$i['filepath']);
		delete_meeting_image($img);
	}

	function logic_delete_meeting_file($mfid)
	{
		if (!is_numeric($mfid)) return false;
		$f = get_meeting_file($mfid);;
		@unlink($_SERVER['DOCUMENT_ROOT'].'/'.$i['filepath']);
		delete_meeting_file($mfid);
	}

  function logic_get_club_logo($cid)
  {
		if (!is_numeric($cid)) return false;
    if (file_exists(CLUB_LOGO_PATH.$cid.".jpg")) $c="{$cid}.jpg"; 
    else if (file_exists(CLUB_LOGO_PATH.$cid.".gif")) $c="{$cid}.gif"; 
    else if (file_exists(CLUB_LOGO_PATH.$cid.".png")) $c="{$cid}.png";
    else $c="0.jpg";
    return $c; 
  }
	
	function logic_get_club($cid)
	{
		if (!is_numeric($cid)) return false;
		$c =fetch_club($cid);
		$c['logo']=logic_get_club_logo($cid);
		return $c; 
	}

	function logic_get_club_board_period($cid,$year=0)
	{
		if (!is_numeric($cid)) return false;
		$ys = logic_get_club_year_start($year);
		$ye = logic_get_club_year_end($year);
		return fetch_club_board($cid,$ys,$ye);
	}

	
	function logic_get_club_board($cid)
	{
		if (!is_numeric($cid)) return false;
		return fetch_club_board($cid);
	}
	
	function logic_get_club_board_roles()
	{
		return fetch_system_roles("where shortname in ".CLUB_BOARD_ROLES);
	}
	

	function logic_get_meeting_duties($mid)
	{
		if (is_numeric($mid))
		{
			$meeting = fetch_meeting($mid);
			$duties = array();
			foreach ($meeting as $key => $value)
			{
				if (strstr($key, "duty")!==false && $value!=0)
				{
			if (is_numeric($value))	$duties[$key] = fetch_user($value);
				}
			}
			return $duties;
		}
	}

	function logic_delete_meeting($mid)
	{
		if (is_numeric($mid))
		{
			$m = logic_get_meeting($mid);
			if (logic_is_admin() || logic_is_club_secretary($m['cid']))
			{
				delete_meeting($mid);
				delete_meeting_attendance($mid);
			}
		}
	}

	function logic_get_meeting($mid)
	{
		if (!is_numeric($mid)) return false;
		if ($mid<0)
		{
			$meeting = fetch_meetings("", 1);
			$res = array();
			foreach($meeting[0] as $key => $value)
			{
				$res[$key] = "";
			}
			$res['meeting_description'] =  '';
			return $res;
		}
		else
		{
			$meeting = fetch_meeting($mid);
			if ($meeting)	
      {
        $meeting['images'] = fetch_images_for_meeting($mid);
        $meeting['files'] = logic_get_meeting_files($mid);
        
	  		$stats = logic_meeting_stats(false,$mid);
				$meeting['minutes_number_of_participants'] = $stats['accepted'];
				$meeting['minutes_number_of_rejections'] = $stats['rejected'];
				$meeting['minutes_percentage'] = $stats['percentage'];        
      }
			return $meeting;
		}
	}
  
  function logic_migrate_meeting_image($data)
  {
  
    $old_filepath = $data['filepath'];
    $new_folder = MEETING_FILES_UPLOAD_PATH."{$data['mid']}/";
    $new_filepath = $new_folder.$data['filename'];
    
    if (!is_dir($new_folder))
    {
      mkdir($new_folder);
      chmod($new_folder, 0777);
    }
     
    logic_import_from_old_site($old_filepath, $new_filepath);
    update_meeting_image($data['miid'], $new_filepath, $data['filename']);
    return $new_filepath;      
  }
	
	function logic_get_meeting_image($miid)
	{
		if (is_numeric($miid))
		{
			$baseurl = fetch_meeting_image($miid);
			if (strpos($baseurl, "sites/rtd")!==false)
			{
				return logic_migrate_meeting_image(fetch_meeting_image_data($miid));
			}
			else
			{
				return $baseurl;
			}
		}
	}
	
  
  function logic_get_latest_minutes()
  {
    if (!logic_is_member())
    {
      return fetch_meetings("where minutes_date!='0000-00-00' and start_time<now() order by start_time desc", 3); 
    }
    else
    {
      $cid = $_SESSION['user']['cid'];
      return fetch_meetings("where minutes_date!='0000-00-00' and start_time<now() and C.cid=$cid order by start_time desc", 3);
    }
  }

	function logic_get_club_meetings($cid)
	{
		if (is_numeric($cid))
		{
			$ys = logic_get_club_year_start();
			$ye = logic_get_club_year_end();
			return fetch_meetings("where start_time>'$ys' and start_time<'$ye' and C.cid=$cid");
		}
	}

  function logic_get_latest_meetings()
  {
    if (!logic_is_member())
    {
      return fetch_meetings("where start_time>now() order by start_time asc", 3); 
    }
    else
    {
      $cid = $_SESSION['user']['cid'];
      return fetch_meetings("where start_time>now() and C.cid=$cid order by start_time asc", 3);
    }
  }


	function logic_get_calendar_meetings()
	{
		$districts = fetch_country();
		$districts[] = array("name" => "RTI", "did" => "0");
		$data = array();
		foreach ($districts as $d)
		{
			$data[$d['name']] = fetch_meetings("where start_time>now() and C.district_did={$d['did']} order by start_time asc", 200);
		}
		return $data;
	}
	function logic_get_calendar_meetings_ics()
	{
			$meeting_data = logic_get_calendar_meetings();
			

			$ics = 
"BEGIN:VCALENDAR
X-WR-CALNAME: ".MASS_MAILER_REPLY_WHO." Calendar
PRODID:-//".MASS_MAILER_REPLY_WHO."//".MASS_MAILER_REPLY_WHO." Calendar//EN
VERSION:2.0
CALSCALE:GREGORIAN
METHOD:PUBLISH
";
// die("<pre>".print_r($meeting_data,true));
	
		foreach ($meeting_data as $d => $meetings)
		{

			for ($i=0;$i<sizeof($meetings);$i++)
			{
				$meeting = $meetings[$i];
				$start = strtotime($meeting['start_time']);
				$end = strtotime($meeting['end_time']);
				$club = $meeting['name'];
				$title = ($club).": ".($meeting['title']);


				$description = ($d);
				$meeting['location'] = ($meeting['location']);

				$ics .=
"BEGIN:VEVENT\r
DTSTART:".date('Ymd',$start)."T".date('Hi',$start)."00\r
DTEND:".date('Ymd',$end)."T".date('Hi',$end)."00\r
SUMMARY:{$title}\r
LOCATION:{$meeting['location']}\r
DESCRIPTION:{$description}\r
UID:RTD-{$meeting['mid']}\r
CLASS:PUBLIC\r
END:VEVENT\r
";			
			}
		}

$ics .= 
"END:VCALENDAR
";

	return $ics;
	}
  
	function logic_get_country($did,$meeting_count=10)
	{		
		if ($did=="" || !is_numeric($did))
		{
			$data = array(
			"districts" => fetch_country(),
			"meetings" => fetch_meetings("where start_time>now() order by start_time asc", $meeting_count),
			"minutes" =>  fetch_meetings("where minutes_date!='0000-00-00' and start_time<now() order by start_time desc", 50),
			);
		}
		else
		{
			if ($did==0) $did = get_district_for_user($_SESSION['user']['uid']);
			$data = array(
			"districts" => fetch_country(),
			"meetings" => fetch_meetings("where start_time>now() and C.district_did=$did order by start_time asc", 10),
			"minutes" =>  fetch_meetings("where minutes_date!='0000-00-00' and start_time<now() and C.district_did=$did order by start_time desc", 10),
			);
		}
		
		for($i=0;$i<sizeof($data['meetings']);$i++)
		{
			$data['meetings'][$i]['start_time'] = logic_date_fix($data['meetings'][$i]['start_time']);
			$data['meetings'][$i]['end_time'] = logic_date_fix($data['meetings'][$i]['end_time']);
			$data['meetings'][$i]['images'] = fetch_images_for_meeting($data['meetings'][$i]['mid']);
		}

		for($i=0;$i<sizeof($data['minutes']);$i++)
		{
			$data['minutes'][$i]['start_time'] = logic_date_fix($data['minutes'][$i]['start_time']);
			$data['minutes'][$i]['end_time'] = logic_date_fix($data['minutes'][$i]['end_time']);
		}
		
		return $data;
	}
	
	function logic_get_article($aid)
	{
		$article = false;
		if ($aid==-1)
		{
			if (logic_is_member())
			{
				$article = fetch_article(LANDING_PAGE_PRIVATE);
			}
			else
			{
				$article = fetch_article(LANDING_PAGE_PUBLIC);
			}
		}
		else
		{
			$article = fetch_article($aid);
		}
		
		if ($article !== false)
		{
			$s = $_SERVER['SERVER_NAME'];
			$c = $article['content'];
			$c = str_ireplace("http://{$s}/", "/", $c);
			$c = str_ireplace("https://{$s}/", "/", $c);
			$c = str_ireplace("https://www.{$s}/", "/", $c);
			$c = str_ireplace("http://www.{$s}/", "/", $c);
			$article['content'] = $c;
		}
		
		
		return $article;
	}
	
	function logic_get_articles($parent_id=-1)
	{
		$articles = fetch_articles($parent_id);
		for ($i=0;$i<sizeof($articles);$i++)
		{
			$articles[$i]['children'] = logic_get_articles($articles[$i]['aid']);			
		}
		return $articles;
	}
	
	function logic_get_all_club_chairmen($future=false)
	{
		return fetch_members_by_roles("('".CHAIRMAN_SHORT_NAME."')",$future);
	}
	
	function get_district_chairman_mail_from_club($cid)
	{
		$did = get_district_for_club($cid);
		$dname = logic_get_district_name($did);
		$ddata = explode(" ", $dname);
		$dnum = $ddata[1];
		$sender_mail = $n['role_short'].$dnum.NB_MAIL_POSTFIX;
		return $sender_mail;
	}
	
	function logic_get_national_board()
	{
		$data = fetch_members_by_roles(NATIONAL_BOARD_ROLES);
		$new_data = array();
		for ($i=0;$i<sizeof($data);$i++)
		{
			$key = $data[$i]['role_short'];
			if ($key == DISTRICT_CHAIRMAN_SHORT)
			{
				$key = DISTRICT_CHAIRMAN_SHORT.substr($data[$i]['district'], strlen($data[$i]['district'])-1,1);
			}
			$new_data[$key] = $data[$i];
		}
		return $new_data;
	}
	
	function logic_is_member()
	{
		return isset($_SESSION['user']) && isset($_SESSION['user']['uid']);
	}

	function logic_is_national_board_member()
	{
		if (isset($_SESSION['user']))
		{
			//if (!isset($_SESSION['user']['national_board_member']))
			{
				$board = fetch_national_board();
				$_SESSION['user']['national_board_member'] = false;
				for ($i=0;$i<sizeof($board);$i++)
				{
					if ($_SESSION['user']['uid']==$board[$i]['uid'])
					{
						$_SESSION['user']['national_board_member'] = logic_get_role_name($board[$i]['rid']);
					}
				}
			}
			return $_SESSION['user']['national_board_member'];
		}
		return false;
	}	

	
	function logic_is_chairman()
	{
		if (isset($_SESSION['user']) && isset($_SESSION['user']['active_roles']))
		{
			foreach ($_SESSION['user']['active_roles'] as $key => $data)
			{
				if ($data['rid'] == CHAIRMAN_ROLE_RID) return true;
				if (defined('VICE_CHAIRMAN_AND_CHAIRMAN_SHARE_RESPONSIBILITY'))
				{
					if ($data['rid'] == VICE_CHAIRMAN_RID) return true;
				}
			}
		}
		return false;
	}	
	
	function logic_is_club_chairman($cid)
	{
		return (logic_is_chairman() && $_SESSION['user']['cid']==$cid);
	}

	function logic_is_group_manager($cid)
	{
		if (!defined('CLUB_GROUP_MANAGER_RID')) return false;
		if (isset($_SESSION['user']))
		{
			if ($_SESSION['user']['cid']!=$cid) return false;
			
			foreach ($_SESSION['user']['active_roles'] as $key => $data)
			{
				if ($data['rid'] == CLUB_GROUP_MANAGER_RID) return true;
			}
		}
		else
		{
			return false;
		}


		
	}

	function logic_is_ceremony_master()
	{
		if (!defined('CEREMONY_ROLE_RID')) return false;
		if (logic_is_admin()) return true;
		if (isset($_SESSION['user']))
		{
			foreach ($_SESSION['user']['active_roles'] as $key => $data)
			{
				if ($data['rid'] == CEREMONY_ROLE_RID) return true;
			}
		}
		return false;
	}
	
	function logic_is_secretary()
	{
		if (logic_is_admin()) return true;
		if (logic_is_chairman()) return true;
		if (isset($_SESSION['user'])&& isset($_SESSION['user']['active_roles']))
		{
			foreach ($_SESSION['user']['active_roles'] as $key => $data)
			{
				if ($data['rid'] == SECRETARY_ROLE_RID) return true;
			}
		}
		return false;
	}	
	
	
	function logic_is_admin()
	{
		if (isset($_SESSION['user']) && isset($_SESSION['user']['active_roles']))
		{
			foreach ($_SESSION['user']['active_roles'] as $key => $data)
			{
				if ($data['rid'] == ADMIN_ROLE_RID) 
				{
					return true;
				}
			}
		}
		return false;
	}	
	
	function logic_date_fix($d)
	{
		$ts = strtotime($d);
		return strftime("%e. %B, %Y", $ts);
	}
	
	function logic_fetch_future_meetings_for_club($cid,$order="asc",$limit=10,$fix=true)
	{
		if (!is_numeric($cid)) return false;
		$meetings = fetch_meetings_for_club($cid,  "and end_time>now()",$order,$limit);
		for($i=0;$i<sizeof($meetings);$i++)
		{
			if ($fix)
			{
				$meetings[$i]['start_time'] = logic_date_fix($meetings[$i]['start_time']);
				$meetings[$i]['end_time'] = logic_date_fix($meetings[$i]['end_time']);
				$meetings[$i]['images'] = fetch_images_for_meeting($meetings[$i]['mid']);
			}
		}
		return $meetings;
	}
	
	function logic_fetch_minutes_this_year($cid)
	{
		if (!is_numeric($cid)) return false;
		$ys = logic_get_club_year_start();
		$ye = logic_get_club_year_end();
		$meetings = fetch_meetings_for_club($cid,  "and (minutes_date is not null and minutes_date!='0000-00-00') and start_time>'$ys' and start_time<'$ye'","desc",999);
		for($i=0;$i<sizeof($meetings);$i++)
		{
			$meetings[$i]['start_time'] = logic_date_fix($meetings[$i]['start_time']);
			$meetings[$i]['end_time'] = logic_date_fix($meetings[$i]['end_time']);
			$meetings[$i]['images'] = fetch_images_for_meeting($meetings[$i]['mid']);
		}

		return $meetings;
	}
	
	function logic_fetch_minutes($cid,$order="asc",$limit=10)
	{
		if (!is_numeric($cid)) return false;
		$meetings = fetch_meetings_for_club($cid,  "and (minutes_date is not null and minutes_date!='0000-00-00')",$order,$limit);
		for($i=0;$i<sizeof($meetings);$i++)
		{
			$meetings[$i]['start_time'] = logic_date_fix($meetings[$i]['start_time']);
			$meetings[$i]['end_time'] = logic_date_fix($meetings[$i]['end_time']);
			$meetings[$i]['images'] = fetch_images_for_meeting($meetings[$i]['mid']);
		}

		return $meetings;
	}

	function logic_get_user_by_id($uid)
	{
		$user = fetch_user($uid);
		return $user;
	}
	
	function logic_get_user_by_username($username)
	{
		$user = fetch_user_by_username($username);
		if (!$user) $user = fetch_user_by_private_email($username);
		if (!$user) $user = fetch_user_by_company_email($username);
		return $user;
	}
  
  function logic_latest_users()
  {
    return get_latest_users();
  }

  function logic_log($section, $text)
  {
	if (isset($_SESSION['user']))
	{
		$text = "{$text} CurrentUID: {$_SESSION['user']['uid']}";
	}
	store_log(isset($_SERVER['REMOTE_ADDR'])?$_SERVER['REMOTE_ADDR']:"localhost", $section, addslashes($text));
  }
  
	function logic_has_valid_exit_date($date)
	{
		// date that expires June 30th  <any year>
		$t = strtotime($date);
		if (date("n",$t)==6 && date("j",$t)==30)
		{
			return true;
		}		
		else
		{
			return false;
		}
	}
  
	function logic_login($username, $password, $server_login=false)
	{
		if (!$server_login)
		{
			if ($username=="" || $password=="" || strpos($username,"'")!==false || strpos($password,"'")!==false) return false;
		}
		
		$user = fetch_user_by_username($username);
		if (!$user) 
		{
			$user = fetch_user_by_private_email($username);
		}
		if (!$user) 
		{
			$user = fetch_user_by_company_email($username);
		}
		
		

		if (!$user) return false;
		else
		{
			if (!$server_login)
			{
				$pwdenc = $user['password'] == md5($password);
				
				if ($user['password'] == md5($password))
				{
				}
				else if ($user['password'] == $password)
				{
				}
				else
				{
					logic_log(__FUNCTION__, "Login failed (wrong username/password) {$username}");
					return false;
				}
			}
		}

		// check if you are a current user
		{
			$roles = fetch_active_roles($user['uid'],true);
			$user['active_roles'] = $roles;
			
			
			foreach ($roles as $role)
			{
				if ($role['rid'] == HONORARY_RID || $role['rid']==MEMBER_ROLE_RID) 
				{
					return $user;
				}
			}
			logic_log(__FUNCTION__, "Roles UID:{$user['uid']}:\n".print_r($roles,true));
			logic_log(__FUNCTION__, "Login failed as honorary or member {$username} UID:{$user['uid']}");
		}
		
		// check if you are an extabler
		if (defined("ALLOW_EXTABLERS_TO_LOGIN") && ALLOW_EXTABLERS_TO_LOGIN && logic_has_valid_exit_date($user['profile_ended']))
		{
			return $user;
		}
		
		return false;
	}
	
  function logic_get_minutes_collection($seed,$did,$cid=0)
  {
	$cache = get_minutes_collection_cache($cid,$seed);
	if (empty($cache))
	{
		$omit = "0";
		$omitc = "0";
		
		// exclude previously used minutes
		$omit_data = get_minutes_collection_cache($cid,'',logic_get_club_year_start());
		foreach($omit_data as $d) $omit .= ",{$d['mid']}";


		$district = get_minutes_collection($cid,$seed,$did,5,$omit,$cid);
		foreach($district as $m) $omit .= ",{$m['mid']}";
		foreach($district as $m)  $omitc .= ",{$m['cid']}";
		
		$remainder = max(0,10 - sizeof($district));

		$whole_country = get_minutes_collection($cid,$seed,-1,$remainder,$omit,$omitc);
		
		foreach($whole_country as $m)
		{
			add_minutes_collection_cache($cid,$seed,$m['mid']);
		}
		foreach($district as $m)
		{
			add_minutes_collection_cache($cid,$seed,$m['mid']);
		}
//		echo "<pre>".print_r(array_merge($whole_country,$district)).$omit."</pre>";
		return array_merge($whole_country,$district);
	}
	else 
	{
		$data = array();
		
		return $cache;
	}
  }
  
  function logic_get_users_per_business($q)
  {
  	if ($q=="") return array();
  	else return get_users_per_business($q);
  }
  
  function logic_get_business_list()
  {
	if (!defined('LOAD_BUSINESS_LIST_FROM_DB') || LOAD_BUSINESS_LIST_FROM_DB)
	{
		return get_business_list();
	}
	else
	{// RTN business codes
		return array(
			"1000 INDUSTRI",
			"1001 NÆRINGSMIDDEL",
			"1002 KJEMIK./PORSEL/GLASS",
			"1003 KJEMISKE PRODUKTER",
			"1004 TEKSTIL/KONFEKSJON",
			"1005 PETROKJEMI",
			"1006 ELEKTRONIKK",
			"1007 FISK/FISKEPRODUKTER",
			"1008 TREVARE",
			"1010 MASKINER/MEK. UTSTYR",
			"1011 METALLURGISK INDUSTRI",
			"1013 TREFOREDLING",
			"1014 SKIPSBYGGING",
			"1015 OLJEINDUSTRI",
			"1016 ENTREPENØRVIRKSOMHET",
			"1017 GLASSFIBERINDUSTRI",
			"1018 GRAFISK INDUSTRI",
			"1019 ELEKTRO/AUTOMASJON",
			"1716 TURISME",
			"2000 HANDEL",
			"2001 MASKINER/UTSTYR",
			"2002 HAGEBRUK/GARTNERI",
			"2003 MOTORKJØRETØYER",
			"2004 DAGLIGVARE DETALJ",
			"2005 DAGLIGVARE ENGROS",
			"2006 BYGNINGSARTIKLER",
			"2007 FETEVARER",
			"2008 MANUFAKTUR",
			"2009 FARGEHANDEL",
			"2010 FOTO",
			"2011 GLASSVARER",
			"2012 KUNST/BRUKSKUNST",
			"2013 ELEKTRO/RADIO/TV",
			"2015 MØBLER",
			"2016 SPORTSARTIKLER",
			"2017 UR/OPTIKK",
			"2018 KONTORUTSTYR",
			"2019 MEDISINER OG MED.UTS",
			"2020 EDB/DATA",
			"2021 TEKSTIL ENGROS",
			"3000 HÅNDVERK",
			"3001 RØRLEGGER",
			"3002 BLIKKENSLAGER",
			"3003 ELEKTRISK INST./REP.",
			"3004 GULLSMED",
			"3005 OPTIKER",
			"3006 FOTOGRAF",
			"3007 GRAFISK VIRKSOMHET",
			"3008 TREARB. VIRKSOMHET",
			"3009 MEKANISK",
			"3010 MALER",
			"3011 MURER",
			"3012 GLASS",
			"3013 SNEKKER",
			"3014 BAKER",
			"3015 BYGG OG ANLEGG",
			"3019 VVS INGENIØR",
			"4000 DIV. ØKONOMISK VIRK.",
			"4001 BANK",
			"4002 FORSIKRING",
			"4003 SHIPPING",
			"4004 ØKONOMI/REGNS/REVISJ",
			"5000 OFFENTLIGE INST.",
			"5001 UTDANNELSE",
			"5002 FORSKNING",
			"5003 BRANNVESEN",
			"5004 STATSADMINISTRASJON",
			"5005 FYLKESADMINISTRASJON",
			"5006 KOMMUNAL ADMIN.",
			"5007 SYKEINSTITUSJONER",
			"5008 KRAFT/ELEKTRISITET",
			"5009 OFFENTLIG TRANSPORT",
			"5010 KIRKE",
			"5011 POLITIETAT",
			"5012 TELEKOMMUNIKASJON",
			"5013 POSTVESEN",
			"5014 MILITÆRVESEN",
			"5016 LIGNINGSVESEN",
			"5017 VEIVESEN",
			"6000 PRIMÆRNÆRING",
			"6001 JORDBRUK",
			"6002 SKOGBRUK",
			"6003 FISKE",
			"6007 GARTNERI",
			"6009 DYREOPPDRETT",
			"6010 FISKEOPPDRETT",
			"7000 SERVICEYRKER",
			"7001 KONSULENTVIRKSOMHET",
			"7002 TRANSPORT/SAMFERDSEL",
			"7003 HOTELL/RESTAURANT",
			"7004 JURIDISK VIRKSOMHET",
			"7005 LEGEVIRKSOMHET",
			"7006 TANNLEGEVIRKSOMHET",
			"7007 FYSIKALSK BEHANDLING",
			"7008 REKLAME- OG ANNONSER",
			"7009 EIENDOMSFORV./OMSETN",
			"7010 REKREASJON/UNDERHOLD",
			"7011 FRISØR/SKJØNNHETSPL",
			"7012 REISEBYRÅ",
			"7013 VASK/RENHOLD",
			"7014 VETERINÆRVIRKSOMHET",
			"7015 SKIPSHANDEL",
			"7016 REDNINGSMANN/AMBULANSE",
			"7017 HOMEOPAT/AKUPUNKTUR",
			"7018 KIROPRAKTOR",
			"7050 SYSTEMUTVIKLING/PROGRAM",
			"7051 NETTVERK/ TELEKOMMUNIKASJON",
			"7052 DATA-SOFTWARE",
			"7053 DATA-HARDWARE",
			"7054 DATA-KONSULENT",
			"7070 WEBDESIGNER",
			"7071 WEBMASTER",
			"7072 WEBMEDARBEIDER",
			"7073 INTERNETT/KONSULENT",
			"7074 MULTIMEDIA",
			"8000 FRIE YRKER",
			"8001 JOURNALIST",
			"8002 MEDIA",
			"8005 ARKITEKT",
			"8006 FOTOGRAF",
			"8007 IDRETTSUTØVER",
			"9000 DIVERSE",
			"9001 ORGANISASJONER",
			"9002 FORENINGER",
			"9003 HOBBYARTIKLER");

	}
  }
  
  function logic_update_club($cid, $data)
  {
	if (logic_may_edit_meeting($cid) && is_numeric($cid) && is_array($data))
	{
		update_club($cid, $data);
	}
  }
  
  function logic_best_club_meetings()
  {
	$data = get_best_meetings(logic_get_club_year_start(), logic_get_club_year_end());
	for ($i=0;$i<sizeof($data['best_club']);$i++)
	{
		$data['best_club'][$i]['data'] = logic_get_club($data['best_club'][$i]['cid']);
		$data['best_club'][$i]['average'] = $data['best_club'][$i]['average'];
	}
	
	for ($i=0;$i<sizeof($data['best_meeting']);$i++)
	{
		$data['best_meeting'][$i]['data'] = logic_get_meeting($data['best_meeting'][$i]['mid']);
		$data['best_meeting'][$i]['club'] = logic_get_club($data['best_meeting'][$i]['data']['cid']);
		$data['best_meeting'][$i]['average'] = $data['best_meeting'][$i]['average'];
	}
	return $data;	
  }
  
  function logic_update_tracker()
  {
  	if (isset($_SESSION['previous_url']))
  	{
  		put_tracker_url($_SERVER['REQUEST_URI'], $_SESSION['previous_url'], addslashes(get_title()));
  	}
  	$_SESSION['previous_url'] = $_SERVER['REQUEST_URI'];
  }
  
  function logic_put_other_meeting($data)
  {
    if (logic_is_member() && logic_may_edit_meeting($_SESSION['user']['cid']))
    {
		if (is_array($data) 
		&& isset($data['title']) 
		&& isset($data['description']) 
		&& isset($data['location'])
		&& isset($data['start_time'])
		&& isset($data['end_time'])
		&& $data['title']!='' 
		&& $data['description']!='')
		{
			put_other_meeting($_SESSION['user']['cid'], $data['title'], $data['description'], $data['location'], $data['start_time'], $data['end_time'] );
		}
    }
  }
  function logic_delete_other_meeting($omid)
  {
    if (is_numeric($omid) && logic_is_member() && logic_may_edit_meeting($_SESSION['user']['cid']))
    {
		delete_other_meeting($omid);
    }
  }

  
  function logic_get_other_meetings($cid)
  {
	if (is_numeric($cid)) return get_other_meetings($cid);
	else return false;
  }
  
  function logic_js_debug($what)
  {
    if (is_array($what)) $what = addslashes(json_encode($what));
    echo "<script>throw new Error('{$what}');</script>";
  }
  
  function logic_get_current_username()
  {
	if (logic_is_member())
	{
		return $_SESSION['user']['username'];
	}
	else return false;
  }
  
  
  function logic_is_honorary($uid)
  {
	$r = fetch_active_roles($uid);
	foreach($r as $k=>$v)
	{
		if ($v['rid']==HONORARY_RID) return true;
	}
	return false;
  }
  
  function logic_club_board_submission_period($m="")
  {
	if ($m=="") $m = date("n");
	return $m>=NEWBOARD_SUBMISSION_PERIOD_START && $m<=NEWBOARD_SUBMISSION_PERIOD_END;
  }
function logic_get_duties($uid)
  {
	$duty_data = fetch_future_duties($uid,3);
	$duties = array();
	
	foreach($duty_data as $d)
	{
		$text="";
		if ($d['duty_3min_uid']==$uid)
		{
			$text = term('duty_3min_uid');
		}
		else if ($d['duty_letters1_uid']==$uid)
		{
			$text = term('duty_letters1_uid');
		}
		else if ($d['duty_letters2_uid']==$uid)
		{
			$text = term('duty_letters2_uid');
		}
		else if ($d['duty_meeting_responsible_uid']==$uid)
		{
			$text = term('duty_meeting_responsible_uid');
		}
		else if ($d['duty_ext1_uid']==$uid)
		{
			$text=$d['duty_ext1_text'];
		}
		else if ($d['duty_ext2_uid']==$uid)
		{
			$text=$d['duty_ext2_text'];
		}
		else if ($d['duty_ext3_uid']==$uid)
		{
			$text=$d['duty_ext3_text'];
		}
		else if ($d['duty_ext4_uid']==$uid)
		{
			$text=$d['duty_ext4_text'];
		}
		else if ($d['duty_ext5_uid']==$uid)
		{
			$text=$d['duty_ext5_text'];
		}
		
		$duties[] = array(
			"duty" => date("j/n - ",strtotime($d['start_time'])).$text,
			"mid" => $d['mid'],
			"start_time" => $d['start_time'],
			"end_time" => $d['end_time'],
			"title" => $d['title']
		);
	}
	
	return $duties;

  }
  
  function logic_get_vcard($uid)
  {
	$p = logic_get_user_by_id($uid);
	$d = date("Ymd")."T".date("Hm")."00Z";
return utf8_decode("BEGIN:VCARD
VERSION:2.1
N:{$p['profile_lastname']};{$p['profile_firstname']}
FN:{$p['profile_firstname']} {$p['profile_lastname']}
ORG:Round Table
PHOTO;JPG:http://rtd.dk/uploads/user_image/?uid={$p['uid']}&landscape&w=50&h=84
TEL;WORK;VOICE:{$p['private_phone']}
TEL;HOME;VOICE:{$p['private_mobile']}
EMAIL;PREF;INTERNET:{$p['private_email']}
REV:{$d}
END:VCARD
 ");
 } 
 
 function logic_get_geolocation_latest()
 {
	$data = get_geolocation_latest(); 
	return $data;
 }
 
 function logic_update_geolocation($lat, $lng)
 {
	if (logic_is_member())
	{
		put_geolocation($_SESSION['user']['uid'], $lat, $lng, 'private');
	}
 }
 
 function logic_get_geodata($lat, $lng)
 {
	$online = get_data("select * from geolocation where sqrt( power({$lat}-lat, 2) + power({$lng}-lng, 2) )<0.15 and reftype='private' order by expiry_date desc limit 100");
	$work  = get_data("select * from geolocation where sqrt( power({$lat}-lat, 2) + power({$lng}-lng, 2) )<0.15 and reftype='work' limit 100");
	$home = get_data("select * from geolocation where sqrt( power({$lat}-lat, 2) + power({$lng}-lng, 2) )<0.15 and reftype='home' limit 100");
	return array_merge($online, $work, $home);
	
//	$sql = "select * from geolocation where sqrt( power({$lat}-lat, 2) + power({$lng}-lng, 2) )<0.15 limit 100";
//	return get_data($sql);
 }
 
 function logic_get_mail()
 {
	if (logic_is_member())
	{
		$sql = 
		"
			SELECT 
			M.id, A.aid, A.filename, M.mail_subject, M.mail_content, M.submit_time, U.profile_firstname as SenderFirstname, U.profile_lastname as SenderLastname, U.uid as SenderUID, (A.aid*128) as token
			FROM mass_mail M

			LEFT JOIN user U ON U.uid=M.uid
			LEFT JOIN mass_mail_attachment A on M.aid=A.aid


			WHERE M.mail_receiver LIKE '%{$_SESSION['user']['private_email']}%'
			OR M.mail_receiver LIKE '%{$_SESSION['user']['company_email']}%'

			ORDER BY M.id DESC
			LIMIT 100
		";
		
		
		return get_data($sql);
	}
 }
 
	?>