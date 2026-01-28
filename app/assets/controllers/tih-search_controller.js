import { Controller } from '@hotwired/stimulus';
import noUiSlider from 'nouislider';
import 'nouislider/dist/nouislider.css';

export default class extends Controller {
    static targets = [
        'form',
        'results',
        'pagination',
        'resultsInfo',
        'sidebar',
        'loader',
        'mapView',
        'departementSection',
        'departementContainer'
    ];

    static values = {
        url: String,
        profiles: Array,
        apiKey: String,
        cityCoordinates: Object
    };

    connect() {
        console.log('✅ TIH Search Stimulus controller connected');
        this.checkboxStates = new Map();
        
        // Store original rate range on first load
        const sliderContainer = this.element.querySelector('#rateSlider');
        if (sliderContainer) {
            this.originalMinRate = parseFloat(sliderContainer.dataset.min) || 0;
            this.originalMaxRate = parseFloat(sliderContainer.dataset.max) || 100;
        }
        
        // Define region coordinates for map zooming
        this.regionCoordinates = {
            'Île-de-France': { lat: 48.8566, lng: 2.3522, zoom: 9 },
            'Provence-Alpes-Côte d\'Azur': { lat: 43.9352, lng: 6.0679, zoom: 8 },
            'Auvergne-Rhône-Alpes': { lat: 45.4472, lng: 4.8506, zoom: 7 },
            'Occitanie': { lat: 43.6043, lng: 1.4437, zoom: 7 },
            'Nouvelle-Aquitaine': { lat: 44.8378, lng: -0.5792, zoom: 7 },
            'Pays de la Loire': { lat: 47.2184, lng: -1.5536, zoom: 8 },
            'Bretagne': { lat: 48.1173, lng: -1.6778, zoom: 8 },
            'Grand Est': { lat: 48.5734, lng: 7.7521, zoom: 7 },
            'Hauts-de-France': { lat: 50.6292, lng: 3.0573, zoom: 8 },
            'Normandie': { lat: 49.1829, lng: 0.3707, zoom: 8 },
            'Bourgogne-Franche-Comté': { lat: 47.2805, lng: 4.9994, zoom: 7 },
            'Centre-Val de Loire': { lat: 47.7516, lng: 1.6751, zoom: 8 }
        };
        
        this.initializeRateSlider();
        this.map = null;
        this.markers = [];
        this.infoWindows = [];
        this.loadGoogleMaps();
    }

    loadGoogleMaps() {
        if (window.google && window.google.maps) {
            return;
        }

        const script = document.createElement('script');
        script.src = `https://maps.googleapis.com/maps/api/js?key=${this.apiKeyValue}&libraries=marker&loading=async`;
        script.async = true;
        script.defer = true;
        document.head.appendChild(script);
    }

    initializeRateSlider() {
        const sliderContainer = this.element.querySelector('#rateSlider');
        const minRateInput = this.element.querySelector('#minRateInput');
        const maxRateInput = this.element.querySelector('#maxRateInput');
        const rateValue = this.element.querySelector('#rateValue');
        
        if (!sliderContainer || !minRateInput || !maxRateInput) return;
        
        // Destroy existing slider if it exists
        if (sliderContainer.noUiSlider) {
            sliderContainer.noUiSlider.destroy();
        }
        
        // Use original values from first page load, not updated values from server
        const minRate = this.originalMinRate || parseFloat(sliderContainer.dataset.min) || 0;
        const maxRate = this.originalMaxRate || parseFloat(sliderContainer.dataset.max) || 100;
        const currentMin = parseFloat(minRateInput.value) || minRate;
        const currentMax = parseFloat(maxRateInput.value) || maxRate;
        
        // Initialize noUiSlider with original range
        noUiSlider.create(sliderContainer, {
            start: [currentMin, currentMax],
            connect: true,
            range: {
                'min': minRate,
                'max': maxRate
            },
            step: 1,
            tooltips: false,
            format: {
                to: value => Math.round(value),
                from: value => Number(value)
            }
        });
        
        // Update inputs and display on slider change
        sliderContainer.noUiSlider.on('update', (values) => {
            const [min, max] = values;
            minRateInput.value = min;
            maxRateInput.value = max;
            if (rateValue) {
                rateValue.textContent = `${min}€ - ${max}€`;
            }
        });
        
        // Trigger filter change on slider change (end of drag)
        sliderContainer.noUiSlider.on('change', () => {
            this.performFilteredSearch();
        });
    }

