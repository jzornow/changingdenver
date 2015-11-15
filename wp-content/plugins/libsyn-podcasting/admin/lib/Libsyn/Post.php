<?php
namespace Libsyn;
class Post extends \Libsyn{

	public static function actionsAndFilters() {
		add_filter( 'attachment_fields_to_save', array('\Libsyn\Post', 'updateAttachmentMeta'), 4);
		add_action('wp_ajax_save-attachment-compat', array('\Libsyn\Post', 'mediaExtraFields'), 0, 1);
		
		//ftp/unreleased tab
		//add_filter('media_upload_tabs', array('\Libsyn\Post', 'addMediaTabs')); //see function below (depreciated)
		add_action('media_upload_libsyn_ftp_unreleased', array('\Libsyn\Post', 'libsynFtpUnreleasedContent') ); // (adds external media content)
		add_action( 'admin_enqueue_scripts', array( '\Libsyn\Post', 'mediaAsset' ) ); // (adds primary media selection asset)
		add_action( 'admin_enqueue_scripts', array( '\Libsyn\Post', 'imageAsset' ) ); // (adds primary media selection asset)
		add_action( 'admin_enqueue_scripts', array( '\Libsyn\Post', 'ftpUnreleasedAsset' ) ); // (adds primary ftp/unreleased selection asset)
		add_action( 'admin_enqueue_scripts', array('\Libsyn\Post', 'enqueueValidation') );  // (adds meta form validation scripts)

	}
	
	
	public static function libsynFtpUnreleasedContent() {
		$error = false;
		$plugin = new Service();
		$api = $plugin->getApis();
		if ($api instanceof Api && !$api->isRefreshExpired()){
			$refreshApi = $api->refreshToken();
			if($refreshApi) { //successfully refreshed
				$ftp_unreleased = $plugin->getFtpUnreleased($api)->{'ftp-unreleased'};
			} else { $error = true; }
		} 

		if($error)
			echo "<p>Oops, you do not have your Libsyn Account setup properly to use this feature, please go to Settings and try again.</p>";
	}
	
    /**
     * (Depreciated) just making sure this is no longer needed.
     * 
     * @param <type> $tabs 
     * 
     * @return <type>
     */
	public static function addMediaTabs($tabs) {
		//$tabs_add["libsyn_external_media"] = 'Add External Media';
		$tabs_add["libsyn_ftp_unreleased"] = 'Libsyn FTP/Unreleased';
		return array_merge($tabs, $tabs_add);
	}

	public static function mediaExtraFields($attachment){
		  global $post;
		  update_post_meta($post->ID, 'meta_link', $attachment['attachments'][$post->ID]['meta_link']);
	}
	
	public static function imageAsset() {
		$type = 'image';
		self::mediaSelectAssets($type);
	}
	
	public static function mediaAsset() {
		$type = 'media';
		self::mediaSelectAssets($type);
	}
	
	public static function ftpUnreleasedAsset() {
		$type = 'libsyn';
		self::mediaSelectAssets($type);
	}
	
	public static function enqueueValidation(){
		wp_enqueue_script('jquery_validate', plugins_url(LIBSYN_DIR.'/lib/js/jquery.validate.min.js'), array('jquery'));
		wp_enqueue_script('libsyn_meta_validation', plugins_url(LIBSYN_DIR.'/lib/js/meta_form.js'));
	}
	
    /**
     * Sets up Media select button
     * 
     * @param <string> $type 
     * 
     * @return <mixed>
     */
	public static function mediaSelectAssets($type) {
		wp_enqueue_media();
		wp_register_script( 'libsyn-nmp-media-'.strtolower($type), plugins_url( LIBSYN_DIR.'/lib/js/media.' . strtolower($type) . '.min.js'), array( 'jquery' ), '1.0.0', true );
		wp_localize_script( 'libsyn-nmp-media-'.strtolower($type), 'libsyn_nmp_media',
			array(
				'title'     => __( 'Upload or Choose Your Custom ' . ucfirst($type) . ' File', 'libsyn-nmp-'.strtolower($type) ), 
				'button'    => __( 'Insert into Input Field', 'libsyn-nmp-'.strtolower($type) ),
			)
		);
		wp_enqueue_script( 'libsyn-nmp-media-'.strtolower($type) );

	}
	
	public static function updateAttachmentMeta($attachment){
		global $post;
		update_post_meta($post->ID, 'meta_link', $attachment['attachments'][$post->ID]['meta_link']);
	}

