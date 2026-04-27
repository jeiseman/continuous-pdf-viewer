# Continuous PDF Viewer

**Version:** 2.1.5
**Author:** Jonathan A Eiseman
**License:** GPL2

*Thanks to Imran Siddiq (https://www.youtube.com/@websquadron/). This code was adapted from his PDF Viewer code.*

A high-performance, WordPress-native PDF viewer powered by PDF.js. Version 2.1 introduces a completely rebuilt rendering engine optimized for 100% continuous scrolling, bringing a modern, native-app reading experience to your WordPress site.

## Key Features

* **Continuous Scroll Engine:** Pages stack seamlessly for smooth, vertical reading. Highly optimized sequential rendering prevents browser lag, even on large documents.
* **Smart Page Tracking:** An intelligent viewport observer automatically detects which page you are reading and instantly updates the toolbar and sidebar thumbnails as you scroll.
* **Advanced Search & Highlight:** Pinpoint search functionality that calculates the exact pixel location of your search terms and smoothly scrolls them directly into the center of your screen.
* **Native Fullscreen Mode:** Utilizes the browser's native Fullscreen API for distraction-free reading, featuring "scroll memory" to keep your place when entering or exiting fullscreen.
* **Gutenberg Block Integration:** A native WordPress block with a click-to-select placeholder UI and seamless Media Library integration.
* **Live Shortcode Generator:** A dedicated admin tools page (`Tools > Continuous PDF Viewer `) that lets you visually configure colors, dimensions, and toolbar features while generating a live shortcode.

## Usage

### Using the Gutenberg Block
1. Open the WordPress Block Editor.
2. Search for the **Continous PDF Viewer** block.
3. Click **Select PDF File** on the canvas to choose a document from your Media Library.
4. Use the right-hand Inspector Controls to customize the start page, default zoom, sidebar visibility, and colors.

### Using the Shortcode
You can place the viewer anywhere using the `[continuous_pdf_viewer]` shortcode. 

**Basic Example:**
```text
[continuous_pdf_viewer url="[https://yoursite.com/wp-content/uploads/document.pdf](https://yoursite.com/wp-content/uploads/document.pdf)"]
