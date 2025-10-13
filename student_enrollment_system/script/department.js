// department.js - Department Management System JavaScript

const DepartmentManager = {
    // Initialize the department management system
    init: function() {
        this.initializeModals();
        this.initializeNotifications();
        this.initializeSearch();
        this.initializeDeleteConfirmation();
        this.setupEventListeners();
        this.showNotifications();
    },

    // Modal functionality
    initializeModals: function() {
        this.departmentModal = document.getElementById("departmentModal");
        this.deleteConfirmation = document.getElementById("deleteConfirmation");
        this.openBtn = document.getElementById("openDepartmentModal");
        this.closeBtns = document.querySelectorAll(".close");
        this.cancelBtns = document.querySelectorAll(".btn-cancel");
    },

    initializeDeleteConfirmation: function() {
        this.confirmDeleteBtn = document.getElementById('confirmDelete');
        this.cancelDeleteBtn = document.getElementById('cancelDelete');
        this.deleteDeptId = null;
        this.deleteDeptName = null;

        this.setupDeleteConfirmationEvents();
    },

    setupEventListeners: function() {
        // Open add department modal
        if (this.openBtn) {
            this.openBtn.addEventListener('click', () => {
                this.openAddDepartmentModal();
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
            if (event.target === this.departmentModal) {
                this.closeModals();
            }
            if (event.target === this.deleteConfirmation) {
                this.hideDeleteConfirmation();
            }
        });

        // Edit button events
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('edit-btn')) {
                this.openEditDepartmentModal(e.target);
            }
        });

        // Delete button events
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('delete-btn')) {
                const deptId = e.target.getAttribute('data-dept-id');
                const deptName = e.target.getAttribute('data-dept-name');
                this.showDeleteConfirmation(deptId, deptName);
            }
        });

        // Close modals on successful form submission
        const departmentForm = document.getElementById('departmentForm');
        if (departmentForm) {
            departmentForm.addEventListener('submit', () => {
                setTimeout(() => {
                    this.closeModals();
                }, 1000);
            });
        }

        const deleteForm = document.getElementById('deleteDepartmentForm');
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

    openAddDepartmentModal: function() {
        // Reset form
        const departmentForm = document.getElementById('departmentForm');
        if (departmentForm) {
            departmentForm.reset();
        }
        
        // Update modal title and buttons
        document.getElementById('departmentModalTitle').textContent = 'Add New Department';
        document.getElementById('addDepartmentBtn').style.display = 'block';
        document.getElementById('updateDepartmentBtn').style.display = 'none';
        
        // Clear hidden dept_id
        document.getElementById('dept_id').value = '';
        
        this.showModal(this.departmentModal);
    },

    openEditDepartmentModal: function(button) {
        // Get department data from data attributes
        const deptId = button.getAttribute('data-dept-id');
        const deptCode = button.getAttribute('data-dept-code');
        const deptName = button.getAttribute('data-dept-name');

        // Fill form with department data
        document.getElementById('dept_id').value = deptId;
        document.getElementById('dept_code').value = deptCode;
        document.getElementById('dept_name').value = deptName;

        // Update modal title and buttons
        document.getElementById('departmentModalTitle').textContent = 'Edit Department';
        document.getElementById('addDepartmentBtn').style.display = 'none';
        document.getElementById('updateDepartmentBtn').style.display = 'block';
        
        this.showModal(this.departmentModal);
    },

    showDeleteConfirmation: function(deptId, deptName) {
        this.deleteDeptId = deptId;
        this.deleteDeptName = deptName;
        
        const message = document.getElementById('deleteMessage');
        if (message) {
            message.textContent = `Are you sure you want to delete "${deptName}"? This action cannot be undone.`;
        }

        if (this.deleteConfirmation) {
            this.deleteConfirmation.classList.add('show');
        }
    },

    hideDeleteConfirmation: function() {
        this.deleteDeptId = null;
        this.deleteDeptName = null;
        
        if (this.deleteConfirmation) {
            this.deleteConfirmation.classList.remove('show');
        }
    },

    executeDelete: function() {
        if (this.deleteDeptId) {
            // Set the department ID in the hidden form and submit it
            const deleteDeptId = document.getElementById('deleteDeptId');
            const deleteForm = document.getElementById('deleteDepartmentForm');
            
            if (deleteDeptId && deleteForm) {
                deleteDeptId.value = this.deleteDeptId;
                deleteForm.submit();
            }
        }
        this.hideDeleteConfirmation();
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
        // Close department modal
        if (this.departmentModal) {
            this.departmentModal.classList.remove('show');
            setTimeout(() => {
                this.departmentModal.style.display = "none";
            }, 300);
        }
        
        // Also close delete confirmation
        this.hideDeleteConfirmation();
    },

    // Search functionality
    initializeSearch: function() {
        this.searchInput = document.getElementById('searchDepartments');
        this.searchButton = document.getElementById('searchButton');
        this.clearSearchBtn = document.getElementById('clearSearch');
        this.searchStats = document.getElementById('searchStats');
        this.departmentRows = document.querySelectorAll('tbody tr');
        this.totalDepartments = this.departmentRows.length;

        this.setupSearchEvents();
        this.updateSearchStats(this.totalDepartments, this.totalDepartments);
    },

    setupSearchEvents: function() {
        // Search input event (real-time search)
        if (this.searchInput) {
            this.searchInput.addEventListener('input', (e) => {
                this.filterDepartments(e.target.value);
            });
            
            // Also allow Enter key to trigger search
            this.searchInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    this.filterDepartments(e.target.value);
                }
            });
        }

        // Search button event
        if (this.searchButton) {
            this.searchButton.addEventListener('click', () => {
                this.filterDepartments(this.searchInput.value);
            });
        }

        // Clear search event
        if (this.clearSearchBtn) {
            this.clearSearchBtn.addEventListener('click', () => {
                this.clearSearch();
            });
        }
    },

    filterDepartments: function(searchTerm) {
        const term = searchTerm.toLowerCase().trim();
        let visibleCount = 0;

        this.departmentRows.forEach(row => {
            const code = row.cells[0].textContent.toLowerCase();
            const name = row.cells[1].textContent.toLowerCase();

            const matches = code.includes(term) || name.includes(term);
            
            if (matches) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        this.updateSearchStats(visibleCount, this.totalDepartments);
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
        this.departmentRows.forEach(row => {
            row.style.display = '';
        });

        this.updateSearchStats(this.totalDepartments, this.totalDepartments);
        this.toggleNoResults(false);
        this.toggleClearSearch(false);
        
        // Focus the search input after clearing
        if (this.searchInput) {
            this.searchInput.focus();
        }
    },

    clearSearchInput: function() {
        if (this.searchInput) {
            this.searchInput.value = '';
        }
        this.toggleClearSearch(false);
    },

    updateSearchStats: function(visible, total) {
        if (this.searchStats) {
            this.searchStats.textContent = `Showing ${visible} of ${total} departments`;
        }
    },

    toggleNoResults: function(show) {
        let noResults = document.getElementById('noResults');
        
        if (show && !noResults) {
            noResults = document.createElement('tr');
            noResults.id = 'noResults';
            noResults.innerHTML = `
                <td colspan="3">
                    <div class="no-results">
                        <div class="no-results-icon">🔍</div>
                        <h3>No departments found</h3>
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
    if (typeof DepartmentManager !== 'undefined') {
        DepartmentManager.init();
    }
});

// Add keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Escape key to close modals
    if (e.key === 'Escape') {
        const departmentModal = document.getElementById('departmentModal');
        const deleteConfirmation = document.getElementById('deleteConfirmation');
        
        if (departmentModal && departmentModal.style.display === 'block') {
            DepartmentManager.closeModals();
        } else if (deleteConfirmation && deleteConfirmation.classList.contains('show')) {
            DepartmentManager.hideDeleteConfirmation();
        }
    }
    
    // Ctrl/Cmd + F to focus search
    if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
        e.preventDefault();
        const searchInput = document.getElementById('searchDepartments');
        if (searchInput) {
            searchInput.focus();
        }
    }
    
    // Ctrl/Cmd + N to open new department modal
    if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
        e.preventDefault();
        const openBtn = document.getElementById('openDepartmentModal');
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
    let exportUrl = `department_export_${type}.php`;
    
    console.log('Export URL:', exportUrl); // Debug log
    
    // Open export in new window
    window.open(exportUrl, '_blank');
}