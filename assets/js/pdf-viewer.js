/**
 * Enhanced PDF Viewer using PDF.js
 * Provides better PDF viewing experience with controls and features
 */

class PDFViewer {
    constructor(container, options = {}) {
        this.container = container;
        this.options = {
            scale: options.scale || 1.2,
            enableControls: options.enableControls !== false,
            enableSearch: options.enableSearch !== false,
            enableDownload: options.enableDownload !== false,
            ...options
        };
        
        this.pdfDoc = null;
        this.pageNum = 1;
        this.pageRendering = false;
        this.pageNumPending = null;
        this.scale = this.options.scale;
        
        this.init();
    }
    
    init() {
        // Check if PDF.js is available
        if (typeof pdfjsLib === 'undefined') {
            console.error('PDF.js library not loaded');
            this.showError('PDF.js library not available');
            return;
        }
        
        // Set PDF.js worker
        pdfjsLib.GlobalWorkerOptions.workerSrc = BASE_URL + '/assets/js/vendor/pdfjs/build/pdf.worker.min.js';
        
        this.createViewer();
    }
    
    createViewer() {
        this.container.innerHTML = `
            <div class="pdf-viewer-wrapper">
                ${this.options.enableControls ? this.createControls() : ''}
                <div class="pdf-canvas-container">
                    <canvas id="pdf-canvas"></canvas>
                </div>
                <div class="pdf-loading" style="display: none;">
                    <div class="loading-spinner"></div>
                    <p>Loading PDF...</p>
                </div>
                <div class="pdf-error" style="display: none;">
                    <div class="error-icon">⚠️</div>
                    <h3>Unable to load PDF</h3>
                    <p class="error-message"></p>
                </div>
            </div>
        `;
        
        this.canvas = this.container.querySelector('#pdf-canvas');
        this.ctx = this.canvas.getContext('2d');
        this.loadingDiv = this.container.querySelector('.pdf-loading');
        this.errorDiv = this.container.querySelector('.pdf-error');
        
        this.addStyles();
        this.bindEvents();
    }
    
    createControls() {
        return `
            <div class="pdf-controls">
                <div class="pdf-controls-left">
                    <button id="pdf-prev" class="pdf-btn" title="Previous Page">
                        <span>‹</span>
                    </button>
                    <span class="pdf-page-info">
                        Page <span id="pdf-page-num">-</span> of <span id="pdf-page-count">-</span>
                    </span>
                    <button id="pdf-next" class="pdf-btn" title="Next Page">
                        <span>›</span>
                    </button>
                </div>
                <div class="pdf-controls-center">
                    <button id="pdf-zoom-out" class="pdf-btn" title="Zoom Out">-</button>
                    <span class="pdf-zoom-info"><span id="pdf-zoom-level">120</span>%</span>
                    <button id="pdf-zoom-in" class="pdf-btn" title="Zoom In">+</button>
                    <button id="pdf-fit-width" class="pdf-btn" title="Fit Width">⟷</button>
                </div>
                <div class="pdf-controls-right">
                    ${this.options.enableSearch ? `
                        <div class="pdf-search">
                            <input type="text" id="pdf-search-input" placeholder="Search in PDF..." />
                            <button id="pdf-search-btn" class="pdf-btn">🔍</button>
                        </div>
                    ` : ''}
                    ${this.options.enableDownload ? `
                        <button id="pdf-download" class="pdf-btn" title="Download PDF">⬇</button>
                    ` : ''}
                    <button id="pdf-fullscreen" class="pdf-btn" title="Fullscreen">⛶</button>
                </div>
            </div>
        `;
    }
    
