<?php
/**
 * The Template for displaying all single posts
 *
 */
 ?>
<?php get_header(); ?>
<?php while ( have_posts() ) : the_post();

          require_once('includes/development_kit/single-post-post-object.php');
		  
          require_once('includes/development_kit/single-post-post-meta.php');
		  
          require_once('includes/development_kit/single-post-post-terms.php');
		  
          require_once('includes/development_kit/single-post-wp-better-attachment.php');
		  
	  endwhile; ?>
<?php get_footer(); ?>
