<?php
/*
Plugin Name: Soliloquy Scheduled Slides
Plugin URI: http://wpninjas.com
Description: Add a begin and expiration date to slides. Expired and slides set for the future will not show up in your slider display.
Version: 1.0
Author: The WP Ninjas
Author URI: http://wpninjas.com
*/

add_action( 'tgmsp_after_meta_defaults', 'wpnj_add_dates_soliloquy' );
function wpnj_add_dates_soliloquy( $post ) {
   $begin = get_post_meta( $post->ID, '_soliloquy_image_begin', true );
   $end = get_post_meta( $post->ID, '_soliloquy_image_end', true );

   echo '<tr id="soliloquy-begin-box-'.$post->ID.'" valign="middle">';
       echo '<th scope="row"><label for="soliloquy-begin-'.$post->ID.'">' . __( 'Begin Date', 'wpnj_soliloquy' ) . '</label></th>';
       echo '<td>';
           echo '<input type="text" id="soliloquy-begin-'.$post->ID.'" class="soliloquy-begin" size="75" name="_soliloquy_uploads[begin]" value="' . $begin . '" /><span class="description">' . __( 'Select a date to begin showing this slide.', 'wpnj_soliloquy' ) . '</span>';
       echo '</td>';
   echo '</tr>';
   echo '<tr id="soliloquy-end-box-'.$post->ID.'" valign="middle">';
       echo '<th scope="row"><label for="soliloquy-end-'.$post->ID.'">' . __( 'Expiration Date', 'wpnj_soliloquy' ) . '</label></th>';
       echo '<td>';
           echo '<input type="text" id="soliloquy-end-'.$post->ID.'" class="soliloquy-end" size="75" name="_soliloquy_uploads[end]" value="' . $end . '" /><span class="description">' . __( 'Select a date to stop showing this slide.', 'wpnj_soliloquy' ) . '</span>';
       echo '</td>';
   echo '</tr>';
}

add_action( 'tgmsp_ajax_update_meta', 'wpnj_save_dates_soliloquy' );
function wpnj_save_dates_soliloquy( $data ){
	/** Make sure attachment ID is an integer */
	$attachment_id = (int) $data['attach'];
	update_post_meta( $attachment_id, '_soliloquy_image_begin', $data['soliloquy-begin'] );
	update_post_meta( $attachment_id, '_soliloquy_image_end', $data['soliloquy-end'] );
}


add_filter( 'tgmsp_image_data', 'wpnj_filter_image_soliloquy', 10, 6 );
function wpnj_filter_image_soliloquy( $image, $attachment, $id ){
	$begin = get_post_meta( $attachment->ID, '_soliloquy_image_begin', true );
	$end = get_post_meta( $attachment->ID, '_soliloquy_image_end', true );
	$image['begin'] = $begin;
	$image['end'] = $end;
	return $image;
}

