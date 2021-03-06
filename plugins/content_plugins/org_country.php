<?
	/*
		content plugin article admin (c) 3kings.dk
		
		02-11-2012	rasmus@3kings.dk	draft
	*/

	IF ($_SERVER['REQUEST_URI'] == $_SERVER['PHP_SELF']) header("location: /");
		
	content_plugin_register('country', 'content_handle_country', 'Hele landet');
	
	
	FUNCTION content_handle_country()
	{
		$cal = "";
	if (!logic_is_member()) return term('article_must_be_logged_in');

		$did = $_REQUEST['country'];
		IF ($did == 0 && $did!="") 
		{
			header("location: /?country=".get_district_for_user($_SESSION['user']['uid']));		
			DIE();
		}
		$country = logic_get_country($did);

		if (isset($_REQUEST['comment']))
		{
			logic_save_comment($_REQUEST['nid'],$_REQUEST['comment'],$did);
		}

		
		$header = term('country_header');
		IF ($did!="")
		{
			FOR ($i=0;$i<sizeof($country['districts']);$i++)
			{
				IF ($did == $country['districts'][$i]['did']) 
				{
					set_title($country['districts'][$i]['name']);
					$html = "<h1>{$country['districts'][$i]['name']}: {$country['districts'][$i]['description']}</h1>";
					$cal = $country['districts'][$i]['name'];
					BREAK;
				}
			}
      
      $news = logic_get_news($did);
      $chair = array_merge(logic_get_news_comments($news['nid']),logic_get_district_chairmain($did),$news,array("did"=>$did,'comments'=>addslashes(json_encode(logic_get_news_comments($news['nid'])))));
      
      if (logic_is_admin() || $_SESSION['user']['uid']==$chair['uid'])
      {
        if (isset($_REQUEST['news']))
        {
          logic_save_news($did,$_REQUEST['news']['title'],$_REQUEST['news']['content']);
        }
        $html .= term_unwrap('district_chairman_post_news', array('did'=>$did));
      }
	  
		// latest minutes
		$html .= term('country_latest_minutes');
		$minutes = ARRAY();
		FOR ($i=0;$i<sizeof($country['minutes']);$i++)
		{
			$img = fetch_images_for_meeting($country['minutes'][$i]['mid']);
			IF (EMPTY($img))
			{
	//			$html .= term('country_future_meeting_item_no_pic');
			}
			ELSE
			{
				$item = $country['minutes'][$i];
				$item['image'] = $img[0]['miid'];
				UNSET($item['minutes_letters']);
				UNSET($item['minutes_3min']);
				UNSET($item['minutes']);
				UNSET($item['description']);
				UNSET($item['location']);
				$item['title'] = str_replace("'", "", $item['title']);
				$minutes[] = $item;
				
//				$html .= term_unwrap('country_future_meeting_item_pic', $img[0]);
//				$html .= term_unwrap('country_future_minutes_item', $country['minutes'][$i]);
			}
			
		}
		$html .= term_unwrap('country_future_minutes_item', $minutes, true);	  

//			echo "<!--- ".print_r($chair,true)."--->";

      $html .= term_unwrap('district_chairman', $chair);
	  $html .= term_unwrap('district_calendar_show', array('name'=>$cal));

      $html .= term('district_clubs');      
      $html .= "<table width=100%><tr>";
      $j = 0;
  		$clubs = logic_get_clubs($did);
  		FOR ($i=0;$i<sizeof($clubs);$i++)
      {
        $c = $clubs[$i];
        $cm = logic_get_club_chairman($c['cid']);
        $count = sizeof(logic_get_active_club_members($c['cid']));
        $html .= "<td valign=top><img style=\"border: 1px solid black;\" src=/uploads/club_logos/{$c['logo']} width=100px></td>".
                 "<td valign=top><a href=?cid={$c['cid']}>{$c['name']}</a><br>F: {$cm['profile_firstname']} {$cm['profile_lastname']}<br>Medlemmer: $count</td>";
        $j++;
        IF ($j==2)
        {
          $j=0;
          $html .= "</tr><tr>";
        }
      }
      $html .= "</tr></table>";
		}
		ELSE
		{		
			$html = $header;
      
      if (logic_is_admin())
      {
        if (isset($_REQUEST['news']))
        {
          logic_save_news('0',$_REQUEST['news']['title'],$_REQUEST['news']['content']);
        }
        $html .= term_unwrap('district_chairman_post_news', array('did'=>''));
      }
      
      

      $html .= term('country_choose_district');
			
			$html .= "<select onchange=\"document.location.href=this.value;\">
							  <option value=\"?country\" selected>".term('country_all_country')."</option>";
						
			
			FOR ($i=0;$i<sizeof($country['districts']);$i++)
			{
				$sel = "";
				IF ($did == $country['districts'][$i]['did']) $sel="SELECTED";
				$html .= "<option $sel value=\"?country={$country['districts'][$i]['did']}\">{$country['districts'][$i]['name']}: {$country['districts'][$i]['description']}</option>";
			}
			
			$html .= "</select><br>";
  		$html .= term('country_choose_club');
  		$html .= "<select onchange=\"document.location.href=this.value;\">
  						  <option value=\"?country={$did}\" selected>".term('country_all_district')."</option>";
  						  
  		$clubs = logic_get_clubs($did);
  		FOR ($i=0;$i<sizeof($clubs);$i++)
  		{
  			$html .= "<option value=\"?cid={$clubs[$i]['cid']}\">{$clubs[$i]['name']}</option>";
  		}
  		
  		$html .= "</select><br>";
      
		}
		
	  $html .= term_unwrap('district_calendar_show', array('name'=>$cal));
		
		
		
		// future meetings
		$html .= term_unwrap('country_future_meetings', $country['meetings'], true);
/*		
		FOR ($i=0;$i<sizeof($country['meetings']);$i++)
		{
			$img = fetch_images_for_meeting($country['meetings'][$i]['mid']);
			IF (EMPTY($img))
			{
				$html .= term('country_future_meeting_item_no_pic');
			}
			ELSE
			{
				$html .= term_unwrap('country_future_meeting_item_pic', $img[0]);
			}
			$html .= term_unwrap('country_future_meeting_item', $country['meetings'][$i]);
		}*/
		

//		$html .= term_unwrap('country_future_minutes_item', array('data'=>json_encode($minutes)));
		
		
		RETURN $html;
	}
?>