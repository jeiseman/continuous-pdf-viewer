<?php
/**
 * Plugin Name: Continuous PDF Viewer
 * Plugin URI:  https://mafw.org
 * Description: A high-performance PDF viewer based on PDF.js with a shortcode generator and Gutenberg support.
 * Version:     2.1.5
 * Author:      Jonathan A Eiseman
 * License:     GPL2
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * 1. The Core Shortcode Renderer (Restored)
 */
add_shortcode('continuous_pdf_viewer', 'cpdfv_render_shortcode');
function cpdfv_render_shortcode( $atts ) {
    $a = shortcode_atts( array(
        'url'                => '',
        'height'             => '80vh',
        'tablet_height'      => '70vh',
        'mobile_height'      => '60vh',
        'cover_mode'         => false,
        'cover_image'        => '',
        'cover_overlay'      => 'yes',
        'cover_overlay_text' => 'Click to Open',
        'cover_button'       => 'yes',
        'cover_button_text'  => 'Open PDF',
        'cover_hint'         => 'Click to view document',
        'title'              => '',
        'subtitle'           => '',
        'brand'              => '',
        'loading_text'       => 'Loading Document...',
        'error_text'         => 'Unable to load PDF. Please try again.',
        'theme'              => 'light',
        'start_page'         => 1,
        'default_zoom'       => 'auto',
        'min_zoom'           => 0.1,
        'max_zoom'           => 10,
        'zoom_step'          => 0.1,
        'f_thumbnails'       => true,
        'f_page_nav'         => true,
        'f_zoom'             => true,
        'f_fit_width_btn'    => true,
        'f_fit_height_btn'   => true,
        'f_search'           => true,
        'f_theme_toggle'     => true,
        'f_print'            => true,
        'f_download'         => true,
        'f_fullscreen'       => true,
        'f_status_bar'       => true,
        'sidebar_open'       => false,
    ), $atts );

    // Extract for easier use, but remember to escape during output
    extract( $a );

    $uid = 'cpdfv-' . wp_generate_password( 8, false );
    
    // Visibility classes: if a feature is false, we add a 'cpdfv-hidden' class
    $v_thumbnails      = $f_thumbnails ? '' : ' cpdfv-hidden';
    $v_page_nav        = $f_page_nav ? '' : ' cpdfv-hidden';
    $v_zoom            = $f_zoom ? '' : ' cpdfv-hidden';
    $v_fit_width_btn   = $f_fit_width_btn ? '' : ' cpdfv-hidden';
    $v_fit_height_btn  = $f_fit_height_btn ? '' : ' cpdfv-hidden';
    $v_search          = $f_search ? '' : ' cpdfv-hidden';
    $v_theme_toggle    = $f_theme_toggle ? '' : ' cpdfv-hidden';
    $v_print           = $f_print ? '' : ' cpdfv-hidden';
    $v_download        = $f_download ? '' : ' cpdfv-hidden';
    $v_fullscreen      = $f_fullscreen ? '' : ' cpdfv-hidden';
    $v_status_bar      = $f_status_bar ? '' : ' cpdfv-hidden';
    $sb_vis            = $f_thumbnails ? '' : ' cpdfv-hidden';

    // Fetch the global option (with the default fallback)
    $pro_tip_text = get_option('cpdfv_global_pro_tip', 'Pro-Tip: Click <a href="#" class="cpdfv-tip-link">Full Screen</a> for the best viewing experience.');

    $custom_css = "
        #" . esc_html( $uid ) . " {
            --cpdfv-height: " . esc_attr( $height ) . ";
            --cpdfv-tablet-height: " . esc_attr( $tablet_height ) . ";
            --cpdfv-mobile-height: " . esc_attr( $mobile_height ) . ";
        }
    ";

    wp_add_inline_style( 'cpdfv-core-css', $custom_css );

    ob_start();
    ?>
    <div id="<?php echo esc_attr( $uid ); ?>"
         class="cpdfv-root <?php echo $theme === 'light' ? 'cpdfv-light' : 'cpdfv-dark'; ?> <?php echo $cover_mode ? 'cpdfv-has-cover' : ''; ?>"
         data-url="<?php echo esc_url( $url ); ?>"
         data-title="<?php echo esc_attr( $title ); ?>"
         data-default-zoom="<?php echo esc_attr( $default_zoom ); ?>"
         data-min-zoom="<?php echo esc_attr( $min_zoom ); ?>"
         data-max-zoom="<?php echo esc_attr( $max_zoom ); ?>"
         data-zoom-step="<?php echo esc_attr( $zoom_step ); ?>"
         data-start-page="<?php echo esc_attr( $start_page ); ?>"
         data-loading-text="<?php echo esc_attr( $loading_text ); ?>"
         data-error-text="<?php echo esc_attr( $error_text ); ?>"
         data-pro-tip="<?php echo esc_attr( $pro_tip_text ); ?>">

        <?php if ( $title || $subtitle || $brand ) : ?>
            <div class="cpdfv-header">
                <?php if ( $brand ) : ?>
                    <div class="cpdfv-header-brand"><?php echo esc_html( $brand ); ?></div>
                <?php endif; ?>
                <?php if ( $title ) : ?>
                    <div class="cpdfv-header-title"><?php echo esc_html( $title ); ?></div>
                <?php endif; ?>
                <?php if ( $subtitle ) : ?>
                    <div class="cpdfv-header-sub"><?php echo esc_html( $subtitle ); ?></div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ( $cover_mode ) : ?>
            <div class="cpdfv-cover" data-el="cover">
                <div class="cpdfv-cover-inner">
                    <div class="cpdfv-cover-art" data-action="openViewer" tabindex="0" role="button" aria-label="<?php echo esc_attr( $cover_hint ? $cover_hint : 'Open document' ); ?>">
                        <?php if ( $cover_image ) : ?>
                            <img class="cpdfv-cover-image" data-el="coverImage" src="<?php echo esc_url( $cover_image ); ?>" alt="<?php echo esc_attr( $title ? $title : 'PDF cover' ); ?>">
                        <?php else : ?>
                            <canvas class="cpdfv-cover-canvas" data-el="coverCanvas"></canvas>
                        <?php endif; ?>
                        <?php if ( $cover_overlay === 'yes' ) : ?>
                            <div class="cpdfv-cover-overlay"><?php echo esc_html( $cover_overlay_text ? $cover_overlay_text : 'Click to Open' ); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="cpdfv-cover-copy">
                        <div class="cpdfv-cover-eyebrow"><?php echo esc_html( $brand ? $brand : 'PDF Viewer' ); ?></div>
                        <div class="cpdfv-cover-title"><?php echo esc_html( $title ? $title : 'Open document' ); ?></div>
                        <?php if ( $subtitle || $cover_hint ) : ?>
                            <div class="cpdfv-cover-text">
                                <?php echo esc_html( $subtitle ? $subtitle . ' ' : '' ); ?>
                                <?php echo esc_html( $cover_hint ); ?>
                            </div>
                        <?php endif; ?>
                        <?php if ( $cover_button !== 'no' ) : ?>
                            <div class="cpdfv-cover-btn-wrap">
                                <button type="button" class="cpdfv-cover-btn" data-action="openViewer"><?php echo esc_html( $cover_button_text ); ?></button>
                            </div>
                        <?php endif; ?>
                        <div class="cpdfv-cover-meta" data-el="coverMeta"></div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="cpdfv-toolbar">
            <?php if ( $f_thumbnails ) : ?>
                <button class="cpdfv-btn<?php echo esc_attr( $v_thumbnails ); ?> <?php echo $sidebar_open ? 'active' : ''; ?>" data-action="sidebar" title="Toggle Thumbnails">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>
                </button>
                <div class="cpdfv-sep<?php echo esc_attr( $v_thumbnails ); ?>"></div>
            <?php endif; ?>

            <?php if ( $f_page_nav ) : ?>
                <div class="cpdfv-page-nav<?php echo esc_attr( $v_page_nav ); ?>">
                    <button class="cpdfv-btn" data-action="prev">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><polyline points="15 18 9 12 15 6"/></svg>
                    </button>
                    <input type="text" class="cpdfv-page-input" data-el="pageInput" value="1">
                    <span class="cpdfv-page-total" data-el="pageTotal">/ -</span>
                    <button class="cpdfv-btn" data-action="next">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><polyline points="9 6 15 12 9 18"/></svg>
                    </button>
                </div>
            <?php endif; ?>

            <?php if ( $f_zoom ) : ?>
                <div class="cpdfv-sep<?php echo esc_attr( $v_zoom ); ?>"></div>
                <button class="cpdfv-btn<?php echo esc_attr( $v_zoom ); ?>" data-action="zoomOut">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="8" y1="11" x2="14" y2="11"/></svg>
                </button>
                <span class="cpdfv-zoom-display<?php echo esc_attr( $v_zoom ); ?>" data-el="zoomDisp">100%</span>
                <button class="cpdfv-btn<?php echo esc_attr( $v_zoom ); ?>" data-action="zoomIn">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="11" y1="8" x2="11" y2="14"/><line x1="8" y1="11" x2="14" y2="11"/></svg>
                </button>
            <?php endif; ?>

            <?php if ( $f_fit_width_btn ) : ?>
                <button class="cpdfv-btn<?php echo esc_attr( $v_fit_width_btn ); ?>" data-action="fitWidth" title="Fit to width">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><line x1="3" y1="12" x2="21" y2="12"/><polyline points="7 8 3 12 7 16"/><polyline points="17 8 21 12 17 16"/><line x1="3" y1="4" x2="3" y2="20"/><line x1="21" y1="4" x2="21" y2="20"/></svg>
                </button>
            <?php endif; ?>

            <?php if ( $f_fit_height_btn ) : ?>
                <button class="cpdfv-btn<?php echo esc_attr( $v_fit_height_btn ); ?>" data-action="fitHeight" title="Fit to height">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><line x1="12" y1="3" x2="12" y2="21"/><polyline points="8 7 12 3 16 7"/><polyline points="8 17 12 21 16 17"/><line x1="4" y1="3" x2="20" y2="3"/><line x1="4" y1="21" x2="20" y2="21"/></svg>
                </button>
            <?php endif; ?>

            <div class="cpdfv-spacer"></div>

            <?php if ( $f_search ) : ?>
                <button class="cpdfv-btn<?php echo esc_attr( $v_search ); ?>" data-action="search" title="Search Document">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                </button>
            <?php endif; ?>

            <?php if ( $f_theme_toggle ) : ?>
                <button class="cpdfv-btn<?php echo esc_attr( $v_theme_toggle ); ?>" data-action="themeToggle" title="Toggle Dark Mode">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
                </button>
            <?php endif; ?>

            <?php if ( $f_print ) : ?>
                <button class="cpdfv-btn<?php echo esc_attr( $v_print ); ?>" data-action="print" title="Print Document">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                </button>
            <?php endif; ?>

            <?php if ( $f_download ) : ?>
                <button class="cpdfv-btn<?php echo esc_attr( $v_download ); ?>" data-action="download" Title="Download PDF">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                </button>
            <?php endif; ?>

            <?php if ( $f_fullscreen ) : ?>
                <button class="cpdfv-btn<?php echo esc_attr( $v_fullscreen ); ?>" data-action="fullscreen" title="Toggle Full Screen">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><polyline points="15 3 21 3 21 9"/><polyline points="9 21 3 21 3 15"/><polyline points="21 3 14 10"/><polyline points="3 21 10 14"/></svg>
                </button>
            <?php endif; ?>
            <div class="cpdfv-viewer-tip">
                <span class="cpdfv-tip-icon">💡</span> 
                <span class="cpdfv-tip-text">
                    Pro-Tip: Click <a href="#" class="cpdfv-tip-link">Full Screen</a> for the best viewing experience.
                </span>
            </div>
        </div>

        <?php if ( $f_search ) : ?>
            <div class="cpdfv-search-bar<?php echo esc_attr( $v_search ); ?>" data-el="searchBar">
                <input type="text" class="cpdfv-search-input" data-el="searchInput" placeholder="Search in document...">
                <span class="cpdfv-search-info" data-el="searchInfo"></span>
                <div class="cpdfv-search-nav">
                    <button class="cpdfv-btn" data-action="searchPrev" title="Previous">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><polyline points="18 15 12 9 6 15"/></svg>
                    </button>
                    <button class="cpdfv-btn" data-action="searchNext" title="Next">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><polyline points="6 9 12 15 18 9"/></svg>
                    </button>
                </div>
                <button class="cpdfv-btn" data-action="searchClose" style="width:26px;height:26px">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width:13px;height:13px"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
        <?php endif; ?>

        <div class="cpdfv-body">
            <?php if ( $f_thumbnails ) : ?>
                <div class="cpdfv-sidebar<?php echo esc_attr( $sb_vis ); ?> <?php echo $sidebar_open ? 'open' : ''; ?>" data-el="sidebar">
                    <div class="cpdfv-sidebar-inner" data-el="thumbs"></div>
                </div>
            <?php else : ?>
                <div class="cpdfv-sidebar" data-el="sidebar" style="display:none">
                    <div class="cpdfv-sidebar-inner" data-el="thumbs"></div>
                </div>
            <?php endif; ?>

            <div class="cpdfv-canvas-wrap" data-el="canvasWrap"><canvas data-el="canvas"></canvas></div>

            <div class="cpdfv-error" data-el="error">
                <div class="cpdfv-error-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                </div>
                <div class="cpdfv-error-title">Unable to load document</div>
                <div class="cpdfv-error-msg" data-el="errorMsg"></div>
                <button class="cpdfv-retry-btn" data-action="retry">Retry</button>
            </div>

            <div class="cpdfv-loader" data-el="loader">
                <div class="cpdfv-spinner"></div>
                <div class="cpdfv-loader-text" data-el="loaderText"><?php echo esc_html( $loading_text ); ?></div>
            </div>
        </div>

        <?php if ( $f_status_bar ) : ?>
            <div class="cpdfv-status<?php echo esc_attr( $v_status_bar ); ?>">
                <div class="cpdfv-status-dot"></div>
                <span data-el="statusInfo">Ready</span>
                <span style="margin-left:auto" data-el="statusPages"></span>
            </div>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * 2. Block Registration Hook (Restored)
 * This binds the backend shortcode to the Gutenberg block editor.
 */
