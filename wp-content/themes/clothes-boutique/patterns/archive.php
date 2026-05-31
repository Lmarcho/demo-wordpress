<?php
 /**
  * Title: Archive
  * Slug: clothes-boutique/archive
  */
?>
<!-- wp:columns -->
<div class="wp-block-columns"><!-- wp:column {"width":"66.66%"} -->
<div class="wp-block-column" style="flex-basis:66.66%"><!-- wp:query {"queryId":37,"query":{"perPage":10,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":true},"layout":{"type":"default"}} -->
<div class="wp-block-query"><!-- wp:post-template {"layout":{"type":"grid","columnCount":null,"minimumColumnWidth":"18rem"}} -->
<!-- wp:group {"className":"post-block","style":{"spacing":{"padding":{"right":"0","left":"0"}},"border":{"radius":"10px"},"shadow":"var:preset|shadow|natural"},"backgroundColor":"base","layout":{"type":"default"}} -->
<div class="wp-block-group post-block has-base-background-color has-background" style="border-radius:10px;padding-right:0;padding-left:0;box-shadow:var(--wp--preset--shadow--natural)"><!-- wp:post-featured-image {"isLink":true,"height":"300px","style":{"border":{"radius":"10px"}}} /-->

<!-- wp:group {"style":{"spacing":{"margin":{"top":"0"},"padding":{"top":"20px","bottom":"20px","left":"20px","right":"20px"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="margin-top:0;padding-top:20px;padding-right:20px;padding-bottom:20px;padding-left:20px"><!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap"}} -->
<div class="wp-block-group"><!-- wp:post-date /-->

<!-- wp:post-author {"showAvatar":false} /--></div>
<!-- /wp:group -->

<!-- wp:post-title {"level":3,"isLink":true,"style":{"typography":{"fontSize":"20px","fontStyle":"normal","fontWeight":"600"},"spacing":{"margin":{"top":"20px"}}}} /-->

<!-- wp:post-excerpt {"moreText":"Read More","excerptLength":10} /--></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
<!-- /wp:post-template -->

<!-- wp:spacer {"height":"40px","style":{"spacing":{"margin":{"top":"0","bottom":"0"}}}} -->
<div style="margin-top:0;margin-bottom:0;height:40px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:query-pagination {"layout":{"type":"flex","justifyContent":"center"}} -->
<!-- wp:query-pagination-previous /-->

<!-- wp:query-pagination-numbers /-->

<!-- wp:query-pagination-next /-->
<!-- /wp:query-pagination -->

<!-- wp:query-no-results -->
<!-- wp:paragraph {"align":"center","placeholder":"Add text or blocks that will display when a query returns no results.","style":{"spacing":{"margin":{"top":"0","bottom":"0"}}}} -->
<p class="has-text-align-center" style="margin-top:0;margin-bottom:0"><?php echo esc_html__( 'No posts found', 'clothes-boutique' ); ?></p>
<!-- /wp:paragraph -->
<!-- /wp:query-no-results --></div>
<!-- /wp:query --></div>
<!-- /wp:column -->

<!-- wp:column {"width":"33.33%","style":{"spacing":{"padding":{"left":"var:preset|spacing|30"}}}} -->
<div class="wp-block-column" style="padding-left:var(--wp--preset--spacing--30);flex-basis:33.33%"><!-- wp:template-part {"slug":"sidebar","area":"uncategorized"} /--></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->