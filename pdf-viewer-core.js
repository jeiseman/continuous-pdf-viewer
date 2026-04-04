(function() {
    // 1. Initialize PDF.js from CDN
    if (!window._cpvPdfJs) {
        window._cpvPdfJs = true;
        var s = document.createElement('script');
        s.src = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js';
        s.onload = function() {
            pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
            document.dispatchEvent(new Event('cpv:ready'));
        };
        document.head.appendChild(s);
    }

    // 2. The main initialization loop
    function boot() {
        var roots = document.querySelectorAll('.pv-root');
        
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
                coverMode: root.classList.contains('pv-has-cover'),
                hasCoverImage: !!root.querySelector('.pv-cover-image'),
                hasThumbs: !!root.querySelector('.pv-sidebar')
            };

            // Setup Viewer State
            var S = {
                pdf: null,
                page: 1,
                total: 0,
                scale: 1.2,
                rendering: false,
                sidebarOpen: false,
                isFs: false,
                currentTheme: root.classList.contains('pv-dark') ? 'dark' : 'light',
                userZoomed: false,
                searchResults: [],
                searchIdx: -1,
                searchQuery: '',
                viewerActivated: !CFG.coverMode,
                fitMode: (CFG.defaultZoom === 'fit_height' ? 'height' : 'width')
            };

            // DOM Element Helper
            function el(n) { return root.querySelector('[data-el="' + n + '"]'); }

            var canvas = el('canvas'),
                ctx = canvas ? canvas.getContext('2d') : null,
                canvasWrap = el('canvasWrap'),
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

            // --- CORE FUNCTIONS ---

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

                    if (loader) loader.classList.remove('active');

                    if (CFG.coverMode && !S.viewerActivated) {
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

            async function renderPage(num) {
                if (!S.pdf || S.rendering) return;
                S.rendering = true;
                S.page = Math.max(1, Math.min(num, S.total));

                if (pageInput) pageInput.value = S.page;

                var page = await S.pdf.getPage(S.page);
                var dpr = window.devicePixelRatio || 1;
                var vp = page.getViewport({ scale: S.scale * dpr });

                canvas.width = vp.width;
                canvas.height = vp.height;
                canvas.style.width = (vp.width / dpr) + 'px';
                canvas.style.height = (vp.height / dpr) + 'px';

                await page.render({ canvasContext: ctx, viewport: vp }).promise;

                // Handle Search Highlighting on Canvas
                if (S.searchQuery && S.searchResults.length) {
                    var activeRes = S.searchIdx >= 0 ? S.searchResults[S.searchIdx] : null;
                    var pm = S.searchResults.filter(function(r) { return r.pageNum === S.page; });
                    
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

                        // Scroll to active result
                        if (activeRes && activeRes.pageNum === S.page && activeRes.rects[0] && canvasWrap) {
                            var r = activeRes.rects[0];
                            canvasWrap.scrollTo({
                                left: Math.max(0, canvas.offsetLeft + r.x * S.scale - canvasWrap.clientWidth / 2),
                                top: Math.max(0, canvas.offsetTop + r.y * S.scale - canvasWrap.clientHeight / 3),
                                behavior: 'smooth'
                            });
                        }
                    }
                }

                S.rendering = false;

                // Sync Thumbnails
                if (thumbsCont) {
                    thumbsCont.querySelectorAll('.pv-thumb-item').forEach(function(el, i) {
                        el.classList.toggle('active', i + 1 === S.page);
                    });
                }
            }

            async function fitWidth() {
                if (!S.pdf) return;
                var page = await S.pdf.getPage(S.page);
                var vp = page.getViewport({ scale: 1 });
                var w = Math.max(120, (canvasWrap ? canvasWrap.clientWidth : 800) - 48);
                S.scale = Math.max(CFG.minZoom, Math.min(w / vp.width, CFG.maxZoom));
                S.fitMode = 'width';
                if (zoomDisp) zoomDisp.textContent = Math.round(S.scale * 100) + '%';
                return renderPage(S.page);
            }

            async function fitHeight() {
                if (!S.pdf) return;
                var page = await S.pdf.getPage(S.page);
                var vp = page.getViewport({ scale: 1 });
                var h = Math.max(120, (canvasWrap ? canvasWrap.clientHeight : 600) - 48);
                S.scale = Math.max(CFG.minZoom, Math.min(h / vp.height, CFG.maxZoom));
                S.fitMode = 'height';
                if (zoomDisp) zoomDisp.textContent = Math.round(S.scale * 100) + '%';
                return renderPage(S.page);
            }

            function setZoom(z) {
                S.scale = Math.max(CFG.minZoom, Math.min(z, CFG.maxZoom));
                S.userZoomed = true;
                if (zoomDisp) zoomDisp.textContent = Math.round(S.scale * 100) + '%';
                if (S.pdf) renderPage(S.page);
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
                    item.className = 'pv-thumb-item' + (i === S.page ? ' active' : '');
                    
                    var tc = document.createElement('canvas');
                    tc.width = vp.width;
                    tc.height = vp.height;
                    await page.render({ canvasContext: tc.getContext('2d'), viewport: vp }).promise;
                    
                    var label = document.createElement('span');
                    label.className = 'pv-thumb-label';
                    label.textContent = i;
                    
                    item.appendChild(tc);
                    item.appendChild(label);
                    
                    (function(n) {
                        item.addEventListener('click', function() { renderPage(n); });
                    })(i);
                    
                    thumbsCont.appendChild(item);
                }
            }

            async function activateViewer() {
                S.viewerActivated = true;
                root.classList.remove('pv-has-cover');
                if (cover) cover.style.display = 'none';

                if (CFG.defaultZoom === 'fit' || CFG.defaultZoom === 'fit_width') {
                    await fitWidth();
                } else if (CFG.defaultZoom === 'fit_height') {
                    await fitHeight();
                } else {
                    setZoom(parseFloat(CFG.defaultZoom) || 1.2);
                }
                renderThumbs();
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
                    await renderPage(S.searchResults[0].pageNum);
                } else {
                    await renderPage(S.page); // Redraw to clear highlights
                }
                updateSearchInfo();
            }

            function updateSearchInfo() {
                if (!searchInfo) return;
                if (!S.searchResults.length && S.searchQuery) {
                    searchInfo.textContent = 'No results';
                } else if (S.searchResults.length) {
                    searchInfo.textContent = (S.searchIdx + 1) + ' / ' + S.searchResults.length;
                } else {
                    searchInfo.textContent = '';
                }
            }

            async function searchNav(dir) {
                if (!S.searchResults.length) return;
                S.searchIdx = (S.searchIdx + dir + S.searchResults.length) % S.searchResults.length;
                var r = S.searchResults[S.searchIdx];
                if (r.pageNum !== S.page) {
                    await renderPage(r.pageNum);
                } else {
                    await renderPage(S.page);
                }
                updateSearchInfo();
            }

            function clearSearch() {
                S.searchQuery = '';
                S.searchResults = [];
                S.searchIdx = -1;
                if (searchInp) searchInp.value = '';
                updateSearchInfo();
                renderPage(S.page);
            }

            // --- EVENT LISTENERS ---

            root.addEventListener('click', function(e) {
                var b = e.target.closest('[data-action]');
                if (!b) return;
                var a = b.getAttribute('data-action');

                switch (a) {
                    case 'prev': renderPage(S.page - 1); break;
                    case 'next': renderPage(S.page + 1); break;
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
                        root.classList.toggle('pv-light', S.currentTheme === 'light');
                        root.classList.toggle('pv-dark', S.currentTheme === 'dark');
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
                        S.isFs = !S.isFs;
                        S.userZoomed = false;
                        root.classList.toggle('fullscreen', S.isFs);
                        setTimeout(function() {
                            if (CFG.defaultZoom === 'fit_height' || S.fitMode === 'height') fitHeight();
                            else fitWidth();
                        }, 400);
                        break;
                    case 'retry':
                        loadPDF();
                        break;
                }
            });

            if (pageInput) {
                pageInput.addEventListener('change', function(e) {
                    var n = parseInt(pageInput.value);
                    if (!isNaN(n)) renderPage(n);
                });
            }

            if (searchInp) {
                searchInp.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' && S.pdf) doSearch(searchInp.value.trim());
                });
            }

            // --- INITIALIZE ---
            loadPDF();
        });
    }

    if (window.pdfjsLib) { 
        boot(); 
    } else { 
        document.addEventListener('cpv:ready', boot); 
    }
})();
