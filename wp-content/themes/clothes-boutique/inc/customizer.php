<?php
/**
 * Space Exploration: Customizer
 *
 * @subpackage Space Exploration
 * @since 1.0
 */

 function clothes_boutique_upgrade_pro_options( $wp_customize ) {

	wp_enqueue_style('customizercustom_css', esc_url( get_template_directory_uri() ). '/assets/css/customize-controls.css');

	$wp_customize->add_section(
		'upgrade_premium',
		array(
			'title'    => esc_html__( 'About Clothes Boutique', 'clothes-boutique' ),
			'priority' => 1,
		)
	);

	class Clothes_Boutique_Pro_Button_Customize_Control extends WP_Customize_Control {
		public $type = 'upgrade_premium';

		function render_content() {
			?>
			<div class="pro_info">
				<ul>
					<li><a class="upgrade-to-pro pro-btn" href="<?php echo esc_url( CLOTHES_BOUTIQUE_PREMIUM_PAGE ); ?>" target="_blank"><i class="dashicons dashicons-cart"></i><?php esc_html_e( 'Upgrade Pro', 'clothes-boutique' ); ?> </a></li>

					<li><a class="upgrade-to-pro" href="<?php echo esc_url( CLOTHES_BOUTIQUE_PRO_DEMO ); ?>" target="_blank"><i class="dashicons dashicons-awards"></i><?php esc_html_e( 'Premium Demo', 'clothes-boutique' ); ?> </a></li>
					
					<li><a class="upgrade-to-pro" href="<?php echo esc_url( CLOTHES_BOUTIQUE_REVIEW ); ?>" target="_blank"><i class="dashicons dashicons-star-filled"></i><?php esc_html_e( 'Rate Us', 'clothes-boutique' ); ?> </a></li>
					
					<li><a class="upgrade-to-pro" href="<?php echo esc_url( CLOTHES_BOUTIQUE_SUPPORT ); ?>" target="_blank"><i class="dashicons dashicons-lightbulb"></i><?php esc_html_e( 'Support Forum', 'clothes-boutique' ); ?> </a></li>	
					
					<li><a class="upgrade-to-pro" href="<?php echo esc_url( CLOTHES_BOUTIQUE_THEME_PAGE ); ?>" target="_blank"><i class="dashicons dashicons-admin-appearance"></i><?php esc_html_e( 'Theme Page', 'clothes-boutique' ); ?> </a></li>
				
					<li><a class="upgrade-to-pro" href="<?php echo esc_url( CLOTHES_BOUTIQUE_THEME_DOCUMENTATION ); ?>" target="_blank"><i class="dashicons dashicons-visibility"></i><?php esc_html_e( 'Theme Documentation', 'clothes-boutique' ); ?> </a></li>
				</ul>
			</div>
			<?php
		}
	}

	$wp_customize->add_setting(
		'pro_info_buttons',
		array(
			'capability'        => 'edit_theme_options',
			'sanitize_callback' => 'clothes_boutique_sanitize_text',
		)
	);

	$wp_customize->add_control(
		new Clothes_Boutique_Pro_Button_Customize_Control(
			$wp_customize,
			'pro_info_buttons',
			array(
				'section' => 'upgrade_premium',
			)
		)
	);
}
add_action( 'customize_register', 'clothes_boutique_upgrade_pro_options' );