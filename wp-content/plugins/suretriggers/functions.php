<?php
/**
 * Global AutomatePlug Functions.
 *
 * @package  Automateplug
 */

/**
 * Get or prepare user id.
 *
 * @return int
 */
function ap_get_current_user_id() {

	$user_id = get_current_user_id();

	if ( $user_id ) {
		return $user_id;
	}

	if ( ! session_id() ) { //phpcs:ignore
		session_start(); //phpcs:ignore
	}

	if ( isset( $_SESSION['ap_user_identifier'] ) ) {
		return $_SESSION['ap_user_identifier']; //phpcs:ignore
	}

	$ap_user_id                     = wp_rand( 1000000000, 9999999999 );
	$_SESSION['ap_user_identifier'] = $ap_user_id; //phpcs:ignore

	return $_SESSION['ap_user_identifier']; //phpcs:ignore

}

/**
 * Get or prepare user id.
 *
 * @param string $email user email.
 *
 * @return int|bool
 */
function ap_get_user_id_from_email( $email ) {

	if ( empty( $email ) || ! email_exists( $email ) ) {
		return false;
	}

	$get_user = get_user_by( 'email', $email );
	if ( ! $get_user instanceof WP_User ) {
		return false;
	}
	return intval( $get_user->ID );

}

add_action(
	'in_admin_header',
	function () {
		if ( isset( $_GET['page'] ) && 'suretriggers' === sanitize_text_field( $_GET['page'] ) ) { // phpcs:ignore
			remove_all_actions( 'admin_notices' );
			remove_all_actions( 'all_admin_notices' );
		}
	},
	999
);

add_action( 'wp_login', 'suretrigger_capture_login_time', 10, 2 );

/**
 * Login time.
 *
 * @param string $user_login user login.
 * @param object $user user.
 * @return void
 */
function suretrigger_capture_login_time( $user_login, $user ) {
	if ( ! property_exists( $user, 'ID' ) ) {
		return;
	}
	update_user_meta( $user->ID, 'st_last_login', time() );
}

/**
 * Add 5-star rating display to plugin row.
 */
add_filter( 'plugin_row_meta', 'suretriggers_add_plugin_rating', 10, 2 );

/**
 * Add 5-star rating to plugin meta row.
 *
 * @param array  $links An array of the plugin's metadata.
 * @param string $file Path to the plugin file relative to the plugins directory.
 * @return array Modified array of plugin metadata.
 */
function suretriggers_add_plugin_rating( $links, $file ) {
	if ( plugin_basename( SURE_TRIGGERS_FILE ) === $file ) {
		// Check if user has already clicked the rating (stored in user meta).
		$user_id        = get_current_user_id();
		$rating_clicked = get_user_meta( $user_id, 'suretriggers_rating_clicked', true );
		
		// If rating has been clicked, don't show it.
		if ( $rating_clicked ) {
			return $links;
		}
		
		$rating_html  = '<span class="suretriggers-rating-wrapper" id="suretriggers-rating-wrapper">';
		$rating_html .= '<a href="https://wordpress.org/support/plugin/suretriggers/reviews/" target="_blank" class="suretriggers-rating-link" title="Rate this plugin" aria-label="Rate SureTriggers 5 stars on WordPress.org">';
		$rating_html .= '<span class="star-rating" role="img" aria-label="5 out of 5 stars">';
		for ( $i = 1; $i <= 5; $i++ ) {
			$rating_html .= '<span class="star star-full" aria-hidden="true"></span>';
		}
		$rating_html .= '</span>';
		$rating_html .= '<span class="screen-reader-text">Rate this plugin</span>';
		$rating_html .= '</a>';
		$rating_html .= '</span>';
		$links[]      = $rating_html;
	}
	return $links;
}

/**
 * Enqueue rating styles for plugin meta row.
 */
add_action( 'admin_enqueue_scripts', 'suretriggers_enqueue_rating_styles' );

/**
 * Enqueue CSS styles for 5-star rating display.
 * Following modular CSS organization best practices.
 *
 * @return void
 */
function suretriggers_enqueue_rating_styles() {
	// Only enqueue on plugins page where rating is displayed.
	$screen = get_current_screen();
	if ( $screen && 'plugins' === $screen->id ) {
		wp_enqueue_style(
			'suretriggers-rating',
			plugin_dir_url( SURE_TRIGGERS_FILE ) . 'assets/css/st-rating.css',
			[],
			defined( 'SURE_TRIGGERS_VER' ) ? SURE_TRIGGERS_VER : '1.0.0'
		);
		
		wp_enqueue_script(
			'suretriggers-rating-js',
			plugin_dir_url( SURE_TRIGGERS_FILE ) . 'assets/js/st-rating.js',
			[ 'jquery' ],
			defined( 'SURE_TRIGGERS_VER' ) ? SURE_TRIGGERS_VER : '1.0.0',
			true
		);
		
		// Localize script with AJAX URL and nonce.
		wp_localize_script(
			'suretriggers-rating-js',
			'suretriggers_rating_ajax',
			[
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'suretriggers_rating_nonce' ),
			]
		);
	}
}

