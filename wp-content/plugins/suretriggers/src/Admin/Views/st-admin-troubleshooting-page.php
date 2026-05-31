<?php
/**
 * SureTriggers Troubleshooting Page.
 * php version 5.6
 *
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.1.4
 */

$clear_cache_url = wp_nonce_url( admin_url( 'admin.php?st-reset=true' ), 'st-reset-action' );
?>
<div class="wrap">
	<div style="font-size: 18px;">
		<?php
		printf(
			/* translators: %s: HTML link to clear cache. */
			esc_html__( 'Please re-connect your site by %s', 'suretriggers' ),
			'<a href="' . esc_url( $clear_cache_url ) . '" style="text-decoration: underline;">' .
			esc_html__( 'clearing the cache!', 'suretriggers' ) .
			'</a>'
		);
		?>
	</div>
</div>
