<?php
/**
 * Title: Category Section
 * Slug: clothes-boutique/category-section
 * Categories: clothes-boutique
 * Keywords: category-section
 * Block Types: core/post-content
 * Post Types: page, wp_template
 */

$clothes_boutique_active_plugins = get_option( 'active_plugins' );
$clothes_boutique_woo_plugin     = 'woocommerce/woocommerce.php';
$clothes_boutique_gutentor_plugin = 'gutentor/gutentor.php';
if ( in_array( $clothes_boutique_woo_plugin, $clothes_boutique_active_plugins ) && in_array( $clothes_boutique_gutentor_plugin, $clothes_boutique_active_plugins ) ) {
?>

<!-- wp:group {"metadata":{"name":"Product Category Section"},"className":"category-section","style":{"spacing":{"padding":{"right":"0px","left":"0px","bottom":"0px","top":"0px"},"margin":{"top":"0","bottom":"0"}},"border":{"radius":"15px"}},"gradient":"section-background","layout":{"type":"constrained","contentSize":"100%"}} -->
<div class="wp-block-group category-section has-section-background-gradient-background has-background" style="border-radius:15px;margin-top:0;margin-bottom:0;padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px"><!-- wp:group {"className":"category-head-box wow zoomIn","style":{"spacing":{"margin":{"bottom":"50px"}}},"layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between"}} -->
<div class="wp-block-group category-head-box wow zoomIn" style="margin-bottom:50px"><!-- wp:heading {"level":3,"className":"category-sec-title","style":{"elements":{"link":{"color":{"text":"var:preset|color|heading-color"}}},"typography":{"fontSize":"26px","textTransform":"uppercase","fontStyle":"normal","fontWeight":"600"}},"textColor":"heading-color"} -->
<h3 class="wp-block-heading category-sec-title has-heading-color-color has-text-color has-link-color" style="font-size:26px;font-style:normal;font-weight:600;text-transform:uppercase"><?php echo esc_html__( 'categories', 'clothes-boutique' ); ?></h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"category-border","style":{"border":{"top":{"color":"var:preset|color|heading-color","width":"1px"},"right":[],"bottom":[],"left":[]}}} -->
<p class="category-border" style="border-top-color:var(--wp--preset--color--heading-color);border-top-width:1px"></p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"className":"category-sec-btn"} -->
<div class="wp-block-buttons category-sec-btn"><!-- wp:button {"textColor":"secondary","style":{"color":{"background":"#00000000"},"elements":{"link":{"color":{"text":"var:preset|color|secondary"}}},"typography":{"fontSize":"15px","textTransform":"capitalize"},"border":{"radius":"5px","width":"1px"},"spacing":{"padding":{"left":"40px","right":"40px","top":"10px","bottom":"10px"}}},"borderColor":"secondary"} -->
<div class="wp-block-button"><a class="wp-block-button__link has-secondary-color has-text-color has-background has-link-color has-border-color has-secondary-border-color has-custom-font-size wp-element-button" href="#" style="border-width:1px;border-radius:5px;background-color:#00000000;padding-top:10px;padding-right:40px;padding-bottom:10px;padding-left:40px;font-size:15px;text-transform:capitalize"><?php echo esc_html__( 'view all', 'clothes-boutique' ); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group -->

<!-- wp:gutentor/t2 {"gID":"g-gwkueeb","gName":"gutentor/t2","pName":"gutentor/t2","t2Temp":1,"t2Taxonomy":"product_cat","t2Number":3,"tBtnTypography":{"fontType":"default","desktopFontSize":16,"tabletFontSize":16,"mobileFontSize":16,"textTransform":"normal"},"tBxP":{"type":"px","dTop":"15","dBottom":"15","tTop":"15","tBottom":"15","mTop":"15","mBottom":"15"},"tTitleC":{"enable":true,"normal":"#1A1A1A","hover":""},"tTitleB":{"enable":true,"normal":"#ffffff","hover":""},"tTitleM":{"dTop":"0"},"tTitleP":{"dTop":"10","dRight":"10","dBottom":"10","dLeft":"10"},"tOnCount":false,"mOnHeight":true,"mHeight":{"type":"vh","mobile":70,"tablet":70,"desktop":70},"className":"product-category-content"} /--></div>
<!-- /wp:group -->

