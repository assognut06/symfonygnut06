// Import CSS
import './styles/tih_search.css';

// TIH Search functionality with AJAX
console.log('✅ TIH Search page loaded');

document.addEventListener('DOMContentLoaded', function() {
    const searchForm = document.querySelector('form[role="search"]');
    const searchInput = document.getElementById('tih-search');
    const resultsContainer = document.getElementById('tih-grid');
    const paginationContainer = document.querySelector('nav[aria-label="Navigation des pages de résultats"]');
    const resultsInfo = document.querySelector('.text-white-50');
    const searchLoader = document.getElementById('search-loader');
    const clearBtn = document.getElementById('clear-search');
    
    if (!searchForm || !resultsContainer) return;

    // Handle clear button
    if (clearBtn) {
        clearBtn.addEventListener('click', function() {
            searchInput.value = '';
            performSearch(1);
        });
    }

    // Handle form submission
    searchForm.addEventListener('submit', function(e) {
        e.preventDefault();
        performSearch(1);
    });

    // Real-time search as user types (starting from 3 letters)
    let debounceTimer;
    searchInput.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        
        const searchTerm = this.value.trim();
        
        // Search if 3+ characters or empty (to show all results)
        if (searchTerm.length >= 3 || searchTerm.length === 0) {
            debounceTimer = setTimeout(() => {
                performSearch(1);
            }, 500); // Wait 500ms after user stops typing
        }
    });

    // Handle pagination clicks
    document.addEventListener('click', function(e) {
        if (e.target.closest('.page-link')) {
            e.preventDefault();
            const link = e.target.closest('.page-link');
            const href = link.getAttribute('href');
            
            if (href && href !== '#') {
                const url = new URL(href, window.location.origin);
                const page = url.searchParams.get('page') || 1;
                performSearch(page);
            }
        }
    });

    function performSearch(page = 1) {
        const searchTerm = searchInput.value.trim();
        const url = new URL(window.location.pathname, window.location.origin);
        
        if (searchTerm) {
            url.searchParams.set('q', searchTerm);
        }
        if (page > 1) {
            url.searchParams.set('page', page);
        }

        // Show loading state
        if (searchLoader) {
            searchLoader.style.display = 'flex';
        }

        fetch(url.toString(), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.text())
        .then(html => {
            // Parse the HTML response
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            
            // Update results
            const newResults = doc.getElementById('tih-grid');
            if (newResults) {
                resultsContainer.innerHTML = newResults.innerHTML;
            }
            
            // Update pagination
            const newPagination = doc.querySelector('nav[aria-label="Navigation des pages de résultats"]');
            if (paginationContainer && newPagination) {
                paginationContainer.innerHTML = newPagination.innerHTML;
            } else if (paginationContainer && !newPagination) {
                paginationContainer.innerHTML = '';
            }
            
            // Update results info
            const newResultsInfo = doc.querySelector('.text-white-50');
            if (resultsInfo && newResultsInfo) {
                resultsInfo.textContent = newResultsInfo.textContent;
            } else if (resultsInfo && !newResultsInfo) {
                resultsInfo.style.display = 'none';
            } else if (!resultsInfo && newResultsInfo) {
                const header = document.querySelector('header.text-center');
                if (header) {
                    const infoClone = newResultsInfo.cloneNode(true);
                    header.appendChild(infoClone);
                }
            }
            
            // Update clear button visibility
            const existingClearBtn = document.getElementById('clear-search');
            if (searchTerm && !existingClearBtn) {
                const clearBtn = document.createElement('button');
                clearBtn.type = 'button';
                clearBtn.id = 'clear-search';
                clearBtn.className = 'btn btn-link clear-search-btn';
                clearBtn.setAttribute('aria-label', 'Effacer la recherche');
                clearBtn.innerHTML = '<i class="bi bi-x-circle"></i>';
                clearBtn.addEventListener('click', function() {
                    searchInput.value = '';
                    performSearch(1);
                });
                searchInput.parentElement.appendChild(clearBtn);
            } else if (!searchTerm && existingClearBtn) {
                existingClearBtn.remove();
            }
            
            // Update URL without reload
            window.history.pushState({}, '', url.toString());
            
            // Hide loader
            if (searchLoader) {
                searchLoader.style.display = 'none';
            }
            
            // Keep focus on search input
            searchInput.focus();
            
            // Scroll to top of results
            resultsContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
        })
        .catch(error => {
            console.error('Search error:', error);
            if (searchLoader) {
                searchLoader.style.display = 'none';
            }
            // Keep focus on search input even on error
            searchInput.focus();
        });
    }

    // Handle browser back/forward buttons
    window.addEventListener('popstate', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const q = urlParams.get('q') || '';
        const page = urlParams.get('page') || 1;
        
        searchInput.value = q;
        performSearch(page);
    });
});