    addStyles() {
        if (document.getElementById('pdf-viewer-styles')) return;
        
        const styles = document.createElement('style');
        styles.id = 'pdf-viewer-styles';
        styles.textContent = `
            .pdf-viewer-wrapper {
                border: 1px solid #ddd;
                border-radius: 8px;
                overflow: hidden;
                background: #f8f9fa;
                position: relative;
            }
            
            .pdf-controls {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 0.75rem 1rem;
                background: #fff;
                border-bottom: 1px solid #e9ecef;
                flex-wrap: wrap;
                gap: 1rem;
            }
            
            .pdf-controls-left,
            .pdf-controls-center,
            .pdf-controls-right {
                display: flex;
                align-items: center;
                gap: 0.5rem;
            }
            
            .pdf-btn {
                padding: 0.5rem;
                border: 1px solid #ced4da;
                background: #fff;
                border-radius: 4px;
                cursor: pointer;
                font-size: 0.9rem;
                min-width: 2rem;
                height: 2rem;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: all 0.2s;
            }
            
            .pdf-btn:hover {
                background: #e9ecef;
                border-color: #adb5bd;
            }
            
            .pdf-btn:disabled {
                opacity: 0.5;
                cursor: not-allowed;
            }
            
            .pdf-page-info,
            .pdf-zoom-info {
                font-size: 0.9rem;
                color: #495057;
                white-space: nowrap;
            }
            
            .pdf-search {
                display: flex;
                align-items: center;
                gap: 0.25rem;
            }
            
            .pdf-search input {
                padding: 0.5rem;
                border: 1px solid #ced4da;
                border-radius: 4px;
                font-size: 0.9rem;
                width: 150px;
            }
            
            .pdf-canvas-container {
                text-align: center;
                padding: 1rem;
                background: #e9ecef;
                min-height: 400px;
                overflow: auto;
            }
            
            #pdf-canvas {
                border: 1px solid #adb5bd;
                box-shadow: 0 4px 8px rgba(0,0,0,0.1);
                background: white;
                max-width: 100%;
                height: auto;
            }
            
            .pdf-loading,
            .pdf-error {
                text-align: center;
                padding: 3rem 1rem;
                color: #6c757d;
            }
            
            .loading-spinner {
                width: 40px;
                height: 40px;
                border: 4px solid #e9ecef;
                border-top: 4px solid #007bff;
                border-radius: 50%;
                animation: spin 1s linear infinite;
                margin: 0 auto 1rem;
            }
            
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
            
            .error-icon {
                font-size: 3rem;
                margin-bottom: 1rem;
            }
            
            .pdf-error h3 {
                color: #dc3545;
                margin-bottom: 0.5rem;
            }
            
            @media (max-width: 768px) {
                .pdf-controls {
                    flex-direction: column;
                    gap: 0.5rem;
                }
                
                .pdf-controls-left,
                .pdf-controls-center,
                .pdf-controls-right {
                    justify-content: center;
                }
                
                .pdf-search input {
                    width: 120px;
                }
            }
        `;
        
        document.head.appendChild(styles);
    }
    
    bindEvents() {
        if (!this.options.enableControls) return;
        
        // Navigation
        const prevBtn = this.container.querySelector('#pdf-prev');
        const nextBtn = this.container.querySelector('#pdf-next');
        
        if (prevBtn) prevBtn.addEventListener('click', () => this.onPrevPage());
        if (nextBtn) nextBtn.addEventListener('click', () => this.onNextPage());
        
        // Zoom
        const zoomInBtn = this.container.querySelector('#pdf-zoom-in');
        const zoomOutBtn = this.container.querySelector('#pdf-zoom-out');
        const fitWidthBtn = this.container.querySelector('#pdf-fit-width');
        
        if (zoomInBtn) zoomInBtn.addEventListener('click', () => this.zoomIn());
        if (zoomOutBtn) zoomOutBtn.addEventListener('click', () => this.zoomOut());
        if (fitWidthBtn) fitWidthBtn.addEventListener('click', () => this.fitWidth());
        
        // Search
        if (this.options.enableSearch) {
            const searchBtn = this.container.querySelector('#pdf-search-btn');
            const searchInput = this.container.querySelector('#pdf-search-input');
            
            if (searchBtn) searchBtn.addEventListener('click', () => this.search());
            if (searchInput) {
                searchInput.addEventListener('keypress', (e) => {
                    if (e.key === 'Enter') this.search();
                });
            }
        }
        
        // Download
        if (this.options.enableDownload) {
            const downloadBtn = this.container.querySelector('#pdf-download');
            if (downloadBtn) downloadBtn.addEventListener('click', () => this.download());
        }
        
        // Fullscreen
        const fullscreenBtn = this.container.querySelector('#pdf-fullscreen');
        if (fullscreenBtn) fullscreenBtn.addEventListener('click', () => this.toggleFullscreen());
    }
    
