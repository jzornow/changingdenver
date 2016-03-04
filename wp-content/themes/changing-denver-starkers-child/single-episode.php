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

  <article class="post">
    <?php if ( has_post_thumbnail() ) { 
      $thumbnail_url = wp_get_attachment_url( get_post_thumbnail_id($post->ID) ); ?>
      <div class="post-header episode-header" style="background-image:url('<?php echo $thumbnail_url ?>')">
    <?php } else { ?>
      <div class="post-header episode-header">
    <?php } ?> 
    <h2 class="post-title">
      <a href="<?php esc_url( the_permalink() ); ?>" title="Permalink to <?php the_title(); ?>" rel="bookmark">
        <?php the_title(); ?>
      </a>
    </h2>
    <time class="post-date" datetime="<?php the_time( 'Y-m-d' ); ?>" pubdate>
      <?php the_date(); ?> <?php the_time(); ?>
    </time>
  </div> 
  <iframe class="player" style="border: none; width:100%" src="//html5-player.libsyn.com/embed/episode/id/<?php echo the_field('episode_id'); ?>/height/46/width/640/theme/standard/autoplay/no/autonext/no/thumbnail/no/preload/no/no_addthis/no/direction/backward/no-cache/true/" height="46" scrolling="no"  allowfullscreen webkitallowfullscreen mozallowfullscreen oallowfullscreen msallowfullscreen></iframe>
  <div class="post-description">
    <?php the_content(); ?>
  </div>
  </article>

<?php endwhile; ?>

<?php Starkers_Utilities::get_template_parts( array( 'parts/shared/footer','parts/shared/html-footer' ) ); ?>