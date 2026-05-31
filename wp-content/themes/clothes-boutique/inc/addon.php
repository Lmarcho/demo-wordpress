<?php
/*
 * @package Clothes Boutique
 */


 function clothes_boutique_admin_enqueue_scripts() {
    wp_enqueue_style( 'clothes-boutique-admin-style', esc_url( get_template_directory_uri() ).'/assets/css/addon.css' );
}
add_action( 'admin_enqueue_scripts', 'clothes_boutique_admin_enqueue_scripts' );

function clothes_boutique_theme_info_menu_link() {

    $clothes_boutique_theme = wp_get_theme();
    add_theme_page(
        /* translators: 1: Theme name. */
        sprintf( esc_html__( 'Welcome to %1$s', 'clothes-boutique' ), $clothes_boutique_theme->get( 'Name' )),
        esc_html__( 'Theme Info', 'clothes-boutique' ),
        'edit_theme_options',
        'clothes-boutique',
        'clothes_boutique_theme_info_page'
    );
}
add_action( 'admin_menu', 'clothes_boutique_theme_info_menu_link' );

function clothes_boutique_theme_info_page() {

    $clothes_boutique_theme = wp_get_theme();
    ?>
<div class="wrap theme-info-wrap">
    <h1><?php printf( esc_html__( 'Welcome to %1$s', 'clothes-boutique' ), esc_html($clothes_boutique_theme->get( 'Name' ))); ?>
    </h1>
    <p class="theme-description">
    <?php esc_html_e( 'Do you want to configure this theme? Look no further, our easy-to-follow theme documentation will walk you through it.', 'clothes-boutique' ); ?>
    </p>
    <div class="columns-wrapper clearfix theme-demo">
        <div class="column column-quarter clearfix start-box"></div>
        <div class="column column-first clearfix">
            <div class="important-link">
                <div class="main-box columns-wrapper clearfix">

                    <div class="themelink column column-half column-border clearfix">
                        <p><strong><?php esc_html_e( 'Free Theme Documentation', 'clothes-boutique' ); ?></strong></p>
                        <p><?php esc_html_e( 'Need more details? Please check our complete and detailed documentation for full theme setup.', 'clothes-boutique' ); ?></p>
                        <a href="<?php echo esc_url( CLOTHES_BOUTIQUE_THEME_DOCUMENTATION ); ?>" target="_blank">
                        <?php esc_html_e( 'Documentation', 'clothes-boutique' ); ?>
                        </a>
                    </div>

                    <div class="themelink column column-half column-padding clearfix">
                        <p><strong><?php esc_html_e( 'Need Help?', 'clothes-boutique' ); ?></strong></p>
                        <p><?php esc_html_e( 'Go to our support forum to help you out in case of queries and doubts regarding our theme.', 'clothes-boutique' ); ?></p>
                        <a href="<?php echo esc_url( CLOTHES_BOUTIQUE_SUPPORT ); ?>" target="_blank">
                        <?php esc_html_e( 'Contact Us', 'clothes-boutique' ); ?>
                        </a>
                    </div>
                </div>
                <hr>
                <div class="main-box columns-wrapper clearfix">

                    <div class="themelink column column-half column-border clearfix">
                        <p><strong><?php esc_html_e( 'Pro version of our theme', 'clothes-boutique' ); ?></strong></p>
                        <p><?php esc_html_e( 'Are you excited for our theme? Then we will proceed for pro version of theme.', 'clothes-boutique' ); ?></p>
                        <a class="get-premium" href="<?php echo esc_url( CLOTHES_BOUTIQUE_PREMIUM_PAGE ); ?>" target="_blank">
                        <?php esc_html_e( 'Get Premium', 'clothes-boutique' ); ?>
                        </a>
                    </div>

                    <div class="themelink column column-half column-padding clearfix">
                        <p><strong><?php esc_html_e( 'Leave us a review', 'clothes-boutique' ); ?></strong></p>
                        <p><?php esc_html_e( 'Are you enjoying our theme? We would love to hear your feedback.', 'clothes-boutique' ); ?></p>
                        <a href="<?php echo esc_url( CLOTHES_BOUTIQUE_REVIEW ); ?>" target="_blank">
                        <?php esc_html_e( 'Rate This Theme', 'clothes-boutique' ); ?>
                        </a>
                    </div>

                </div>
            </div>
        </div>
        <div class="column column-quarter clearfix start-box"> 
            <div class="bundle-info">
                <img src="<?php echo esc_url( get_template_directory_uri().'/assets/images/bundle.png'); ?>" alt="<?php echo esc_attr( 'screenshot', 'clothes-boutique'); ?>" class="bundle-image"/>
                <div class="bundle-content themelink">
                    <h3><?php esc_html_e( 'WordPress Theme Bundle', 'clothes-boutique' ); ?></h3>
                    <small><b><?php esc_html_e( 'Get access to a collection of 100+ stunning WordPress themes for just $99 — featuring designs for every business niche!', 'clothes-boutique' ); ?></small></b>
                    <a class="get-premium" href="<?php echo esc_url( CLOTHES_BOUTIQUE_BUNDLE_PAGE ); ?>" target="_blank">
                    <?php esc_html_e( 'Get Bundle at 20% OFF', 'clothes-boutique' ); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div id="getting-started">
        <div class="section">
            <h3><?php 
            /* translators: %s: Theme name. */
            printf( esc_html__( 'Getting started with %s', 'clothes-boutique' ),
            esc_html($clothes_boutique_theme->get( 'Name' ))); ?></h3>
            <div class="columns-wrapper clearfix">
                <div class="column column-half clearfix">
                    <div class="section themelink">
                        <div class="">
                            <a class="" href="<?php echo esc_url( CLOTHES_BOUTIQUE_PREMIUM_PAGE ); ?>" target="_blank"><?php esc_html_e( 'Get Premium', 'clothes-boutique' ); ?></a>
                            <a href="<?php echo esc_url( CLOTHES_BOUTIQUE_PRO_DEMO ); ?>" target="_blank"><?php esc_html_e( 'View Demo', 'clothes-boutique' ); ?></a>
                            <a class="get-premium" href="<?php echo esc_url( CLOTHES_BOUTIQUE_BUNDLE_PAGE ); ?>" target="_blank"><?php esc_html_e( 'Bundle of 100+ Themes at $99', 'clothes-boutique' ); ?></a>
                        </div>
                        <div class="theme-description-1"><?php echo esc_html($clothes_boutique_theme->get( 'Description' )); ?></div>
                    </div>
                </div>
                <div class="column column-half clearfix">
                    <img src="<?php echo esc_url( $clothes_boutique_theme->get_screenshot() ); ?>" alt="<?php echo esc_attr( 'screenshot', 'clothes-boutique'); ?>"/>
                </div>
            </div>
        </div>
    </div>
    <hr>
    <div id="theme-author">
      <p><?php
        /* translators: 1: Theme name, 2: Author name, 3: Call to action text. */
        printf( esc_html__( '%1$s is proudly brought to you by %2$s. If you like this theme, %3$s :)', 'clothes-boutique' ),
            esc_html($clothes_boutique_theme->get( 'Name' )),
            '<a target="_blank" href="' . esc_url( 'https://www.theclassictemplates.com/', 'clothes-boutique' ) . '">classictemplate</a>',
            '<a target="_blank" href="' . esc_url(CLOTHES_BOUTIQUE_REVIEW ) . '" title="' . esc_attr__( 'Rate it', 'clothes-boutique' ) . '">' . esc_html_x( 'rate it', 'If you like this theme, rate it', 'clothes-boutique' ) . '</a>'
        );
        ?></p>
    </div>
</div>
<?php
}
?>