//Enqueue the datepicker jQuery UI extension.
add_action( 'admin_init', 'wpnj_add_datepicker' );
function wpnj_add_datepicker(){
	if( isset( $_REQUEST['post'] ) ){
		if( get_post_type( $_REQUEST['post'] ) == 'soliloquy' ){
			wp_enqueue_script( 'jquery-ui-datepicker' );
            wp_enqueue_style('jquery-style', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css');
		}
	}
}

//Enqueue the datepicker jQuery UI extension.
add_action( 'admin_print_scripts-media-upload-popup', 'wpnj_add_media_datepicker' );
function wpnj_add_media_datepicker(){
	wp_enqueue_script( 'jquery-ui-datepicker' );
    wp_enqueue_style('jquery-style', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css');
}

add_action( 'tgmsp_after_image_meta_table', 'wpnj_add_soliloquy_js' );
function wpnj_add_soliloquy_js( $attachment ){
	?>
	<script type="text/javascript">
		jQuery(document).ready(function($) {

			$("#soliloquy-begin-<?php echo $attachment->ID;?>").datepicker();
			$("#soliloquy-end-<?php echo $attachment->ID;?>").datepicker();

			$(".soliloquy-meta-submit").live("click", function(e){
				$("#soliloquy-begin-<?php echo $attachment->ID;?>").removeClass("hasDatepicker");
				$("#soliloquy-end-<?php echo $attachment->ID;?>").removeClass("hasDatepicker");
			});
		});
	</script>

	<?php
}

add_action( 'tgmsp_slider_images', 'wpnj_filter_image_array_soliloquy', 10, 3);
function wpnj_filter_image_array_soliloquy( $images, $meta, $attachments ){
	$today = current_time( 'timestamp' );
	$tmp_array = array();
	for ($x=0; $x < count( $images ); $x++) {
		$begin = $images[$x]['begin'];
		$end = $images[$x]['end'];

		$begin = strtotime( $begin." 12:00 am" );
		$end = strtotime( $end." 11:59 pm" );

		if( $begin != '' AND $end != '' ){
			if( $today > $begin AND $today < $end ){
				$tmp_array[] = $images[$x];
			}
		}else if( $end != '' ){
			if( $today < $end ){
				$tmp_array[] = $images[$x];
			}
		}else{
			$tmp_array[] = $images[$x];
		}

	}

	return $tmp_array;
}

add_filter( 'tgmsp_media_fields', 'wpnj_filter_media_fields', 10, 2 );
function wpnj_filter_media_fields( $fields, $attachment ){
	$fields['soliloquy_begin'] = array(
		'label' => 'Begin Date',
		'input' => 'text',
		'value' => get_post_meta( $attachment->ID, '_soliloquy_image_begin', true )
	);
	$fields['soliloquy_end'] = array(
		'label' => 'End Date',
		'input' => 'text',
		'value' => get_post_meta( $attachment->ID, '_soliloquy_image_end', true )
	);

	$html = '<script type="text/javascript">';
		$html .= 'jQuery(document).ready(function($) {';
			$html .= 'jQuery("#attachments\\\['.$attachment->ID.'\\\]\\\[soliloquy_begin\\\]").datepicker();';
			$html .= 'jQuery("#attachments\\\['.$attachment->ID.'\\\]\\\[soliloquy_end\\\]").datepicker();';
			$html .= 'jQuery(".soliloquy-meta-submit").live("click", function(e){';
				$html .= 'jQuery("#attachments\\['.$attachment->ID.'\\]\\[soliloquy_begin\\]").removeClass("hasDatepicker");';
				$html .= 'jQuery("#attachments\\['.$attachment->ID.'\\]\\[soliloquy_end\\]").removeClass("hasDatepicker");';
			$html .= '});';
		$html .= '});';
	$html .= '</script>';

	$fields['soliloquy_date_js'] = array(
		'label' => '',
		'input' => 'html',
		'html' 	=> $html
	);
	return $fields;
}

add_action( 'tgmsp_update_media_fields', 'wpnj_save_media_fields', 10, 2 );
function wpnj_save_media_fields( $attachment, $post_var ){
	update_post_meta( $attachment['ID'], '_soliloquy_image_begin', $post_var['soliloquy_begin'] );
	update_post_meta( $attachment['ID'], '_soliloquy_image_end', $post_var['soliloquy_end'] );
}

add_filter( 'tgmsp_ajax_refresh_callback', 'wpnj_ajax_refresh_filter', 10, 2 );
function wpnj_ajax_refresh_filter( $image, $attachment ){
	$begin = get_post_meta( $attachment->ID, '_soliloquy_image_begin', true );
	$end = get_post_meta( $attachment->ID, '_soliloquy_image_end', true );
	$html = '<tr id="soliloquy-begin-box-'.$attachment->ID.'" valign="middle">';
       $html .= '<th scope="row"><label for="soliloquy-begin-'.$attachment->ID.'">Begin Date</label></th>';
       $html .= '<td>';
           $html .= '<input type="text" id="soliloquy-begin-'.$attachment->ID.'" class="soliloquy-begin" size="75" name="_soliloquy_uploads[begin]" value="' . $begin . '" /><span class="description">Select a date to begin showing this slide.</span>';
       $html .= '</td>';
   $html .= '</tr>';
   $html .= '<tr id="soliloquy-end-box-'.$attachment->ID.'" valign="middle">';
       $html .= '<th scope="row"><label for="soliloquy-end-'.$attachment->ID.'">End Date</label></th>';
       $html .= '<td>';
           $html .= '<input type="text" id="soliloquy-end-'.$attachment->ID.'" class="soliloquy-end" size="75" name="_soliloquy_uploads[end]" value="' . $end . '" /><span class="description">Select a date to stop showing this slide.</span>';
       $html .= '</td>';
   $html .= '</tr>';

	$image['after_meta_defaults'] = $html;
	return $image;
}

add_filter( 'tgmsp_ajax_refresh_callback', 'wpnj_ajax_refresh_filter_js', 10, 2 );
function wpnj_ajax_refresh_filter_js( $image, $attachment ){
	$html = '<script type="text/javascript">';
		$html .= 'jQuery(document).ready(function($) {';
			$html .= '$("#soliloquy-begin-'.$attachment->ID.'").datepicker();';
			$html .= '$("#soliloquy-end-'.$attachment->ID.'").datepicker();';

			$html .= '$(".soliloquy-meta-submit").live("mousedown", function(e){';
				$html .= '$("#soliloquy-begin-'.$attachment->ID.'").removeClass("hasDatepicker");';
				$html .= '$("#soliloquy-end-'.$attachment->ID.'").removeClass("hasDatepicker");';
			$html .= '});';
		$html .= '});';
	$html .= '</script>';
	$image['after_image_meta_table'] = $html;
	return $image;
}

/*
I used this to ensure that the stuff was being output to the front properly.

function test($true, $id, $images, $soliloquy_data, $soliloquy_count, $slider) {
   foreach ($images as $image) {
       echo "<pre>";
       print_r( $image );
       echo "</pre>";
   }
}
add_filter( 'tgmsp_pre_load_slider', 'test', 10, 6 );
*/