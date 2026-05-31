<?php
/**
 * Title: Header
 * Slug: clothes-boutique/header
 * Categories: header, clothes-boutique
 * Keywords: header
 * Block Types: core/template-part/header
 */
?>

<!-- wp:group {"align":"wide","className":"main-header","style":{"spacing":{"padding":{"top":"0px","bottom":"0px","left":"0px","right":"0px"}}},"layout":{"type":"constrained","justifyContent":"center","contentSize":"100%"}} -->
<div class="wp-block-group alignwide main-header" style="padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px"><!-- wp:group {"className":"header-top","style":{"spacing":{"padding":{"top":"10px","bottom":"10px"}}},"backgroundColor":"secondary","layout":{"type":"constrained","contentSize":"80%"}} -->
<div class="wp-block-group header-top has-secondary-background-color has-background" style="padding-top:10px;padding-bottom:10px"><!-- wp:columns {"verticalAlignment":"center","className":"header-top-boxes"} -->
<div class="wp-block-columns are-vertically-aligned-center header-top-boxes"><!-- wp:column {"verticalAlignment":"center","width":"15%","className":"header-phone"} -->
<div class="wp-block-column is-vertically-aligned-center header-phone" style="flex-basis:15%"><!-- wp:paragraph {"className":"header-phone-num","style":{"elements":{"link":{"color":{"text":"var:preset|color|base"}}},"typography":{"fontSize":"14px"}},"textColor":"base"} -->
<p class="header-phone-num has-base-color has-text-color has-link-color" style="font-size:14px"><a href="tel:1234567890"><i class="fa-solid fa-phone"></i><?php echo esc_html__( '+1234567890', 'clothes-boutique' ); ?></a></p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center","width":"20%","className":"header-mail"} -->
<div class="wp-block-column is-vertically-aligned-center header-mail" style="flex-basis:20%"><!-- wp:paragraph {"align":"center","className":"header-mail-text","style":{"elements":{"link":{"color":{"text":"var:preset|color|base"}}},"typography":{"fontSize":"14px"}},"textColor":"base"} -->
<p class="has-text-align-center header-mail-text has-base-color has-text-color has-link-color" style="font-size:14px"><a href="mailto:styleaura@example.com"><i class="fa-regular fa-envelope"></i><?php echo esc_html__( 'styleaura@example.com', 'clothes-boutique' ); ?></a></p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center","width":"65%","className":"header-text"} -->
<div class="wp-block-column is-vertically-aligned-center header-text" style="flex-basis:65%"><!-- wp:paragraph {"align":"right","style":{"elements":{"link":{"color":{"text":"var:preset|color|base"}}},"typography":{"fontSize":"14px"}},"textColor":"base"} -->
<p class="has-text-align-right has-base-color has-text-color has-link-color" style="font-size:14px"><i class="fa-solid fa-bullhorn"></i><?php echo esc_html__( 'Free Shipping on All Orders Above $50 — Shop the Latest Fashion Trends Now!', 'clothes-boutique' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"header-bottom","style":{"spacing":{"margin":{"top":"0px","bottom":"0px"},"padding":{"top":"10px","bottom":"10px"}}},"backgroundColor":"primary","layout":{"type":"constrained","contentSize":"80%"}} -->
<div class="wp-block-group header-bottom has-primary-background-color has-background" style="margin-top:0px;margin-bottom:0px;padding-top:10px;padding-bottom:10px"><!-- wp:columns {"verticalAlignment":"center","className":"header-btm-middle","style":{"spacing":{"blockGap":{"top":"var:preset|spacing|20","left":"10px"}}}} -->
<div class="wp-block-columns are-vertically-aligned-center header-btm-middle"><!-- wp:column {"verticalAlignment":"center","width":"20%","className":"header-logo-box"} -->
<div class="wp-block-column is-vertically-aligned-center header-logo-box" style="flex-basis:20%"><!-- wp:group {"className":"logo-box","layout":{"type":"constrained"}} -->
<div class="wp-block-group logo-box"><!-- wp:site-title {"level":0,"style":{"typography":{"fontSize":"30px","fontStyle":"normal","fontWeight":"400","textTransform":"capitalize"},"elements":{"link":{"color":{"text":"var:preset|color|heading-color"}}}},"textColor":"heading-color","fontFamily":"satisfy"} /--></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center","width":"50%","className":"header-menu-box"} -->
<div class="wp-block-column is-vertically-aligned-center header-menu-box" style="flex-basis:50%"><!-- wp:navigation {"textColor":"heading-color","icon":"menu","overlayBackgroundColor":"secondary","overlayTextColor":"base","metadata":{"ignoredHookedBlocks":["woocommerce/customer-account","woocommerce/mini-cart"]},"style":{"spacing":{"blockGap":"25px"},"typography":{"fontStyle":"normal","fontWeight":"500","fontSize":"15px","textTransform":"capitalize"}},"fontFamily":"body","layout":{"type":"flex","justifyContent":"center"}} -->
<!-- wp:navigation-link {"label":"home","url":"#","kind":"custom","isTopLevelLink":true} /-->

<!-- wp:navigation-link {"label":"shop","url":"#","kind":"custom","isTopLevelLink":true} /-->

<!-- wp:navigation-link {"label":"men","url":"#","kind":"custom","isTopLevelLink":true} /-->

<!-- wp:navigation-link {"label":"women","url":"#","kind":"custom","isTopLevelLink":true} /-->

<!-- wp:navigation-link {"label":"kids","url":"#","kind":"custom","isTopLevelLink":true} /-->

<!-- wp:navigation-link {"label":"Buy Now","opensInNewTab":true,"url":"https://www.theclassictemplates.com/products/clothing-store-wordpress-theme","kind":"custom","isTopLevelLink":true} /-->
<!-- /wp:navigation --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center","width":"30%","className":"header-btn-box"} -->
<div class="wp-block-column is-vertically-aligned-center header-btn-box" style="flex-basis:30%"><!-- wp:group {"className":"header-top-box","style":{"spacing":{"blockGap":"35px"}},"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"right"}} -->
<div class="wp-block-group header-top-box"><!-- wp:search {"label":"Search","showLabel":false,"placeholder":"Search For cloths","widthUnit":"%","buttonText":"Search","buttonPosition":"button-inside","buttonUseIcon":true,"query":{"post_type":"product"},"className":"header-search","style":{"elements":{"link":{"color":{"text":"var:preset|color|secondary"}}},"border":{"radius":"5px","width":"1px"}},"backgroundColor":"button-color","textColor":"secondary","borderColor":"secondary","namespace":"woocommerce/product-search"} /-->

<!-- wp:group {"className":"header-woo-btns","style":{"spacing":{"blockGap":"10px"}},"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"right"}} -->
<div class="wp-block-group header-woo-btns"><!-- wp:woocommerce/cart-link {"content":"","className":"header-cart","style":{"elements":{"link":{"color":{"text":"var:preset|color|heading-color"}}}}} /-->

<!-- wp:woocommerce/customer-account {"displayStyle":"icon_only","iconStyle":"alt","iconClass":"wc-block-customer-account__account-icon","className":"header-account","textColor":"heading-color","style":{"typography":{"fontSize":"25px"},"elements":{"link":{"color":{"text":"var:preset|color|heading-color"}}},"spacing":{"padding":{"top":"0px","bottom":"0px","left":"0px","right":"0px"}}}} /--></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->