<?php
/*
Template Name: Home Page
*/
?>

<?php 
  Starkers_Utilities::get_template_parts( array( 'parts/shared/html-header' ) );
?>

<div class="home-background-wrapper">

  <div class="brand-wrapper">
      <div class="header-menu">
        <?php wp_nav_menu( array( 'theme_location' => 'header-menu' ) ); ?>
      </div>
  </div>
<!--
  <?php query_posts('showposts=1'); ?>
  <?php // if ( have_posts() ) while ( have_posts() ) : the_post(); ?>
  <article>

    <h2><?php the_title(); ?></h2>
    <time datetime="<?php the_time( 'Y-m-d' ); ?>" pubdate><?php the_date(); ?> <?php the_time(); ?></time> 
    <?php the_content(); ?>     

    <?php // if ( get_the_author_meta( 'description' ) ) : ?>
    <?php // echo get_avatar( get_the_author_meta( 'user_email' ) ); ?>
    <h3>About <?php echo get_the_author() ; ?></h3>
    <?php the_author_meta( 'description' ); ?>
    <?php // endif; ?>


  </article>
  <?php // endwhile; ?>
-->
</div>

<?php 
  // Starkers_Utilities::get_template_parts( array( 'parts/shared/footer','parts/shared/html-footer' ) ); 
?>