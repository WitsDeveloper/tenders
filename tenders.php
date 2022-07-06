<?php
/**
 * @package  Tenders
 * 
 * Plugin Name: Tenders Board
 * Description: This plugin adds ability to post tenders, showcase them on the front end and allow applicants to submit their applications. Plugin developed by Sammy M. Waweru.
 * Version: 1.2
 * Requires at least: 5.2
 * Requires PHP: 7.2
 * Author: Sammy Waweru
 * Author URI: http://www.witstechnologies.co.ke
 * License: GPLv3 or later
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: tenders
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! defined( 'TENDERS_PLUGIN_PATH' ) ) {
	define( 'TENDERS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
	define( 'TENDERS_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
}

if ( !class_exists( 'Tenders' ) ) {
	class Tenders {

		function register() {
			add_action( 'init', array( $this, 'tenders_init' ) );			
			//add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
			add_action( 'add_meta_boxes', array( $this, 'add_custom_metaboxes' ) );			
			add_action( 'save_post', array( $this, 'add_save_meta_box_data' ) );
			add_action( 'save_post', array( $this, 'application_add_save_meta_box_data' ) );
			add_filter( 'manage_edit-tenders_columns', array( $this, 'add_new_columns' ) );
			add_filter( 'manage_edit-tenders_sortable_columns', array( $this, 'add_new_columns' ) );
			add_action( 'manage_tenders_posts_custom_column', array( $this, 'add_custom_columns' ) );
			add_filter( 'manage_edit-tenders_applications_columns', array( $this, 'applications_add_new_columns' ) );
			add_filter( 'manage_edit-tenders_applications_sortable_columns', array( $this, 'applications_add_new_columns' ) );
			add_action( 'manage_tenders_applications_posts_custom_column', array( $this, 'applications_add_custom_columns' ) );			
			add_action( 'restrict_manage_posts', array( $this, 'add_tender_application_filter' ) );
			add_filter( 'parse_query', array( $this, 'get_all_applications') );
			add_action( 'admin_enqueue_scripts', array( $this, 'back_end_enqueue' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'front_end_enqueue' ) );
			add_shortcode( 'tenders_list', array( $this, 'tenders_list_shortcode' ) );
		}
		
		function tenders_init() {
			// generated a CPT
			$this->custom_post_type();
			$this->applications_custom_post_type();
			
			// Add Filter to redirect Single Page Template
			add_filter('single_template', array($this, 'get_tenders_single_template'), 10, 1); 
			
			// Add Filter to redirect Archive Page Template
			add_filter('archive_template', array($this, 'get_tenders_archive_template'), 10, 1);
			flush_rewrite_rules();	
		}

		function activate() {				
			// flush rewrite rules
			flush_rewrite_rules();
		}

		function deactivate() {
			// flush rewrite rules
			flush_rewrite_rules();
		}

		function back_end_enqueue() {
			// enqueue back end styles and scripts
			wp_enqueue_style( 'tenders-backend-style', plugins_url( '/assets/backend-tenders-style.css', __FILE__ ) );			
		}
		
		function front_end_enqueue() {
			// enqueue front end styles and scripts
			wp_enqueue_style( 'font-awesome-style', plugins_url( '/assets/font-awesome/css/font-awesome.min.css', __FILE__ ) );
			wp_enqueue_style( 'tenders-frontend-style', plugins_url( '/assets/frontend-tenders-style.css', __FILE__ ) );
			wp_enqueue_script( 'tenders-frontend-script', plugins_url( '/assets/frontend-tenders-script.js', __FILE__ ) );
		}

		/**
		 * Register a new Post Type
		 *
		 * Post type tenders
		 */
		function custom_post_type() {
			$singular = "Tender";
			$plural = "Tenders";
			$menuname = "Tender Board";
			
			$labels = array(
				'name'               	=> $plural,
				'singular_name'      	=> $singular,
				'menu_name'						=> $menuname,
				'all_items'          	=> 'All ' . $plural, 
				'add_name'           	=> 'Add New',
				'add_new_item'       	=> 'Add New ' . $singular,
				'edit'               	=> 'Edit',
				'edit_item'          	=> 'Edit ' . $singular,
				'new_item'           	=> 'New ' . $singular,
				'view'               	=> 'View ' . $singular,
				'view_item'				  	=> 'View ' . $singular,
				'search_term'			  	=> 'Search ' . $plural,
				'parent'			 			 	=> 'Parent ' . $singular,
				'not_found'						=> 'No ' . $plural . ' found',
				'not_found_in_trash'	=> 'No ' . $plural . ' found in Trash',				
			);
			$args = array(
				'labels'								=> $labels,
				'description'						=> __( 'This allows you to manage '. $plural .'.', 'tenders' ),
				'public'            		=> true,
				'publicly_queryable'		=> true,
				'show_ui'								=> true,
				'show_in_nav_menus'			=> true,
				'show_in_menu'					=> true,
				'query_var'							=> true,
				'rewrite'								=> array( 'slug' => 'tenders' ),
				'capability_type'				=> 'post',
				'has_archive'						=> true,
				'hierarchical'					=> false,
				'menu_position'					=> 25,
				'menu_icon'							=> 'dashicons-clipboard',
				'supports'							=> array( 'title', 'editor', 'comments', 'author' ),
				'taxonomies'						=> array( 'tenders_cat', 'tenders_loc' ),
				'show_in_rest'					=> true,
				'rest_base'							=> 'tenders',
				'rest_controller_class'	=> 'WP_REST_Posts_Controller',				
			);
			
			register_post_type( 'tenders', $args );						

			// Add new taxonomy, make it hierarchical (like categories)
			$labels = array(
				'name'              => _x( 'Tender Categories', 'taxonomy general name', 'tenders' ),
				'singular_name'     => _x( 'Tender Category', 'taxonomy singular name', 'tenders' ),
				'search_items'      => __( 'Search Categories', 'tenders' ),
				'all_items'         => __( 'All Categories', 'tenders' ),
				'parent_item'       => __( 'Parent Category', 'tenders' ),
				'parent_item_colon' => __( 'Parent Category:', 'tenders' ),
				'edit_item'         => __( 'Edit Category', 'tenders' ),
				'update_item'       => __( 'Update Category', 'tenders' ),
				'add_new_item'      => __( 'Add New Category', 'tenders' ),
				'new_item_name'     => __( 'New Category Name', 'tenders' ),
				'menu_name'         => __( 'Categories', 'tenders' ),
			);

			$args = array(
				'hierarchical'      		=> true,
				'labels'            		=> $labels,
				'public'								=> true,
        'show_in_quick_edit'		=> true,
				'show_ui'           		=> true,
				'show_in_nav_menus'			=> true,
				'show_admin_column' 		=> true,
				'query_var'         		=> true,
				'rewrite'           		=> array( 'slug' => 'tenders_cat' ),
				'show_in_rest'					=> true,
				'rest_base'							=> 'tenders_cat',
        'rest_controller_class'	=> 'WP_REST_Terms_Controller',
			);

			register_taxonomy( 'tenders_cat', array( 'tenders' ), $args );
			
			// Add new taxonomy, make it hierarchical (like categories)
			$labels = array(
				'name'              => _x( 'Tender Locations', 'taxonomy general name', 'tenders' ),
				'singular_name'     => _x( 'Tender Location', 'taxonomy singular name', 'tenders' ),
				'search_items'      => __( 'Search Locations', 'tenders' ),
				'all_items'         => __( 'All Locations', 'tenders' ),
				'parent_item'       => __( 'Parent Location', 'tenders' ),
				'parent_item_colon' => __( 'Parent Location:', 'tenders' ),
				'edit_item'         => __( 'Edit Location', 'tenders' ),
				'update_item'       => __( 'Update Location', 'tenders' ),
				'add_new_item'      => __( 'Add New Location', 'tenders' ),
				'new_item_name'     => __( 'New Location Name', 'tenders' ),
				'menu_name'         => __( 'Locations', 'tenders' ),				
			);

			$args = array(
				'hierarchical'      		=> true,
				'labels'            		=> $labels,
				'public'								=> true,
        'show_in_quick_edit'		=> true,
				'show_ui'           		=> true,
				'show_in_nav_menus'			=> false,
				'show_admin_column'			=> false,
				'query_var'         		=> true,
				'rewrite'           		=> array( 'slug' => 'tenders_loc' ),
				'show_in_rest'					=> true,
				'rest_base'							=> 'tenders_loc',
        'rest_controller_class'	=> 'WP_REST_Terms_Controller',
			);

			register_taxonomy( 'tenders_loc', array( 'tenders' ), $args );
		}

		/**
		 * Register a new Post Type
		 *
		 * Post type tenders_applications
		 */
		function applications_custom_post_type() {
			if(post_type_exists("tenders_applications"))
				return;
			
			$plural = 'Applications';
			$singular = 'Application';

			$labels = array(								
				'edit_item'						=> 'View/Edit ' . $singular,
				'not_found'						=> 'No ' . $plural . ' found',
				'not_found_in_trash'	=> 'No ' . $plural . ' found in Trash',
			);
			$args = array(
				'label'								=> $plural,
				'labels'							=> $labels,
				'description'					=> __( 'This allows you to manage '. $plural .'.', 'tenders' ),
				'public'							=> false,
				'publicly_queryable'	=> false,
				'show_ui'							=> true,
				'show_in_nav_menus'		=> false,
				'show_in_menu'				=> 'edit.php?post_type=tenders',
				'capability_type'			=> 'post',
				'capabilities'				=> array(
					'create_posts'			=> 'do_not_allow',
				),
				'map_meta_cap'				=> true,
				'hierarchical'				=> false,
				'supports'						=> array(''),
			);

			register_post_type( 'tenders_applications', $args );
		}

		/**
		 * Adds a submenu page under a custom post type parent.
		 */
		function add_admin_menu() {
			add_submenu_page(
				'edit.php?post_type=tenders',
				__( 'Applications', 'tenders' ),
				__( 'Applications', 'tenders' ),
				'manage_options',
				'edit.php?post_type=tenders_applications'				
			);
		}
		
		/**
		 * Update form to support uploading of files
		 *
		 * Add "multipart/form-data" to the enctype form element
		 */
		function update_edit_form() {
			echo ' enctype="multipart/form-data"';
		}

		/**
		 * Add a custom metaboxes
		 *
		 * Create metabox to hold fields
		 */
		function add_custom_metaboxes(){
			add_meta_box(
				'tenders_meta',
				'Tender Data',
				array( $this, 'tenders_meta_callback' ),
				'tenders',
				'normal',
				'high'
			);
			
			add_meta_box(
				'tenders_applications_meta',
				'Application Data',
				array( $this, 'tenders_applications_meta_callback' ),
				'tenders_applications',
				'normal',
				'high'
			);
			
			add_action( 'post_edit_form_tag', array( $this, 'update_edit_form' ) );
		}
		
		/**
		 * Prints the box content.
		 * 
		 * @param WP_Post $post The object for the current post/page.
		 */
		function tenders_meta_callback( $post ) {
			//Add a nounce field for this form		
			wp_nonce_field( basename( __FILE__ ), 'tenders_nonce_data' );					
			
			$tender_document = get_post_meta( $post->ID, 'tender_document', true );			
			?>			
			<div class="settings-tab metaboxes-tab">
				<div class="meta-row">
					<div class="the-metabox file tender_document clearfix">
						<?php
						if( !empty($tender_document[0]['url']) ){
							echo '<a class="button" href="'.esc_url($tender_document[0]['url']).'" target="_blank">'. esc_html('View Tender Qualification Document', 'tenders') .'</a><br>';
							$fieldlabel = esc_html('Upload New Tender Qualification Document (replace above)', 'tenders');
						}else{
							$fieldlabel = esc_html('Upload Tender Qualification Document', 'tenders');
						}
						?>
						<label for="tender_document" class="row-tender_document"><strong><?php _e($fieldlabel); ?></strong></label>
						<p><input type="file" id="tender_document" name="tender_document" value=""></p>
					</div>
				</div>
			</div>
			<?php			
		}			

		/**
		 * When the post is saved, saves our custom data.
		 *
		 * @param int $post_id The ID of the post being saved.
		 */
		function add_save_meta_box_data( $post_id ) {
			// Check save status
			$is_autosave = wp_is_post_autosave( $post_id );
			$is_revision = wp_is_post_revision( $post_id );
			// Verify that the nonce is valid.
			$noune = isset($_POST['tenders_nonce_data'])?$_POST['tenders_nonce_data']:NULL;
			if ( ! wp_verify_nonce( $noune, basename( __FILE__ ) ) ) {
				return;
			}
			
			// If this is an autosave, our form has not been submitted, so we don't want to do anything.
			if ( $is_autosave || $is_revision ) {
				return;
			}
			
			// Check the user's permissions.
			if(!current_user_can('edit_post', $post_id)) {
				return;
			}
			
			// Setup the array of supported file types.
			$supported_types = $this->allowed_doc_mime_types();
			
			//Attempt to upload the file
			$upload = $this->upload_attachment_file( $_FILES['tender_document'], $supported_types );
			
			//Check if file was uploaded and update
			if(isset($upload['error']) && $upload['error'] != 0) {
				wp_die('There was an error uploading your file. The error is: ' . $upload['error']);
			}else{
				$ufiles = get_post_meta( $post_id, 'tender_document', true );
				if( empty( $ufiles ) ) $ufiles = array();
				$ufiles[] = $upload;
				update_post_meta( $post_id, 'tender_document', $ufiles );
			}
		}
				
		/**
		 * Display callback for the submenu page.
		 */
		function tenders_applications_meta_callback( $post ) {
			//Add a nounce field for this form		
			wp_nonce_field( basename( __FILE__ ), 'tenders_applications_nonce_data' );
			
			//Get the parent ID
			$parent_id = 349;
			$tender_title = strip_tags( get_the_title( $parent_id ) );
			
			$post_meta = get_post_meta( $post->ID );
			$tenderapp_firstname = isset($post_meta['tenderapp_firstname'][0]) ? esc_attr($post_meta['tenderapp_firstname'][0]) : '';
			$tenderapp_lastname = isset($post_meta['tenderapp_lastname'][0]) ? esc_attr($post_meta['tenderapp_lastname'][0]) : '';
			$tenderapp_phone = isset($post_meta['tenderapp_phone'][0]) ? esc_attr($post_meta['tenderapp_phone'][0]) : '';
			$tenderapp_email = isset($post_meta['tenderapp_email'][0]) ? esc_attr($post_meta['tenderapp_email'][0]) : '';
			$tenderapp_companyname = isset($post_meta['tenderapp_companyname'][0]) ? esc_attr($post_meta['tenderapp_companyname'][0]) : '';
			$tenderapp_companywebsite = isset($post_meta['tenderapp_companywebsite'][0]) ? esc_url($post_meta['tenderapp_companywebsite'][0]) : '';
			$tenderapp_status = isset($post_meta['tenderapp_status'][0]) ? esc_attr($post_meta['tenderapp_status'][0]) : 'new';
			//Get files
			$file_krapin = get_post_meta( $post->ID, 'file_krapin', true );
			$file_kracert = get_post_meta( $post->ID, 'file_kracert', true );
			$file_nssfcert = get_post_meta( $post->ID, 'file_nssfcert', true );
			$file_nhiffcert = get_post_meta( $post->ID, 'file_nhiffcert', true );
			$file_tenderdoc = get_post_meta( $post->ID, 'file_tenderdoc', true );
			?>						
			<div class="settings-tab metaboxes-tab">
				<div class="meta-row">
					<div class="the-metabox file tender_document clearfix">
						<h2 class="tender-title"><?php esc_html_e(sprintf('Applied For: %s', $tender_title), 'tenders'); ?></h2>
						<table class="form-table" role="presentation">
							<tbody>
								<tr>
									<th scope="row"><?php esc_html_e('First Name', 'tenders'); ?></th>
									<td><?php _e($tenderapp_firstname); ?></td>
								</tr>
								
								<tr>
									<th scope="row"><?php esc_html_e('Last Name', 'tenders'); ?></th>
									<td><?php _e($tenderapp_lastname); ?></td>
								</tr>
								
								<tr>
									<th scope="row"><?php esc_html_e('Phone Name', 'tenders'); ?></th>
									<td><?php _e($tenderapp_phone); ?></td>
								</tr>
								
								<tr>
									<th scope="row"><?php esc_html_e('Email', 'tenders'); ?></th>
									<td><?php _e($tenderapp_email); ?></td>
								</tr>
								
								<tr>
									<th scope="row"><?php esc_html_e('Company Name', 'tenders'); ?></th>
									<td><?php _e($tenderapp_companyname); ?></td>
								</tr>
								
								<tr>
									<th scope="row"><?php esc_html_e('Company Website', 'tenders'); ?></th>
									<td><?php _e($tenderapp_companywebsite); ?></td>
								</tr>
								
								<tr>
									<th scope="row" colspan="2"><?php esc_html_e('Uploaded Documents', 'tenders'); ?><hr></th>
								</tr>
								
								<tr>
									<th scope="row"><?php esc_html_e('KRA Pin Certificate', 'tenders'); ?></th>
									<td><?php _e(isset($file_krapin[0]['url']) ? '<a class="button" href="'.esc_url($file_krapin[0]['url']).'" target="_blank">View Document</a>' : 'Not provided'); ?></td>
								</tr>
								
								<tr>
									<th scope="row"><?php esc_html_e('KRA Clearance Certificate', 'tenders'); ?></th>
									<td><?php _e(isset($file_kracert[0]['url']) ? '<a class="button" href="'.esc_url($file_kracert[0]['url']).'" target="_blank">View Document</a>' : 'Not provided'); ?></td>
								</tr>
								
								<tr>
									<th scope="row"><?php esc_html_e('NSSF Clearance Certificate', 'tenders'); ?></th>
									<td><?php _e(isset($file_nssfcert[0]['url']) ? '<a class="button" href="'.esc_url($file_nssfcert[0]['url']).'" target="_blank">View Document</a>' : 'Not provided'); ?></td>
								</tr>
								
								<tr>
									<th scope="row"><?php esc_html_e('NHIF Clearance Certificate', 'tenders'); ?></th>
									<td><?php _e(isset($file_nhiffcert[0]['url']) ? '<a class="button" href="'.esc_url($file_nhiffcert[0]['url']).'" target="_blank">View Document</a>' : 'Not provided'); ?></td>
								</tr>
								
								<tr>
									<th scope="row"><?php esc_html_e('Filled Tender Document', 'tenders'); ?></th>
									<td><?php _e(isset($file_tenderdoc[0]['url']) ? '<a class="button" href="'.esc_url($file_tenderdoc[0]['url']).'" target="_blank">View Document</a>' : 'Not provided'); ?></td>
								</tr>
								
								<tr>
									<th scope="row"><label for="tenderapp_status"><?php esc_html_e('Application Status', 'tenders'); ?></label></th>
									<td><select name="tenderapp_status" id="tenderapp_status">
									<?php
									foreach($this->list_application_status() as $k => $v){
										$select = ($k == $tenderapp_status)?'selected="selected"':'';
										_e("<option $select value=\"$k\">$v</option>");
									}
									?>
									</select>
									<p class="description" id="tagline-description"><?php esc_html_e('Specify status of this application', 'tenders'); ?></p></td>
								</tr>
							
							</tbody>
						</table>						
					</div>
				</div>
			</div>
			<?php
		}
		
		/**
		 * When the post is saved, saves our custom application data.
		 *
		 * @param int $post_id The ID of the post being saved.
		 */
		function application_add_save_meta_box_data( $post_id ) {
			// Check save status
			$is_autosave = wp_is_post_autosave( $post_id );
			$is_revision = wp_is_post_revision( $post_id );
			// Verify that the nonce is valid.
			$noune = isset($_POST['tenders_applications_nonce_data'])?$_POST['tenders_applications_nonce_data']:NULL;
			if ( ! wp_verify_nonce( $noune, basename( __FILE__ ) ) ) {
				return;
			}
			
			// If this is an autosave, our form has not been submitted, so we don't want to do anything.
			if ( $is_autosave || $is_revision ) {
				return;
			}
			
			// Check the user's permissions.
			if(!current_user_can('edit_post', $post_id)) {
				return;
			}
			
			$tenderapp_status = filter_input(INPUT_POST, 'tenderapp_status');
			
			// Make sure the file array isn't empty
			if(!empty($tenderapp_status)) {
				update_post_meta( $post_id, 'tenderapp_status', sanitize_text_field($tenderapp_status) );
			}
		}

		/**
		 * Add new columns to the post table
		 *
		 * @param Array $columns - Current columns on the list post
		 */		
		function add_new_columns( $columns ){
			$date = $columns['date'];
			$comments = $columns['comments'];
			unset($columns['date']);
			unset($columns['author']);
			unset($columns['comments']);
			unset($columns['taxonomy-tenders_cat']);
			$columns['taxonomy-tenders_cat'] = "Category";
			$columns['taxonomy-tenders_loc'] = "Location";
			$columns["tender_document"] = "Tender Document";
			$columns["tender_applications"] = "Tender Applications";	
			$columns['comments'] = $comments;
			$columns['date'] = $date;
			
			return $columns;
		}

		/**
		 * Display data in new columns
		 *
		 * @param  $column Current column
		 *
		 * @return Data for the column
		 */
		function add_custom_columns( $column ) {
			global $post;
			
			$tenderpost = get_children(array('posts_per_page' => -1, 'post_parent' => $post->ID, 'post_type' => 'tenders_applications'));
			$posts_count = count($tenderpost);
			
			$post_link = get_edit_post_link( $post->ID );
			$post_link = get_admin_url().'edit.php?post_type=tenders_applications&tender_id='.$post->ID;

			switch ( $column ) {
				case 'tender_document':
					$tender_document = get_post_meta( $post->ID, 'tender_document', true );
					echo !empty($tender_document[0]['url']) ? '<a href="'.esc_url($tender_document[0]['url']).'" target="_blank">View Tender Document</a>' : 'Not provided';
				break;
				case 'tender_applications':
					echo !empty($post_link) ? '<a href="'.esc_url($post_link).'">Tender Applications ('.$posts_count.')</a>' : '';
				break;		
			}
		}

		/**
		 * Add new columns to the post table on applications
		 *
		 * @param Array $columns - Current columns on the list post
		 */
		function applications_add_new_columns( $columns ){
			unset($columns['title']);
			unset($columns['date']);
			$columns["title"] = "Tender";
			$columns["applicantname"] = "Applicant Name";
			$columns["phone"] = "Phone";
			$columns["email"] = "Email";
			$columns["company"] = "Company Name";
			$columns["website"] = "Website";
			$columns["status"] = "Status";
			$columns['date'] = "Date";
			
			return $columns;
		}

		/**
		 * Display data in new columns on applications
		 *
		 * @param  $column Current column
		 *
		 * @return Data for the column
		 */
		function applications_add_custom_columns( $column ) {
			global $post;

			switch ( $column ) {
				case 'applicantname':
					$firstname = get_post_meta( $post->ID, 'tenderapp_firstname', true );
					$lastname = get_post_meta( $post->ID, 'tenderapp_lastname', true );
					$applicantname = $firstname.' '.$lastname;
					echo !empty($applicantname) ? $applicantname : '';
				break;
				case 'phone':
					$tenderapp_phone = get_post_meta( $post->ID, 'tenderapp_phone', true );
					echo !empty($tenderapp_phone) ? '<a href="tel:'.$tenderapp_phone.'">'.$tenderapp_phone.'</a>' : '';
				break;
				case 'email':					
					$tenderapp_email = get_post_meta( $post->ID, 'tenderapp_email', true );
					echo !empty($tenderapp_email) ? '<a href="mailto:'.$tenderapp_email.'">'.$tenderapp_email.'</a>' : '';
				break;
				case 'company':
					echo get_post_meta( $post->ID, 'tenderapp_companyname', true );
				break;
				case 'status':
					echo $this->get_application_status( get_post_meta( $post->ID, 'tenderapp_status', true ) );
				break;
				case 'website':
					$tenderapp_companywebsite = get_post_meta( $post->ID, 'tenderapp_companywebsite', true );
					echo !empty($tenderapp_companywebsite) ? '<a href="'.esc_url($tenderapp_companywebsite).'">'.esc_url($tenderapp_companywebsite).'</a>' : '';
				break;		
			}
		}
		
		/**
		 * To load single tender page in frontend.
		 *
		 * @param   string  $single_template    Default Single Page Path.        	
		 * @return  string  $single_template    Plugin Single Page Path.
		 */
		function get_tenders_single_template($single_template) {			
			global $post;
			
			if ('tenders' === $post->post_type) {
				$single_template = (!file_exists(get_template_directory_uri() . '/tenders/single-tenders.php') ) ? TENDERS_PLUGIN_PATH . '/templates/single-tenders.php' : get_template_directory_uri() . '/tenders/single-tenders.php';
			}
			return $single_template;
		}

		/**
		 * To load archive tender page in frontend.
		 *
		 * @param   string  $archive_template   Default Archive Page Path.     	
		 * @return  string  $archive_template   Plugin Archive Page Path.
		 */
		function get_tenders_archive_template($archive_template) {
			if (is_post_type_archive('tenders')) {
				$archive_template = (!file_exists(get_template_directory_uri() . '/tenders/archive-tenders.php') ) ? TENDERS_PLUGIN_PATH . 'templates/archive-tenders.php' : get_template_directory_uri() . '/tenders/archive-tenders.php';
			}
			return $archive_template;
		}
		
		// Add a shortcode
		function tenders_list_shortcode( $atts ) {
			ob_start();
			if ( !is_admin() ){
				include (!file_exists(get_template_directory_uri() . '/tenders/shortcode-tenders.php') ) ? TENDERS_PLUGIN_PATH . 'templates/shortcode-tenders.php' : get_template_directory_uri() . '/tenders/shortcode-tenders.php';
			}
			$content = ob_get_contents();
			ob_end_clean();
			return $content;			
		}		
		
		/**
		 * List tenders dropdown.
		 * 
		 * Add application listing to admin 
		 */
		function add_tender_application_filter() {
			$type = ( NULL != filter_input( INPUT_GET, 'post_type') ) ? filter_input( INPUT_GET, 'post_type') : 'post';

			//only add filter to post type you want
			if ('tenders_applications' == $type) {

				//change this to the list of values you want to show
				//in 'label' => 'value' format
				$tenders = array();
				$tenderposts = get_posts(array('posts_per_page' => -1, 'post_type' => 'tenders'));

				// All Tenders
				if ($tenderposts):
					foreach ($tenderposts as $tender):
						$tenders[$tender->ID] = $tender->post_title;
					endforeach;
				endif;

				//  Extract tenders with same title
				$duplicate_tenders = array_unique(array_diff_assoc($tenders, array_unique($tenders)));

				// Append tender id with same title's tender
				if (is_array($duplicate_tenders)):
					foreach ($tenders as $id => $tender_title):
						if (in_array($tender_title, $duplicate_tenders)):
							$_tenders[$id] = $tender_title . '-' . $id;
						else:
							$_tenders[$id] = $tender_title;
						endif;
					endforeach;
				endif;


				$selected_tender = ( NULL != filter_input( INPUT_GET, 'tender_id') ) ? filter_input( INPUT_GET, 'tender_id', FILTER_VALIDATE_INT) : 0;

				if (!empty($_tenders)) {
					?>
					<select name="tender_id">
						<option value="0"><?php _e('All Tenders', 'tenders'); ?></option>
						<?Php
						foreach ($_tenders as $key => $value) {
							printf(
								'<option value="%s"%s>%s</option>', esc_attr($key), $key == $selected_tender ? ' selected="selected"' : '', esc_attr($value)
							);
						}
						?>
					</select>
					<?php
				}
			}
		}
		
		/**
		 * View all Application.
		 * 
		 * Update query for getting all aplications against a tender.
		 */
		function get_all_applications( $query ) {
			$tender_id = ( NULL != filter_input( INPUT_GET, 'tender_id') ) ? filter_input( INPUT_GET, 'tender_id', FILTER_VALIDATE_INT) : 0;
			
			// Check for the custom post type admin screen
			if ( is_admin() && 'tenders_applications' == $query->query['post_type'] ) {
				// Add query for the student list
				if (!empty($tender_id)) {
					$qv = &$query->query_vars;
					$qv['post_parent'] = esc_attr($tender_id);
				}
			}
		}
		
		/**
		 * Allowed documents for upload
		 *
		 * @return Array Allowed mime types
		 */
		function allowed_doc_mime_types(){
			return array(
				'application/pdf',
				'application/msword',
				'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
				'application/vnd.ms-excel',
				'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		}
		
		/**
		 * Upload function
		 * 
		 * @return array Upload data
		 */
		function upload_attachment_file( $file, $supported_types = array() ){
			$upload = array();
			// Make sure the file array isn't empty
			if(!empty($file['name'])){
				if ( ! function_exists( 'wp_handle_upload' ) ) {
					require_once( ABSPATH . 'wp-admin/includes/file.php' );
				}
				// Get the file type of the upload
				$arr_file_type = wp_check_filetype(basename($file['name']));
				$uploaded_type = $arr_file_type['type'];
				
				// Check if the type is supported. If not, throw an error.
				if(in_array($uploaded_type, $supported_types)) {
					$upload_overrides = array( 'test_form' => false );
					// Use the WordPress API to upload the file
					$upload = wp_handle_upload($file, $upload_overrides);
					// Return upload meta array
					return $upload;
				}else{
					return $upload['error'] = "The file type that you've uploaded is not of the supported type.";
				}
			}else{
				return false;
			}
		}
		
		/*
		 * Array of pay types
		 *
		 * @return Array 
		 */
		function list_application_status(){
			return array(
				'new'					=> 'New',
				'approved'		=> 'Approved',
				'disapproved'	=> 'Disapproved',
				'cancelled'		=> 'Cancelled');
		}
		
		//Get pay types given shortcode
		function get_application_status($code){
			foreach($this->list_application_status() as $k => $v){
				if($k == $code){
					return $v;
				}
			}
		}
		
		//Redirect function
		function redirect($to){
			if(!headers_sent())
				header('Location: '.$to);
			else{
				return '<script type="text/javascript">
				window.location.href="'.$to.'";
				</script>
				<noscript>
				<meta http-equiv="refresh" content="0;url='.$to.'">
				</noscript>';
			}
			exit();
		}
				
	}
	
	$Tenders = new Tenders();
	$Tenders->register();

	// activation
	register_activation_hook( __FILE__, array( $Tenders, 'activate' ) );

	// deactivation
	register_deactivation_hook( __FILE__, array( $Tenders, 'deactivate' ) );
}