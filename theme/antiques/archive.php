<?php
/**
 * The Template for displaying all taxonomies inculding category
 *
 */
 ?>
<?php get_header(); ?>
<ul style="margin-top:40px;">
<?php while ( have_posts() ) : the_post();
		 echo '<li><p>[ID: '.$post->ID.'] '.$post->post_title.'</p>';
		 echo '<div id="debug-content-'.$post->ID.'" class="debug-content" >';
           	//require('includes/development_kit/single-post-post-object.php');
		  
       //   require('includes/development_kit/single-post-post-meta.php');
		  
          require('includes/development_kit/single-post-post-terms.php');
		  
        //  require('includes/development_kit/single-post-wp-better-attachment.php');
		 echo '</div>';
		 echo '</li>';
	  endwhile; ?>
</ul>
<?php get_footer(); ?>
