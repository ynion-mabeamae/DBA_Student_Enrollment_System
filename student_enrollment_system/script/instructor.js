// instructor.js - Instructor Management System JavaScript

const InstructorManager = {
    // Initialize the instructor management system
    init: function(isEditing = false) {
        this.initializeModal();
        this.initializeNotifications();
        this.initializeSearch();
        this.initializeDeleteConfirmation();
        
        if (isEditing) {
            this.openEditModal();
        }
    },

    // Modal functionality
    initializeModal: function() {
        this.modal = document.getElementById("instructorModal");
        this.openBtn = document.getElementById("openInstructorModal");
        this.closeBtn = document.querySelector(".close");
        this.cancelBtn = document.getElementById("cancelInstructor");

        this.setupModalEvents();
    },

    setupModalEvents: function() {
        // Open modal for adding new instructor
        if (this.openBtn) {
            this.openBtn.addEventListener('click', () => {
                this.openNewInstructorModal();
            });
        }

        // Close modal when clicking X
        if (this.closeBtn) {
            this.closeBtn.addEventListener('click', () => {
                this.closeModal();
            });
        }

        // Close modal when clicking cancel button
        if (this.cancelBtn) {
            this.cancelBtn.addEventListener('click', () => {
                this.closeModal();
            });
        }

        // Close modal when clicking outside of it
        document.addEventListener('click', (event) => {
            if (event.target === this.modal) {
                this.closeModal();
            }
        });

        // Close modal after successful form submission
        const instructorForm = document.getElementById('instructorForm');
        if (instructorForm) {
            instructorForm.addEventListener('submit', () => {
                setTimeout(() => {
                    this.closeModal();
                }, 1000);
            });
        }
    },

    openNewInstructorModal: function() {
        // Reset form for new instructor
        const instructorForm = document.getElementById('instructorForm');
        if (instructorForm) {
            instructorForm.reset();
        }
        
        // Update modal title
        const modalTitle = document.querySelector('.modal-header h2');
        if (modalTitle) {
            modalTitle.textContent = 'Add New Instructor';
        }
        
        // Show add button, hide update button
        this.toggleFormButtons('add');
        
        this.showModal();
    },

    openEditModal: function() {
        // Show update button, hide add button
        this.toggleFormButtons('update');
        
        this.showModal();
    },

    toggleFormButtons: function(mode) {
        const addButton = document.querySelector('button[name="add_instructor"]');
        const updateButton = document.querySelector('button[name="update_instructor"]');
        
        if (mode === 'add') {
            if (addButton) addButton.style.display = 'block';
            if (updateButton) updateButton.style.display = 'none';
        } else if (mode === 'update') {
            if (addButton) addButton.style.display = 'none';
            if (updateButton) updateButton.style.display = 'block';
        }
    },

    showModal: function() {
        if (this.modal) {
            this.modal.style.display = "block";
            // Add animation class
            setTimeout(() => {
                this.modal.classList.add('modal-show');
            }, 10);
        }
    },

    closeModal: function() {
        if (this.modal) {
            this.modal.classList.remove('modal-show');
            setTimeout(() => {
                this.modal.style.display = "none";
            }, 300);
            
            // Remove edit parameters from URL
            if (window.location.search.includes('edit_id')) {
                window.history.replaceState({}, document.title, window.location.pathname);
            }
        }
    },

    // Search functionality
    initializeSearch: function() {
        this.searchInput = document.getElementById('searchInstructors');
        this.clearSearchBtn = document.getElementById('clearSearch');
        this.searchStats = document.getElementById('searchStats');
        this.instructorRows = document.querySelectorAll('tbody tr');
        this.totalInstructors = this.instructorRows.length;

        this.setupSearchEvents();
        this.updateSearchStats(this.totalInstructors, this.totalInstructors);
    },

    setupSearchEvents: function() {
        // Search input event
        if (this.searchInput) {
            this.searchInput.addEventListener('input', (e) => {
                this.filterInstructors(e.target.value);
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

    filterInstructors: function(searchTerm) {
        const term = searchTerm.toLowerCase().trim();
        let visibleCount = 0;

        this.instructorRows.forEach(row => {
            const name = row.cells[0].textContent.toLowerCase();
            const email = row.cells[1].textContent.toLowerCase();
            const department = row.cells[2].textContent.toLowerCase();

            const matches = name.includes(term) || email.includes(term) || department.includes(term);
            
            if (matches) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        this.updateSearchStats(visibleCount, this.totalInstructors);
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

        this.instructorRows.forEach(row => {
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

        this.updateSearchStats(visibleCount, this.totalInstructors);
        this.toggleNoResults(visibleCount === 0);
        this.clearSearchInput();
    },

    clearSearch: function() {
        if (this.searchInput) {
            this.searchInput.value = '';
        }
        
        // Show all rows
        this.instructorRows.forEach(row => {
            row.style.display = '';
        });

        // Remove active class from filter buttons
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.classList.remove('active');
        });

        this.updateSearchStats(this.totalInstructors, this.totalInstructors);
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
            this.searchStats.textContent = `Showing ${visible} of ${total} instructors`;
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
                        <h3>No instructors found</h3>
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

    // Delete confirmation functionality
    initializeDeleteConfirmation: function() {
        this.deleteConfirmation = document.getElementById('deleteConfirmation');
        this.confirmDeleteBtn = document.getElementById('confirmDelete');
        this.cancelDeleteBtn = document.getElementById('cancelDelete');
        this.deleteInstructorId = null;
        this.deleteInstructorName = null;

        this.setupDeleteConfirmationEvents();
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

        // Add click event listeners to all delete buttons
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('delete-btn')) {
                const instructorId = e.target.getAttribute('data-instructor-id');
                const instructorName = e.target.getAttribute('data-instructor-name');
                this.showDeleteConfirmation(instructorId, instructorName);
            }
        });
    },

    showDeleteConfirmation: function(instructorId, instructorName) {
        this.deleteInstructorId = instructorId;
        this.deleteInstructorName = instructorName;
        
        const message = document.getElementById('deleteMessage');
        if (message) {
            message.textContent = `Are you sure you want to delete "${instructorName}"? This action cannot be undone.`;
        }

        if (this.deleteConfirmation) {
            this.deleteConfirmation.classList.add('show');
        }
    },

    hideDeleteConfirmation: function() {
        this.deleteInstructorId = null;
        this.deleteInstructorName = null;
        
        if (this.deleteConfirmation) {
            this.deleteConfirmation.classList.remove('show');
        }
    },

    executeDelete: function() {
        if (this.deleteInstructorId) {
            // Set the instructor ID in the hidden form and submit it
            const deleteInstructorId = document.getElementById('deleteInstructorId');
            const deleteForm = document.getElementById('deleteInstructorForm');
            
            if (deleteInstructorId && deleteForm) {
                deleteInstructorId.value = this.deleteInstructorId;
                deleteForm.submit();
            }
        }
        this.hideDeleteConfirmation();
    },

    // Notification functionality
    initializeNotifications: function() {
        this.setupNotificationEvents();
        this.autoHideNotifications();
        this.showNotifications();
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
    // Check if we're in editing mode
    const urlParams = new URLSearchParams(window.location.search);
    const isEditing = urlParams.has('edit_id');
    
    if (typeof InstructorManager !== 'undefined') {
        InstructorManager.init(isEditing);
    }
});

// Add keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Escape key to close modal or delete confirmation
    if (e.key === 'Escape') {
        const modal = document.getElementById('instructorModal');
        const deleteConfirm = document.getElementById('deleteConfirmation');
        
        if (modal && modal.style.display === 'block') {
            InstructorManager.closeModal();
        } else if (deleteConfirm && deleteConfirm.classList.contains('show')) {
            InstructorManager.hideDeleteConfirmation();
        }
    }
    
    // Ctrl/Cmd + F to focus search
    if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
        e.preventDefault();
        const searchInput = document.getElementById('searchInstructors');
        if (searchInput) {
            searchInput.focus();
        }
    }
    
    // Ctrl/Cmd + N to open new instructor modal
    if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
        e.preventDefault();
        const openBtn = document.getElementById('openInstructorModal');
        if (openBtn) {
            openBtn.click();
        }
    }
});