<!-- wp:spacer {"height":"50px"} -->
<div style="height:50px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<?php } else { ?>

<!-- wp:group {"metadata":{"name":"Product Category Section"},"className":"category-section","style":{"spacing":{"padding":{"right":"0px","left":"0px","bottom":"0px","top":"0px"},"margin":{"top":"0","bottom":"0"}},"border":{"radius":"15px"}},"gradient":"section-background","layout":{"type":"constrained","contentSize":"80%"}} -->
<div class="wp-block-group category-section has-section-background-gradient-background has-background" style="border-radius:15px;margin-top:0;margin-bottom:0;padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px"><!-- wp:group {"className":"category-head-box wow zoomIn","style":{"spacing":{"margin":{"bottom":"50px"}}},"layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between"}} -->
<div class="wp-block-group category-head-box wow zoomIn" style="margin-bottom:50px"><!-- wp:heading {"level":3,"className":"category-sec-title","style":{"elements":{"link":{"color":{"text":"var:preset|color|heading-color"}}},"typography":{"fontSize":"26px","textTransform":"uppercase","fontStyle":"normal","fontWeight":"600"}},"textColor":"heading-color"} -->
<h3 class="wp-block-heading category-sec-title has-heading-color-color has-text-color has-link-color" style="font-size:26px;font-style:normal;font-weight:600;text-transform:uppercase"><?php echo esc_html__( 'categories', 'clothes-boutique' ); ?></h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"category-border","style":{"border":{"top":{"color":"var:preset|color|heading-color","width":"1px"},"right":[],"bottom":[],"left":[]}}} -->
<p class="category-border" style="border-top-color:var(--wp--preset--color--heading-color);border-top-width:1px"></p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"className":"category-sec-btn"} -->
<div class="wp-block-buttons category-sec-btn"><!-- wp:button {"textColor":"secondary","style":{"color":{"background":"#00000000"},"elements":{"link":{"color":{"text":"var:preset|color|secondary"}}},"typography":{"fontSize":"15px","textTransform":"capitalize"},"border":{"radius":"5px","width":"1px"},"spacing":{"padding":{"left":"40px","right":"40px","top":"10px","bottom":"10px"}}},"borderColor":"secondary"} -->
<div class="wp-block-button"><a class="wp-block-button__link has-secondary-color has-text-color has-background has-link-color has-border-color has-secondary-border-color has-custom-font-size wp-element-button" href="#" style="border-width:1px;border-radius:5px;background-color:#00000000;padding-top:10px;padding-right:40px;padding-bottom:10px;padding-left:40px;font-size:15px;text-transform:capitalize"><?php echo esc_html__( 'view all', 'clothes-boutique' ); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group -->

<!-- wp:columns {"className":"category-boxes wow zoomIn","style":{"spacing":{"blockGap":{"top":"30px","left":"30px"}}}} -->
<div class="wp-block-columns category-boxes wow zoomIn"><!-- wp:column {"className":"category-box"} -->
<div class="wp-block-column category-box"><!-- wp:cover {"url":"<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/category1.png","id":44,"dimRatio":0,"isUserOverlayColor":true,"focalPoint":{"x":0.5,"y":0},"minHeight":550,"isDark":false,"sizeSlug":"full","className":"category-bg","style":{"spacing":{"padding":{"top":"0px","bottom":"0px","left":"0px","right":"0px"}}},"layout":{"type":"default"}} -->
<div class="wp-block-cover is-light category-bg" style="padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px;min-height:550px"><img class="wp-block-cover__image-background wp-image-44 size-full" alt="" src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/category1.png" style="object-position:50% 0%" data-object-fit="cover" data-object-position="50% 0%"/><span aria-hidden="true" class="wp-block-cover__background has-background-dim-0 has-background-dim"></span><div class="wp-block-cover__inner-container"><!-- wp:paragraph {"align":"center","placeholder":"Write title…","className":"category-title","style":{"elements":{"link":{"color":{"text":"var:preset|color|heading-color"}}},"spacing":{"padding":{"top":"25px","bottom":"25px","left":"12px","right":"12px"}},"typography":{"textTransform":"uppercase","fontSize":"25px"}},"backgroundColor":"base","textColor":"heading-color"} -->
<p class="has-text-align-center category-title has-heading-color-color has-base-background-color has-text-color has-background has-link-color" style="padding-top:25px;padding-right:12px;padding-bottom:25px;padding-left:12px;font-size:25px;text-transform:uppercase"><a href="#"><strong><?php echo esc_html__( 'women', 'clothes-boutique' ); ?></strong><?php echo esc_html__( ' collection', 'clothes-boutique' ); ?></a></p>
<!-- /wp:paragraph --></div></div>
<!-- /wp:cover --></div>
<!-- /wp:column -->