    /**
     * Adds Meta box html
     * 
     * @param <WP_Post> $object 
     * @param <mixed> $box 
     * 
     * @return <mixed>
     */
	public static function addLibsynPostMeta ($object, $box){
		wp_nonce_field( basename( __FILE__ ), 'libsyn_post_episode_nonce' );
		echo "<link type='text/css' rel='stylesheet' href='" . plugins_url(LIBSYN_DIR.'/lib/css/libsyn_meta_form.css') . "' />";
		
		$plugin = new Service();
		$api = $plugin->getApis();
		/* Handle saved api */
		$render = false; //default rendering to false
		$refreshTokenProblem = false;
		if ($api instanceof \Libsyn\Api && !$api->isRefreshExpired()){
			$render = true;
			if(!(current_user_can( 'upload_files' ))||!(current_user_can( 'edit_posts' ))) $render = false; //check logged in user privileges.
			$refreshApi = $api->refreshToken();
			if($refreshApi) { //successfully refreshed
				$api = $api->retrieveApiById($api->getPluginApiId());
			} else { $render = false; $refreshTokenProblem = true;}
		} elseif ($api->isRefreshExpired()) { $render = false; }
		if($api instanceof Libsyn\Api) $render = true;
		
		$isPowerpress = \Libsyn\Service\Integration::getInstance()->checkPlugin('powerpress');
		?>
		<?php wp_enqueue_script( 'jquery-ui-dialog', array('jquery-ui')); ?>
		<?php wp_enqueue_style( 'wp-jquery-ui-dialog'); ?>
		<?php //wp_enqueue_script( 'libsyn-playlist-post-page', plugins_url(LIBSYN_DIR.'/lib/js/libsyn_playlist_post_page.js')); ?>
		<?php wp_enqueue_script( 'libsyn-player-settings-post-page', plugins_url(LIBSYN_DIR.'/lib/js/libsyn_player_settings_post_page.js')); ?>
		<?php IF($render): ?>
		<?php wp_enqueue_script( 'jquery-filestyle', plugins_url(LIBSYN_DIR.'/lib/js/jquery-filestyle.min.js')); ?>
		<?php wp_enqueue_style( 'jquery-filestyle', plugins_url(LIBSYN_DIR.'/lib/css/jquery-filestyle.css')); ?>
		<?php wp_enqueue_style( 'jquery-simplecombobox', plugins_url(LIBSYN_DIR.'/lib/css/jquery.libsyn-scombobox.min.css')); ?>
		<?php wp_enqueue_script( 'jquery-simplecombobox', plugins_url(LIBSYN_DIR.'/lib/js/jquery.libsyn-scombobox.min.js')); ?>
		<?php wp_enqueue_style( 'metaForm', '/wp-content/plugins/'.LIBSYN_DIR.'/lib/css/libsyn_meta_boxes.css' ); ?>

		<div id="libsyn-playlist-page-dialog" class="hidden" title="Create Podcast Playlist">
			<p>
				<span style="font-weight:bold;">Playlist Type:</span><br>
				<input type="radio" name="playlist-media-type" value="audio" id="playlist-media-type-audio" checked="checked">Audio
				<input type="radio" name="playlist-media-type" value="video" id="playlist-media-type-video">Video
				<div style="padding:5px;display:none;" id="playlist-dimensions-div">
					<label for="playlist-video-width">Width </label>
					<input name="playlist-video-width" id="playlist-video-width" type="text" value="640">
					<br>
					<label for="playlist-video-height">Height</label>
					<input name="playlist-video-height" id="playlist-video-height" type="text" value="360">
				</div>
				<br><span style="font-weight:bold;">Playlist Source:</span><br>
				<input type="radio" name="playlist-feed-type" value="<?php if(isset($api)&&($api!==false)) _e("libsyn-podcast-".$api->getShowId());else _e("my-podcast"); ?>" id="my-podcast" checked="checked">My Libsyn Podcast
				<br>
				<input type="radio" name="playlist-feed-type" value="other-podcast" id="other-podcast">Other Podcast
				<label for="<?php _e( 'podcast-url', 'playlist-dialog' ); ?>"><?php _e( 'Podcast Url:' ); ?></label>
				<input class="widefat" id="<?php _e( "podcast-url", 'playlist-dialog' ); ?>" name="<?php _e( "podcast-url", 'playlist-dialog' ) ?>" type="text" value="<?php _e(esc_attr( get_post_meta( $object->ID, 'playlist-podcast-url', true ) )); ?>" type="url" style="display:none;" class="other-url" placeholder="http://www.your-wordpress-site.com/rss">
			</p>
		</div>
		<div id="libsyn-player-settings-page-dialog" class="hidden" title="Playlist Settings"></div>
		<?php //handle post error message
		
		//remove post error if any
		$error = get_post_meta($object->ID, 'libsyn-post-error', true);
		if($error == 'true') {
			add_filter('post_updated_messages', function($messages) {
				$messages['post'][2] = "There was an error posting content, please check settings and try again.";
				return $messages;
			});
		}
		delete_post_meta($object->ID, 'libsyn-post-error', 'true', true);
		?>
		<script type="text/javascript">
			(function ($){
				$(document).ready(function() {
					
					window.libsyn_site_url = "<?php _e(get_site_url()); ?>";
					window.libsyn_data_id =  '<?php _e($object->ID) ?>';
					
					if("<?php _e($refreshTokenProblem)?>" == "true") {
						$('.api-problem-box').fadeIn('normal');
						$('.libsyn-post-form').hide();
					}
					var data = '<?php _e($object->ID) ?>';
					$('.loading-libsyn-form').fadeIn('normal');
					$('.libsyn-post-form').hide();
					$.ajax({
						url: '<?php _e(get_site_url().'/?load_libsyn_media=1') ?>',
						type: 'POST',
						data: data,
						cache: false,
						dataType: 'json',
						processData: false, // Don't process the files
						contentType: false, // Set content type to false as jQuery will tell the server its a query string request
						success: function(data, textStatus, jqXHR) {
							 if(!data) {
								//Handle errors here
								$('.loading-libsyn-form').hide();
								$('.api-problem-box').fadeIn('normal');
							 } else if(typeof data.error == 'undefined') { //Successful response
								
								//remove ftp/unreleased
								$.ajax({
									url : window.libsyn_site_url + "/?remove_ftp_unreleased=1",
									type: 'POST',
									data: data,
									cache: false,
									dataType: 'json',
									processData: false, // Don't process the files
									contentType: false, // Set content type to false as jQuery will tell the server its a query string request
									success : function(data) {          
										console.log('remove fired! ' + data);
									},
									error : function(request,error)
									{
										//error
										//alert("Request: "+JSON.stringify(request));
									}
								});
								
								//show div & hide spinner
								$('.loading-libsyn-form').hide();
								$('.libsyn-post-form').fadeIn('normal');								
								$("#libsyn-categories").empty();
								
								
								//handle categories section
								for(i = 0; i < data.length; i++) {
									if(i==0) { var firstValue = data[i]; }
									$("#libsyn-categories").append("<option value=\"" + data[i] + "\">" + data[i] + "</option>");
								}

								var savedCategory = "<?php echo esc_attr( get_post_meta( $object->ID, 'libsyn-post-episode-category-selection', true ) ); ?>";
								if(savedCategory.length > 0) var firstValue = savedCategory;
								//$("#libsyn-post-episode-category-selection").val(firstValue);
								$("#libsyn-categories").scombobox({
									highlight: true,
									highlightInvalid: false
								});
								$("#libsyn-post-episode-category-selection").attr({'class': 'scombobox-value'}).appendTo($("#libsyn-categories"));
								$("input.scombobox-display").val(firstValue);
								$('.scombobox-value[name=libsyn-post-episode-category]').val(firstValue);
								
								
								$("#libsyn-categories").scombobox('change', function() {
									<?php /*
									$("#libsyn-categories .scombobox-value").val($("#libsyn-categories .scombobox-display").val());
									console.log("change fired");
									console.log("whole val: " + $("#libsyn-categories").scombobox("val"));
									console.log("text val: " + $("#libsyn-categories .scombobox-display").val());
									*/ ?>
									$("#libsyn-post-episode-category-selection").val($("#libsyn-categories .scombobox-display").val());
									
								/*	$("#libsyn-post-episode-category-selection").val($("#libsyn-categories .scombobox-display").val());
									//console.log($("#libsyn-categories").scombobox('val'), $(this).val(), $("#libsyn-post-episode-category-selection").val(), $('#libsyn-categories').children('.scombobox-display').val()); */
								});
																
								$('#libsyn-categories').children('.scombobox-display').focus(function(){
									$(this).css({'border': '1px solid #60a135'});
									$('.scombobox-dropdown-background').css({'border-color': '#60a135 #60a135 #60a135 -moz-use-text-color', 'border': '1px solid #60a135'});
								}).on("blur", function() {
									$(this).css({'border': '1px solid #CCC'});
									$('.scombobox-dropdown-background').css({'border': '1px solid #CCC', 'border-color': '#ccc #ccc #ccc -moz-use-text-color'});
									var currVal = $("#libsyn-categories .scombobox-display").val();
									var sel = $('#libsyn-categories select');
									var opt = $('<option>').attr('value', currVal).html(currVal);
									sel.append(opt);
								});

							 } else {
								//Handle errors here
								$('.loading-libsyn-form').hide();
								$('.libsyn-post-form').fadeIn('normal');
								$('.options-error').fadeIn('normal');
								//$('.api-problem-box').fadeIn('normal');
							 }
						},
						error: function(jqXHR, textStatus, errorThrown) {
							// Handle errors here
							$('.loading-libsyn-form').hide();
							$('.configuration-problem').fadeIn('normal');
						}
					});
										
					//Load Player Settings
					$("#libsyn-player-settings-page-dialog").load("<?php _e(get_site_url()); ?>/wp-content/plugins/<?php _e(LIBSYN_DIR); ?>/admin/views/box_playersettings.php", function() {
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
						<?php
							$postPlayerUseThumbnail = get_post_meta( $object->ID, 'libsyn-post-episode-player_use_thumbnail', true );  
							$playerUseThumbnail = (!is_null($postPlayerUseThumbnail)&&!empty($postPlayerUseThumbnail))?$postPlayerUseThumbnail:get_option('libsyn-podcasting-player_use_thumbnail');
						?>
						var playerUseThumbnail = '<?php _e($playerUseThumbnail); ?>';
						if(playerUseThumbnail == 'use_thumbnail') {
							$('#player_use_thumbnail').attr('checked', true);
						}
						
						//set default value of player theme
						<?php
							$postPlayerTheme = get_post_meta( $object->ID, 'libsyn-post-episode-player_use_theme', true );  
							$playerTheme = (!is_null($postPlayerTheme)&&!empty($postPlayerTheme))?$postPlayerTheme:get_option('libsyn-podcasting-player_use_theme');
						?>
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
						<?php
							$postPlayerHeight = get_post_meta( $object->ID, 'libsyn-post-episode-player_height', true );  
							$playerHeight = (!is_null($postPlayerHeight)&&!empty($postPlayerHeight))?$postPlayerHeight:get_option('libsyn-podcasting-player_height'); 
						?>
						<?php
							$postPlayerWidth = get_post_meta( $object->ID, 'libsyn-post-episode-player_width', true );  
							$playerWidth = (!is_null($postPlayerWidth)&&!empty($postPlayerWidth))?$postPlayerWidth:get_option('libsyn-podcasting-player_width'); 
						?>
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
						<?php
							$postPlayerPlacement = get_post_meta( $object->ID, 'libsyn-post-episode-player_placement', true );  
							$playerPlacement = (!is_null($postPlayerPlacement)&&!empty($postPlayerPlacement))?$postPlayerPlacement:get_option('libsyn-podcasting-player_placement');
						?>
						var playerPlacement = '<?php _e($playerPlacement); ?>';
						if(playerPlacement == 'top') {
							$('#player_placement_top').attr('checked', true);
						} else if(playerPlacement == 'bottom') {
							$('#player_placement_bottom').attr('checked', true);
						} else { //player placement is not set
							$('#player_placement_top').attr('checked', true);
						}
						
						
					});
		
					$( "#libsyn-upload-media-dialog" ).dialog({
						autoOpen: false,
						draggable: false,
						height: 'auto',
						width: 500,
						modal: true,
						resizable: false,
						open: function(){
							$('.ui-widget-overlay').bind('click',function(){
								$('#libsyn-upload-media-dialog').dialog('close');
							})
						},
						buttons: [
							{
								id: "dialog-button-cancel",
								text: "Cancel",
								click: function(){
									$('#libsyn-upload-media-dialog').dialog('close');
								}
							},
							{
								id: "dialog-button-upload",
								text: "Upload",
								class: "button-primary",
								click: function(){
									$('#dialog-button-upload').attr("disabled", true);
									$('.upload-error-dialog').empty().append('<img id="upload-dialog-spinner" src="<?php _e(plugins_url(LIBSYN_DIR.'/lib/images/upload-spinner.gif')) ?>"></img>').fadeIn('normal');
									var dlg = $(this);
									var url = "<?php _e($plugin->getApiBaseUri().'/media')?>";
									var mediaUploadForm = new FormData();
									mediaUploadForm.append('show_id', '<?php if(isset($api)) _e($api->getShowId()) ?>');
									mediaUploadForm.append('form_access_token', '<?php if(isset($api)) _e($api->getAccessToken()) ?>');
									mediaUploadForm.append('upload', $('#libsyn-media-file-upload')[0].files[0]);
									$.ajax({
										url: url,
										type: 'POST',
										data: mediaUploadForm,
										processData: false,
										contentType: false,
										success: function (response, textStatus, xhr) {
												$("#libsyn-new-media-media").val("libsyn-upload-" + response._embedded.media.content_id).attr("readonly", true);
												dlg.dialog('close');
												//dlg.empty();
										},
										 error: function (xhr, status, error) {
											if(xhr.responseJSON.validation_messages.upload.length >= 0) {
												var stringError = xhr.responseJSON.validation_messages.upload;
												$('.upload-error-dialog').empty().append(
													"Error Uploading:  " + xhr.responseJSON.validation_messages.upload
												);
											} else {
												$('.upload-error-dialog').empty().append("Error Uploading:  " + error);
											}
											//$('.upload-error').fadeIn('normal');
											
											$('#upload-dialog-spinner').hide();
											$('#dialog-button-upload').attr("disabled", false);
											$('.upload-error-dialog').fadeIn('normal');
										}
									});
								}
							}
						]
					});	
					$( "#libsyn-upload-asset-dialog" ).dialog({
						autoOpen: false,
						draggable: false,
						height: 'auto',
						width: 'auto',
						modal: true,
						resizable: false,
						open: function(){
							$('.ui-widget-overlay').bind('click',function(){
								$('#libsyn-upload-asset-dialog').dialog('close');
							})
						},
						buttons: [
							{
								id: "dialog-button-cancel",
								text: "Cancel",
								click: function(){
									$('#libsyn-upload-asset-dialog').dialog('close');
								}
							}
						]
					});

					$('#libsyn-upload-media').click(function(event) {
						event.preventDefault();
						$("#libsyn-upload-media-dialog").dialog( "open" );
					});
					
					$('#libsyn-clear-media-button').click(function(event) {
						event.preventDefault();
						$("#libsyn-new-media-media").val('').attr('readonly', false);
					});
					
					$('#libsyn-clear-image-button').click(function(event) {
						event.preventDefault();
						$("#libsyn-new-media-image").val('').attr('readonly', false);
					});
					
					//set tvrating
					if("<?php _e(esc_attr( get_post_meta( $object->ID, 'libsyn-post-episode-tvrating', true ) )); ?>" != "")
						$("#libsyn-post-episode-tvrating").val("<?php _e(esc_attr( get_post_meta( $object->ID, 'libsyn-post-episode-tvrating', true ) )); ?>");
					if("<?php _e(esc_attr( get_post_meta( $object->ID, 'libsyn-post-episode-itunes', true ) )); ?>" != "")
						$("#libsyn-post-episode-itunes").val("<?php _e(esc_attr( get_post_meta( $object->ID, 'libsyn-post-episode-itunes', true ) )); ?>");
					if("<?php _e(esc_attr( get_post_meta( $object->ID, 'libsyn-post-episode', true ) )); ?>" == "isLibsynPost")
						$("#libsyn-post-episode").prop("checked", true);
					

		
				});
			}) (jQuery);		
		</script>
		<?php ENDIF; ?>
		<div class="loading-libsyn-form" style="background: url(<?php _e(plugins_url(LIBSYN_DIR.'/lib/images/spinner.gif')) ?>);background-repeat: no-repeat;background-position: left center;display: none;"><br><br><br><br>Loading...</div>
		<div class="configuration-problem" style="display: none;">
			<p>Please configure your <a href="<?php _e(get_site_url()); ?>/wp-admin/admin.php?page=<?php _e(LIBSYN_DIR); ?>/admin/settings.php">Libsyn Podcast Plugin</a> with your Libsyn Hosting account to use this feature.</p>
		</div>
		<?php if($isPowerpress){ ?>
		<div class="configuration-problem-powerpress" style="border: 1px solid red;">
			<p style="color:red;font-weight:bold;padding-left:10px;">You Currently have 'Powerpress Plugin' installed.
			<br>Please visit the <a href="<?php _e(get_site_url()); ?>/wp-admin/admin.php?page=<?php _e(LIBSYN_DIR); ?>/admin/settings.php">settings</a> and make any configuration changes before posting.  (note: The Libsyn plugin will conflict with this plugin)</p>
		</div>		
		<?php } ?>
		<div class="api-problem-box" style="display: none;">
			<p> We encountered a problem with the Libsyn API.  Please Check your <a href="<?php _e(get_site_url()); ?>/wp-admin/admin.php?page=<?php _e(LIBSYN_DIR); ?>/admin/settings.php">settings</a> and try again.</p>
		</div>
		<div class="libsyn-post-form">
			<table class="form-table">
				<tr valign="top">
					<p><strong><?php echo __( 'The post title and post body above will be used for your podcast episode.', 'libsyn-nmp' ); ?></strong></p>
				</tr>
				<tr valign="top">
					  <?php $isLibsynPostChecked = ( get_post_meta($object->ID, '_isLibsynPost', true) )?' checked="checked"':''; ?>
					  <th><label for="libsyn-post-episode"><?php _e( "Post Libsyn Episode", 'example' ); ?></label></th>
					  <td>
						<input type="checkbox" name="libsyn-post-episode" id="libsyn-post-episode" value="isLibsynPost" <?php echo $isLibsynPostChecked ?>/>
					  </td>
				</tr>
				<tr valign="top">
					  <th><?php _e("Episode Media*", 'libsyn-post-episode-media'); ?></th>
					  <td>
						<div id="libsyn-primary-media-settings">
							<div id="libsyn-new-media-settings">
								<div class="upload-error" style="display:none;color:red;font-weight:bold;">There was an error uploading media, please check settings and try again.</div>
								<p><?php echo __( 'Select Primary Media for Episode by clicking the button below.', 'libsyn-nmp' ); ?></p>
								<p>
									<button class="button button-primary" id="libsyn-upload-media" title="<?php echo esc_attr__( 'Click here to upload media for episode', 'libsyn-nmp' ); ?>"><?php echo __( 'Upload Media', 'libsyn-nmp' ); ?></button>
									<a href="#" class="libsyn-open-media button button-primary" title="<?php echo esc_attr__( 'Click Here to Open the Media Manager', 'libsyn-nmp' ); ?>"><?php echo __( 'Select Wordpress Media', 'libsyn-nmp' ); ?></a>
									<a href="#" class="libsyn-open-ftp_unreleased button button-primary" title="<?php echo esc_attr__( 'Click Here to Open the Media Manager', 'libsyn-nmp' ); ?>"><?php echo __( 'Select ftp/unreleased', 'libsyn-nmp' ); ?></a>
								</p>
								<p>
								<?php $libsyn_media_media = get_post_meta( $object->ID, 'libsyn-new-media-media', true ); ?>
								<label for="libsyn-new-media-media"><?php echo __( 'Media Url', 'libsyn-nmp' ); ?></label> <input type="url" id="libsyn-new-media-media" name="libsyn-new-media-media" size="70" value="<?php echo esc_attr( $libsyn_media_media ); ?>" pattern="https?://.+" <?php if(isset($libsyn_media_media)&&!empty($libsyn_media_media)) echo 'readonly'; ?>/>
								<button class="button" id="libsyn-clear-media-button" title="<?php echo esc_attr__( 'Clear primary media', 'libsyn-nmp' ); ?>"><?php echo __( 'Clear', 'libsyn-nmp' ); ?></button>
								</p>
							</div>
							<div id="libsyn-upload-media-dialog" class="hidden" title="Upload Media">
								<h3>Select Media to upload:</h3>
								<input id="libsyn-media-file-upload" type="file" name="upload" class="jfilestyle" data-buttonText="Choose Media" data-size="300px">
								<div class="upload-error-dialog" style="display:none;color:red;font-weight:bold;"></div>
							</div>
						</div>
					  </td>
				</tr>
				<tr valign="top">
					  <th><?php _e("Episode Subtitle", 'libsyn-post-episode-subtitle'); ?></th>
					  <td>
						<div id="titlediv">
							<div id="titlewrap">
								<input id="libsyn-post-episode-subtitle" type="text" autocomplete="off" value="<?php echo get_post_meta( $object->ID, 'libsyn-post-episode-subtitle', true ); ?>" size="30" name="libsyn-post-episode-subtitle" style="width:100%;" maxlength="255">
							</div>
						</div>
					  </td>
				</tr>
				<tr valign="top">
					  <th><?php _e("Episode Category*", 'libsyn-post-episode-category-selection'); ?></th>
					  <td>
						<div id="titlediv">
							<div id="titlewrap">
								<!-- TODO: Change to selectable text for list of show's categories refer datalist-->
								<div class="options-error" style="display:none;color:red;font-weight:bold;">Could not populate categories, manually enter category.</div>
								<select id="libsyn-categories" name="libsyn-post-episode-category">
									<option value="general">general</option>
								</select>
								<input type="hidden" value="<?php echo get_post_meta( $object->ID, 'libsyn-post-episode-category-selection', true ); ?>" name="libsyn-post-episode-category-selection" id="libsyn-post-episode-category-selection" />
							</div>
						</div>
					  </td>
				</tr>		
				<tr valign="top">
					  <th><?php _e("Episode Thumbnail", 'libsyn-post-episode-media'); ?></th>
					  <td>
						<div id="libsyn-primary-media-settings">
							<div id="libsyn-new-media-settings">
								<p><?php echo __( 'Select image for episode thumbnail by clicking the button below.', 'libsyn-nmp' ); ?></p>
								<p>
								<?php $libsyn_episode_thumbnail = esc_attr( get_post_meta( $object->ID, 'libsyn-new-media-image', true ) ); ?>
								<a href="#" class="libsyn-open-image button button-primary" title="<?php echo esc_attr__( 'Click Here to Open the Image Manager', 'libsyn-nmp' ); ?>"><?php echo __( 'Select Episode Thumbnail', 'libsyn-nmp' ); ?></a></p>
								<p><label for="libsyn-new-media-image"><?php echo __( 'Media Url', 'libsyn-nmp' ); ?></label> <input type="url" id="libsyn-new-media-image" name="libsyn-new-media-image" size="70" value="<?php echo (!empty($libsyn_episode_thumbnail))?$libsyn_episode_thumbnail:''; ?>" pattern="https?://.+" <?php if(isset($libsyn_episode_thumbnail)&&!empty($libsyn_episode_thumbnail)) echo 'readonly';?>/>
								<button class="button" id="libsyn-clear-image-button" title="<?php echo esc_attr__( 'Clear image url', 'libsyn-nmp' ); ?>"><?php echo __( 'Clear', 'libsyn-nmp' ); ?></button>
								</p>
							</div>
							<div id="libsyn-upload-asset-dialog" class="hidden" title="Upload Image">
								<p>Select Image to upload:</p>
								<br>
							</div>
						</div>
					  </td>	
				</tr>
				<tr valign="top">
					  <th><?php _e("Tags/Keywords", 'libsyn-post-episode-keywords'); ?></th>
					  <td>
						<div id="titlediv">
							<div id="titlewrap">
								<input id="libsyn-post-episode-keywords" type="text" autocomplete="off" value="<?php echo get_post_meta( $object->ID, 'libsyn-post-episode-keywords', true ); ?>" size="30" name="libsyn-post-episode-keywords" style="width:100%;" maxlength="255" placeholder="keyword1, keyword2, keyword3">
							</div>
						</div>
					  </td>
				</tr>
				<tr valign="top">
					  <th><?php _e("Rating", 'libsyn-post-episode-rating'); ?></th>
					  <td>
						<div id="titlediv">
							<div id="titlewrap">
								<select id="libsyn-post-episode-itunes" name="libsyn-post-episode-itunes">
									<option value="no">Not Set</option>
									<option value="clean">Clean</option>
									<option value="yes">Explicit</option>
								</select>	
							</div>
						</div>
					  </td>
				</tr>
				<tr valign="top">
					  <th><?php _e("TV Rating", 'libsyn-post-episode-rating'); ?></th>
					  <td>
						<div id="titlediv">
							<div id="titlewrap">
								<select id="libsyn-post-episode-tvrating" name="libsyn-post-episode-tvrating">
									<option value="no">Not Set</option>
									<option value="TV-Y">TV-Y</option>
									<option value="TV-Y7">TV-Y7</option>
									<option value="TV-14">TV-14</option>
									<option value="TV-G">TV-G</option>
									<option value="TV-PG">TV-PG</option>
									<option value="TV-MA">TV-MA</option>
								</select>									
							</div>
						</div>
					  </td>
				</tr>
				<tr valign="top">
					  <th><?php _e("TV Rating Contains", 'libsyn-post-episode-rating'); ?></th>
					  <td>
						<div id="titlediv">
							<div id="titlewrap">
								
								<input type="checkbox" id="libsyn-post-episode-tvrating-contains-courseLanguage" name="libsyn-post-episode-tvrating-contains-courseLanguage" value="Course Language" <?php if (self::checkFormItem($object->ID,'tvrating-contains','courseLanguage')) echo 'checked="checked"';?>>Course Language<br>
								<input type="checkbox" id="libsyn-post-episode-tvrating-contains-sexualContent" name="libsyn-post-episode-tvrating-contains-sexualContent" value="Sexual Content" <?php if (self::checkFormItem($object->ID,'tvrating-contains','sexualContent')) echo 'checked="checked"';?>>Sexual Content<br>
								<input type="checkbox" id="libsyn-post-episode-tvrating-contains-suggestiveDialogue" name="libsyn-post-episode-tvrating-contains-suggestiveDialogue" value="Suggestive Dialogue" <?php if (self::checkFormItem($object->ID,'tvrating-contains','suggestiveDialogue')) echo 'checked="checked"';?>>Suggestive Dialogue<br>
								<input type="checkbox" id="libsyn-post-episode-tvrating-contains-violence" name="libsyn-post-episode-tvrating-contains-violence" value="Violence" <?php if (self::checkFormItem($object->ID,'tvrating-contains','violence')) echo 'checked="checked"';?>>Violence
							</div>
						</div>	
					  </td>
				</tr>
			
			</table>
		</div>
		</p>
		<?php 
		
	}
	
