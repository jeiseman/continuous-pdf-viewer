# PDF Viewer Pro

A lightweight, robust WordPress plugin that renders interactive PDF documents directly on your pages using the native Gutenberg Block Editor. Built with vanilla JavaScript and the PDF.js engine for maximum performance without the overhead of complex build tools.

## Features

* **Gutenberg Integration:** Custom `[cpv/pdf-viewer]` block for seamless embedding directly from the WordPress editor.
* **Shortcode Generator:** Dedicated admin tools page to generate customizable `[pdf_viewer]` shortcodes for classic editing.
* **Interactive Annotation Layer:** Fully supports hyperlinks mapped from the original MS Word documents.
* **Dual Themes:** Easily toggle between Light and Dark display modes.
* **Custom Overlays:** Support for cover images and customizable loading/error states.
* **Responsive Design:** Fluid canvas scaling, fullscreen mode, and mobile-friendly zoom controls.
* **Optimized Loading:** Scripts and styles are logically separated and enqueued only when needed, pulling the core engine directly from a CDN.

## Requirements

* WordPress 5.8+ (for Gutenberg Block support)
* PHP 7.4+

## Installation

1. Download the `pdf-viewer-pro` folder.
2. Upload the folder to the `/wp-content/plugins/` directory on your server.
3. Navigate to the **Plugins** menu in your WordPress dashboard.
4. Locate **PDF Viewer Pro** and click **Activate**.

## Usage

### Using the Gutenberg Block

1. Open any page or post in the Block Editor.
2. Click the `+` icon to add a new block and search for **PDF Viewer Pro** (located under the Media category).
3. Use the sidebar to upload or select a PDF from your Media Library.
4. Customize the theme, starting page, and zoom levels in the block settings.

### Using the Shortcode

1. Navigate to **Tools > PDF Viewer Pro Generator** in the WordPress admin menu.
2. Configure your desired settings (URL, Light/Dark mode, zoom steps).
3. Click **Generate Shortcode**.
4. Copy the resulting `[pdf_viewer ...]` string and paste it into any post, page, or text widget.

## File Structure

* `pdf-viewer-pro.php`: Core plugin file handling WordPress hooks, block registration, and shortcode processing.
* `block/`: Contains `block.json` and `index.js` for Gutenberg editor integration.
* `pdf-viewer-core.js` & `pdf-viewer-core.css`: Frontend engine for rendering the PDF.js canvas and interactive layers.
* `pdf-viewer-admin.js` & `pdf-viewer-admin.css`: Backend UI and logic for the shortcode generator.

## Dependencies

This plugin utilizes [PDF.js](https://mozilla.github.io/pdf.js/) (v3.11.174) served via Cloudflare CDN for document rendering.

## License

GPLv2
