/**
 * Custom JavaScript for Lab Programming Allocation System
 * Anti-cheating measures, utilities, and interactive features
 */

// Global configuration
if (typeof APP_CONFIG === 'undefined') {
    var APP_CONFIG = {
        autoSaveInterval: 30000, // 30 seconds
        refreshInterval: 5000,    // 5 seconds for monitoring
        baseURL: window.location.origin
    };
}

/**
 * ANTI-CHEATING MEASURES FOR STUDENT EXAM PAGE
 */
var AntiCheat = {
    /**
     * Disable copy, paste, cut operations
     */
    disableCopyPaste: function () {
        document.addEventListener('copy', function (e) {
            e.preventDefault();
            showToast('Copying is disabled during exam', 'warning');
            return false;
        });

        document.addEventListener('paste', function (e) {
            e.preventDefault();
            showToast('Pasting is disabled during exam', 'warning');
            return false;
        });

        document.addEventListener('cut', function (e) {
            e.preventDefault();
            showToast('Cutting is disabled during exam', 'warning');
            return false;
        });
    },

    /**
     * Disable right-click context menu
     */
    disableRightClick: function () {
        document.addEventListener('contextmenu', function (e) {
            e.preventDefault();
            showToast('Right-click is disabled during exam', 'warning');
            return false;
        });
    },

    /**
     * Block developer tools shortcuts
     */
    blockDevTools: function () {
        document.addEventListener('keydown', function (e) {
            // Blocked key codes: F12 (123), Ctrl+Shift+I (73), Ctrl+Shift+J (74), Ctrl+U (85)
            const blockedKeys = [123, 73, 74, 85];
            const isDevToolKey = blockedKeys.includes(e.keyCode) && (e.ctrlKey || e.keyCode === 123);
            
            if (isDevToolKey || (e.ctrlKey && e.keyCode === 85)) {
                e.preventDefault();
                showToast('System action disabled', 'warning');
                return false;
            }
            return true;
        });
    },

    /**
     * Warn on page refresh
     */
    warnOnRefresh: function () {
        window.onbeforeunload = function () {
            return "Are you sure you want to refresh? Your allocated questions will not change.";
        };
    },

    /**
     * Initialize all anti-cheat measures
     */
    init: function () {
        this.disableCopyPaste();
        this.disableRightClick();
        this.blockDevTools();
        this.warnOnRefresh();
        console.log('Anti-cheat measures activated');
    }
};

/**
 * TIMER FUNCTIONALITY
 */