    /**
     * simple function checks the camel case of a form name prefix "libsyn-post-episode"
     * 
	 * @pram  <int> $id  WP post id ($object->ID)
     * @param <string> $prefix 
     * @param <type> $camelCaseName 
     * 
     * @return <type>
     */
	public static function checkFormItem($id,$prefix, $camelCaseName) {
		$cc_text = preg_replace(array('/(?<=[^A-Z])([A-Z])/', '/(?<=[^0-9])([0-9])/'), ' $0', $camelCaseName);
		$cc_text = ucwords($cc_text);
		$check = esc_attr( get_post_meta( $id, 'libsyn-post-episode-'.$prefix.'-'.$camelCaseName, true ) );
		if(!empty($check)&&$check==$cc_text) return true; else return false;
	}
	
    /**
     * Handles the post data fields from addLibsynPostMeta
     * 
     * @param <int> $post_id 
     * @param <WP_Post> $post 
     * 
     * @return <bool>
     */
	public static function handlePost ($post_id, $post) {
		if (isset($post->post_status)&&'auto-draft'==$post->post_status) return;
		
		/* Verify the nonce before proceeding. */
		if (!isset($_POST['libsyn_post_episode_nonce'])||!wp_verify_nonce($_POST['libsyn_post_episode_nonce'], basename( __FILE__ ))) return $post_id;
		
		/* Get the post type object. */
		$post_type = get_post_type_object($post->post_type);
		
		/* Check if the current user has permission to edit the post. */
		if ( !current_user_can($post_type->cap->edit_post, $post_id))
			return $post_id;
		
		/* Get the posted data and sanitize it for use as an HTML class. */
		
		$new_meta_values = array();
		$new_meta_values['libsyn-post-episode'] = (isset($_POST['libsyn-post-episode'])?$_POST['libsyn-post-episode']:'');
		$new_meta_values['libsyn-new-media-media'] = (isset($_POST['libsyn-new-media-media'])?$_POST['libsyn-new-media-media']:'');
		$new_meta_values['libsyn-post-episode-subtitle'] = (isset($_POST['libsyn-post-episode-subtitle'])?$_POST['libsyn-post-episode-subtitle']:'');
		$new_meta_values['libsyn-post-episode-category-selection'] = (isset($_POST['libsyn-post-episode-category-selection'])?$_POST['libsyn-post-episode-category-selection']:'');
		$new_meta_values['libsyn-new-media-image'] = (isset($_POST['libsyn-new-media-image'])?$_POST['libsyn-new-media-image']:'');
		$new_meta_values['libsyn-post-episode-keywords'] = (isset($_POST['libsyn-post-episode-keywords'])?$_POST['libsyn-post-episode-keywords']:'');
		$new_meta_values['libsyn-post-episode-itunes'] = (isset($_POST['libsyn-post-episode-itunes'])?$_POST['libsyn-post-episode-itunes']:'');
		$new_meta_values['libsyn-post-episode-tvrating'] = (isset($_POST['libsyn-post-episode-tvrating'])?$_POST['libsyn-post-episode-tvrating']:'');
		$new_meta_values['libsyn-post-episode-tvrating-contains-courseLanguage'] = (isset($_POST['libsyn-post-episode-tvrating-contains-courseLanguage'])?$_POST['libsyn-post-episode-tvrating-contains-courseLanguage']:'');
		$new_meta_values['libsyn-post-episode-tvrating-contains-sexualContent'] = (isset($_POST['libsyn-post-episode-tvrating-contains-sexualContent'])?$_POST['libsyn-post-episode-tvrating-contains-sexualContent']:'');
		$new_meta_values['libsyn-post-episode-tvrating-contains-suggestiveDialogue'] = (isset($_POST['libsyn-post-episode-tvrating-contains-suggestiveDialogue'])?$_POST['libsyn-post-episode-tvrating-contains-suggestiveDialogue']:'');
		$new_meta_values['libsyn-post-episode-tvrating-contains-violence'] = (isset($_POST['libsyn-post-episode-tvrating-contains-violence'])?$_POST['libsyn-post-episode-tvrating-contains-violence']:'');
		//player settings
		if (isset($_POST['player_use_thumbnail'])) {
			if(!empty($_POST['player_use_thumbnail'])&&$_POST['player_use_thumbnail']==='use_thumbnail') {
				$new_meta_values['libsyn-post-episode-player_use_thumbnail'] = $_POST['player_use_thumbnail'];
			} elseif(empty($_POST['player_use_thumbnail'])) {
				$new_meta_values['libsyn-post-episode-player_use_thumbnail'] = 'none';
			}
		} else {
			$new_meta_values['libsyn-post-episode-player_use_thumbnail'] = get_option('libsyn-podcasting-player_use_thumbnail');
			if(empty($new_meta_values['libsyn-post-episode-player_use_thumbnail'])) $new_meta_values['libsyn-post-episode-player_use_thumbnail'] = 'none';
		}
		$new_meta_values['libsyn-post-episode-player_use_theme'] = (isset($_POST['player_use_theme']))?$_POST['player_use_theme']:get_option('libsyn-podcasting-player_use_theme');
		$new_meta_values['libsyn-post-episode-player_width'] = (isset($_POST['player_width']))?$_POST['player_width']:get_option('libsyn-podcasting-player_width');
		$new_meta_values['libsyn-post-episode-player_height'] = (isset($_POST['player_height']))?$_POST['player_height']:get_option('libsyn-podcasting-player_height');
		$new_meta_values['libsyn-post-episode-player_placement'] = (isset($_POST['player_placement']))?$_POST['player_placement']:get_option('libsyn-podcasting-player_placement');
		self::handleMetaValueArray($post_id, $new_meta_values);

		/* Call Post to Libsyn based on post_status */
		try{
			switch($post->post_status) {
				case 'future':
					self::postEpisode($post, true);
					break;
					
				case 'draft':
					self::postEpisode($post, false, true);
					break;
					
				case 'pending':
					//echo("Pending, not sure where to do here");exit;
					break;
					
				case 'private':
					//echo("We do not handle private");exit;
					break;
					
				case 'publish':
					self::postEpisode($post);
					break;
					
				default:
					return;
			}
		} catch (Exception $e) {
			echo $e->getMessage();
		}
	}
	
	
    /**
     * Handle meta values based on the way they are setup in array.
	 * see (array) $new_meta_values
     * 
     * @param <array> $new_meta_values
     * 
     * @return <type>
     */
	public static function handleMetaValueArray($post_id, $new_meta_values) {
		/* If a new meta value was added and there was no previous value, add it. */
		foreach ($new_meta_values as $key => $val) {
			$meta_value = get_post_meta($post_id, $key, true);
			$sanitize = new \Libsyn\Service\Sanitize();
			if(!isset($url)) $url = '';
			 //sanitize value
			if($key==='libsyn-new-media-image') $clean_val = $sanitize->url_raw($val);
				elseif($key==='libsyn-new-media-media'&&(strpos($val, 'libsyn-ftp-')===false||strpos($url, 'libsyn-upload-')===false)) $clean_val = $sanitize->url_raw($val);
					else $clean_val = $sanitize->text($val);
			if (!empty($clean_val)&&empty($meta_value)) // no meta_value so create
				add_post_meta($post_id, $key, $clean_val, true);
			elseif (!empty($clean_val)&&$clean_val!==$meta_value) //doesn't match old value so update
				update_post_meta($post_id, $key, $clean_val);
			elseif (empty($clean_val)&&!empty($meta_value)) //old value doesn't exist, delete it
				delete_post_meta($post_id, $key, $meta_value);
		}
	}
	
	
	
