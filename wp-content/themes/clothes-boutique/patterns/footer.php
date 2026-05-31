<?php
/**
 * Title: Footer
 * Slug: clothes-boutique/footer
 * Categories: footer,clothes-boutique
 * Keywords: footer
 * Block Types: core/template-part/footer
 */
?>

<!-- wp:group {"className":"footer-outer","style":{"spacing":{"margin":{"top":"40px"}}},"layout":{"type":"default"}} -->
<div class="wp-block-group footer-outer" style="margin-top:40px"><!-- wp:group {"align":"full","className":"footer-section","style":{"spacing":{"padding":{"right":"0px","left":"0px"}},"border":{"radius":{"topLeft":"15px","topRight":"15px"}}},"backgroundColor":"secondary","layout":{"type":"constrained","contentSize":"80%"}} -->
<div class="wp-block-group alignfull footer-section has-secondary-background-color has-background" style="border-top-left-radius:15px;border-top-right-radius:15px;padding-right:0px;padding-left:0px"><!-- wp:columns {"className":"footer-boxes","style":{"spacing":{"padding":{"top":"40px","bottom":"25px"},"blockGap":{"top":"30px"}}}} -->
<div class="wp-block-columns footer-boxes" style="padding-top:40px;padding-bottom:25px"><!-- wp:column {"className":"footer-box1 wow bounceInUp","style":{"spacing":{"padding":{"right":"var:preset|spacing|50"}}}} -->
<div class="wp-block-column footer-box1 wow bounceInUp" style="padding-right:var(--wp--preset--spacing--50)"><!-- wp:site-title {"style":{"elements":{"link":{"color":{"text":"var:preset|color|base"}}},"typography":{"fontSize":"30px"}},"textColor":"base"} /-->

<!-- wp:paragraph {"style":{"elements":{"link":{"color":{"text":"var:preset|color|base"}}},"typography":{"fontSize":"14px"}},"textColor":"base"} -->
<p class="has-base-color has-text-color has-link-color" style="font-size:14px"><em><?php echo esc_html__( 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.', 'clothes-boutique' ); ?></em></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"footer-phone","style":{"elements":{"link":{"color":{"text":"var:preset|color|base"}}},"typography":{"fontSize":"14px"}},"textColor":"base"} -->
<p class="footer-phone has-base-color has-text-color has-link-color" style="font-size:14px"><a href="tel:1234567890"><i class="fa-solid fa-phone"></i><?php echo esc_html__( '+1234567890', 'clothes-boutique' ); ?></a></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"footer-mail","style":{"elements":{"link":{"color":{"text":"var:preset|color|base"}}},"typography":{"fontSize":"14px"}},"textColor":"base"} -->
<p class="footer-mail has-base-color has-text-color has-link-color" style="font-size:14px"><a href="mailto:styleaura@example.com"><i class="fa-regular fa-envelope"></i><?php echo esc_html__( 'styleaura@example.com', 'clothes-boutique' ); ?></a></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"footer-location","style":{"elements":{"link":{"color":{"text":"var:preset|color|base"}}},"typography":{"fontSize":"14px"}},"textColor":"base"} -->
<p class="footer-location has-base-color has-text-color has-link-color" style="font-size:14px"><a href="#"><i class="fa-solid fa-location-arrow"></i><?php echo esc_html__( '300 Lane, Los Angeles, CA 90028, USA', 'clothes-boutique' ); ?></a></p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column {"className":"footer-box2 wow bounceInUp"} -->
<div class="wp-block-column footer-box2 wow bounceInUp"><!-- wp:heading {"level":4,"style":{"elements":{"link":{"color":{"text":"var:preset|color|base"}}},"typography":{"fontSize":"26px","textTransform":"capitalize","fontStyle":"normal","fontWeight":"500"}},"textColor":"base","fontFamily":"heading"} -->
<h4 class="wp-block-heading has-base-color has-text-color has-link-color has-heading-font-family" style="font-size:26px;font-style:normal;font-weight:500;text-transform:capitalize"><em><?php echo esc_html__( 'more links', 'clothes-boutique' ); ?></em></h4>
<!-- /wp:heading -->

<!-- wp:navigation {"textColor":"base","overlayMenu":"never","icon":"menu","overlayBackgroundColor":"secondary","metadata":{"ignoredHookedBlocks":["woocommerce/customer-account"]},"style":{"spacing":{"blockGap":"12px"},"typography":{"fontStyle":"normal","fontWeight":"400","textTransform":"capitalize","fontSize":"16px"}},"layout":{"type":"flex","justifyContent":"left","orientation":"vertical"}} -->
<!-- wp:navigation-link {"label":"Home","url":"#","kind":"custom","isTopLevelLink":true} /-->

<!-- wp:navigation-link {"label":"Shop","url":"#","kind":"custom","isTopLevelLink":true} /-->

<!-- wp:navigation-link {"label":"Men","url":"#","kind":"custom","isTopLevelLink":true} /-->

<!-- wp:navigation-link {"label":"Women","url":"#","kind":"custom","isTopLevelLink":true} /-->

<!-- wp:navigation-link {"label":"Kids","url":"#","kind":"custom","isTopLevelLink":true} /-->

<!-- wp:navigation-link {"label":"Blog","url":"#","kind":"custom","isTopLevelLink":true} /-->

<!-- wp:navigation-link {"label":"Contact","url":"#","kind":"custom","isTopLevelLink":true} /-->

<!-- /wp:navigation --></div>
<!-- /wp:column -->