<!-- wp:column {"className":"category-box"} -->
<div class="wp-block-column category-box"><!-- wp:cover {"url":"<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/category2.png","id":59,"dimRatio":0,"isUserOverlayColor":true,"minHeight":550,"isDark":false,"sizeSlug":"full","className":"category-bg","style":{"spacing":{"padding":{"top":"0px","bottom":"0px","left":"0px","right":"0px"}}},"layout":{"type":"default"}} -->
<div class="wp-block-cover is-light category-bg" style="padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px;min-height:550px"><img class="wp-block-cover__image-background wp-image-59 size-full" alt="" src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/category2.png" data-object-fit="cover"/><span aria-hidden="true" class="wp-block-cover__background has-background-dim-0 has-background-dim"></span><div class="wp-block-cover__inner-container"><!-- wp:paragraph {"align":"center","placeholder":"Write title…","className":"category-title","style":{"elements":{"link":{"color":{"text":"var:preset|color|heading-color"}}},"spacing":{"padding":{"top":"25px","bottom":"25px","left":"12px","right":"12px"}},"typography":{"textTransform":"uppercase","fontSize":"25px"}},"backgroundColor":"base","textColor":"heading-color"} -->
<p class="has-text-align-center category-title has-heading-color-color has-base-background-color has-text-color has-background has-link-color" style="padding-top:25px;padding-right:12px;padding-bottom:25px;padding-left:12px;font-size:25px;text-transform:uppercase"><a href="#"><strong><?php echo esc_html__( 'men', 'clothes-boutique' ); ?></strong><?php echo esc_html__( ' collection', 'clothes-boutique' ); ?></a></p>
<!-- /wp:paragraph --></div></div>
<!-- /wp:cover --></div>
<!-- /wp:column -->

<!-- wp:column {"className":"category-box"} -->
<div class="wp-block-column category-box"><!-- wp:cover {"url":"<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/category3.png","id":60,"dimRatio":0,"isUserOverlayColor":true,"minHeight":550,"sizeSlug":"full","className":"category-bg","style":{"spacing":{"padding":{"top":"0px","bottom":"0px","left":"0px","right":"0px"}}},"layout":{"type":"default"}} -->
<div class="wp-block-cover category-bg" style="padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px;min-height:550px"><img class="wp-block-cover__image-background wp-image-60 size-full" alt="" src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/category3.png" data-object-fit="cover"/><span aria-hidden="true" class="wp-block-cover__background has-background-dim-0 has-background-dim"></span><div class="wp-block-cover__inner-container"><!-- wp:paragraph {"align":"center","placeholder":"Write title…","className":"category-title","style":{"elements":{"link":{"color":{"text":"var:preset|color|heading-color"}}},"spacing":{"padding":{"top":"25px","bottom":"25px","left":"12px","right":"12px"}},"typography":{"textTransform":"uppercase","fontSize":"25px"}},"backgroundColor":"base","textColor":"heading-color"} -->
<p class="has-text-align-center category-title has-heading-color-color has-base-background-color has-text-color has-background has-link-color" style="padding-top:25px;padding-right:12px;padding-bottom:25px;padding-left:12px;font-size:25px;text-transform:uppercase"><a href="#"><strong><?php echo esc_html__( 'kids', 'clothes-boutique' ); ?></strong><?php echo esc_html__( ' collection', 'clothes-boutique' ); ?></a></p>
<!-- /wp:paragraph --></div></div>
<!-- /wp:cover --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->

<!-- wp:spacer {"height":"50px"} -->
<div style="height:50px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<?php } ?>