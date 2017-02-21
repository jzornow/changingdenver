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

?>