    /**
     * Attaches form field values
     * 
     * @param <array> $form_fields 
     * @param <WP_Post> $post 
     * 
     * @return <type>
     */
	public static function attachFieldsToEdit($form_fields, $post) {
		$field_value = get_post_meta($post->ID, 'location', true);
		$form_fields['location'] = array(
			'value' => $field_value ? $field_value : '',
			'label' => __( 'Location' ),
			'helps' => __( 'Set a location for this attachment' ),
		);
		return $form_fields;
	}

    /**
     * Handles the Meta post box classes
     * 
     * @param <mixed> $classes 
     * 
     * @return <mixed>
     */
	public static function metaPostClasses ($classes) {
		/* Get the current post ID. */
		$post_id = get_the_ID();

		/* If we have a post ID, proceed. */
		if ( !empty( $post_id ) ) {
			$post_class = get_post_meta( $post_id, 'libsyn_post_episode', true );
			if ( !empty( $post_class ) ) $classes[] = sanitize_html_class( $post_class );
		}
		return $classes;		
	
	}
	
    /**
     * Main Post script which handles Libsyn API posting. Used for post scheduled/immediate post.
     * 
     * @param <WP_Post> $post 
     * @param <int> $post_id 
     * @param <bool> $schedule 
     * @param <bool> $draft 
     * 
     * @return <Libsyn_Item|mixed>
     */
	public static function postEpisode($post, $isSchedule=false, $isDraft=false) {
		/* Back out quickly if the post to libsyn is not checked */
		if(get_post_meta($post->ID, 'libsyn-post-episode', true)!=='isLibsynPost') return;
		$plugin = new Service();
		$api = $plugin->getApis();
		
		//testing
		
		//get post player settings
		$playerSettings = array();
		$playerSettings['player_use_thumbnail'] = get_option('libsyn-podcasting-player_use_thumbnail');
		$playerSettings['player_use_theme'] = get_option('libsyn-podcasting-player_use_theme');
		$playerSettings['player_height'] = get_option('libsyn-podcasting-player_height');
		$playerSettings['player_width'] = get_option('libsyn-podcasting-player_width');
		$playerSettings['player_placement'] = get_option('libsyn-podcasting-player_placement');
		

		//Create item API array
		$item = array();
		$item['show_id'] = $api->getShowId();
		$item['item_title'] = $post->post_title;
		$item['item_subtitle'] = get_post_meta($post->ID, 'libsyn-post-episode-subtitle', true);
		$item['thumbnail_url'] = get_post_meta($post->ID, 'libsyn-new-media-image', true);
		$item['item_body'] = $content = wp_kses_post(self::stripShortcode('podcast', self::stripShortcode('podcast', $post->post_content)));
		$item['item_category'] = get_post_meta($post->ID, 'libsyn-post-episode-category-selection', true);
		$item['itunes_explicit'] = get_post_meta($post->ID, 'libsyn-post-episode-itunes', true);
		if($item['itunes_explicit']==='explicit') $item['itunes_explicit'] = 'yes';
		$item['tv_rating'] = get_post_meta($post->ID, 'libsyn-post-episode-tvrating', true);
		if($item['tv_rating']==='no')$item['tv_rating'] = '';
		//$item['tv_subrating'] = get_post_meta($post->ID, 'libsyn-post-episode-tvrating-contains', true);
		$item['item_keywords'] = get_post_meta($post->ID, 'libsyn-post-episode-keywords', true);
		
		//player settings //post params are height(int),theme(standard,mini),width(int)
		$item['height'] = get_post_meta($post->ID, 'libsyn-post-episode-player_height', true);
		$item['width'] = get_post_meta($post->ID, 'libsyn-post-episode-player_width', true);
		$item['theme'] = get_post_meta($post->ID, 'libsyn-post-episode-player_use_theme', true);

		//handle primary content
		$url = get_post_meta($post->ID, 'libsyn-new-media-media', true);
		if(strpos($url, 'libsyn-ftp-')!==false) $content_id = str_replace('http:', '', str_replace('https:', '', str_replace('/', '', str_replace('libsyn-ftp-', '', $url))));
		if(strpos($url, 'libsyn-upload-')!==false) $content_id = str_replace('http:', '', str_replace('https:', '', str_replace('/', '', str_replace('libsyn-upload-', '', $url))));
		if(isset($content_id)&&is_numeric($content_id)) { //then is ftp/unreleased
			$item['primary_content_id'] = intval($content_id);
		} elseif(!empty($url)) { //is regular
			$sanitize = new \Libsyn\Service\Sanitize();
			$item['primary_content_url'] = $sanitize->url_raw($url);
		} else {
			//throw new Exception('Primary media error, please check your Libsyn settings.');
		}

		//get destinations
		$destinations = $plugin->getDestinations($api);
		//TODO: commenting out all Exceptions put in place a logging system.
		//if(!$destinations) throw new Exception('Error using the Libsyn API, please try again later.');
		//TODO: Handle validation error on bad api call for destinations.

		if($isSchedule) $releaseDate = $post->post_date_gmt;
			else $releaseDate = 'now';
		
		if($isDraft) $item['is_draft'] = 'true';
			else $item['is_draft'] = 'false';
		
		$item['releases'] = array();
		foreach($destinations->destinations as $destination) {
			if($destination->destination_type!=='WordPress') {
				$item['releases'][] = array(
					'destination_id'	=>	$destination->destination_id,
					'release_date'		=>	$releaseDate,
					//'expires'			=> $expiresDate, //TODO: Perhaps add expires for posts eventually (optional feature)
				);
			}
		}
	
		//is this post an update or new?
		$wp_libsyn_item_id = get_post_meta( $post->ID, 'libsyn-item-id', true );
		$isUpdatePost = (empty($wp_libsyn_item_id))?false:true;
		if($isUpdatePost) $item['item_id'] = $wp_libsyn_item_id;

		//run post
		$libsyn_post = $plugin->postPost($api, $item);
		if($libsyn_post!==false) {
			self::updatePost($post, $libsyn_post, $isUpdatePost);
		} else  { add_post_meta($post->ID, 'libsyn-post-error', 'true', true); }
	}
	
