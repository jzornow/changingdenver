<?php
/*
Plugin Name: Libsyn Podcast Plugin
Plugin URI: http://wordpress.org/plugins/libsyn-podcasting/
Description: Post or edit Libsyn Podcast episodes directly through Wordpress.
Version: 0.9.4.3
Author: Libsyn
Author URI: http://www.libsyn.com/wodpress-plugin/
License: GPLv3
*/

define("LIBSYN_KEY", "libsynmodule_");
define("LIBSYN_DIR", basename(dirname(__FILE__)));
define("LIBSYN_ADMIN_DIR", basename(dirname(__FILE__))."/admin/");

if ( ! function_exists( 'libsyn_unqprfx_embed_shortcode' ) ) :

	function libsyn_enqueue_script() {
		wp_enqueue_script( 'jquery' );
	}
	add_action( 'wp_enqueue_scripts', 'libsyn_enqueue_script' );
	
	/* Add iframe shortcode */
	function libsyn_unqprfx_embed_shortcode( $atts, $content = null ) {
		$defaults = array(
			'src' => '',
			'width' => '100%',
			'height' => '480',
			'scrolling' => 'no',
			'class' => 'podcast-class',
			'frameborder' => '0'
		);

		foreach ( $defaults as $default => $value ) { // add defaults
			if ( ! @array_key_exists( $default, $atts ) ) { // hide warning with "@" when no params at all
				$atts[$default] = $value;
			}
		}

		$src_cut = substr( $atts["src"], 0, 35 ); // special case for google maps
		if( strpos( $src_cut, 'maps.google' ) ){
			$atts["src"] .= '&output=embed';
		}

		// get_params_from_url
		if( isset( $atts["get_params_from_url"] ) && ( $atts["get_params_from_url"] == '1' || $atts["get_params_from_url"] == 1 || $atts["get_params_from_url"] == 'true' ) ) {
			if( $_GET != NULL ){
				if( strpos( $atts["src"], '?' ) ){ // if we already have '?' and GET params
					$encode_string = '&';
				}else{
					$encode_string = '?';
				}
				foreach( $_GET as $key => $value ){
					$encode_string .= $key.'='.$value.'&';
				}
			}
			$atts["src"] .= $encode_string;
		}

		$html = '';
		if( isset( $atts["same_height_as"] ) ){
			$same_height_as = $atts["same_height_as"];
		}else{
			$same_height_as = '';
		}
		
		if( $same_height_as != '' ){
			$atts["same_height_as"] = '';
			if( $same_height_as != 'content' ){ // we are setting the height of the iframe like as target element
				if( $same_height_as == 'document' || $same_height_as == 'window' ){ // remove quotes for window or document selectors
					$target_selector = $same_height_as;
				}else{
					$target_selector = '"' . $same_height_as . '"';
				}
				$html .= '
					<script>
					jQuery(function($){
						var target_height = $(' . $target_selector . ').height();
						$("iframe.' . $atts["class"] . '").height(target_height);
						//alert(target_height);
					});
					</script>
				';
			}else{ // set the actual height of the iframe (show all content of the iframe without scroll)
				$html .= '
					<script>
					jQuery(function($){
						$("iframe.' . $atts["class"] . '").bind("load", function() {
							var embed_height = $(this).contents().find("body").height();
							$(this).height(embed_height);
						});
					});
					</script>
				';
			}
		}
		$html .= '<iframe';
        foreach( $atts as $attr => $value ) {
			if( $attr != 'same_height_as' ){ // remove some attributes
				if( $value != '' ) { // adding all attributes
					$html .= ' ' . $attr . '="' . $value . '"';
				} else { // adding empty attributes
					$html .= ' ' . $attr;
				}
			}
		}
		$html .= '></iframe>';
		return $html;
	}
	add_shortcode( 'iframe', 'libsyn_unqprfx_embed_shortcode' );
	add_shortcode( 'podcast', 'libsyn_unqprfx_embed_shortcode' );

