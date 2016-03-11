<?php
/**
 * The main template file
 * This is the most generic template file in a WordPress theme
 * and one of the two required files for a theme (the other being style.css).
 * It is used to display a page when nothing more specific matches a query.
 * E.g., it puts together the home page when no home.php file 
 *
 * Please see /external/starkers-utilities.php for info on Starkers_Utilities::get_template_parts()
 *
 * @package 	WordPress
 * @subpackage 	Starkers
 * @since 		Starkers 4.0
 */
?>
<?php Starkers_Utilities::get_template_parts( array( 'parts/shared/html-header', 'parts/shared/header' ) ); ?>
<div class="content">
  <?php if ( have_posts() ): ?>
    <div class="page-title-block">
      <h1 class="page-title">Boxing in the Rain Shadow</h1>
      <h2 class="page-subtitle">The Changing Denver Blog</h2>
    </div>
    <ol class="blog-posts-list">
      <?php while ( have_posts() ) : the_post(); ?>
      	<li class="post">
          <article class="post-content">
          <?php 
            if ( has_post_thumbnail() ) { 
              $thumbnail_url = wp_get_attachment_url( get_post_thumbnail_id( $post->ID ) ); 
          ?>
            <div class="post-header" style="background-image: url('<?php echo $thumbnail_url; ?>');">
          <?php } else { ?>
            <div class="post-header">
          <?php } ?>
              <h2 class="post-title">
                <a href="<?php esc_url( the_permalink() ); ?>" title="<?php the_title(); ?>" rel="bookmark">
                  <?php the_title(); ?>
                </a>
              </h2>
              <time class="post-date" datetime="<?php the_time( 'Y-m-d' ); ?>" pubdate>
                <?php the_date(); ?> <?php the_time(); ?>
              </time> 
            </div>
            <div class="post-excerpt">
              <?php the_content(" [More...]") ?>
            </div>
          </article>
        </li>
      <?php endwhile; ?>
    </ol>
  <?php else: ?>
    <h2>No posts to display</h2>
  <?php endif; ?>
</div>
<?php Starkers_Utilities::get_template_parts( array( 'parts/shared/footer','parts/shared/html-footer') ); ?>
