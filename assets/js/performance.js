/**
 * Performance Optimization Module
 * Handles lazy loading, caching, and performance improvements
 */

class PerformanceOptimizer {
    constructor() {
        this.cache = new Map();
        this.imageObserver = null;
        this.init();
    }

    init() {
        this.setupLazyLoading();
        this.setupRequestCaching();
        this.optimizeFormSubmissions();
        this.setupTablePagination();
    }

    // Lazy Loading for Images
    setupLazyLoading() {
        if ('IntersectionObserver' in window) {
            this.imageObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        if (img.dataset.src) {
                            img.src = img.dataset.src;
                            img.classList.remove('lazy');
                            this.imageObserver.unobserve(img);
                        }
                    }
                });
            });

            // Observe all lazy images
            document.querySelectorAll('img[data-src]').forEach(img => {
                img.classList.add('lazy');
                this.imageObserver.observe(img);
            });
        }
    }

    // Request Caching
    setupRequestCaching() {
        this.originalFetch = window.fetch.bind(window);
        window.fetch = this.cachedFetch.bind(this);
    }

    async cachedFetch(url, options = {}) {
        // Only cache GET requests
        if (options.method && options.method !== 'GET') {
            return this.originalFetch.call(window, url, options);
        }

        const cacheKey = url + JSON.stringify(options);
        
        // Check cache first
        if (this.cache.has(cacheKey)) {
            const cached = this.cache.get(cacheKey);
            // Cache for 5 minutes
            if (Date.now() - cached.timestamp < 300000) {
                return Promise.resolve(cached.response.clone());
            }
        }

        // Make request and cache result
        const response = await this.originalFetch.call(window, url, options);
        if (response.ok) {
            this.cache.set(cacheKey, {
                response: response.clone(),
                timestamp: Date.now()
            });
        }

        return response;
    }

    // Optimize Form Submissions
    optimizeFormSubmissions() {
        document.addEventListener('submit', (e) => {
            const form = e.target;
            if (form.tagName === 'FORM') {
                this.handleFormSubmission(form);
            }
        });
    }

    handleFormSubmission(form) {
        // Prevent double submissions
        const submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
        if (submitBtn && !submitBtn.disabled) {
            setTimeout(() => {
                submitBtn.disabled = true;
                submitBtn.classList.add('loading');
                
                // Re-enable after 3 seconds as fallback
                setTimeout(() => {
                    submitBtn.disabled = false;
                    submitBtn.classList.remove('loading');
                }, 3000);
            }, 100);
        }
    }

    // Table Pagination for Large Datasets
    setupTablePagination() {
        document.querySelectorAll('table.large-dataset').forEach(table => {
            this.paginateTable(table);
        });
    }

    paginateTable(table, rowsPerPage = 50) {
        const tbody = table.querySelector('tbody');
        if (!tbody) return;

        const rows = Array.from(tbody.querySelectorAll('tr'));
        if (rows.length <= rowsPerPage) return;

        let currentPage = 1;
        const totalPages = Math.ceil(rows.length / rowsPerPage);

        // Create pagination controls
        const paginationContainer = document.createElement('div');
        paginationContainer.className = 'table-pagination';
        paginationContainer.innerHTML = `
            <div class="pagination-info">
                Showing <span class="current-range"></span> of ${rows.length} entries
            </div>
            <div class="pagination-controls">
                <button class="btn btn-sm" id="prev-page" disabled>Previous</button>
                <span class="page-info">Page <span class="current-page">1</span> of ${totalPages}</span>
                <button class="btn btn-sm" id="next-page">Next</button>
            </div>
        `;

        table.parentNode.insertBefore(paginationContainer, table.nextSibling);

        const showPage = (page) => {
            const start = (page - 1) * rowsPerPage;
            const end = start + rowsPerPage;

            rows.forEach((row, index) => {
                row.style.display = (index >= start && index < end) ? '' : 'none';
            });

            // Update pagination info
            paginationContainer.querySelector('.current-page').textContent = page;
            paginationContainer.querySelector('.current-range').textContent = 
                `${start + 1}-${Math.min(end, rows.length)}`;

            // Update button states
            paginationContainer.querySelector('#prev-page').disabled = page === 1;
            paginationContainer.querySelector('#next-page').disabled = page === totalPages;
        };

        // Event listeners
        paginationContainer.querySelector('#prev-page').addEventListener('click', () => {
            if (currentPage > 1) {
                currentPage--;
                showPage(currentPage);
            }
        });

        paginationContainer.querySelector('#next-page').addEventListener('click', () => {
            if (currentPage < totalPages) {
                currentPage++;
                showPage(currentPage);
            }
        });

        // Show first page
        showPage(1);
    }

    // Debounce function for search inputs
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // Setup debounced search
    setupDebouncedSearch() {
        document.querySelectorAll('input[type="search"], .search-input').forEach(input => {
            const originalHandler = input.oninput || input.onkeyup;
            if (originalHandler) {
                input.oninput = this.debounce(originalHandler, 300);
                input.onkeyup = null;
            }
        });
    }

    // Preload critical resources
    preloadResources(urls) {
        urls.forEach(url => {
            const link = document.createElement('link');
            link.rel = 'prefetch';
            link.href = url;
            document.head.appendChild(link);
        });
    }

    // Clear cache
    clearCache() {
        this.cache.clear();
    }

    // Get cache statistics
    getCacheStats() {
        return {
            size: this.cache.size,
            entries: Array.from(this.cache.keys())
        };
    }
}

// Initialize performance optimizer
document.addEventListener('DOMContentLoaded', () => {
    window.performanceOptimizer = new PerformanceOptimizer();
    
    // Setup debounced search after DOM is ready
    window.performanceOptimizer.setupDebouncedSearch();
    
    // Preload common resources
    const commonResources = [
        'api/search.php',
        'api/document_details.php',
        'assets/css/notifications.css'
    ];
    window.performanceOptimizer.preloadResources(commonResources);
});

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = PerformanceOptimizer;
}