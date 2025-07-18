/**
 * Universal Zoom Modal Component
 * Provides zoom functionality for all preview popups and modals
 */

class ZoomModal {
    constructor() {
        this.currentZoom = 1;
        this.minZoom = 0.1;
        this.maxZoom = 5;
        this.zoomStep = 0.2;
        this.isDragging = false;
        this.startX = 0;
        this.startY = 0;
        this.translateX = 0;
        this.translateY = 0;
        this.currentElement = null;
        
        this.createZoomModal();
        this.bindEvents();
    }
    
    createZoomModal() {
        // Create zoom modal HTML
        const zoomModalHTML = `
            <div id="universalZoomModal" class="zoom-modal" style="display: none;">
                <div class="zoom-modal-overlay"></div>
                <div class="zoom-modal-container">
                    <div class="zoom-modal-header">
                        <div class="zoom-modal-title" id="zoomModalTitle">Preview</div>
                        <div class="zoom-modal-controls">
                            <button class="zoom-btn" id="zoomOut" title="Zoom Out">−</button>
                            <span class="zoom-level" id="zoomLevel">100%</span>
                            <button class="zoom-btn" id="zoomIn" title="Zoom In">+</button>
                            <button class="zoom-btn" id="zoomReset" title="Reset Zoom">⌂</button>
                            <button class="zoom-btn" id="zoomFit" title="Fit to Screen">⊞</button>
                            <button class="zoom-btn zoom-close" id="zoomClose" title="Close">×</button>
                        </div>
                    </div>
                    <div class="zoom-modal-content" id="zoomModalContent">
                        <div class="zoom-container" id="zoomContainer">
                            <div class="zoom-content" id="zoomContent"></div>
                        </div>
                    </div>
                    <div class="zoom-modal-footer">
                        <div class="zoom-instructions">
                            <span>🖱️ Drag to pan • 🔍 Scroll to zoom • ⌨️ ESC to close</span>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Add to document
        document.body.insertAdjacentHTML('beforeend', zoomModalHTML);
        
        // Add CSS styles
        this.addZoomStyles();
    }
    
    addZoomStyles() {
        const style = document.createElement('style');
        style.textContent = `
            .zoom-modal {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                z-index: 10000;
                display: flex;
                align-items: center;
                justify-content: center;
                animation: zoomModalFadeIn 0.3s ease-out;
            }
            
            .zoom-modal-overlay {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.95);
                backdrop-filter: blur(5px);
            }
            
            .zoom-modal-container {
                position: relative;
                width: 95%;
                height: 95%;
                max-width: 1400px;
                max-height: 900px;
                background: #fff;
                border-radius: 12px;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
                display: flex;
                flex-direction: column;
                overflow: hidden;
            }
            
            .zoom-modal-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 1rem 1.5rem;
                background: linear-gradient(135deg, #667eea, #764ba2);
                color: white;
                border-bottom: 1px solid #e9ecef;
            }
            
            .zoom-modal-title {
                font-size: 1.2rem;
                font-weight: 600;
                margin: 0;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
                max-width: 50%;
            }
            
            .zoom-modal-controls {
                display: flex;
                align-items: center;
                gap: 0.5rem;
            }
            
            .zoom-btn {
                width: 36px;
                height: 36px;
                border: none;
                border-radius: 6px;
                background: rgba(255, 255, 255, 0.2);
                color: white;
                font-size: 1.2rem;
                font-weight: bold;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: all 0.2s ease;
                backdrop-filter: blur(10px);
            }
            
            .zoom-btn:hover {
                background: rgba(255, 255, 255, 0.3);
                transform: scale(1.05);
            }
            
            .zoom-btn:active {
                transform: scale(0.95);
            }
            
            .zoom-close {
                background: rgba(220, 53, 69, 0.8);
                font-size: 1.5rem;
            }
            
            .zoom-close:hover {
                background: rgba(220, 53, 69, 1);
            }
            
            .zoom-level {
                background: rgba(255, 255, 255, 0.2);
                padding: 0.5rem 0.75rem;
                border-radius: 6px;
                font-size: 0.9rem;
                font-weight: 600;
                min-width: 60px;
                text-align: center;
                backdrop-filter: blur(10px);
            }
            
