<?php
/**
 * Title: Banner
 * Slug: clothes-boutique/banner
 * Categories: clothes-boutique
 * Keywords: banner
 * Block Types: core/post-content
 * Post Types: page, wp_template
 */
?>

<!-- wp:group {"tagName":"main","metadata":{"name":"Banner"},"style":{"spacing":{"margin":{"top":"0px","bottom":"0px"},"padding":{"right":"0px","left":"0px"}}},"layout":{"type":"constrained","contentSize":"100%"}} -->
<main class="wp-block-group" style="margin-top:0px;margin-bottom:0px;padding-right:0px;padding-left:0px"><!-- wp:group {"className":"banner-section","style":{"dimensions":{"minHeight":"700px"}},"backgroundColor":"primary","layout":{"type":"constrained","contentSize":"80%"}} -->
<div class="wp-block-group banner-section has-primary-background-color has-background" style="min-height:700px"><!-- wp:columns {"className":"banner-content-box wow zoomIn"} -->
<div class="wp-block-columns banner-content-box wow zoomIn"><!-- wp:column {"verticalAlignment":"center","width":"45%","className":"banner-left-box"} -->
<div class="wp-block-column is-vertically-aligned-center banner-left-box" style="flex-basis:45%"><!-- wp:paragraph {"className":"bnr-sub-title","style":{"elements":{"link":{"color":{"text":"var:preset|color|secondary"}}},"typography":{"fontSize":"20px"}},"textColor":"secondary","fontFamily":"satisfy"} -->
<p class="bnr-sub-title has-secondary-color has-text-color has-link-color has-satisfy-font-family" style="font-size:20px"><?php echo esc_html__( 'Up to 60% OFF', 'clothes-boutique' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:heading {"className":"bnr-title","style":{"elements":{"link":{"color":{"text":"var:preset|color|secondary"}}},"typography":{"fontSize":"38px","textTransform":"capitalize","lineHeight":1.4,"fontStyle":"normal","fontWeight":"600"},"spacing":{"margin":{"top":"8px"}}},"textColor":"secondary"} -->
<h2 class="wp-block-heading bnr-title has-secondary-color has-text-color has-link-color" style="margin-top:8px;font-size:38px;font-style:normal;font-weight:600;line-height:1.4;text-transform:capitalize"><?php echo esc_html__( 'new season collection trendy fashion for every mood', 'clothes-boutique' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"bnr-content","style":{"elements":{"link":{"color":{"text":"var:preset|color|secondary"}}},"spacing":{"margin":{"top":"8px"}}},"textColor":"secondary"} -->
<p class="bnr-content has-secondary-color has-text-color has-link-color" style="margin-top:8px"><?php echo esc_html__( 'Discover your perfect look with our curated range of stylish outfits and accessories.', 'clothes-boutique' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"className":"bnr-button"} -->
<div class="wp-block-buttons bnr-button"><!-- wp:button {"backgroundColor":"button-color","textColor":"secondary","style":{"elements":{"link":{"color":{"text":"var:preset|color|secondary"}}},"border":{"radius":"5px"},"typography":{"fontSize":"15px","fontStyle":"normal","fontWeight":"500","textTransform":"capitalize"},"spacing":{"padding":{"left":"30px","right":"30px","top":"12px","bottom":"12px"}}}} -->
<div class="wp-block-button"><a class="wp-block-button__link has-secondary-color has-button-color-background-color has-text-color has-background has-link-color has-custom-font-size wp-element-button" href="#" style="border-radius:5px;padding-top:12px;padding-right:30px;padding-bottom:12px;padding-left:30px;font-size:15px;font-style:normal;font-weight:500;text-transform:capitalize"><?php echo esc_html__( 'shop now', 'clothes-boutique' ); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->

<!-- wp:group {"className":"slider-nav","layout":{"type":"flex","flexWrap":"nowrap"}} -->
<div class="wp-block-group slider-nav"><!-- wp:group {"className":"img-bg","style":{"border":{"radius":"5px"},"spacing":{"padding":{"top":"8px","bottom":"8px","left":"8px","right":"8px"}}},"backgroundColor":"base","layout":{"type":"default"}} -->
<div class="wp-block-group img-bg has-base-background-color has-background" style="border-radius:5px;padding-top:8px;padding-right:8px;padding-bottom:8px;padding-left:8px"><!-- wp:image {"id":20,"width":"auto","height":"100px","sizeSlug":"full","linkDestination":"none","align":"center"} -->
<figure class="wp-block-image aligncenter size-full is-resized"><img src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/slide1.png" alt="" class="wp-image-20" style="width:auto;height:100px"/></figure>
<!-- /wp:image --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"img-bg","style":{"border":{"radius":"5px"},"spacing":{"padding":{"top":"8px","bottom":"8px","left":"8px","right":"8px"}}},"backgroundColor":"base","layout":{"type":"default"}} -->
<div class="wp-block-group img-bg has-base-background-color has-background" style="border-radius:5px;padding-top:8px;padding-right:8px;padding-bottom:8px;padding-left:8px"><!-- wp:image {"id":32,"width":"auto","height":"100px","sizeSlug":"full","linkDestination":"none","align":"center"} -->
<figure class="wp-block-image aligncenter size-full is-resized"><img src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/slide2.png" alt="" class="wp-image-32" style="width:auto;height:100px"/></figure>
<!-- /wp:image --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"img-bg","style":{"border":{"radius":"5px"},"spacing":{"padding":{"top":"8px","bottom":"8px","left":"8px","right":"8px"}}},"backgroundColor":"base","layout":{"type":"default"}} -->
<div class="wp-block-group img-bg has-base-background-color has-background" style="border-radius:5px;padding-top:8px;padding-right:8px;padding-bottom:8px;padding-left:8px"><!-- wp:image {"id":33,"width":"auto","height":"100px","sizeSlug":"full","linkDestination":"none","align":"center"} -->
<figure class="wp-block-image aligncenter size-full is-resized"><img src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/slide3.png" alt="" class="wp-image-33" style="width:auto;height:100px"/></figure>
<!-- /wp:image --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"img-bg","style":{"border":{"radius":"5px"},"spacing":{"padding":{"top":"8px","bottom":"8px","left":"8px","right":"8px"}}},"backgroundColor":"base","layout":{"type":"default"}} -->
<div class="wp-block-group img-bg has-base-background-color has-background" style="border-radius:5px;padding-top:8px;padding-right:8px;padding-bottom:8px;padding-left:8px"><!-- wp:image {"id":39,"width":"auto","height":"100px","sizeSlug":"full","linkDestination":"none","align":"center"} -->
<figure class="wp-block-image aligncenter size-full is-resized"><img src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/slide4.png" alt="" class="wp-image-39" style="width:auto;height:100px"/></figure>
<!-- /wp:image --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"bottom","width":"55%","className":"banner-right-box"} -->
<div class="wp-block-column is-vertically-aligned-bottom banner-right-box" style="flex-basis:55%"><!-- wp:group {"className":"slider-for","layout":{"type":"constrained"}} -->
<div class="wp-block-group slider-for"><!-- wp:group {"className":"right-img-box","layout":{"type":"constrained"}} -->
<div class="wp-block-group right-img-box"><!-- wp:image {"id":30,"width":"auto","height":"500px","sizeSlug":"full","linkDestination":"none","align":"center"} -->
<figure class="wp-block-image aligncenter size-full is-resized"><img src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/slide1.png" alt="" class="wp-image-30" style="width:auto;height:500px"/></figure>
<!-- /wp:image --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"right-img-box","layout":{"type":"constrained"}} -->
<div class="wp-block-group right-img-box"><!-- wp:image {"id":34,"width":"auto","height":"500px","sizeSlug":"full","linkDestination":"none","align":"center"} -->
<figure class="wp-block-image aligncenter size-full is-resized"><img src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/slide2.png" alt="" class="wp-image-34" style="width:auto;height:500px"/></figure>
<!-- /wp:image --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"right-img-box","layout":{"type":"constrained"}} -->
<div class="wp-block-group right-img-box"><!-- wp:image {"id":35,"width":"auto","height":"500px","sizeSlug":"full","linkDestination":"none","align":"center"} -->
<figure class="wp-block-image aligncenter size-full is-resized"><img src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/slide3.png" alt="" class="wp-image-35" style="width:auto;height:500px"/></figure>
<!-- /wp:image --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"right-img-box","layout":{"type":"constrained"}} -->
<div class="wp-block-group right-img-box"><!-- wp:image {"id":40,"width":"auto","height":"500px","sizeSlug":"full","linkDestination":"none","align":"center"} -->
<figure class="wp-block-image aligncenter size-full is-resized"><img src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/slide4.png" alt="" class="wp-image-40" style="width:auto;height:500px"/></figure>
<!-- /wp:image --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group --></main>
<!-- /wp:group -->

<!-- wp:spacer {"height":"50px"} -->
<div style="height:50px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->