<!-- wp:column {"className":"footer-box3 wow bounceInUp"} -->
<div class="wp-block-column footer-box3 wow bounceInUp"><!-- wp:heading {"level":4,"style":{"elements":{"link":{"color":{"text":"var:preset|color|base"}}},"typography":{"fontSize":"26px","textTransform":"capitalize","fontStyle":"normal","fontWeight":"500"}},"textColor":"base","fontFamily":"heading"} -->
<h4 class="wp-block-heading has-base-color has-text-color has-link-color has-heading-font-family" style="font-size:26px;font-style:normal;font-weight:500;text-transform:capitalize"><em><?php echo esc_html__( 'categories', 'clothes-boutique' ); ?></em></h4>
<!-- /wp:heading -->

<!-- wp:list {"style":{"typography":{"fontSize":"16px"}}} -->
<ul style="font-size:16px" class="wp-block-list"><!-- wp:list-item {"style":{"elements":{"link":{"color":{"text":"var:preset|color|base"}}},"spacing":{"margin":{"bottom":"10px"}},"typography":{"textTransform":"capitalize"}},"textColor":"base"} -->
<li class="has-base-color has-text-color has-link-color" style="margin-bottom:10px;text-transform:capitalize"><a href="#"><em><?php echo esc_html__( 'men', 'clothes-boutique' ); ?></em></a></li>
<!-- /wp:list-item -->

<!-- wp:list-item {"style":{"elements":{"link":{"color":{"text":"var:preset|color|base"}}},"spacing":{"margin":{"bottom":"10px"}},"typography":{"textTransform":"capitalize"}},"textColor":"base"} -->
<li class="has-base-color has-text-color has-link-color" style="margin-bottom:10px;text-transform:capitalize"><a href="#"><em><?php echo esc_html__( 'women', 'clothes-boutique' ); ?></em></a></li>
<!-- /wp:list-item -->

<!-- wp:list-item {"style":{"elements":{"link":{"color":{"text":"var:preset|color|base"}}},"spacing":{"margin":{"bottom":"10px"}},"typography":{"textTransform":"capitalize"}},"textColor":"base"} -->
<li class="has-base-color has-text-color has-link-color" style="margin-bottom:10px;text-transform:capitalize"><a href="#"><em><?php echo esc_html__( 'kids', 'clothes-boutique' ); ?></em></a></li>
<!-- /wp:list-item -->

<!-- wp:list-item {"style":{"elements":{"link":{"color":{"text":"var:preset|color|base"}}},"spacing":{"margin":{"bottom":"10px"}},"typography":{"textTransform":"capitalize"}},"textColor":"base"} -->
<li class="has-base-color has-text-color has-link-color" style="margin-bottom:10px;text-transform:capitalize"><a href="#"><em><?php echo esc_html__( 'sale', 'clothes-boutique' ); ?></em></a></li>
<!-- /wp:list-item --></ul>
<!-- /wp:list --></div>
<!-- /wp:column -->

<!-- wp:column {"className":"footer-box4 wow bounceInUp"} -->
<div class="wp-block-column footer-box4 wow bounceInUp"><!-- wp:heading {"level":4,"style":{"elements":{"link":{"color":{"text":"var:preset|color|base"}}},"typography":{"fontSize":"26px","textTransform":"capitalize","fontStyle":"normal","fontWeight":"500"}},"textColor":"base","fontFamily":"heading"} -->
<h4 class="wp-block-heading has-base-color has-text-color has-link-color has-heading-font-family" style="font-size:26px;font-style:normal;font-weight:500;text-transform:capitalize"><em><?php echo esc_html__( 'social media', 'clothes-boutique' ); ?></em></h4>
<!-- /wp:heading -->

<!-- wp:social-links {"iconColor":"base","iconColorValue":"#ffffff","openInNewTab":true,"showLabels":true,"className":"is-style-logos-only footer-social-box","layout":{"type":"flex","orientation":"vertical"}} -->
<ul class="wp-block-social-links has-visible-labels has-icon-color is-style-logos-only footer-social-box"><!-- wp:social-link {"url":"www.youtube.com","service":"youtube"} /-->

<!-- wp:social-link {"url":"www.instagram.com","service":"instagram"} /-->

<!-- wp:social-link {"url":"www.x.com","service":"x"} /-->

<!-- wp:social-link {"url":"www.pinterest.com","service":"pinterest"} /-->

<!-- wp:social-link {"url":"www.facebook.com","service":"facebook"} /--></ul>
<!-- /wp:social-links --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->

<!-- wp:paragraph {"align":"center","className":"footer-copyright","style":{"elements":{"link":{"color":{"text":"var:preset|color|base"}}},"spacing":{"padding":{"top":"30px","bottom":"30px"}},"border":{"top":{"color":"var:preset|color|base","width":"1px"}}},"textColor":"base"} -->
<p class="has-text-align-center footer-copyright has-base-color has-text-color has-link-color" style="border-top-color:var(--wp--preset--color--base);border-top-width:1px;padding-top:30px;padding-bottom:30px"><a rel="noreferrer noopener" href="https://www.theclassictemplates.com/products/clothes-boutique" target="_blank"><?php echo esc_html__( 'Clothes Boutique Theme', 'clothes-boutique' ); ?></a><?php echo esc_html__( ' By Classic Templates', 'clothes-boutique' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"right"}} -->
<div class="wp-block-buttons"><!-- wp:button {"className":"scroll-top-btn","style":{"spacing":{"padding":{"left":"0","right":"0","top":"0","bottom":"0"}}}} -->
<div class="wp-block-button scroll-top-btn"><a class="wp-block-button__link wp-element-button" style="padding-top:0;padding-right:0;padding-bottom:0;padding-left:0"><i class="fa-solid fa-angle-up"></i></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->