            .zoom-modal-content {
                flex: 1;
                position: relative;
                overflow: hidden;
                background: #f8f9fa;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            .zoom-container {
                width: 100%;
                height: 100%;
                overflow: hidden;
                position: relative;
                cursor: grab;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            .zoom-container.dragging {
                cursor: grabbing;
            }
            
            .zoom-content {
                transition: transform 0.2s ease;
                transform-origin: center center;
                max-width: none;
                max-height: none;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            .zoom-content img {
                max-width: 100%;
                max-height: 100%;
                object-fit: contain;
                border-radius: 8px;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
                user-select: none;
                pointer-events: none;
            }
            
            .zoom-content iframe {
                width: 100%;
                height: 100%;
                border: none;
                border-radius: 8px;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
            }
            
            .zoom-modal-footer {
                padding: 0.75rem 1.5rem;
                background: #f8f9fa;
                border-top: 1px solid #e9ecef;
                text-align: center;
            }
            
            .zoom-instructions {
                font-size: 0.85rem;
                color: #6c757d;
                font-weight: 500;
            }
            
            @keyframes zoomModalFadeIn {
                from {
                    opacity: 0;
                    transform: scale(0.9);
                }
                to {
                    opacity: 1;
                    transform: scale(1);
                }
            }
            
            @media (max-width: 768px) {
                .zoom-modal-container {
                    width: 98%;
                    height: 98%;
                    border-radius: 8px;
                }
                
                .zoom-modal-header {
                    padding: 0.75rem 1rem;
                }
                
                .zoom-modal-title {
                    font-size: 1rem;
                    max-width: 40%;
                }
                
                .zoom-btn {
                    width: 32px;
                    height: 32px;
                    font-size: 1rem;
                }
                
                .zoom-level {
                    font-size: 0.8rem;
                    padding: 0.4rem 0.6rem;
                    min-width: 50px;
                }
                
                .zoom-instructions {
                    font-size: 0.75rem;
                }
            }
        `;
        
        document.head.appendChild(style);
    }
    
    bindEvents() {
        // Zoom controls
        document.getElementById('zoomIn').addEventListener('click', () => this.zoomIn());
        document.getElementById('zoomOut').addEventListener('click', () => this.zoomOut());
        document.getElementById('zoomReset').addEventListener('click', () => this.resetZoom());
        document.getElementById('zoomFit').addEventListener('click', () => this.fitToScreen());
        document.getElementById('zoomClose').addEventListener('click', () => this.close());
        
        // Close on overlay click
        document.querySelector('.zoom-modal-overlay').addEventListener('click', () => this.close());
        
        // Keyboard events
        document.addEventListener('keydown', (e) => this.handleKeyboard(e));
        
        // Mouse wheel zoom
        document.getElementById('zoomContainer').addEventListener('wheel', (e) => this.handleWheel(e));
        
        // Pan functionality
        const container = document.getElementById('zoomContainer');
        container.addEventListener('mousedown', (e) => this.startPan(e));
        container.addEventListener('mousemove', (e) => this.pan(e));
        container.addEventListener('mouseup', () => this.endPan());
        container.addEventListener('mouseleave', () => this.endPan());
        
        // Touch events for mobile
        container.addEventListener('touchstart', (e) => this.startPan(e.touches[0]));
        container.addEventListener('touchmove', (e) => {
            e.preventDefault();
            this.pan(e.touches[0]);
        });
        container.addEventListener('touchend', () => this.endPan());
    }
    
    open(content, title = 'Preview', type = 'image') {
        const modal = document.getElementById('universalZoomModal');
        const titleEl = document.getElementById('zoomModalTitle');
        const contentEl = document.getElementById('zoomContent');
        
        // Reset state
        this.currentZoom = 1;
        this.translateX = 0;
        this.translateY = 0;
        
        // Set title
        titleEl.textContent = title;
        
        // Set content based on type
        if (type === 'image') {
            contentEl.innerHTML = `<img src="${content}" alt="${title}" onload="window.zoomModal.fitToScreen()">`;
        } else if (type === 'pdf') {
            // Use enhanced PDF.js viewer
            const pdfViewerUrl = `${BASE_URL}/index.php?page=pdf_viewer&file=${encodeURIComponent(src.split('/').pop())}&title=${encodeURIComponent(title)}`;
            window.open(pdfViewerUrl, '_blank', 'width=1200,height=800,scrollbars=yes,resizable=yes');
            return;
            contentEl.innerHTML = `<iframe src="${content}" title="${title}"></iframe>`;
        } else {
            contentEl.innerHTML = content;
        }
        
        // Show modal
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        
        // Store current element for reference
        this.currentElement = contentEl.firstElementChild;
        
        // Update zoom level display
        this.updateZoomLevel();
    }
    
    close() {
        const modal = document.getElementById('universalZoomModal');
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
        
        // Reset state
        this.currentZoom = 1;
        this.translateX = 0;
        this.translateY = 0;
        this.currentElement = null;
    }
    
    zoomIn() {
        if (this.currentZoom < this.maxZoom) {
            this.currentZoom = Math.min(this.currentZoom + this.zoomStep, this.maxZoom);
            this.applyTransform();
        }
    }
    
    zoomOut() {
        if (this.currentZoom > this.minZoom) {
            this.currentZoom = Math.max(this.currentZoom - this.zoomStep, this.minZoom);
            this.applyTransform();
        }
    }
    
    resetZoom() {
        this.currentZoom = 1;
        this.translateX = 0;
        this.translateY = 0;
        this.applyTransform();
    }
    
    fitToScreen() {
        if (!this.currentElement) return;
        
        const container = document.getElementById('zoomContainer');
        const containerRect = container.getBoundingClientRect();
        
        if (this.currentElement.tagName === 'IMG') {
            // Wait for image to load
            if (this.currentElement.naturalWidth === 0) {
                this.currentElement.onload = () => this.fitToScreen();
                return;
            }
            
            const imgWidth = this.currentElement.naturalWidth;
            const imgHeight = this.currentElement.naturalHeight;
            
            const scaleX = (containerRect.width - 40) / imgWidth;
            const scaleY = (containerRect.height - 40) / imgHeight;
            
            this.currentZoom = Math.min(scaleX, scaleY, 1);
        } else {
            this.currentZoom = 1;
        }
        
        this.translateX = 0;
        this.translateY = 0;
        this.applyTransform();
    }
    
    applyTransform() {
        if (!this.currentElement) return;
        
        const transform = `translate(${this.translateX}px, ${this.translateY}px) scale(${this.currentZoom})`;
        this.currentElement.style.transform = transform;
        this.updateZoomLevel();
    }
    
    updateZoomLevel() {
        const zoomLevelEl = document.getElementById('zoomLevel');
        zoomLevelEl.textContent = Math.round(this.currentZoom * 100) + '%';
    }
    
    handleKeyboard(e) {
        if (document.getElementById('universalZoomModal').style.display === 'none') return;
        
        switch (e.key) {
            case 'Escape':
                this.close();
                break;
            case '+':
            case '=':
                e.preventDefault();
                this.zoomIn();
                break;
            case '-':
                e.preventDefault();
                this.zoomOut();
                break;
            case '0':
                e.preventDefault();
                this.resetZoom();
                break;
            case 'f':
            case 'F':
                e.preventDefault();
                this.fitToScreen();
                break;
        }
    }
    
    handleWheel(e) {
        e.preventDefault();
        
        const delta = e.deltaY > 0 ? -this.zoomStep : this.zoomStep;
        const newZoom = Math.max(this.minZoom, Math.min(this.maxZoom, this.currentZoom + delta));
        
        if (newZoom !== this.currentZoom) {
            // Zoom towards mouse position
            const rect = e.currentTarget.getBoundingClientRect();
            const mouseX = e.clientX - rect.left - rect.width / 2;
            const mouseY = e.clientY - rect.top - rect.height / 2;
            
            const zoomRatio = newZoom / this.currentZoom;
            this.translateX = mouseX - (mouseX - this.translateX) * zoomRatio;
            this.translateY = mouseY - (mouseY - this.translateY) * zoomRatio;
            
            this.currentZoom = newZoom;
            this.applyTransform();
        }
    }
    
    startPan(e) {
        if (this.currentZoom <= 1) return;
        
        this.isDragging = true;
        this.startX = e.clientX - this.translateX;
        this.startY = e.clientY - this.translateY;
        
        document.getElementById('zoomContainer').classList.add('dragging');
    }
    
    pan(e) {
        if (!this.isDragging) return;
        
        this.translateX = e.clientX - this.startX;
        this.translateY = e.clientY - this.startY;
        
        this.applyTransform();
    }
    
    endPan() {
        this.isDragging = false;
        document.getElementById('zoomContainer').classList.remove('dragging');
    }
}

// Initialize zoom modal when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    window.zoomModal = new ZoomModal();
});

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ZoomModal;
}