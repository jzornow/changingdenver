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
  register_post_type( 'episode',
    array(
      'labels' => array(
        'name' => __( 'Episodes' ),
        'singular_name' => __( 'Episode' )
      ),
      'public' => true,
      'has_archive' => true,
      'rewrite' => array('slug' => 'episodes'),
      'supports' => array( 'title', 'editor', 'custom-fields', 'thumbnail' )
    )
  );
}

?>