    filterChange(event) {
        this.performFilteredSearch();
    }

    regionChange(event) {
        const regionCheckbox = event.target;
        const regionName = regionCheckbox.value;
        const isChecked = regionCheckbox.checked;

        // Get all département items for this region
        const departementItems = this.element.querySelectorAll(`.departement-item[data-region="${regionName}"]`);
        
        if (isChecked) {
            // Show départements for this region
            departementItems.forEach(item => {
                item.style.display = 'block';
            });
        } else {
            // Hide and uncheck départements for this region
            departementItems.forEach(item => {
                item.style.display = 'none';
                const checkbox = item.querySelector('input[type="checkbox"]');
                if (checkbox) {
                    checkbox.checked = false;
                }
            });
        }

        // Update map view if map is visible and initialized
        if (this.map && this.hasMapViewTarget && !this.mapViewTarget.classList.contains('d-none')) {
            this.updateMapForSelectedRegions();
        }

        // Trigger filter change
        this.performFilteredSearch();
    }

    updateMapForSelectedRegions() {
        // Get all checked region checkboxes
        const checkedRegions = Array.from(this.element.querySelectorAll('.region-checkbox:checked'))
            .map(cb => cb.value);

        if (checkedRegions.length === 1) {
            // Single region selected, zoom to that region
            const region = checkedRegions[0];
            const coords = this.regionCoordinates[region];
            if (coords) {
                this.map.setCenter({ lat: coords.lat, lng: coords.lng });
                this.map.setZoom(coords.zoom);
            }
        } else {
            // No regions or multiple regions selected, show all of France
            this.map.setCenter({ lat: 46.603354, lng: 1.888334 });
            this.map.setZoom(6);
        }
    }