add_action( 'init', 'cpdfv_register_block' );
function cpdfv_register_block() {
    register_block_type( __DIR__ . '/block', array(
        'render_callback' => 'cpdfv_render_shortcode'
    ));
}

/**
 * 3. Frontend Assets Enqueue (Updated for ES Modules)
 */
add_action( 'wp_enqueue_scripts', 'cpdfv_frontend_assets' );
function cpdfv_frontend_assets() {
    wp_enqueue_style(
        'cpdfv-core-css',
        plugins_url( 'pdf-viewer-core.css', __FILE__ ),
        array(),
        filemtime( plugin_dir_path( __FILE__ ) . 'pdf-viewer-core.css' )
    );

    // Enqueue our core JS (We no longer need to register/enqueue pdfjs-lib separately)
    wp_enqueue_script(
        'cpdfv-core-js',
        plugins_url( 'pdf-viewer-core.js', __FILE__ ),
        array(), // Dependencies array is now empty
        filemtime( plugin_dir_path( __FILE__ ) . 'pdf-viewer-core.js' ),
        true
    );

    // Localize settings (Important: Update the worker to the .mjs version)
    wp_localize_script( 'cpdfv-core-js', 'cpdfvSettings', array(
        'workerUrl' => plugins_url( 'lib/pdfjs/pdf.worker.mjs', __FILE__ )
    ));
}

