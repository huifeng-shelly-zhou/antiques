<?php
/**
 * The template for displaying 404 pages (Not Found)
 *
 */
?>

<?php get_header(); ?>

	<div id="primary" class="content-area">
		<div id="content" class="site-content" role="main">

			<header class="page-header">
				<h1 class="page-title">Not Found</h1>
			</header>

			<div class="page-wrapper">
				<div class="page-content">
					<p> Sorry we cannot locate your page.</p>
					<?php get_search_form(); ?>
				</div><!-- .page-content -->
			</div><!-- .page-wrapper -->

		</div><!-- #content -->
	</div><!-- #primary -->

<?php get_footer(); ?>