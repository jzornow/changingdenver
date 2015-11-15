(function ($){
	$( "#libsyn-player-settings-page-dialog" ).dialog({
		autoOpen: false,
		draggable: false,
		height: 'auto',
		width: 600,
		modal: true,
		resizable: false,
		open: function(){
			$('#player-settings-submit').hide();
			$('#player-description-text').empty().append('<em>Player settings for this post.  **Note these settings will be changed only for this post.  You can modify the default player settings on the Libsyn Podcasting settings page.</em>');
			
			if($('#playlist-settings-input-div').length > 0) {
				$('#playlist-settings-input-div').empty();
			} else {
				$(".libsyn-post-form").append('<div id="playlist-settings-input-div" class="hidden"></div>');
			}
			$('.ui-widget-overlay').bind('click',function(){
				updateFormWithSettings();
				$('#libsyn-player-settings-page-dialog').dialog('close');
			})
		},
		buttons: [
			{
				id: "dialog-player-settings-button-cancel",
				text: "Cancel",
				click: function(){
					updateFormWithSettings();
					$('#libsyn-player-settings-page-dialog').dialog('close');
				},
			},
			{
				id: "dialog-button-insert",
				text: "Use Custom Settings",
				class: "button-primary",
				click: function(){
					var dlgPlayerSettings = $(this);
					updateFormWithSettings();
					dlgPlayerSettings.dialog('close');
				}
			}
		]
	});
	
	var updateFormWithSettings = function() {
		//player_use_thumbnail
		if($('#player_use_thumbnail').is(':checked')) {
			$("#playlist-settings-input-div").append('<input name="player_use_thumbnail" value="use_thumbnail">');
		} else {
			$("#playlist-settings-input-div").append('<input name="player_use_thumbnail" value="">');
		}
		
		//player_use_theme
		if($('#player_use_theme_standard').is(':checked')) {
			$("#playlist-settings-input-div").append('<input name="player_use_theme" value="standard" type="hidden">');
		} else if($('#player_use_theme_mini').is(':checked')) {
			$("#playlist-settings-input-div").append('<input name="player_use_theme" value="mini" type="hidden">');
		} else {
			$("#playlist-settings-input-div").append('<input name="player_use_theme" value="" type="hidden">');
		}
		
		//player_width
		var playerSettingsWidth = $('#player_width').val();
		$("#playlist-settings-input-div").append('<input name="player_width" value="' + playerSettingsWidth + '" type="hidden">');
		
		
		//player_height
		var playerSettingsHeight = $('#player_height').val();
		$("#playlist-settings-input-div").append('<input name="player_height" value="' + playerSettingsHeight + '" type="hidden">');
		
		//player_placement
		if($('#player_placement_top').is(':checked')) {
			$("#playlist-settings-input-div").append('<input name="player_placement" value="top" type="hidden">');
		} else if($('#player_placement_bottom').is(':checked')) {
			$("#playlist-settings-input-div").append('<input name="player_placement" value="bottom" type="hidden">');
		} else {
			$("#playlist-settings-input-div").append('<input name="player_use_theme" value="" type="hidden">');
		}
	};
	
	var playerSettingsButton = $("<button/>",
	{
		text: " Libsyn Player Settings",
		click: function(event) {
			event.preventDefault();
			$("#libsyn-player-settings-page-dialog").dialog( "open" );
		},
		class: "button",
		"data-editor": "content",
		"font": "400 18px/1 dashicons"
	}).prepend("<span class=\"dashicons dashicons-format-video wp-media-buttons-icon\"></span>");

	$("#wp-content-media-buttons").append(playerSettingsButton);
	
	
}) (jQuery);