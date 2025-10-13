// section.js - Section Management System JavaScript

const SectionManager = {
    // Initialize the section management system
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
        this.sectionModal = document.getElementById("sectionModal");
        this.deleteConfirmation = document.getElementById("deleteConfirmation");
        this.openBtn = document.getElementById("openSectionModal");
        this.closeBtns = document.querySelectorAll(".close");
        this.cancelBtns = document.querySelectorAll(".btn-cancel");
    },

    initializeDeleteConfirmation: function() {
        this.confirmDeleteBtn = document.getElementById('confirmDelete');
        this.cancelDeleteBtn = document.getElementById('cancelDelete');
        this.deleteSectionId = null;
        this.deleteSectionCode = null;

        this.setupDeleteConfirmationEvents();
    },

    setupEventListeners: function() {
        // Open add section modal
        if (this.openBtn) {
            this.openBtn.addEventListener('click', () => {
                this.openAddSectionModal();
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
            if (event.target === this.sectionModal) {
                this.closeModals();
            }
            if (event.target === this.deleteConfirmation) {
                this.hideDeleteConfirmation();
            }
        });

        // Edit button events
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('edit-btn')) {
                this.openEditSectionModal(e.target);
            }
        });

        // Delete button events
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('delete-btn')) {
                const sectionId = e.target.getAttribute('data-section-id');
                const sectionCode = e.target.getAttribute('data-section-code');
                this.showDeleteConfirmation(sectionId, sectionCode);
            }
        });

        // Close modals on successful form submission
        const sectionForm = document.getElementById('sectionForm');
        if (sectionForm) {
            sectionForm.addEventListener('submit', () => {
                setTimeout(() => {
                    this.closeModals();
                }, 1000);
            });
        }

        const deleteForm = document.getElementById('deleteSectionForm');
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

    openAddSectionModal: function() {
        // Reset form
        const sectionForm = document.getElementById('sectionForm');
        if (sectionForm) {
            sectionForm.reset();
        }
        
        // Update modal title and buttons
        document.getElementById('sectionModalTitle').textContent = 'Add New Section';
        document.getElementById('addSectionBtn').style.display = 'block';
        document.getElementById('updateSectionBtn').style.display = 'none';
        
        // Clear hidden section_id
        document.getElementById('section_id').value = '';
        
        this.showModal(this.sectionModal);
    },

    openEditSectionModal: function(button) {
        // Get section data from data attributes
        const sectionId = button.getAttribute('data-section-id');
        const sectionCode = button.getAttribute('data-section-code');
        const courseId = button.getAttribute('data-course-id');
        const termId = button.getAttribute('data-term-id');
        const instructorId = button.getAttribute('data-instructor-id');
        const dayPattern = button.getAttribute('data-day-pattern');
        const startTime = button.getAttribute('data-start-time');
        const endTime = button.getAttribute('data-end-time');
        const roomId = button.getAttribute('data-room-id');
        const maxCapacity = button.getAttribute('data-max-capacity');

        // Fill form with section data
        document.getElementById('section_id').value = sectionId;
        document.getElementById('section_code').value = sectionCode;
        document.getElementById('course_id').value = courseId;
        document.getElementById('term_id').value = termId;
        document.getElementById('instructor_id').value = instructorId;
        document.getElementById('day_pattern').value = dayPattern;
        document.getElementById('start_time').value = startTime;
        document.getElementById('end_time').value = endTime;
        document.getElementById('room_id').value = roomId;
        document.getElementById('max_capacity').value = maxCapacity;

        // Update modal title and buttons
        document.getElementById('sectionModalTitle').textContent = 'Edit Section';
        document.getElementById('addSectionBtn').style.display = 'none';
        document.getElementById('updateSectionBtn').style.display = 'block';
        
        this.showModal(this.sectionModal);
    },

    showDeleteConfirmation: function(sectionId, sectionCode) {
        this.deleteSectionId = sectionId;
        this.deleteSectionCode = sectionCode;
        
        const message = document.getElementById('deleteMessage');
        if (message) {
            message.textContent = `Are you sure you want to delete "${sectionCode}"? This action cannot be undone.`;
        }

        if (this.deleteConfirmation) {
            this.deleteConfirmation.classList.add('show');
        }
    },

    hideDeleteConfirmation: function() {
        this.deleteSectionId = null;
        this.deleteSectionCode = null;
        
        if (this.deleteConfirmation) {
            this.deleteConfirmation.classList.remove('show');
        }
    },

    executeDelete: function() {
        if (this.deleteSectionId) {
            // Set the section ID in the hidden form and submit it
            const deleteSectionId = document.getElementById('deleteSectionId');
            const deleteForm = document.getElementById('deleteSectionForm');
            
            if (deleteSectionId && deleteForm) {
                deleteSectionId.value = this.deleteSectionId;
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
        // Close section modal
        if (this.sectionModal) {
            this.sectionModal.classList.remove('show');
            setTimeout(() => {
                this.sectionModal.style.display = "none";
            }, 300);
        }
        
        // Also close delete confirmation
        this.hideDeleteConfirmation();
    },

    // Search functionality
    initializeSearch: function() {
        this.searchInput = document.getElementById('searchSections');
        this.searchButton = document.getElementById('searchButton');
        this.clearSearchBtn = document.getElementById('clearSearch');
        this.searchStats = document.getElementById('searchStats');
        this.sectionRows = document.querySelectorAll('tbody tr');
        this.totalSections = this.sectionRows.length;

        this.setupSearchEvents();
        this.updateSearchStats(this.totalSections, this.totalSections);
    },

    setupSearchEvents: function() {
        // Search input event (real-time search)
        if (this.searchInput) {
            this.searchInput.addEventListener('input', (e) => {
                this.filterSections(e.target.value);
            });
            
            // Also allow Enter key to trigger search
            this.searchInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    this.filterSections(e.target.value);
                }
            });
        }

        // Search button event
        if (this.searchButton) {
            this.searchButton.addEventListener('click', () => {
                this.filterSections(this.searchInput.value);
            });
        }

        // Clear search event
        if (this.clearSearchBtn) {
            this.clearSearchBtn.addEventListener('click', () => {
                this.clearSearch();
            });
        }
    },

    filterSections: function(searchTerm) {
        const term = searchTerm.toLowerCase().trim();
        let visibleCount = 0;

        this.sectionRows.forEach(row => {
            const sectionCode = row.cells[0].textContent.toLowerCase();
            const course = row.cells[1].textContent.toLowerCase();
            const termCode = row.cells[2].textContent.toLowerCase();
            const instructor = row.cells[3].textContent.toLowerCase();
            const schedule = row.cells[4].textContent.toLowerCase();
            const room = row.cells[5].textContent.toLowerCase();

            const matches = sectionCode.includes(term) || course.includes(term) || 
                           termCode.includes(term) || instructor.includes(term) || 
                           schedule.includes(term) || room.includes(term);
            
            if (matches) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        this.updateSearchStats(visibleCount, this.totalSections);
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
        this.sectionRows.forEach(row => {
            row.style.display = '';
        });

        this.updateSearchStats(this.totalSections, this.totalSections);
        this.toggleNoResults(false);
        this.toggleClearSearch(false);
        
        // Focus the search input after clearing
        if (this.searchInput) {
            this.searchInput.focus();
        }
    },

    updateSearchStats: function(visible, total) {
        if (this.searchStats) {
            this.searchStats.textContent = `Showing ${visible} of ${total} sections`;
        }
    },

    toggleNoResults: function(show) {
        let noResults = document.getElementById('noResults');
        
        if (show && !noResults) {
            noResults = document.createElement('tr');
            noResults.id = 'noResults';
            noResults.innerHTML = `
                <td colspan="8">
                    <div class="no-results">
                        <div class="no-results-icon">🔍</div>
                        <h3>No sections found</h3>
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
    if (typeof SectionManager !== 'undefined') {
        SectionManager.init();
    }
});

// Add keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Escape key to close modals
    if (e.key === 'Escape') {
        const sectionModal = document.getElementById('sectionModal');
        const deleteConfirmation = document.getElementById('deleteConfirmation');
        
        if (sectionModal && sectionModal.style.display === 'block') {
            SectionManager.closeModals();
        } else if (deleteConfirmation && deleteConfirmation.classList.contains('show')) {
            SectionManager.hideDeleteConfirmation();
        }
    }
    
    // Ctrl/Cmd + F to focus search
    if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
        e.preventDefault();
        const searchInput = document.getElementById('searchSections');
        if (searchInput) {
            searchInput.focus();
        }
    }
    
    // Ctrl/Cmd + N to open new section modal
    if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
        e.preventDefault();
        const openBtn = document.getElementById('openSectionModal');
        if (openBtn) {
            openBtn.click();
        }
    }
});

// Simple Modal Functions
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'block';
        setTimeout(() => {
            modal.classList.add('show');
        }, 10);
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('show');
        setTimeout(() => {
            modal.style.display = 'none';
        }, 300);
        document.body.style.overflow = 'auto';
    }
}

// Close modals when clicking outside
document.addEventListener('DOMContentLoaded', function() {
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeModal(modal.id);
            }
        });
    });

    // Close modals with escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const openModals = document.querySelectorAll('.modal.show');
            openModals.forEach(modal => {
                closeModal(modal.id);
            });
        }
    });
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
    let exportUrl = `section_export_${type}.php`;
    
    console.log('Export URL:', exportUrl); // Debug log
    
    // Open export in new window
    window.open(exportUrl, '_blank');
}