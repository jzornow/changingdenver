<?php
$authorized = false;
$plugin = new Libsyn\Service();
$sanitize = new Libsyn\Service\Sanitize();
$api = $plugin->getApis();
$render = true;
$error = false;
if(isset($_GET)&&is_array($_GET)) parse_str(http_build_query($_GET));

if(!isset($_POST['redirect_url']))
	if(isset($_GET)) $redirectUri = get_site_url().'/wp-admin/?'.http_build_query($_GET);
		else $redirectUri = get_site_url().'/wp-admin/';
else { $redirectUri = $_POST['redirect_url']; }

/* Handle saved api */
if ($api instanceof Libsyn\Api && !$api->isRefreshExpired()){
	$refreshApi = $api->refreshToken(); 
	if($refreshApi) { //successfully refreshed
		$api = $api->retrieveApiById($api->getPluginApiId()); 
	} else { //in case of a api call error...
		$handleApi = true; 
		$clientId = (!isset($clientId))?$api->getClientId():$clientId; 
		$clientSecret = (!isset($clientSecret))?$api->getClientSecret():$clientSecret; 
		$api = false;
		if(isset($showSelect)) unset($showSelect);
	}
}

/* Handle Form Submit */
if (isset($_POST['submit'])&&($_POST['submit']==='Save Changes'||$_POST['submit']==='Save Player Settings')) { //has showSelect on form.
	if($api instanceof Libsyn\Api) { //Brand new setup or changes?
		if($_POST['submit']==='Save Player Settings') { //has Player Settings Update
			//sanitize player_settings
			$playerSettings = array();
			if(!isset($_POST['player_use_thumbnail'])) $playerSettings['player_use_thumbnail'] = '';
				else $playerSettings['player_use_thumbnail'] = $_POST['player_use_thumbnail'];
			$playerSettings['player_use_theme'] = $_POST['player_use_theme'];
			$playerSettings['player_height'] = $_POST['player_height'];
			$playerSettings['player_width'] = $_POST['player_width'];
			$playerSettings['player_placement'] = $_POST['player_placement'];
			$playerSettings_clean = $sanitize->player_settings($playerSettings);

			if(!$playerSettings_clean||empty($playerSettings_clean)) { //malformed data
				$error =  true; $msg = 'Something wrong with player input settings, please try again.';
			} elseif(is_array($playerSettings_clean)) { //looks good update options
				foreach ($playerSettings_clean as $key => $val) {
					update_option('libsyn-podcasting-'.$key, $val);
				}
			}
			
		} elseif ($_POST['submit']==='Save Changes') { //has config changes or update
			if (isset($_POST['showSelect'])) $api->setShowId($_POST['showSelect']);
			if($api->getClientSecret()!==$sanitize->clientSecret($_POST['clientSecret'])) $api->setClientSecret($sanitize->clientSecret($_POST['clientSecret']));
			if($api->getClientId()!==$sanitize->clientId($_POST['clientId'])) $api->setClientId($sanitize->clientId($_POST['clientId']));
			if(!isset($_POST['feed_redirect_url'])) $_POST['feed_redirect_url'] = '';
			if($api->getFeedRedirectUrl()!==$_POST['feed_redirect_url']) $api->setFeedRedirectUrl($_POST['feed_redirect_url']);
			$update = $plugin->updateSettings($api);
			if($update!==false) $msg = "Settings Updated";
			
			//do feed import
			$show_id = $api->getShowId();
			if($api->getFeedRedirectUrl()!==$_POST['feed_redirect_url']&&!empty($_POST['feed_redirect_url'])&&!empty($show_id)) {
				$feedImport = $plugin->feedImport($api);
				if(!$feedImport) { $msg = "Feed Import failed, check data or try again later."; $error = true; }
				$importer = new LIbsyn\Service\Importer();
				$importer->setFeedRedirect($api);
			}
		}
	} else { // for brand new setup just store in session through redirects.
		if(isset($_POST['clientId'])&&isset($_POST['clientSecret'])) { 
			update_option('client', array('id' => $sanitize->clientId($_POST['clientId']), 'secret' => $sanitize->clientSecret($_POST['clientSecret']))); 
			$clientId = $_POST['clientId']; 
		}
		if(isset($_SESSION['code'])) { $code = $sanitize->text($_POST['code']); }
	}
}