/**
 * NEW: Force WordPress to output cpdfv-core-js as an ES Module
 */
add_filter( 'script_loader_tag', 'cpdfv_add_module_type', 10, 3 );
function cpdfv_add_module_type( $tag, $handle, $src ) {
    if ( 'cpdfv-core-js' === $handle ) {
        // Find the opening <script tag and replace it with <script type="module"
        return str_replace( '<script ', '<script type="module" ', $tag );
    }
    return $tag;
}

/**
 * 4. Admin Menu Registration
 */
add_action( 'admin_menu', 'cpdfv_admin_menu' );
function cpdfv_admin_menu() {
    add_management_page(
        'Continuous PDF Viewer Generator',
        'Continuous PDF Viewer',
        'edit_posts',
        'cpdfv-generator',
        'cpdfv_admin_page'
    );
}

/**
 * 5. Admin Settings Enqueue
 */
add_action( 'admin_enqueue_scripts', 'cpdfv_admin_assets' );
function cpdfv_admin_assets( $hook ) {
    if ( $hook !== 'tools_page_cpdfv-generator' ) return;
    wp_enqueue_style( 'cpdfv-admin-css', plugins_url( 'pdf-viewer-admin.css', __FILE__ ), array(), '2.1.0' );
    wp_enqueue_style( 'wp-color-picker' );
    wp_enqueue_script( 'wp-color-picker' );
    wp_enqueue_media();
    wp_enqueue_script( 'cpdfv-admin-js', plugins_url( 'pdf-viewer-admin.js', __FILE__ ), array( 'jquery', 'wp-color-picker' ), '2.1.0', true );
}

