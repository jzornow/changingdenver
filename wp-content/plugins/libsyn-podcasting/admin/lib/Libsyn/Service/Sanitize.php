<?php
namespace Libsyn\Service;

/*
	This is a helper class containing all the necessary sanitization 
	for the post fields and API call/response fields.
	
*/
class Sanitize extends \Libsyn\Service{

/* ALL DB FIELDS */

    /**
     * plugin_api_id
     * 
     * @param <int> $pluginApiId 
     * 
     * @return <int>
     */
	public function pluginApiId($pluginApiId) {
		if(!empty($pluginApiId)&&is_numeric($pluginApiId)) {
			$safe_pluginApiId = intval($pluginApiId);
		} else $safe_pluginApiId = '';
		return $safe_pluginApiId;
	}

    /**
     * client_id
     * 
     * @param <string> $clientId 
     * 
     * @return <string>
     */
	public function clientId($clientId) {
		if(!empty($clientId)&&is_string($clientId)) {
			$clientId = (strlen($clientId)==12)?$clientId:'';
			$safe_clientId = sanitize_text_field($clientId);
		} else $safe_clientId = '';
		return $safe_clientId;
	}
	
    /**
     * client_secret
     * 
     * @param <string> $clientSecret 
     * 
     * @return <string>
     */
	public function clientSecret($clientSecret) {
		if(!empty($clientSecret)&&is_string($clientSecret)) {
			$clientSecret = (strlen($clientSecret)==20)?$clientSecret:'';
			$safe_clientSecret = sanitize_text_field($clientSecret);
		} else $safe_clientSecret = '';
		return $safe_clientSecret;
	}
	
    /**
     * access_token
     * 
     * @param <string> $accessToken 
     * 
     * @return <string>
     */
	public function accessToken($accessToken) {
		if(!empty($accessToken)&&is_string($accessToken)) {
			$accessToken = (strlen($accessToken)==40)?$accessToken:'';
			$safe_accessToken = sanitize_text_field($accessToken);
		} else $safe_accessToken = '';
		return $safe_accessToken;
	}

	
    /**
     * refresh_token
     * 
     * @param <string> $refreshToken 
     * 
     * @return <string>
     */
	public function refreshToken($refreshToken) {
		if(!empty($refreshToken)&&is_string($refreshToken)) {
			$refreshToken = (strlen($refreshToken)==40)?$refreshToken:'';
			$safe_refreshToken = sanitize_text_field($refreshToken);
		} else $safe_refreshToken = '';
		return $safe_refreshToken;
	}
	
    /**
     * show_id
     * 
     * @param <int> $showId 
     * 
     * @return <int>
     */
	public function showId($showId) {
		if(!empty($showId)&&is_numeric($showId)) {
			$safe_showId = sanitize_text_field(intval($showId));
		} else $safe_showId = '';
		return $safe_showId;
	}
	
    /**
     * refresh_token_expires
     * 
     * @param <string> $refreshTokenExpires 
     * 
     * @return <string>
     */
	public function refreshTokenExpires($refreshTokenExpires) {
		if(!empty($refreshTokenExpires)&&is_string($refreshTokenExpires)) {
			$refreshTokenExpires = (strlen($refreshTokenExpires)==19)?$refreshTokenExpires:'';
			$safe_refreshTokenExpires = sanitize_option('date_format', $refreshTokenExpires);
		} else $safe_refreshTokenExpires = '';
		return $safe_refreshTokenExpires;
	}
	
    /**
     * access_token_expires
     * 
     * @param <string> $accessTokenExpires 
     * 
     * @return <string>
     */
	public function accessTokenExpires($accessTokenExpires) {
		if(!empty($accessTokenExpires)&&is_string($accessTokenExpires)) {
			$accessTokenExpires = (strlen($accessTokenExpires)==19)?$accessTokenExpires:'';
			$safe_accessTokenExpires = sanitize_option('date_format', $accessTokenExpires);
		} else $safe_accessTokenExpires = '';
		return $safe_accesshTokenExpires;
	}
	
    /**
     * creation_date
     * 
     * @param <string> $creationDate 
     * 
     * @return <string>
     */
	public function creationDate($creationDate) {
		if(!empty($creationDate)&&is_string($creationDate)) {
			$creationDate = (strlen($creationDate)==19)?$creationDate:'';
			$safe_creationDate = sanitize_option('date_format', $creationDate);
		} else $safe_creationDate = '';
		return $safe_creationDate;
	}
	
