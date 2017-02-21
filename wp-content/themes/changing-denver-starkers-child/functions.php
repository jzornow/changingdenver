<?php

  add_action( 'wp_enqueue_scripts', 'theme_enqueue_styles' );
  function theme_enqueue_styles() {
      wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
  }

  show_admin_bar(false);

  function register_my_menus() {
  register_nav_menus(
    array( 'header-menu' => __( 'Header Menu' ) )
  );
}

add_action( 'init', 'register_my_menus' );

// Note: This really should be in a separate plugin.

add_action( 'init', 'create_episodes_post_type' );
function create_episodes_post_type() {
  $labels = array(
    'name'               => _x( 'Episodes', 'post type general name' ),
    'singular_name'      => _x( 'Episode', 'post type singular name' ),
    'menu_name'          => _x( 'Episodes', 'admin menu' ),
    'name_admin_bar'     => _x( 'Episode', 'add new on admin bar' ),
    'add_new'            => _x( 'Add New', 'episode' ),
    'add_new_item'       => __( 'Add New Episode' ),
    'new_item'           => __( 'New Episode' ),
    'edit_item'          => __( 'Edit Episode' ),
    'view_item'          => __( 'View Episode' ),
    'all_items'          => __( 'All Episode' ),
    'search_items'       => __( 'Search Episode' ),
    'parent_item_colon'  => __( 'Parent Episode:' ),
    'not_found'          => __( 'No episodes found.' ),
    'not_found_in_trash' => __( 'No episodes found in Trash.' )
  );

  $args = array(
    'labels' => $labels,
    'public' => true,
    'has_archive' => true,
    'rewrite' => array('slug' => 'episodes'),
    'supports' => array( 'title', 'editor', 'thumbnail' )
  );

  register_post_type( 'episode', $args );
}

add_action( 'init', 'create_seasons_post_type' );
function create_seasons_post_type() {
  $labels = array(
    'name'               => _x( 'Seasons', 'post type general name' ),
    'singular_name'      => _x( 'Season', 'post type singular name' ),
    'menu_name'          => _x( 'Seasons', 'admin menu' ),
    'name_admin_bar'     => _x( 'Season', 'add new on admin bar' ),
    'add_new'            => _x( 'Add New', 'season' ),
    'add_new_item'       => __( 'Add New Season' ),
    'new_item'           => __( 'New Season' ),
    'edit_item'          => __( 'Edit Season' ),
    'view_item'          => __( 'View Season' ),
    'all_items'          => __( 'All Seasons' ),
    'search_items'       => __( 'Search Seasons' ),
    'parent_item_colon'  => __( 'Parent Seasons:' ),
    'not_found'          => __( 'No seasons found.' ),
    'not_found_in_trash' => __( 'No seasons found in Trash.' )
  );

  $args = array(
    'labels' => $labels,
    'public' => true,
    'has_archive' => true,
    'rewrite' => array('slug' => 'seasons'),
    'supports' => array( 'title' )
  );

  register_post_type( 'season', $args );
}

// Custom Field Definitions for Episodes

if(function_exists("register_field_group"))
{
  register_field_group(array (
    'id' => 'acf_episode-information',
    'title' => 'Episode Information',
    'fields' => array (
      array (
        'key' => 'field_56b055ae251a6',
        'label' => 'Episode ID',
        'name' => 'episode_id',
        'type' => 'number',
        'instructions' => 'The ID for this episode in LibSyn.',
        'required' => 1,
        'default_value' => '',
        'placeholder' => '',
        'prepend' => '',
        'append' => '',
        'min' => '',
        'max' => '',
        'step' => '',
      ),
      array (
        'key' => 'field_58a12cec344ed',
        'label' => 'Season Number',
        'name' => 'season_number',
        'type' => 'post_object',
        'required' => 1,
        'post_type' => array (
          0 => 'season',
        ),
        'taxonomy' => array (
          0 => 'all',
        ),
        'allow_null' => 0,
        'multiple' => 0,
      ),
    ),
    'location' => array (
      array (
        array (
          'param' => 'post_type',
          'operator' => '==',
          'value' => 'episode',
          'order_no' => 0,
          'group_no' => 0,
        ),
      ),
    ),
    'options' => array (
      'position' => 'acf_after_title',
      'layout' => 'default',
      'hide_on_screen' => array (
      ),
    ),
    'menu_order' => 0,
  ));
}


?>