    /**
     * Temp change global state of WP to fool shortcode
     * 
     * @param <string> $code name of the shortcode
     * @param <string> $content 
     * 
     * @return <string> content with shortcode striped
     */
	public static function stripShortcode($code, $content) {
		global $shortcode_tags;

		$stack = $shortcode_tags;
		if($code=="all") $shortcode_tags = array();
			else $shortcode_tags = array($code => 1);

		$content = strip_shortcodes($content);

		$shortcode_tags = $stack;
		return $content;
	}
	
    /**
     * Just updates the WP_Post after a successful Libsyn Episode Post
     * 
     * @param <WP_Post> $post 
     * @param <object> $libsyn_post
     * 
     * @return <bool>
     */
	public static function updatePost($post, $libsyn_post, $isUpdatePost) {
		global $wpdb;
		
		//grab player settings
		$playerHeight = get_post_meta($post->ID, 'libsyn-post-episode-player_height', true);
		$playerWidth = get_post_meta($post->ID, 'libsyn-post-episode-player_width', true);
		$playerPlacement = get_post_meta($post->ID, 'libsyn-post-episode-player_placement', true);
		$playerPlacement = ($playerPlacement==='top')?$playerPlacement:'bottom'; //defaults to bottom

		//update post db
		if(!$isUpdatePost) {
			if($playerPlacement==='top') {
				$wpdb->update(
					$wpdb->prefix . 'posts',
					array(
						'post_content' => '[podcast src="'.$libsyn_post->url.'" height="'.$playerHeight.'" width="'.$playerWidth.'"]<br />'.wp_kses_post(self::stripShortcode('podcast', $post->post_content)),
						'post_modified' => date("Y-m-d H:i:s"),
						'post_modified_gmt' => gmdate("Y-m-d H:i:s"),
					),
					array('ID' => $post->ID)
				);
			} else {
				$wpdb->update(
					$wpdb->prefix . 'posts',
					array(
						'post_content' => wp_kses_post(self::stripShortcode('podcast', $post->post_content)).'<br />[podcast src="'.$libsyn_post->url.'" height="'.$playerHeight.'" width="'.$playerWidth.'"]',
						'post_modified' => date("Y-m-d H:i:s"),
						'post_modified_gmt' => gmdate("Y-m-d H:i:s"),
					),
					array('ID' => $post->ID)
				);				
			}
		} else {
			//shortcode stuff
			$shortcode_pattern = get_shortcode_regex();
			preg_match('/'.$shortcode_pattern.'/s', $post->post_content, $matches);

			if(is_array($matches)) {
				if (isset($matches[2]) && $matches[2] == 'podcast') { // matches (has player shortcode)
					$post_content_text = $post->post_content;
					$podcast_shortcode_text = '[podcast src="'.$libsyn_post->url.'" height="'.$playerHeight.'" width="'.$playerWidth.'"]';
					$new_post_content = str_replace($matches[0], $podcast_shortcode_text, $post_content_text);
					
					$wpdb->update(
						$wpdb->prefix . 'posts',
						array(
							'post_content' => $new_post_content,
							'post_modified' => date("Y-m-d H:i:s"),
							'post_modified_gmt' => gmdate("Y-m-d H:i:s"),
						),
						array('ID' => $post->ID)
					);
				} elseif(!isset($matches[2])) { //somehow doesn't have the player shortcode and is update
					if($playerPlacement==='top') {
						$wpdb->update(
							$wpdb->prefix . 'posts',
							array(
								'post_content' => '[podcast src="'.$libsyn_post->url.'" height="'.$playerHeight.'" width="'.$playerWidth.'"]<br />'.wp_kses_post(self::stripShortcode('podcast', $post->post_content)),
								'post_modified' => date("Y-m-d H:i:s"),
								'post_modified_gmt' => gmdate("Y-m-d H:i:s"),
							),
							array('ID' => $post->ID)
						);
					} else {
						$wpdb->update(
							$wpdb->prefix . 'posts',
							array(
								'post_content' => wp_kses_post(self::stripShortcode('podcast', $post->post_content)).'<br />[podcast src="'.$libsyn_post->url.'" height="'.$playerHeight.'" width="'.$playerWidth.'"]',
								'post_modified' => date("Y-m-d H:i:s"),
								'post_modified_gmt' => gmdate("Y-m-d H:i:s"),
							),
							array('ID' => $post->ID)
						);				
					}					
				}
			}
		}
		update_post_meta($post->ID, 'libsyn-item-id', $libsyn_post->id, true);
	}
	
