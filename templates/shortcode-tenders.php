<?php
/**
 * The Template for displaying tenders based on shortcode attributes
 *
 * This template can be overridden by copying it to yourtheme/tenders/shortcode-tenders.php
 *
 * @package     Tenders/Templates
 * @version     1.2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

ob_start();

global $tender_query, $post;

// Retrieves Public Query Page Variable
if (get_query_var('paged')) {
	$paged = (int) get_query_var('paged');
} elseif (get_query_var('page')) {
	$paged = (int) get_query_var('page');
} else {
	$paged = 1;
}

// Merge WP_Query $args array on each $_GET element
$search_keyword = ( NULL != filter_input(INPUT_GET, 'search_keywords') ) ? sanitize_text_field( (filter_input(INPUT_GET, 'search_keywords') ) ) : '';

// Get Shortcode Attributes 
$atts = empty($atts) ? '' : $atts;
extract( shortcode_atts( array (
		'order' => 'ASC',
		'orderby' => 'date',
		'posts' => -1,
		'category' => $selected_category,
		'location' => $selected_location,
), $atts ) );

// Define query parameters based on attributes
// WP_Query Default Arguments
$args = apply_filters(
	'tenders_args', 
	array(
		'post_type' => 'tenders',
		'order' => $order,
		'orderby' => $orderby,
		'posts_per_page' => $posts,
		'paged' => $paged,
		'tenders_cat' => $category,
		'tenders_loc' => $location,
		's' => $search_keyword,
	)
);

$tender_query = new WP_Query( $args );
?>
<section class="tenders" id="archive_tenders">
	<div class="container archive-tenders">
		<?php
		if ($tender_query->have_posts()):
			while ($tender_query->have_posts()): $tender_query->the_post();
				echo '<div class="tender-list">';
					$when_posted = human_time_diff(get_post_time('U'), current_time('timestamp'));
					the_title( '<h2 class="tender-title pull-left">', '</h2>' );					
					echo '<div class="tender-terms pull-right">';
					echo get_the_term_list( $post->ID, 'tenders_cat', '<span class="text-muted"><i class="fa fa-folder"></i> ', ', ', '</span>' );
					echo get_the_term_list( $post->ID, 'tenders_loc', '<span class="text-muted"><i class="fa fa-globe"></i> ', ', ', '</span>' );
					echo '<span class="text-muted"><i class="fa fa-calendar-check-o"></i> '.$when_posted.'</span>';
					echo '</div><div class="clearfix"></div>';
									
					the_content();
										
					$tender_doc = get_post_meta( $post->ID, 'tender_document', true );
					echo '<div class="tender-buttons"><a href="'. get_the_permalink() .'" class="btn btn-primary">'. esc_html('Read More', 'tenders') .'</a>';
					if( !empty( $tender_doc[0]['url'] ) ){
						echo '&nbsp;<a href="'. esc_url( $tender_doc[0]['url'] ) .'" class="btn btn-primary" target="_blank">'. esc_html('Download Tender Document', 'tenders') .'</a>';
					}
					echo '</div>';
				echo '</div>
				<div class="clearfix"></div>';
			endwhile;			
			
			//TODO
			//Add Pagination here
			
		else:
		
			//Show 404 Not found
			?>
			<div class="text-center">
				<h2 class="section-heading"><?php esc_html_e( 'Ooops! No Content Available', 'tenders' );?></h2>
				<h3 class="section-subheading text-muted"><?php esc_html_e( 'We&rsquo;re sorry you landed on this page. It seems we can&rsquo;t find what you&rsquo;re looking for. Perhaps searching can help.', 'tenders' ); ?></h3>
				<?php get_search_form(); ?>
			</div>
			<?php
			
		endif;
		
		wp_reset_postdata();
		?>
	</div>
</section>
<?php
$html_archive = ob_get_clean();

/**
 * Modify the Tenders Archive Page Template. 
 *                                       
 * @param   html    $html_archive   Tenders Archive Page HTML.                   
 */
echo apply_filters('archive_tenders', $html_archive);

/* Omit closing PHP tag at the end of PHP files to avoid "headers already sent" issues. */