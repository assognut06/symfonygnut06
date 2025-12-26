import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = [
        'form',
        'results',
        'pagination',
        'resultsInfo',
        'sidebar',
        'loader'
    ];

    static values = {
        url: String
    };

    connect() {
        console.log('✅ TIH Search Stimulus controller connected');
        this.checkboxStates = new Map();
    }

    filterChange(event) {
        this.performFilteredSearch();
    }

    clearFilters(event) {
        event.preventDefault();
        this.element.querySelectorAll('.filter-checkbox').forEach(cb => cb.checked = false);
        this.performFilteredSearch();
    }

    handlePagination(event) {
        event.preventDefault();
        const link = event.currentTarget;
        const href = link.getAttribute('href');
        
        if (href && href !== '#') {
            const url = new URL(href, window.location.origin);
            this.fetchResults(url.toString());
        }
    }

    performFilteredSearch() {
        const formData = new FormData(this.formTarget);
        const url = new URL(this.urlValue, window.location.origin);
        
        // Add all checked filters to URL
        for (let [key, value] of formData.entries()) {
            url.searchParams.append(key, value);
        }

        this.fetchResults(url.toString());
    }

    fetchResults(url) {
        // Show loading state
        if (this.hasLoaderTarget) {
            this.loaderTarget.style.display = 'flex';
        }

        // Save current checked state before AJAX call
        this.saveCheckboxStates();

        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.text();
        })
        .then(html => {
            this.updateContent(html);
            
            // Update URL without reload
            window.history.pushState({}, '', url);
            
            // Hide loader
            if (this.hasLoaderTarget) {
                this.loaderTarget.style.display = 'none';
            }
            
            // Scroll to top of results
            this.resultsTarget.scrollIntoView({ behavior: 'smooth', block: 'start' });
        })
        .catch(error => {
            console.error('Filter error:', error);
            if (this.hasLoaderTarget) {
                this.loaderTarget.style.display = 'none';
            }
        });
    }

    updateContent(html) {
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        
        // Update results
        const newResults = doc.getElementById('tih-grid');
        if (newResults && this.hasResultsTarget) {
            this.resultsTarget.innerHTML = newResults.innerHTML;
        }
        
        // Update pagination
        const newPagination = doc.querySelector('nav[aria-label="Navigation des pages de résultats"]');
        if (this.hasPaginationTarget) {
            if (newPagination) {
                this.paginationTarget.innerHTML = newPagination.innerHTML;
            } else {
                this.paginationTarget.innerHTML = '';
            }
        }
        
        // Update results info
        const newResultsInfo = doc.querySelector('.text-white-50');
        if (this.hasResultsInfoTarget) {
            if (newResultsInfo) {
                this.resultsInfoTarget.textContent = newResultsInfo.textContent;
                this.resultsInfoTarget.style.display = '';
            } else {
                this.resultsInfoTarget.style.display = 'none';
            }
        }
        
        // Update filter sidebar with new counts
        const newSidebar = doc.querySelector('aside .card-body');
        if (newSidebar && this.hasSidebarTarget) {
            this.sidebarTarget.innerHTML = newSidebar.innerHTML;
            this.restoreCheckboxStates();
        }
    }

    saveCheckboxStates() {
        this.checkboxStates.clear();
        this.element.querySelectorAll('.filter-checkbox').forEach(cb => {
            const key = `${cb.name}|${cb.value}`;
            this.checkboxStates.set(key, cb.checked);
        });
    }

    restoreCheckboxStates() {
        this.element.querySelectorAll('.filter-checkbox').forEach(checkbox => {
            const key = `${checkbox.name}|${checkbox.value}`;
            if (this.checkboxStates.has(key)) {
                checkbox.checked = this.checkboxStates.get(key);
            }
        });
    }

    handleBrowserNavigation(event) {
        location.reload();
    }
}