/**
 * 6. Gutenberg Editor Enqueue
 */
add_action( 'enqueue_block_editor_assets', 'cpdfv_enqueue_block_editor_assets' );
function cpdfv_enqueue_block_editor_assets() {
    wp_enqueue_script(
        'cpdfv-block-editor-js',
        plugins_url( 'block/index.js', __FILE__ ),
        array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components' ),
        filemtime( plugin_dir_path( __FILE__ ) . 'block/index.js' ),
        true
    );
}

// 8. Register the setting
add_action('admin_init', 'cpdfv_register_settings');
function cpdfv_register_settings() {
    register_setting(
        'cpdfv_settings_group', 
        'cpdfv_global_pro_tip',
        array(
            'type'              => 'string',
            'sanitize_callback' => 'wp_kses_post', // Safely allows standard HTML links
            'default'           => 'Pro-Tip: Click <a href="#" class="cpdfv-tip-link">Full Screen</a> for the best viewing experience.'
        )
    );
}

// 9. Add the Settings Page to the WordPress menu
add_action('admin_menu', 'cpdfv_add_settings_page');
function cpdfv_add_settings_page() {
    add_options_page('PDF Viewer Settings', 'PDF Viewer', 'manage_options', 'cpdfv-settings', 'cpdfv_settings_page_html');
}