    /**
     * Handles WP callback to send variable to trigger AJAX response.
     * 
     * @param <array> $vars 
     * 
     * @return <type>
     */
	public static function plugin_add_trigger_load_form_data($vars) {
		$vars[] = 'load_libsyn_media';
		return $vars;
	}
	
    /**
     * Handles WP callback to send variable to trigger AJAX response.
     * 
     * @param <array> $vars 
     * 
     * @return <type>
     */
	public static function plugin_add_trigger_remove_ftp_unreleased($vars) {
		$vars[] = 'remove_ftp_unreleased';
		return $vars;
	}
	
    /**
     * Handle ajax page for loading post page form data
     * 
     * 
     * @return <type>
     */
	public static function loadFormData() {
		if(intval(get_query_var('load_libsyn_media')) == 1&&(current_user_can( 'upload_files' ))&&(current_user_can( 'edit_posts' ))) {
			global $wpdb;
			$error = false;
			$plugin = new Service();
			$api = $plugin->getApis();
			$wpdb->delete($wpdb->prefix . 'posts', array('post_mime_type' => 'libsyn/ftp-unreleased'));
			$wpdb->delete($wpdb->prefix . 'posts', array('post_mime_type' => 'audio/ftp-unreleased'));
			$wpdb->delete($wpdb->prefix . 'posts', array('post_mime_type' => 'video/ftp-unreleased'));

			$wpdb->get_results($wpdb->prepare("DELETE FROM ".$wpdb->prefix."postmeta WHERE meta_value LIKE %s", "/libsyn/ftp-unreleased%"));
			if ($api instanceof Api && !$api->isRefreshExpired()){
				$refreshApi = $api->refreshToken();
				if($refreshApi) { //successfully refreshed
					/* Remove and add FTP/Unreleased Media */
					$ftp_unreleased = $plugin->getFtpUnreleased($api)->{'ftp-unreleased'};
					if(!empty($ftp_unreleased)) {
						foreach($ftp_unreleased as $media) {
							// We need to make sure we are working with only audio/video files...
							if((strpos($media->mime_type, 'audio')!== false)||(strpos($media->mime_type, 'video') !== false)) {
								
								//for new versions of wordpress - handle media info in metadata
								if(strpos($media->mime_type, 'video') !== false) {
									
								} elseif(strpos($media->mime_type, 'audio')!== false) {
									
								} else {
									//neither audio or video
								}
								
								$file_name = explode('.', $media->file_name);
								$mime_type = explode('/', $media->mime_type);
								$data = array(
										'post_author'			=>	get_current_user_id(),
										'post_date'				=>	date("Y-m-d H:i:s"),
										'post_date_gmt'			=>	date("Y-m-d H:i:s"),
										'post_content'			=>	'Libsyn FTP/Unreleased Media: '.$media->file_name,
										'post_title'			=>	$file_name[0],
										'post_excerpt'			=>	'',
										'post_status'			=>	'inherit',
										'comment_status'		=>	'open',
										'ping_status'			=>	'closed',
										'post_password'			=>	'',
										'post_name'				=>	'libsyn-ftp-'.$media->content_id,
										'to_ping'				=>	'',
										'pinged'				=>	'',
										'post_modified'			=>	date("Y-m-d H:i:s"),
										'post_modified_gmt'		=>	date("Y-m-d H:i:s"),
										'post_content_filtered'	=>	'',
										'post_parent'			=>	0,
										'guid'					=>	$media->file_name,
										'menu_order'			=>	0,
										'post_type'				=>	'attachment',
										'post_mime_type'		=>	'libsyn/ftp-unreleased',
										'comment_count'			=>	0,			
								);
								//$wpdb->insert($wpdb->prefix . 'posts', $data);
								$post_id = wp_insert_post($data, false);
								
								//add post meta
								add_post_meta($post_id, '_wp_attached_file', '/libsyn/ftp-unreleased/'.$media->file_name);
							}
						}
					}
					/* Get categories and send output on success */
					$categories = $plugin->getCategories($api)->{'categories'};
					if(!is_array($categories)) $categories = array($categories);
					$json = array();
					foreach($categories as $category)
						if(isset($category->item_category_name)) $json[] = $category->item_category_name;
					//if(empty($json)) $error = true;	
				} else { $error = true; }
			} else { $error = true; }
			//set output
			header('Content-Type: application/json');
			if(!$error) echo json_encode($json);
				else echo json_encode(array());
			exit;
		}
	}
	
    /**
     * Cleares post meta and posts for ftp/unreleased data.
     * 
     * 
     * @return <type>
     */
	public static function removeFTPUnreleased() {
		global $wpdb;
		$error = true;
		if(intval(get_query_var('remove_ftp_unreleased')) === 1) {
			$wpdb->delete($wpdb->prefix . 'posts', array('post_mime_type' => 'libsyn/ftp-unreleased'));
			$wpdb->delete($wpdb->prefix . 'posts', array('post_mime_type' => 'audio/ftp-unreleased'));
			$wpdb->delete($wpdb->prefix . 'posts', array('post_mime_type' => 'video/ftp-unreleased'));
			$wpdb->get_results($wpdb->prepare("DELETE FROM ".$wpdb->prefix."postmeta WHERE meta_value LIKE %s", "/libsyn/ftp-unreleased%"));
			$error = false;
			
			//set output
			header('Content-Type: application/json');
			if(!$error) echo json_encode(true);
				else echo json_encode(false);
			exit;
		}
		return;
	}
	
}
?>
