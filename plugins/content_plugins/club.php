<?
/*
		content plugin article admin (c) 3kings.dk
		
		02-11-2012	rasmus@3kings.dk	draft
	*/

	if ($_SERVER['REQUEST_URI'] == $_SERVER['PHP_SELF']) header("location: /");
		
	content_plugin_register('cid', 'content_handle_club', 'Klub');

	function club_header($club)
	{
		$members = logic_get_active_club_members($club['cid']);
		$mails = array();
		foreach($members as $k=>$v)
		{
			$mails[] = $v['private_email'];
		}
		$club['membermails'] = implode("; ",$mails);
		
		$board = logic_get_club_board($club['cid']);
		$mails = array();
		foreach($board as $k=>$v)
		{
			$u = logic_get_user_by_id($v['uid']);
			$mails[] = $u['private_email'];
		}
		$club['boardmails'] = implode(";",$mails);
		
		$html = "<div class='col-xs-12 col-sm-8 col-md-10 club-page club-page-header right-part'>";
		$html .= "<div class=\"container-out clearfix\">";
		$html .= term_unwrap('club_header', $club);
		
		return $html;
	}
	
	
	function club_book($club)
	{
		$members = logic_get_active_club_members($club['cid']);
		die(print_r($members,true));
	}
	function content_handle_gallery($club)
	{
		$data = logic_get_meeting_gallery($club['cid']);
		return club_header($club).term_unwrap('club_gallery', $data, true);	
	}
	
	function content_handle_message($club)
	{
		$msg = $_REQUEST['message'];
		if (empty($msg))
		{
			if (logic_may_edit_meeting($club['cid']) && $_SESSION['user']['cid']==$club['cid'])
			{
				return club_header($club).term_unwrap('club_message_prepare_send_club_admin', $club);
			}
			else
			{
				return club_header($club).term_unwrap('club_message_prepare_send', $club);
			}
		}
		else
		{
			if (isset($_REQUEST['sms']))
			{
				logic_send_sms($msg);
			}
			else
			{
				logic_send_message_to_active_club_members($club, $msg);
			}
			return club_header($club).term_unwrap('club_message_sent', array('message'=>$msg));
		}
	}
	
	function content_handle_club_ics($meetings,$club)
	{
			
			$fn = utf8_decode($club['name']).".ics";
			header("Content-Type: text/Calendar");
			header("Content-Disposition: inline; filename=\"$fn\"");

			$ics = 
"BEGIN:VCALENDAR
X-WR-CALNAME: {$club['name']} Calendar
PRODID:-//{$club['name']}//{$club['name']} Calendar//EN
VERSION:2.0
CALSCALE:GREGORIAN
METHOD:PUBLISH
";

		for ($i=0;$i<sizeof($meetings);$i++)
		{
			$meeting = $meetings[$i];
			$start = strtotime($meeting['start_time']);
			$end = strtotime($meeting['end_time']);
			$title = utf8_decode($club['name']).": ".utf8_decode($meeting['title']);


			$description = utf8_decode(html_entity_decode(strip_tags(str_replace("<br>","\r\n",$meeting['description'])),ENT_QUOTES,'UTF-8'));
			$description = str_replace("\r\n", " ", $description);


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

$ics .= 
"END:VCALENDAR
";

			die($ics);


	}
	
  function content_handle_special_club($club)
  {
		if (isset($_REQUEST['ics']) && $club)
		{
			return content_handle_club_ics(logic_fetch_future_meetings_for_club($club['cid'], 'asc', 100, false),$club);
		}

    set_title($club['name']);
    //$html = term_unwrap('special_club_page', $club);
	$html = "<div class='col-xs-12 col-sm-8 col-md-10 club-page right-part'>";
	
		if (logic_is_admin())
		{
			$html .= term_unwrap('special_club_page_admin',$club);
      
      if (isset($_REQUEST['meeting']))
      {
        logic_save_meeting($_REQUEST['meeting'],-1, $club['cid']);
      }
		}

		$meetings = logic_fetch_future_meetings_for_club($club['cid'],"asc",100);
		$minutes = logic_fetch_minutes($club['cid'],"desc", 50);
		
		if (sizeof($meetings)) $html .= term_unwrap('club_future_meetings', $meetings, true);
		if (sizeof($minutes)) $html .= term_unwrap('club_minutes', $minutes, true);

		$missing_minutes = logic_meeting_with_no_minutes($club['cid']);
		if (sizeof($missing_minutes)>0)
		{
		  $html .= term('club_missing_minutes');
          $html .= '<ul class="center">';
			for($i=0;$i<sizeof($missing_minutes);$i++)
			{
				$html .= term_unwrap('club_future_meetings_item', $missing_minutes[$i]);
			}
            $html .= '</ul>';
		}
		$html .= "</div>";
    return $html;
  }
  
	function content_handle_edit_club($club)
	{
		$data = $_REQUEST['edit'];
		if (is_array($data))
		{
			if (isset($_FILES['logo']))
			{
				$logo = logic_upload_club_logo($_FILES['logo'],$club['cid']);
			}
            if (isset($_FILES['chiarmain_image']))
			{              
				$logo1 = logic_upload_club_chairman($_FILES['chiarmain_image'],$club['cid']);
			}
			logic_update_club($club['cid'], $data);
			$club = logic_get_club($club['cid']);
		}
		if (logic_is_admin()) 
		{
			$club['all_clubs'] = addslashes(json_encode(logic_get_clubs()));
			return term_unwrap('edit_club_admin', $club);
		}
		else return term_unwrap('edit_club_secretary', $club);
	}
	
	function content_handle_archive($club)
	{
		
		$html = club_header($club);

		$minutes = logic_fetch_minutes($club['cid'],"desc", 9999);
		$html .= term_unwrap('club_minutes_archive', $minutes, true);
		return $html;
	}
  
	function content_handle_club()
	{
		
		if (!logic_is_member() && !logic_is_mummy()) 
		{
		  header("location: ?aid=-1");
		  die();
		}
    
    
		$cid = isset($_REQUEST['cid'])?$_REQUEST['cid']:$_SESSION['user']['cid'];

    if (logic_is_admin() && $cid==-1)
    {
      $cid = logic_create_club();
      header("location:/?cid={$cid}&edit");
      die();
    }
    
    if (logic_is_admin() && isset($_REQUEST['permanent_delete_club']))
    {
      logic_delete_club($cid);
      header("location:/");
      die();
    }


		$club = logic_get_club($cid);
		
		if ((logic_is_admin() || logic_is_club_secretary($club['cid'])) && isset($_REQUEST['edit'])) 
		{
			return content_handle_edit_club($club);
		}
		
		if ((logic_is_admin() || logic_is_club_secretary($club['cid'])) && isset($_REQUEST['delete_omid'])) 
		{
			logic_delete_other_meeting($_REQUEST['delete_omid']);
		}

		
		if (isset($_REQUEST['book']))
		{
			return club_book($club);
		}

		
		if ($club['district_did']==0)
		{
		  return content_handle_special_club($club);
		}
		
		if (isset($_REQUEST['archive']))
		{
			return content_handle_archive($club);
		}
		if (isset($_REQUEST['gallery']))
		{
			return content_handle_gallery($club);	
		}
		if (isset($_REQUEST['message']))
		{
			return content_handle_message($club);
		}
    
		$board = logic_get_club_board($cid);		



		if (isset($_REQUEST['ics']) && $club)
		{
			return content_handle_club_ics(logic_fetch_future_meetings_for_club($club['cid'], 'asc', 100, false),$club);
		}
	
	
		$html = club_header($club);

		if (!logic_is_mummy())		
		{
			if (($_SESSION['user']['cid'] == $club['cid'] && logic_is_secretary()) || logic_is_admin())
			{
				$html .= term_unwrap('club_secretary_tools',$club);
			}
			else if (($_SESSION['user']['cid'] == $club['cid'] && logic_is_ceremony_master()) || logic_is_admin())
			{
				$html .= term_unwrap('club_cerm_tools',$club);
			}
		}
	
	
		$meetings = logic_fetch_future_meetings_for_club($club['cid'],"asc",100);
		$minutes = logic_fetch_minutes_this_year($club['cid'],"desc", 30);
    $other_meetings = array("meetings" => logic_get_other_meetings($club['cid']), "birthday" => logic_get_club_birthdays($club['cid']));
		$html .= term_unwrap('club_future_meetings', $meetings, true);
		if (logic_is_admin() || logic_is_club_secretary($club['cid']))
		{
			$html .= term_unwrap('club_other_meetings_secretary', $other_meetings, true);
		}
		else
		{
			$html .= term_unwrap('club_other_meetings', $other_meetings, true);
		}
		
		
		$html .= term_unwrap('club_minutes', $minutes, true);
		$html .= term_unwrap('club_archive', $club);
/*		
		for($i=0;$i<sizeof($meetings);$i++)
		{
			$html .= term_unwrap('club_future_meetings_item', $meetings[$i]);
		}
		$html .= term('club_latest_minutes');
		$minutes = logic_fetch_minutes($club['cid'],"desc", 10);
		for($i=0;$i<sizeof($minutes);$i++)
		{
			$html .= term_unwrap('club_future_meetings_item', $minutes[$i]);
		}
*/		
		
		$html .= term('club_missing_minutes');
		$missing_minutes = logic_meeting_with_no_minutes($club['cid']);
        $html .= '<ul class="center">';
		for($i=0;$i<sizeof($missing_minutes);$i++)
		{
			$html .= term_unwrap('club_future_meetings_item', $missing_minutes[$i]);
		}
        $html .= '</ul>';
		
		$html .= "<div class=\"container-out clearfix\">";
			$html .= "<div class=\"title title-section\">";
				$html .= term('club_board');
			$html .= "</div><!-- .title.title-section -->";		
			$html .= "<div class=row>";
				$html .= term_unwrap('club_board_member', array('data'=>addslashes(json_encode($board))));
			$html .= "</div><!-- row -->";	
		$html .= "</div><!-- container-out clearfix -->";	
		
/*		foreach ($board as $k => $v)
		{
			$html .= term_unwrap('club_board_member', $v);
		}*/
		
		if (isset($_REQUEST['allmembers']))
		{
			$club_members = logic_get_all_club_members($club['cid']);
		}
		else
		{
			$club_members = logic_get_active_club_members($club['cid']);
		}
		$html .= "<div class=\"container-out clearfix\">";
			$html .= "<div class=\"title title-section\">";
				$html .= "<h2>Medlemmer</h2><p><button class=btn onclick=\"document.location.href=document.location.href+'&allmembers';\" >vis aktive og gamle medlemmer</button></p>";
			$html .= "</div><!-- .title.title-section -->";	
			
		$html .= "<div class=row>";
		$html .= term_unwrap('club_members', array('members'=>addslashes(json_encode($club_members))));
		$html .= "</div><!-- row -->";
		$html .= "</div><!-- container-out -->";
		$html .= '</div></div><!-- CLUB PAGE ENDS HERE -->';
		$html .= '<div class="container clubpg_last_sec" style="clear:both;">';
		$html .= term_unwrap('club_member_stat', logic_get_club_stats($club['cid']), true);

		set_title($club['name']);		
		$html .= '</div>';
		return $html;
	}
?>