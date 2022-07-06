<?php
/**
 * The Template for displaying all single tenders
 *
 * This template can be overridden by copying it to yourtheme/tenders/single-tenders.php
 *
 * @package     Tenders/Templates
 * @version     1.2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
ob_start();

get_header();

global $post;

$error = new WP_Error();
$FormSaved = (1 === filter_input( INPUT_GET, 'success', FILTER_VALIDATE_INT ))?TRUE:FALSE;
$FormData = array();
$FormInitiated = filter_input(INPUT_POST, 'SubmitApplication');

if( $FormInitiated ){
	$noune = filter_input(INPUT_POST, 'tenders_applications_form_nonce');
	if ( ! isset( $noune ) || ! wp_verify_nonce( $noune, '_tenders_applications' ) ){
		return;
	}else{
		if ( is_user_logged_in() ) {
			$current_user = wp_get_current_user();
		}
		// Process form data
		$parent_id = filter_input( INPUT_POST, 'tender_id', FILTER_VALIDATE_INT );
		// Add form data into an array
		$FormData['tenderapp_firstname']			= filter_input(INPUT_POST, 'tenderapp_firstname');
		$FormData['tenderapp_lastname']				= filter_input(INPUT_POST, 'tenderapp_lastname');
		$FormData['tenderapp_phone']					= filter_input(INPUT_POST, 'tenderapp_phone');
		$FormData['tenderapp_email']					= filter_input(INPUT_POST, 'tenderapp_email', FILTER_VALIDATE_EMAIL);
		$FormData['tenderapp_companyname']		= filter_input(INPUT_POST, 'tenderapp_companyname');
		$FormData['tenderapp_companywebsite']	= esc_url_raw( filter_input(INPUT_POST, 'tenderapp_companywebsite', FILTER_VALIDATE_URL) );
		$FormData['tenderapp_status']					= 'new';
		
		//Validate data
		if( empty($FormData['tenderapp_firstname']) )
		$error->add( 'invalid', 'First name must be provided' );
		if( empty($FormData['tenderapp_lastname']) )
		$error->add( 'invalid', 'Last name must be provided' );
		if( empty($FormData['tenderapp_phone']) )
		$error->add( 'invalid', 'Phone number must be provided' );
		if( empty($FormData['tenderapp_email']) )
		$error->add( 'invalid', 'A valid email address is required' );
		if( empty($FormData['tenderapp_companyname']) )
		$error->add( 'invalid', 'Company must be provided' );		

		if( empty($parent_id) || sizeof( $error->get_error_messages() ) > 0 ){
			$FormSaved = FALSE;
		}else{
			$args = apply_filters('tenders_applications_insert_post_args', array(
				'post_type'    => 'tenders_applications',
				'post_content' => '',
				'post_parent'  => intval( $parent_id ),
				'post_title'   => trim( esc_html( strip_tags( get_the_title( $parent_id ) ) ) ),
				'post_status'  => 'publish',
			));			
			$pid = wp_insert_post($args);
			
			// Save Applicant Details
			foreach( $FormData as $key => $val ){
				if (substr($key, 0, 10) == 'tenderapp_'){
					$val = is_array( $val ) ? serialize( $val ) : $val;		
					add_post_meta( $pid, $key, sanitize_text_field( $val ) );					
				}
			}
			
			//Upload documents
			// Setup the array of supported file types.
			$supported_types = $Tenders->allowed_doc_mime_types();
			
			foreach( $_FILES as $file => $filemeta ){         
				//Attempt to upload the file
				$upload = $Tenders->upload_attachment_file( $filemeta, $supported_types );
				
				//Check if file was uploaded and update
				if(isset($upload['error']) && $upload['error'] != 0) {
					wp_die('There was an error uploading your file. The error is: ' . $upload['error']);
				}else{
					$ufiles = get_post_meta( $pid, $file, true );
					if( empty( $ufiles ) ) $ufiles = array();
					$ufiles[] = $upload;
					update_post_meta( $pid, $file, $ufiles );
					//update_post_meta( $pid, $file, esc_url_raw($upload['url']) );
				}
			}
			
			//wp_safe_redirect( get_permalink( $post->post_parent ) );
			$redirect = get_permalink( $post->post_parent ).'?success=1';
			echo $Tenders->redirect($redirect);
		}
	}
}
?>
<section class="tenders u-align-left u-clearfix u-white u-section-3" id="singe_tenders">
	<div class="container single-tenders u-clearfix u-sheet u-sheet-1">
		<?php 
		if( have_posts() ) :
			while( have_posts() ) : the_post();
				?>
				<div class="tender-description">
					<?php
						the_title( '<h2>', '</h2>' );
						the_content();
						
						$tender_doc = get_post_meta( $post->ID, 'tender_document', true );
						if( !empty( $tender_doc[0]['url'] ) ){
							echo '<a href="'.esc_url( $tender_doc[0]['url'] ) .'" class="btn btn-primary" target="_blank">'. esc_html('Download Tender Document', 'tenders') .'</a>';
						}
						?>
				</div>
				<div class="clearfix"></div>
				<div class="tender-application-form">
					<?php
					if ( !$FormSaved ) {
						$FormData['tenderapp_firstname']			= !empty($FormData['tenderapp_firstname'])?$FormData['tenderapp_firstname']:$current_user->user_firstname;
						$FormData['tenderapp_lastname']				= !empty($FormData['tenderapp_lastname'])?$FormData['tenderapp_lastname']:$current_user->user_lastname;
						$FormData['tenderapp_phone']					= !empty($FormData['tenderapp_phone'])?$FormData['tenderapp_phone']:get_the_author_meta( 'user_phone', $current_user->ID );
						$FormData['tenderapp_email']					= !empty($FormData['tenderapp_email'])?$FormData['tenderapp_email']:$current_user->user_email;
						$FormData['tenderapp_companyname']		= !empty($FormData['tenderapp_companyname'])?$FormData['tenderapp_companyname']:'';
						$FormData['tenderapp_companywebsite']	= !empty($FormData['tenderapp_companywebsite'])?$FormData['tenderapp_companywebsite']:'';
						
						if( sizeof( $error->get_error_messages() ) > 0 ){
							echo '<div class="error">
								<p>Something went wrong. Please rectify these errors:</p>
								<ul>
									<li>'. implode( '</li><li>', $error->get_error_messages() ) .'</li>
								</ul>
							</div>';
						}
					?>
					<form id="tenders_form" class="form-horizontal" method="post" action="" enctype="multipart/form-data">
						<input type="hidden" name="action" value="tenders_applications_form">
						<input type="hidden" name="tender_id" value="<?php the_ID(); ?>" >
						<?php wp_nonce_field( '_tenders_applications', 'tenders_applications_form_nonce' ); ?>
						<h3><?php esc_html_e('Apply online', 'tenders'); ?></h3>
						<p><?php esc_html_e('Fields marked with asterisk are required', 'tenders'); ?></p>
						<div class="personal-applicant-details">
							<div class="row">
								<div class="col-md-4">
									<label for="tenderapp_firstname" class="control-label"><?php esc_html_e('First Name', 'tenders'); ?> <span class="required">*</span></label>
								</div>								
								<div class="col-md-8">
									<div class="form-group">	
										<input type="text" name="tenderapp_firstname" value="<?php esc_html_e( $FormData['tenderapp_firstname'], 'tenders' ); ?>" class="form-control required" id="tenderapp_firstname" required>
									</div>
								</div>
							</div>
							<div class="row">
								<div class="col-md-4">
									<label for="tenderapp_lastname" class="control-label"><?php esc_html_e('Last Name', 'tenders'); ?> <span class="required">*</span></label>
								</div>								
								<div class="col-md-8">
									<div class="form-group">
										<input type="text" name="tenderapp_lastname" value="<?php esc_html_e( $FormData['tenderapp_lastname'], 'tenders' ); ?>" class="form-control required" id="tenderapp_lastname" required>
									</div>
								</div>
							</div>
							<div class="row">
								<div class="col-md-4">
									<label for="tenderapp_phone" class="control-label"><?php esc_html_e('Phone', 'tenders'); ?> <span class="required">*</span></label>
								</div>								
								<div class="col-md-8">
									<div class="form-group">
										<input type="tel" name="tenderapp_phone" value="<?php esc_html_e( $FormData['tenderapp_phone'], 'tenders' ); ?>" class="form-control required phone" id="tenderapp_phone" required>
									</div>
								</div>
							</div>
							<div class="row">
								<div class="col-md-4">
									<label for="tenderapp_email" class="control-label"><?php esc_html_e('Email', 'tenders'); ?> <span class="required">*</span></label>
								</div>								
								<div class="col-md-8">
									<div class="form-group">
										<input type="email" name="tenderapp_email" value="<?php esc_html_e( $FormData['tenderapp_email'], 'tenders' ); ?>" class="form-control required email" id="tenderapp_email"  required>
									</div>
								</div>	
							</div>					
						</div>
						<div class="tender-applicant-details">
							<div class="row">
								<div class="col-md-4">
									<label for="tenderapp_companyname" class="control-label"><?php esc_html_e('Company Name', 'tenders'); ?> <span class="required">*</span></label>
								</div>								
								<div class="col-md-8">
									<div class="form-group">
										<input type="text" name="tenderapp_companyname" value="<?php esc_html_e( $FormData['tenderapp_companyname'], 'tenders' ); ?>" class="form-control required" id="tenderapp_companyname" required>
									</div>
								</div>
							</div>
							<div class="row">
								<div class="col-md-4">
									<label for="tenderapp_companywebsite" class="control-label"><?php esc_html_e('Company Website', 'tenders'); ?>
	</label>
								</div>								
								<div class="col-md-8">
									<div class="form-group">
										<input type="url" name="tenderapp_companywebsite" value="<?php esc_html_e( $FormData['tenderapp_companywebsite'], 'tenders' ); ?>" class="form-control" id="tenderapp_companywebsite" placeholder="http://www.example.com">
									</div>
								</div>
							</div>
							<div class="row">
								<div class="col-md-4">
									<label for="file_krapin" class="control-label"><?php esc_html_e('Upload KRA Pin Certificate', 'tenders'); ?> <span class="required">*</span></label>
								</div>								
								<div class="col-md-8">
									<div class="form-group">
										<input type="file" name="file_krapin" value="" class="form-control required" id="file_krapin" required>
									</div>
								</div>
							</div>
							<div class="row">
								<div class="col-md-4">
									<label for="file_kracert" class="control-label"><?php esc_html_e('Upload KRA Clearance Certificate', 'tenders'); ?> <span class="required">*</span></label>
								</div>								
								<div class="col-md-8">
									<div class="form-group">
										<input type="file" name="file_kracert" value="" class="form-control required" id="file_kracert" required>
									</div>
								</div>
							</div>
							<div class="row">
								<div class="col-md-4">
									<label for="file_nssfcert" class="control-label"><?php esc_html_e('Upload NSSF Clearance Certificate', 'tenders'); ?></label>
								</div>								
								<div class="col-md-8">
									<div class="form-group">
										<input type="file" name="file_nssfcert" value="" class="form-control" id="file_nssfcert">
									</div>
								</div>
							</div>
							<div class="row">
								<div class="col-md-4">
									<label for="file_nhiffcert" class="control-label"><?php esc_html_e('Upload NHIF Clearance Certificate', 'tenders'); ?></label>
								</div>								
								<div class="col-md-8">
									<div class="form-group">
										<input type="file" name="file_nhiffcert" value="" class="form-control" id="file_nhiffcert">
									</div>
								</div>
							</div>
							<div class="row">
								<div class="col-md-4">
									<label for="file_tenderdoc" class="control-label"><?php esc_html_e('Upload the Filled Tender Document', 'tenders'); ?></label>
								</div>								
								<div class="col-md-8">
									<div class="form-group">
										<input type="file" name="file_tenderdoc" value="" class="form-control" id="file_tenderdoc">
									</div>
								</div>
							</div>
							
							<div class="form-group">
								<input type="submit" name="<?php esc_html_e('SubmitApplication', 'tenders'); ?>" class="btn btn-primary" id="app_submit">
							</div>
						</div>		
					</form>
					<?php
					}else{
						_e( '<div class="success">Your details were submitted successfully.</div>', 'tenders' );
					}
					?>
				</div>
				<?php
			endwhile; 
			// end of the loop. 
		else:
		
			//Show 404 Not found
			?>
			<div class="text-center">
				<h2 class="section-heading"><?php esc_html_e( 'Ooops! No Content Available', 'tenders' );?></h2>
				<p class="section-subheading text-muted">
				<?php esc_html_e( 'We&rsquo;re sorry you landed on this page. It seems we can&rsquo;t find what you&rsquo;re looking for. Perhaps searching can help.', 'tenders' ); ?>
				</p>
				<?php get_search_form(); ?>
			</div>
			<?php
			
		endif;
		?>
	</div>
</section>
<?php
$html_single = ob_get_clean();

/**
 * Modify the Tenders Single Page Template. 
 *                                       
 * @param   html    $html_single   Tenders Single Page HTML.                   
 */
echo apply_filters('single_tenders', $html_single);

get_footer();

/* Omit closing PHP tag at the end of PHP files to avoid "headers already sent" issues. */

