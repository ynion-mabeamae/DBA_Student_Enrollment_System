const PrerequisiteManager = {
    // Initialize the prerequisite management system
    init: function() {
        console.log('Initializing Prerequisite Manager...');
        this.initializeModals();
        this.initializeNotifications();
        this.initializeSearch();
        this.initializeDeleteConfirmation();
        this.setupEventListeners();
        this.showNotifications();
    },

    // Modal functionality
    initializeModals: function() {
        this.prerequisiteModal = document.getElementById("prerequisiteModal");
        this.deleteConfirmation = document.getElementById("deleteConfirmation");
        this.openBtn = document.getElementById("openPrerequisiteModal");
        this.closeBtns = document.querySelectorAll(".close");
        this.cancelBtns = document.querySelectorAll(".btn-cancel");
    },

    initializeDeleteConfirmation: function() {
        this.confirmDeleteBtn = document.getElementById('confirmDelete');
        this.cancelDeleteBtn = document.getElementById('cancelDelete');
        this.deletePrereqId = null;
        this.deleteCourseName = null;
        this.deletePrereqName = null;

        this.setupDeleteConfirmationEvents();
    },

    setupEventListeners: function() {
        // Open add prerequisite modal
        if (this.openBtn) {
            this.openBtn.addEventListener('click', () => {
                this.openAddPrerequisiteModal();
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
            if (event.target === this.prerequisiteModal) {
                this.closeModals();
            }
            if (event.target === this.deleteConfirmation) {
                this.hideDeleteConfirmation();
            }
        });

        // Edit button events
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('edit-btn')) {
                this.openEditPrerequisiteModal(e.target);
            }
        });

        // Delete button events
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('delete-btn')) {
                const prereqId = e.target.getAttribute('data-prereq-id');
                const courseName = e.target.getAttribute('data-course-name');
                const prereqName = e.target.getAttribute('data-prereq-name');
                this.showDeleteConfirmation(prereqId, courseName, prereqName);
            }
        });

        // Form validation
        const prerequisiteForm = document.getElementById('prerequisiteForm');
        if (prerequisiteForm) {
            prerequisiteForm.addEventListener('submit', (e) => {
                if (!this.validatePrerequisiteForm()) {
                    e.preventDefault();
                }
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

    openAddPrerequisiteModal: function() {
        // Reset form
        const prerequisiteForm = document.getElementById('prerequisiteForm');
        if (prerequisiteForm) {
            prerequisiteForm.reset();
        }
        
        // Update modal title and buttons
        document.getElementById('prerequisiteModalTitle').textContent = 'Add New Prerequisite';
        document.getElementById('addPrerequisiteBtn').style.display = 'block';
        document.getElementById('updatePrerequisiteBtn').style.display = 'none';
        
        // Clear hidden prereq_id
        document.getElementById('prereq_id').value = '';
        
        this.showModal(this.prerequisiteModal);
    },

    openEditPrerequisiteModal: function(button) {
        // Get prerequisite data from data attributes
        const prereqId = button.getAttribute('data-prereq-id');
        const courseId = button.getAttribute('data-course-id');
        const prereqCourseId = button.getAttribute('data-prereq-course-id');

        console.log('Editing prerequisite:', { prereqId, courseId, prereqCourseId });

        // Fill form with prerequisite data
        document.getElementById('prereq_id').value = prereqId;
        document.getElementById('course_id').value = courseId;
        document.getElementById('prereq_course_id').value = prereqCourseId;

        // Update modal title and buttons
        document.getElementById('prerequisiteModalTitle').textContent = 'Edit Prerequisite';
        document.getElementById('addPrerequisiteBtn').style.display = 'none';
        document.getElementById('updatePrerequisiteBtn').style.display = 'block';
        
        this.showModal(this.prerequisiteModal);
    },

    showDeleteConfirmation: function(prereqId, courseName, prereqName) {
        this.deletePrereqId = prereqId;
        this.deleteCourseName = courseName;
        this.deletePrereqName = prereqName;
        
        const message = document.getElementById('deleteMessage');
        if (message) {
            message.textContent = `Are you sure you want to delete the prerequisite relationship between "${courseName}" and "${prereqName}"? This action cannot be undone.`;
        }

        if (this.deleteConfirmation) {
            this.deleteConfirmation.style.display = 'flex';
            setTimeout(() => {
                this.deleteConfirmation.classList.add('show');
            }, 10);
        }
    },

    hideDeleteConfirmation: function() {
        this.deletePrereqId = null;
        this.deleteCourseName = null;
        this.deletePrereqName = null;
        
        if (this.deleteConfirmation) {
            this.deleteConfirmation.classList.remove('show');
            setTimeout(() => {
                this.deleteConfirmation.style.display = 'none';
            }, 300);
        }
    },

    executeDelete: function() {
        console.log('Executing delete for prerequisite ID:', this.deletePrereqId);
        
        if (this.deletePrereqId) {
            const prereqId = parseInt(this.deletePrereqId, 10); // Ensure it's an integer
            if (isNaN(prereqId)) {
                console.error('Invalid prerequisite ID:', this.deletePrereqId);
                alert('Invalid prerequisite ID. Please try again.');
                this.hideDeleteConfirmation();
                return;
            }

            const formData = new FormData();
            formData.append('prereq_id', prereqId);
            formData.append('delete_prerequisite', '1');
            
            console.log('Sending delete request with data:', {
                prereq_id: prereqId,
                delete_prerequisite: '1'
            });
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Delete response status:', response.status);
                console.log('Delete response URL:', response.url);
                this.hideDeleteConfirmation();
                // Reload the page to show updated list and any session messages
                window.location.reload();
            })
            .catch(error => {
                console.error('Delete error:', error);
                alert('Error deleting prerequisite: ' + error.message);
                this.hideDeleteConfirmation();
            });
        } else {
            console.error('No prerequisite ID to delete');
            alert('No prerequisite selected for deletion.');
            this.hideDeleteConfirmation();
        }
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
        // Close prerequisite modal
        if (this.prerequisiteModal) {
            this.prerequisiteModal.classList.remove('show');
            setTimeout(() => {
                this.prerequisiteModal.style.display = "none";
                this.resetPrerequisiteForm();
            }, 300);
        }
        
        // Also close delete confirmation
        this.hideDeleteConfirmation();
    },

    resetPrerequisiteForm: function() {
        const form = document.getElementById('prerequisiteForm');
        if (form) {
            form.reset();
            document.getElementById('prerequisiteModalTitle').textContent = 'Add New Prerequisite';
            document.getElementById('addPrerequisiteBtn').style.display = 'block';
            document.getElementById('updatePrerequisiteBtn').style.display = 'none';
            document.getElementById('prereq_id').value = '';
        }
    },

    validatePrerequisiteForm: function() {
        const courseId = document.getElementById('course_id').value;
        const prereqCourseId = document.getElementById('prereq_course_id').value;
        
        if (courseId === prereqCourseId) {
            alert('A course cannot be a prerequisite for itself. Please select different courses.');
            return false;
        }
        
        return true;
    },

    // Search functionality
    initializeSearch: function() {
        this.searchInput = document.getElementById('searchPrerequisites');
        this.searchButton = document.getElementById('searchButton');
        this.clearSearchBtn = document.getElementById('clearSearch');
        this.searchStats = document.getElementById('searchStats');
        this.prerequisiteRows = document.querySelectorAll('tbody tr');
        this.totalPrerequisites = this.prerequisiteRows.length;

        this.setupSearchEvents();
        this.updateSearchStats(this.totalPrerequisites, this.totalPrerequisites);
    },

    setupSearchEvents: function() {
        // Search button event
        if (this.searchButton) {
            this.searchButton.addEventListener('click', () => {
                this.filterPrerequisites();
            });
        }

        // Search input event
        if (this.searchInput) {
            this.searchInput.addEventListener('keyup', (e) => {
                if (e.key === 'Enter') {
                    this.filterPrerequisites();
                }
            });
        }

        // Clear search event
        if (this.clearSearchBtn) {
            this.clearSearchBtn.addEventListener('click', () => {
                this.clearSearch();
            });
        }
    },

    filterPrerequisites: function() {
        const searchTerm = this.searchInput.value.toLowerCase().trim();
        let visibleCount = 0;

        this.prerequisiteRows.forEach(row => {
            if (row.cells.length >= 2) {
                const courseCode = row.cells[0].querySelector('.course-code')?.textContent.toLowerCase() || '';
                const courseTitle = row.cells[0].querySelector('.course-title')?.textContent.toLowerCase() || '';
                const prereqCode = row.cells[1].querySelector('.course-code')?.textContent.toLowerCase() || '';
                const prereqTitle = row.cells[1].querySelector('.course-title')?.textContent.toLowerCase() || '';
                
                const matches = courseCode.includes(searchTerm) || 
                               courseTitle.includes(searchTerm) || 
                               prereqCode.includes(searchTerm) || 
                               prereqTitle.includes(searchTerm);
                
                row.style.display = matches ? '' : 'none';
                if (matches) visibleCount++;
            }
        });

        this.updateSearchStats(visibleCount, this.totalPrerequisites);
        this.toggleNoResults(visibleCount === 0 && searchTerm.length > 0);
        this.toggleClearSearch(searchTerm.length > 0);
    },

    clearSearch: function() {
        if (this.searchInput) {
            this.searchInput.value = '';
        }
        
        // Show all rows
        this.prerequisiteRows.forEach(row => {
            row.style.display = '';
        });

        this.updateSearchStats(this.totalPrerequisites, this.totalPrerequisites);
        this.toggleNoResults(false);
        this.toggleClearSearch(false);
    },

    updateSearchStats: function(visible, total) {
        if (this.searchStats) {
            this.searchStats.textContent = `Showing ${visible} of ${total} prerequisites`;
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
                        <h3>No prerequisites found</h3>
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
    },

    closeNotification: function(notification) {
        if (notification) {
            notification.classList.remove('show');
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
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
    if (typeof PrerequisiteManager !== 'undefined') {
        PrerequisiteManager.init();
    }
});

// Add keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Escape key to close modals
    if (e.key === 'Escape') {
        const prerequisiteModal = document.getElementById('prerequisiteModal');
        const deleteConfirmation = document.getElementById('deleteConfirmation');
        
        if (prerequisiteModal && prerequisiteModal.style.display === 'block') {
            PrerequisiteManager.closeModals();
        } else if (deleteConfirmation && deleteConfirmation.classList.contains('show')) {
            PrerequisiteManager.hideDeleteConfirmation();
        }
    }
    
    // Ctrl/Cmd + F to focus search
    if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
        e.preventDefault();
        const searchInput = document.getElementById('searchPrerequisites');
        if (searchInput) {
            searchInput.focus();
        }
    }
    
    // Ctrl/Cmd + N to open new prerequisite modal
    if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
        e.preventDefault();
        const openBtn = document.getElementById('openPrerequisiteModal');
        if (openBtn) {
            openBtn.click();
        }
    }
});

// Debug function - call this in browser console to test delete (unchanged)
window.debugDelete = function(prereqId) {
    console.log('Manual delete test for ID:', prereqId);
    
    const formData = new FormData();
    formData.append('prereq_id', prereqId);
    formData.append('delete_prerequisite', '1');
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Debug delete response:', response);
        window.location.reload();
    })
    .catch(error => {
        console.error('Debug delete error:', error);
        alert('Error: ' + error.message);
    });
};

// Export data function
function exportData(type) {
    // Build export URL
    let exportUrl = `prerequisite_export_${type}.php`;
    
    console.log('Export URL:', exportUrl); // Debug log
    
    // Open export in new window
    window.open(exportUrl, '_blank');
}