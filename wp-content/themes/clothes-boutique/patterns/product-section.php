<?php
/**
 * Title: Service Section
 * Slug: clothes-boutique/product-section
 * Categories: clothes-boutique
 * Keywords: product-section
 * Block Types: core/post-content
 * Post Types: page, wp_template
 */

$clothes_boutique_pluginsList = get_option( 'active_plugins' );
$clothes_boutique_plugin = 'woocommerce/woocommerce.php';
$clothes_boutique_results = in_array( $clothes_boutique_plugin , $clothes_boutique_pluginsList);
if ( $clothes_boutique_results )  {
?>

<!-- wp:group {"metadata":{"name":"Product Section"},"className":"product-section","style":{"spacing":{"padding":{"right":"0px","left":"0px","top":"0px","bottom":"0px"},"margin":{"top":"0","bottom":"0px"}}},"backgroundColor":"quaternary","layout":{"type":"constrained","contentSize":"80%"}} -->
<div class="wp-block-group product-section has-quaternary-background-color has-background" style="margin-top:0;margin-bottom:0px;padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px"><!-- wp:group {"className":"product-head-box wow zoomIn","layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between"}} -->
<div class="wp-block-group product-head-box wow zoomIn"><!-- wp:heading {"level":3,"className":"product-sec-title","style":{"elements":{"link":{"color":{"text":"var:preset|color|heading-color"}}},"typography":{"fontSize":"26px","textTransform":"uppercase","fontStyle":"normal","fontWeight":"600"}},"textColor":"heading-color"} -->
<h3 class="wp-block-heading product-sec-title has-heading-color-color has-text-color has-link-color" style="font-size:26px;font-style:normal;font-weight:600;text-transform:uppercase"><?php echo esc_html__( 'recent products', 'clothes-boutique' ); ?></h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"product-border","style":{"border":{"top":{"color":"var:preset|color|heading-color","width":"1px"},"right":[],"bottom":[],"left":[]}}} -->
<p class="product-border" style="border-top-color:var(--wp--preset--color--heading-color);border-top-width:1px"></p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"className":"product-sec-btn"} -->
<div class="wp-block-buttons product-sec-btn"><!-- wp:button {"textColor":"secondary","style":{"color":{"background":"#00000000"},"elements":{"link":{"color":{"text":"var:preset|color|secondary"}}},"typography":{"fontSize":"15px","textTransform":"capitalize"},"border":{"radius":"5px","width":"1px"},"spacing":{"padding":{"left":"40px","right":"40px","top":"10px","bottom":"10px"}}},"borderColor":"secondary"} -->
<div class="wp-block-button"><a class="wp-block-button__link has-secondary-color has-text-color has-background has-link-color has-border-color has-secondary-border-color has-custom-font-size wp-element-button" href="#" style="border-width:1px;border-radius:5px;background-color:#00000000;padding-top:10px;padding-right:40px;padding-bottom:10px;padding-left:40px;font-size:15px;text-transform:capitalize"><?php echo esc_html__( 'view all', 'clothes-boutique' ); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group -->

<!-- wp:spacer {"height":"40px"} -->
<div style="height:40px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:woocommerce/product-collection {"queryId":0,"query":{"perPage":8,"pages":1,"offset":0,"postType":"product","order":"desc","orderBy":"date","search":"","exclude":[],"inherit":false,"taxQuery":[],"isProductCollectionBlock":true,"featured":false,"woocommerceOnSale":false,"woocommerceStockStatus":["instock","outofstock","onbackorder"],"woocommerceAttributes":[],"woocommerceHandPickedProducts":[],"timeFrame":{"operator":"in","value":"-7 days"},"filterable":false,"relatedBy":{"categories":true,"tags":true}},"tagName":"div","displayLayout":{"type":"flex","columns":4,"shrinkColumns":true},"dimensions":{"widthType":"fill"},"collection":"woocommerce/product-collection/new-arrivals","hideControls":["inherit","order","filterable"],"queryContextIncludes":["collection"],"__privatePreviewState":{"isPreview":false,"previewMessage":"Actual products will vary depending on the page being viewed."},"className":"product-content wow zoomIn"} -->
<div class="wp-block-woocommerce-product-collection product-content wow zoomIn"><!-- wp:woocommerce/product-template {"className":"product-in-content"} -->
<!-- wp:woocommerce/product-image {"showSaleBadge":false,"imageSizing":"thumbnail","isDescendentOfQueryLoop":true,"height":"350px","className":"product-img","style":{"spacing":{"margin":{"bottom":"20px"}}}} -->
<!-- wp:woocommerce/product-sale-badge {"className":"product-sale","backgroundColor":"base","textColor":"heading-color","style":{"border":{"width":"0px","style":"none","radius":"0px"},"elements":{"link":{"color":{"text":"var:preset|color|heading-color"}}},"typography":{"fontSize":"17px","lineHeight":1.6}}} /-->
<!-- /wp:woocommerce/product-image -->

<!-- wp:post-title {"textAlign":"left","level":5,"isLink":true,"className":"product-title","style":{"spacing":{"margin":{"bottom":"0.75rem","top":"0"}},"typography":{"lineHeight":"1.4","fontSize":"20px","fontStyle":"normal","fontWeight":"500","textTransform":"uppercase"},"elements":{"link":{"color":{"text":"var:preset|color|heading-color"}}}},"textColor":"heading-color","__woocommerceNamespace":"woocommerce/product-collection/product-title"} /-->

