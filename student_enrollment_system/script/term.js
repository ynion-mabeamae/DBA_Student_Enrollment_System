// term.js - Term Management System JavaScript

const TermManager = {
    // Initialize the term management system
    init: function() {
        this.initializeModals();
        this.initializeNotifications();
        this.initializeSearch();
        this.initializeDeleteConfirmation();
        this.setupEventListeners();
        this.showNotifications();
        this.setMinEndDate();
    },

    // Modal functionality
    initializeModals: function() {
        this.termModal = document.getElementById("termModal");
        this.deleteConfirmation = document.getElementById("deleteConfirmation");
        this.openBtn = document.getElementById("openTermModal");
        this.closeBtns = document.querySelectorAll(".close");
        this.cancelBtns = document.querySelectorAll(".btn-cancel");
    },

    initializeDeleteConfirmation: function() {
        this.confirmDeleteBtn = document.getElementById('confirmDelete');
        this.cancelDeleteBtn = document.getElementById('cancelDelete');
        this.deleteTermId = null;
        this.deleteTermCode = null;

        this.setupDeleteConfirmationEvents();
    },

    setupEventListeners: function() {
        // Open add term modal
        if (this.openBtn) {
            this.openBtn.addEventListener('click', () => {
                this.openAddTermModal();
            });
        }

        // Close modals when clicking X
        this.closeBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                this.closeModals();
            });
        });

        // Close modals when clicking cancel buttons
        this.cancelBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                this.closeModals();
            });
        });

        // Close modals when clicking outside
        document.addEventListener('click', (event) => {
            if (event.target === this.termModal) {
                this.closeModals();
            }
            if (event.target === this.deleteConfirmation) {
                this.hideDeleteConfirmation();
            }
        });

        // Edit button events
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('edit-btn')) {
                this.openEditTermModal(e.target);
            }
        });

        // Delete button events
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('delete-btn')) {
                const termId = e.target.getAttribute('data-term-id');
                const termCode = e.target.getAttribute('data-term-code');
                this.showDeleteConfirmation(termId, termCode);
            }
        });

        // Date validation
        const startDateInput = document.getElementById('start_date');
        const endDateInput = document.getElementById('end_date');

        if (startDateInput) {
            startDateInput.addEventListener('change', () => {
                this.setMinEndDate();
            });
        }

        // Close modals on successful form submission
        const termForm = document.getElementById('termForm');
        if (termForm) {
            termForm.addEventListener('submit', (e) => {
                if (!this.validateDates()) {
                    e.preventDefault();
                    return false;
                }
                setTimeout(() => {
                    this.closeModals();
                }, 1000);
            });
        }

        const deleteForm = document.getElementById('deleteTermForm');
        if (deleteForm) {
            deleteForm.addEventListener('submit', () => {
                setTimeout(() => {
                    this.closeModals();
                }, 1000);
            });
        }
    },

    setupDeleteConfirmationEvents: function() {
        // Confirm delete
        if (this.confirmDeleteBtn) {
            this.confirmDeleteBtn.addEventListener('click', () => {
                this.executeDelete();
            });
        }

        // Cancel delete
        if (this.cancelDeleteBtn) {
            this.cancelDeleteBtn.addEventListener('click', () => {
                this.hideDeleteConfirmation();
            });
        }

        // Close when clicking outside
        if (this.deleteConfirmation) {
            this.deleteConfirmation.addEventListener('click', (e) => {
                if (e.target === this.deleteConfirmation) {
                    this.hideDeleteConfirmation();
                }
            });
        }
    },

    openAddTermModal: function() {
        // Reset form
        const termForm = document.getElementById('termForm');
        if (termForm) {
            termForm.reset();
        }
        
        // Update modal title and buttons
        document.getElementById('termModalTitle').textContent = 'Add New Term';
        document.getElementById('addTermBtn').style.display = 'block';
        document.getElementById('updateTermBtn').style.display = 'none';
        
        // Clear hidden term_id
        document.getElementById('term_id').value = '';
        
        // Set today's date as default for start date
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('start_date').value = today;
        
        this.setMinEndDate();
        this.showModal(this.termModal);
    },

    openEditTermModal: function(button) {
        // Get term data from data attributes
        const termId = button.getAttribute('data-term-id');
        const termCode = button.getAttribute('data-term-code');
        const startDate = button.getAttribute('data-start-date');
        const endDate = button.getAttribute('data-end-date');

        // Fill form with term data
        document.getElementById('term_id').value = termId;
        document.getElementById('term_code').value = termCode;
        document.getElementById('start_date').value = startDate;
        document.getElementById('end_date').value = endDate;

        // Update modal title and buttons
        document.getElementById('termModalTitle').textContent = 'Edit Term';
        document.getElementById('addTermBtn').style.display = 'none';
        document.getElementById('updateTermBtn').style.display = 'block';
        
        this.setMinEndDate();
        this.showModal(this.termModal);
    },

    showDeleteConfirmation: function(termId, termCode) {
        this.deleteTermId = termId;
        this.deleteTermCode = termCode;
        
        const message = document.getElementById('deleteMessage');
        if (message) {
            message.textContent = `Are you sure you want to delete "${termCode}"? This action cannot be undone.`;
        }

        if (this.deleteConfirmation) {
            this.deleteConfirmation.classList.add('show');
        }
    },

    hideDeleteConfirmation: function() {
        this.deleteTermId = null;
        this.deleteTermCode = null;
        
        if (this.deleteConfirmation) {
            this.deleteConfirmation.classList.remove('show');
        }
    },

    executeDelete: function() {
        if (this.deleteTermId) {
            // Set the term ID in the hidden form and submit it
            const deleteTermId = document.getElementById('deleteTermId');
            const deleteForm = document.getElementById('deleteTermForm');
            
            if (deleteTermId && deleteForm) {
                deleteTermId.value = this.deleteTermId;
                deleteForm.submit();
            }
        }
        this.hideDeleteConfirmation();
    },

    setMinEndDate: function() {
        const startDateInput = document.getElementById('start_date');
        const endDateInput = document.getElementById('end_date');
        
        if (startDateInput && endDateInput) {
            endDateInput.min = startDateInput.value;
        }
    },

    validateDates: function() {
        const startDate = document.getElementById('start_date').value;
        const endDate = document.getElementById('end_date').value;
        
        if (startDate && endDate) {
            const start = new Date(startDate);
            const end = new Date(endDate);
            
            if (end < start) {
                alert('End date cannot be earlier than start date.');
                return false;
            }
        }
        return true;
    },

    showModal: function(modal) {
        if (modal) {
            modal.style.display = "block";
            setTimeout(() => {
                modal.classList.add('show');
            }, 10);
        }
    },

    closeModals: function() {
        // Close term modal
        if (this.termModal) {
            this.termModal.classList.remove('show');
            setTimeout(() => {
                this.termModal.style.display = "none";
            }, 300);
        }
        
        // Also close delete confirmation
        this.hideDeleteConfirmation();
    },

    // Search functionality
    initializeSearch: function() {
        this.searchInput = document.getElementById('searchTerms');
        this.searchButton = document.getElementById('searchButton');
        this.clearSearchBtn = document.getElementById('clearSearch');
        this.searchStats = document.getElementById('searchStats');
        this.termRows = document.querySelectorAll('tbody tr');
        this.totalTerms = this.termRows.length;

        this.setupSearchEvents();
        this.updateSearchStats(this.totalTerms, this.totalTerms);
    },

    setupSearchEvents: function() {
        // Search input event (real-time search)
        if (this.searchInput) {
            this.searchInput.addEventListener('input', (e) => {
                this.filterTerms(e.target.value);
            });
            
            // Also allow Enter key to trigger search
            this.searchInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    this.filterTerms(e.target.value);
                }
            });
        }

        // Search button event
        if (this.searchButton) {
            this.searchButton.addEventListener('click', () => {
                this.filterTerms(this.searchInput.value);
            });
        }

        // Clear search event
        if (this.clearSearchBtn) {
            this.clearSearchBtn.addEventListener('click', () => {
                this.clearSearch();
            });
        }
    },

    filterTerms: function(searchTerm) {
        const term = searchTerm.toLowerCase().trim();
        let visibleCount = 0;

        this.termRows.forEach(row => {
            const code = row.cells[0].textContent.toLowerCase();
            const startDate = row.cells[1].textContent.toLowerCase();
            const endDate = row.cells[2].textContent.toLowerCase();

            const matches = code.includes(term) || startDate.includes(term) || endDate.includes(term);
            
            if (matches) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        this.updateSearchStats(visibleCount, this.totalTerms);
        this.toggleNoResults(visibleCount === 0 && term.length > 0);
        this.toggleClearSearch(term.length > 0);
        
        // Focus the search input after filtering
        if (this.searchInput) {
            this.searchInput.focus();
        }
    },

    clearSearch: function() {
        if (this.searchInput) {
            this.searchInput.value = '';
        }
        
        // Show all rows
        this.termRows.forEach(row => {
            row.style.display = '';
        });

        this.updateSearchStats(this.totalTerms, this.totalTerms);
        this.toggleNoResults(false);
        this.toggleClearSearch(false);
        
        // Focus the search input after clearing
        if (this.searchInput) {
            this.searchInput.focus();
        }
    },

    updateSearchStats: function(visible, total) {
        if (this.searchStats) {
            this.searchStats.textContent = `Showing ${visible} of ${total} terms`;
        }
    },

    toggleNoResults: function(show) {
        let noResults = document.getElementById('noResults');
        
        if (show && !noResults) {
            noResults = document.createElement('tr');
            noResults.id = 'noResults';
            noResults.innerHTML = `
                <td colspan="5">
                    <div class="no-results">
                        <div class="no-results-icon">🔍</div>
                        <h3>No terms found</h3>
                        <p>Try adjusting your search terms</p>
                    </div>
                </td>
            `;
            document.querySelector('tbody').appendChild(noResults);
        } else if (!show && noResults) {
            noResults.remove();
        }
    },

    toggleClearSearch: function(show) {
        if (this.clearSearchBtn) {
            this.clearSearchBtn.style.display = show ? 'block' : 'none';
        }
    },

    // Notification functionality
    initializeNotifications: function() {
        this.setupNotificationEvents();
        this.autoHideNotifications();
    },

    setupNotificationEvents: function() {
        // Close notification when clicking close button
        const closeButtons = document.querySelectorAll('.notification-close');
        closeButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                this.closeNotification(e.target.closest('.notification'));
            });
        });

        // Auto-close notifications when clicking anywhere
        document.addEventListener('click', (e) => {
            if (e.target.closest('.notification')) {
                setTimeout(() => {
                    this.closeNotification(e.target.closest('.notification'));
                }, 3000);
            }
        });
    },

    closeNotification: function(notification) {
        if (notification) {
            notification.classList.remove('show');
            setTimeout(() => {
                notification.style.display = 'none';
            }, 300);
        }
    },

    autoHideNotifications: function() {
        const notifications = document.querySelectorAll('.notification');
        notifications.forEach(notification => {
            setTimeout(() => {
                this.closeNotification(notification);
            }, 5000);
        });
    },

    showNotifications: function() {
        const notifications = document.querySelectorAll('.notification');
        notifications.forEach(notification => {
            setTimeout(() => {
                notification.classList.add('show');
            }, 100);
        });
    }
};

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    if (typeof TermManager !== 'undefined') {
        TermManager.init();
    }
});

// Add keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Escape key to close modals
    if (e.key === 'Escape') {
        const termModal = document.getElementById('termModal');
        const deleteConfirmation = document.getElementById('deleteConfirmation');
        
        if (termModal && termModal.style.display === 'block') {
            TermManager.closeModals();
        } else if (deleteConfirmation && deleteConfirmation.classList.contains('show')) {
            TermManager.hideDeleteConfirmation();
        }
    }
    
    // Ctrl/Cmd + F to focus search
    if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
        e.preventDefault();
        const searchInput = document.getElementById('searchTerms');
        if (searchInput) {
            searchInput.focus();
        }
    }
    
    // Ctrl/Cmd + N to open new term modal
    if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
        e.preventDefault();
        const openBtn = document.getElementById('openTermModal');
        if (openBtn) {
            openBtn.click();
        }
    }
});

// Auto-hide toast notifications after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    const toasts = document.querySelectorAll('.toast');
    toasts.forEach(toast => {
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(100%)';
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 300);
        }, 5000);
    });
});

// Export data function
function exportData(type) {
    // Build export URL
    let exportUrl = `term_export_${type}.php`;
    
    console.log('Export URL:', exportUrl); // Debug log
    
    // Open export in new window
    window.open(exportUrl, '_blank');
}