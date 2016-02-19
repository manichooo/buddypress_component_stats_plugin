<?php
/*
  Plugin Name: Buddypress Component Stats
  Description: This plugin allows to obtain statistics about the users who interact in the social network and classifies the statistics of the main components of buddypress (Forums, Groups, Blogs, Comments, Activity,   		  Friends) showing results on the most active in each of these components.
  Version: 1.0
  Author: manichooo
*/
?>
<?php         		                       	                     						
	/* Actions que vinculan las funciones de instalacion y desintalacion desde el panel de administracion de plugins de wordpress */						
	add_action('activate_buddypress-component-stats/buddypress-component-stats.php','stats_install');
	add_action('deactivate_buddypress-component-stats/buddypress-component-stats.php', 'stats_desinstall');		
	
	/* call to function that include the js and css files for the plugin*/
	add_action('admin_init', 'my_admin_init');
	
	/* Add a menu item to Wordpress menu panel */
	add_action('admin_menu', 'buddypress_component_stats_add_menu');						
	
	/* Call to function that obtain the stats information from the front end view plugin */
	add_action('wp_ajax_results_query', 'get_component_stats');
		
	/* Function that recopile stats information about the users interaction by components (Activity Stream, Groups, Forums, Comments, Blogs, Friendship) on the social network  */				
	function get_component_stats() {
		global $wpdb;										
		$star_date = $_POST['start_date'];
		$final_date = $_POST['final_date'];
		$component = $_POST['component'];
		
		if($component != 'friendship'){
			$html.= "<br/>
				<h4>Results found on <span class='component'>$component</span> component between <strong><b>$star_date</b> and <b>$final_date</b></strong></h4></br>									
				<table id='myTable' class='tablesorter'>
					<thead>
			";
		} else {
			$html.= "<br/>													
				<h4>Results found on <span class='component'>$component</span> component</h4></br>
				<table id='myTable' class='tablesorter'>
					<thead>
			";
		}
								
		switch($component){
			case 'activity':																																
				$sql = "
					SELECT COUNT(type) as publications, wp_users.display_name, wp_users.ID, wp_users.user_email, wp_users.user_registered, MAX(wp_bp_activity.date_recorded) AS lastest 
					FROM wp_bp_activity, wp_users
					WHERE wp_users.ID = wp_bp_activity.user_id AND component = 'activity' AND type = 'activity_update' AND date_recorded BETWEEN '".$star_date." 00:00:00' AND '".$final_date." 23:59:59' 
					GROUP BY(user_id) 
					ORDER BY (publications) DESC
				";								
				        					        
				$response = $wpdb->get_results($sql);
				$records = sizeof($response);								        
				
				if($response){					
					$query = true;
					paginateResults(2,1,$records);								
					$html.= "						
						<tr>
							<th>User Avatar</th>
							<th>User</th>
							<th>Amount Publications</th>
							<th>e-mail</th>
							<th>Registered from</th>
							<th>Last Update</th>
						</tr>
						</thead>
						<tbody>
					";																																																						                    
					foreach ( $response as $rs ) {																																																													
						$total += $rs->publications;																													
						$html.="																									
						<tr>					
							<td class='avatar' align='center'>".get_avatar( $rs->user_email, 24 )."</td>
							<td>$rs->display_name</td>														
							<td align='center'><a href='#' onclick=\"javascript:detailed_publications('".$rs->ID."','".$star_date."','".$final_date."', 'activity')\">$rs->publications</a></td>
							<td>$rs->user_email</td>
							<td>".normalize_dates($rs->user_registered)."</td>
							<td>".normalize_dates($rs->lastest)."</td>					
						</tr>";											
					}																																																																																																									
				} else {
					echo "<h3><strong>No records have been found between the selected dates</strong></h3>";											
				}																							
			break;
			
			case 'groups':												
				$sql = "
					SELECT COUNT(type) as publications, wp_users.display_name, wp_users.ID, wp_users.user_email, wp_users.user_registered, MAX(wp_bp_activity.date_recorded) AS lastest,					
					(SELECT COUNT(wp_bp_groups_members.user_id) FROM wp_bp_groups_members, wp_users u2 WHERE u2.ID = wp_bp_groups_members.user_id and u2.ID = wp_users.ID ) as groups							           														
					FROM wp_bp_activity, wp_users			
					WHERE wp_users.ID = wp_bp_activity.user_id AND component = 'groups' AND type = 'activity_update' 
					AND date_recorded BETWEEN '".$star_date." 00:00:00' AND '".$final_date." 23:59:59'
					GROUP BY wp_bp_activity.user_id
					ORDER BY (publications) DESC
				"; 								   					        
				$response = $wpdb->get_results($sql);
				$records = sizeof($response);								        
				
				if($response){	
					
					$query = true;
					paginateResults(4,1,$records);
					
					$html.= "
						<tr>
							<th>User Avatar</th>
							<th>Username</th>
							<th>Amount Groups Involved</th>
							<th>Invloved Groups Name</th>
							<th>Amount Publications</th>
							<th>e-mail</th>
							<th>Registered From</th>
							<th>Last Update</th>
						</tr>
						</thead>
						<tbody>
					";																																												
					
					foreach ( $response as $rs ) {																																																								
						$total += $rs->publications;																																								
						$subsql = "
							SELECT DISTINCT(wp_bp_groups.name) FROM wp_bp_groups_members, wp_bp_groups WHERE wp_bp_groups_members.user_id = '".$rs->ID."' AND wp_bp_groups_members.group_id = wp_bp_groups.ID
						";
						$subresponse = $wpdb->get_results($subsql);													        				
						if($subresponse){
							$groupsname='';
							foreach ( $subresponse as $rsg ){
								$groupsname.= $rsg->name."<br />";
							}
						}
																		
						$html.="
						<tr>					
							<td class='avatar' align='center'>".get_avatar( $rs->user_email, 24 )."</td>
							<td>$rs->display_name</td>							
							<td align='center'>$rs->groups</td>
							<td align='center'>$groupsname</td>
							<td align='center'><a href='#' onclick=\"javascript:detailed_publications('".$rs->ID."','".$star_date."','".$final_date."','groups')\">$rs->publications</a></td>
							<td>$rs->user_email</td>
							<td>".normalize_dates($rs->user_registered)."</td>
							<td>".normalize_dates($rs->lastest)."</td>
						</tr>";															
					}																																																																																					
				} else {
					echo "<h3><strong>No records have been found between the selected dates</strong></h3>";
				}														
			break;
			
			case 'forums': 
				$sql = "
					SELECT COUNT(type) as publications, wp_bp_activity.content, wp_users.display_name, wp_users.ID, wp_users.user_email, wp_users.user_registered, MAX(wp_bp_activity.date_recorded) AS lastest									
					FROM wp_bp_activity, wp_users			
					WHERE wp_users.ID = wp_bp_activity.user_id AND component = 'groups' AND (type = 'new_forum_topic' OR type = 'new_forum_post') 
					AND date_recorded BETWEEN '".$star_date." 00:00:00' AND '".$final_date." 23:59:59'
					GROUP BY wp_bp_activity.user_id
					ORDER BY (publications) DESC
				"; 								   					        
				$response = $wpdb->get_results($sql);
				$records = sizeof($response);								        
				
				if($response){			
					
					$query = true;
					paginateResults(2,1,$records);
					
					$html.="
						<tr>
							<th>User Avatar</th>
							<th>Username</th>				
							<th>Amount Forum Publications</th>
							<th>e-mail</th>
							<th>Registered From</th>
							<th>Last Update</th>				
						</tr>
						</thead>
						<tbody>
					";																																												
					
					foreach ( $response as $rs ) {																																																								
						$total += $rs->publications;						
						$html.="						
							<tr>					
								<td class='avatar' align='center'>".get_avatar( $rs->user_email, 24 )."</td>
								<td>$rs->display_name</td>					
								<td align='center'><a href='#' onclick=\"javascript:detailed_publications('".$rs->ID."','".$star_date."','".$final_date."', 'forums')\">$rs->publications</a></td>
								<td>$rs->user_email</td>
								<td>".normalize_dates($rs->user_registered)."</td>
								<td>".normalize_dates($rs->lastest)."</td>										
							</tr>
						";															
					}																																																																																																						
				} else {
					echo "<h3><strong>No records have been found between the selected dates</strong></h3>";
				}
								
			break;
			
			case 'blogs': 
				$sql = "SELECT blog_id, domain, path, registered, last_updated FROM wp_blogs WHERE last_updated BETWEEN '".$star_date." 00:00:00' AND '".$final_date." 23:59:59'";					   					        
				$response = $wpdb->get_results($sql);
				$records = sizeof($response);								        
				
				if($response){								
					$query=true;
					paginateResults(2,1,$records);
					
					$html.= "
					<tr>
						<th>Blogname</th>						
						<th>Blog URL</th>
						<th>Amount Articles Published</th>
						<th>Amount Comments</th>
						<th>Date Created</th>
						<th>Last Update</th>																	
					</tr>
					</thead>
					<tbody>";																																																						
										
					foreach ($response as $rs) {																																																																			
							$url = 'http://'.$rs->domain.$rs->path;						
							if($rs->blog_id != 1) {								
								$subsql = 
								"
									SELECT COUNT(wp_".$rs->blog_id."_comments.comment_ID) as comments, wp_".$rs->blog_id."_options.option_value as blogname, 
									(SELECT COUNT(wp_".$rs->blog_id."_posts.post_type) FROM wp_".$rs->blog_id."_posts 
									WHERE wp_".$rs->blog_id."_posts.post_type = 'post' AND wp_".$rs->blog_id."_posts.post_status = 'publish' 
									AND wp_".$rs->blog_id."_posts.post_date BETWEEN '".$star_date." 00:00:00' AND '".$final_date." 23:59:59') as articles
									FROM wp_".$rs->blog_id."_options, wp_".$rs->blog_id."_comments 
									WHERE wp_".$rs->blog_id."_options.option_name = 'blogname' AND wp_".$rs->blog_id."_comments.comment_date BETWEEN '".$star_date." 00:00:00' AND '".$final_date." 23:59:59'
									ORDER BY articles DESC								 
								";							
								
								$responseblogs = $wpdb->get_results($subsql);																								
								foreach($responseblogs as $rsb) {							
									$total += $rsb->articles;								
									$html.="
									<tr>												
										<td>$rsb->blogname</td>									
										<td><a href='".$url."' target='_blank'>$url</a></td>
										<td align='center'><a href='#' onclick=\"javascript:detailed_publications('".$rs->blog_id."','".$star_date."','".$final_date."', 'blogs')\">$rsb->articles</a></td>													
										<td align='center'>$rsb->comments</td>
										<td>".normalize_dates($rs->registered)."</td>
										<td>".normalize_dates($rs->last_updated)."</td>
									</tr>";	
								}
							} else {						
								$subsql = 
								"
									SELECT COUNT(wp_comments.comment_ID) as comments, wp_options.option_value as blogname, 
									(SELECT COUNT(wp_posts.ID) FROM wp_posts WHERE wp_posts.post_type = 'post' AND wp_posts.post_status = 'publish' 
									AND wp_posts.post_date BETWEEN '".$star_date." 00:00:00' AND '".$final_date." 23:59:59') as articles
									FROM wp_options, wp_comments 
									WHERE wp_options.option_name = 'blogname' AND wp_comments.comment_date BETWEEN '".$star_date." 00:00:00' AND '".$final_date." 23:59:59'
									ORDER BY articles DESC	
								";
																
								$responseblogs = $wpdb->get_results($subsql);																
								foreach($responseblogs as $rsb) {							
									$total += $rsb->articles;																
									$html.="
									<tr>												
										<td>$rsb->blogname</td>
										<td><a href='".$url."' target='_blank'>$url</a></td>
										<td align='center'><a href='#' onclick=\"javascript:detailed_publications('".$rs->blog_id."','".$star_date."','".$final_date."', 'blogs')\">$rsb->articles</a></td>
										<td align='center'>$rsb->comments</td>
										<td>".normalize_dates($rs->registered)."</td>
										<td>".normalize_dates($rs->last_updated)."</td>
									</tr>";	
								}
							}																						 																											
						}																																																																																																																												
				} else {
					echo "<h3><strong>No records have been found between the selected dates</strong></h3>";
				}				 
			break;
						
			case 'comments': 
				$sql = "SELECT wp_users.ID, wp_users.display_name, wp_users.user_registered, wp_users.user_email FROM wp_users";					   					        
				$response = $wpdb->get_results($sql);
				$records = sizeof($response);								        
				
				if($response){			
					
					$query = true;
					paginateResults(2,1,$records);
					
					$html.= "
						<tr>
							<th>User Avatar</th>
							<th>Username</th>												
							<th>Amount Comments on Blogs</th>
							<th>Registered From</th>																							
						</tr>
						</thead>
						<tbody>
					";																																																																					
					
					$users = array();
					$pos=0;															
					
					foreach ($response as $rs) {																																																																																										
						
						$users[$pos]['UserName'] = $rs->display_name;	
						$users[$pos]['RegisteredDate'] = $rs->user_registered;
						$users[$pos]['email'] = $rs->user_email;
						
						$sqlblogs = "SELECT blog_id FROM wp_blogs";
						$responseblogs = $wpdb->get_results($sqlblogs); 												
						
						foreach($responseblogs as $rsblog){																																																																		
								if($rsblog->blog_id != 1) {								
									$subsql = "
										SELECT COUNT(user_id) as comments
										FROM wp_".$rsblog->blog_id."_comments, wp_users 
										WHERE wp_".$rsblog->blog_id."_comments.user_id = wp_users.ID 
										AND wp_users.ID = ".$rs->ID."
										AND wp_".$rsblog->blog_id."_comments.comment_date BETWEEN '".$star_date." 00:00:00' AND '".$final_date." 23:59:59'
									";
																																				
								} else {						
									$subsql = "
										SELECT COUNT(user_id) as comments 
										FROM wp_users, wp_comments
										WHERE wp_comments.user_id = wp_users.ID 
										AND wp_users.ID = ".$rs->ID."
										AND wp_comments.comment_date BETWEEN '".$star_date." 00:00:00' AND '".$final_date." 23:59:59'
									";																																				
								}
								
								$responseco = $wpdb->get_results($subsql);																																
								foreach ($responseco as $rscom){									
									$users[$pos]['Comments'] += $rscom->comments;
								}
						}
						$pos++;																																																						
					}																																								
														
					for($i=0; $i<sizeof($users); $i++){																																																
						$html.="
							<tr>																							
								<td class='avatar' align='center'>".get_avatar( $users[$i]['email'], 24 )."</td>
								<td>".$users[$i]['UserName']."</td>												
								<td align='center'>".$users[$i]['Comments']."</td>
								<td>".normalize_dates($users[$i]['RegisteredDate'])."</td>											
							</tr>
						";
						$total+=$users[$i]['Comments'];												
					}																																																																																																														
				} else {
					echo "<h3><strong>No records have been found between the selected dates</strong></h3>";
				}				
			break;	
			
			case 'friendship':				
				$sql = "
					SELECT wp_users.display_name, wp_users.user_registered, wp_usermeta.meta_value, wp_users.user_email
					FROM wp_users, wp_usermeta
					WHERE wp_users.ID = wp_usermeta.user_id
					AND wp_usermeta.meta_key = 'total_friend_count'
					ORDER BY wp_usermeta.meta_value DESC					
				";
					   					        
				$response = $wpdb->get_results($sql);
				$records = sizeof($response);								        
				
				if($response){							
					$query = true;
					paginateResults(3,1,$records);					
					$html.= "
					<tr>
						<th>User Avatar</th>
						<th>Username</th>												
						<th>e-mail</th>
						<th>Amount of Friends</th>
						<th>Registered From</th>																							
					</tr>
					</thead>
					<tbody>";																																																																																								
					
					foreach ($response as $rs) {	
						$profile = get_bloginfo('home')."/members/".$rs->display_name."/";																																																																																																																							
						$html.="
							<tr>																							
								<td class='avatar' align='center'>".get_avatar( $rs->user_email, 24 )."</td>
								<td><a href=".$profile." target='_blank'>$rs->display_name</a></td>
								<td>$rs->user_email</td>
								<td align='center'>$rs->meta_value</td>
								<td>".normalize_dates($rs->user_registered)."</td>											
							</tr>
						";																					
					}																																																																																																																																
				} else {
					echo "<h3><strong>No records have been found between the selected dates</strong></h3>";								
				}				 
			break;						
		}
				
		if($component != 'friendship'){
			$html.="
					<table>
					<tr>
						<td><i style='float:left; width:100%;'>Total publications on <span class='component'>$component </span> component: $total</i></td>
					</tr>
					</table>			
				</tbody>
				</table>					
			";
		} else {
			$html.="								
				</tbody>
				</table>					
			";		
		}
		
		echo $html;														
		if(isset($query)){									
			$paramspdf = get_bloginfo('url') . '/' . PLUGINDIR . '/' . dirname(plugin_basename(__FILE__))."/template/pdf.php?type=$component&start_date=$star_date&final_date=$final_date";
			$paramsxls = get_bloginfo('url') . '/' . PLUGINDIR . '/' . dirname(plugin_basename(__FILE__))."/template/xls.php?type=$component&start_date=$star_date&final_date=$final_date";											
			echo "<div id='export'>
				<div id='btnexport'>
					<div id='openexport'>
						<a href='#' id='clicExportar' onclick='javascript:return false;'>Export</a>
					</div>					
				</div>
				<div id='formats'>
					<div id='pdf'>																
						<a href='".$paramspdf."' id='hrefpdf' target='_blank'>PDF</a>
					</div>					
					<div id='xls'>
						<a href='".$paramsxls."' id='hrefpdf' target='_blank'>XLS</a>
					</div>
				</div>
			</div>";
		}
		die();																 																			
	}
		
	/* call action to display detailde results */
	add_action('wp_ajax_component_detailed_stats', 'get_component_detailed_stats');

	function get_component_detailed_stats() {
		global $wpdb;		
		$user_id = $_POST['user_id'];
		$star_date = $_POST['start_date'];
		$final_date = $_POST['final_date'];		
		$component = $_POST['component'];												
		
		switch($component){		
		case 'activity':						
			$sql = "
				SELECT wp_bp_activity.content, wp_users.display_name, wp_bp_activity.date_recorded
				FROM wp_bp_activity, wp_users
				WHERE wp_users.ID = wp_bp_activity.user_id AND component = 'activity' AND type = 'activity_update' AND date_recorded BETWEEN '".$star_date." 00:00:00' AND '".$final_date." 23:59:59'
				AND wp_users.ID = '".$user_id."'
				ORDER BY wp_bp_activity.date_recorded DESC
			";
			
			$response = $wpdb->get_results($sql);
			$records = sizeof($response);
			
			if($response){				
				$html = "<br/>													
					<table width='100%' id='tabladetalle'>
					<thead>
					<tr>								
						<th width='70%'>Content</th>
						<th width='30%'>Date</th>				
					</tr>
					</thead>
					<tbody>
				";
				
				foreach ( $response as $rs ) {																																																																																																																																				
					$html.="																									
					<tr>
						<td width='70%'>$rs->content</td>
						<td width='30%' align='right'>".normalize_dates($rs->date_recorded)."</td>
					</tr>";
				}
				
				$html.="							
					</tbody>
					</table>
					</br><h4>Records found for the user <strong><b>".$rs->display_name."</b></strong> 
					between <strong><b>$star_date</b> and <b>$final_date</b></strong> on<strong><b> <span class='component'>$component</span> </b></strong>component</h4>					
				";
			}			
		break;
		
		case 'groups':
			$sql = "
				SELECT wp_bp_activity.content, wp_users.display_name, wp_bp_activity.date_recorded, wp_bp_groups.name
				FROM wp_bp_activity, wp_users, wp_bp_groups
				WHERE wp_users.ID = wp_bp_activity.user_id AND component = 'groups' AND type = 'activity_update' AND date_recorded BETWEEN '".$star_date." 00:00:00' AND '".$final_date." 23:59:59'
				AND wp_users.ID = '".$user_id."' AND wp_bp_groups.id = wp_bp_activity.item_id			
				ORDER BY wp_bp_activity.date_recorded DESC
			"; 				      					        
			
			$response = $wpdb->get_results($sql);
			$records = sizeof($response);
			if($response){
				
				$html = "<br/>													
				<table width='100%' id='tabladetalle'>
				<thead>
				<tr>								
					<th width='40%'>Content</th>
					<th width='30%'>Group Name</th>
					<th width='30%'>Date</th>				
				</tr>
				</thead>
				<tbody>";
				
				foreach ( $response as $rs ) {																																																																																		
					$html.="																									
					<tr>										
						<td width='40%'>$rs->content</td>
						<td width='30%'>$rs->name</td>
						<td width='30%' align='right'>".normalize_dates($rs->date_recorded)."</td>									
					</tr>";											
				}
				
				$html.="							
					</tbody>
					</table>
					</br><h4>Records found for the user <strong><b>".$rs->display_name."</b></strong> 
					between <strong><b>$star_date</b> and <b>$final_date</b></strong> on<strong><b> <span class='component'>$component</span> </b></strong>component</h4>					
				";
			}	
		break;
		
		case 'forums':
			$sql = "
				SELECT wp_bp_activity.content, wp_users.display_name, wp_bp_activity.date_recorded, wp_bp_activity.item_id			
				FROM wp_bp_activity, wp_users 
				WHERE wp_users.ID = wp_bp_activity.user_id AND component = 'groups' AND (type = 'new_forum_topic' OR type = 'new_forum_post') AND date_recorded 
				BETWEEN '".$star_date." 00:00:00' AND '".$final_date." 23:59:59'
				AND wp_users.ID = '".$user_id."'			
				ORDER BY wp_bp_activity.date_recorded DESC
			"; 				      					        
			
			$response = $wpdb->get_results($sql);
			$records = sizeof($response);
			if($response){
								
				$html = "<br/>													
					<table width='100%' id='tabladetalle'>
					<thead>
						<tr>								
							<th width='40%'>Content</th>
							<th width='20%'>Published on Forum</th>
							<th width='20%'>Forum Group</th>
							<th width='20%'>Date</th>				
						</tr>
					</thead>
					<tbody>
				";
				
				foreach ( $response as $rs ) {
					$subsql = "
						SELECT wp_bp_activity.content, wp_bp_groups.name 				
						FROM wp_bp_activity, wp_bp_groups
						WHERE wp_bp_activity.component = 'groups' AND wp_bp_activity.type = 'new_forum_topic' AND wp_bp_groups.id = wp_bp_activity.item_id
						AND wp_bp_activity.item_id	= '".$rs->item_id."'
					"; 
					
					$subresponse = $wpdb->get_results($subsql);
					if($subresponse){
						foreach ( $subresponse as $rsf ) {
							$forum = $rsf->content;
							$group = $rsf->name;
						}
					}																									
					
					$html.="																									
						<tr>										
							<td width='40%'>$rs->content</td>
							<td width='20%'>$forum</td>
							<td width='20%'>$group</td>
							<td width='20%' align='right'>".normalize_dates($rs->date_recorded)."</td>									
						</tr>
					";											
				}
				$html.="							
					</tbody>
					</table>
					</br><h4>Records found for the user <strong><b>".$rs->display_name."</b></strong> 
					between <strong><b>$star_date</b> and <b>$final_date</b></strong> on<strong><b> <span class='component'>$component</span> </b></strong>component</h4>					
				";
			}				
		break;
		
		case 'blogs':
			$id_blog = $_POST['user_id'];																			
			$html = "<br/>													
			<table width='100%' id='tabladetalle'>
			<thead>
			<tr>								
				<th width='20%'>Article Title</th>
				<th width='20%'>Publication Date</th>
				<th width='60%'>
					Comments
					<table width='100%'>
						<tr>
							<td width='20%'>Comment Author</td> <td width='50%'>Content</td> <td width='30%'>Date</td>
						</tr>
					</table>
				</th>											
			</tr>
			</thead>
			<tbody>";													
			
			if($id_blog!=1) {
				 $sql="
					SELECT wp_".$id_blog."_posts.post_title, wp_".$id_blog."_posts.post_date, wp_".$id_blog."_posts.ID
					FROM wp_".$id_blog."_posts
					WHERE wp_".$id_blog."_posts.post_status = 'publish' AND wp_".$id_blog."_posts.post_type = 'post' AND wp_".$id_blog."_posts.post_date BETWEEN '".$star_date." 00:00:00' AND '".$final_date." 23:59:59'
				";					
				$response = $wpdb->get_results($sql);						
				if($response) {																									
					foreach($response as $rs){													
						
						$subco="
							SELECT wp_".$id_blog."_comments.comment_author, wp_".$id_blog."_comments.comment_date, wp_".$id_blog."_comments.comment_content
							FROM wp_".$id_blog."_posts, wp_".$id_blog."_comments
							WHERE wp_".$id_blog."_comments.comment_post_ID = wp_".$id_blog."_posts.ID
							AND wp_".$id_blog."_comments.comment_post_ID = '".$rs->ID."'
							AND wp_".$id_blog."_comments.comment_date BETWEEN '".$star_date." 00:00:00' AND '".$final_date." 23:59:59'																					 
						";																									
						
						$html.="																									
							<tr>										
								<td valign='top' width='20%'>$rs->post_title</td>
								<td valign='top' width='20%'>".normalize_dates($rs->post_date)."</td>
								<td valign='top' width='60%'>
						";													
						$subresponse = $wpdb->get_results($subco);													
						if ($subresponse) {
							foreach($subresponse as $rsco){																								
								
								$html.="																																		
									<table width='100%'>
										<tr>
											<td valign='top' align='left' width='20%'>$rsco->comment_author</td> 
											<td valign='top' width='50%' align='left'>$rsco->comment_content</td> 
											<td valign='top' width='30%' align='right'>".normalize_dates($rsco->comment_date)."</td>
										</tr>
									</table>
								";	
							}
						} else {
							$html.="																																		
								<table width='100%'>
									<tr>
										<td colspan='3'>Without Comments</td> 										
									</tr>
								</table>
							";
						}													
						$html.="
								</td>																	
							</tr>
						";														
					}
				}
				
				$slqblog = "SELECT wp_".$id_blog."_options.option_value FROM wp_".$id_blog."_options WHERE wp_".$id_blog."_options.option_name = 'blogname'";
				$responseblog = $wpdb->get_results($slqblog);
				$final_datelog = $responseblog[0]->option_value;					
					
			} else {
				$sql="
					SELECT wp_posts.post_title, wp_posts.post_date, wp_posts.ID
					FROM wp_posts
					WHERE wp_posts.post_status = 'publish' AND wp_posts.post_type = 'post' AND wp_posts.post_date BETWEEN '".$star_date." 00:00:00' AND '".$final_date." 23:59:59'
				";					
				$response = $wpdb->get_results($sql);						
				if($response) {																									
					foreach($response as $rs){													
						$subco="
							SELECT wp_comments.comment_author, wp_comments.comment_date, wp_comments.comment_content
							FROM wp_posts, wp_comments
							WHERE wp_comments.comment_post_ID = wp_posts.ID AND wp_comments.comment_post_ID = '".$rs->ID."' AND wp_comments.comment_date BETWEEN '".$star_date." 00:00:00' AND '".$final_date." 23:59:59'								 
						";																			
						
						$html.="																									
							<tr>										
								<td valign='top' width='20%'>$rs->post_title</td>
								<td valign='top' width='20%'>".normalize_dates($rs->post_date)."</td>
								<td valign='top' width='60%'>
						";													
						$subresponse = $wpdb->get_results($subco);													
						if ($subresponse) {
							foreach($subresponse as $rsco){																
								
								$html.="																																		
									<table width='100%'>
										<tr>
											<td valign='top' align='left' width='20%'>$rsco->comment_author</td> 
											<td valign='top' width='50%' align='left'>$rsco->comment_content</td> 
											<td valign='top' width='30%' align='right'>".normalize_dates($rsco->comment_date)."</td>
										</tr>
									</table>
								";	
							}
						} else {
							$html.="																																		
									<table width='100%'>
										<tr>
											<td colspan='3'>Without Comments</td> 											
										</tr>
									</table>
								";
						}													
						$html.="
								</td>																	
							</tr>
						";														
					}
				}
				$slqblog = "SELECT wp_options.option_value FROM wp_options WHERE wp_options.option_name = 'blogname'";
				$responseblog = $wpdb->get_results($slqblog);
				$final_datelog = $responseblog[0]->option_value;								
			}																					      					        												
		$html.="							
			</tbody>
			</table>
			<br/><h4>Records found for the articles of the blog <span class='component'>".$final_datelog."</span> between <strong><b>$star_date</b> and <b>$final_date</b></strong></h4>					
		";							
		break;				
		}
				
		echo $html;
		die();
	}
		
	/* Fucntion to paginate results using javascript */
	function paginateResults($a,$b,$records) {
	?>
		<script type="text/javascript" language="javascript">
			jQuery("#myTable").tablesorter({         
				sortList: [[<?php echo $a; ?>, <?php echo $b; ?>]] 
			});			
			jQuery('#green').smartpaginator({ 				
				totalrecords: <?php echo $records; ?>,
				recordsperpage: 25, 
				datacontainer: 'myTable', 
				initval:0,
				length: 10,
				dataelement: 'tr',								
				next: 'Next', 
				prev: 'Prev', 
				first: 'First', 
				last: 'Last',
				display: 'single',
				theme: 'green' 				
			});
			
			jQuery('#btnexport').click(function(){
				jQuery('#formats').toggle();
			}); 									       		
		</script>
    <?php
	}
		
	/* Function to normalize dates to format Day of week, Month-00 of year hour-am/pm */				
	function normalize_dates($dates){								
		$timezone = get_option('timezone_string');
		date_default_timezone_set($timezone);
		$data = explode(" ", $dates);
		$date = explode("-", $data[0]);
		$hour = explode(":", $data[1]);						
		$time = mktime($hour[0], $hour[1], $hour[2], $date[1], $date[2], $date[0]);												
		$returndate = ucfirst(strftime("%A", $time)).", ".ucfirst(strftime("%B",$time))."-".strftime("%d of %Y %I:%M", $time)."-".date("a");																				
		$returndate = utf8_encode($returndate);		
		return $returndate;
	}
																																																																													
	/* enqueue scripts and styles */		
	function my_admin_init() {
		/* $pluginfolder = get_bloginfo('url') . '/' . PLUGINDIR . '/' . dirname(plugin_basename(__FILE__)); */
		$pluginfolder = WP_PLUGIN_URL . '/' .dirname(plugin_basename(__FILE__));
		wp_enqueue_style('jquery.ui.theme', $pluginfolder . '/template/css/jquery-ui.css');
		wp_enqueue_style('table-sorter', $pluginfolder . '/template/css/table-sorter.css');
		wp_enqueue_style('custom-layout', $pluginfolder . '/template/css/custom-layout.css');
		wp_enqueue_style('pagination-styles', $pluginfolder . '/template/css/smartpaginator.css');		
		wp_enqueue_script('jquery-ui-core');
		wp_enqueue_script('jquery-ui-tabs', $pluginfolder . '/template/js/ui.tabs.min.js');	
		wp_enqueue_script('jquery-ui-datepicker', $pluginfolder . '/template/js/ui.datepicker.min.js');		
		wp_enqueue_script('jquery-paginator', $pluginfolder . '/template/js/smartpaginator.js');
		wp_enqueue_script('jquery-table-sorter', $pluginfolder . '/template/js/jquery.tablesorter.js');												
	}
	
	/* funcion que es llamada al dar click en el menu insertado en la barra de opciones de administracion de wordpress */
	function stats_panel(){	 
	  include('template/stats_panel.php');
	} 
			
	/* Crear un vinculo para el plugin en la pestaña Herramientas de wordpress */
	function buddypress_component_stats_add_menu(){   
	  add_menu_page('BuddyPress Component Stats', 'BuddyPress Component Stats', 'manage_options', 'buddypress-component-stats', 'stats_panel', plugin_dir_url( __FILE__ ).'/template/images/stats.png');	  
	}
		
	/* funcion que es llamda al desinstalar el plugin */
	function stats_install(){	   
	}
	
	/* funcion que es llamda al instalar el plugin */
	function stasts_desinstall(){	   
	}																			
?>