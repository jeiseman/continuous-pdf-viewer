import * as pdfjsLib from './lib/pdfjs/pdf.mjs';

// 1. Initialize Local PDF.js Worker
if (window.cpdfvSettings) {
    pdfjsLib.GlobalWorkerOptions.workerSrc = window.cpdfvSettings.workerUrl;
}

// 2. The main initialization loop
function boot() {
    var roots = document.querySelectorAll('.cpdfv-root');
    
    roots.forEach(function(root) {
        // Prevent double-initialization
        if (root.dataset.initialized) return;
        root.dataset.initialized = 'true';

        // Extract configuration from data attributes
        var CFG = {
            url: root.dataset.url,
            defaultZoom: root.dataset.defaultZoom || 'fit_width',
            minZoom: parseFloat(root.dataset.minZoom) || 0.3,
            maxZoom: parseFloat(root.dataset.maxZoom) || 5,
            zoomStep: parseFloat(root.dataset.zoomStep) || 0.2,
            startPage: parseInt(root.dataset.startPage) || 1,
            loadingText: root.dataset.loadingText || 'Loading...',
            errorText: root.dataset.errorText || 'Error loading PDF.',
            title: root.dataset.title || '',
            coverMode: root.classList.contains('cpdfv-has-cover'),
            hasCoverImage: !!root.querySelector('.cpdfv-cover-image'),
            hasThumbs: !!root.querySelector('.cpdfv-sidebar'),
            proTipText: root.dataset.proTip !== undefined ? root.dataset.proTip : ''
        };

        // Setup Viewer State
        var S = {
            pdf: null,
            page: 1,
            total: 0,
            scale: 1.2,
            sidebarOpen: false,
            isFs: false,
            currentTheme: root.classList.contains('cpdfv-dark') ? 'dark' : 'light',
            userZoomed: false,
            searchResults: [],
            searchIdx: -1,
            searchQuery: '',
            viewerActivated: !CFG.coverMode,
            fitMode: (CFG.defaultZoom === 'fit_height' ? 'height' : 'width'),
            scrollObserver: null
        };

        // DOM Element Helper
        function el(n) { return root.querySelector('[data-el="' + n + '"]'); }

        var canvasWrap = el('canvasWrap'),
            sidebar = el('sidebar'),
            thumbsCont = el('thumbs'),
            loader = el('loader'),
            loaderText = el('loaderText'),
            errorEl = el('error'),
            errorMsg = el('errorMsg'),
            pageInput = el('pageInput'),
            pageTotal = el('pageTotal'),
            zoomDisp = el('zoomDisp'),
            searchBar = el('searchBar'),
            searchInp = el('searchInput'),
            searchInfo = el('searchInfo'),
            cover = el('cover'),
            coverCanvas = el('coverCanvas'),
            coverMeta = el('coverMeta');

        // Hide the original fallback canvas immediately
        var originalCanvas = el('canvas');
        if (originalCanvas) originalCanvas.style.display = 'none';

        async function loadPDF() {
            if (errorEl) errorEl.classList.remove('active');
            if (loader) {
                if (loaderText) loaderText.textContent = CFG.loadingText;
                loader.classList.add('active');
            }

            try {
                S.pdf = await pdfjsLib.getDocument(CFG.url).promise;
                S.total = S.pdf.numPages;
                S.page = Math.min(CFG.startPage, S.total);

                if (pageTotal) pageTotal.textContent = '/ ' + S.total;
                var sp = el('statusPages');
                if (sp) sp.textContent = S.total + ' pages';
                var si = el('statusInfo');
                if (si) si.textContent = CFG.title || 'Loaded';

                if (CFG.coverMode && !S.viewerActivated) {
                    if (loader) loader.classList.remove('active');
                    if (CFG.hasCoverImage) {
                        if (coverMeta) coverMeta.textContent = S.total + ' pages ready to open';
                    } else {
                        await renderCover();
                    }
                } else {
                    await activateViewer();
                }
            } catch (e) {
                console.error("PDF Load Error: ", e);
                if (loader) loader.classList.remove('active');
                if (errorEl) {
                    errorEl.classList.add('active');
                    if (errorMsg) errorMsg.textContent = CFG.errorText + ' (' + (e.message || e) + ')';
                }
                if (canvasWrap) canvasWrap.style.display = 'none';
            }
        }

        function renderDocument() {
            canvasWrap.classList.add('cpdfv-continuous-wrap');
            
            // Clear old canvases
            var oldCanvases = canvasWrap.querySelectorAll('.cpdfv-cont-page');
            oldCanvases.forEach(function(c) { c.remove(); });
            
            if (loader) loader.style.display = 'flex';

            var dpr = window.devicePixelRatio || 1;
            var renderQueue = [];

            // 1. Instantly construct the DOM layout so the user can scroll immediately
            for (var i = 1; i <= S.pdf.numPages; i++) {
                var pageCanvas = document.createElement('canvas');
                pageCanvas.className = 'cpdfv-cont-page';
                pageCanvas.setAttribute('data-page-num', i);
                canvasWrap.appendChild(pageCanvas);
                renderQueue.push({ pageNum: i, canvas: pageCanvas });

                // Set up the smart scroll observer
                if (!S.scrollObserver) {
                    S.scrollObserver = new IntersectionObserver(function(entries) {
                        entries.forEach(function(entry) {
                            if (entry.isIntersecting) {
                                var visiblePageNum = parseInt(entry.target.getAttribute('data-page-num'));
                                S.page = visiblePageNum;
                                if (pageInput) pageInput.value = visiblePageNum;
                                if (thumbsCont) {
                                    thumbsCont.querySelectorAll('.cpdfv-thumb-item').forEach(function(el, idx) {
                                        el.classList.toggle('active', idx + 1 === visiblePageNum);
                                    });
                                }
                            }
                        });
                    }, { root: canvasWrap, rootMargin: '-40% 0px -40% 0px', threshold: 0 });
                }
                S.scrollObserver.observe(pageCanvas);
            }

            // 2. Process rendering sequentially to prevent browser lag
            function processQueue() {
                if (renderQueue.length === 0) {
                    if (loader) loader.style.display = 'none';
                    return;
                }
                var task = renderQueue.shift();
                
                S.pdf.getPage(task.pageNum).then(function(page) {
                    var viewport = page.getViewport({ scale: S.scale * dpr });
                    var ctx = task.canvas.getContext('2d');
                    
                    task.canvas.height = viewport.height;
                    task.canvas.width = viewport.width;
                    task.canvas.style.width = (viewport.width / dpr) + 'px';
                    task.canvas.style.height = (viewport.height / dpr) + 'px';

                    page.render({ canvasContext: ctx, viewport: viewport }).promise.then(function() {
                        // Apply Search Highlights
                        if (S.searchQuery && S.searchResults.length) {
                            var activeRes = S.searchIdx >= 0 ? S.searchResults[S.searchIdx] : null;
                            var pm = S.searchResults.filter(function(r) { return r.pageNum === task.pageNum; });
                            if (pm.length) {
                                var sc = S.scale * dpr;
                                pm.forEach(function(m) {
                                    var ia = activeRes && activeRes === m;
                                    ctx.save();
                                    ctx.fillStyle = ia ? 'rgba(255,160,0,0.45)' : 'rgba(255,220,0,0.3)';
                                    ctx.strokeStyle = ia ? 'rgba(255,120,0,0.8)' : 'rgba(200,180,0,0.5)';
                                    ctx.lineWidth = ia ? 2 : 1;
                                    m.rects.forEach(function(r) {
                                        ctx.fillRect(r.x * sc, r.y * sc, r.w * sc, r.h * sc);
                                        ctx.strokeRect(r.x * sc, r.y * sc, r.w * sc, r.h * sc);
                                    });
                                    ctx.restore();
                                });
                            }
                        }
                        processQueue();
                    });
                });
            }
            
            processQueue();
        }

        async function fitWidth() {
            if (!S.pdf) return;
            var page = await S.pdf.getPage(S.page);
            var vp = page.getViewport({ scale: 1 });
            var w = Math.max(120, (canvasWrap ? canvasWrap.clientWidth : 800) - 48);
            S.scale = Math.max(CFG.minZoom, Math.min(w / vp.width, CFG.maxZoom));
            S.fitMode = 'width';
            if (zoomDisp) zoomDisp.textContent = Math.round(S.scale * 100) + '%';
            renderDocument();
        }

        async function fitHeight() {
            if (!S.pdf) return;
            var page = await S.pdf.getPage(S.page);
            var vp = page.getViewport({ scale: 1 });
            var h = Math.max(120, (canvasWrap ? canvasWrap.clientHeight : 600) - 48);
            S.scale = Math.max(CFG.minZoom, Math.min(h / vp.height, CFG.maxZoom));
            S.fitMode = 'height';
            if (zoomDisp) zoomDisp.textContent = Math.round(S.scale * 100) + '%';
            renderDocument();
        }

        function setZoom(z) {
            S.scale = Math.max(CFG.minZoom, Math.min(z, CFG.maxZoom));
            S.userZoomed = true;
            if (zoomDisp) zoomDisp.textContent = Math.round(S.scale * 100) + '%';
            if (S.pdf) renderDocument();
        }

        async function renderCover() {
            if (!coverCanvas || !S.pdf) return;
            var page = await S.pdf.getPage(1);
            var dpr = window.devicePixelRatio || 1;
            var base = page.getViewport({ scale: 1 });
            var maxW = Math.max(180, (coverCanvas.parentElement ? coverCanvas.parentElement.clientWidth : 320) - 8);
            var maxH = Math.max(220, (cover ? cover.clientHeight : 420) - 80);
            var scale = Math.min(maxW / base.width, maxH / base.height);
            var vp = page.getViewport({ scale: scale * dpr });

            coverCanvas.width = vp.width;
            coverCanvas.height = vp.height;
            coverCanvas.style.width = (vp.width / dpr) + 'px';
            coverCanvas.style.height = (vp.height / dpr) + 'px';
            await page.render({ canvasContext: coverCanvas.getContext('2d'), viewport: vp }).promise;

            if (coverMeta) coverMeta.textContent = S.total + ' page' + (S.total !== 1 ? 's' : '') + ' ready to open';
        }

        async function renderThumbs() {
            if (!thumbsCont || !CFG.hasThumbs) return;
            thumbsCont.innerHTML = '';
            
            for (var i = 1; i <= S.total; i++) {
                var page = await S.pdf.getPage(i);
                var vp = page.getViewport({ scale: 0.35 });
                var item = document.createElement('div');
                item.className = 'cpdfv-thumb-item' + (i === S.page ? ' active' : '');
                
                var tc = document.createElement('canvas');
                tc.width = vp.width;
                tc.height = vp.height;
                await page.render({ canvasContext: tc.getContext('2d'), viewport: vp }).promise;
                
                var label = document.createElement('span');
                label.className = 'cpdfv-thumb-label';
                label.textContent = i;
                
                item.appendChild(tc);
                item.appendChild(label);
                
                (function(n) {
                    item.addEventListener('click', function() {
                        var targetCanvas = canvasWrap.querySelectorAll('.cpdfv-cont-page')[n - 1]; 
                        if (targetCanvas) targetCanvas.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    });
                })(i);
                
                thumbsCont.appendChild(item);
            }
        }

        async function activateViewer() {
            S.viewerActivated = true;
            root.classList.remove('cpdfv-has-cover');
            if (cover) cover.style.display = 'none';

            if (CFG.defaultZoom === 'fit' || CFG.defaultZoom === 'fit_width') {
                await fitWidth();
            } else if (CFG.defaultZoom === 'fit_height') {
                await fitHeight();
            } else {
                setZoom(parseFloat(CFG.defaultZoom) || 1.2);
            }
            
            renderThumbs();

            // Initial scroll to start page if not page 1
            if (S.page > 1) {
                setTimeout(function() {
                    var startCanvas = canvasWrap.querySelectorAll('.cpdfv-cont-page')[S.page - 1];
                    if (startCanvas) startCanvas.scrollIntoView({ block: 'start' });
                }, 100);
            }
        }

        // --- SEARCH FUNCTIONS ---

        async function doSearch(q) {
            S.searchQuery = q.toLowerCase();
            S.searchResults = [];
            S.searchIdx = -1;

            if (!q || !S.pdf) {
                updateSearchInfo();
                return;
            }

            if (searchInfo) searchInfo.textContent = 'Searching...';

            for (var i = 1; i <= S.total; i++) {
                var page = await S.pdf.getPage(i);
                var txt = await page.getTextContent();
                var vp = page.getViewport({ scale: 1 });

                txt.items.forEach(function(item) {
                    var str = item.str.toLowerCase();
                    var idx = 0;
                    while ((idx = str.indexOf(q, idx)) !== -1) {
                        var tx = item.transform;
                        var x = tx[4], y = tx[5];
                        var fs = Math.sqrt(tx[0] * tx[0] + tx[1] * tx[1]);
                        var cw = item.width / item.str.length;
                        
                        S.searchResults.push({
                            pageNum: i,
                            rects: [{ x: x + idx * cw, y: vp.height - y - fs * 0.85, w: q.length * cw, h: fs * 1.2 }]
                        });
                        idx += q.length;
                    }
                });
            }
            if (S.searchResults.length) {
                S.searchIdx = 0;
                renderDocument(); // Redraws to show highlights

                // Immediately scroll to the exact pixel location of the first result
                var targetCanvas = canvasWrap.querySelectorAll('.cpdfv-cont-page')[S.searchResults[0].pageNum - 1];
                var firstRect = S.searchResults[0].rects[0];

                if (targetCanvas && firstRect) {
                    // Use a slight timeout to ensure the DOM is ready after the redraw
                    setTimeout(function() {
                        var exactY = targetCanvas.offsetTop + (firstRect.y * S.scale) - (canvasWrap.clientHeight / 2);
                        canvasWrap.scrollTo({ top: Math.max(0, exactY), behavior: 'smooth' });
                    }, 50);
                }
            } else {
                renderDocument(); // Redraws to clear highlights
            }
            updateSearchInfo();
        }

        function updateSearchInfo() {
            if (!searchInfo) return;

            // Find the specific navigation buttons inside the search bar
            var prevBtn = searchBar ? searchBar.querySelector('[data-action="searchPrev"]') : null;
            var nextBtn = searchBar ? searchBar.querySelector('[data-action="searchNext"]') : null;

            if (!S.searchResults.length && S.searchQuery) {
                searchInfo.textContent = 'No results';
                if (prevBtn) prevBtn.disabled = true;
                if (nextBtn) nextBtn.disabled = true;
            } else if (S.searchResults.length) {
                searchInfo.textContent = (S.searchIdx + 1) + ' / ' + S.searchResults.length;
    
                // Disable Prev if we are on the very first result (index 0)
                if (prevBtn) prevBtn.disabled = (S.searchIdx === 0);
    
                // Disable Next if we are on the very last result
                if (nextBtn) nextBtn.disabled = (S.searchIdx === S.searchResults.length - 1);
            } else {
                searchInfo.textContent = '';
                if (prevBtn) prevBtn.disabled = true;
                if (nextBtn) nextBtn.disabled = true;
            }
        }

        function searchNav(dir) {
            if (!S.searchResults.length) return;

            // Calculate the next index without wrapping
            var nextIdx = S.searchIdx + dir;
            if (nextIdx < 0 || nextIdx >= S.searchResults.length) {
                return; // Stop at the beginning or the end!
            }

            S.searchIdx = nextIdx;
            var r = S.searchResults[S.searchIdx];

            var targetCanvas = canvasWrap.querySelectorAll('.cpdfv-cont-page')[r.pageNum - 1];
            if (targetCanvas && r.rects && r.rects[0]) {
                // Calculate the exact pixel location of the highlighted word
                var exactY = targetCanvas.offsetTop + (r.rects[0].y * S.scale) - (canvasWrap.clientHeight / 2);
    
                canvasWrap.scrollTo({
                    top: Math.max(0, exactY),
                    behavior: 'smooth'
                });
            }

            updateSearchInfo();
            renderDocument(); // Updates the orange "active" highlight color
        }

        function clearSearch() {
            S.searchQuery = '';
            S.searchResults = [];
            S.searchIdx = -1;
            if (searchInp) searchInp.value = '';
            updateSearchInfo();
            renderDocument();
        }

        // --- EVENT LISTENERS ---

        root.addEventListener('click', function(e) {
            var b = e.target.closest('[data-action]');
            if (!b) return;
            var a = b.getAttribute('data-action');

            switch (a) {
                case 'prev': 
                    if (S.page > 1) {
                        var prevCanvas = canvasWrap.querySelectorAll('.cpdfv-cont-page')[S.page - 2];
                        if (prevCanvas) {
                            S.page--; // Instantly update internal state
                            if (pageInput) pageInput.value = S.page; // Instantly update toolbar
                            prevCanvas.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        }
                    }
                    break;
                case 'next': 
                    if (S.page < S.total) {
                        var nextCanvas = canvasWrap.querySelectorAll('.cpdfv-cont-page')[S.page];
                        if (nextCanvas) {
                            S.page++; // Instantly update internal state
                            if (pageInput) pageInput.value = S.page; // Instantly update toolbar
                            nextCanvas.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        }
                    }
                    break;
                case 'zoomIn': setZoom(S.scale + CFG.zoomStep); break;
                case 'zoomOut': setZoom(S.scale - CFG.zoomStep); break;
                case 'fitWidth': S.userZoomed = false; fitWidth(); break;
                case 'fitHeight': S.userZoomed = false; fitHeight(); break;
                case 'openViewer': activateViewer(); break;
                case 'sidebar':
                    S.sidebarOpen = !S.sidebarOpen;
                    if (sidebar) sidebar.classList.toggle('open', S.sidebarOpen);
                    b.classList.toggle('active', S.sidebarOpen);
                    break;
                case 'search':
                    if (searchBar) {
                        searchBar.classList.toggle('open');
                        if (searchBar.classList.contains('open') && searchInp) searchInp.focus();
                    }
                    break;
                case 'searchClose':
                    if (searchBar) searchBar.classList.remove('open');
                    clearSearch();
                    break;
                case 'searchPrev': searchNav(-1); break;
                case 'searchNext': searchNav(1); break;
                case 'themeToggle':
                    S.currentTheme = S.currentTheme === 'dark' ? 'light' : 'dark';
                    root.classList.toggle('cpdfv-light', S.currentTheme === 'light');
                    root.classList.toggle('cpdfv-dark', S.currentTheme === 'dark');
                    break;
                case 'print':
                    if (CFG.url) {
                        var w = window.open(CFG.url);
                        if (w) setTimeout(function() { w.print(); }, 1000);
                    }
                    break;
                case 'download':
                    if (CFG.url) {
                        var a2 = document.createElement('a');
                        a2.href = CFG.url;
                        a2.download = (CFG.title || 'document') + '.pdf';
                        a2.target = '_blank';
                        a2.click();
                    }
                    break;
                case 'fullscreen':
                    // 1. Toggle State
                    S.isFs = !S.isFs;

                    if (S.isFs) {
                        // Find the sidebar specifically to check its class
                        var currentSidebar = root.querySelector('[data-el="sidebar"]');
            if (currentSidebar && currentSidebar.classList.contains('open')) {
                var thumbBtn = root.querySelector('[data-action="sidebar"]');
            if (thumbBtn) {
                                thumbBtn.click();
                }
                        } 
                        if (root.requestFullscreen) root.requestFullscreen();
                        else if (root.webkitRequestFullscreen) root.webkitRequestFullscreen();

                        // Start Watchdog for 'Esc' key
                        if (window.pvWatchdog) clearInterval(window.pvWatchdog);
                        window.pvWatchdog = setInterval(function() {
                            if (!document.fullscreenElement && !document.webkitIsFullScreen) {
            
                                S.isFs = false;
                                root.classList.remove('fullscreen');
                                b.classList.remove('active'); // b is the button from your click listener
            
                                if (S.fitMode === 'height') fitHeight(); else fitWidth();
                                window.dispatchEvent(new Event('resize'));
            
                                clearInterval(window.pvWatchdog);
                            }
                        }, 500);
                    } else {
                        // EXITING MANUALLY
                        if (window.pvWatchdog) clearInterval(window.pvWatchdog);
    
                        if (document.fullscreenElement || document.webkitFullscreenElement) {
                            if (document.exitFullscreen) document.exitFullscreen();
                            else if (document.webkitExitFullscreen) document.webkitExitFullscreen();
                        }
                    }

                    // 2. Update CSS classes
                    root.classList.toggle('fullscreen', S.isFs);
                    b.classList.toggle('active', S.isFs);

                    // 3. Layout Refresh
                    setTimeout(function() {
                        if (S.fitMode === 'height') fitHeight(); else fitWidth();
                        window.dispatchEvent(new Event('resize'));
                    }, 400);
                    break;
                case 'thumbs':
                    // 1. Toggle the internal state
                    S.sidebarOpen = !S.sidebarOpen;

                    // 2. Update the sidebar element using your pre-defined 'sidebar' variable
                    if (sidebar) {
                        sidebar.classList.toggle('open', S.sidebarOpen);
                    }

                    // 3. Update the button's active state
                    b.classList.toggle('active', S.sidebarOpen);

                    // 4. Adjust the main viewer margin
                    var mainCont = root.querySelector('.cpdfv-main');
                    if (mainCont) {
                        mainCont.style.marginLeft = S.sidebarOpen ? '200px' : '0';
                    }
                    break;
                case 'retry':
                    loadPDF();
                    break;
            }
        });

        if (pageInput) {
            pageInput.addEventListener('change', function(e) {
                var n = parseInt(pageInput.value);
                if (isNaN(n) || n < 1 || n > S.pdf.numPages) {
                    pageInput.value = S.page; 
                    return;
                }
                var targetCanvas = canvasWrap.querySelectorAll('.cpdfv-cont-page')[n - 1];
                if (targetCanvas) targetCanvas.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        }

        if (searchInp) {
            searchInp.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && S.pdf) {
                    var val = searchInp.value.trim();
                    // If the query is the same, just jump to the next result
                    if (val.toLowerCase() === S.searchQuery && S.searchResults.length) {
                        searchNav(1);
                    } else {
                        doSearch(val);
                    }
                }
            });
        }

        // --- INITIALIZE ---
        //
        // THE TAB FIX: Watch for visibility changes to fix the 30% zoom bug
        if (window.ResizeObserver) {
            const tabObserver = new ResizeObserver(function(entries) {
                for (const entry of entries) {
                    // If the width is now > 0, the GenerateBlocks Tab has been opened
                    if (entry.contentRect.width > 0) {
                        // 100ms delay ensures the tab opening transition is fully complete
                        setTimeout(function() {
                            if (typeof fitWidth === 'function') {
                                fitWidth(root);
                            }
                        }, 100);
                        
                        // Stop watching this specific viewer to save performance
                        tabObserver.unobserve(root);
                    }
                }
            });
            
            // Start monitoring the specific .cpdfv-root element
            tabObserver.observe(root);
        }
        // --- DYNAMIC PRO TIP TEXT & VISIBILITY ---
        var proTipContainer = root.querySelector('.cpdfv-viewer-tip');
        var proTipTextSpan = root.querySelector('.cpdfv-tip-text');

        if (proTipContainer && proTipTextSpan) {
            // 1. Check if the WordPress setting is completely empty
            if (CFG.proTipText.trim() === '') {
                // Hide the entire element (including the lightbulb icon)
                proTipContainer.style.display = 'none';
            } else {
                // 2. Inject the HTML string into the text span
                proTipTextSpan.innerHTML = CFG.proTipText;
                proTipContainer.style.display = ''; // Ensure it remains visible

                // 3. Attach the Fullscreen listener to the newly injected link
                root.querySelectorAll('.cpdfv-tip-link').forEach(function(link) {
                    link.addEventListener('click', function(e) {
                        e.preventDefault();
                        if (!S.isFs) {
                            var fsBtn = root.querySelector('[data-action="fullscreen"]');
                            if (fsBtn) {
                                fsBtn.dispatchEvent(new Event('click', { bubbles: true }));
                            }
                        }
                    });
                });
            }
        }

        // Kick off the initial load
        loadPDF();
    });
}

// 3. Module Execution
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
} else {
    boot();
}
