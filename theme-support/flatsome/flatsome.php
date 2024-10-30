<?php
defined( 'ABSPATH' ) || exit;

add_action( 'wp_head', 'xlwcfg_theme_eva_head_css', 95 );

function xlwcfg_theme_eva_head_css() {
	ob_start();
	?>
    <style>
        .products.row .product .xlwcfg_text_wrap[data-type='grid']{display:inline-block}
        .xlwcfg_text_wrap[data-type='grid']{margin-bottom:5px}
    </style>
	<?php
	echo ob_get_clean();
}