endif; // end of if(function_exists('libsyn_unqprfx_embed_shortcode'))	

	
	/* Add Oembed */
	function libsyn_add_oembed_handlers() {
		wp_oembed_add_provider( 'http://html5-player.libsyn.com/*', 'http://oembed.tony.dev.libsyn.com/', false );
	}
	libsyn_add_oembed_handlers();
	
	/* admin menu */
	function libsyn_plugin_admin_menu() {
		add_menu_page('Libsyn Plugin', 'Libsyn Podcasting', 'administrator', LIBSYN_ADMIN_DIR . 'settings.php', '', plugins_url('lib/images/icon.png', __FILE__));
		add_submenu_page( LIBSYN_ADMIN_DIR . 'settings.php', 'Post Episode', 'Post Episode', 'administrator', 'post-new.php');
		//add_submenu_page( LIBSYN_ADMIN_DIR . 'settings.php', 'Plugin Support', 'Plugin Support', 'administrator', LIBSYN_ADMIN_DIR . 'support.php');
		//add_submenu_page( LIBSYN_ADMIN_DIR . 'settings.php', 'Playlist Creator', 'Post Playlist', 'administrator', LIBSYN_ADMIN_DIR . 'playlist.php');
	}
	add_action('admin_menu', 'libsyn_plugin_admin_menu');

	function libsyn_unqprfx_plugin_meta( $links, $file ) { // add 'Plugin page' and 'Donate' links to plugin meta row
		if ( strpos( $file, 'libsyn.php' ) !== false ) {
			$links = array_merge( $links, array( '<a href="http://libysn.com/libsyn-wordpress-plugin/" title="Libsyn Wordpress Plugin">' . __('Libsyn') . '</a>' ) );
		}
		return $links;
	}
	add_filter( 'plugin_row_meta', 'libsyn_unqprfx_plugin_meta', 10, 2 );
	
	/* Add Libsyn Post Meta */
	function add_libsyn_post_meta($post) {
		add_meta_box(
			'libsyn-meta-box',
			__( 'Post Episode'),
			'\Libsyn\Post::addLibsynPostMeta',
			'post',
			'normal',
			'default'
		);
	}
	
	/* Include all Libsyn Classes */
	function build_libsyn_includes($scope) {
		$classesDir = array();
		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator(
					plugin_dir_path( __FILE__ ) .  $scope . '/lib/Libsyn/'
				),
			RecursiveIteratorIterator::SELF_FIRST
		);
		   foreach($iterator as $file) { 
			if($file->isDir()) {
				$path = $file->getRealpath() ;
				$path2 = PHP_EOL;
				$path3 = $path.$path2;
				$result = end(explode('/', $path3));
				if(str_replace(array("\r\n", "\r", "\n"), "", $result)!=='includes') $classesDir[] = $path;
			}
		}
		$includesArray = array();$libsyn_includes = array();
		foreach($classesDir as $row) foreach (glob($row.'/*.php') as $filename) $includesArray[$filename] = 'include';
		foreach($includesArray as $key => $val) $libsyn_includes[] = $key;
		usort($libsyn_includes, "sort_array");
		return array_reverse($libsyn_includes);
	}
	
	function sort_array ($a,$b) { return strlen($b)- strlen($a); }
	
	function build_libsyn_includes_original($scope) {
		return array (
			plugin_dir_path( __FILE__ ) .  $scope . '/lib/'.'Libsyn.php',
			plugin_dir_path( __FILE__ ) .  $scope . '/lib/'.'functions.php',
			plugin_dir_path( __FILE__ ) .  $scope . '/lib/Libsyn/'.'Api.php',
			plugin_dir_path( __FILE__ ) .  $scope . '/lib/Libsyn/'.'Post.php',
			plugin_dir_path( __FILE__ ) .  $scope . '/lib/Libsyn/'.'Defs.php',
			plugin_dir_path( __FILE__ ) .  $scope . '/lib/Libsyn/'.'Service.php',
			plugin_dir_path( __FILE__ ) .  $scope . '/lib/Libsyn/'.'Playlist.php',
			plugin_dir_path( __FILE__ ) .  $scope . '/lib/Libsyn/'.'Service/Importer.php',
			plugin_dir_path( __FILE__ ) .  $scope . '/lib/Libsyn/'.'Service/Integration.php',
			plugin_dir_path( __FILE__ ) .  $scope . '/lib/Libsyn/'.'Service/Playlist.php',
			plugin_dir_path( __FILE__ ) .  $scope . '/lib/Libsyn/'.'Service/Sanitize.php',
			plugin_dir_path( __FILE__ ) .  $scope . '/lib/Libsyn/'.'PlaylistWidget.php',
		);
	}

	$plugin_list = get_option('active_plugins');
	//if plugin is active declare plugin
	if( in_array(LIBSYN_DIR.'/'.LIBSYN_DIR.'.php', $plugin_list)) {
		//$libsyn_admin_includes = build_libsyn_includes('admin'); //may be able to use this in the future but it is not working on php 5.3
		$libsyn_admin_includes = build_libsyn_includes_original('admin');
		//global $libsyn_admin_includes;
		foreach($libsyn_admin_includes as $include) { require_once($include); }

		/* Declare Plugin */
		$plugin = new \Libsyn\Service();
		$hasApi = $plugin->getApis();
		if($hasApi !== false) {
			add_action( 'add_meta_boxes_post', 'add_libsyn_post_meta');
			add_action('save_post', '\Libsyn\Post::handlePost', 10, 2);
			add_filter( 'show_post_locked_dialog', '__return_false' );
			\Libsyn\Post::actionsAndFilters();
		}
		
		//playlist
		// add_action( 'widgets_init', function(){
			 // register_widget( 'Libsyn\PlaylistWidget' );
		// });
		
		//playlist ajax
		// add_filter('query_vars','Libsyn\\Playlist::plugin_add_trigger_load_libsyn_playlist');
		// add_action('template_redirect', 'Libsyn\\Playlist::loadLibsynPlaylist');
		// add_filter('query_vars','Libsyn\\Playlist::plugin_add_trigger_load_playlist');
		// add_action('template_redirect', 'Libsyn\\Playlist::loadPlaylist');
		
		//post form ajax
		add_filter('query_vars','Libsyn\\Post::plugin_add_trigger_load_form_data');
		add_action('template_redirect', 'Libsyn\\Post::loadFormData');
		
		//post form ajax
		add_filter('query_vars','Libsyn\\Post::plugin_add_trigger_remove_ftp_unreleased');
		add_action('template_redirect', 'Libsyn\\Post::removeFTPUnreleased');
		
		//shortcode embedding
		add_action('save_post', '\Libsyn\Playlist::playlistInit', 10, 2);
		add_shortcode( 'libsyn-playlist', '\Libsyn\Playlist::embedShortcode' );
		
		/* Check Dependencies */
		\Libsyn\Service\Integration::getInstance()->checkPhpVersion();
		\Libsyn\Service\Integration::getInstance()->checkPlugin('powerpress');
		
	}