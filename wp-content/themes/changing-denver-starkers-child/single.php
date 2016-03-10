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
  
  <div class="content">
  
    <?php if ( have_posts() ) while ( have_posts() ) : the_post(); ?>

      <article class="post">
        <div class="post-header" style="background-image:url('<?php  echo get_stylesheet_directory_uri() . '/img/cover-photo-' . rand(1,4) . '.jpg' ?>')">
          <h2 class="post-title">
            <a href="<?php esc_url( the_permalink() ); ?>" title="Permalink to <?php the_title(); ?>" rel="bookmark">
              <?php the_title(); ?>
            </a>
          </h2>
          <time class="post-date" datetime="<?php the_time( 'Y-m-d' ); ?>" pubdate>
            <?php the_date(); ?> <?php the_time(); ?>
          </time>
        </div> 

        <?php 
          if ( has_post_thumbnail() ) { 
            $thumbnail_id = get_post_thumbnail_id($post->ID);
            $thumbnail_url = wp_get_attachment_url( $thumbnail_id );
          ?>
          <div class="post-body post-body-with-columns">
            <div class="post-thumbnail-holder">
              <a class="post-thumbnail-link" href="<?php echo $thumbnail_url ?>">
                <img class="post-thumbnail" src="<?php echo $thumbnail_url ?>">
              </a>
              <?php if ( has_excerpt($thumbnail_id) ) { ?>
                <div class="post-thumbnail-caption">
                  <?php echo get_post($thumbnail_id)->post_excerpt; ?>
                </div>
              <?php } ?>
            </div>
        <?php } else { ?>
          <div class="post-body">
        <?php } ?> 
        <div class="post-description">
          <?php the_content(); ?>
        </div>
      </div>
    </article>

    <?php endwhile; ?>

  </div>

<?php Starkers_Utilities::get_template_parts( array( 'parts/shared/footer','parts/shared/html-footer' ) ); ?>