    async loadPDF(url) {
        this.showLoading();
        
        try {
            const loadingTask = pdfjsLib.getDocument(url);
            this.pdfDoc = await loadingTask.promise;
            
            this.updatePageCount();
            this.renderPage(this.pageNum);
            this.hideLoading();
            
        } catch (error) {
            console.error('Error loading PDF:', error);
            this.showError('Failed to load PDF document');
        }
    }
    
    renderPage(num) {
        if (this.pageRendering) {
            this.pageNumPending = num;
            return;
        }
        
        this.pageRendering = true;
        
        this.pdfDoc.getPage(num).then((page) => {
            const viewport = page.getViewport({ scale: this.scale });
            this.canvas.height = viewport.height;
            this.canvas.width = viewport.width;
            
            const renderContext = {
                canvasContext: this.ctx,
                viewport: viewport
            };
            
            const renderTask = page.render(renderContext);
            
            renderTask.promise.then(() => {
                this.pageRendering = false;
                if (this.pageNumPending !== null) {
                    this.renderPage(this.pageNumPending);
                    this.pageNumPending = null;
                }
            });
        });
        
        this.updatePageInfo();
    }
    
    updatePageInfo() {
        const pageNumSpan = this.container.querySelector('#pdf-page-num');
        const zoomSpan = this.container.querySelector('#pdf-zoom-level');
        
        if (pageNumSpan) pageNumSpan.textContent = this.pageNum;
        if (zoomSpan) zoomSpan.textContent = Math.round(this.scale * 100);
        
        this.updateNavigationButtons();
    }
    
    updatePageCount() {
        const pageCountSpan = this.container.querySelector('#pdf-page-count');
        if (pageCountSpan && this.pdfDoc) {
            pageCountSpan.textContent = this.pdfDoc.numPages;
        }
    }
    
    updateNavigationButtons() {
        const prevBtn = this.container.querySelector('#pdf-prev');
        const nextBtn = this.container.querySelector('#pdf-next');
        
        if (prevBtn) prevBtn.disabled = this.pageNum <= 1;
        if (nextBtn) nextBtn.disabled = this.pageNum >= this.pdfDoc.numPages;
    }
    
    onPrevPage() {
        if (this.pageNum <= 1) return;
        this.pageNum--;
        this.renderPage(this.pageNum);
    }
    
    onNextPage() {
        if (this.pageNum >= this.pdfDoc.numPages) return;
        this.pageNum++;
        this.renderPage(this.pageNum);
    }
    
    zoomIn() {
        this.scale = Math.min(this.scale * 1.2, 3.0);
        this.renderPage(this.pageNum);
    }
    
    zoomOut() {
        this.scale = Math.max(this.scale / 1.2, 0.5);
        this.renderPage(this.pageNum);
    }
    
    fitWidth() {
        const containerWidth = this.container.querySelector('.pdf-canvas-container').clientWidth - 40;
        this.pdfDoc.getPage(this.pageNum).then((page) => {
            const viewport = page.getViewport({ scale: 1 });
            this.scale = containerWidth / viewport.width;
            this.renderPage(this.pageNum);
        });
    }
    
    search() {
        const searchInput = this.container.querySelector('#pdf-search-input');
        if (!searchInput) return;
        
        const query = searchInput.value.trim();
        if (!query) return;
        
        // Basic search implementation - can be enhanced
        alert(`Search functionality for "${query}" - This can be enhanced with text layer extraction`);
    }
    
    download() {
        if (this.options.downloadUrl) {
            window.open(this.options.downloadUrl, '_blank');
        }
    }
    
    toggleFullscreen() {
        if (!document.fullscreenElement) {
            this.container.requestFullscreen?.() || 
            this.container.webkitRequestFullscreen?.() || 
            this.container.msRequestFullscreen?.();
        } else {
            document.exitFullscreen?.() || 
            document.webkitExitFullscreen?.() || 
            document.msExitFullscreen?.();
        }
    }
    
    showLoading() {
        this.loadingDiv.style.display = 'block';
        this.errorDiv.style.display = 'none';
        this.canvas.style.display = 'none';
    }
    
    hideLoading() {
        this.loadingDiv.style.display = 'none';
        this.canvas.style.display = 'block';
    }
    
    showError(message) {
        this.errorDiv.style.display = 'block';
        this.errorDiv.querySelector('.error-message').textContent = message;
        this.loadingDiv.style.display = 'none';
        this.canvas.style.display = 'none';
    }
}

// Global PDF viewer utility
window.PDFViewer = PDFViewer;