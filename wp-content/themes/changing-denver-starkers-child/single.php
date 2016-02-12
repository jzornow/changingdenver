<?php
/**
 * The Template for displaying all single posts
 *
 * Please see /external/starkers-utilities.php for info on Starkers_Utilities::get_template_parts()
 *
 * @package 	WordPress
 * @subpackage 	Starkers
 * @since 		Starkers 4.0
 */
?>
<?php Starkers_Utilities::get_template_parts( array( 'parts/shared/html-header', 'parts/shared/header' ) ); ?>

<?php if ( have_posts() ) while ( have_posts() ) : the_post(); ?>

  <article class="blog-post">
    <div class="blog-post-thumbnail">
      <a href="<?php esc_url( the_permalink() ); ?>" title="Permalink to <?php the_title(); ?>" rel="bookmark">
        <?php if ( has_post_thumbnail() ) {
          the_post_thumbnail(); 
        } else { ?>
          <div class="placeholder-image"></div>
        <?php } ?>
      </a>
    </div>
    <div class="blog-post-content">
      <div class="blog-post-header">
        <h2 class="blog-post-title">
          <a href="<?php esc_url( the_permalink() ); ?>" title="Permalink to <?php the_title(); ?>" rel="bookmark">
            <?php the_title(); ?>
          </a>
        </h2>
        <time class="blog-post-date" datetime="<?php the_time( 'Y-m-d' ); ?>" pubdate>
          <?php the_date(); ?> <?php the_time(); ?>
        </time>
      </div> 
      <?php the_content(); ?>
    </div>
  </article>

<?php endwhile; ?>

<?php Starkers_Utilities::get_template_parts( array( 'parts/shared/footer','parts/shared/html-footer' ) ); ?>