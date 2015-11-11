<?
 /**
 *	data fetching library for round table system (c) 3kings.dk 2012-2013
 *	
 *	call with datafetcher.php?test to run internal sanity check
 *		
 *	28-10-2012	rasmus@3kings.dk	draft
 *	29-10-2012	rasmus@3kings.dk	added meetings fetcher
 *	31-10-2012	rasmus@3kings.dk	added fetch_active_roles
 *	01-11-2012	rasmus@3kings.dk	added slashes to article saving
 *	06-11-2012	rasmus@3kings.dk	added mailer
 *	12-11-2012	rasmus@3kings.dk	meeting stats
 *	13-11-2012	rasmus@3kings.dk	list of clubs
 *	25-11-2012 	rasmus@3kings.dk 	time stamp updater
 *	27-11-2012	rasmus@3kings.dk	role update
 *	28-11-2012	rasmus@3kings.dk	refactoring to new get_data function
 *	29-11-2012	rasmus@3kings.dk	create_user
 *	29-11-2012	rasmus@3kings.dk	documentation added
 *	02-01-2013  rasmus@3kings.dk  minutes meeting collection
 *	03-01-2013  rasmus@3kings.dk  delete_meeting_file  
 *	08-01-2013	rasmus@3kings.dk	update_user_club($uid, $new_cid);
 *	29-01-2013	rasmus@3kings.dk	tabler service functionality
 *	14-02-2013	rasmus@3kings.dk	tracker
 *	21-02-2013	rasmus@3kings.dk	get users
 *  26-02-2013  rasmus@3kings.dk  attachment to newsletters
 *  02-03-2013  rasmus@3kings.dk  delete meeting attendance rows 
 *	26-03-2013	rasmus@3kings.dk	best club/meetings
 *	13-05-2013	rasmus@3kings.dk	added external/internal links to articles
 *	28-06-2013  rasmus@3kings.dk  added delete_role_period
 *	29-06-2013  rasmus@3kings.dk  added put_other_meeting, get_other_meetings  
 *	20-09-2013	rasmus@3kings.dk	store_log
* 21-05-2014 rasmus@3kings.dk get_district_for_club
 */

    $path = realpath('.');
	require_once $path.'/includes/mysqlconnect.php';
	require_once $path.'/includes/stacktrace.php';


	function put_user_path_tracker($uid,$uri)
	{
			fire_sql("insert into user_path_tracker (uid,uri,ts) values ('{$uid}', '{$uri}', now())");
	}

	function fetch_future_duties($uid,$limit)
	{
	if (!is_numeric($uid))
	{
		logic_log(__FUNCTION__, 'SQL Injection UID'+addslashes($uid));
		die();
	}
	$sql = "
	select * from meeting where
	end_time>now() and
	(duty_3min_uid={$uid} or
	duty_letters1_uid={$uid} or
	duty_letters2_uid={$uid} or
	duty_meeting_responsible_uid={$uid} or
	duty_ext1_uid={$uid} or
	duty_ext2_uid={$uid} or
	duty_ext3_uid={$uid} or
	duty_ext4_uid={$uid} or
	duty_ext5_uid={$uid})
	order by start_time asc limit {$limit}";
	return get_data($sql);
	}
  
	function fetch_club_names()
	{
		return get_data("select name,cid from club order by name");
	}
	
	/**
	 *	get html table from an sql query
	 *	@param string $sql sql query
	 *	@return string html table
	 */
	function get_html_table($sql,$tags_allowed=false)
	{
		$data = get_data($sql);
		if (empty($data)) return "Empty data";
		
		$html = "<table border=1>";

		$first_row = true;
		foreach($data as $row)
		{
			// print headers
			if ($first_row)
			{
				$html .= "<tr>";
				foreach($row as $key => $value)
				{
					$html .= "<th>{$key}</th>";
				}
				$html .= "</tr>";
				$first_row = false;
			}

			// print row
			$html .= "<tr>";
			foreach($row as $key => $value)
			{
				if (!$tags_allowed)	$value = strip_tags($value);
				$html .= "<td>{$value}</td>";
			}
			$html .= "</tr>";
		}
		
		$html .= "</table>";
		return $html;
	}

	
	function update_sms_balance($cid, $change)
	{
		if (!is_numeric($cid))
		{
			logic_log(__FUNCTION__, 'SQL Injection CID'+addslashes($cid));
			die();
		}

		$data = get_data("select balance from sms_account where cid='{$cid}'");
		if (empty($data))
		{
			fire_sql("insert into sms_account (cid,balance) values ('{$cid}', '{$change}')");
		}
		else
		{
			fire_sql("update sms_account set balance={$change}+balance where cid={$cid}");
		}
	}
	
	function get_sms_balance($cid)
	{
		if (!is_numeric($cid))
		{
			logic_log(__FUNCTION__, 'SQL Injection CID'+addslashes($cid));
			die();
		}
		$data = get_one_data_row("select balance from sms_account where cid={$cid}");
		if (empty($data)) return '0';
		else return $data['balance'];
	}
	
	function put_sms_history($uid, $cid, $msg)
	{
		if (!is_numeric($uid) || !is_numeric($cid))
		{
			logic_log(__FUNCTION__, 'SQL Injection UID'+addslashes($uid)+' CID'+addslashes($cid));
			die();
		}
		fire_sql("insert into sms_history (sender_uid, receiver_cid, message, ts) values ('{$uid}', '{$cid}', '{$msg}', now())");
	}
	
	function get_sms_history($cid)
	{
		if (!is_numeric($cid))
		{
			logic_log(__FUNCTION__, 'SQL Injection CID'+addslashes($cid));
			die();
		}
		return get_data("select * from sms_history where cid={$cid}");
	}
	
	
		/**
	 *	get xml from an sql query
	 *	@param string $sql sql query
	 *	@return string xml table
	 */
	function get_xml($sql)
	{
		$data = get_data($sql);
		if (empty($data)) return "Empty data";
		
		$html = "<xml>";

		foreach($data as $row)
		{
			$html .= "<row>\n";
			foreach($row as $key => $value)
			{
				$value = htmlspecialchars(strip_tags($value));
				$html .= "<{$key}>{$value}</{$key}>\n";
			}
			$html .= "</row>\n";
		}
		
		$html .= "</xml>\n";
		return $html;
	}

	
	/**
	 * 	get users by role and location
	 *	@param mixed[] $roles array of role id's
	 *	@param mixed[] $districts array of district id's
	 *	@return mixed[] data for users
	 */
	function get_users($roles, $districts)
	{
		$r = implode(",", $roles);
		$d = implode(",", $districts);
		$sql = "
			select u.uid,u.private_email from user u
			inner join club c on c.cid=u.cid
			inner join role r on r.uid=u.uid
			where 
			r.rid in ($r) 
			and r.start_date<now() 
			and r.end_date>now() 
			and c.district_did in ($d)
		";
		return get_data($sql);
	}
	
	/**
	 *	get facebook, youtube, etc. links for a meeting
	 *	@param int $mid meeting id
	 *	@return mixed[] meeting links
	 */
	function get_meeting_links($mid)
	{
		if (!is_numeric($mid))
		{
			logic_log(__FUNCTION__, 'SQL Injection MID'+addslashes($mid));
			die();
		}
		$sql = "select * from meeting_link where mid='$mid'";
		return get_data($sql);
	}
	
	/**
	 *	save facebook, youtube, etc. links for a meeting
	 * @param int $mid meeting id
	 * @param string $source fb,yt
	 * @param string $link link
	 */
	function save_meeting_link($mid, $source, $link)
	{
		if (!is_numeric($mid))
		{
			logic_log(__FUNCTION__, 'SQL Injection MID'+addslashes($mid));
			die();
		}
		
		$source = addslashes($source);
		$link = addslashes($link);
		
		$sql = "insert into meeting_link (mid, media_source, media_link) values ('{$mid}', '{$source}', '{$link}')";
		fire_sql($sql);
	}
	
	/**
	 * delete facebook, youtube, etc. links for a meeting
	 * @param int $mlid meeting link id
	 */
	function delete_meeting_link($mlid)
	{
		if (!is_numeric($mlid))
		{
			logic_log(__FUNCTION__, 'SQL Injection MLID'+addslashes($mlid));
			die();
		}
		
		$sql = "delete from meeting_link where mlid='{$mlid}'";
		fire_sql($sql);
	}
	
	
	/**
	 * 	get club's from uids
	 *	@param mixed[] $uids user id's
	 *	@return mixed[] club data
	 */
	function get_clubs_from_uids($uids)
	{
		$u = implode(",",$uids);
		return get_data("
			select distinct U.cid,C.name from user U
			inner join club C on C.cid=U.cid
			where U.uid in ($u)		
		");
	}
	

	/** 
	 * 	fetch database instance
	 * 	@return active database instance for current page view
	 */
	function get_db()
	{
		global $g_db;
		return $g_db;
	}
  
  /**
   * fire sql against the database without expecting data back
   * @param string $sql sql to fire at the database
   */      
  function fire_sql($sql)
  {
    $db = get_db();
    $db->execute($sql);
  }
	
	/**
	 *	fire sql against db connection
	 *	@return mixed[] assoc array data set of rows returned from database call
	 *	@param string $sql sql statement to execute
	 */
	function get_data($sql)
	{
		$db = get_db();
		$rs = $db->execute($sql);
		$data = array();
		while ($row = $db->fetchassoc($rs)) $data[] = $row;
		return $data;
	}
	
	/**
	 * put banner into db
	 * @param binary $image image data
	 * @param string $mimetype image mimetype
	 * @param date $startdate start date
	 * @param date $enddate end date
	 * @param int $position banner position
	 * @param string $title text description
	 * @return inserted id
	 */
	function put_banner($image, $mimetype, $startdate, $enddate, $position, $title)
	{
		$db = get_db();
		$db->execute("insert into banner (image, mimetype, startdate, enddate, position, title) values ('$image', '$mimetype', '$startdate', '$enddate', '$position', '$title')");		
		return $db->insertid();
	}


	/**
	 *	change club affiliation for user
	 *	@param int $uid user id
	 *	@param int $new_cid club id
	 */
	function update_user_club($uid, $new_cid)
	{
		if (!is_numeric($uid) || !is_numeric($new_cid))
		{
			logic_log(__FUNCTION__, 'SQL Injection UID'+addslashes($uid));
			die();
		}

		$db = get_db();
			$db->execute("update user set cid=$new_cid where uid=$uid");
	}
	
	
	/**
	 * get banners from database
	 * @param int $position banner position
	 * @param int $limit number of banners to retrieve
	 */
	function get_banners($position=-1, $limit=9999)
	{
		if (!is_numeric($position))
		{
			logic_log(__FUNCTION__, 'SQL Injection position'+addslashes($position));
			die();
		}
		if ($position<0) return get_data("select * from banner order by bid desc limit $limit");
		else return get_data("select * from banner where position=$position order by rand() limit $limit");
	}
	
	/**
	 * get banner from database
	 * @param int $bid banner id
	 */
	function get_banner($bid)
	{
		if (!is_numeric($bid))
		{
			logic_log(__FUNCTION__, 'SQL Injection BID'+addslashes($bid));
			die();
		}
		return get_one_data_row("select * from banner where bid=$bid");
	}
	
	/**
	 * update click counter for banners
	 * @param int $bid banner id
	 * @param ip $ip ip address of clicker
	 */
	function put_banner_click($bid, $ip)
	{
		if (!is_numeric($bid))
		{
			logic_log(__FUNCTION__, 'SQL Injection BID'+addslashes($bid));
			die();
		}
		
		$ip = addslashes($ip);
		
		$db = get_db();
		$db->execute("insert into banner_click (bid, clicktime, ipn) values ('$bid', now(), INET_ATON('$ip'))");
	}
  
	/**
	 * fetch news item
	 * @param int $did district id (or 0 for national or -1 for international)
	 */
  function fetch_news($did,$pv=false,$limit=1)
  {
	if (!is_numeric($did))
	{
		logic_log(__FUNCTION__, 'SQL Injection DID'+addslashes($did));
		die();
	}
	
    $sql = "";
  	if ($pv!==false)
  	{
      $sql = "select nid,title,content,posted from news where did=$did and posted>'$pv' order by posted desc limit $limit"; 
  	}
  	else
  	{
//    	$sql = "select nid,title,content,posted from news where did=$did order by posted desc limit $limit";
		$sql = "select nid,title,content,posted,(SELECT COUNT(*) FROM news_comment NC where NC.nid=N.nid) as count from news N where did={$did} order by posted desc limit {$limit}";
    }
    if ($limit==1) return get_one_data_row($sql);
    else return get_data($sql);
  }
  
	/**
	 * fetch news item
	 * @param int $nid news id
	 */
  function fetch_specific_news($nid)
  {
		if (!is_numeric($nid))
		{
			logic_log(__FUNCTION__, 'SQL Injection NID'+addslashes($nid));
			die();
		}
  	return get_one_data_row("select * from news where nid=$nid");
  }
  
  
  /**
   * save comment to news item
   * @param int $nid news id
   * @param text $comment comment
   * @param int $uid poster id
   */
  function save_comment($nid,$comment,$uid)
  {
		if (!is_numeric($nid) || !is_numeric($uid))
		{
			logic_log(__FUNCTION__, 'SQL Injection NID'+addslashes($nid)+' UID'+addslashes($uid));
			die();
		}
		
		$comment = addslashes($comment);
  	$db = get_db();
  	$db->execute("insert into news_comment (nid,content,uid,posted) values ('$nid', '$comment', '$uid', now())");
  }

	/**
	 * save news item
	 * @param int $did district id (or 0 for national or -1 for international)
	 * @param string $title news title
	 * @param string $content news content      
	 */
  function save_news($did,$title,$content)
  {
		if (!is_numeric($did))
		{
			logic_log(__FUNCTION__, 'SQL Injection DID'+addslashes($did));
			die();
		}
		
		$title = addslashes($title);
		$content = addslashes($content);
    $db = get_db();
    $db->execute("insert into news (did,title,content,posted) values ('$did','$title','$content',now())");
  }

  /**
   * get district chairman from district
   * @return mixed[] user data for district chairmain
   * @param int $did district id
   */
  function get_district_chairman_from_district($did)
  {
		if (!is_numeric($did))
		{
			logic_log(__FUNCTION__, 'SQL Injection DID'+addslashes($did));
			die();
		}
    return get_one_data_row("
      select U.uid, U.username, U.profile_firstname, U.profile_lastname, U.private_email, U.private_mobile, U.company_facebook, U.company_linkdin, U.company_twitter from user U
      inner join role R on R.uid=U.uid
      inner join club C on U.cid=C.cid
      where R.rid=14 and C.district_did=$did and R.start_date<now() and R.end_date>now()
    ");
  }           
 
	/**
	 * store log info
	 * @param
	 */
	 function store_log($remote_addr, $section, $logtext)
	 {
		fire_sql("insert into log (remote_addr, section, logtext, ts) values ('{$remote_addr}','{$section}','{$logtext}',now())");
	 }
  	
  /**
   * mummy login
   * @param string $club club name (e.g. RT111)
   * @param string $password password
   */	
   function mummy_login($club, $password)
   {
	$club = addslashes($club);
	$password = addslashes($password);
   	return get_one_data_row("select * from club where name like '%$club%' and mummy_password='$password'");
   }
   
   /**
    * return all active users and roles
   */
   function get_user_report()
   {
   	return get_data("
				select 
				U.uid as UID,U.profile_firstname as Fornavn,U.profile_lastname as Efternavn,U.profile_birthdate as Foedselsdato, U.profile_started as CharterDato,U.profile_ended as Udmeldelsesdato, U.private_address as Vej, U.private_houseno as HusNr, U.private_houseletter as Bogstav, U.private_housefloor Etage, U.private_houseplacement Side, U.private_zipno as PostNr, U.private_city as Bynavn, U.private_mobile as MobilTlf, U.private_email as Email, 
				C.name as Klub, 
				D.name as Distrikt,
				RD.shortname as Rolle
				from user U
				inner join club C on U.cid=C.cid
				inner join district D on C.district_did=D.did
				inner join role R on R.uid=U.uid
				inner join role_definition RD on RD.rid=R.rid
				where R.end_date>=date(now()) and R.start_date<=date(now())
				order by U.profile_firstname
   	");
  	}
  	
  /**
   * get club secretary
   * @return mixed[] user data secretary
   * @param int $cid club id
   */
  function get_club_secretary($cid)
  {
		if (!is_numeric($cid))
		{
			logic_log(__FUNCTION__, 'SQL Injection CID'+addslashes($cid));
			die();
		}
    return get_one_data_row("
      select U.uid, U.username, U.profile_firstname, U.profile_lastname, U.private_email from user U
      inner join role R on R.uid=U.uid
      where R.rid=".SECRETARY_ROLE_RID." and U.cid=$cid 
	  and R.start_date<=date(now()) and R.end_date>=date(now()) 
    ");
  }           

  /**
   * get club chairman
   * @return mixed[] user data chairman
   * @param int $cid club id
   */
  function get_club_chairman($cid)
  {
		if (!is_numeric($cid))
		{
			logic_log(__FUNCTION__, 'SQL Injection CID'+addslashes($cid));
			die();
		}
    return get_one_data_row("
      select U.uid, U.username, U.profile_firstname, U.profile_lastname, U.private_email from user U
      inner join role R on R.uid=U.uid
      where R.rid=".CHAIRMAN_ROLE_RID." and U.cid=$cid
	  and R.start_date<=date(now()) and R.end_date>=date(now())
    ");
  }           

	/**
	 *	get files for articles
	 *	@return mixed[] assoc array with data for files to article
	 *	@param int $aid article id
	 */
	 function get_article_files($aid, $gallery_only=false)
	 {
		if (!is_numeric($aid))
		{
			logic_log(__FUNCTION__, 'SQL Injection AID'+addslashes($aid));
			die();
		}
		if ($gallery_only) return get_data("select * from article_file where aid=$aid and show_in_gallery=1");
		else return get_data("select * from article_file where aid=$aid");
	 }
	 
	/**
	 *	get files for articles
	 *	@return mixed[] assoc array with data for files to article
	 *	@param int $afid article file id
	 */
	 function get_article_file($afid)
	 {
		if (!is_numeric($afid))
		{
			logic_log(__FUNCTION__, 'SQL Injection AFID'+addslashes($afid));
			die();
		}
		return get_one_data_row("select * from article_file where afid=$afid");
	 }
	 
	 function delete_article_file($afid)
	 {
		if (!is_numeric($afid))
		{
			logic_log(__FUNCTION__, 'SQL Injection AFID'+addslashes($afid));
			die();
		}
		$db = get_db();
		$db->execute("delete from article_file where afid='$afid'");
	 }

	 function put_article_file($aid, $mime, $filename, $show_in_gallery)
	 {
		if (!is_numeric($aid))
		{
			logic_log(__FUNCTION__, 'SQL Injection AID'+addslashes($aid));
			die();
		}
		$db = get_db();
		if ($show_in_gallery) $show_in_gallery='1';
		else $show_in_gallery ='0';
		$db->execute("insert into article_file (aid, mimetype, filename, show_in_gallery) values ('$aid', '$mime', '$filename', '$show_in_gallery')");
		return $db->insertid();
	 }
	
	/**
	 *	fire sql against db connection
	 *	@return mixed[] assoc array data set with one row
	 *	@param string $sql sql statement to execute
	 */
	function get_one_data_row($sql)
	{
		$db = get_db();
		$rs = $db->execute($sql);
		return $db->fetchassoc($rs);
	}

	/** 
	* retrieve district id for a given club
	* @param int $cid club id
	* @return int district id
	*/
	function get_district_for_club($cid)
	{
		if (!is_numeric($cid))
		{
			logic_log(__FUNCTION__, 'SQL Injection CID'+addslashes($cid));
			die();
		}
		$data = get_one_data_row("select did from club where cid={$cid}");
		return $data['did'];
	}

	/**
	 *	retrieve the district id for a user
	 *	@param	int $uid	user id
	 *	@return	 int district id
	 */
	 function get_district_for_user($uid)
	 {
	 	$data = get_one_data_row("	
	 							select D.did from district D 
								inner join club C on C.district_did=D.did
								inner join user U on C.cid=U.cid
								where U.uid=$uid
								");
			return $data['did'];
	 	}
	 
	
	/**
	 *	free text search for meetings
	 *	@return mixed[] meetings matching the query search term <mid,title,start_time>
	 *	@param string $q search query
	 */
	function fetch_search_meetings($q)
	{
		return get_data("
			select mid,title,start_time,cid from meeting
			where 
			title like '%$q%' or
			description like '%$q%'
			order by start_time desc
		");
	}

	/**
	 *	fetch nomination data from table
	 *	@return mixed[] nominations as requested <nid, uid, rid, date_start, date_end, nomination_date, nominator_uid, nominator_comment>
	 *	@param int $rid role id to query
	 *	@param bool $pending true to query nominations that are not approved - false otherwise
	 */	
	function fetch_nominations($rid, $pending)
	{
		$approved = $pending ? "0":"1";
		return get_data("
 		SELECT
			nid, U.uid, U.cid, rid, date_start, date_end, nomination_date, nominator_uid, nominator_comment, U.profile_firstname, U.profile_lastname, C.name as club, U.profile_ended, U.profile_birthdate
			FROM
			nomination N
			inner join user U on U.uid=N.uid
			inner join club C on U.cid=C.cid
			WHERE
			approved='$approved'
			AND
			rid='$rid'
			ORDER BY nomination_date		
		");
	}
	
	/**
	 *	fetch single nomination
	 *	@return mixed[] nomination
	 *	@param int $nid nomination id
	 */
	function fetch_nomination($nid)
	{
		return get_one_data_row("select * from nomination where nid=$nid");
	}
	
	/** create row in the role nomination table
	 *	@param int $uid user id
	 *	@param date $start start date of role nominiation
	 *	@param date $end end date of role nomination
	 *	@param int $rid role id
	 *	@param int $nominator_uid user id of nominator
	 *	@param date $nomination_date date for nomination
	 *	@param string $comment nomination text
	 */
	function save_role_nomination($uid, $start, $end, $rid, $nominator_uid, $nomination_date, $comment)
	{
		$db = get_db();
		$db->execute("insert into nomination (rid, uid, date_start, date_end, nomination_date, nominator_uid, nominator_comment) values ('$rid', '$uid', '$start', '$end', '$nomination_date', '$nominator_uid', '$comment')");
	}

	/** update row in the role nomination table
	 *	@param mixed[] $data nomination data
	 *	@param int $nid nomination id
	 */
	function update_role_nomination($nid, $data)
	{
		$db = get_db();
		$values = array();
		foreach($data as $key => $value)
		{
			$value = addslashes($value);
			$values[] = "$key='$value'";
		}			
		$values_sql = implode(",",$values);
		$sql = "update nomination set $values_sql where nid=$nid";
		$db->execute($sql);
	}
	
	/**
	 *	update club data
	 *	@param int $cid club id
	 *	@param mixed[] club data to update
	 */
	function update_club($cid, $data)
	{
		$db = get_db();
		$values = array();
		foreach($data as $key => $value)
		{
			$value = addslashes($value);
			$values[] = "$key='$value'";
		}			
		$values_sql = implode(",",$values);
		$sql = "update club set $values_sql where cid=$cid";
		$db->execute($sql);
	}

	
	/**
	 *	free text search for clubs
	 *	@return mixed[] clubs matching the query search term <cid,name>
	 *	@param string $q search query
	 */
	function fetch_search_clubs($q)
	{
		return get_data("
			select cid,name from club where
			name like '%$q%' or
			description like '%$q%' or
			meeting_place like '%$q%' 
			order by name asc
		");
	}
	
	/**
	 *	free text search for articles
	 *	@return mixed[] articles matching the query search term <aid,title>
	 *	@param string $q search query
	 */
	function fetch_search_articles($q)
	{
		return get_data("
			select aid,title from article where
			title like '%$q%' or
			content like '%$q%'
			order by title asc
		");
	}
	
	/**
	 *	free text search for users
	 *	@return mixed[] users matching the query search term <uid,profile_firstname,profile_lastname,club>
	 *	@param string $q search query
	 */
	function fetch_search_users($q,$old=false)
	{
		if ($old)
		{
			$sql ="
				select 
				U.uid,
				U.profile_firstname,
				U.profile_lastname,
				D.name as district, 
				C.name as club, 
				U.private_mobile as private_phone 
				from user U 
				inner join club C on U.cid=C.cid
				inner join role R on R.uid=U.uid
				inner join district D on D.did = C.district_did
				where
				R.start_date<now() and R.end_date>now() 
				and
				(profile_firstname like '%{$q}%' or
				profile_lastname like '%{$q}%' or
				private_address like '%{$q}%' or
				private_city like '%{$q}%' or
				company_position like '%{$q}%' or
				company_profile like '%{$q}%' or
				company_name like '%{$q}%' or
				private_email like '%{$q}%' or
				company_email like '%{$q}%' or
				company_business like '%{$q}%' or
				company_city like '%{$q}%' or
				private_phone like '%{$q}%' or
				company_phone like '%{$q}%' or
				private_profile like '%{$q}%' or
				C.name like '%{$q}%' or
				concat(profile_firstname, ' ', profile_lastname) like '%{$q}%')
				order by profile_firstname asc
				";
		}
		else
		{
			$sql ="
					select 
					U.uid,
					U.profile_firstname,
					U.profile_lastname,
					D.name as district, 
					C.name as club, 
					U.private_mobile as private_phone  
					from user U 
					inner join club C on U.cid=C.cid
					inner join role R on R.uid=U.uid
					inner join district D on D.did = C.district_did
					where
					R.start_date<now() and R.end_date>now() and (R.rid=".MEMBER_ROLE_RID." or R.rid=".HONORARY_ROLE_RID.") 
					and
					(profile_firstname like '%{$q}%' or
					profile_lastname like '%{$q}%' or
					private_address like '%{$q}%' or
					private_city like '%{$q}%' or
					company_position like '%{$q}%' or
					company_profile like '%{$q}%' or
					company_name like '%{$q}%' or
					private_email like '%{$q}%' or
					company_email like '%{$q}%' or
					company_business like '%{$q}%' or
					company_city like '%{$q}%' or
					private_phone like '%{$q}%' or
					company_phone like '%{$q}%' or
					private_profile like '%{$q}%' or
					C.name like '%{$q}%' or
					concat(profile_firstname, ' ', profile_lastname) like '%{$q}%')
					order by profile_firstname asc
				";				
		}
//		echo $sql;
		return get_data($sql);
	}

	/**
	 *	list the active roles within the system
	 *	@return mixed[] roles and their definition <rid,shortname>
	 *	@param string $filter sql filter (where ...)
	 */
	function fetch_system_roles($filter="")
	{
		return get_data("select rid,shortname,description from role_definition $filter");
	}

	/**
	 *	update period for assigned role for specific user with a specific start date. example usage: terminate a users role earlier than scheduled
	 *	@param int $uid user id
	 *	@param int $rid role id
	 *	@param date $startdate the role start date
	 *	@param date $new_enddate new end date for role assignment
	 */
	function update_specific_role_period($uid,$rid,$startdate,$new_enddate)
	{
		$db = get_db();
		$db->execute("update role set end_date='$new_enddate' where uid=$uid and rid=$rid and start_date='$startdate'");		
	}

	/** 
	 *	end role period
	 *	@param int $riid role identifier
	 *	@param date $end_date end date (date("Y-m-d"))
	 */	
	function end_role_period($riid, $end_date)
	{
		$db = get_db();
		$db->execute("update role set end_date='$end_date' where riid=$riid");
	}

	/** 
	 *	delete role period
	 *	@param int $riid role identifier
	 *	@param date $end_date end date (date("Y-m-d"))
	 */	
	function delete_role_period($riid)
	{
		$db = get_db();
		$db->execute("delete from role where riid='$riid'");
	}
	

	/**
	 *	insert role assignment for user
	 *	@param int $uid user id
	 *	@param int $rid role id
	 *	@param date $startdate start date of assigment
	 *	@param date $enddate end date of assignemnt
	 */
	function add_role($uid, $rid, $startdate, $enddate)
	{
		$db = get_db();
		$db->execute("insert into role (uid,rid,start_date,end_date) values ('$uid', '$rid', '$startdate', '$enddate')");
	}


	/**
	 *	count meetings
	 *	@param int $cid club id
	 
	 */
	function count_meetings($cid, $period_start, $period_end)
	{
		$db = get_db();
		$sql = "
			select count(*) from meeting where cid=$cid and start_time<'$period_end' and start_time>'$period_start'
		";
		return $db->fetchsinglevalue($db->execute($sql));
	}
	
	/**
	 * get news comments
	 * @param int $nid news id
	 * @return mixed[] news comments
	 */
	function get_comments($nid)
	{
		return get_data("select * from news_comment where nid=$nid order by posted asc");
	}


  /**
   * get meeting that a given user has accepted within a specified period
   * @param int $uid user id
   * @param date $period_start start period
   * @param date $period_end period end           
   */
  function get_meeting_attendance($uid, $period_start, $period_end)
  {
    $sql ="
    select C.cid as cid, M.mid as mid,M.title as title,C.name as club,M.start_time as start_time from meeting_attendance as MA
    inner join meeting M on M.mid=MA.mid
    inner join club C on C.cid=M.cid
    where 
    MA.uid=$uid 
    and MA.accepted=1
    and M.start_time<'$period_end'
    and M.start_time>'$period_start'
    order by M.start_time asc
    ";
    return get_data($sql);  
  }

	/**
	 *	count meeting attendance rows
	 *	@param int $uid user id
	 *	@param int $accepted 0 for not accepted, 1 for accepted
	 *	@param date $period_start search period start
	 *	@param date $period_end search period end
	 *	@param int $cid club id (negative if non-home meetings)
	 *	@return number of hits
	 */
	function count_meeting_attendance($uid, $accepted, $period_start, $period_end, $cid)
	{
		$db = get_db();
		if ($cid>0)
		{
			$sql = "
						select count(*) from meeting_attendance MA
						inner join meeting M on M.mid=MA.mid
						where 
						MA.uid=$uid
						and MA.accepted='$accepted'
						and M.start_time<'$period_end'
						and M.start_time>'$period_start'
						and M.cid=$cid
			";	
		}
		else
		{
			$cid = -$cid;
			$sql = "
						select count(*) from meeting_attendance MA
						inner join meeting M on M.mid=MA.mid
						where 
						MA.uid=$uid
						and MA.accepted='$accepted'
						and M.start_time<'$period_end'
						and M.start_time>'$period_start'
						and M.cid!=$cid
			";	
		}
			
		return $db->fetchsinglevalue($db->execute($sql));
	}
	

	/**
	 *	update or insert role assignment for user
	 *	@param int $uid user id
	 *	@param int $rid role id
	 *	@param date $startdate start date of assigment
	 *	@param date $enddate end date of assignemnt
	 */
	function update_role($uid, $rid, $startdate, $enddate)
	{
		$db = get_db();
		
		if ($db->fetchsinglevalue($db->execute("select count(*) from role where uid=$uid and rid=$rid"))>0)
		{
			$db->execute("update role set start_date='$startdate', end_date='$enddate' where uid=$uid and rid=$rid");
		}
		else
		{
			$db->execute("insert into role (uid,rid,start_date,end_date) values ('$uid', '$rid', '$startdate', '$enddate')");
		}
	}
	
	
	
	/**
	 *	update time stamp field on arbitrary table/field
	 *	@param string $table where the data should be updated
	 *	@param string $where sql where clause for updating
	 *	@param string $field to be updated with now()-value from sql
	 */
	function update_timestamp($table, $where, $field, $ts='now()')
	{
		$db = get_db();
		$db->execute("update $table set $field=$ts where $where");	
	}
	
	/**
	 *	retrieve a list of clubs matching the parameters defined in $filter
	 *	@return mixed[] clubs matching the filter <cid,name>
	 *	@param string $filter sql where clause for selecting clubs, e.g. cid>12
	 */
	
	function fetch_clubs($filter="")
	{
		return get_data("select * from club $filter order by charter_date asc");
	}
	
	/**
	 *	count the number of rows matching the filter of meeting attendance, if no filter is defined the result will be the number who has rejected/accepted the invitation.
	 *	@return int count of rows
	 *	@param int $mid id of the meeting to count attendance for
	 *	@param string $filter sql where-statement for selection (may be empty)
	 */
	function fetch_meeting_stats($mid,$filter="and MA.mid>0")
	{
		$db = get_db();
		$sql = "
						select count(*) from meeting_attendance MA
						inner join user U on U.uid=MA.uid
						inner join meeting M on M.mid=MA.mid
						where MA.mid={$mid} {$filter}
		";
		$rs = $db->execute($sql);
		return $db->fetchsinglevalue($rs);
	}
	
	/**
	 *	save mail in the cronjob mail queue
	 *	@param string $to email address to send to
	 *	@param string $subj email subject
	 *	@param string $subj email content
	 *	@param bool $slash should we add slashes or not? default = true
	 *	@param int $attachment_id id of attachment (0 if no attachment)   
	 *	@param int $uid user id of sender (0 if default robot-sender)   
	 */	
	function save_mail($to, $subj, $content, $slash=true, $attachment_id=0, $uid=0)
	{
		if ($slash)
		{
			$subj = addslashes($subj);
			$content = addslashes($content);
		}
		$db = get_db();
		$db->execute("insert into mass_mail (mail_subject, mail_content, mail_receiver, submit_time, aid, uid) values ('$subj', '$content', '$to', now(), $attachment_id, $uid)");
	}
	
	/**
	 *	fetch attendance rows for a given meeting
	 *	@return mixed[] meeting attendance as defined by database
	 *	@param string $mid meeting id
	 */
	function fetch_meeting_attendance($mid)
	{
		$sql = "select U.private_email, C.name as club_name,U.profile_firstname, U.profile_lastname, U.uid, MA.response_date, MA.comment, MA.accepted from meeting_attendance MA
						inner join user U on U.uid=MA.uid
						inner join club C on C.cid=U.cid
						where mid=$mid
						order by MA.accepted,U.profile_firstname asc
		";
		return get_data($sql);
	}
	
	
	/**
	 *	unlink image from meeting
	 *	@param string $img image id
	 */
	function delete_meeting_image($img)
	{
		$db = get_db();
		$db->execute("delete from meeting_image where miid=$img");
	}
	/**
	 *	unlink file from meeting
	 *	@param string $mfid meeting file id
	 */
	function delete_meeting_file($mfid)
	{
		$db = get_db();
		$db->execute("delete from meeting_file where mfid=$mfid");
	}

	/**
	 *	link image to meeting
	 *	@return int id of meeting image
	 *	@param string $fn filepath on server to image
	 *	@param int $mid meeting id to associate the image
	 */
	function save_meeting_image($fp, $fn, $mid)
	{
		$db = get_db();
		$sql = "insert into meeting_image (mid,filepath,filename) values('$mid', '$fp', '$fn')";
		$db->execute($sql);
		return $db->insertid();
	}

	/**
	 *	link file to meeting
	 *	@return int id of meeting image
	 *	@param string $fn filepath on server to image
	 *	@param int $mid meeting id to associate the file
	 */
	function save_meeting_file($fp, $fn, $mid)
	{
		$db = get_db();
		$sql = "insert into meeting_file (mid,filepath,filename) values('$mid', '$fp', '$fn')";
		$db->execute($sql);
		return $db->insertid();
	}
  
  /**
   *  update meeting file path and name
   *  @param int $mfid meeting file id
   *  @param string $fp filepath
   *  @param string $fn filename
   */              
  function update_meeting_file($mfid, $fp, $fn)
  {
    $sql = "update meeting_file set filepath='$fp', filename='$fn' where mfid=$mfid";
    $db = get_db();
    $db->execute($sql);
  }
  
  /**
   *  update meeting image path and name
   *  @param int $miid meeting image id
   *  @param string $fp filepath
   *  @param string $fn filename
   */              
  function update_meeting_image($miid, $fp, $fn)
  {
    $sql = "update meeting_image set filepath='$fp', filename='$fn' where miid=$miid";
    $db = get_db();
    $db->execute($sql);
  }

	
	/**
	 *	delete all meeting attendance data from table for a given meeting 
	 *	@todo delete this function (is it used?)
	 *	@param string $mid meeting id
	 */
	function delete_meeting_attendance($mid)
	{
		$db = get_db();
    $db->execute("delete from meeting_attendance where mid=$mid");
	}
  function delete_meeting_attendance_for_user($uid)
  { 
    $db = get_db();
    $db->execute("delete from meeting_attendance where uid=$uid");
  }
  
  function delete_user_role_data($uid)
  {
    $db = get_db();
    $db->execute("delete from role where uid=$uid");
  }
  
  function delete_user_data($uid)
  {
    $db = get_db();
    $db->execute("delete from user where uid=$uid");
  }	
	/**
	 *	delete meeting from database
	 *	@param string $mid meeting id
	 */
	function delete_meeting($mid)
	{
		$db = get_db();
		$db->execute("delete from meeting where mid=$mid");
	}
	
	/**
	 *	fetch latest members in user base
	 *	@return mixed[] latest members <uid,cid,clubname,profile_firstname,profile_lastname,profile_image,company_position>
	 *	@param string $limit number of users to retrieve
	 */
	function fetch_latest_members($limit=10)
	{
		$sql = "
							select 
							U.uid,C.cid,C.name as clubname,U.profile_firstname,U.profile_lastname,U.profile_image, U.company_position from user U
							left join club C on C.cid=U.cid
							inner join role R on R.uid=U.uid
							where profile_image!='' and R.rid=".MEMBER_ROLE_RID." and R.start_date<now() and R.end_date>now() order by profile_started desc limit $limit
		";
		return get_data($sql);
	}
	
	/**
	 *	calculate average member age for members matching the given criteria
	 *	@return double average age
	 *	@param string $criteria sql where statement for filtering members
	 */
	function fetch_avg_member_age($criteria="")
	{
		$db = get_db();
		$rs = $db->execute("select AVG( (YEAR(now())-YEAR(profile_birthdate)) ) as avg from user where uid>0 and profile_birthdate!='0000-00-00' $criteria");
		return $db->fetchsinglevalue($rs);
	}
	
	/**
	 *	count number of members in user base
	 *	@return int number of members matching the criteria
	 *	@param string $criteria sql where statement for filtering members
	 */
	function fetch_member_count($criteria="",$extras="")
	{
		$db = get_db();
		$rs = $db->execute("select count(*),cid from user U {$extras} where uid>0 $criteria");
		return $db->fetchsinglevalue($rs);
	}
	
	/**
	 *	retrieve info about members based on their roles
	 *	@param	string $role_string short name listing ('LF', 'VLF', 'LK')
	 */
	function fetch_members_by_roles($role_string)
	{
		return get_data("
				select C.name as club,D.name as district,U.uid,U.profile_firstname,U.profile_lastname,RD.shortname as role_short,RD.description as role from user U
				inner join role R on R.uid=U.uid
				inner join club C on C.cid=U.cid
				inner join district D on D.did=C.district_did
				inner join role_definition RD on RD.rid=R.rid
				where RD.shortname in $role_string and R.end_date>now() and R.start_date<now()
				order by RD.weight desc
		");
	}
	
	/**
	* read the district name
	* @param int $did district id
	* @return string name of district 
	*/
	function get_district_name($did)
	{
		return get_one_data_row("select name from district where did=$did");
	}
	
	
	/**
	 * delete an item from tabler service category
	 * @param int $tid item id
	 */
	function delete_tabler_service_item($tid)
	{
		$db = get_db();
		$db->execute("delete from tabler_service_item where tid=$tid");
	}
	
	/**
	 *	count number of active roles
	 *	@return int number of active roles
	 *	@param string $criteria sql where statement for filtering members
	 */
	function fetch_num_active_roles($criteria="",$daterange="R.end_date>=date(now()) and R.start_date<=date(now())")
	{
		$db = get_db();
		$sql = "
 			  select  count(*)
				from user U
				inner join club C on U.cid=C.cid
				inner join district D on C.district_did=D.did
				inner join role R on R.uid=U.uid
				inner join role_definition RD on RD.rid=R.rid
				where $daterange 
				$criteria
				";
				if (isset($_REQUEST['debug'])) echo "<!--- $sql --->\n";
		$rs = $db->execute($sql);
		return $db->fetchsinglevalue($rs);
	}
	
	/**
	 *	fetch profile information of national board (roles specified in config.php by constant NATIONAL_BOARD_ROLES)
	 *	@return mixed[] national board members <image,uid,firstname,lastname,rolename>
	 */
	function fetch_national_board()
	{
		$nationalroles = NATIONAL_BOARD_ROLES;
		$sql = "
						select 
						U.profile_image as image, U.uid as uid, U.profile_firstname as firstname, U.profile_lastname as lastname, RD.shortname as rolename, R.rid as rid
						from user U
						inner join role R on R.uid=U.uid
						inner join role_definition RD on R.rid=RD.rid
						where 
						RD.shortname in $nationalroles
						and R.start_date<now()
						and R.end_date>now()
						order by RD.rid asc
		";
		return get_data($sql);
	}
	
  /**
   * delete meeting attendance data for meeting
   * @param int $mid meeting id
   * @param int $uid user id
   */           
  function delete_meeting_attendance_for_uid($mid,$uid)
  {
    fire_sql("delete from meeting_attendance where uid=$uid and mid=$mid");
  }
  
  /**
   * update user view tracker
   * @param int $uid user id viewed
   * @param int $viewer_uid usre id of viewer
   */
  function put_user_view($uid, $viewer_uid)
  {
	fire_sql("insert into user_view_tracker (uid,viewer_uid,ts) values ('$uid', '$viewer_uid', now())");
  }
  
  function get_user_view($uid,$count)
  {
	return get_data("select distinct(viewer_uid) from user_view_tracker where uid=$uid order by ts desc limit $count");
  }
  
  
	/**
	 *	update/insert meeting attendance for a given user to a given meeting
	 *	@param int $mid meeting id
	 *	@param int $uid user id
	 *	@param bool $accepted is the invitaiton accepted?
	 *	@param string $comment comment related to reply
	 */
	function save_meeting_attendance($mid,$uid,$accepted=1,$comment="")
	{
		$db = get_db();
		
		$comment = addslashes($comment);

		$rs = $db->execute("select maid from meeting_attendance where uid=$uid and mid=$mid");
		if ($db->numrows($rs)!=0)
		{
			$maid = $db->fetchsinglevalue($rs);
			$sql = "update meeting_attendance set response_date=now(), uid='$uid', accepted='$accepted', comment='$comment' where maid='$maid'";
			$db->execute($sql);
		}
		else
		{
			$sql = "insert into meeting_attendance (mid,uid,accepted,comment) values ('$mid', '$uid', '$accepted', '$comment')";
			$db->execute($sql);
		}
	}
	
	function get_meeting_attendance_uid($uid,$mid)
	{
		$db = get_db();
		$rs = $db->execute("select maid from meeting_attendance where uid=$uid and mid=$mid");
		return $db->numrows($rs);
	}


	/**
	 *	fetch all members of a given club
	 *	@return mixed[] members <uid,username,profile_firstname,profile_lastname>
	 *	@param int $cid club id
	 */
	function fetch_all_club_members($cid)
	{
		$sql = "
						select 
						*,
						(select max(weight) from role RR inner join role_definition RRD on RR.rid=RRD.rid where RR.uid=U.uid) as Weight 
						from user U
						where 
						U.cid=$cid and day(U.profile_ended)=30 and month(U.profile_ended)=6
						order by U.profile_firstname ASC
		";
		return get_data($sql);
	}
	
	
	/**
	 *	fetch active members of a given club
	 *	@return mixed[] members <uid,username,profile_firstname,profile_lastname>
	 *	@param int $cid club id
	 */
	function fetch_active_club_members($cid)
	{
		$sql = "
						select 
						*,
						(select max(weight) from role RR inner join role_definition RRD on RR.rid=RRD.rid where RR.uid=U.uid and RR.start_date<now() and RR.end_date>now()) as Weight  
						from user U
						inner join role R on R.uid=U.uid
						where 
						U.cid=$cid 
						and R.rid IN (".MEMBER_ROLE_RID.",".HONORARY_ROLE_RID.")
						and R.start_date<now()
						and R.end_date>now()
						order by weight desc
		";
    
    
    
		return get_data($sql);
	}
	
	/**
	 *	fetch club board for a given club
	 *	@return mixed[] board members <image,uid,firstname,lastname,rolename>
	 *	@param int $cid club id
	 *	@param string $pstart period start
	 *	@param string $pend period end
	 */
	function fetch_club_board($cid,$pstart=false,$pend=false)
	{
		if (!$pstart) $s='now()';
		else $s = "'{$pstart}'";
		
		if (!$pend) $e='now()';
		else $e = "'{$pend}'";
		$boardroles = CLUB_BOARD_ROLES;
		$sql = "
						select 
						U.profile_image as image, U.uid as uid, U.profile_firstname as firstname, U.profile_lastname as lastname, RD.shortname as rolename
						from user U
						right join role R on R.uid=U.uid
						inner join role_definition RD on R.rid=RD.rid
						where 
						U.cid=$cid
						and RD.shortname in $boardroles
						and R.start_date<={$s}
						and R.end_date>={$e}
						order by RD.rid asc
					";
		return get_data($sql);
	}

  function create_club()
  {
    $db = get_db();
    $db->execute("insert into club (name) values ('No name')");
    $cid = $db->insertid();
    return $cid;
  }
  
  function delete_club($cid)
  {
    $db = get_db();
    $db->execute("delete from club where cid={$cid}");
  }


	/**
	 *	create a user and associate with club
	 *	@return int new user id
	 *	@param mixed[] $data <key,value> array to insert in new user
	 *	@param int $cid club id
	 */
	function create_user($data,$cid)
	{
			$db = get_db();
			$data['cid'] = $cid;
			$fields = array();
			$values = array();
			foreach($data as $key => $value)
			{
				$value = str_replace("\n","",$value);
				$value = str_replace("\r","",$value);
				$value = addslashes($value);
				$fields[] = $key;
				$values[] = "'$value'";
			}
			$fields_sql = implode(",", $fields);
			$values_sql = implode(",", $values);
			$sql = "insert into user ($fields_sql) values ($values_sql)";
			$db->execute($sql);
			$uid = $db->insertid();
			return $uid;
	}
	
	/**
	 *	insert/update meeting
	 *	@return int meeting id
	 *	@param mixed[] $meeting <key,value> array to insert/update meeting
	 *	@param int $mid meeting id
	 */
	function save_meeting($meeting, $mid)
	{
		$db = get_db();
		if ($mid<0)
		{
			$fields = array();
			$values = array();
			foreach($meeting as $key => $value)
			{
				$value = str_replace("\n","",$value);
				$value = str_replace("\r","",$value);
				$value = addslashes($value);
				$fields[] = $key;
				$values[] = "'$value'";
			}
			$fields_sql = implode(",", $fields);
			$values_sql = implode(",", $values);
			$sql = "insert into meeting ($fields_sql) values ($values_sql)";
			$db->execute($sql);
			$mid = $db->insertid();
		}
		else
		{
			$values = array();
			foreach($meeting as $key => $value)
			{
				$value = addslashes($value);
				$values[] = "$key='$value'";
			}			
			$values_sql = implode(",",$values);
			$sql = "update meeting set $values_sql where mid=$mid";
			$db->execute($sql);
		}
		return $mid;
	}


	/**
	 *	insert/update article
	 *	@return int article id
	 *	@param int $aid article id (-1 for new article)
	 *	@param string $title article title
	 *	@param string $content article content
	 *	@param bool $public is article visible without logging in?
	 *	@param int $uid author id
	 *	@param int $weight sort order weight for ranking articles in menues
	 *	@param int $parent_aid parent article id (-1 if top level article)
	 *	@param string $link external/internal link (may be left blank)
	 */
	function save_article($aid, $title, $content, $public, $uid, $weight, $parent_aid, $link="")
	{
		if ($public == "") $public = "0";
		$title = addslashes($title);
		$content = addslashes($content);
		$db = get_db();
		if ($aid==-1)
		{
			$sql = "insert into article (title, content, uid, last_update, public, weight, parent_aid, link) values ('$title', '$content', '$uid', now(), '$public', '$weight', '$parent_aid', '$link')";
			$db->execute($sql);
			return $db->insertid();
		}
		else
		{
			$sql = "update article set link='$link', parent_aid='$parent_aid', weight='$weight', public='$public', title='$title', content='$content', uid='$uid', last_update=now() where aid=$aid";
			$db->execute($sql);
			return $aid;
		}
	}

	/**
	 *	fetch article from database
	 *	@return mixed[] article data 
	 *	@param int $aid article id
	 */
	function fetch_article($aid)
	{
		$db = get_db();
		$rs = $db->execute("select * from article where aid=$aid");
		$article = $db->fetchassoc($rs);
		
		$rs = $db->execute("select * from article_file where aid=$aid");
		while ($image = $db->fetchassoc($rs))
		{
			$article['image'][] = $image;
		}
		return $article;
	}

	/**
	 *	fetch list of articles
	 *	@return mixed[] articles <aid,title,public>
	 *	@param int $parent parent article id (-1 for top level articles)
	 */
	function fetch_articles($parent=-1)
	{
		$sql = "select aid,title,public from article where parent_aid=$parent order by weight asc";
		return get_data($sql);
	}


	/**
	 *	fetch data for meeting image
	 *	@return mixed[] meeting image data
	 *	@param int $miid meeting image id
	 */
	function fetch_meeting_image_data($miid)
	{
		$db = get_db();
		$rs = $db->execute("select * from meeting_image where miid=$miid");
		if ($db->numrows($rs)==0)
    {
      die(stacktrace());
    }
		return $db->fetchassoc($rs);
	}

	/**
	 *	fetch meeting image file path
	 *	@return string file path of image
	 *	@param int $miid meeting image id
	 *	@todo delete (is this used anywhere?)
	 */
  function fetch_meeting_image($miid)
	{
		$db = get_db();
		$rs = $db->execute("select filepath from meeting_image where miid=$miid");
		if ($db->numrows($rs)==0) die(stacktrace());
		return $db->fetchsinglevalue($rs);
	}

	/**
	 *	fetch active roles for specific user
	 *	@return mixed[] role information
	 *	@param int $uid user id
	 */
	function fetch_active_roles($uid,$future=false)
	{
		$db = get_db();

		
		if (!$future)
		{
			$rs = $db->execute("select * from role R inner join role_definition RD on RD.rid=R.rid where R.uid=$uid and R.start_date<=date(now()) and R.end_date>=date(now()) order by R.end_date");
		}
		else
		{
			$rs = $db->execute("
select * from role R inner join role_definition RD on RD.rid=R.rid where 
R.uid=$uid and 
DATE_SUB(R.start_date,interval 1 month) <= date(now()) and 
R.end_date>=date(now()) 
order by R.end_date
");
		}
		
		$roles = array();
		while ($row = $db->fetchassoc($rs))
		{
			$roles[] = $row;
		}
		return $roles;
	}
	
	/**
	 *	fetch old roles for specific user
	 *	@return mixed[] role information
	 *	@param int $uid user id
	 */
	function fetch_old_roles($uid)
	{
		$db = get_db();
		$rs = $db->execute("select * from role R inner join role_definition RD on RD.rid=R.rid where R.uid=$uid and R.end_date<now() order by R.end_date");
		$roles = array();
		while ($row = $db->fetchassoc($rs))
		{
			$roles[] = $row;
		}
		return $roles;
	}
	

	/**
	 *	fetch active roles for specific user
	 *	@return mixed[] role information
	 *	@param int $uid user id
	 */
	function fetch_active_role_names($uid)
	{
		return get_data("select RD.rid,RD.description from role R inner join role_definition RD on RD.rid=R.rid where R.uid=$uid and R.start_date<=date(now()) and R.end_date>=date(now()) and R.rid!=".MINIMUM_ROLE_ALLOWED_RID);
	}

	
	
	/**
	 *	fetch meeting data
	 *	@return mixed[] meeting data 
	 *	@param int $mid meeting id
	 */
	function fetch_meeting($mid)
	{
		$db = get_db();
		$rs = $db->execute("select *,M.description as meeting_description, C.description as clubname from meeting M inner join club C on M.cid=C.cid where mid=$mid");
		if ($db->numrows($rs)==0) return false;
		else 
		{
			$result = $db->fetchassoc($rs);
			return $result;
		}
	}
	
	/**
	 *	fetch images related to meeting
	 *	@return mixed[] image data array <miid,filepath,filename>
	 *	@param int $mid meeting id
	 */
	function fetch_images_for_meeting($mid)
	{
		return get_data("select miid,filepath,filename from meeting_image where mid=$mid");
	}

	/**
	 *	fetch meeting data for multiple meetings
	 *	@return mixed[] meeting data 
	 *	@param string $filter sql where statement for filtering meetings
	 *	@param int $limit number of items to return (max)
	 */
	function fetch_meetings($filter="", $limit=25)
	{
		return get_data("select * from meeting M inner join club C on M.cid=C.cid $filter limit $limit");
	}



	/**
	 *	fetch meeting data for multiple meetings in club
	 *	@return mixed[] meeting data 
	 *	@param string $filter sql where statement for filtering meetings
	 *	@param int $limit number of items to return (max)
	 *	@param string $order "asc"/"desc" sort of start_date
	 *	@param int $cid club id
	 */
	function fetch_meetings_for_club($cid, $filter="", $order="asc", $limit=10)
	{
		return get_data("select * from meeting where cid=$cid $filter order by start_time $order limit $limit");
	}

	/**
	 *	fetch user data based on private email
	 *	@return mixed[] user data 
	 *	@param string $username username
	 */
	function fetch_user_by_private_email($email)
	{
		$email = addslashes($email);
		$db = get_db();
		$rs = $db->execute("select * from user where private_email='$email'");
		if ($db->numrows($rs)==0) return false;
		else return $db->fetchassoc($rs);
	}


	/**
	 *	fetch user data based on company email
	 *	@return mixed[] user data 
	 *	@param string $username username
	 */
	function fetch_user_by_company_email($email)
	{
		$email = addslashes($email);
		$db = get_db();
		$rs = $db->execute("select * from user where company_email='$email'");
		if ($db->numrows($rs)==0) return false;
		else return $db->fetchassoc($rs);
	}
    
    function fetch_user_by_company_facebook($fb)
	{
		$fb = addslashes($fb);
		$db = get_db();
		$rs = $db->execute("select * from user where company_facebook='$fb'");
		if ($db->numrows($rs)==0) return false;
		else return $db->fetchassoc($rs);
	}
    
    function fetch_user_by_company_linkdin($link)
	{
		$link = addslashes($link);
		$db = get_db();
		$rs = $db->execute("select * from user where company_linkdin='$link'");
		if ($db->numrows($rs)==0) return false;
		else return $db->fetchassoc($rs);
	}
    
    function fetch_user_by_company_google($google)
	{
		$google = addslashes($google);
		$db = get_db();
		$rs = $db->execute("select * from user where company_google='$google'");
		if ($db->numrows($rs)==0) return false;
		else return $db->fetchassoc($rs);
	}


	/**
	 *	fetch user data based on user name
	 *	@return mixed[] user data 
	 *	@param string $username username
	 */
	function fetch_user_by_username($username)
	{
		$username = addslashes($username);
		$db = get_db();
		$rs = $db->execute("select * from user where username='$username'");
		if ($db->numrows($rs)==0) return false;
		else return $db->fetchassoc($rs);
	}
	
	/**
	 *	fetch user data based on user id
	 *	@return mixed[] user data
	 *	@param int $uid user id
	 */
	function fetch_user($uid)
	{
		$db = get_db();
		$rs = $db->execute("select * from user where uid=$uid");
		if ($db->numrows($rs)==0) return false;
		else return $db->fetchassoc($rs);
	}
	
	/**
	 *	fetch club data
	 *	@return mixed[] club data 
	 *	@param int $cid club id
	 */
	function fetch_club($cid)
	{
		$db = get_db();
		$rs = $db->execute("select * from club where cid=$cid");
		if ($db->numrows($rs)==0) return false;
		else return $db->fetchassoc($rs);
	}

	/**
	 *	fetch all districts in the country
	 *	@return mixed[] districts <did,name,description>
	 */
	function fetch_country()
	{
		return get_data("select did,name,description from district order by name");
	}
	

	/**
	 *	fetch district data
	 *	@return mixed[] district data 
	 *	@param int $did district id
	 */
	function fetch_district($did)
	{
		$db = get_db();
		$rs = $db->execute("select * from district where did=$did");
		if ($db->numrows($rs)==0) return false;
		else return $db->fetchassoc($rs);
	}
	
	/**
	 *	fetch roles for a given user
	 *	@return mixed[] role data 
	 *	@param int $uid user id
	 */
	function fetch_user_roles($uid)
	{
		return get_data("
													select * from role R
													inner join role_definition RD on RD.rid=R.rid
													where R.uid=$uid order by start_date desc
												");
	}
	
	/**
	 *	save user data 
	 *	@param int $uid user id
	 *	@param mixed[] $data user data
	 */
	function save_user($uid,$data)
	{
		$db = get_db();
		$values = array();
		foreach($data as $key => $value)
		{
			$value = addslashes($value);
			$values[] = "$key='$value'";
		}			
		$values_sql = implode(",",$values);
		$sql = "update user set $values_sql where uid=$uid";
		//die($sql);
		$db->execute($sql);
	}

  /**
   * put meeting rating
   * @param int $mid meeting id
   * @param int $uid user id
   * @param int $r rating
   */
  function put_meeting_rating($mid,$uid,$r)
  {
    $db = get_db();
    $db->execute("insert into meeting_rating (mid,uid,rating) values ('$mid','$uid','$r')");
  }              
  
  /**
   * get files attached to meeting
   * @param int $mid meeting id
   */        
  function get_meeting_files($mid)
  {
    return get_data("select * from meeting_file where mid=$mid");
  }
  
  /**
   * get meeting file data
   * @param int $mfid meeting file id
   */        
  function get_meeting_file($mfid)
  {
		if (!is_numeric($mfid))
		{
			logic_log(__FUNCTION__, 'SQL Injection MFID'+addslashes($mfid));
			die();
		}
    return get_one_data_row("select filename,filepath from meeting_file where mfid=$mfid");  
  }
  
  /**
   *  get rating for a specific meeting
   *  @param int $mid meeting id
   *  @param int $uid if specified the users rating value for meeting will be returned
   *  return int rating
   */              
  function get_meeting_rating($mid, $uid=-1)
  {
		if (!is_numeric($mid))
		{
			logic_log(__FUNCTION__, 'SQL Injection MID'+addslashes($mid));
			die();
		}
    if ($uid>0)
    {
      return get_data("select rating from meeting_rating where mid=$mid and uid=$uid");
    }
    else
    {
      return get_data("select rating from meeting_rating where mid=$mid");
    }
  }
  
  /**
   *	update last page view for user
   *	@param int $uid user id
   */
  function update_last_page_view($uid, $page_title="", $page_url="")
  {
		if (!is_numeric($uid))
		{
			logic_log(__FUNCTION__, 'SQL Injection MFID'+addslashes($mfid));
			die();
		}
		$page_title = addslashes($page_title);
		$last_page_url = addslashes($page_url);
  	$db = get_db();
  	$db->execute("update user set last_page_view=now(), last_page_title='{$page_title}', last_page_url='{$page_url}' where uid=$uid");
  }
  
  /**
   *	search users for business
   *	@param string $q keyword
   *	@param mixed[] all users matching the business code
   */
  function get_users_per_business($q)
  {
	$q = addslashes($q);
  return get_data("select * from user where profile_ended>now() and company_business='$q' and company_name!='' order by company_name asc");
  }
  
  /**
   * load company businesses from user lists
   */
  function get_business_list()
  {
  	return get_data("select distinct(company_business) from user order by company_business asc");
  }
  
  /**
   *	assert wheter a user has logged on within the last period
   *	@param int $uid user id
   *	@param string $range range to look back ("3 month")
   */
  function get_last_page_view_within_range($uid,$range="3 month")
  {
		if (!is_numeric($uid))
		{
			logic_log(__FUNCTION__, 'SQL Injection UID'+addslashes($uid));
			die();
		}
		$sql = "SELECT count(*) FROM `user` where uid=$uid and (last_page_view is null or last_page_view<date_sub(now(), interval $range))";
		$data = get_one_data_row($sql);
		if (current($data)==0) return false;
		else return true;		
  }

	/**
	 *	get items from tabler_service_item table
	 *	@param int $tsid tabler service cateogy id
	 */
	function get_tabler_service_items($tsid)
	{
		if (!is_numeric($tsid))
		{
			logic_log(__FUNCTION__, 'SQL Injection TSID'+addslashes($tsid));
			die();
		}
		return get_data("select * from tabler_service_item where tsid=$tsid order by posted desc");
	}

	/**
	 *	add item to tabler service catalog
	 *	@param int $tsid category id
	 *	@param string $headline headline
	 *	@param string $description html description
	 *	@param string $location location
	 *	@param string $price price for the event
	 *	@param string $duration avg duration of event
	 *	@param string $contact contact parameters
	 *	@param int $uid user id of creator
	 */	
	function put_tabler_service_item($tsid, $headline, $description, $location, $price, $duration, $contact, $uid)
	{
		if (!is_numeric($tsid))
		{
			logic_log(__FUNCTION__, 'SQL Injection TSID'+addslashes($tsid));
			die();
		}
		$db = get_db();
		$db->execute("insert into tabler_service_item (tsid,headline,description,location,price,duration,contact,uid,posted) values ('$tsid', '$headline', '$description', '$location', '$price', '$duration', '$contact', '$uid', now())");
	}
	
	/**
	 * get tabler service categories
	 */
	function get_tabler_service_categories()
	{
		return get_data("select * from tabler_service");
	}

	/**
	 *	update tracker
	 *	@param string $current current $_SERVER['REQUEST_URI']
	 *	@param string $previous previous $_SERVER['REQUEST_URI']
	 *	@param string $title current title
	 */
	function put_tracker_url($current, $previous, $title)
	{
		$sql = "
			insert into tracker (current,previous, title) values ('$current', '$previous', '$title')
			on duplicate key update counter=counter+1;
		";
		$db = get_db();
		$db->execute($sql);
	}

	/**
	 * read geo location for any id
	 * @param int $id external id (e.g. user id, etc.)
	 * @param string $type values (private,company,club,meeting,etc.)
	 */
	function get_geolocation($id,$type)
	{
		$sql = "select * from geolocation where refid=$id and reftype='$type'";
		return get_one_data_row($sql);
	}
	
	    
	function get_geolocation_latest()
	{
		$sql = 
		"select 
U.uid, G.lat, G.lng, G.reftype, U.profile_firstname, U.profile_lastname, U.private_phone, U.private_mobile, U.company_phone, C.cid, C.name, U.last_page_view, U.last_page_title
from user U
JOIN geolocation G on U.uid=G.refid
inner join club C on C.cid=U.cid
where G.reftype='private'
order by last_page_view desc limit 25";
		logic_log("get_geolocation_latest", $sql);
		return get_data($sql);
	}
	
	/**
	 * put location for any id
	 * @param int $id external id (e.g. user id, etc.);
	 * @param double $lat lat
	 * @param double $lng lng
	 * @param string $type values (private,company,club,meeting,etc.)
	 * @param date $expiry expiry date
	 */
	function put_geolocation($id, $lat, $lng, $type, $expiry=false) 
	{
		$loc = get_geolocation($id,$type);

		if (empty($loc))
		{
			$sql = "insert into geolocation (refid,reftype,lat,lng,expiry_date) values ('$id', '$type', '$lat', '$lng', date_add(now(), interval 1 day))";
		}
		else
		{
			$gid = $loc['gid'];
			$sql = "update geolocation set lat='{$lat}', lng='{$lng}', expiry_date=date_add(now(), interval 1 day) where gid={$gid}";
		}

		logic_log("put_geolocation", $sql);
		fire_sql($sql);
	}


	
  /**
   * get latest users online
   * @return sorted list of 10 last users
   */        
  function get_latest_users()
  {
    $sql = "
      select u.uid,u.profile_firstname,u.profile_lastname,c.name as club,u.last_page_view from user u
      inner join club c on c.cid=u.cid
      order by last_page_view desc limit 10";
    return get_data($sql);
  }
  
	/**
	 *	read tracker
	 *	@param string $url current $_SERVER['REQUEST_URI']
	 */
	function get_tracker($url)
	{
		$sql = "select * from tracker where previous='$url' order by counter desc limit 5";
		return get_data($sql);
	}

	/**
	 *	get all clubs chartered between [start;end] period
	 *	@param string $start start date
	 *	@param string $end end date
	 */
	function get_club_jubilees($start, $end)
	{
		$start = addslashes($start);
		$end = addslashes($end);
		$sql = "
				select 
				c.cid, c.name as club,cc.name as charter_club,
				c.charter_date,
				d.name as district
				from club c
				inner join district d on d.did=c.district_did
				inner join club cc on c.charter_club_cid=cc.cid
				where
				c.charter_date>'$start' and c.charter_date<'$end'
				order by c.cid asc
		";
		return get_data($sql);
	}
	
	
	/**
	 *	get best meeting and clubs based on meeting rating
	 *	@param string $period_start
	 *	@param string $period_end
	 */
	function get_best_meetings($period_start, $period_end)
	{
		$period_start =addslashes($period_start);
		$period_end = addslashes($period_end);
		$data = array();
		$sql = "
				select 
				count(MR.rating) count,M.cid,avg(MR.rating) as average 
				from meeting_rating MR
				inner join meeting M on MR.mid=M.mid
				where M.start_time>'{$period_start}' and M.end_time<'{$period_end}'
				group by M.cid
				order by average desc,count desc
				limit 10
		";
		$data['best_club'] = get_data($sql);
		
		$sql = "
				select MR.mid,avg(MR.rating) as average, count(MR.rating) as count from meeting_rating MR
				inner join meeting M on MR.mid=M.mid
				where M.start_time>'{$period_start}' and M.end_time<'{$period_end}'
				group by mid order by average desc,count desc limit 10
		";
		$data['best_meeting'] = get_data($sql);
		
		return $data;	
	}
	
	
	/**
	 *	get all active members chartered between [start;end] period
	 *	@param string $start start date
	 *	@param string $end end date
	 */
	function get_jubilees($start, $end)
	{
		$start = addslashes($start);
		$end = addslashes($end);
		$sql = "
				select u.profile_started, u.uid,u.profile_firstname,u.profile_lastname,c.name as club, d.name as district from user u 
				inner join role r on r.uid=u.uid
				inner join club c on u.cid=c.cid
				inner join district d on d.did=c.district_did
				where 
				profile_started>'$start' 
				and profile_started<'$end'
				and r.rid in (".MEMBER_ROLE_RID.")
				and r.end_date>now()
				order by d.did asc
		";
		return get_data($sql);
	}
	
  /**
   * add mail attachment to database
   * @param string $fn filename - path is NOT included
   * @return id of inserted element
   */           
  function put_mail_attachment($fn)
  {
	$fn = addslashes($fn);
    $db = get_db();
    $db->execute("insert into mass_mail_attachment (filename) values ('$fn')");
    return $db->insertid();
  }

  /**
   * read attachment
   * @param int $aid attachment id
   * @return string filename - without path!
   */
  function get_attachment($aid)
  {
		if (!is_numeric($aid))
		{
			logic_log(__FUNCTION__, 'SQL Injection AID'+addslashes($aid));
			die();
		}
    return get_one_data_row("select filename from mass_mail_attachment where aid=$aid");
  }
  
  function put_search_query($q)
  {
	$q = addslashes($q);
  $sql = "
			insert into search (q) values ('$q')
			on duplicate key update count=count+1;
		";
		fire_sql($sql);
  }

  /** get data of random user
  */
  function get_random_user()
  {
	return get_one_data_row("select * from user U
inner join role R on R.uid=U.uid
inner join club C on C.cid=U.cid
where U.profile_image!='' and U.company_name!='' and R.rid=6 and R.start_date<now() and R.end_date>now()
order by rand()
limit 1");
  }
  
  
  /** store other meeting (without attendance req)
   *  @param int $cid club id
   *  @param string $title meeting title
   *  @param string $description meeting description
   *  @param string $location road directions
   *  @param date $start
   *  @param date $end
   */                    
  function put_other_meeting($cid, $title, $description, $location, $start, $end)
  {
		if (!is_numeric($cid))
		{
			logic_log(__FUNCTION__, 'SQL Injection CID'+addslashes($cid));
			die();
		}
		
		$title = addslashes($title);
		$description = addslashes($description);
		$location = addslashes($location);
		$start = addslashes($start);
		$end = addslashes($end);
		
    $sql =
    "
      insert into other_meeting (cid,title,description,location,start_time,end_time) values
      ('$cid','$title','$description','$location','$start','$end')
    ";
    
    fire_sql($sql);
  }
  
  /** 	delete other meeting
   *	@param int $omid meeting id
   */
  function delete_other_meeting($omid)
  {
		if (!is_numeric($omid))
		{
			logic_log(__FUNCTION__, 'SQL Injection OMID'+addslashes($omid));
			die();
		}
	fire_sql("delete from other_meeting where omid=$omid");
  }
  
  
  /** fetch other meetings (meetings without attendance req)
   *  @param int $cid (optional) club id
   *  @return mixed[] meeting data
   */        
  function get_other_meetings($cid=false)
  {
    if ($cid)
    {
		if (!is_numeric($cid))
		{
			logic_log(__FUNCTION__, 'SQL Injection CID'+addslashes($cid));
			die();
		}
      $sql = "select * from other_meeting where cid='$cid' and start_time>now() order by start_time asc";
    }
    else
    {
      $sql = "select * from other_meeting where start_time>now() order by start_time asc";
    }
    return get_data($sql);
  }
  
  function get_user_birthday($cid,$today)
  {
	if ($today)
	{
		if (!$cid)
		{
			return get_data("select uid,profile_firstname,profile_lastname,profile_birthdate,profile_image from user where          DATE_FORMAT(profile_birthdate,'%m-%d') = DATE_FORMAT(NOW(),'%m-%d') and profile_ended>now()");
		}
		else
		{
			if (!is_numeric($cid))
			{
				logic_log(__FUNCTION__, 'SQL Injection CID'+addslashes($cid));
				die();
			}
			return get_data("select uid,profile_firstname,profile_lastname,profile_birthdate,profile_image from user where          DATE_FORMAT(profile_birthdate,'%m-%d') = DATE_FORMAT(NOW(),'%m-%d') and profile_ended>now() and cid='$cid'");
		}
	}
	else
	{
		if (!$cid)
		{
			return get_data("select uid,profile_firstname,profile_lastname,profile_birthdate,profile_image from user where month(profile_birthdate)=month(now()) and profile_ended>now()");
		}
		else
		{
			if (!is_numeric($cid))
			{
				logic_log(__FUNCTION__, 'SQL Injection CID'+addslashes($cid));
				die();
			}
			return get_data("select uid,profile_firstname,profile_lastname,profile_birthdate,profile_image from user where cid='$cid' and month(profile_birthdate)=month(now()) and profile_ended>now()");
		}
	}
  }
  
  function clear_minutes_collection_cache($cid, $seed)
  {
		if (!is_numeric($cid))
		{
			logic_log(__FUNCTION__, 'SQL Injection CID'+addslashes($cid));
			die();
		}
		$seed = addslashes($seed);
	$sql = "delete from meeting_letters where cid={$cid} and collid={$seed}";
	fire_sql($sql);
  }
  
  
  function get_minutes_collection_cache($cid,$seed,$club_year=false)
  {
		if (!is_numeric($cid))
		{
			logic_log(__FUNCTION__, 'SQL Injection CID'+addslashes($cid));
			die();
		}
	if ($seed == '')
	{
		$sql = "select M.cid as cid,C.name as club,M.mid,M.title,M.start_time from meeting_letters ML 
				inner join meeting M on M.mid=ML.letter_mid
				inner join club C on C.cid=M.cid
				where ML.cid='$cid'
		";
	}
	else
	{
		if ($club_year!==false)
		{
			$sql = "select M.cid as cid,C.name as club,M.mid,M.title,M.start_time from meeting_letters ML 
					inner join meeting M on M.mid=ML.letter_mid
					inner join club C on C.cid=M.cid
					where ML.cid='$cid' 
					and ML.collid='$seed'
					and M.start_time>'$club_year'
		";
		}
		else
		{
			$sql = "select M.cid as cid,C.name as club,M.mid,M.title,M.start_time from meeting_letters ML 
					inner join meeting M on M.mid=ML.letter_mid
					inner join club C on C.cid=M.cid
					where ML.cid='$cid' and ML.collid='$seed'
		";
		}
	}
	return get_data($sql);
  }
  	function fetch_meeting_gallery($cid)
	{
		if (!is_numeric($cid))
		{
			logic_log(__FUNCTION__, 'SQL Injection CID'+addslashes($cid));
			die();
		}
		$sql ="
select M.title,MI.miid,M.start_time from meeting M
inner join meeting_image MI on MI.mid=M.mid
where M.cid={$cid}
order by M.start_time desc	
	";
		return get_data($sql);	
	}

  function add_minutes_collection_cache($cid,$seed,$mid)
  {
		if (!is_numeric($cid))
		{
			logic_log(__FUNCTION__, 'SQL Injection CID'+addslashes($cid));
			die();
		}
	fire_sql("insert into meeting_letters (cid,letter_mid,collid) values ('$cid','$mid','$seed')");
  }
  
  /**
   *  get minutes collection
   *  @param int $cid club id
   *  @param int $did district id
   *  @param int $seed random seed   
   *  @param int $limit number of elements to fetch   
   *  @return mixed[] minutes
   */
    function get_minutes_collection($cid,$seed,$did=-1,$limit=10,$omit_mids="0",$omit_cids="0")
   {
		if (!is_numeric($cid))
		{
			logic_log(__FUNCTION__, 'SQL Injection CID'+addslashes($cid));
			die();
		}
	$sql = "";
    if ($did<0)
    {
      $sql = "select distinct M.cid as cid,C.name as club,M.mid,M.title,M.start_time from meeting M
              inner join club C on C.cid=M.cid 
              where 
			  C.cid != $cid and
			  C.cid not in ({$omit_cids}) and
			  M.mid not in ({$omit_mids}) and
              start_time>date_sub(now(), interval 6 month) and
              minutes_date>date_sub(now(), interval 6 month) 
              group by C.cid 
			  order by rand($seed)
              limit $limit";
    }
    else
    {
      $sql = "select distinct M.cid as cid,C.name as club,M.mid,M.title,M.start_time from meeting M
              inner join club C on C.cid=M.cid
              where 
              C.district_did=$did and
			  C.cid not in ({$omit_cids}) and
			  M.mid not in ({$omit_mids}) and
              start_time>date_sub(now(), interval 6 month) and
              minutes_date>date_sub(now(), interval 6 month) 
			  group by C.cid 
              order by rand($seed)
              limit $limit";
    }
//	echo "$sql \n";
  return get_data($sql);
   }           
 ?>