/* Handle API Creation/Update*/
	if((!$api)||($api->isRefreshExpired())) { //does not have $api setup yet in WP
		$render = false;
		/* Handle login and auth. */
		if(!$authorized) {
			if(isset($code)) { //handle auth callback $_POST['code']
				// (THIS FIRES WHEN YOU APPROVE API)
				$url = $redirectUri."&code=".$code."&authorized=true";
				if(isset($_SESSION['clientId'])) $url .= "&clientId=".$sanitize->clientId($_SESSION['clientId']);
				if(isset($_SESSION['clientSecret'])) $url .= "&clientSecret=".$sanitize->clientSecret($_SESSION['clientSecret']);
				echo "<script type=\"text/javascript\">
						if(typeof window.location.assign == 'function') window.location.assign(\"".$url."\");
							else if (typeof window.location == 'object') window.location(\"".$url."\");
								else if (typeof window.location.href == 'string') window.location.href = \"".$url."\";
									else alert('Unknown script error 1021.  To help us improve this plugin, please report this error to support@libsyn.com.');
					</script>";
			} elseif(isset($clientId)) { //doesn't have api yet
				$html = $plugin->oauthAuthorize($clientId, $redirectUri);
				echo $html;
			} elseif ($api instanceof Libsyn\Api) { //has api (update)
				$html = $plugin->oauthAuthorize($api->getClientId(), $redirectUri);
				echo $html;
			}
		} elseif(($authorized!==false)) { //has auth token
			if ($api instanceof Libsyn\Api) {
				if(!isset($clientId)) $clientId = $api->getClientId();
				if(!isset($clientSecret)) $clientSecret = $api->getClientSecret();
			} else {
				$client = get_option('client');
				if(!isset($clientId)) $clientId = $sanitize->clientId($client['id']);
				if(!isset($clientSecret)) $clientSecret = $sanitize->clientSecret($client['secret']);
			}

			/* Auth login */
			$json = $plugin->requestBearer(
					$sanitize->clientId($clientId),
					$sanitize->clientSecret($clientSecret),
					$sanitize->text($code),
					$sanitize->url_raw(urldecode($redirectUri))
				);
			$check = $plugin->checkResponse($json);
			$response= (array) json_decode($json->body);
			if(!$check) {echo "<div class\"updated\"><span style=\"font-weight:bold;\">".implode(" ", $json->response)."<br>".$response['detail']."<span></div>"; }
			elseif($check) {
				$response = $response + array(
					'client_id' => $sanitize->clientId($clientId),
					'client_secret' => $sanitize->clientSecret($clientSecret),
				);
				if($api instanceof Libsyn\Api && $api->isRefreshExpired()) {
					$api = $api->update($response);
				} else { 
					$libsyn_api = $plugin->createLibsynApi($response);
					$libsyn_api->refreshToken();
					$api = $libsyn_api->retrieveApiById($libsyn_api->getPluginApiId());
					$url = get_site_url().'/wp-admin/?page='.LIBSYN_DIR.'/admin/settings.php';
					echo "<script type=\"text/javascript\">
							if (typeof window.top.location.href == 'string') window.top.location.href = \"".$url."\";
								else if(typeof document.location.href == 'string') document.location.href = \"".$url."\";
									else alert('Unknown javascript error 1022.  Please report this error to support@libsyn.com and help us improve this plugin!');
						 </script>";
				}
				if(!$api) {echo "<div class\"updated\"><span style=\"font-weight:bold;\">Problem with the API connection, please check settings or try again.<span></div>"; }
			}
		}
	}

/* Form Stuff */
if($api instanceof Libsyn\Api && ($api->getShowId()===null||$api->getShowId()==='')) {
	$msg = "You must select a show to publish to.";
	$requireShowSelect = true;
	$error = true;
} elseif ($api===false&&!isset($clientId)) { $render = true; }

?>

<?php IF($render): ?>
<?php wp_enqueue_script( 'jquery-ui-dialog', array('jquery-ui')); ?>
<?php wp_enqueue_style( 'wp-jquery-ui-dialog'); ?>
<?php wp_enqueue_style( 'metaBoxes', '/wp-content/plugins/'.LIBSYN_DIR.'/lib/css/libsyn_meta_boxes.css' ); ?>
<?php wp_enqueue_style( 'metaForm', '/wp-content/plugins/'.LIBSYN_DIR.'/lib/css/libsyn_meta_form.css' ); ?>
	<style media="screen" type="text/css">
	.code { font-family:'Courier New', Courier, monospace; }
	.code-bold {
		font-family:'Courier New', Courier, monospace; 
		font-weight: bold;
	}
	</style>

	<div class="wrap">
	<?php if (isset($msg)) echo $plugin->createNotification($msg, $error); ?>
	  <h2><?php _e("Libsyn Plugin Settings", LIBSYN_DIR); ?><span style="float:right"><a href="http://www.libsyn.com/"><img src="<?php _e(get_site_url()); ?>/wp-content/plugins/<?php _e(LIBSYN_DIR); ?>/lib/images/libsyn_dark-small.png" title="Libsyn Podcasting" height="28px"></a></span></h2>
	  <form name="<?php echo LIBSYN_KEY . "form" ?>" id="<?php echo LIBSYN_KEY . "form" ?>" method="post" action="">
		 <div id="poststuff">
		  <div id="post-body">
			<div id="post-body-content">
			<?php if(isset($api) && ($api !== false)) { $shows = $plugin->getShows($api)->{'user-shows'};?>
			<!-- BOS Existing API -->
			  <div class="stuffbox" style="width:93.5%">
				<h3 class="hndle"><span><?php _e("Modify Api", LIBSYN_DIR); ?></span></h3>
				<div class="inside" style="margin: 15px;">
				  <p><em><?php _e("Libsyn account application settings can be found <a href=\"https://four.libsyn.com/wordpress-plugins\" target=\"_blank\">here.</a>", LIBSYN_DIR); ?></em></p>
				  <table class="form-table">
					<tr valign="top">
					  <th><?php _e("Client ID:", LIBSYN_DIR); ?></th>
					  <td>
						<input id="clientId" type="text" value="<?php _e($api->getClientId()); ?>" name="clientId" maxlength="12" pattern="[a-zA-Z0-9]{12}" required/> 
					  </td>
					</tr>
					<tr valign="top">
					  <th><?php _e("Client Secret:", LIBSYN_DIR); ?></th>
					  <td>
						<input id="clientSecret" value="<?php _e($api->getClientSecret()); ?>" type="password" name="clientSecret" maxlength="20" pattern="[a-zA-Z0-9]{20}" required/>
						<input type="hidden" name="handleApi" id="handleApi" />
					  </td>
					</tr>
					<tr valign="top">
					  <th><?php _e("Select Show:", LIBSYN_DIR); ?></th>
					  <td>
						<select name="showSelect" autofocus required>
							<?php 
								if(isset($requireShowSelect)&&($requireShowSelect)) echo  "<option value=\"\">None</option>";
								foreach($shows as $show) {
									if($api->getShowId()==$show->{'show_id'}||count($shows)===1)
										echo  "<option value=\"".$sanitize->showId($show->{'show_id'})."\" selected>".$show->{'show_title'}."</option>";
									else
										echo  "<option value=\"".$sanitize->showId($show->{'show_id'})."\">".$show->{'show_title'}."</option>";
								}
							?>
						</select>
					  </td>
					</tr>
					<?php if(is_int($api->getShowId())) { ?>
					<tr valign="top">
						<th></th>
						<td>
							<div class="inside" style="margin: 15px;">Libsyn is connected to your Wordpress account successfully.</div>
						</td>
					</tr>					
					<?php } ?>
					<tr valign="top">
					  <th></th>
					  <td>
						<?php submit_button(); ?>
					  </td>
					</tr>
				  </table>
				</div>
			  </div>
			  <!-- EOS Existing API -->			
			<?php } else { //new?>
			<?php $setup_new = true; ?>
			<!-- BOS Add new API -->
			  <div class="stuffbox">
				<h3 class="hndle"><span><?php _e("Add New Api", LIBSYN_DIR); ?></span></h3>
				<div class="inside" style="margin: 15px;">
				  <p><em><?php _e("Enter settings provided from your Libsyn account application setup <a href=\"http://libsyn.com/developer_api\" target=\"_blank\">here.</a>", LIBSYN_DIR); ?></em></p>
				  <table class="form-table">
					<tr valign="top">
					  <th><?php _e("Client ID:", LIBSYN_DIR); ?></th>
					  <td>
						<input id="clientId" type="text" value="" name="clientId" pattern="[a-zA-Z0-9]{12}" required/> 
					  </td>
					</tr>
					<tr valign="top">
					  <th><?php _e("Client Secret:", LIBSYN_DIR); ?></th>
					  <td>
						<input id="clientSecret" type="text" value="" name="clientSecret" pattern="[a-zA-Z0-9]{20}" required/> 
					  </td>
					</tr>
					<tr valign="top">
					  <th></th>
					  <td>
						<?php submit_button(); ?>
					  </td>
					</tr>
				  </table>
				</div>
			  </div>
			  <!-- EOS Add new API -->
			<?php } ?>	
	<?php IF(isset($podcasts)): ?>
			  <div class="stuffbox">
				<h3 class="hndle"><span><?php _e("Saved Playlists", LIBSYN_DIR); ?></span></h3>
				<div class="inside" style="margin: 15px;">
				  <table class="form-table">
	<?php  FOREACH($podcasts as $podcast): ?>
					<tr valign="top">
					  <th><?php _e($podcast->post_title, LIBSYN_DIR); ?></th>		
					  <td>
						id: <?php _e($podcast->ID); ?>
						<input id="podcast_delete_button" type="button" value="delete" name="podcast_feed_button" onclick="form.submit();" <?php checked("delete", get_option(LIBSYN_KEY . "podcast_delete_button")); ?> />		 
					  </td>
					</tr>
	<?php  ENDFOREACH; ?>
				  </table>
				</div>
			  </div>
	<?php ENDIF; ?>
			<?php if(!isset($setup_new)) { ?>
			  <!-- BOS Bottom L/R Boxes -->
			  <div class="box_left_column">
				  <div class="stuffbox box_left_content"></div>
			  </div>
			  <div class="box_right_column">
				  <div class="stuffbox">
					<div class="inside box_right_content_1">
					</div>
				  </div>
				  <div class="stuffbox">
					<div class="inside box_right_content_2">
					</div>
				  </div>
			  </div>
			  <div id="accept-dialog" class="hidden" title="Confirm Integration">
				<p><span style="color:red;font-weight:600;">Warning!</span> By accepting you will modifying your Libsyn Account & Wordpress Posts for usage with the Podcast Plugin.  We suggest backing up your Wordpress database before proceeding.</p>
				<br>
			  </div>

			  <!-- EOS Bottom L/R Boxes -->
			<?php } ?>
			</div>
		  </div>
		</div>
	  </form>
	</div>
	<?php $feed_redirect_url = (isset($api)&&$api!==false)?$api->getFeedRedirectUrl():''; ?>	
	<?php IF(isset($json)&&!empty($json)): ?>
	<script type="text/html" id="tmpl-wp-playlist-current-item">
		<# if ( data.image ) { #>
		<img src="{{ data.thumb.src }}"/>
		<# } #>
		<div class="wp-playlist-caption">
			<span class="wp-playlist-item-meta wp-playlist-item-title">&#8220;{{ data.title }}&#8221;</span>
			<# if ( data.meta.album ) { #><span class="wp-playlist-item-meta wp-playlist-item-album">{{ data.meta.album }}</span><# } #>
			<# if ( data.meta.artist ) { #><span class="wp-playlist-item-meta wp-playlist-item-artist">{{ data.meta.artist }}</span><# } #>
		</div>
	</script>
	<script type="text/html" id="tmpl-wp-playlist-item">
		<div class="wp-playlist-item">
			<a class="wp-playlist-caption" href="{{ data.src }}">
				{{ data.index ? ( data.index + '. ' ) : '' }}
				<# if ( data.caption ) { #>
					{{ data.caption }}
				<# } else { #>
					<span class="wp-playlist-item-title">&#8220;{{{ data.title }}}&#8221;</span>
					<# if ( data.artists && data.meta.artist ) { #>
					<span class="wp-playlist-item-artist"> &mdash; {{ data.meta.artist }}</span>
					<# } #>
				<# } #>
			</a>
			<# if ( data.meta.length_formatted ) { #>
			<div class="wp-playlist-item-length">{{ data.meta.length_formatted }}</div>
			<# } #>
		</div>
	</script>
	<?php ENDIF; ?>
	
	<?php //PP check goes here ?>
	
	<?php IF(!ISSET($setup_new)): ?>
	
	<?php //PP box goes here ?>
	
	<!-- BOS Handle PlayerSettings -->
	<?php 
		//handle adding settings fields for player-setings
		register_setting('general', 'libsyn-podcasting-player_use_thumbnail');
		register_setting('general', 'libsyn-podcasting-player_use_theme');
		register_setting('general', 'libsyn-podcasting-player_height');
		register_setting('general', 'libsyn-podcasting-player_width');
		register_setting('general', 'libsyn-podcasting-player_placement');
	?>
	<script type="text/javascript">
		(function($){
			$(document).ready(function(){
				$(".box_left_content").load("<?php _e(get_site_url()); ?>/wp-content/plugins/<?php _e(LIBSYN_DIR); ?>/admin/views/box_playersettings.php", function() {
					
					//add stuff to ajax box
					$("#player_use_theme_standard_image").append('<img src="<?php _e(get_site_url()); ?>/wp-content/plugins/<?php _e(LIBSYN_DIR); ?>/lib/images/player-preview-standard.jpg">');
					$("#player_use_theme_mini_image").append('<img src="<?php _e(get_site_url()); ?>/wp-content/plugins/<?php _e(LIBSYN_DIR); ?>/lib/images/player-preview-standard-mini.jpg">');
					$(".post-position-shape-top").append('<img src="<?php _e(get_site_url()); ?>/wp-content/plugins/<?php _e(LIBSYN_DIR); ?>/lib/images/player_position.png" style="vertical-align:top;">');
					$(".post-position-shape-bottom").append('<img src="<?php _e(get_site_url()); ?>/wp-content/plugins/<?php _e(LIBSYN_DIR); ?>/lib/images/player_position.png" style="vertical-align:top;">');
					
					//validate button
					$('<a>').text('Validate').attr({
						class: 'button'
					}).click( function() {
						var current_feed_redirect_input = validator_url + encodeURIComponent($("#feed_redirect_input").attr('value'));
						window.open(current_feed_redirect_input);
					}).insertAfter("#feed_redirect_input");
					
					//set default value for player use thumbnail
					<?php $playerUseThumbnail = get_option('libsyn-podcasting-player_use_thumbnail'); ?>
					var playerUseThumbnail = '<?php _e($playerUseThumbnail); ?>';
					if(playerUseThumbnail == 'use_thumbnail') {
						$('#player_use_thumbnail').attr('checked', true);
					}
					
					//set default value of player theme
					<?php $playerTheme = get_option('libsyn-podcasting-player_use_theme'); ?>
					var playerTheme = '<?php _e($playerTheme); ?>';
					if(playerTheme == 'standard') {
						$('#player_use_theme_standard').attr('checked', true);	
						//check if player_use_thumbnail is checked
						if($('#player_use_thumbnail').is(':checked')) {
							if(parseInt($('#player_height').val()) < 200) $('#player_height').val(200);
						} else {
							$('#player_height').attr({"min": "45"});
							if(parseInt($('#player_height').val()) < 45) $('#player_height').val(45);
						}						
					} else if(playerTheme == 'mini') {
						$('#player_use_theme_mini').attr('checked', true);	
						//check if player_use_thumbnail is checked
						if($('#player_use_thumbnail').is(':checked')) {
							if(parseInt($('#player_height').val()) < 200) $('#player_height').val(200);
						} else {
							$('#player_height').attr({"min": "26"});
							if(parseInt($('#player_height').val()) < 26) $('#player_height').val(26);
						}						
					} else { //default: getPlayerTheme is not set
						//set default value of player theme to standard if not saved
						$('#player_use_theme_standard').attr('checked', true);						
						//check if player_use_thumbnail is checked
						if($('#player_use_thumbnail').is(':checked')) {
							if(parseInt($('#player_height').val()) < 200) $('#player_height').val(200);
						} else {
							$('#player_height').attr({"min": "45"});
							if(parseInt($('#player_height').val()) < 45) $('#player_height').val(45);
						}
					}
					
					//player theme checkbox settings
					$('#player_use_theme_standard').change(function() {
						if($('#player_use_theme_standard').is(':checked')) {
							//check if player_use_thumbnail is checked
							if($('#player_use_thumbnail').is(':checked')) {
								if(parseInt($('#player_height').val()) < 200) $('#player_height').val(200);
							} else {
								$('#player_height').attr({"min": "45"});
								if(parseInt($('#player_height').val()) < 45) $('#player_height').val(45);
							}							
						} else if($('#player_use_theme_mini').is(':checked')) {
							//check if player_use_thumbnail is checked
							if($('#player_use_thumbnail').is(':checked')) {
								if(parseInt($('#player_height').val()) < 200) $('#player_height').val(200);
							} else {
								$('#player_height').attr({"min": "26"});
								if(parseInt($('#player_height').val()) < 26) $('#player_height').val(26);
							}
						}
					});
					$('#player_use_theme_mini').change(function() {
						if($('#player_use_theme_standard').is(':checked')) {
							//check if player_use_thumbnail is checked
							if($('#player_use_thumbnail').is(':checked')) {
								if(parseInt($('#player_height').val()) < 200) $('#player_height').val(200);
							} else {
								$('#player_height').attr({"min": "45"});
								if(parseInt($('#player_height').val()) < 45) $('#player_height').val(45);
							}							
						} else if($('#player_use_theme_mini').is(':checked')) {
							//check if player_use_thumbnail is checked
							if($('#player_use_thumbnail').is(':checked')) {
								if(parseInt($('#player_height').val()) < 200) $('#player_height').val(200);
							} else {
								$('#player_height').attr({"min": "26"});
								if(parseInt($('#player_height').val()) < 26) $('#player_height').val(26);
							}
						}
					});
					
					//player values height & width
					<?php $playerHeight = get_option('libsyn-podcasting-player_height'); ?>
					<?php $playerWidth = get_option('libsyn-podcasting-player_width'); ?>
					var playerHeight = parseInt('<?php _e($playerHeight); ?>');
					var playerWidth = parseInt('<?php _e($playerWidth); ?>');
					
					//height
					if(isNaN(playerHeight)) {
						$('#player_height').val(360);
					} else {
						if($('#player_use_theme_standard').is(':checked')) {
							if(playerHeight >= 45) $('#player_height').val(playerHeight);
								else $('#player_height').val(45);
						} else if($('#player_use_theme_mini').is(':checked')) {
							if(playerHeight >= 26) $('#player_height').val(playerHeight);
								else $('#player_height').val(26);
						} else {
							$('#player_height').val(360);
						}
					}
					
					//width
					if(isNaN(playerWidth)) {
						$('#player_width').val(480);
					} else {
						if($('#player_use_theme_standard').is(':checked')) {
							if(playerWidth >= 200) $('#player_width').val(playerWidth);
								else $('#player_width').val(200);
						} else if($('#player_use_theme_mini').is(':checked')) {
							if(playerWidth >= 100) $('#player_width').val(playerWidth);
								else $('#player_width').val(100);
						} else {
							$('#player_width').val(480);
						}
					}
					
					//player use thumbnail checkbox settings
					$('#player_use_thumbnail').change(function() {
						if($(this).is(':checked')) {
							if($('#player_height').val() == '' || parseInt($('#player_height').val()) <= 200) { //below min height
								$('#player_height').val("200");
								$('#player_height').attr({"min": "200"});
							}
						} else {
							if($('#player_use_theme_standard').is(':checked')) {
								$('#player_height').attr({"min": "45"});
							} else if($('#player_use_theme_mini').is(':checked')){
								$('#player_height').attr({"min": "26"});
							}
							
						}
					});
					
					//player placement checkbox settings
					<?php $playerPlacement = get_option('libsyn-podcasting-player_placement'); ?>
					var playerPlacement = '<?php _e($playerPlacement); ?>';
					if(playerPlacement == 'top') {
						$('#player_placement_top').attr('checked', true);
					} else if(playerPlacement == 'bottom') {
						$('#player_placement_bottom').attr('checked', true);
					} else { //player placement is not set
						$('#player_placement_top').attr('checked', true);
					}

				});
			});
		})(jQuery);
	</script>
	<!-- EOS Handle PlayerSettings -->
	<!-- BOS Handle About -->
	<script type="text/javascript">
		(function($){
			$(".box_right_content_1").load("<?php _e(get_site_url()); ?>/wp-content/plugins/<?php _e(LIBSYN_DIR); ?>/admin/views/box_about.php", function() {
				$("#version").text('Version <?php _e($plugin->getPluginVersion()); ?>');
			});
		})(jQuery);
	</script>
	<!-- EOS Handle About -->
	<!-- BOS Handle Support -->
	<script type="text/javascript">
		(function($){
			$(".box_right_content_2").load("<?php _e(get_site_url()); ?>/wp-content/plugins/<?php _e(LIBSYN_DIR); ?>/admin/views/box_support.php?libsyn_dir=<?php _e(LIBSYN_DIR); ?>", function() {
				
			});
		})(jQuery);
	</script>
	<!-- EOS Handle Support -->
	<?php ENDIF; ?>
<?php ENDIF; ?>
