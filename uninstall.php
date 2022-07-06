<?php 
if( !defined( 'ABSPATH') && !defined('WP_UNINSTALL_PLUGIN') )
	exit();

// Clear Database stored data
$tenders = get_posts( array( 'post_type' => 'tenders', 'numberposts' => -1 ) );

foreach( $tenders as $tender ) {
	wp_delete_post( $tender->ID, true );
	wp_delete_attachment( $tender->ID, true );
}

$tenders_applications = get_posts( array( 'post_type' => 'tenders_applications', 'numberposts' => -1 ) );

foreach( $tenders_applications as $tenders_application ) {
	wp_delete_post( $tenders_application->ID, true );
	wp_delete_attachment( $tenders_application->ID, true );
}