    /**
     * feed_redirect_url
     * 
     * @param <type> $feedRedirectUrl 
     * 
     * @return <type>
     */
	public function feedRedirectUrl($feedRedirectUrl) {
		if(!empty($feedRedirectUrl)&&is_string($feedRedirectUrl)) {
			$safe_feedRedirectUrl = esc_url_raw($feedRedirectUrl);
		} else $safe_feedRedirectUrl = '';
	}
	
    /**
     * itunes_subscription_url
     * 
     * @param <type> $itunesSubscriptionUrl 
     * 
     * @return <type>
     */
	public function itunesSubscriptionUrl($itunesSubscriptionUrl) {
		if(!empty($itunesSubscriptionUrl)&&is_string($itunesSubscriptionUrl)) {
			$safe_itunesSubscriptionUrl = esc_url_raw($itunesSubscriptionUrl);
		} else $safe_itunesSubscriptionUrl = '';
	}
	
	
	
	
	/* OTHER */
	
    /**
     * Generic text validation
     * 
     * @param <string> $text 
     * 
     * @return <string>
     */
	public function text($text) {
		if(!empty($text)&&is_string($text)) {
			$safe_text = sanitize_text_field($text);
		} else $safe_text = '';
		return $safe_text;
	}
	
    /**
     * Generic numeric validation
     * 
     * @param <int> $numeric 
     * 
     * @return <int>
     */
	public function numeric($numeric) {
		if(!empty($numeric)&&is_numeric($numeric)) {
			$safe_numeric = intval($numeric);
		} else $safe_numeric = '';
		return $safe_numeric;
	}
	
    /**
     * Generic url_raw validation
     * 
     * @param <string> $url_raw 
     * 
     * @return <string>
     */
	public function url_raw($url_raw) {
		if(!empty($url_raw)&&is_string($url_raw)) {
			$safe_url_raw = esc_url_raw($url_raw);
		} else $safe_url_raw = '';
		return $safe_url_raw;
	}
	
    /**
     * Generic date_format validation
     * 
     * @param <string> $date_format 
     * 
     * @return <string>
     */
	public function date_format($date_format) {
		if(!empty($date_format)&&is_string($date_format)) {
			$date_format = (strlen($date_format)==19)?$date_format:'';
			$safe_date_format = sanitize_option('date_format', $date_format); 
		} else $safe_date_format = '';
		return $safe_date_format;
	}
	
    /**
     * Handles array of player settings
	 * uses WP register_setting()
     * 
     * @param <array> $player_settings
	 * 		array [ 
	 *			(use_thumbnail,null) player_use_thumbnail
	 *			(standard,mini) player_use_theme,
	 *			(int) player_width,
	 *			(int) player_height,
	 *			(top,bottom) player_placement,
	 *		]
     * 
     * @return <string>
     */
	public function player_settings($player_settings) {
		$error = false;
		
		//player_use_thubnail
		$player_settings['player_use_thumbnail'] = $this->text($player_settings['player_use_thumbnail']);
		if($player_settings['player_use_thumbnail']!=='use_thumbnail') {
			$player_settings['player_use_thumbnail'] = '';
		} else { 
			//looks good 
		}
		
		//player_use_theme
		$player_settings['player_use_theme'] = $this->text($player_settings['player_use_theme']);
		if(!empty($player_settings['player_use_theme'])) {
			if($player_settings['player_use_theme']!=='standard'&&$player_settings['player_use_theme']!=='mini') {
				$player_settings['player_use_theme'] = '';
			} else { 
				//looks good 
			}
		} else { $error = true; }

		//player_width
		$player_settings['player_width'] = $this->numeric($player_settings['player_width']);
		if(!empty($player_settings['player_width'])) {
			//looks good
		} else { $error = true; }

		//player_height
		$player_settings['player_height'] = $this->numeric($player_settings['player_height']);
		if(!empty($player_settings['player_height'])) {
			//looks good
		} else { $error = true; }

		//player_placement
		$player_settings['player_placement'] = $this->text($player_settings['player_placement']);
		if(!empty($player_settings['player_placement'])) {
			if($player_settings['player_placement']!=='top'&&$player_settings['player_placement']!=='bottom') {
				$player_settings['player_placement'] = '';
			} else {
				//looks good
			}
		} else { $error = true; }

		if($error) return array();
			else return $player_settings;
	}
}