<!-- wp:group {"className":"product-price-box","style":{"spacing":{"blockGap":"25px"}},"layout":{"type":"flex","flexWrap":"nowrap"}} -->
<div class="wp-block-group product-price-box"><!-- wp:woocommerce/product-button {"textAlign":"center","isDescendentOfQueryLoop":true,"className":"product-btn","fontSize":"small"} /-->

<!-- wp:woocommerce/product-price {"isDescendentOfQueryLoop":true,"textAlign":"left","className":"product-price","textColor":"heading-color","fontSize":"small","style":{"elements":{"link":{"color":{"text":"var:preset|color|heading-color"}}},"typography":{"fontStyle":"normal","fontWeight":"500"}}} /--></div>
<!-- /wp:group -->
<!-- /wp:woocommerce/product-template --></div>
<!-- /wp:woocommerce/product-collection --></div>
<!-- /wp:group -->

<!-- wp:spacer {"height":"50px"} -->
<div style="height:50px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<?php } else { ?>

<!-- wp:group {"metadata":{"name":"Product Section"},"className":"product-section","style":{"spacing":{"padding":{"right":"0px","left":"0px","top":"0px","bottom":"0px"},"margin":{"top":"0","bottom":"0px"}}},"backgroundColor":"quaternary","layout":{"type":"constrained","contentSize":"80%"}} -->
<div class="wp-block-group product-section has-quaternary-background-color has-background" style="margin-top:0;margin-bottom:0px;padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px"><!-- wp:group {"className":"product-head-box wow zoomIn","layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between"}} -->
<div class="wp-block-group product-head-box wow zoomIn"><!-- wp:heading {"level":3,"className":"product-sec-title","style":{"elements":{"link":{"color":{"text":"var:preset|color|heading-color"}}},"typography":{"fontSize":"26px","textTransform":"uppercase","fontStyle":"normal","fontWeight":"600"}},"textColor":"heading-color"} -->
<h3 class="wp-block-heading product-sec-title has-heading-color-color has-text-color has-link-color" style="font-size:26px;font-style:normal;font-weight:600;text-transform:uppercase"><?php echo esc_html__( 'recent products', 'clothes-boutique' ); ?></h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"product-border","style":{"border":{"top":{"color":"var:preset|color|heading-color","width":"1px"},"right":[],"bottom":[],"left":[]}}} -->
<p class="product-border" style="border-top-color:var(--wp--preset--color--heading-color);border-top-width:1px"></p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"className":"product-sec-btn"} -->
<div class="wp-block-buttons product-sec-btn"><!-- wp:button {"textColor":"secondary","style":{"color":{"background":"#00000000"},"elements":{"link":{"color":{"text":"var:preset|color|secondary"}}},"typography":{"fontSize":"15px","textTransform":"capitalize"},"border":{"radius":"5px","width":"1px"},"spacing":{"padding":{"left":"40px","right":"40px","top":"10px","bottom":"10px"}}},"borderColor":"secondary"} -->
<div class="wp-block-button"><a class="wp-block-button__link has-secondary-color has-text-color has-background has-link-color has-border-color has-secondary-border-color has-custom-font-size wp-element-button" href="#" style="border-width:1px;border-radius:5px;background-color:#00000000;padding-top:10px;padding-right:40px;padding-bottom:10px;padding-left:40px;font-size:15px;text-transform:capitalize"><?php echo esc_html__( 'view all', 'clothes-boutique' ); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group -->

<!-- wp:spacer {"height":"40px"} -->
<div style="height:40px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:group {"className":"product-content wow zoomIn","layout":{"type":"default"}} -->
<div class="wp-block-group product-content wow zoomIn"><!-- wp:columns {"className":"product-in-content"} -->
<div class="wp-block-columns product-in-content"><!-- wp:column {"className":"product-box"} -->
<div class="wp-block-column product-box"><!-- wp:cover {"url":"<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/product-1.png","id":120,"dimRatio":0,"isUserOverlayColor":true,"focalPoint":{"x":0.5,"y":0},"minHeight":350,"sizeSlug":"full","className":"product-img","style":{"spacing":{"margin":{"bottom":"22px"},"padding":{"top":"0px","bottom":"0px","left":"0px","right":"0px"}}},"layout":{"type":"default"}} -->
<div class="wp-block-cover product-img" style="margin-bottom:22px;padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px;min-height:350px"><img class="wp-block-cover__image-background wp-image-120 size-full" alt="" src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/product-1.png" style="object-position:50% 0%" data-object-fit="cover" data-object-position="50% 0%"/><span aria-hidden="true" class="wp-block-cover__background has-background-dim-0 has-background-dim"></span><div class="wp-block-cover__inner-container"><!-- wp:paragraph {"align":"center","placeholder":"Write title…","className":"wc-block-components-product-sale-badge","style":{"elements":{"link":{"color":{"text":"var:preset|color|heading-color"}}},"spacing":{"padding":{"top":"20px","bottom":"20px","left":"10px","right":"10px"}},"typography":{"fontSize":"15px","textTransform":"uppercase","fontStyle":"normal","fontWeight":"600"},"border":{"radius":"0px"}},"backgroundColor":"base","textColor":"heading-color"} -->
<p class="has-text-align-center wc-block-components-product-sale-badge has-heading-color-color has-base-background-color has-text-color has-background has-link-color" style="border-radius:0px;padding-top:20px;padding-right:10px;padding-bottom:20px;padding-left:10px;font-size:15px;font-style:normal;font-weight:600;text-transform:uppercase"><?php echo esc_html__( 'sale', 'clothes-boutique' ); ?></p>
<!-- /wp:paragraph --></div></div>
<!-- /wp:cover -->

