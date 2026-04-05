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
 * 1. Frontend Enqueue: Loads the core viewer and local PDF.js
 */
add_action( 'wp_enqueue_scripts', 'cpv_frontend_assets' );
function cpv_frontend_assets() {
    // Load the new Core Viewer CSS file
    wp_enqueue_style( 'cpv-core-css', plugins_url( 'pdf-viewer-core.css', __FILE__ ), array(), '2.1.0' );
    
    // Register the local PDF.js library
    wp_register_script( 'pdfjs-lib', plugins_url( 'lib/pdfjs/pdf.min.js', __FILE__ ), array(), '3.11.174', true );

    // Enqueue your custom core logic
    wp_enqueue_script( 'cpv-core-js', plugins_url( 'pdf-viewer-core.js', __FILE__ ), array( 'pdfjs-lib' ), '2.1.0', true );
    
    // Safely pass the local worker URL to our JavaScript
    wp_localize_script( 'cpv-core-js', 'cpvSettings', array(
        'workerUrl' => plugins_url( 'lib/pdfjs/pdf.worker.min.js', __FILE__ )
    ));
}

/**
 * 2. Admin Enqueue: Loads the generator logic and color picker
 */
add_action( 'admin_enqueue_scripts', 'cpv_admin_assets' );
function cpv_admin_assets( $hook ) {
    // Only load these on your specific Tools page
    if ( $hook !== 'tools_page_cpv-generator' ) {
        return;
    }
    // Load the new Admin CSS file
    wp_enqueue_style( 'cpv-admin-css', plugins_url( 'pdf-viewer-admin.css', __FILE__ ), array(), '2.1.0' );

    // Load native WordPress assets for the generator
    wp_enqueue_style( 'wp-color-picker' );
    wp_enqueue_script( 'wp-color-picker' );
    wp_enqueue_media();

    // Enqueue your custom admin generator logic
    wp_enqueue_script( 
        'cpv-admin-js', 
        plugins_url( 'pdf-viewer-admin.js', __FILE__ ), 
        array( 'jquery', 'wp-color-picker' ), 
        '2.1.0', 
        true 
    );
}

function cpv_enqueue_block_editor_assets() {
    wp_enqueue_script(
        'cpv-block-editor-js',
        plugins_url( 'block/index.js', __FILE__ ),
        array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components' ),
        filemtime( plugin_dir_path( __FILE__ ) . 'block/index.js' ),
        true
    );
}
add_action( 'enqueue_block_editor_assets', 'cpv_enqueue_block_editor_assets' );

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