    clearFilters(event) {
        event.preventDefault();
        this.element.querySelectorAll('.filter-checkbox').forEach(cb => cb.checked = false);
        // Hide all département items
        this.element.querySelectorAll('.departement-item').forEach(item => {
            item.style.display = 'none';
        });
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
        
        // Add all checked filters to URL, excluding empty values
        for (let [key, value] of formData.entries()) {
            if (value !== '' && value !== 'all') {
                url.searchParams.append(key, value);
            }
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
            // Reinitialize the rate slider after sidebar update
            this.initializeRateSlider();
        }
        
        // Update profiles value for map
        const newMain = doc.querySelector('main[data-tih-search-profiles-value]');
        if (newMain) {
            const newProfilesJson = newMain.getAttribute('data-tih-search-profiles-value');
            if (newProfilesJson) {
                try {
                    this.profilesValue = JSON.parse(newProfilesJson);
                    // Refresh map if it's currently visible
                    if (this.hasMapViewTarget && !this.mapViewTarget.classList.contains('d-none')) {
                        this.initializeMap();
                    }
                } catch (e) {
                    console.error('Error parsing profiles data:', e);
                }
            }
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

    showGridView(event) {
        document.getElementById('gridViewBtn').classList.add('active');
        document.getElementById('mapViewBtn').classList.remove('active');
        this.resultsTarget.classList.remove('d-none');
        if (this.hasMapViewTarget) {
            this.mapViewTarget.classList.add('d-none');
        }
        // Show pagination in grid view
        if (this.hasPaginationTarget) {
            this.paginationTarget.classList.remove('d-none');
        }
    }

    showMapView(event) {
        document.getElementById('mapViewBtn').classList.add('active');
        document.getElementById('gridViewBtn').classList.remove('active');
        this.resultsTarget.classList.add('d-none');
        
        // Hide pagination in map view
        if (this.hasPaginationTarget) {
            this.paginationTarget.classList.add('d-none');
        }
        
        if (this.hasMapViewTarget) {
            this.mapViewTarget.classList.remove('d-none');
            this.initializeMap();
        }
    }

    initializeMap() {
        if (!window.google || !window.google.maps) {
            console.error('Google Maps not loaded yet');
            setTimeout(() => this.initializeMap(), 500);
            return;
        }

        if (!this.map) {
            // Initialize map centered on France
            this.map = new google.maps.Map(document.getElementById('tih-map'), {
                center: { lat: 46.603354, lng: 1.888334 },
                zoom: 6,
                mapId: 'tih_search_map'
            });
        }

        // Clear existing markers and info windows
        this.markers.forEach(marker => marker.setMap(null));
        this.infoWindows.forEach(infoWindow => infoWindow.close());
        this.markers = [];
        this.infoWindows = [];

        // Group profiles by city
        const profilesByCity = {};
        if (this.profilesValue && Array.isArray(this.profilesValue)) {
            this.profilesValue.forEach(profile => {
                if (profile.city) {
                    if (!profilesByCity[profile.city]) {
                        profilesByCity[profile.city] = [];
                    }
                    profilesByCity[profile.city].push(profile);
                }
            });
        }

        // Create bounds to fit all markers
        const bounds = new google.maps.LatLngBounds();
        const cityCoordinates = this.cityCoordinatesValue || {};

        // If no profiles, reset to France view
        if (Object.keys(profilesByCity).length === 0) {
            this.map.setCenter({ lat: 46.603354, lng: 1.888334 });
            this.map.setZoom(6);
            return;
        }

        // Create markers for each city with profiles
        let markersCreated = 0;
        let citiesWithoutCoords = [];
        
        Object.entries(profilesByCity).forEach(([city, profiles]) => {
            const coords = cityCoordinates[city];
            
            if (!coords || !coords.lat || !coords.lng) {
                console.warn(`No coordinates found for city: ${city}`);
                citiesWithoutCoords.push(city);
                return;
            }
            
            const position = { lat: coords.lat, lng: coords.lng };
            const count = profiles.length;
            
            // Create custom marker content with label
            const markerContent = document.createElement('div');
            markerContent.style.cssText = `
                width: 50px;
                height: 50px;
                border-radius: 50%;
                background: #F86CF8;
                border: 3px solid #ffffff;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 16px;
                font-weight: bold;
                color: #ffffff;
                box-shadow: 0 2px 6px rgba(0,0,0,0.3);
                cursor: pointer;
            `;
            markerContent.textContent = count.toString();
            
            // Create AdvancedMarkerElement
            const marker = new google.maps.marker.AdvancedMarkerElement({
                position: position,
                map: this.map,
                title: `${count} profil${count > 1 ? 's' : ''} à ${city}`,
                content: markerContent
            });

            // Create info window content with carousel
            const infoWindow = new google.maps.InfoWindow({
                content: this.createCarouselPopup(profiles, city)
            });

            marker.addListener('click', () => {
                // Close all other info windows
                this.infoWindows.forEach(iw => iw.close());
                infoWindow.open(this.map, marker);
                
                // Initialize carousel after info window opens
                google.maps.event.addListenerOnce(infoWindow, 'domready', () => {
                    this.initializeCarousel(city);
                });
            });

            this.markers.push(marker);
            this.infoWindows.push(infoWindow);
            bounds.extend(position);
            markersCreated++;
        });

        if (citiesWithoutCoords.length > 0) {
            console.warn(`Missing coordinates for ${citiesWithoutCoords.length} cities:`, citiesWithoutCoords);
        }
        
        console.log(`Created ${markersCreated} markers for ${Object.keys(profilesByCity).length} cities`);

        // Adjust map view based on number of markers
        if (this.markers.length === 0) {
            // No markers, show France
            this.map.setCenter({ lat: 46.603354, lng: 1.888334 });
            this.map.setZoom(6);
        } else if (this.markers.length === 1) {
            // For single marker, center on it with reasonable zoom
            const position = this.markers[0].position;
            this.map.setCenter(position);
            this.map.setZoom(11);
        } else {
            // For 2+ markers, fit bounds to show all markers
            this.map.fitBounds(bounds);
            
            // Prevent over-zooming when markers are close together
            google.maps.event.addListenerOnce(this.map, 'bounds_changed', () => {
                const currentZoom = this.map.getZoom();
                if (currentZoom > 11) {
                    this.map.setZoom(11);
                } else if (currentZoom < 5) {
                    this.map.setZoom(5);
                }
            });
        }
    }

    createCarouselPopup(profiles, city) {
        if (profiles.length === 1) {
            const profile = profiles[0];
            return `
                <div class="map-popup">
                    <h6>${profile.firstName} ${profile.lastName}</h6>
                    <p class="mb-1"><i class="bi bi-geo-alt"></i> ${profile.postalCode} ${profile.city}</p>
                    ${profile.rate ? `<p class="mb-1"><i class="bi bi-currency-euro"></i> ${profile.rate}€ / ${profile.rateType}</p>` : ''}
                    ${profile.availability ? `<p class="mb-2"><small>${profile.availability}</small></p>` : ''}
                    <a href="/tih/tih/${profile.id}" class="btn btn-sm btn-primary w-100">Voir le profil</a>
                </div>
            `;
        }

        // Multiple profiles - create carousel
        const carouselId = `carousel-${city.replace(/[^a-zA-Z0-9]/g, '')}`;
        const profilesHtml = profiles.map((profile, index) => `
            <div class="carousel-item ${index === 0 ? 'active' : ''}" data-profile-index="${index}">
                <div class="map-popup">
                    <h6>${profile.firstName} ${profile.lastName}</h6>
                    <p class="mb-1"><i class="bi bi-geo-alt"></i> ${profile.postalCode} ${profile.city}</p>
                    ${profile.rate ? `<p class="mb-1"><i class="bi bi-currency-euro"></i> ${profile.rate}€ / ${profile.rateType}</p>` : ''}
                    ${profile.availability ? `<p class="mb-2"><small>${profile.availability}</small></p>` : ''}
                    <div class="d-flex gap-2 align-items-center">
                        <a href="/tih/tih/${profile.id}" class="btn btn-sm btn-primary flex-grow-1">Voir le profil</a>
                        <span class="badge bg-secondary">${index + 1}/${profiles.length}</span>
                    </div>
                </div>
            </div>
        `).join('');

        return `
            <div class="carousel-container">
                <div class="carousel-wrapper" id="${carouselId}">
                    ${profilesHtml}
                </div>
                ${profiles.length > 1 ? `
                    <div class="carousel-controls">
                        <button class="carousel-prev btn btn-sm btn-outline-secondary" data-carousel="${carouselId}">
                            <i class="bi bi-chevron-left"></i>
                        </button>
                        <button class="carousel-next btn btn-sm btn-outline-secondary" data-carousel="${carouselId}">
                            <i class="bi bi-chevron-right"></i>
                        </button>
                    </div>
                ` : ''}
            </div>
        `;
    }

    initializeCarousel(city) {
        const carouselId = `carousel-${city.replace(/[^a-zA-Z0-9]/g, '')}`;
        const wrapper = document.getElementById(carouselId);
        
        if (!wrapper) return;

        const items = wrapper.querySelectorAll('.carousel-item');
        let currentIndex = 0;

        const showSlide = (index) => {
            items.forEach((item, i) => {
                item.classList.toggle('active', i === index);
            });
        };

        const prevBtn = document.querySelector(`button[data-carousel="${carouselId}"].carousel-prev`);
        const nextBtn = document.querySelector(`button[data-carousel="${carouselId}"].carousel-next`);

        if (prevBtn) {
            prevBtn.addEventListener('click', () => {
                currentIndex = (currentIndex - 1 + items.length) % items.length;
                showSlide(currentIndex);
            });
        }

        if (nextBtn) {
            nextBtn.addEventListener('click', () => {
                currentIndex = (currentIndex + 1) % items.length;
                showSlide(currentIndex);
            });
        }

        // Touch swipe support
        let touchStartX = 0;
        let touchEndX = 0;

        wrapper.addEventListener('touchstart', (e) => {
            touchStartX = e.changedTouches[0].screenX;
        });

        wrapper.addEventListener('touchend', (e) => {
            touchEndX = e.changedTouches[0].screenX;
            handleSwipe();
        });

        const handleSwipe = () => {
            if (touchEndX < touchStartX - 50) {
                // Swipe left - next
                currentIndex = (currentIndex + 1) % items.length;
                showSlide(currentIndex);
            }
            if (touchEndX > touchStartX + 50) {
                // Swipe right - prev
                currentIndex = (currentIndex - 1 + items.length) % items.length;
                showSlide(currentIndex);
            }
        };
    }
}
