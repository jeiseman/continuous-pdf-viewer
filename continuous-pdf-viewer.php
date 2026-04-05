<?php
/**
 * Plugin Name: Continuous PDF Viewer
 * Plugin URI:  https://mafw.org
 * Description: A high-performance PDF viewer based on PDF.js with a shortcode generator and Gutenberg support.
 * Version:     2.1.0
 * Author:      Jonathan A Eiseman
 * License:     GPL2
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * 1. The Core Shortcode Renderer (Restored)
 */
add_shortcode('pdf_viewer', 'cpv_render_shortcode');
function cpv_render_shortcode( $atts ) {
    if(!is_array($atts))$atts=array();
    $atts=array_change_key_case((array)$atts,CASE_LOWER);
    $atts=shortcode_atts(array(
        'url'=>'','title'=>'','subtitle'=>'','brand'=>'',
        'height'=>'720px','tablet_height'=>'600px','mobile_height'=>'480px',
        'width'=>'100%','max_width'=>'100%','border_radius'=>'12',
        'theme'=>'light','accent'=>'#4f7df3','bg_color'=>'','surface_color'=>'','text_color'=>'',
        'font'=>'DM Sans','font_display'=>'Instrument Serif',
        'download_desktop'=>'yes','download_tablet'=>'yes','download_mobile'=>'yes','print_desktop'=>'yes','print_tablet'=>'yes','print_mobile'=>'yes','fullscreen_desktop'=>'yes','fullscreen_tablet'=>'yes','fullscreen_mobile'=>'yes','search_desktop'=>'yes','search_tablet'=>'yes','search_mobile'=>'yes','thumbnails_desktop'=>'yes','thumbnails_tablet'=>'yes','thumbnails_mobile'=>'yes','zoom_desktop'=>'yes','zoom_tablet'=>'yes','zoom_mobile'=>'yes','theme_toggle_desktop'=>'yes','theme_toggle_tablet'=>'yes','theme_toggle_mobile'=>'yes','page_nav_desktop'=>'yes','page_nav_tablet'=>'yes','page_nav_mobile'=>'yes','fit_width_btn_desktop'=>'yes','fit_width_btn_tablet'=>'yes','fit_width_btn_mobile'=>'yes','fit_height_btn_desktop'=>'yes','fit_height_btn_tablet'=>'yes','fit_height_btn_mobile'=>'yes','status_bar_desktop'=>'yes','status_bar_tablet'=>'yes','status_bar_mobile'=>'yes',
        'keyboard'=>'yes',
        'default_zoom'=>'fit_width','min_zoom'=>'0.3','max_zoom'=>'5','zoom_step'=>'0.2',
        'start_page'=>'1','sidebar_open'=>'no','cover_mode'=>'no','cover_image'=>'','cover_height'=>'720px','cover_button'=>'yes','cover_button_text'=>'Open PDF','cover_hint'=>'Click to open the document viewer','cover_button_align'=>'left','cover_button_padding'=>'12px 18px','cover_button_radius'=>'10px','cover_overlay'=>'no','cover_overlay_text'=>'Click to Open','cover_overlay_bg'=>'rgba(0,0,0,0.45)','cover_overlay_color'=>'#ffffff','cover_overlay_padding'=>'18px 24px','cover_overlay_radius'=>'0px','cover_overlay_font_size'=>'18px','cover_overlay_font_weight'=>'700','viewer_shadow'=>'yes',
        'loading_text'=>'Loading document...','error_text'=>'Could not load the document. Please check the file URL.',
    ),$atts,'pdf_viewer');

    if(empty($atts['url'])||trim((string)$atts['url'])==='')return '<p style="color:red;font-weight:600;">&#9888; [pdf_viewer] Error: <code>url</code> attribute is required.</p>';
    $atts['url']=trim(strip_tags((string)$atts['url']));

    static $instance=0;$instance++;$uid='cpv'.$instance;

    $url=esc_url($atts['url']);if(empty($url)&&!empty($atts['url']))$url=esc_attr($atts['url']);
    $title=esc_html($atts['title']);$subtitle=esc_html($atts['subtitle']);$brand=esc_html($atts['brand']);
    $height=esc_attr($atts['height']);$tablet_height=esc_attr($atts['tablet_height']);$mobile_height=esc_attr($atts['mobile_height']);
    $width=esc_attr($atts['width']);$max_width=esc_attr($atts['max_width']);$border_radius=intval($atts['border_radius']);
    $theme=$atts['theme']==='dark'?'dark':'light';
    $accent=sanitize_hex_color($atts['accent'])?:'#4f7df3';
    $font=esc_attr($atts['font']);$font_display=esc_attr($atts['font_display']);

    $fd_download=$atts['download_desktop']==='yes';$ft_download=$atts['download_tablet']==='yes';$fm_download=$atts['download_mobile']==='yes';$f_download=$fd_download||$ft_download||$fm_download;
    $fd_print=$atts['print_desktop']==='yes';$ft_print=$atts['print_tablet']==='yes';$fm_print=$atts['print_mobile']==='yes';$f_print=$fd_print||$ft_print||$fm_print;
    $fd_fullscreen=$atts['fullscreen_desktop']==='yes';$ft_fullscreen=$atts['fullscreen_tablet']==='yes';$fm_fullscreen=$atts['fullscreen_mobile']==='yes';$f_fullscreen=$fd_fullscreen||$ft_fullscreen||$fm_fullscreen;
    $fd_search=$atts['search_desktop']==='yes';$ft_search=$atts['search_tablet']==='yes';$fm_search=$atts['search_mobile']==='yes';$f_search=$fd_search||$ft_search||$fm_search;
    $fd_thumbnails=$atts['thumbnails_desktop']==='yes';$ft_thumbnails=$atts['thumbnails_tablet']==='yes';$fm_thumbnails=$atts['thumbnails_mobile']==='yes';$f_thumbnails=$fd_thumbnails||$ft_thumbnails||$fm_thumbnails;
    $fd_zoom=$atts['zoom_desktop']==='yes';$ft_zoom=$atts['zoom_tablet']==='yes';$fm_zoom=$atts['zoom_mobile']==='yes';$f_zoom=$fd_zoom||$ft_zoom||$fm_zoom;
    $fd_theme_toggle=$atts['theme_toggle_desktop']==='yes';$ft_theme_toggle=$atts['theme_toggle_tablet']==='yes';$fm_theme_toggle=$atts['theme_toggle_mobile']==='yes';$f_theme_toggle=$fd_theme_toggle||$ft_theme_toggle||$fm_theme_toggle;
    $fd_page_nav=$atts['page_nav_desktop']==='yes';$ft_page_nav=$atts['page_nav_tablet']==='yes';$fm_page_nav=$atts['page_nav_mobile']==='yes';$f_page_nav=$fd_page_nav||$ft_page_nav||$fm_page_nav;
    $fd_fit_width_btn=$atts['fit_width_btn_desktop']==='yes';$ft_fit_width_btn=$atts['fit_width_btn_tablet']==='yes';$fm_fit_width_btn=$atts['fit_width_btn_mobile']==='yes';$f_fit_width_btn=$fd_fit_width_btn||$ft_fit_width_btn||$fm_fit_width_btn;
    $fd_fit_height_btn=$atts['fit_height_btn_desktop']==='yes';$ft_fit_height_btn=$atts['fit_height_btn_tablet']==='yes';$fm_fit_height_btn=$atts['fit_height_btn_mobile']==='yes';$f_fit_height_btn=$fd_fit_height_btn||$ft_fit_height_btn||$fm_fit_height_btn;
    $fd_status_bar=$atts['status_bar_desktop']==='yes';$ft_status_bar=$atts['status_bar_tablet']==='yes';$fm_status_bar=$atts['status_bar_mobile']==='yes';$f_status_bar=$fd_status_bar||$ft_status_bar||$fm_status_bar;
    $f_keyboard=$atts['keyboard']==='yes';

    /* 3-device visibility helper: returns CSS classes */
    if(!function_exists('cpv_vis3')){
        function cpv_vis3($d,$t,$m){$c='';if(!$d)$c.=' cpv-hd';if(!$t)$c.=' cpv-ht';if(!$m)$c.=' cpv-hm';return $c;}
    }
    $v_download=cpv_vis3($fd_download,$ft_download,$fm_download);
    $v_print=cpv_vis3($fd_print,$ft_print,$fm_print);
    $v_fullscreen=cpv_vis3($fd_fullscreen,$ft_fullscreen,$fm_fullscreen);
    $v_search=cpv_vis3($fd_search,$ft_search,$fm_search);
    $v_thumbnails=cpv_vis3($fd_thumbnails,$ft_thumbnails,$fm_thumbnails);
    $v_zoom=cpv_vis3($fd_zoom,$ft_zoom,$fm_zoom);
    $v_theme_toggle=cpv_vis3($fd_theme_toggle,$ft_theme_toggle,$fm_theme_toggle);
    $v_page_nav=cpv_vis3($fd_page_nav,$ft_page_nav,$fm_page_nav);
    $v_fit_width_btn=cpv_vis3($fd_fit_width_btn,$ft_fit_width_btn,$fm_fit_width_btn);
    $v_fit_height_btn=cpv_vis3($fd_fit_height_btn,$ft_fit_height_btn,$fm_fit_height_btn);
    $v_status_bar=cpv_vis3($fd_status_bar,$ft_status_bar,$fm_status_bar);

    $default_zoom=esc_attr($atts['default_zoom']);$min_zoom=floatval($atts['min_zoom']);
    $max_zoom=floatval($atts['max_zoom']);$zoom_step=floatval($atts['zoom_step']);
    $start_page=max(1,intval($atts['start_page']));$sidebar_open=$atts['sidebar_open']==='yes';$cover_mode=$atts['cover_mode']==='yes';
    $cover_image=esc_url($atts['cover_image']);if(empty($cover_image)&&!empty($atts['cover_image']))$cover_image=esc_attr($atts['cover_image']);
    $cover_height=esc_attr($atts['cover_height']);
    $cover_button=($atts['cover_button']==='no')?'no':'yes';
    $cover_button_text=esc_html($atts['cover_button_text']);$cover_hint=esc_html($atts['cover_hint']);
    $cover_button_align=in_array($atts['cover_button_align'],array('left','center','right','stretch'),true)?$atts['cover_button_align']:'left';
    $cover_button_padding=esc_attr($atts['cover_button_padding']);
    $cover_button_radius=esc_attr($atts['cover_button_radius']);
    $cover_overlay=($atts['cover_overlay']==='yes')?'yes':'no';
    $cover_overlay_text=esc_html($atts['cover_overlay_text']);
    $cover_overlay_bg=esc_attr($atts['cover_overlay_bg']);
    $cover_overlay_color=esc_attr($atts['cover_overlay_color']);
    $cover_overlay_padding=esc_attr($atts['cover_overlay_padding']);
    $cover_overlay_radius=esc_attr($atts['cover_overlay_radius']);
    $cover_overlay_font_size=esc_attr($atts['cover_overlay_font_size']);
    $cover_overlay_font_weight=esc_attr($atts['cover_overlay_font_weight']);
    $viewer_shadow=$atts['viewer_shadow']==='no'?'none':'0 4px 60px rgba(0,0,0,0.35),0 0 0 1px rgba(255,255,255,0.03) inset';
    $cover_button_justify='flex-start';$cover_button_width='auto';if($cover_button_align==='center')$cover_button_justify='center';elseif($cover_button_align==='right')$cover_button_justify='flex-end';elseif($cover_button_align==='stretch'){$cover_button_justify='flex-start';$cover_button_width='100%';}
    $loading_text=esc_html($atts['loading_text']);$error_text=esc_html($atts['error_text']);
    $bg_color=sanitize_hex_color($atts['bg_color']);$surface_color=sanitize_hex_color($atts['surface_color']);$text_color=sanitize_hex_color($atts['text_color']);
    $ar=hexdec(substr($accent,1,2));$ag=hexdec(substr($accent,3,2));$ab=hexdec(substr($accent,5,2));
    $accent_glow="rgba({$ar},{$ag},{$ab},0.25)";
    $gf_body=str_replace(' ','+',$font);$gf_display=str_replace(' ','+',$font_display);

    /* Sidebar device class */
    $sb_vis='';if(!$fd_thumbnails)$sb_vis.=' cpv-hd-sb';if(!$ft_thumbnails)$sb_vis.=' cpv-ht-sb';if(!$fm_thumbnails)$sb_vis.=' cpv-hm-sb';

    ob_start();
    ?>
    <div id="<?php echo $uid;?>"
         class="pv-root <?php echo $theme==='light'?'pv-light':'pv-dark';?> <?php echo $cover_mode?'pv-has-cover':'';?>"
         data-url="<?php echo $url; ?>"
         data-title="<?php echo esc_attr($title); ?>"
         data-default-zoom="<?php echo esc_attr($default_zoom); ?>"
         data-min-zoom="<?php echo esc_attr($min_zoom); ?>"
         data-max-zoom="<?php echo esc_attr($max_zoom); ?>"
         data-zoom-step="<?php echo esc_attr($zoom_step); ?>"
         data-start-page="<?php echo esc_attr($start_page); ?>"
         data-loading-text="<?php echo esc_attr($loading_text); ?>"
         data-error-text="<?php echo esc_attr($error_text); ?>">
      <?php if($title||$subtitle||$brand):?><div class="pv-header"><?php if($brand):?><div class="pv-header-brand"><?php echo $brand;?></div><?php endif;?><?php if($title):?><div class="pv-header-title"><?php echo $title;?></div><?php endif;?><?php if($subtitle):?><div class="pv-header-sub"><?php echo $subtitle;?></div><?php endif;?></div><?php endif;?>
      <?php if($cover_mode):?><div class="pv-cover" data-el="cover"><div class="pv-cover-inner"><div class="pv-cover-art" data-action="openViewer" tabindex="0" role="button" aria-label="<?php echo esc_attr($cover_hint ? $cover_hint : 'Open document');?>"><?php if($cover_image):?><img class="pv-cover-image" data-el="coverImage" src="<?php echo $cover_image;?>" alt="<?php echo esc_attr($title?$title:'PDF cover');?>"><?php else:?><canvas class="pv-cover-canvas" data-el="coverCanvas"></canvas><?php endif;?><?php if($cover_overlay==='yes'):?><div class="pv-cover-overlay"><?php echo $cover_overlay_text ? $cover_overlay_text : 'Click to Open';?></div><?php endif;?></div><div class="pv-cover-copy"><div class="pv-cover-eyebrow"><?php echo $brand?$brand:'PDF Viewer';?></div><div class="pv-cover-title"><?php echo $title?$title:'Open document';?></div><?php if($subtitle||$cover_hint):?><div class="pv-cover-text"><?php echo $subtitle?$subtitle.' ':'';?><?php echo $cover_hint;?></div><?php endif;?><?php if($cover_button!=='no'):?><div class="pv-cover-btn-wrap"><button type="button" class="pv-cover-btn" data-action="openViewer"><?php echo $cover_button_text;?></button></div><?php endif;?><div class="pv-cover-meta" data-el="coverMeta"></div></div></div></div><?php endif;?>
      <div class="pv-toolbar">
        <?php if($f_thumbnails):?><button class="pv-btn<?php echo $v_thumbnails;?> <?php echo $sidebar_open?'active':'';?>" data-action="sidebar" title="Toggle Thumbnails"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg></button><div class="pv-sep<?php echo $v_thumbnails;?>"></div><?php endif;?>
        <?php if($f_page_nav):?><div class="pv-page-nav<?php echo $v_page_nav;?>"><button class="pv-btn" data-action="prev"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><polyline points="15 18 9 12 15 6"/></svg></button><input type="text" class="pv-page-input" data-el="pageInput" value="1"><span class="pv-page-total" data-el="pageTotal">/ -</span><button class="pv-btn" data-action="next"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><polyline points="9 6 15 12 9 18"/></svg></button></div><?php endif;?>
        <?php if($f_zoom):?><div class="pv-sep<?php echo $v_zoom;?>"></div><button class="pv-btn<?php echo $v_zoom;?>" data-action="zoomOut"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="8" y1="11" x2="14" y2="11"/></svg></button><span class="pv-zoom-display<?php echo $v_zoom;?>" data-el="zoomDisp">100%</span><button class="pv-btn<?php echo $v_zoom;?>" data-action="zoomIn"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="11" y1="8" x2="11" y2="14"/><line x1="8" y1="11" x2="14" y2="11"/></svg></button><?php endif;?>
        <?php if($f_fit_width_btn):?><button class="pv-btn<?php echo $v_fit_width_btn;?>" data-action="fitWidth" title="Fit to width"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><line x1="3" y1="12" x2="21" y2="12"/><polyline points="7 8 3 12 7 16"/><polyline points="17 8 21 12 17 16"/><line x1="3" y1="4" x2="3" y2="20"/><line x1="21" y1="4" x2="21" y2="20"/></svg></button><?php endif;?>
        <?php if($f_fit_height_btn):?><button class="pv-btn<?php echo $v_fit_height_btn;?>" data-action="fitHeight" title="Fit to height"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><line x1="12" y1="3" x2="12" y2="21"/><polyline points="8 7 12 3 16 7"/><polyline points="8 17 12 21 16 17"/><line x1="4" y1="3" x2="20" y2="3"/><line x1="4" y1="21" x2="20" y2="21"/></svg></button><?php endif;?>
        <div class="pv-spacer"></div>
        <?php if($f_search):?><button class="pv-btn<?php echo $v_search;?>" data-action="search" title="Search Document"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></button><?php endif;?>
        <?php if($f_theme_toggle):?><button class="pv-btn<?php echo $v_theme_toggle;?>" data-action="themeToggle" title="Toggle Dark Mode"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg></button><?php endif;?>
        <?php if($f_print):?><button class="pv-btn<?php echo $v_print;?>" data-action="print" title="Print Document"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg></button><?php endif;?>
        <?php if($f_download):?><button class="pv-btn<?php echo $v_download;?>" data-action="download" Title="Download PDF"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg></button><?php endif;?>
        <?php if($f_fullscreen):?><button class="pv-btn<?php echo $v_fullscreen;?>" data-action="fullscreen" title="Toggle Full Screen"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><polyline points="15 3 21 3 21 9"/><polyline points="9 21 3 21 3 15"/><polyline points="21 3 14 10"/><polyline points="3 21 10 14"/></svg></button><?php endif;?>
      </div>
      <?php if($f_search):?><div class="pv-search-bar<?php echo $v_search;?>" data-el="searchBar"><input type="text" class="pv-search-input" data-el="searchInput" placeholder="Search in document..."><span class="pv-search-info" data-el="searchInfo"></span><div class="pv-search-nav"><button class="pv-btn" data-action="searchPrev" title="Previous"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><polyline points="18 15 12 9 6 15"/></svg></button><button class="pv-btn" data-action="searchNext" title="Next"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><polyline points="6 9 12 15 18 9"/></svg></button></div><button class="pv-btn" data-action="searchClose" style="width:26px;height:26px"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width:13px;height:13px"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button></div><?php endif;?>
      <div class="pv-body">
        <?php if($f_thumbnails):?><div class="pv-sidebar<?php echo $sb_vis;?> <?php echo $sidebar_open?'open':'';?>" data-el="sidebar"><div class="pv-sidebar-inner" data-el="thumbs"></div></div><?php else:?><div class="pv-sidebar" data-el="sidebar" style="display:none"><div class="pv-sidebar-inner" data-el="thumbs"></div></div><?php endif;?>
        <div class="pv-canvas-wrap" data-el="canvasWrap"><canvas data-el="canvas"></canvas></div>
        <div class="pv-error" data-el="error"><div class="pv-error-icon"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg></div><div class="pv-error-title">Unable to load document</div><div class="pv-error-msg" data-el="errorMsg"></div><button class="pv-retry-btn" data-action="retry">Retry</button></div>
        <div class="pv-loader" data-el="loader"><div class="pv-spinner"></div><div class="pv-loader-text" data-el="loaderText"><?php echo $loading_text;?></div></div>
      </div>
      <?php if($f_status_bar):?><div class="pv-status<?php echo $v_status_bar;?>"><div class="pv-status-dot"></div><span data-el="statusInfo">Ready</span><span style="margin-left:auto" data-el="statusPages"></span></div><?php endif;?>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * 2. Block Registration Hook (Restored)
 * This binds the backend shortcode to the Gutenberg block editor.
 */
add_action( 'init', 'cpv_register_block' );
function cpv_register_block() {
    register_block_type( __DIR__ . '/block', array(
        'render_callback' => 'cpv_render_shortcode'
    ));
}

/**
 * 3. Frontend Assets Enqueue
 */
add_action( 'wp_enqueue_scripts', 'cpv_frontend_assets' );
function cpv_frontend_assets() {
    wp_enqueue_style( 'cpv-core-css', plugins_url( 'pdf-viewer-core.css', __FILE__ ), array(), '2.1.0' );
    wp_register_script( 'pdfjs-lib', plugins_url( 'lib/pdfjs/pdf.min.js', __FILE__ ), array(), '3.11.174', true );
    wp_enqueue_script( 'cpv-core-js', plugins_url( 'pdf-viewer-core.js', __FILE__ ), array( 'pdfjs-lib' ), '2.1.0', true );
    wp_localize_script( 'cpv-core-js', 'cpvSettings', array(
        'workerUrl' => plugins_url( 'lib/pdfjs/pdf.worker.min.js', __FILE__ )
    ));
}

/**
 * 4. Admin Menu Registration
 */
add_action( 'admin_menu', 'cpv_admin_menu' );
function cpv_admin_menu() {
    add_management_page(
        'Continuous PDF Viewer Generator',
        'Continuous PDF Viewer',
        'edit_posts',
        'cpv-generator',
        'cpv_admin_page'
    );
}

/**
 * 5. Admin Settings Enqueue
 */
add_action( 'admin_enqueue_scripts', 'cpv_admin_assets' );
function cpv_admin_assets( $hook ) {
    if ( $hook !== 'tools_page_cpv-generator' ) return;
    wp_enqueue_style( 'cpv-admin-css', plugins_url( 'pdf-viewer-admin.css', __FILE__ ), array(), '2.1.0' );
    wp_enqueue_style( 'wp-color-picker' );
    wp_enqueue_script( 'wp-color-picker' );
    wp_enqueue_media();
    wp_enqueue_script( 'cpv-admin-js', plugins_url( 'pdf-viewer-admin.js', __FILE__ ), array( 'jquery', 'wp-color-picker' ), '2.1.0', true );
}

/**
 * 6. Gutenberg Editor Enqueue
 */
add_action( 'enqueue_block_editor_assets', 'cpv_enqueue_block_editor_assets' );
function cpv_enqueue_block_editor_assets() {
    wp_enqueue_script(
        'cpv-block-editor-js',
        plugins_url( 'block/index.js', __FILE__ ),
        array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components' ),
        filemtime( plugin_dir_path( __FILE__ ) . 'block/index.js' ),
        true
    );
}

/**
 * 7. Admin Settings Page HTML
 */
function cpv_admin_page() {
if ( ! current_user_can( 'edit_posts' ) ) return;
?>
<div id="cpv-wrap">
<div class="cpv-hero"><span class="cpv-hero-badge">Shortcode Generator</span><h1>Continuous PDF Viewer</h1><p>Configure your viewer below - shortcode updates live on the right. Copy and paste it anywhere.</p></div>
<div class="cpv-grid">
<div class="cpv-config-col">

<div class="cpv-card"><div class="cpv-card-head"><div class="cpv-card-icon" style="background:#eef2ff;color:#4f7df3"></div><h2>Document</h2></div>
<div class="cpv-card-body">
  <div class="cpv-field"><label>PDF URL <span class="cpv-hint">(required)</span></label><div class="cpv-url-wrap"><input type="text" id="cpv_url" data-attr="url" placeholder="/wp-content/uploads/2025/whitepaper.pdf"><button type="button" class="cpv-media-btn" id="cpv-media-btn"> Media</button></div></div>
  <div class="cpv-field"><label>Cover Image URL <span class="cpv-hint">(optional)</span></label><div class="cpv-url-wrap"><input type="text" id="cpv_cover_image" data-attr="cover_image" placeholder="/wp-content/uploads/2025/pdf-cover.jpg"><button type="button" class="cpv-media-btn" id="cpv-cover-media-btn"> Image</button></div></div>
  <div class="cpv-field"><label>Title</label><input type="text" data-attr="title" placeholder="2025 Industry Whitepaper"></div>
  <div class="cpv-field"><label>Subtitle</label><input type="text" data-attr="subtitle" placeholder="Explore key findings..."></div>
  <div class="cpv-field"><label>Brand Label</label><input type="text" data-attr="brand" placeholder="Acme Corp"></div>
</div></div>

<div class="cpv-card"><div class="cpv-card-head"><div class="cpv-card-icon" style="background:#fef3e2;color:#e8860c"></div><h2>Dimensions</h2></div>
<div class="cpv-card-body">
  <div class="cpv-row-3">
    <div class="cpv-field"><label>Height Desktop</label><input type="text" data-attr="height" data-default="720px" value="720px"></div>
    <div class="cpv-field"><label>Height Tablet</label><input type="text" data-attr="tablet_height" data-default="600px" value="600px"></div>
    <div class="cpv-field"><label>Height Mobile</label><input type="text" data-attr="mobile_height" data-default="480px" value="480px"></div>
  </div>
  <div class="cpv-row-3">
    <div class="cpv-field"><label>Width</label><input type="text" data-attr="width" data-default="100%" value="100%"></div>
    <div class="cpv-field"><label>Max Width</label><input type="text" data-attr="max_width" data-default="100%" value="100%"></div>
    <div class="cpv-field"><label>Border Radius <span class="cpv-hint">(px)</span></label><input type="number" data-attr="border_radius" data-default="12" value="12" min="0" max="50"></div>
  </div>
</div></div>

<div class="cpv-card"><div class="cpv-card-head"><div class="cpv-card-icon" style="background:#f0e6ff;color:#8b5cf6"></div><h2>Theme &amp; Colours</h2></div>
<div class="cpv-card-body">
  <div class="cpv-row">
    <div class="cpv-field"><label>Theme</label><select data-attr="theme" data-default="light"><option value="light" selected>Light</option><option value="dark">Dark</option></select></div>
    <div class="cpv-field"><label>Accent Colour</label><input type="text" class="cpv-color" data-attr="accent" data-default="#4f7df3" value="#4f7df3"></div>
  </div>
  <div class="cpv-row">
    <div class="cpv-field"><label>Background <span class="cpv-hint">(optional)</span></label><input type="text" class="cpv-color" data-attr="bg_color" value=""></div>
    <div class="cpv-field"><label>Surface Colour</label><input type="text" class="cpv-color" data-attr="surface_color" value=""></div>
  </div>
  <div class="cpv-row">
    <div class="cpv-field"><label>Text Colour <span class="cpv-hint">(optional)</span></label><input type="text" class="cpv-color" data-attr="text_color" value=""></div><div class="cpv-field"></div>
  </div>
  <div class="cpv-row">
    <div class="cpv-field"><label>Body Font</label><input type="text" data-attr="font" data-default="DM Sans" value="DM Sans"></div>
    <div class="cpv-field"><label>Display Font</label><input type="text" data-attr="font_display" data-default="Instrument Serif" value="Instrument Serif"></div>
  </div>
</div></div>

<div class="cpv-card"><div class="cpv-card-head"><div class="cpv-card-icon" style="background:#e8faf0;color:#059669"></div><h2>Feature Visibility</h2></div>
<div class="cpv-card-body">
    <div class="cpv-drow" data-feature="download">
      <div class="cpv-drow-info"><div class="cpv-drow-label">Download Button</div><div class="cpv-drow-desc">Allow users to download the PDF</div></div>
      <div class="cpv-drow-pills">
        <div class="cpv-pill active" data-attr="download_desktop" data-default="yes" title="Desktop (>1024px)"><span class="cpv-pill-icon"></span><span class="cpv-pill-text">Desktop</span><span class="cpv-pill-dot"></span></div>
        <div class="cpv-pill active" data-attr="download_tablet" data-default="yes" title="Tablet (641-1024px)"><span class="cpv-pill-icon"></span><span class="cpv-pill-text">Tablet</span><span class="cpv-pill-dot"></span></div>
        <div class="cpv-pill active" data-attr="download_mobile" data-default="yes" title="Mobile (<640px)"><span class="cpv-pill-icon"></span><span class="cpv-pill-text">Mobile</span><span class="cpv-pill-dot"></span></div>
      </div>
    </div>
    <div class="cpv-drow" data-feature="print">
      <div class="cpv-drow-info"><div class="cpv-drow-label">Print Button</div><div class="cpv-drow-desc">Allow users to print the document</div></div>
      <div class="cpv-drow-pills">
        <div class="cpv-pill active" data-attr="print_desktop" data-default="yes" title="Desktop (>1024px)"><span class="cpv-pill-icon"></span><span class="cpv-pill-text">Desktop</span><span class="cpv-pill-dot"></span></div>
        <div class="cpv-pill active" data-attr="print_tablet" data-default="yes" title="Tablet (641-1024px)"><span class="cpv-pill-icon"></span><span class="cpv-pill-text">Tablet</span><span class="cpv-pill-dot"></span></div>
        <div class="cpv-pill active" data-attr="print_mobile" data-default="yes" title="Mobile (<640px)"><span class="cpv-pill-icon"></span><span class="cpv-pill-text">Mobile</span><span class="cpv-pill-dot"></span></div>
      </div>
    </div>
    <div class="cpv-drow" data-feature="fullscreen">
      <div class="cpv-drow-info"><div class="cpv-drow-label">Fullscreen</div><div class="cpv-drow-desc">Expand viewer to fill entire screen</div></div>
      <div class="cpv-drow-pills">
        <div class="cpv-pill active" data-attr="fullscreen_desktop" data-default="yes" title="Desktop (>1024px)"><span class="cpv-pill-icon"></span><span class="cpv-pill-text">Desktop</span><span class="cpv-pill-dot"></span></div>
        <div class="cpv-pill active" data-attr="fullscreen_tablet" data-default="yes" title="Tablet (641-1024px)"><span class="cpv-pill-icon"></span><span class="cpv-pill-text">Tablet</span><span class="cpv-pill-dot"></span></div>
        <div class="cpv-pill active" data-attr="fullscreen_mobile" data-default="yes" title="Mobile (<640px)"><span class="cpv-pill-icon"></span><span class="cpv-pill-text">Mobile</span><span class="cpv-pill-dot"></span></div>
      </div>
    </div>
    <div class="cpv-drow" data-feature="search">
      <div class="cpv-drow-info"><div class="cpv-drow-label">Search</div><div class="cpv-drow-desc">Text search within the document</div></div>
      <div class="cpv-drow-pills">
        <div class="cpv-pill active" data-attr="search_desktop" data-default="yes" title="Desktop (>1024px)"><span class="cpv-pill-icon"></span><span class="cpv-pill-text">Desktop</span><span class="cpv-pill-dot"></span></div>
        <div class="cpv-pill active" data-attr="search_tablet" data-default="yes" title="Tablet (641-1024px)"><span class="cpv-pill-icon"></span><span class="cpv-pill-text">Tablet</span><span class="cpv-pill-dot"></span></div>
        <div class="cpv-pill active" data-attr="search_mobile" data-default="yes" title="Mobile (<640px)"><span class="cpv-pill-icon"></span><span class="cpv-pill-text">Mobile</span><span class="cpv-pill-dot"></span></div>
      </div>
    </div>
    <div class="cpv-drow" data-feature="thumbnails">
      <div class="cpv-drow-info"><div class="cpv-drow-label">Thumbnails Sidebar</div><div class="cpv-drow-desc">Page thumbnail navigation panel</div></div>
      <div class="cpv-drow-pills">
        <div class="cpv-pill active" data-attr="thumbnails_desktop" data-default="yes" title="Desktop (>1024px)"><span class="cpv-pill-icon"></span><span class="cpv-pill-text">Desktop</span><span class="cpv-pill-dot"></span></div>
        <div class="cpv-pill active" data-attr="thumbnails_tablet" data-default="yes" title="Tablet (641-1024px)"><span class="cpv-pill-icon"></span><span class="cpv-pill-text">Tablet</span><span class="cpv-pill-dot"></span></div>
        <div class="cpv-pill active" data-attr="thumbnails_mobile" data-default="yes" title="Mobile (<640px)"><span class="cpv-pill-icon"></span><span class="cpv-pill-text">Mobile</span><span class="cpv-pill-dot"></span></div>
      </div>
    </div>
    <div class="cpv-drow" data-feature="zoom">
      <div class="cpv-drow-info"><div class="cpv-drow-label">Zoom Controls</div><div class="cpv-drow-desc">Zoom in, out, and percentage display</div></div>
      <div class="cpv-drow-pills">
        <div class="cpv-pill active" data-attr="zoom_desktop" data-default="yes" title="Desktop (>1024px)"><span class="cpv-pill-icon"></span><span class="cpv-pill-text">Desktop</span><span class="cpv-pill-dot"></span></div>
        <div class="cpv-pill active" data-attr="zoom_tablet" data-default="yes" title="Tablet (641-1024px)"><span class="cpv-pill-icon"></span><span class="cpv-pill-text">Tablet</span><span class="cpv-pill-dot"></span></div>
        <div class="cpv-pill active" data-attr="zoom_mobile" data-default="yes" title="Mobile (<640px)"><span class="cpv-pill-icon"></span><span class="cpv-pill-text">Mobile</span><span class="cpv-pill-dot"></span></div>
      </div>
    </div>
    <div class="cpv-drow" data-feature="theme_toggle">
      <div class="cpv-drow-info"><div class="cpv-drow-label">Theme Toggle</div><div class="cpv-drow-desc">Let users switch dark &amp; light</div></div>
      <div class="cpv-drow-pills">
        <div class="cpv-pill active" data-attr="theme_toggle_desktop" data-default="yes" title="Desktop (>1024px)"><span class="cpv-pill-icon"></span><span class="cpv-pill-text">Desktop</span><span class="cpv-pill-dot"></span></div>
        <div class="cpv-pill active" data-attr="theme_toggle_tablet" data-default="yes" title="Tablet (641-1024px)"><span class="cpv-pill-icon"></span><span class="cpv-pill-text">Tablet</span><span class="cpv-pill-dot"></span></div>
        <div class="cpv-pill active" data-attr="theme_toggle_mobile" data-default="yes" title="Mobile (<640px)"><span class="cpv-pill-icon"></span><span class="cpv-pill-text">Mobile</span><span class="cpv-pill-dot"></span></div>
      </div>
    </div>
    <div class="cpv-drow" data-feature="page_nav">
      <div class="cpv-drow-info"><div class="cpv-drow-label">Page Navigation</div><div class="cpv-drow-desc">Prev / next buttons and page input</div></div>
      <div class="cpv-drow-pills">
        <div class="cpv-pill active" data-attr="page_nav_desktop" data-default="yes" title="Desktop (>1024px)"><span class="cpv-pill-icon"></span><span class="cpv-pill-text">Desktop</span><span class="cpv-pill-dot"></span></div>
        <div class="cpv-pill active" data-attr="page_nav_tablet" data-default="yes" title="Tablet (641-1024px)"><span class="cpv-pill-icon"></span><span class="cpv-pill-text">Tablet</span><span class="cpv-pill-dot"></span></div>
        <div class="cpv-pill active" data-attr="page_nav_mobile" data-default="yes" title="Mobile (<640px)"><span class="cpv-pill-icon"></span><span class="cpv-pill-text">Mobile</span><span class="cpv-pill-dot"></span></div>
      </div>
    </div>
    <div class="cpv-drow" data-feature="fit_width_btn">
      <div class="cpv-drow-info"><div class="cpv-drow-label">Fit-to-Width Button</div><div class="cpv-drow-desc">One-click auto-fit zoom</div></div>
      <div class="cpv-drow-pills">
        <div class="cpv-pill active" data-attr="fit_width_btn_desktop" data-default="yes" title="Desktop (>1024px)"><span class="cpv-pill-icon"></span><span class="cpv-pill-text">Desktop</span><span class="cpv-pill-dot"></span></div>
        <div class="cpv-pill active" data-attr="fit_width_btn_tablet" data-default="yes" title="Tablet (641-1024px)"><span class="cpv-pill-icon"></span><span class="cpv-pill-text">Tablet</span><span class="cpv-pill-dot"></span></div>
        <div class="cpv-pill active" data-attr="fit_width_btn_mobile" data-default="yes" title="Mobile (<640px)"><span class="cpv-pill-icon"></span><span class="cpv-pill-text">Mobile</span><span class="cpv-pill-dot"></span></div>
      </div>
    </div>
    <div class="cpv-drow" data-feature="fit_height_btn">
      <div class="cpv-drow-info"><div class="cpv-drow-label">Fit-to-Height Button</div><div class="cpv-drow-desc">One-click auto-fit height</div></div>
      <div class="cpv-drow-pills">
        <div class="cpv-pill active" data-attr="fit_height_btn_desktop" data-default="yes" title="Desktop (>1024px)"><span class="cpv-pill-icon"></span><span class="cpv-pill-text">Desktop</span><span class="cpv-pill-dot"></span></div>
        <div class="cpv-pill active" data-attr="fit_height_btn_tablet" data-default="yes" title="Tablet (641-1024px)"><span class="cpv-pill-icon"></span><span class="cpv-pill-text">Tablet</span><span class="cpv-pill-dot"></span></div>
        <div class="cpv-pill active" data-attr="fit_height_btn_mobile" data-default="yes" title="Mobile (<640px)"><span class="cpv-pill-icon"></span><span class="cpv-pill-text">Mobile</span><span class="cpv-pill-dot"></span></div>
      </div>
    </div>
    <div class="cpv-drow" data-feature="status_bar">
      <div class="cpv-drow-info"><div class="cpv-drow-label">Status Bar</div><div class="cpv-drow-desc">Bottom bar showing file info</div></div>
      <div class="cpv-drow-pills">
        <div class="cpv-pill active" data-attr="status_bar_desktop" data-default="yes" title="Desktop (>1024px)"><span class="cpv-pill-icon"></span><span class="cpv-pill-text">Desktop</span><span class="cpv-pill-dot"></span></div>
        <div class="cpv-pill active" data-attr="status_bar_tablet" data-default="yes" title="Tablet (641-1024px)"><span class="cpv-pill-icon"></span><span class="cpv-pill-text">Tablet</span><span class="cpv-pill-dot"></span></div>
        <div class="cpv-pill active" data-attr="status_bar_mobile" data-default="yes" title="Mobile (<640px)"><span class="cpv-pill-icon"></span><span class="cpv-pill-text">Mobile</span><span class="cpv-pill-dot"></span></div>
      </div>
    </div>
    <div class="cpv-toggle-row"><div><div class="cpv-toggle-label">Keyboard Shortcuts</div><div class="cpv-toggle-desc">Arrow keys, +/-, Ctrl+F, Esc</div></div><label class="cpv-switch"><input type="checkbox" data-attr="keyboard" data-default="yes" checked><span class="slider"></span></label></div>
</div></div>

<div class="cpv-card"><div class="cpv-card-head"><div class="cpv-card-icon" style="background:#fef2f2;color:#dc2626"></div><h2>Zoom Settings</h2></div>
<div class="cpv-card-body">
  <div class="cpv-field"><label>Default Zoom <span class="cpv-hint">(fit_width, fit_height, fit, or number)</span></label><input type="text" data-attr="default_zoom" data-default="fit_width" value="fit_width"></div>
  <div class="cpv-row-3">
    <div class="cpv-field"><label>Min Zoom</label><input type="number" data-attr="min_zoom" data-default="0.3" value="0.3" step="0.1" min="0.1"></div>
    <div class="cpv-field"><label>Max Zoom</label><input type="number" data-attr="max_zoom" data-default="5" value="5" step="0.5" min="1"></div>
    <div class="cpv-field"><label>Zoom Step</label><input type="number" data-attr="zoom_step" data-default="0.2" value="0.2" step="0.05" min="0.05"></div>
  </div>
</div></div>

<div class="cpv-card"><div class="cpv-card-head"><div class="cpv-card-icon" style="background:#f0f8ff;color:#2563eb"></div><h2>Behaviour</h2></div>
<div class="cpv-card-body">
  <div class="cpv-row">
    <div class="cpv-field"><label>Start Page</label><input type="number" data-attr="start_page" data-default="1" value="1" min="1"></div>
    <div class="cpv-field"><label>Open Sidebar on Load</label><select data-attr="sidebar_open" data-default="no"><option value="no">No</option><option value="yes">Yes</option></select></div>
  </div>
  <div class="cpv-row">
    <div class="cpv-field"><label>Show Cover First</label><select data-attr="cover_mode" data-default="no"><option value="no">No</option><option value="yes">Yes</option></select></div>
    <div class="cpv-field"><label>Show Cover Button</label><select data-attr="cover_button" data-default="yes"><option value="yes">Yes</option><option value="no">No</option></select></div>
  </div>
</div></div>
</div></div>
</div>
<?php
}
?>
