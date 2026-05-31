<?php
/**
 * Title: Blog Section
 * Slug: clothes-boutique/blog-section
 * Categories: clothes-boutique
 * Keywords: blog-section
 * Block Types: core/post-content
 * Post Types: page, wp_template
 */
?>

<!-- wp:group {"metadata":{"name":"Blog Section"},"className":"blog-section","style":{"spacing":{"padding":{"right":"0px","left":"0px","bottom":"50px","top":"50px"},"margin":{"top":"0","bottom":"0"}},"border":{"radius":"15px"}},"gradient":"section-background","layout":{"type":"constrained","contentSize":"80%"}} -->
<div class="wp-block-group blog-section has-section-background-gradient-background has-background" style="border-radius:15px;margin-top:0;margin-bottom:0;padding-top:50px;padding-right:0px;padding-bottom:50px;padding-left:0px"><!-- wp:group {"className":"blog-head-box wow zoomIn","style":{"spacing":{"margin":{"bottom":"50px"}}},"layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between"}} -->
<div class="wp-block-group blog-head-box wow zoomIn" style="margin-bottom:50px"><!-- wp:heading {"level":3,"className":"blog-sec-title","style":{"elements":{"link":{"color":{"text":"var:preset|color|heading-color"}}},"typography":{"fontSize":"26px","textTransform":"uppercase","fontStyle":"normal","fontWeight":"600"}},"textColor":"heading-color"} -->
<h3 class="wp-block-heading blog-sec-title has-heading-color-color has-text-color has-link-color" style="font-size:26px;font-style:normal;font-weight:600;text-transform:uppercase"><?php echo esc_html__( 'latest fashion trends', 'clothes-boutique' ); ?></h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"blog-border","style":{"border":{"top":{"color":"var:preset|color|heading-color","width":"1px"},"right":[],"bottom":[],"left":[]}}} -->
<p class="blog-border" style="border-top-color:var(--wp--preset--color--heading-color);border-top-width:1px"></p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"className":"blog-sec-btn"} -->
<div class="wp-block-buttons blog-sec-btn"><!-- wp:button {"textColor":"secondary","style":{"color":{"background":"#00000000"},"elements":{"link":{"color":{"text":"var:preset|color|secondary"}}},"typography":{"fontSize":"15px","textTransform":"capitalize"},"border":{"radius":"5px","width":"1px"},"spacing":{"padding":{"left":"40px","right":"40px","top":"10px","bottom":"10px"}}},"borderColor":"secondary"} -->
<div class="wp-block-button"><a class="wp-block-button__link has-secondary-color has-text-color has-background has-link-color has-border-color has-secondary-border-color has-custom-font-size wp-element-button" href="#" style="border-width:1px;border-radius:5px;background-color:#00000000;padding-top:10px;padding-right:40px;padding-bottom:10px;padding-left:40px;font-size:15px;text-transform:capitalize"><?php echo esc_html__( 'view all', 'clothes-boutique' ); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group -->

<!-- wp:query {"queryId":11,"query":{"perPage":2,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":false},"metadata":{"categories":["posts"],"patternName":"core/query-medium-posts","name":"Image at left"},"className":"blog-boxes wow zoomIn"} -->
<div class="wp-block-query blog-boxes wow zoomIn"><!-- wp:post-template {"className":"blog-in-boxes","style":{"spacing":{"blockGap":"15px"}},"layout":{"type":"grid","columnCount":2}} -->
<!-- wp:columns {"verticalAlignment":"center","align":"wide","className":"blog-box","style":{"border":{"radius":"5px"},"spacing":{"padding":{"top":"6px","bottom":"6px","left":"6px","right":"6px"}}},"backgroundColor":"primary"} -->
<div class="wp-block-columns alignwide are-vertically-aligned-center blog-box has-primary-background-color has-background" style="border-radius:5px;padding-top:6px;padding-right:6px;padding-bottom:6px;padding-left:6px"><!-- wp:column {"verticalAlignment":"center","width":"40%","className":"blog-left-box"} -->
<div class="wp-block-column is-vertically-aligned-center blog-left-box" style="flex-basis:40%"><!-- wp:group {"className":"blog-img-box","style":{"color":{"background":"#9d9d9d"},"dimensions":{"minHeight":"250px"},"border":{"radius":"5px"}},"layout":{"type":"default"}} -->
<div class="wp-block-group blog-img-box has-background" style="border-radius:5px;background-color:#9d9d9d;min-height:250px"><!-- wp:post-featured-image {"isLink":true,"height":"250px","className":"blog-img","style":{"border":{"radius":"5px"}}} /--></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center","width":"60%","className":"blog-right-box"} -->
<div class="wp-block-column is-vertically-aligned-center blog-right-box" style="flex-basis:60%"><!-- wp:post-title {"level":5,"isLink":true,"className":"blog-title","style":{"typography":{"fontSize":"20px","fontStyle":"normal","fontWeight":"600"}}} /-->

<!-- wp:post-excerpt {"moreText":"Read More","excerptLength":15,"className":"blog-excerpt","style":{"typography":{"fontSize":"14px"}}} /--></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->
<!-- /wp:post-template --></div>
<!-- /wp:query --></div>
<!-- /wp:group -->