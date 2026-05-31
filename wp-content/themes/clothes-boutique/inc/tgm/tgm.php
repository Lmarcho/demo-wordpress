<?php
require get_template_directory() . '/inc/tgm/class-tgm-plugin-activation.php';
/**
 * Recommended plugins.
 */
function clothes_boutique_register_recommended_plugins() {
	$plugins = array(
		array(
			'name'             => __( 'WooCommerce', 'clothes-boutique' ),
			'slug'             => 'woocommerce',
			'source'           => '',
			'required'         => false,
			'force_activation' => false,
		),
		array(
			'name'             => __( 'Gutentor', 'clothes-boutique' ),
			'slug'             => 'gutentor',
			'source'           => '',
			'required'         => false,
			'force_activation' => false,
		),
		array(
			'name'             => __( 'Classic Blog Grid', 'clothes-boutique' ),
			'slug'             => 'classic-blog-grid',
			'source'           => '',
			'required'         => false,
			'force_activation' => false,
		)
	);
	$config = array();
	tgmpa( $plugins, $config );
}
add_action( 'tgmpa_register', 'clothes_boutique_register_recommended_plugins' );