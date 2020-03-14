<?php
/*
Plugin Name: Add external files
Description: Add external files to the media library without importing, i.e. uploading them to your WordPress site.
Version: 1.0.0
Author: Rex Kong
*/
namespace aef;

function init_aef() {
	$style = 'aef-css';
	$css_file = plugins_url( '/add-files-url.css', __FILE__ );
	wp_register_style( $style, $css_file );
	wp_enqueue_style( $style );
	$script = 'aef-js';
	$js_file = plugins_url( '/add-files-url.js', __FILE__ );
	wp_register_script( $script, $js_file, array( 'jquery' ) );
	wp_enqueue_script( $script );
}
add_action( 'admin_enqueue_scripts', 'aef\init_aef' );

add_action( 'admin_menu', 'aef\add_submenu' );
add_action( 'post-plupload-upload-ui', 'aef\post_upload_ui' );
add_action( 'post-html-upload-ui', 'aef\post_upload_ui' );
add_action( 'wp_ajax_add_external_files_url', 'aef\wp_ajax_add_external_files_url' );
add_action( 'admin_post_add_external_files_url', 'aef\admin_post_add_external_files_url' );

add_filter( 'get_attached_file', function( $file, $attachment_id ) {
	if ( empty( $file ) ) {
		$post = get_post( $attachment_id );
		if ( get_post_type( $post ) == 'attachment' ) {
			return $post->guid;
		}
	}
	return $file;
}, 10, 2 );

// 菜单名字
function add_submenu() {
	add_submenu_page(
		'upload.php',
		__( 'Add External Files URL' ),
		__( 'Add External Files URL' ),
		'manage_options',
		'add-external-files-url',
		'aef\print_submenu_page'
	);
}

function post_upload_ui() {
	$media_library_mode = get_user_option( 'media_library_mode', get_current_user_id() );
?>
	<div id="aef-in-upload-ui">
		<div class="row1">
			<?php echo __('or'); ?>
		</div>
		<div class="row2">
			<?php if ( 'grid' === $media_library_mode ) : ?>
				<button id="aef-show" class="button button-large">
					<?php echo __('Add External Files URL'); ?>
				</button>
				<?php print_media_new_panel( true ); ?>
			<?php else : ?>
				<a class="button button-large" href="<?php echo esc_url( admin_url( '/upload.php?page=add-external-files-url', __FILE__ ) ); ?>">
					<?php echo __('Add External Files URL'); ?>
				</a>
			<?php endif; ?>
		</div>
	</div>
<?php
}

// 主界面
function print_submenu_page() {
?>
	<form action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="post">
	  <?php print_media_new_panel( false ); ?>
	</form>
<?php
}

// 界面UI
function print_media_new_panel( $is_in_upload_ui ) {
?>
	<div id="aef-media-new-panel" <?php if ( $is_in_upload_ui ) : ?>style="display: none"<?php endif; ?>>
		<label id="aef-urls-label"><?php echo __('Add files from URLs'); ?></label>
		<input type="text" id="aef-urls" name="urls" required placeholder="<?php echo __("Please fill in the URLs.");?>" value="<?php if ( isset( $_GET['urls'] ) ) echo esc_url( $_GET['urls'] ); ?>"></input>
		<input type="text" name="title" id="aef-title" required placeholder="<?php echo __("Please fill in the title.");?>" value="<?php if ( isset( $_GET['urls'] ) )?>">
		<div id="aef-hidden" <?php if ( $is_in_upload_ui || empty( $_GET['error'] ) ) : ?>style="display: none"<?php endif; ?>>
		<div>
			<span id="aef-error"><?php if ( isset( $_GET['error'] ) ) echo esc_html( $_GET['error'] ); ?></span>
			<?php echo _('Please fill in the following properties manually. If you leave the fields blank (or 0 for width/height), the plugin will try to resolve them automatically.'); ?>
		</div>
		</div>
		<div id="aef-buttons-row">
		<input type="hidden" name="action" value="add_external_files_url">
		<span class="spinner"></span>
		<input type="button" id="aef-clear" class="button" value="<?php echo __('Clear') ?>">
		<input type="submit" id="aef-add" class="button button-primary" value="<?php echo __('Add') ?>">
		<?php if ( $is_in_upload_ui ) : ?>
			<input type="button" id="aef-cancel" class="button" value="<?php echo __('Cancel') ?>">
		<?php endif; ?>
		</div>
	</div>
<?php
}

// 请求方法
function wp_ajax_add_external_files_url() {
	$info = add_external_files_url();
	$attachment_ids = $info['attachment_ids'];
	$attachments = array();
	foreach ( $attachment_ids as $attachment_id ) {
		if ( $attachment = wp_prepare_attachment_for_js( $attachment_id ) ) {
			array_push( $attachments, $attachment );
		} else {
			$error = "There's an attachment sucessfully inserted to the media library but failed to be retrieved from the database to be displayed on the page.";
		}
	}
	$info['attachments'] = $attachments;
	if ( isset( $error ) ) {
		$info['error'] = isset( $info['error'] ) ? $info['error'] . "\nAnother error also occurred. " . $error : $error;
	}
	wp_send_json_success( $info );
}

function admin_post_add_external_files_url() {
	$info = add_external_files_url();
	$redirect_url = 'upload.php';
	$urls = $info['urls'];
	if ( ! empty( $urls ) ) {
		$redirect_url = $redirect_url . '?page=add-external-files-url&urls=' . urlencode( $urls );
		$redirect_url = $redirect_url . '&error=' . urlencode( $info['error'] );
		$redirect_url = $redirect_url . '&width=' . urlencode( $info['width'] );
		$redirect_url = $redirect_url . '&height=' . urlencode( $info['height'] );
	}
	wp_redirect( admin_url( $redirect_url ) );
	exit;
}

function sanitize_and_validate_input() {
	$raw_urls = explode( "\n", $_POST['urls'] );
	$urls = array();
	foreach ( $raw_urls as $i => $raw_url ) {
		$urls[$i] = esc_url_raw( trim( $raw_url ) );
	}
    unset( $url );

	$input = array(
		'urls' =>  $urls,
		'title' => sanitize_text_field( $_POST['title'] ),
		'width' => sanitize_text_field( $_POST['width'] ),
		'height' => sanitize_text_field( $_POST['height'] )
	);

	return $input;
}

function add_external_files_url() {
	$info = sanitize_and_validate_input();

	if ( isset( $info['error'] ) ) {
		return $info;
	}

	$urls = $info['urls'];
	$width = $info['width'];
	$height = $info['height'];
	$title = $info['title'];

	$attachment_ids = array();
	$failed_urls = array();
	

	foreach ( $urls as $url ) {
		$width_of_the_image = 0;
		$height_of_the_image = 0;
		$filename = wp_basename( $url );
		$attachment = array(
			'guid' => $url,
			'post_title' => $title,
		);
		$attachment_metadata = array(
			'width' => $width_of_the_image,
			'height' => $height_of_the_image,
			'file' => $title );
		$attachment_metadata['sizes'] = array( 'full' => $attachment_metadata );
		$attachment_id = wp_insert_attachment( $attachment );
		wp_update_attachment_metadata( $attachment_id, $attachment_metadata );
		array_push( $attachment_ids, $attachment_id );
	}

	$info['attachment_ids'] = $attachment_ids;

	$failed_urls_string = implode( "\n", $failed_urls );
	$info['urls'] = $failed_urls_string;

	if ( ! empty( $failed_urls_string ) ) {
		$info['error'] = 'Failed to get info of the files.';
	}

	return $info;
}