/**
 * Handle AJAX request to mark rating as clicked.
 */
add_action( 'wp_ajax_suretriggers_rating_clicked', 'suretriggers_handle_rating_clicked' );

/**
 * Mark rating as clicked for current user.
 *
 * @return void
 */
function suretriggers_handle_rating_clicked() {
	// Check if nonce is set and verify it.
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'suretriggers_rating_nonce' ) ) {
		wp_die( 'Security check failed' );
	}
	
	// Mark rating as clicked for current user.
	$user_id = get_current_user_id();
	if ( $user_id ) {
		update_user_meta( $user_id, 'suretriggers_rating_clicked', true );
		wp_send_json_success( 'Rating marked as clicked' );
	} else {
		wp_send_json_error( 'User not logged in' );
	}
}

/**
 * SureTrigger Trigger Button shortcode.
 *
 * @param array $atts Attributes.
 * @param null  $content Content.
 * @return string|bool
 */
function suretrigger_button( $atts, $content = null ) {
	$atts = shortcode_atts(
		[
			'id'                   => 0,
			'button_label'         => __( 'Click here', 'suretriggers' ),
			'user_redirect_url'    => '',
			'visitor_redirect_url' => '',
			'button_class'         => 'suretrigger_button',
			'button_id'            => 'suretrigger_button',
			'click_loading_label'  => __( 'Clicking...', 'suretriggers' ),
			'after_clicked_label'  => __( 'Clicked!!', 'suretriggers' ),
			'click_once'           => 'true',
			'cookie_duration'      => '15',
		],
		$atts,
		'trigger_button' 
	);
	ob_start();
	$user_id = get_current_user_id();
	?>

	<form method="post" class="suretrigger_button_form" id="suretrigger_button_form_<?php echo esc_attr( (string) $atts['id'] ); ?>">
		<input type="hidden" name="st_trigger_id" value="<?php echo esc_attr( (string) $atts['id'] ); ?>" />
		<input type="hidden" name="st_nonce" value="<?php echo esc_attr( wp_create_nonce( 'suretrigger_form' ) ); ?>"/>
		<input type="hidden" name="st_login_url" value="<?php echo esc_attr( $atts['user_redirect_url'] ); ?>"/>
		<input type="hidden" name="st_non_login_url" value="<?php echo esc_attr( $atts['visitor_redirect_url'] ); ?>"/>
		<input type="hidden" name="st_click" value="<?php echo esc_attr( $atts['click_once'] ); ?>"/>
		<input type="hidden" name="st_button_label" value="<?php echo esc_attr( $atts['button_label'] ); ?>"/>
		<input type="hidden" name="st_loading_label" value="<?php echo esc_attr( $atts['click_loading_label'] ); ?>"/>
		<input type="hidden" name="st_clicked_label" value="<?php echo esc_attr( $atts['after_clicked_label'] ); ?>"/>
		<input type="hidden" name="action" value="handle_trigger_button_click"/>
		<input type="hidden" name="st_cookie_duration" value="<?php echo esc_attr( $atts['cookie_duration'] ); ?>"/>
		<input type="hidden" name="st_user_id" value="<?php echo esc_attr( (string) $user_id ); ?>"/>
		<?php
		global $post;
		if ( ! empty( $post ) && is_object( $post ) && isset( $post->ID ) && isset( $post->post_title ) ) {
			?>
			<input type="hidden" name="st_button_post_id" value="<?php echo esc_attr( $post->ID ); ?>"/>
			<input type="hidden" name="st_button_post_title" value="<?php echo esc_attr( $post->post_title ); ?>"/>
			<?php
		}
		$cookie_name = 'st_trigger_button_clicked_' . esc_attr( (string) $atts['id'] );
		if ( isset( $_COOKIE[ $cookie_name ] ) && 'yes_' . $user_id == $_COOKIE[ $cookie_name ] ) {
			?>
			<button type="button" class="<?php echo esc_attr( $atts['button_class'] ); ?>" id="<?php echo esc_attr( $atts['button_id'] ); ?>"><?php echo esc_html( $atts['after_clicked_label'] ); ?></button>
			<?php
		} else {
			?>
			<button type="button" class="<?php echo esc_attr( $atts['button_class'] ); ?>" id="<?php echo esc_attr( $atts['button_id'] ); ?>" onclick="st_trigger_ajax(this);return false;"><?php echo esc_html( $atts['button_label'] ); ?></button>
			<?php
		}
		?>
	</form>

	<?php
	return ob_get_clean();
}
add_shortcode( 'st_trigger_button', 'suretrigger_button' );

