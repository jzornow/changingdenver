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
  <?php 
    $paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;
    $the_query = new WP_Query( array(
      'post_type' => 'episode',
      'paged' => $paged
    ));
  ?>

  <?php if ( $the_query->have_posts() ): ?>
    <div class="page-title-block">
      <h1 class="page-title">Episodes</h1>
    </div>
        <ol class="posts-list">
      <?php while ( $the_query->have_posts() ) : $the_query->the_post(); ?>
      	<li class="post">
          <article class="post-content">
          <?php 
            if ( has_post_thumbnail() ) { 
              $thumbnail_url = wp_get_attachment_url( get_post_thumbnail_id( $post->ID ) ); 
          ?>
			      <div class="post-header episode-header" style="background-image: url('<?php echo $thumbnail_url; ?>');">
          <?php } else { ?>
            <div class="post-header episode-header">
          <?php } ?>
              <h2 class="post-title">
                <a href="<?php esc_url( the_permalink() ); ?>" title="<?php the_title(); ?>" rel="bookmark">
                  <?php the_title(); ?>
                </a>
              </h2>
        			<time class="post-date" datetime="<?php the_time( 'Y-m-d' ); ?>" pubdate>
                <?php the_date(); ?>
              </time> 
            </div>
            <iframe class="player" style="border: none; width:100%" src="//html5-player.libsyn.com/embed/episode/id/<?php echo the_field('episode_id'); ?>/height/46/width/640/theme/standard/autoplay/no/autonext/no/thumbnail/no/preload/no/no_addthis/no/direction/backward/no-cache/true/" height="46" scrolling="no"  allowfullscreen webkitallowfullscreen mozallowfullscreen oallowfullscreen msallowfullscreen></iframe>
            <?php echo apply_filters('the_content', $mycontent); ?>
      		</article>
      	</li>
      <?php endwhile; ?>
    </ol>
  <?php else: ?>
    <h2>No posts to display</h2>
  <?php endif; ?>

  <div class="page-numbers-holder">
    <?php

      global $wp_query;

      $big = 999999999; // need an unlikely integer

      echo paginate_links( array(
        'base' => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
        'format' => '?paged=%#%',
        'current' => max( 1, get_query_var('paged') ),
        'total' => $the_query->max_num_pages
      ) );
    
    ?>
  </div>
</div>

<?php Starkers_Utilities::get_template_parts( array( 'parts/shared/footer','parts/shared/html-footer') ); ?>