var ExamTimer = {
    timeRemaining: 0,
    timerInterval: null,

    /**
     * Start countdown timer
     * @param {number} minutes - Duration in minutes
     * @param {function} onComplete - Callback when timer reaches 0
     */
    start: function (minutes, onComplete) {
        this.timeRemaining = minutes * 60; // Convert to seconds

        this.timerInterval = setInterval(() => {
            this.timeRemaining--;

            const mins = Math.floor(this.timeRemaining / 60);
            const secs = this.timeRemaining % 60;

            const display = `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;

            // Update timer display
            const timerElement = document.getElementById('timer-display');
            if (timerElement) {
                timerElement.textContent = display;

                // Add warning class when less than 5 minutes
                if (this.timeRemaining < 300) {
                    timerElement.classList.add('warning');
                }
            }

            // Timer expired
            if (this.timeRemaining <= 0) {
                clearInterval(this.timerInterval);
                if (typeof onComplete === 'function') {
                    onComplete();
                }
            }
        }, 1000);
    },

    /**
     * Stop timer
     */
    stop: function () {
        if (this.timerInterval) {
            clearInterval(this.timerInterval);
        }
    }
};

/**
 * AUTO-SAVE FUNCTIONALITY
 */
var AutoSave = {
    saveInterval: null,

    /**
     * Start auto-saving code editors
     * @param {Object} editors - Object containing CodeMirror instances
     */
    start: function (editors) {
        this.saveInterval = setInterval(() => {
            Object.keys(editors).forEach(key => {
                const code = editors[key].getValue();
                localStorage.setItem(`draft_${key}`, code);
            });
            console.log('Code auto-saved');
        }, APP_CONFIG.autoSaveInterval);
    },

    /**
     * Stop auto-saving
     */
    stop: function () {
        if (this.saveInterval) {
            clearInterval(this.saveInterval);
        }
    },

    /**
     * Restore saved drafts
     * @param {Object} editors - Object containing CodeMirror instances
     */
    restore: function (editors) {
        Object.keys(editors).forEach(key => {
            const savedCode = localStorage.getItem(`draft_${key}`);
            if (savedCode) {
                editors[key].setValue(savedCode);
                console.log(`Restored draft for ${key}`);
            }
        });
    }
};

/**
 * UTILITY FUNCTIONS
 */

/**
 * Show toast notification
 * @param {string} message - Message to display
 * @param {string} type - Type: success, error, warning, info
 */
function showToast(message, type = 'info') {
    const toastContainer = document.getElementById('toast-container') || createToastContainer();

    const toast = document.createElement('div');
    toast.className = `alert alert-${type} alert-dismissible fade show`;
    toast.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    toastContainer.appendChild(toast);

    // Auto remove after 5 seconds
    setTimeout(() => {
        toast.remove();
    }, 5000);
}

/**
 * Create toast container if it doesn't exist
 */
function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toast-container';
    container.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    document.body.appendChild(container);
    return container;
}

/**
 * Show loading overlay
 */
function showLoading(message = 'Loading...') {
    const overlay = document.createElement('div');
    overlay.id = 'loading-overlay';
    overlay.className = 'spinner-overlay';
    overlay.innerHTML = `
        <div class="text-center">
            <div class="spinner-border spinner-border-lg text-light" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="text-white mt-3">${message}</p>
        </div>
    `;
    document.body.appendChild(overlay);
}

/**
 * Hide loading overlay
 */
function hideLoading() {
    const overlay = document.getElementById('loading-overlay');
    if (overlay) {
        overlay.remove();
    }
}

/**
 * Confirm action
 * @param {string} message - Confirmation message
 * @param {function} callback - Callback if confirmed
 */
function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

/**
 * Format date
 * @param {string} dateString - Date string
 * @returns {string} Formatted date
 */
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleString();
}

/**
 * Copy to clipboard
 * @param {string} text - Text to copy
 */
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showToast('Copied to clipboard', 'success');
    }).catch(() => {
        showToast('Failed to copy', 'error');
    });
}

/**
 * DataTable initialization helper
 * @param {string} tableId - Table element ID
 */
function initDataTable(tableId) {
    // Simple table enhancement (if jQuery DataTables is loaded)
    if (typeof $.fn.DataTable !== 'undefined') {
        $(`#${tableId}`).DataTable({
            pageLength: 25,
            responsive: true,
            order: [[0, 'asc']]
        });
    }
}

/**
 * Form validation helper
 * @param {string} formId - Form element ID
 * @returns {boolean} Validation status
 */
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return false;

    let isValid = true;
    const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');

    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.classList.add('is-invalid');
            isValid = false;
        } else {
            input.classList.remove('is-invalid');
        }
    });

    return isValid;
}

/**
 * AJAX Helper
 * @param {string} url - Request URL
 * @param {Object} data - Data to send
 * @param {function} onSuccess - Success callback
 * @param {function} onError - Error callback
 */
function ajaxRequest(url, data, onSuccess, onError) {
    $.ajax({
        url: url,
        method: 'POST',
        data: data,
        dataType: 'json',
        success: function (response) {
            if (typeof onSuccess === 'function') {
                onSuccess(response);
            }
        },
        error: function (xhr, status, error) {
            if (typeof onError === 'function') {
                onError(error);
            } else {
                showToast('Request failed: ' + error, 'error');
            }
        }
    });
}

/**
 * Initialize tooltips and popovers (Bootstrap)
 */
function initBootstrapComponents() {
    // Tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Popovers
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
}

/**
 * Sidebar Toggle Logic
 */
/**
 * Search Suggestions Module
 */