// 10. Render the Settings Page UI
function cpdfv_settings_page_html() {
    // The default tip if the option hasn't been saved yet
    $default_tip = 'Pro-Tip: Click <a href="#" class="cpdfv-tip-link">Full Screen</a> for the best viewing experience.';
    $current_tip = get_option('cpdfv_global_pro_tip', $default_tip);
    ?>
    <div class="wrap">
        <h1>Continuous PDF Viewer Settings</h1>
        <form method="post" action="options.php">
            <?php settings_fields('cpdfv_settings_group'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Toolbar Pro Tip Text</th>
                    <td>
                        <textarea name="cpdfv_global_pro_tip" rows="3" style="width: 100%; max-width: 600px;"><?php echo esc_textarea($current_tip); ?></textarea>
                        <p class="description">Leave this completely blank to remove the tip and the lightbulb icon from the toolbar.</p>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

/**
 * 11. Admin Settings Page HTML
 */
function cpdfv_admin_page() {
if ( ! current_user_can( 'edit_posts' ) ) return;
?>
<div id="cpdfv-wrap">
<div class="cpdfv-hero"><span class="cpdfv-hero-badge">Shortcode Generator</span><h1>Continuous PDF Viewer</h1><p>Configure your viewer below - shortcode updates live on the right. Copy and paste it anywhere.</p></div>
<div class="cpdfv-grid">
<div class="cpdfv-config-col">

<div class="cpdfv-card"><div class="cpdfv-card-head"><div class="cpdfv-card-icon" style="background:#eef2ff;color:#4f7df3"></div><h2>Document</h2></div>
<div class="cpdfv-card-body">
  <div class="cpdfv-field"><label>PDF URL <span class="cpdfv-hint">(required)</span></label><div class="cpdfv-url-wrap"><input type="text" id="cpdfv_url" data-attr="url" placeholder="/wp-content/uploads/2025/whitepaper.pdf"><button type="button" class="cpdfv-media-btn" id="cpdfv-media-btn"> Media</button></div></div>
  <div class="cpdfv-field"><label>Cover Image URL <span class="cpdfv-hint">(optional)</span></label><div class="cpdfv-url-wrap"><input type="text" id="cpdfv_cover_image" data-attr="cover_image" placeholder="/wp-content/uploads/2025/pdf-cover.jpg"><button type="button" class="cpdfv-media-btn" id="cpdfv-cover-media-btn"> Image</button></div></div>
  <div class="cpdfv-field"><label>Title</label><input type="text" data-attr="title" placeholder="2025 Industry Whitepaper"></div>
  <div class="cpdfv-field"><label>Subtitle</label><input type="text" data-attr="subtitle" placeholder="Explore key findings..."></div>
  <div class="cpdfv-field"><label>Brand Label</label><input type="text" data-attr="brand" placeholder="Acme Corp"></div>
</div></div>

<div class="cpdfv-card"><div class="cpdfv-card-head"><div class="cpdfv-card-icon" style="background:#fef3e2;color:#e8860c"></div><h2>Dimensions</h2></div>
<div class="cpdfv-card-body">
  <div class="cpdfv-row-3">
    <div class="cpdfv-field"><label>Height Desktop</label><input type="text" data-attr="height" data-default="80vh" value="80vh"></div>
    <div class="cpdfv-field"><label>Height Tablet</label><input type="text" data-attr="tablet_height" data-default="70vh" value="70vh"></div>
    <div class="cpdfv-field"><label>Height Mobile</label><input type="text" data-attr="mobile_height" data-default="60vh" value="60vh"></div>
  </div>
  <div class="cpdfv-row-3">
    <div class="cpdfv-field"><label>Width</label><input type="text" data-attr="width" data-default="100%" value="100%"></div>
    <div class="cpdfv-field"><label>Max Width</label><input type="text" data-attr="max_width" data-default="100%" value="100%"></div>
    <div class="cpdfv-field"><label>Border Radius <span class="cpdfv-hint">(px)</span></label><input type="number" data-attr="border_radius" data-default="12" value="12" min="0" max="50"></div>
  </div>
</div></div>

<div class="cpdfv-card"><div class="cpdfv-card-head"><div class="cpdfv-card-icon" style="background:#f0e6ff;color:#8b5cf6"></div><h2>Theme &amp; Colours</h2></div>
<div class="cpdfv-card-body">
  <div class="cpdfv-row">
    <div class="cpdfv-field"><label>Theme</label><select data-attr="theme" data-default="light"><option value="light" selected>Light</option><option value="dark">Dark</option></select></div>
    <div class="cpdfv-field"><label>Accent Colour</label><input type="text" class="cpdfv-color" data-attr="accent" data-default="#4f7df3" value="#4f7df3"></div>
  </div>
  <div class="cpdfv-row">
    <div class="cpdfv-field"><label>Background <span class="cpdfv-hint">(optional)</span></label><input type="text" class="cpdfv-color" data-attr="bg_color" value=""></div>
    <div class="cpdfv-field"><label>Surface Colour</label><input type="text" class="cpdfv-color" data-attr="surface_color" value=""></div>
  </div>
  <div class="cpdfv-row">
    <div class="cpdfv-field"><label>Text Colour <span class="cpdfv-hint">(optional)</span></label><input type="text" class="cpdfv-color" data-attr="text_color" value=""></div><div class="cpdfv-field"></div>
  </div>
  <div class="cpdfv-row">
    <div class="cpdfv-field"><label>Body Font</label><input type="text" data-attr="font" data-default="DM Sans" value="DM Sans"></div>
    <div class="cpdfv-field"><label>Display Font</label><input type="text" data-attr="font_display" data-default="Instrument Serif" value="Instrument Serif"></div>
  </div>
</div></div>

<div class="cpdfv-card"><div class="cpdfv-card-head"><div class="cpdfv-card-icon" style="background:#e8faf0;color:#059669"></div><h2>Feature Visibility</h2></div>
<div class="cpdfv-card-body">
    <div class="cpdfv-drow" data-feature="download">
      <div class="cpdfv-drow-info"><div class="cpdfv-drow-label">Download Button</div><div class="cpdfv-drow-desc">Allow users to download the PDF</div></div>
      <div class="cpdfv-drow-pills">
        <div class="cpdfv-pill active" data-attr="download_desktop" data-default="yes" title="Desktop (>1024px)"><span class="cpdfv-pill-icon"></span><span class="cpdfv-pill-text">Desktop</span><span class="cpdfv-pill-dot"></span></div>
        <div class="cpdfv-pill active" data-attr="download_tablet" data-default="yes" title="Tablet (641-1024px)"><span class="cpdfv-pill-icon"></span><span class="cpdfv-pill-text">Tablet</span><span class="cpdfv-pill-dot"></span></div>
        <div class="cpdfv-pill active" data-attr="download_mobile" data-default="yes" title="Mobile (<640px)"><span class="cpdfv-pill-icon"></span><span class="cpdfv-pill-text">Mobile</span><span class="cpdfv-pill-dot"></span></div>
      </div>
    </div>
    <div class="cpdfv-drow" data-feature="print">
      <div class="cpdfv-drow-info"><div class="cpdfv-drow-label">Print Button</div><div class="cpdfv-drow-desc">Allow users to print the document</div></div>
      <div class="cpdfv-drow-pills">
        <div class="cpdfv-pill active" data-attr="print_desktop" data-default="yes" title="Desktop (>1024px)"><span class="cpdfv-pill-icon"></span><span class="cpdfv-pill-text">Desktop</span><span class="cpdfv-pill-dot"></span></div>
        <div class="cpdfv-pill active" data-attr="print_tablet" data-default="yes" title="Tablet (641-1024px)"><span class="cpdfv-pill-icon"></span><span class="cpdfv-pill-text">Tablet</span><span class="cpdfv-pill-dot"></span></div>
        <div class="cpdfv-pill active" data-attr="print_mobile" data-default="yes" title="Mobile (<640px)"><span class="cpdfv-pill-icon"></span><span class="cpdfv-pill-text">Mobile</span><span class="cpdfv-pill-dot"></span></div>
      </div>
    </div>
    <div class="cpdfv-drow" data-feature="fullscreen">
      <div class="cpdfv-drow-info"><div class="cpdfv-drow-label">Fullscreen</div><div class="cpdfv-drow-desc">Expand viewer to fill entire screen</div></div>
      <div class="cpdfv-drow-pills">
        <div class="cpdfv-pill active" data-attr="fullscreen_desktop" data-default="yes" title="Desktop (>1024px)"><span class="cpdfv-pill-icon"></span><span class="cpdfv-pill-text">Desktop</span><span class="cpdfv-pill-dot"></span></div>
        <div class="cpdfv-pill active" data-attr="fullscreen_tablet" data-default="yes" title="Tablet (641-1024px)"><span class="cpdfv-pill-icon"></span><span class="cpdfv-pill-text">Tablet</span><span class="cpdfv-pill-dot"></span></div>
        <div class="cpdfv-pill active" data-attr="fullscreen_mobile" data-default="yes" title="Mobile (<640px)"><span class="cpdfv-pill-icon"></span><span class="cpdfv-pill-text">Mobile</span><span class="cpdfv-pill-dot"></span></div>
      </div>
    </div>
    <div class="cpdfv-drow" data-feature="search">
      <div class="cpdfv-drow-info"><div class="cpdfv-drow-label">Search</div><div class="cpdfv-drow-desc">Text search within the document</div></div>
      <div class="cpdfv-drow-pills">
        <div class="cpdfv-pill active" data-attr="search_desktop" data-default="yes" title="Desktop (>1024px)"><span class="cpdfv-pill-icon"></span><span class="cpdfv-pill-text">Desktop</span><span class="cpdfv-pill-dot"></span></div>
        <div class="cpdfv-pill active" data-attr="search_tablet" data-default="yes" title="Tablet (641-1024px)"><span class="cpdfv-pill-icon"></span><span class="cpdfv-pill-text">Tablet</span><span class="cpdfv-pill-dot"></span></div>
        <div class="cpdfv-pill active" data-attr="search_mobile" data-default="yes" title="Mobile (<640px)"><span class="cpdfv-pill-icon"></span><span class="cpdfv-pill-text">Mobile</span><span class="cpdfv-pill-dot"></span></div>
      </div>
    </div>
    <div class="cpdfv-drow" data-feature="thumbnails">
      <div class="cpdfv-drow-info"><div class="cpdfv-drow-label">Thumbnails Sidebar</div><div class="cpdfv-drow-desc">Page thumbnail navigation panel</div></div>
      <div class="cpdfv-drow-pills">
        <div class="cpdfv-pill active" data-attr="thumbnails_desktop" data-default="yes" title="Desktop (>1024px)"><span class="cpdfv-pill-icon"></span><span class="cpdfv-pill-text">Desktop</span><span class="cpdfv-pill-dot"></span></div>
        <div class="cpdfv-pill active" data-attr="thumbnails_tablet" data-default="yes" title="Tablet (641-1024px)"><span class="cpdfv-pill-icon"></span><span class="cpdfv-pill-text">Tablet</span><span class="cpdfv-pill-dot"></span></div>
        <div class="cpdfv-pill active" data-attr="thumbnails_mobile" data-default="yes" title="Mobile (<640px)"><span class="cpdfv-pill-icon"></span><span class="cpdfv-pill-text">Mobile</span><span class="cpdfv-pill-dot"></span></div>
      </div>
    </div>
    <div class="cpdfv-drow" data-feature="zoom">
      <div class="cpdfv-drow-info"><div class="cpdfv-drow-label">Zoom Controls</div><div class="cpdfv-drow-desc">Zoom in, out, and percentage display</div></div>
      <div class="cpdfv-drow-pills">
        <div class="cpdfv-pill active" data-attr="zoom_desktop" data-default="yes" title="Desktop (>1024px)"><span class="cpdfv-pill-icon"></span><span class="cpdfv-pill-text">Desktop</span><span class="cpdfv-pill-dot"></span></div>
        <div class="cpdfv-pill active" data-attr="zoom_tablet" data-default="yes" title="Tablet (641-1024px)"><span class="cpdfv-pill-icon"></span><span class="cpdfv-pill-text">Tablet</span><span class="cpdfv-pill-dot"></span></div>
        <div class="cpdfv-pill active" data-attr="zoom_mobile" data-default="yes" title="Mobile (<640px)"><span class="cpdfv-pill-icon"></span><span class="cpdfv-pill-text">Mobile</span><span class="cpdfv-pill-dot"></span></div>
      </div>
    </div>
    <div class="cpdfv-drow" data-feature="theme_toggle">
      <div class="cpdfv-drow-info"><div class="cpdfv-drow-label">Theme Toggle</div><div class="cpdfv-drow-desc">Let users switch dark &amp; light</div></div>
      <div class="cpdfv-drow-pills">
        <div class="cpdfv-pill active" data-attr="theme_toggle_desktop" data-default="yes" title="Desktop (>1024px)"><span class="cpdfv-pill-icon"></span><span class="cpdfv-pill-text">Desktop</span><span class="cpdfv-pill-dot"></span></div>
        <div class="cpdfv-pill active" data-attr="theme_toggle_tablet" data-default="yes" title="Tablet (641-1024px)"><span class="cpdfv-pill-icon"></span><span class="cpdfv-pill-text">Tablet</span><span class="cpdfv-pill-dot"></span></div>
        <div class="cpdfv-pill active" data-attr="theme_toggle_mobile" data-default="yes" title="Mobile (<640px)"><span class="cpdfv-pill-icon"></span><span class="cpdfv-pill-text">Mobile</span><span class="cpdfv-pill-dot"></span></div>
      </div>
    </div>
    <div class="cpdfv-drow" data-feature="page_nav">
      <div class="cpdfv-drow-info"><div class="cpdfv-drow-label">Page Navigation</div><div class="cpdfv-drow-desc">Prev / next buttons and page input</div></div>
      <div class="cpdfv-drow-pills">
        <div class="cpdfv-pill active" data-attr="page_nav_desktop" data-default="yes" title="Desktop (>1024px)"><span class="cpdfv-pill-icon"></span><span class="cpdfv-pill-text">Desktop</span><span class="cpdfv-pill-dot"></span></div>
        <div class="cpdfv-pill active" data-attr="page_nav_tablet" data-default="yes" title="Tablet (641-1024px)"><span class="cpdfv-pill-icon"></span><span class="cpdfv-pill-text">Tablet</span><span class="cpdfv-pill-dot"></span></div>
        <div class="cpdfv-pill active" data-attr="page_nav_mobile" data-default="yes" title="Mobile (<640px)"><span class="cpdfv-pill-icon"></span><span class="cpdfv-pill-text">Mobile</span><span class="cpdfv-pill-dot"></span></div>
      </div>
    </div>
    <div class="cpdfv-drow" data-feature="fit_width_btn">
      <div class="cpdfv-drow-info"><div class="cpdfv-drow-label">Fit-to-Width Button</div><div class="cpdfv-drow-desc">One-click auto-fit zoom</div></div>
      <div class="cpdfv-drow-pills">
        <div class="cpdfv-pill active" data-attr="fit_width_btn_desktop" data-default="yes" title="Desktop (>1024px)"><span class="cpdfv-pill-icon"></span><span class="cpdfv-pill-text">Desktop</span><span class="cpdfv-pill-dot"></span></div>
        <div class="cpdfv-pill active" data-attr="fit_width_btn_tablet" data-default="yes" title="Tablet (641-1024px)"><span class="cpdfv-pill-icon"></span><span class="cpdfv-pill-text">Tablet</span><span class="cpdfv-pill-dot"></span></div>
        <div class="cpdfv-pill active" data-attr="fit_width_btn_mobile" data-default="yes" title="Mobile (<640px)"><span class="cpdfv-pill-icon"></span><span class="cpdfv-pill-text">Mobile</span><span class="cpdfv-pill-dot"></span></div>
      </div>
    </div>
    <div class="cpdfv-drow" data-feature="fit_height_btn">
      <div class="cpdfv-drow-info"><div class="cpdfv-drow-label">Fit-to-Height Button</div><div class="cpdfv-drow-desc">One-click auto-fit height</div></div>
      <div class="cpdfv-drow-pills">
        <div class="cpdfv-pill active" data-attr="fit_height_btn_desktop" data-default="yes" title="Desktop (>1024px)"><span class="cpdfv-pill-icon"></span><span class="cpdfv-pill-text">Desktop</span><span class="cpdfv-pill-dot"></span></div>
        <div class="cpdfv-pill active" data-attr="fit_height_btn_tablet" data-default="yes" title="Tablet (641-1024px)"><span class="cpdfv-pill-icon"></span><span class="cpdfv-pill-text">Tablet</span><span class="cpdfv-pill-dot"></span></div>
        <div class="cpdfv-pill active" data-attr="fit_height_btn_mobile" data-default="yes" title="Mobile (<640px)"><span class="cpdfv-pill-icon"></span><span class="cpdfv-pill-text">Mobile</span><span class="cpdfv-pill-dot"></span></div>
      </div>
    </div>
    <div class="cpdfv-drow" data-feature="status_bar">
      <div class="cpdfv-drow-info"><div class="cpdfv-drow-label">Status Bar</div><div class="cpdfv-drow-desc">Bottom bar showing file info</div></div>
      <div class="cpdfv-drow-pills">
        <div class="cpdfv-pill active" data-attr="status_bar_desktop" data-default="yes" title="Desktop (>1024px)"><span class="cpdfv-pill-icon"></span><span class="cpdfv-pill-text">Desktop</span><span class="cpdfv-pill-dot"></span></div>
        <div class="cpdfv-pill active" data-attr="status_bar_tablet" data-default="yes" title="Tablet (641-1024px)"><span class="cpdfv-pill-icon"></span><span class="cpdfv-pill-text">Tablet</span><span class="cpdfv-pill-dot"></span></div>
        <div class="cpdfv-pill active" data-attr="status_bar_mobile" data-default="yes" title="Mobile (<640px)"><span class="cpdfv-pill-icon"></span><span class="cpdfv-pill-text">Mobile</span><span class="cpdfv-pill-dot"></span></div>
      </div>
    </div>
    <div class="cpdfv-toggle-row"><div><div class="cpdfv-toggle-label">Keyboard Shortcuts</div><div class="cpdfv-toggle-desc">Arrow keys, +/-, Ctrl+F, Esc</div></div><label class="cpdfv-switch"><input type="checkbox" data-attr="keyboard" data-default="yes" checked><span class="slider"></span></label></div>
</div></div>

<div class="cpdfv-card"><div class="cpdfv-card-head"><div class="cpdfv-card-icon" style="background:#fef2f2;color:#dc2626"></div><h2>Zoom Settings</h2></div>
<div class="cpdfv-card-body">
  <div class="cpdfv-field"><label>Default Zoom <span class="cpdfv-hint">(fit_width, fit_height, fit, or number)</span></label><input type="text" data-attr="default_zoom" data-default="fit_width" value="fit_width"></div>
  <div class="cpdfv-row-3">
    <div class="cpdfv-field"><label>Min Zoom</label><input type="number" data-attr="min_zoom" data-default="0.3" value="0.3" step="0.1" min="0.1"></div>
    <div class="cpdfv-field"><label>Max Zoom</label><input type="number" data-attr="max_zoom" data-default="5" value="5" step="0.5" min="1"></div>
    <div class="cpdfv-field"><label>Zoom Step</label><input type="number" data-attr="zoom_step" data-default="0.2" value="0.2" step="0.05" min="0.05"></div>
  </div>
</div></div>

<div class="cpdfv-card"><div class="cpdfv-card-head"><div class="cpdfv-card-icon" style="background:#f0f8ff;color:#2563eb"></div><h2>Behaviour</h2></div>
<div class="cpdfv-card-body">
  <div class="cpdfv-row">
    <div class="cpdfv-field"><label>Start Page</label><input type="number" data-attr="start_page" data-default="1" value="1" min="1"></div>
    <div class="cpdfv-field"><label>Open Sidebar on Load</label><select data-attr="sidebar_open" data-default="no"><option value="no">No</option><option value="yes">Yes</option></select></div>
  </div>
  <div class="cpdfv-row">
    <div class="cpdfv-field"><label>Show Cover First</label><select data-attr="cover_mode" data-default="no"><option value="no">No</option><option value="yes">Yes</option></select></div>
    <div class="cpdfv-field"><label>Show Cover Button</label><select data-attr="cover_button" data-default="yes"><option value="yes">Yes</option><option value="no">No</option></select></div>
  </div>
</div></div>
</div>

<div class="cpdfv-preview-col">
  <div class="cpdfv-card" style="position: sticky; top: 40px;">
    <div class="cpdfv-card-head">
      <div class="cpdfv-card-icon" style="background:#e8f0fe;color:#1a73e8"></div>
      <h2>Your Shortcode</h2>
    </div>
    <div class="cpdfv-card-body">
      <p style="margin-top:0; color:#64748b; font-size:14px;">Copy this generated shortcode and paste it into any WordPress post, page, or widget.</p>
      <textarea id="cpdfv-shortcode-output" readonly="readonly" rows="8" style="width:100%; font-family: monospace; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 6px; padding: 12px; font-size: 13px;"></textarea>
      <button type="button" id="cpdfv-copy-btn" class="button button-primary" style="margin-top: 15px; width: 100%;">Copy to Clipboard</button>
      <span id="cpdfv-copy-success" style="display:none; color:#059669; font-size:13px; margin-top:10px; text-align:center; display:block;">Copied!</span>
    </div>
  </div>
</div>
</div>
</div>
<?php
}
?>
