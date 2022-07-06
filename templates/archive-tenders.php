<?php
/**
 * The Template for displaying all tenders
 *
 * This template can be overridden by copying it to yourtheme/tenders/archive-tenders.php
 *
 * @package     Tenders/Templates
 * @version     1.2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

ob_start();

get_header(); 

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
$selected_category = ( NULL != filter_input(INPUT_GET, 'selected_category') && -1 != filter_input(INPUT_GET, 'selected_category') ) ? sanitize_text_field(filter_input(INPUT_GET, 'selected_category')) : '';
$selected_location = ( NULL != filter_input(INPUT_GET, 'selected_location') && -1 != filter_input(INPUT_GET, 'selected_location') ) ? sanitize_text_field(filter_input(INPUT_GET, 'selected_location')) : '';   

// WP_Query Default Arguments
$args = apply_filters(
	'tenders_args', 
	array(
		'post_type' => 'tenders',
		'posts_per_page' => 15,
		'paged' => $paged,
		'tenders_cat' => $selected_category,
		'tenders_loc' => $selected_location,
		's' => $search_keyword,
	)
);

$tender_query = new WP_Query( $args );
?>
<section class="tenders u-align-left u-clearfix u-white u-section-3" id="archive_tenders">
	<div class="container archive-tenders u-clearfix u-sheet u-sheet-1">
		<?php
		if( empty($atts) ){
		/**
		 * Creating list on non-empty tender category
		 * 
		 * Tender Category Selectbox
		 */
		// Tender Category Arguments
		$category_args = array(
				'show_option_none' => esc_html__('Category', 'tenders'),
				'orderby'          => 'NAME',
				'order'            => 'ASC',
				'hide_empty'       => 0,
				'echo'             => FALSE,
				'hierarchical'     => TRUE,
				'name'             => 'selected_category',
				'id'               => 'category',
				'class'            => 'form-control',
				'selected'         => $selected_category,
				'taxonomy'         => 'tenders_cat',
				'value_field'      => 'slug',
		);
		
		// Display or retrieve the HTML dropdown list of tender category
		$category_select = wp_dropdown_categories( $category_args );
		
		/**
		 * Creating list on non-empty tender location
		 * 
		 * Tender Location Selectbox
		 */
		// Tender Location Arguments
		$location_args = array(
				'show_option_none' => esc_html__('Location', 'tenders'),
				'orderby' => 'NAME',
				'order' => 'ASC',
				'hide_empty' => 0,
				'echo' => FALSE,
				'name' => 'selected_location',
				'id' => 'location',
				'class' => 'form-control',
				'selected' => $selected_location,
				'hierarchical' => TRUE,
				'taxonomy' => 'tenders_loc',
				'value_field' => 'slug',
		);
		
		// Display or retrieve the HTML dropdown list of job locations                  
		$location_select = wp_dropdown_categories( $location_args );
		?>
		<div class="tenders-search-filters">
			<form class="filters-form" action="" method="get">
			<div class="row">
				
				<!-- Keywords Search-->    
				<div class="tenders-search-keywords col-md-4">
					<div class="form-group">
					<label class="sr-only" for="search_keywords"><?php esc_html_e('Keywords', 'tenders'); ?></label>
					<input type="text" class="form-control" value="<?php esc_attr_e( strip_tags( $search_keyword ) ); ?>" placeholder="<?php _e('Keywords', 'tenders'); ?>" id="search_keywords" name="search_keywords">
					</div>
				</div>
				
				<!-- Category Filter-->        
				<div class="tenders-search-categories col-md-3">
					<div class="form-group">
						<?php
						if (isset($category_select) && (NULL != $category_select )) {
							echo $category_select;
            }
						?>
					</div>
				</div>
				
				<!-- Location Filter-->
				<div class="tenders-search-location col-md-3">
					<div class="form-group">
					<?php
					if (isset($location_select) && (NULL != $location_select )) {
						echo $location_select;
					}
					?>
					</div>
				</div>
				<div class="tenders-search-button col-md-2"><input class="btn-search btn btn-primary" value="&#xf002;" type="submit"></div>
			</div>
			</form>
		</div>
		<?php
		}
		
		if ($tender_query->have_posts()):
			while ($tender_query->have_posts()): $tender_query->the_post();
				echo '<div class="tender-list">';
					$when_posted = human_time_diff(get_post_time('U'), current_time('timestamp'));
					the_title( '<h2 class="tender-title pull-left">', '</h2>' );
					echo '<div class="tender-terms pull-right">';
					echo get_the_term_list( $post->ID, 'tenders_cat', '<span class="text-muted"><i class="fa fa-folder"></i> ', ', ', '</span>' );
					echo get_the_term_list( $post->ID, 'tenders_loc', '<span class="text-muted"><i class="fa fa-globe"></i> ', ', ', '</span>' );
					echo '<span class="text-muted"><i class="fa fa-calendar-check-o"></i> '.$when_posted.'</span>';
					echo '</div>
					<div class="clearfix"></div>';
									
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
				<p class="section-subheading text-muted"><?php esc_html_e( 'We&rsquo;re sorry you landed on this page. It seems we can&rsquo;t find what you&rsquo;re looking for. Perhaps searching can help.', 'tenders' ); ?></p>
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

get_footer();

/* Omit closing PHP tag at the end of PHP files to avoid "headers already sent" issues. */