<!-- wp:heading {"className":"product-title","level":5,"style":{"typography":{"fontSize":"18px","textTransform":"uppercase","fontStyle":"normal","fontWeight":"500"}}} -->
<h5 class="wp-block-heading product-title" style="font-size:18px;font-style:normal;font-weight:500;text-transform:uppercase"><?php echo esc_html__( 'floral dress', 'clothes-boutique' ); ?></h5>
<!-- /wp:heading -->

<!-- wp:group {"className":"product-price-box","style":{"spacing":{"blockGap":"24px","margin":{"top":"8px"}}},"layout":{"type":"flex","flexWrap":"nowrap"}} -->
<div class="wp-block-group product-price-box" style="margin-top:8px"><!-- wp:buttons {"className":"product-btn"} -->
<div class="wp-block-buttons product-btn"><!-- wp:button {"backgroundColor":"secondary","style":{"spacing":{"padding":{"left":"10px","right":"10px","top":"10px","bottom":"10px"}}}} -->
<div class="wp-block-button"><a class="wp-block-button__link has-secondary-background-color has-background wp-element-button" href="#" style="padding-top:10px;padding-right:10px;padding-bottom:10px;padding-left:10px"><img class="wp-image-129" style="width: 43px;" src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/cart-img.png" alt=""></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->

