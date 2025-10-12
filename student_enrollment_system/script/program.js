// program.js - Program Management System JavaScript

const ProgramManager = {
    // Initialize the program management system
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
        this.programModal = document.getElementById("programModal");
        this.deleteModal = document.getElementById("deleteModal");
        this.openBtn = document.getElementById("openProgramModal");
        this.closeBtns = document.querySelectorAll(".close");
        this.cancelBtns = document.querySelectorAll(".btn-cancel");
    },

    initializeDeleteConfirmation: function() {
        this.deleteConfirmation = document.getElementById('deleteConfirmation');
        this.confirmDeleteBtn = document.getElementById('confirmDelete');
        this.cancelDeleteBtn = document.getElementById('cancelDelete');
        this.deleteProgramId = null;
        this.deleteProgramName = null;

        this.setupDeleteConfirmationEvents();
    },

    setupEventListeners: function() {
        // Open add program modal
        if (this.openBtn) {
            this.openBtn.addEventListener('click', () => {
                this.openAddProgramModal();
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
            if (event.target === this.programModal) {
                this.closeModals();
            }
            if (event.target === this.deleteModal) {
                this.closeModals();
            }
            if (event.target === this.deleteConfirmation) {
                this.hideDeleteConfirmation();
            }
        });

        // Edit button events
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('edit-btn')) {
                this.openEditProgramModal(e.target);
            }
        });

        // Delete button events
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('delete-btn')) {
                const programId = e.target.getAttribute('data-program-id');
                const programName = e.target.getAttribute('data-program-name');
                this.showDeleteConfirmation(programId, programName);
            }
        });

        // Close modals on successful form submission
        const programForm = document.getElementById('programForm');
        if (programForm) {
            programForm.addEventListener('submit', () => {
                setTimeout(() => {
                    this.closeModals();
                }, 1000);
            });
        }

        const deleteForm = document.getElementById('deleteForm');
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

    openAddProgramModal: function() {
        // Reset form
        const programForm = document.getElementById('programForm');
        if (programForm) {
            programForm.reset();
        }
        
        // Update modal title and buttons
        document.getElementById('programModalTitle').textContent = 'Add New Program';
        document.getElementById('addProgramBtn').style.display = 'block';
        document.getElementById('updateProgramBtn').style.display = 'none';
        
        // Clear hidden program_id
        document.getElementById('program_id').value = '';
        
        this.showModal(this.programModal);
    },

    openEditProgramModal: function(button) {
        // Get program data from data attributes
        const programId = button.getAttribute('data-program-id');
        const programCode = button.getAttribute('data-program-code');
        const programName = button.getAttribute('data-program-name');
        const deptId = button.getAttribute('data-dept-id');

        // Fill form with program data
        document.getElementById('program_id').value = programId;
        document.getElementById('program_code').value = programCode;
        document.getElementById('program_name').value = programName;
        document.getElementById('dept_id').value = deptId || '';

        // Update modal title and buttons
        document.getElementById('programModalTitle').textContent = 'Edit Program';
        document.getElementById('addProgramBtn').style.display = 'none';
        document.getElementById('updateProgramBtn').style.display = 'block';
        
        this.showModal(this.programModal);
    },

    openDeleteModal: function(button) {
        const programId = button.getAttribute('data-program-id');
        const programName = button.getAttribute('data-program-name');

        // Set delete message and program ID
        document.getElementById('deleteMessage').textContent = 
            `Are you sure you want to delete "${programName}"? This action cannot be undone.`;
        document.getElementById('delete_program_id').value = programId;

        this.showModal(this.deleteModal);
    },

    showDeleteConfirmation: function(programId, programName) {
        this.deleteProgramId = programId;
        this.deleteProgramName = programName;
        
        const message = document.getElementById('deleteMessage');
        if (message) {
            message.textContent = `Are you sure you want to delete "${programName}"? This action cannot be undone.`;
        }

        if (this.deleteConfirmation) {
            this.deleteConfirmation.classList.add('show');
        }
    },

    hideDeleteConfirmation: function() {
        this.deleteProgramId = null;
        this.deleteProgramName = null;
        
        if (this.deleteConfirmation) {
            this.deleteConfirmation.classList.remove('show');
        }
    },

    executeDelete: function() {
        if (this.deleteProgramId) {
            // Set the program ID in the hidden form and submit it
            const deleteProgramId = document.getElementById('deleteProgramId');
            const deleteForm = document.getElementById('deleteProgramForm');
            
            if (deleteProgramId && deleteForm) {
                deleteProgramId.value = this.deleteProgramId;
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
        // Close program modal
        if (this.programModal) {
            this.programModal.classList.remove('show');
            setTimeout(() => {
                this.programModal.style.display = "none";
            }, 300);
        }

        // Close delete modal
        if (this.deleteModal) {
            this.deleteModal.classList.remove('show');
            setTimeout(() => {
                this.deleteModal.style.display = "none";
            }, 300);
        }
        
        // Also close delete confirmation
        this.hideDeleteConfirmation();
    },

    // Search functionality
    initializeSearch: function() {
        this.searchInput = document.getElementById('searchPrograms');
        this.clearSearchBtn = document.getElementById('clearSearch');
        this.searchStats = document.getElementById('searchStats');
        this.programRows = document.querySelectorAll('tbody tr');
        this.totalPrograms = this.programRows.length;

        this.setupSearchEvents();
        this.updateSearchStats(this.totalPrograms, this.totalPrograms);
    },

    setupSearchEvents: function() {
        // Search input event
        if (this.searchInput) {
            this.searchInput.addEventListener('input', (e) => {
                this.filterPrograms(e.target.value);
            });
        }

        // Clear search event
        if (this.clearSearchBtn) {
            this.clearSearchBtn.addEventListener('click', () => {
                this.clearSearch();
            });
        }

        // Quick filter events
        const filterButtons = document.querySelectorAll('.filter-btn');
        filterButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                const filter = e.target.dataset.filter;
                this.applyQuickFilter(filter, e.target);
            });
        });
    },

    filterPrograms: function(searchTerm) {
        const term = searchTerm.toLowerCase().trim();
        let visibleCount = 0;

        this.programRows.forEach(row => {
            const code = row.cells[0].textContent.toLowerCase();
            const name = row.cells[1].textContent.toLowerCase();
            const department = row.cells[2].textContent.toLowerCase();

            const matches = code.includes(term) || name.includes(term) || department.includes(term);
            
            if (matches) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        this.updateSearchStats(visibleCount, this.totalPrograms);
        this.toggleNoResults(visibleCount === 0 && term.length > 0);
        this.toggleClearSearch(term.length > 0);
    },

    applyQuickFilter: function(filter, button) {
        // Remove active class from all filter buttons
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.classList.remove('active');
        });

        // Add active class to clicked button
        button.classList.add('active');

        let visibleCount = 0;

        this.programRows.forEach(row => {
            const department = row.cells[2].textContent.toLowerCase();
            let matches = true;

            if (filter === 'assigned') {
                matches = !department.includes('not assigned');
            } else if (filter === 'unassigned') {
                matches = department.includes('not assigned');
            }
            // 'all' filter shows everything

            if (matches) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        this.updateSearchStats(visibleCount, this.totalPrograms);
        this.toggleNoResults(visibleCount === 0);
        this.clearSearchInput();
    },

    clearSearch: function() {
        if (this.searchInput) {
            this.searchInput.value = '';
        }
        
        // Show all rows
        this.programRows.forEach(row => {
            row.style.display = '';
        });

        // Remove active class from filter buttons
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.classList.remove('active');
        });

        this.updateSearchStats(this.totalPrograms, this.totalPrograms);
        this.toggleNoResults(false);
        this.toggleClearSearch(false);
    },

    clearSearchInput: function() {
        if (this.searchInput) {
            this.searchInput.value = '';
        }
        this.toggleClearSearch(false);
    },

    updateSearchStats: function(visible, total) {
        if (this.searchStats) {
            this.searchStats.textContent = `Showing ${visible} of ${total} programs`;
        }
    },

    toggleNoResults: function(show) {
        let noResults = document.getElementById('noResults');
        
        if (show && !noResults) {
            noResults = document.createElement('tr');
            noResults.id = 'noResults';
            noResults.innerHTML = `
                <td colspan="4">
                    <div class="no-results">
                        <div class="no-results-icon">🔍</div>
                        <h3>No programs found</h3>
                        <p>Try adjusting your search terms or filters</p>
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
    if (typeof ProgramManager !== 'undefined') {
        ProgramManager.init();
    }
});

// Add keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Escape key to close modals
    if (e.key === 'Escape') {
        const programModal = document.getElementById('programModal');
        const deleteModal = document.getElementById('deleteModal');
        const deleteConfirmation = document.getElementById('deleteConfirmation');
        
        if (programModal && programModal.style.display === 'block') {
            ProgramManager.closeModals();
        } else if (deleteModal && deleteModal.style.display === 'block') {
            ProgramManager.closeModals();
        } else if (deleteConfirmation && deleteConfirmation.classList.contains('show')) {
            ProgramManager.hideDeleteConfirmation();
        }
    }
    
    // Ctrl/Cmd + F to focus search
    if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
        e.preventDefault();
        const searchInput = document.getElementById('searchPrograms');
        if (searchInput) {
            searchInput.focus();
        }
    }
    
    // Ctrl/Cmd + N to open new program modal
    if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
        e.preventDefault();
        const openBtn = document.getElementById('openProgramModal');
        if (openBtn) {
            openBtn.click();
        }
    }
});

// Export data function
function exportData(type) {
    // Build export URL
    let exportUrl = `program_export_${type}.php`;
    
    console.log('Export URL:', exportUrl); // Debug log
    
    // Open export in new window
    window.open(exportUrl, '_blank');
}