var SearchSuggestions = {
    init: function () {
        const input = document.getElementById('searchInput');
        const dropdown = document.getElementById('searchSuggestions');
        const form = document.getElementById('searchBar');

        if (!input || !dropdown) return;

        let debounceTimer;

        input.addEventListener('input', function (e) {
            const query = e.target.value.trim();

            clearTimeout(debounceTimer);

            if (query.length < 2) {
                dropdown.classList.add('d-none');
                return;
            }

            debounceTimer = setTimeout(() => {
                SearchSuggestions.fetch(query, dropdown);
            }, 300);
        });

        // Close when clicking outside
        document.addEventListener('click', function (e) {
            if (!form.contains(e.target)) {
                dropdown.classList.add('d-none');
            }
        });

        // Focus handling
        input.addEventListener('focus', function () {
            if (input.value.trim().length >= 2) {
                dropdown.classList.remove('d-none');
            }
        });
    },

    fetch: function (query, dropdown) {
        // Correct path construction
        const apiPath = APP_CONFIG.baseURL + '/LAB%20PROGRAMMING%20ALLOCATION%20SYSTEM/admin/api/search_suggestions.php'; // Adjust if needed relative to root

        fetch(`${apiPath}?q=${encodeURIComponent(query)}`)
            .then(res => res.json())
            .then(data => {
                if (data.length > 0) {
                    let html = '';
                    data.forEach(item => {
                        // Icon mapping fallback
                        let icon = 'bi-search';
                        if (item.type === 'student') icon = 'bi-person';
                        if (item.type === 'staff') icon = 'bi-briefcase';
                        if (item.type === 'subject') icon = 'bi-book';
                        if (item.type === 'lab') icon = 'bi-building';

                        html += `
                            <a href="${item.url}" class="search-item">
                                <i class="bi ${icon}"></i>
                                <div>
                                    <div class="fw-bold small">${item.label}</div>
                                    <div class="text-muted" style="font-size: 0.7rem;">${item.sub}</div>
                                </div>
                            </a>
                        `;
                    });

                    // Add "View all results" link
                    html += `
                        <div class="border-top mt-2 pt-2">
                            <button type="submit" form="searchBar" class="btn btn-sm btn-link text-decoration-none w-100 text-start text-primary fw-bold" style="font-size: 0.8rem;">
                                View all results for "${query}"
                            </button>
                        </div>
                    `;

                    dropdown.innerHTML = html;
                    dropdown.classList.remove('d-none');
                } else {
                    dropdown.innerHTML = `<div class="p-3 text-muted small text-center">No matches found</div>`;
                    dropdown.classList.remove('d-none');
                }
            })
            .catch(err => {
                console.error('Search error:', err);
            });
    }
};

/**
 * Sidebar Toggle Logic
 */
var Sidebar = {
    init: function () {
        const toggleBtn = document.getElementById('sidebarToggleBtn');
        if (!toggleBtn) return;

        // Load state from localStorage
        const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
        if (isCollapsed) {
            document.body.classList.add('sidebar-collapsed');
            const sidebar = document.getElementById('mainSidebar');
            if (sidebar) sidebar.classList.add('collapsed');
        }

        toggleBtn.addEventListener('click', () => {
            const sidebar = document.getElementById('mainSidebar');
            if (sidebar) {
                const isNowCollapsed = document.body.classList.toggle('sidebar-collapsed');
                sidebar.classList.toggle('collapsed', isNowCollapsed);
                localStorage.setItem('sidebarCollapsed', isNowCollapsed);
            }
        });
    }
};

/**
 * Document ready
 */
document.addEventListener('DOMContentLoaded', function () {
    // Initialize Bootstrap components
    initBootstrapComponents();

    // Initialize Sidebar
    Sidebar.init();

    // Initialize Search Suggestions
    if (typeof SearchSuggestions !== 'undefined') {
        SearchSuggestions.init();
    }

    // Log system info
    console.log('Lab Programming Allocation System');
    console.log('Version: 1.0.0');
    console.log('Server:', APP_CONFIG.baseURL);
});