<!-- wp:group {"className":"product-price","layout":{"type":"flex","flexWrap":"nowrap"}} -->
<div class="wp-block-group product-price"><!-- wp:paragraph {"className":"price-reg","style":{"typography":{"fontSize":"14px","fontStyle":"normal","fontWeight":"500","textDecoration":"line-through"}}} -->
<p class="price-reg" style="font-size:14px;font-style:normal;font-weight:500;text-decoration:line-through"><?php echo esc_html__( '$49.00', 'clothes-boutique' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"price-sale","style":{"typography":{"fontSize":"14px","fontStyle":"normal","fontWeight":"500"}}} -->
<p class="price-sale" style="font-size:14px;font-style:normal;font-weight:500"><?php echo esc_html__( '$39.00', 'clothes-boutique' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column {"className":"product-box"} -->
<div class="wp-block-column product-box"><!-- wp:cover {"url":"<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/product-2.png","id":148,"dimRatio":0,"isUserOverlayColor":true,"focalPoint":{"x":0.5,"y":0},"minHeight":350,"isDark":false,"sizeSlug":"full","className":"product-img","style":{"spacing":{"margin":{"bottom":"22px"},"padding":{"top":"0px","bottom":"0px","left":"0px","right":"0px"}}},"layout":{"type":"default"}} -->
<div class="wp-block-cover is-light product-img" style="margin-bottom:22px;padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px;min-height:350px"><img class="wp-block-cover__image-background wp-image-148 size-full" alt="" src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/product-2.png" style="object-position:50% 0%" data-object-fit="cover" data-object-position="50% 0%"/><span aria-hidden="true" class="wp-block-cover__background has-background-dim-0 has-background-dim"></span><div class="wp-block-cover__inner-container"><!-- wp:paragraph {"align":"center","placeholder":"Write title…","className":"wc-block-components-product-sale-badge","style":{"elements":{"link":{"color":{"text":"var:preset|color|heading-color"}}},"spacing":{"padding":{"top":"20px","bottom":"20px","left":"10px","right":"10px"}},"typography":{"fontSize":"15px","textTransform":"uppercase","fontStyle":"normal","fontWeight":"600"},"border":{"radius":"0px"}},"backgroundColor":"base","textColor":"heading-color"} -->
<p class="has-text-align-center wc-block-components-product-sale-badge has-heading-color-color has-base-background-color has-text-color has-background has-link-color" style="border-radius:0px;padding-top:20px;padding-right:10px;padding-bottom:20px;padding-left:10px;font-size:15px;font-style:normal;font-weight:600;text-transform:uppercase"><?php echo esc_html__( 'sale', 'clothes-boutique' ); ?></p>
<!-- /wp:paragraph --></div></div>
<!-- /wp:cover -->

<!-- wp:heading {"className":"product-title","level":5,"style":{"typography":{"fontSize":"18px","textTransform":"uppercase","fontStyle":"normal","fontWeight":"500"}}} -->
<h5 class="wp-block-heading product-title" style="font-size:18px;font-style:normal;font-weight:500;text-transform:uppercase"><?php echo esc_html__( 'Polo T-Shirt', 'clothes-boutique' ); ?></h5>
<!-- /wp:heading -->

<!-- wp:group {"className":"product-price-box","style":{"spacing":{"blockGap":"24px","margin":{"top":"8px"}}},"layout":{"type":"flex","flexWrap":"nowrap"}} -->
<div class="wp-block-group product-price-box" style="margin-top:8px"><!-- wp:buttons {"className":"product-btn"} -->
<div class="wp-block-buttons product-btn"><!-- wp:button {"backgroundColor":"secondary","style":{"spacing":{"padding":{"left":"10px","right":"10px","top":"10px","bottom":"10px"}}}} -->
<div class="wp-block-button"><a class="wp-block-button__link has-secondary-background-color has-background wp-element-button" href="#" style="padding-top:10px;padding-right:10px;padding-bottom:10px;padding-left:10px"><img class="wp-image-129" style="width: 43px;" src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/cart-img.png" alt=""></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->

<!-- wp:group {"className":"product-price","layout":{"type":"flex","flexWrap":"nowrap"}} -->
<div class="wp-block-group product-price"><!-- wp:paragraph {"className":"price-reg","style":{"typography":{"fontSize":"14px","fontStyle":"normal","fontWeight":"500","textDecoration":"line-through"}}} -->
<p class="price-reg" style="font-size:14px;font-style:normal;font-weight:500;text-decoration:line-through"><?php echo esc_html__( '$49.00', 'clothes-boutique' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"price-sale","style":{"typography":{"fontSize":"14px","fontStyle":"normal","fontWeight":"500"}}} -->
<p class="price-sale" style="font-size:14px;font-style:normal;font-weight:500"><?php echo esc_html__( '$39.00', 'clothes-boutique' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column {"className":"product-box"} -->
<div class="wp-block-column product-box"><!-- wp:cover {"url":"<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/product-3.png","id":149,"dimRatio":0,"isUserOverlayColor":true,"focalPoint":{"x":0.5,"y":0},"minHeight":350,"sizeSlug":"full","className":"product-img","style":{"spacing":{"margin":{"bottom":"22px"},"padding":{"top":"0px","bottom":"0px","left":"0px","right":"0px"}}},"layout":{"type":"default"}} -->
<div class="wp-block-cover product-img" style="margin-bottom:22px;padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px;min-height:350px"><img class="wp-block-cover__image-background wp-image-149 size-full" alt="" src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/product-3.png" style="object-position:50% 0%" data-object-fit="cover" data-object-position="50% 0%"/><span aria-hidden="true" class="wp-block-cover__background has-background-dim-0 has-background-dim"></span><div class="wp-block-cover__inner-container"><!-- wp:paragraph {"align":"center","placeholder":"Write title…","className":"wc-block-components-product-sale-badge","style":{"elements":{"link":{"color":{"text":"var:preset|color|heading-color"}}},"spacing":{"padding":{"top":"20px","bottom":"20px","left":"10px","right":"10px"}},"typography":{"fontSize":"15px","textTransform":"uppercase","fontStyle":"normal","fontWeight":"600"},"border":{"radius":"0px"}},"backgroundColor":"base","textColor":"heading-color"} -->
<p class="has-text-align-center wc-block-components-product-sale-badge has-heading-color-color has-base-background-color has-text-color has-background has-link-color" style="border-radius:0px;padding-top:20px;padding-right:10px;padding-bottom:20px;padding-left:10px;font-size:15px;font-style:normal;font-weight:600;text-transform:uppercase"><?php echo esc_html__( 'sale', 'clothes-boutique' ); ?></p>
<!-- /wp:paragraph --></div></div>
<!-- /wp:cover -->

<!-- wp:heading {"className":"product-title","level":5,"style":{"typography":{"fontSize":"18px","textTransform":"uppercase","fontStyle":"normal","fontWeight":"500"}}} -->
<h5 class="wp-block-heading product-title" style="font-size:18px;font-style:normal;font-weight:500;text-transform:uppercase"><?php echo esc_html__( 'Casual Top', 'clothes-boutique' ); ?></h5>
<!-- /wp:heading -->

<!-- wp:group {"className":"product-price-box","style":{"spacing":{"blockGap":"24px","margin":{"top":"8px"}}},"layout":{"type":"flex","flexWrap":"nowrap"}} -->
<div class="wp-block-group product-price-box" style="margin-top:8px"><!-- wp:buttons {"className":"product-btn"} -->
<div class="wp-block-buttons product-btn"><!-- wp:button {"backgroundColor":"secondary","style":{"spacing":{"padding":{"left":"10px","right":"10px","top":"10px","bottom":"10px"}}}} -->
<div class="wp-block-button"><a class="wp-block-button__link has-secondary-background-color has-background wp-element-button" href="#" style="padding-top:10px;padding-right:10px;padding-bottom:10px;padding-left:10px"><img class="wp-image-129" style="width: 43px;" src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/cart-img.png" alt=""></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->

<!-- wp:group {"className":"product-price","layout":{"type":"flex","flexWrap":"nowrap"}} -->
<div class="wp-block-group product-price"><!-- wp:paragraph {"className":"price-reg","style":{"typography":{"fontSize":"14px","fontStyle":"normal","fontWeight":"500","textDecoration":"line-through"}}} -->
<p class="price-reg" style="font-size:14px;font-style:normal;font-weight:500;text-decoration:line-through"><?php echo esc_html__( '$49.00', 'clothes-boutique' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"price-sale","style":{"typography":{"fontSize":"14px","fontStyle":"normal","fontWeight":"500"}}} -->
<p class="price-sale" style="font-size:14px;font-style:normal;font-weight:500"><?php echo esc_html__( '$39.00', 'clothes-boutique' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column {"className":"product-box"} -->
<div class="wp-block-column product-box"><!-- wp:cover {"url":"<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/product-4.png","id":150,"dimRatio":0,"isUserOverlayColor":true,"focalPoint":{"x":0.5,"y":0},"minHeight":350,"isDark":false,"sizeSlug":"full","className":"product-img","style":{"spacing":{"margin":{"bottom":"22px"},"padding":{"top":"0px","bottom":"0px","left":"0px","right":"0px"}}},"layout":{"type":"default"}} -->
<div class="wp-block-cover is-light product-img" style="margin-bottom:22px;padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px;min-height:350px"><img class="wp-block-cover__image-background wp-image-150 size-full" alt="" src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/product-4.png" style="object-position:50% 0%" data-object-fit="cover" data-object-position="50% 0%"/><span aria-hidden="true" class="wp-block-cover__background has-background-dim-0 has-background-dim"></span><div class="wp-block-cover__inner-container"><!-- wp:paragraph {"align":"center","placeholder":"Write title…","className":"wc-block-components-product-sale-badge","style":{"elements":{"link":{"color":{"text":"var:preset|color|heading-color"}}},"spacing":{"padding":{"top":"20px","bottom":"20px","left":"10px","right":"10px"}},"typography":{"fontSize":"15px","textTransform":"uppercase","fontStyle":"normal","fontWeight":"600"},"border":{"radius":"0px"}},"backgroundColor":"base","textColor":"heading-color"} -->
<p class="has-text-align-center wc-block-components-product-sale-badge has-heading-color-color has-base-background-color has-text-color has-background has-link-color" style="border-radius:0px;padding-top:20px;padding-right:10px;padding-bottom:20px;padding-left:10px;font-size:15px;font-style:normal;font-weight:600;text-transform:uppercase"><?php echo esc_html__( 'sale', 'clothes-boutique' ); ?></p>
<!-- /wp:paragraph --></div></div>
<!-- /wp:cover -->

<!-- wp:heading {"className":"product-title","level":5,"style":{"typography":{"fontSize":"18px","textTransform":"uppercase","fontStyle":"normal","fontWeight":"500"}}} -->
<h5 class="wp-block-heading product-title" style="font-size:18px;font-style:normal;font-weight:500;text-transform:uppercase"><?php echo esc_html__( 'Denim Jacket', 'clothes-boutique' ); ?></h5>
<!-- /wp:heading -->

<!-- wp:group {"className":"product-price-box","style":{"spacing":{"blockGap":"24px","margin":{"top":"8px"}}},"layout":{"type":"flex","flexWrap":"nowrap"}} -->
<div class="wp-block-group product-price-box" style="margin-top:8px"><!-- wp:buttons {"className":"product-btn"} -->
<div class="wp-block-buttons product-btn"><!-- wp:button {"backgroundColor":"secondary","style":{"spacing":{"padding":{"left":"10px","right":"10px","top":"10px","bottom":"10px"}}}} -->
<div class="wp-block-button"><a class="wp-block-button__link has-secondary-background-color has-background wp-element-button" href="#" style="padding-top:10px;padding-right:10px;padding-bottom:10px;padding-left:10px"><img class="wp-image-129" style="width: 43px;" src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/cart-img.png" alt=""></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->

<!-- wp:group {"className":"product-price","layout":{"type":"flex","flexWrap":"nowrap"}} -->
<div class="wp-block-group product-price"><!-- wp:paragraph {"className":"price-reg","style":{"typography":{"fontSize":"14px","fontStyle":"normal","fontWeight":"500","textDecoration":"line-through"}}} -->
<p class="price-reg" style="font-size:14px;font-style:normal;font-weight:500;text-decoration:line-through"><?php echo esc_html__( '$49.00', 'clothes-boutique' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"price-sale","style":{"typography":{"fontSize":"14px","fontStyle":"normal","fontWeight":"500"}}} -->
<p class="price-sale" style="font-size:14px;font-style:normal;font-weight:500"><?php echo esc_html__( '$39.00', 'clothes-boutique' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->

<!-- wp:columns {"className":"product-in-content","style":{"spacing":{"margin":{"top":"40px"}}}} -->
<div class="wp-block-columns product-in-content" style="margin-top:40px"><!-- wp:column {"className":"product-box"} -->
<div class="wp-block-column product-box"><!-- wp:cover {"url":"<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/product-5.png","id":120,"dimRatio":0,"isUserOverlayColor":true,"focalPoint":{"x":0.5,"y":0},"minHeight":350,"sizeSlug":"full","className":"product-img","style":{"spacing":{"margin":{"bottom":"22px"},"padding":{"top":"0px","bottom":"0px","left":"0px","right":"0px"}}},"layout":{"type":"default"}} -->
<div class="wp-block-cover product-img" style="margin-bottom:22px;padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px;min-height:350px"><img class="wp-block-cover__image-background wp-image-120 size-full" alt="" src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/product-5.png" style="object-position:50% 0%" data-object-fit="cover" data-object-position="50% 0%"/><span aria-hidden="true" class="wp-block-cover__background has-background-dim-0 has-background-dim"></span><div class="wp-block-cover__inner-container"><!-- wp:paragraph {"align":"center","placeholder":"Write title…","className":"wc-block-components-product-sale-badge","style":{"elements":{"link":{"color":{"text":"var:preset|color|heading-color"}}},"spacing":{"padding":{"top":"20px","bottom":"20px","left":"10px","right":"10px"}},"typography":{"fontSize":"15px","textTransform":"uppercase","fontStyle":"normal","fontWeight":"600"},"border":{"radius":"0px"}},"backgroundColor":"base","textColor":"heading-color"} -->
<p class="has-text-align-center wc-block-components-product-sale-badge has-heading-color-color has-base-background-color has-text-color has-background has-link-color" style="border-radius:0px;padding-top:20px;padding-right:10px;padding-bottom:20px;padding-left:10px;font-size:15px;font-style:normal;font-weight:600;text-transform:uppercase"><?php echo esc_html__( 'sale', 'clothes-boutique' ); ?></p>
<!-- /wp:paragraph --></div></div>
<!-- /wp:cover -->

<!-- wp:heading {"className":"product-title","level":5,"style":{"typography":{"fontSize":"18px","textTransform":"uppercase","fontStyle":"normal","fontWeight":"500"}}} -->
<h5 class="wp-block-heading product-title" style="font-size:18px;font-style:normal;font-weight:500;text-transform:uppercase"><?php echo esc_html__( 'Breeze Stole', 'clothes-boutique' ); ?></h5>
<!-- /wp:heading -->

<!-- wp:group {"className":"product-price-box","style":{"spacing":{"blockGap":"24px","margin":{"top":"8px"}}},"layout":{"type":"flex","flexWrap":"nowrap"}} -->
<div class="wp-block-group product-price-box" style="margin-top:8px"><!-- wp:buttons {"className":"product-btn"} -->
<div class="wp-block-buttons product-btn"><!-- wp:button {"backgroundColor":"secondary","style":{"spacing":{"padding":{"left":"10px","right":"10px","top":"10px","bottom":"10px"}}}} -->
<div class="wp-block-button"><a class="wp-block-button__link has-secondary-background-color has-background wp-element-button" href="#" style="padding-top:10px;padding-right:10px;padding-bottom:10px;padding-left:10px"><img class="wp-image-129" style="width: 43px;" src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/cart-img.png" alt=""></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->

<!-- wp:group {"className":"product-price","layout":{"type":"flex","flexWrap":"nowrap"}} -->
<div class="wp-block-group product-price"><!-- wp:paragraph {"className":"price-reg","style":{"typography":{"fontSize":"14px","fontStyle":"normal","fontWeight":"500","textDecoration":"line-through"}}} -->
<p class="price-reg" style="font-size:14px;font-style:normal;font-weight:500;text-decoration:line-through"><?php echo esc_html__( '$49.00', 'clothes-boutique' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"price-sale","style":{"typography":{"fontSize":"14px","fontStyle":"normal","fontWeight":"500"}}} -->
<p class="price-sale" style="font-size:14px;font-style:normal;font-weight:500"><?php echo esc_html__( '$39.00', 'clothes-boutique' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column {"className":"product-box"} -->
<div class="wp-block-column product-box"><!-- wp:cover {"url":"<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/product-6.png","id":148,"dimRatio":0,"isUserOverlayColor":true,"focalPoint":{"x":0.5,"y":0},"minHeight":350,"isDark":false,"sizeSlug":"full","className":"product-img","style":{"spacing":{"margin":{"bottom":"22px"},"padding":{"top":"0px","bottom":"0px","left":"0px","right":"0px"}}},"layout":{"type":"default"}} -->
<div class="wp-block-cover is-light product-img" style="margin-bottom:22px;padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px;min-height:350px"><img class="wp-block-cover__image-background wp-image-148 size-full" alt="" src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/product-6.png" style="object-position:50% 0%" data-object-fit="cover" data-object-position="50% 0%"/><span aria-hidden="true" class="wp-block-cover__background has-background-dim-0 has-background-dim"></span><div class="wp-block-cover__inner-container"><!-- wp:paragraph {"align":"center","placeholder":"Write title…","className":"wc-block-components-product-sale-badge","style":{"elements":{"link":{"color":{"text":"var:preset|color|heading-color"}}},"spacing":{"padding":{"top":"20px","bottom":"20px","left":"10px","right":"10px"}},"typography":{"fontSize":"15px","textTransform":"uppercase","fontStyle":"normal","fontWeight":"600"},"border":{"radius":"0px"}},"backgroundColor":"base","textColor":"heading-color"} -->
<p class="has-text-align-center wc-block-components-product-sale-badge has-heading-color-color has-base-background-color has-text-color has-background has-link-color" style="border-radius:0px;padding-top:20px;padding-right:10px;padding-bottom:20px;padding-left:10px;font-size:15px;font-style:normal;font-weight:600;text-transform:uppercase"><?php echo esc_html__( 'sale', 'clothes-boutique' ); ?></p>
<!-- /wp:paragraph --></div></div>
<!-- /wp:cover -->

<!-- wp:heading {"className":"product-title","level":5,"style":{"typography":{"fontSize":"18px","textTransform":"uppercase","fontStyle":"normal","fontWeight":"500"}}} -->
<h5 class="wp-block-heading product-title" style="font-size:18px;font-style:normal;font-weight:500;text-transform:uppercase"><?php echo esc_html__( 'StyleTop', 'clothes-boutique' ); ?></h5>
<!-- /wp:heading -->

<!-- wp:group {"className":"product-price-box","style":{"spacing":{"blockGap":"24px","margin":{"top":"8px"}}},"layout":{"type":"flex","flexWrap":"nowrap"}} -->
<div class="wp-block-group product-price-box" style="margin-top:8px"><!-- wp:buttons {"className":"product-btn"} -->
<div class="wp-block-buttons product-btn"><!-- wp:button {"backgroundColor":"secondary","style":{"spacing":{"padding":{"left":"10px","right":"10px","top":"10px","bottom":"10px"}}}} -->
<div class="wp-block-button"><a class="wp-block-button__link has-secondary-background-color has-background wp-element-button" href="#" style="padding-top:10px;padding-right:10px;padding-bottom:10px;padding-left:10px"><img class="wp-image-129" style="width: 43px;" src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/cart-img.png" alt=""></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->

<!-- wp:group {"className":"product-price","layout":{"type":"flex","flexWrap":"nowrap"}} -->
<div class="wp-block-group product-price"><!-- wp:paragraph {"className":"price-reg","style":{"typography":{"fontSize":"14px","fontStyle":"normal","fontWeight":"500","textDecoration":"line-through"}}} -->
<p class="price-reg" style="font-size:14px;font-style:normal;font-weight:500;text-decoration:line-through"><?php echo esc_html__( '$49.00', 'clothes-boutique' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"price-sale","style":{"typography":{"fontSize":"14px","fontStyle":"normal","fontWeight":"500"}}} -->
<p class="price-sale" style="font-size:14px;font-style:normal;font-weight:500"><?php echo esc_html__( '$39.00', 'clothes-boutique' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column {"className":"product-box"} -->
<div class="wp-block-column product-box"><!-- wp:cover {"url":"<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/product-7.png","id":149,"dimRatio":0,"isUserOverlayColor":true,"focalPoint":{"x":0.5,"y":0},"minHeight":350,"sizeSlug":"full","className":"product-img","style":{"spacing":{"margin":{"bottom":"22px"},"padding":{"top":"0px","bottom":"0px","left":"0px","right":"0px"}}},"layout":{"type":"default"}} -->
<div class="wp-block-cover product-img" style="margin-bottom:22px;padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px;min-height:350px"><img class="wp-block-cover__image-background wp-image-149 size-full" alt="" src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/product-7.png" style="object-position:50% 0%" data-object-fit="cover" data-object-position="50% 0%"/><span aria-hidden="true" class="wp-block-cover__background has-background-dim-0 has-background-dim"></span><div class="wp-block-cover__inner-container"><!-- wp:paragraph {"align":"center","placeholder":"Write title…","className":"wc-block-components-product-sale-badge","style":{"elements":{"link":{"color":{"text":"var:preset|color|heading-color"}}},"spacing":{"padding":{"top":"20px","bottom":"20px","left":"10px","right":"10px"}},"typography":{"fontSize":"15px","textTransform":"uppercase","fontStyle":"normal","fontWeight":"600"},"border":{"radius":"0px"}},"backgroundColor":"base","textColor":"heading-color"} -->
<p class="has-text-align-center wc-block-components-product-sale-badge has-heading-color-color has-base-background-color has-text-color has-background has-link-color" style="border-radius:0px;padding-top:20px;padding-right:10px;padding-bottom:20px;padding-left:10px;font-size:15px;font-style:normal;font-weight:600;text-transform:uppercase"><?php echo esc_html__( 'sale', 'clothes-boutique' ); ?></p>
<!-- /wp:paragraph --></div></div>
<!-- /wp:cover -->

<!-- wp:heading {"className":"product-title","level":5,"style":{"typography":{"fontSize":"18px","textTransform":"uppercase","fontStyle":"normal","fontWeight":"500"}}} -->
<h5 class="wp-block-heading product-title" style="font-size:18px;font-style:normal;font-weight:500;text-transform:uppercase"><?php echo esc_html__( 'DailyWear Top', 'clothes-boutique' ); ?></h5>
<!-- /wp:heading -->

<!-- wp:group {"className":"product-price-box","style":{"spacing":{"blockGap":"24px","margin":{"top":"8px"}}},"layout":{"type":"flex","flexWrap":"nowrap"}} -->
<div class="wp-block-group product-price-box" style="margin-top:8px"><!-- wp:buttons {"className":"product-btn"} -->
<div class="wp-block-buttons product-btn"><!-- wp:button {"backgroundColor":"secondary","style":{"spacing":{"padding":{"left":"10px","right":"10px","top":"10px","bottom":"10px"}}}} -->
<div class="wp-block-button"><a class="wp-block-button__link has-secondary-background-color has-background wp-element-button" href="#" style="padding-top:10px;padding-right:10px;padding-bottom:10px;padding-left:10px"><img class="wp-image-129" style="width: 43px;" src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/cart-img.png" alt=""></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->

<!-- wp:group {"className":"product-price","layout":{"type":"flex","flexWrap":"nowrap"}} -->
<div class="wp-block-group product-price"><!-- wp:paragraph {"className":"price-reg","style":{"typography":{"fontSize":"14px","fontStyle":"normal","fontWeight":"500","textDecoration":"line-through"}}} -->
<p class="price-reg" style="font-size:14px;font-style:normal;font-weight:500;text-decoration:line-through"><?php echo esc_html__( '$49.00', 'clothes-boutique' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"price-sale","style":{"typography":{"fontSize":"14px","fontStyle":"normal","fontWeight":"500"}}} -->
<p class="price-sale" style="font-size:14px;font-style:normal;font-weight:500"><?php echo esc_html__( '$39.00', 'clothes-boutique' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column {"className":"product-box"} -->
<div class="wp-block-column product-box"><!-- wp:cover {"url":"<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/product-8.png","id":150,"dimRatio":0,"isUserOverlayColor":true,"focalPoint":{"x":0.5,"y":0},"minHeight":350,"isDark":false,"sizeSlug":"full","className":"product-img","style":{"spacing":{"margin":{"bottom":"22px"},"padding":{"top":"0px","bottom":"0px","left":"0px","right":"0px"}}},"layout":{"type":"default"}} -->
<div class="wp-block-cover is-light product-img" style="margin-bottom:22px;padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px;min-height:350px"><img class="wp-block-cover__image-background wp-image-150 size-full" alt="" src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/product-8.png" style="object-position:50% 0%" data-object-fit="cover" data-object-position="50% 0%"/><span aria-hidden="true" class="wp-block-cover__background has-background-dim-0 has-background-dim"></span><div class="wp-block-cover__inner-container"><!-- wp:paragraph {"align":"center","placeholder":"Write title…","className":"wc-block-components-product-sale-badge","style":{"elements":{"link":{"color":{"text":"var:preset|color|heading-color"}}},"spacing":{"padding":{"top":"20px","bottom":"20px","left":"10px","right":"10px"}},"typography":{"fontSize":"15px","textTransform":"uppercase","fontStyle":"normal","fontWeight":"600"},"border":{"radius":"0px"}},"backgroundColor":"base","textColor":"heading-color"} -->
<p class="has-text-align-center wc-block-components-product-sale-badge has-heading-color-color has-base-background-color has-text-color has-background has-link-color" style="border-radius:0px;padding-top:20px;padding-right:10px;padding-bottom:20px;padding-left:10px;font-size:15px;font-style:normal;font-weight:600;text-transform:uppercase"><?php echo esc_html__( 'sale', 'clothes-boutique' ); ?></p>
<!-- /wp:paragraph --></div></div>
<!-- /wp:cover -->

<!-- wp:heading {"className":"product-title","level":5,"style":{"typography":{"fontSize":"18px","textTransform":"uppercase","fontStyle":"normal","fontWeight":"500"}}} -->
<h5 class="wp-block-heading product-title" style="font-size:18px;font-style:normal;font-weight:500;text-transform:uppercase"><?php echo esc_html__( 'DarkVibe Jacket', 'clothes-boutique' ); ?></h5>
<!-- /wp:heading -->

<!-- wp:group {"className":"product-price-box","style":{"spacing":{"blockGap":"24px","margin":{"top":"8px"}}},"layout":{"type":"flex","flexWrap":"nowrap"}} -->
<div class="wp-block-group product-price-box" style="margin-top:8px"><!-- wp:buttons {"className":"product-btn"} -->
<div class="wp-block-buttons product-btn"><!-- wp:button {"backgroundColor":"secondary","style":{"spacing":{"padding":{"left":"10px","right":"10px","top":"10px","bottom":"10px"}}}} -->
<div class="wp-block-button"><a class="wp-block-button__link has-secondary-background-color has-background wp-element-button" href="#" style="padding-top:10px;padding-right:10px;padding-bottom:10px;padding-left:10px"><img class="wp-image-129" style="width: 43px;" src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/cart-img.png" alt=""></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->

<!-- wp:group {"className":"product-price","layout":{"type":"flex","flexWrap":"nowrap"}} -->
<div class="wp-block-group product-price"><!-- wp:paragraph {"className":"price-reg","style":{"typography":{"fontSize":"14px","fontStyle":"normal","fontWeight":"500","textDecoration":"line-through"}}} -->
<p class="price-reg" style="font-size:14px;font-style:normal;font-weight:500;text-decoration:line-through"><?php echo esc_html__( '$49.00', 'clothes-boutique' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"price-sale","style":{"typography":{"fontSize":"14px","fontStyle":"normal","fontWeight":"500"}}} -->
<p class="price-sale" style="font-size:14px;font-style:normal;font-weight:500"><?php echo esc_html__( '$39.00', 'clothes-boutique' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

<!-- wp:spacer {"height":"50px"} -->
<div style="height:50px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<?php } ?>