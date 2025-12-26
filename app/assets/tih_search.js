// Import CSS
import './styles/tih_search.css';

// TIH Search functionality with filters
console.log('✅ TIH Search page loaded');

document.addEventListener('DOMContentLoaded', function() {
    const filterForm = document.getElementById('filter-form');
    const filterCheckboxes = document.querySelectorAll('.filter-checkbox');
    const clearFiltersBtn = document.getElementById('clear-filters');
    const resultsContainer = document.getElementById('tih-grid');
    const paginationContainer = document.querySelector('nav[aria-label="Navigation des pages de résultats"]');
    const resultsInfo = document.querySelector('.text-white-50');
    const searchLoader = document.getElementById('search-loader');
    
    if (!filterForm || !resultsContainer) return;

    // Handle filter changes
    filterCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            performFilteredSearch();
        });
    });

    // Handle clear filters button
    if (clearFiltersBtn) {
        clearFiltersBtn.addEventListener('click', function() {
            filterCheckboxes.forEach(cb => cb.checked = false);
            performFilteredSearch();
        });
    }

    // Handle pagination clicks
    document.addEventListener('click', function(e) {
        if (e.target.closest('.page-link')) {
            e.preventDefault();
            const link = e.target.closest('.page-link');
            const href = link.getAttribute('href');
            
            if (href && href !== '#') {
                const url = new URL(href, window.location.origin);
                fetchResults(url.toString());
            }
        }
    });

    function performFilteredSearch() {
        const formData = new FormData(filterForm);
        const url = new URL(window.location.pathname, window.location.origin);
        
        // Add all checked filters to URL
        for (let [key, value] of formData.entries()) {
            url.searchParams.append(key, value);
        }

        fetchResults(url.toString());
    }

    function fetchResults(url) {
        // Show loading state
        if (searchLoader) {
            searchLoader.style.display = 'flex';
        }

        fetch(url, {
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
            }
            
            // Update filter sidebar with new counts
            const newSidebar = doc.querySelector('aside .card-body');
            const currentSidebar = document.querySelector('aside .card-body');
            if (newSidebar && currentSidebar) {
                // Preserve checked state
                const checkedValues = {};
                document.querySelectorAll('.filter-checkbox:checked').forEach(cb => {
                    const name = cb.name;
                    if (!checkedValues[name]) checkedValues[name] = [];
                    checkedValues[name].push(cb.value);
                });
                
                currentSidebar.innerHTML = newSidebar.innerHTML;
                
                // Restore checked state
                Object.keys(checkedValues).forEach(name => {
                    checkedValues[name].forEach(value => {
                        const checkbox = document.querySelector(`.filter-checkbox[name="${name}"][value="${value}"]`);
                        if (checkbox) checkbox.checked = true;
                    });
                });
                
                // Reattach event listeners
                document.querySelectorAll('.filter-checkbox').forEach(checkbox => {
                    checkbox.addEventListener('change', function() {
                        performFilteredSearch();
                    });
                });
                
                const newClearBtn = document.getElementById('clear-filters');
                if (newClearBtn) {
                    newClearBtn.addEventListener('click', function() {
                        document.querySelectorAll('.filter-checkbox').forEach(cb => cb.checked = false);
                        performFilteredSearch();
                    });
                }
            }
            
            // Update URL without reload
            window.history.pushState({}, '', url);
            
            // Hide loader
            if (searchLoader) {
                searchLoader.style.display = 'none';
            }
            
            // Scroll to top of results
            resultsContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
        })
        .catch(error => {
            console.error('Filter error:', error);
            if (searchLoader) {
                searchLoader.style.display = 'none';
            }
        });
    }

    // Handle browser back/forward buttons
    window.addEventListener('popstate', function() {
        location.reload();
    });
});