/**
 * SureTrigger Trigger Button custom style.
 *
 * @return void
 */
function suretrigger_button_custom_style() {
	wp_enqueue_style( 'st-trigger-button-style', SURE_TRIGGERS_URL . 'assets/css/st-trigger-button.css', [], SURE_TRIGGERS_VER );
	wp_enqueue_script( 'st-trigger-button-script', SURE_TRIGGERS_URL . 'assets/js/st-trigger-button.js', [], SURE_TRIGGERS_VER, true );
	wp_localize_script( 'st-trigger-button-script', 'st_ajax_object', [ 'ajax_url' => admin_url( 'admin-ajax.php' ) ] );
}
add_action( 'wp_enqueue_scripts', 'suretrigger_button_custom_style' );

/**
 * SureTrigger Trigger Button action.
 * 
 * @return void
 */
function suretrigger_trigger_button_action() {

	// Trigger the custom hook before ajax response.
	do_action( 'st_trigger_button_before_click_hook' );

	if ( ! isset( $_POST['st_nonce'] ) || ! wp_verify_nonce( wp_strip_all_tags( $_POST['st_nonce'] ), 'suretrigger_form' ) ) {
		wp_send_json_error( [ 'error' => 'Invalid nonce' ] );
	}

	if ( is_user_logged_in() ) {
		$user_id = get_current_user_id();

		if ( isset( $_POST['st_trigger_id'] ) && ! empty( $_POST['st_trigger_id'] ) ) {

			$st_trigger_id = sanitize_text_field( $_POST['st_trigger_id'] );

			$cookie_duration = isset( $_POST['st_cookie_duration'] ) ? sanitize_text_field( $_POST['st_cookie_duration'] ) : '';
			$st_click        = isset( $_POST['st_click'] ) ? sanitize_text_field( $_POST['st_click'] ) : '';

			$post_data = [];

			if ( isset( $_POST['st_button_post_id'] ) ) {
				$post_data['parent_post_id'] = sanitize_text_field( $_POST['st_button_post_id'] );
			}

			if ( isset( $_POST['st_button_post_title'] ) ) {
				$post_data['parent_post_title'] = sanitize_text_field( $_POST['st_button_post_title'] );
			}
			do_action( 'st_trigger_button_action', $st_trigger_id, $user_id, sanitize_text_field( $cookie_duration ), $st_click, $post_data );
			
			if ( isset( $_POST['st_login_url'] ) && ! empty( $_POST['st_login_url'] ) ) {
				wp_send_json_success( esc_url_raw( $_POST['st_login_url'] ) );
			}
		}
	} else {
		if ( isset( $_POST['st_non_login_url'] ) && ! empty( $_POST['st_non_login_url'] ) ) {
			wp_send_json_success( esc_url_raw( $_POST['st_non_login_url'] ) );
		} else {
			wp_send_json_success( wp_login_url() );
		}
	}

	// Trigger the custom hook after ajax response.
	do_action( 'st_trigger_button_after_click_hook' );

	wp_die();
}
add_action( 'wp_ajax_handle_trigger_button_click', 'suretrigger_trigger_button_action' );
add_action( 'wp_ajax_nopriv_handle_trigger_button_click', 'suretrigger_trigger_button_action' );

/**
 * SureTrigger Trigger Button set cookie.
 * 
 * @param int $st_trigger_id Trigger ID.
 * @param int $user_id User ID.
 * @param int $cookie_duration Cookie Duration.
 * 
 * @return void
 */
function st_trigger_button_set_cookie( $st_trigger_id, $user_id, $cookie_duration ) {
	// Set the cookie.
	$cookie_name  = 'st_trigger_button_clicked_' . $st_trigger_id;
	$cookie_value = 'yes_' . $user_id;
	if ( isset( $cookie_duration ) ) {
		$expiration = time() + 60 * 60 * 24 * intval( $cookie_duration ); // Set the expiration time as per user requested.
	} else {
		$expiration = time() + 60 * 60 * 24 * 15;
	}

	if ( ! defined( 'COOKIEPATH' ) ) {
		define( 'COOKIEPATH', '/' );
	}

	if ( ! defined( 'COOKIE_DOMAIN' ) ) {
		define( 'COOKIE_DOMAIN', false );
	}

	$secure = is_ssl();
	setcookie( $cookie_name, $cookie_value, $expiration, COOKIEPATH, COOKIE_DOMAIN, $secure, true ); // phpcs:ignore

}
add_action( 'st_trigger_button_set_cookie', 'st_trigger_button_set_cookie', 10, 3 );
