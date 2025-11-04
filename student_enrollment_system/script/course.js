// Modal Functions
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}

// Edit Course Function
function editCourse(courseId) {
    // Show loading overlay
    const loadingOverlay = document.getElementById('editLoadingOverlay');
    if (loadingOverlay) {
        loadingOverlay.style.display = 'flex';
    }

    // Open the modal first
    openModal('edit-course-modal');

    // Get course data from table row
    const courseRow = document.querySelector(`tr[data-course-id="${courseId}"]`);
    if (courseRow) {
        const cells = courseRow.querySelectorAll('td');
        
        // Populate the form fields directly from table data
        document.getElementById('edit_course_id').value = courseId;
        document.getElementById('edit_course_code').value = courseRow.querySelector('.course-code').textContent;
        document.getElementById('edit_course_title').value = cells[1].textContent;
        document.getElementById('edit_units').value = parseFloat(courseRow.querySelector('.course-units').textContent);
        
        // Get lecture hours
        const lectureBadge = cells[3].querySelector('.hours-badge');
        document.getElementById('edit_lecture_hours').value = lectureBadge ? parseInt(lectureBadge.textContent) : 0;
        
        // Get lab hours
        const labBadge = cells[4].querySelector('.hours-badge');
        document.getElementById('edit_lab_hours').value = labBadge ? parseInt(labBadge.textContent) : 0;
        
        // Get department - this might need AJAX call if not available in table
        // For now, we'll keep the current selection
    }

    // Hide loading overlay after a short delay
    setTimeout(() => {
        if (loadingOverlay) {
            loadingOverlay.style.display = 'none';
        }
    }, 500);
}

// Toast Notification Function
function showToast(message, type = 'info') {
    const toastContainer = document.getElementById('toastContainer');
    if (!toastContainer) return;

    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    
    // Add icon based on type
    let icon = 'ℹ️';
    switch (type) {
        case 'success':
            icon = '✅';
            break;
        case 'error':
            icon = '❌';
            break;
        case 'warning':
            icon = '⚠️';
            break;
    }
    toast.innerHTML = `${icon} ${message}`;

    toastContainer.appendChild(toast);

    // Auto remove after 5 seconds
    setTimeout(() => {
        toast.remove();
    }, 5000);
}

// Initialize the page
document.addEventListener('DOMContentLoaded', function() {
    console.log('Course management system initialized');
    
    // Delete Confirmation Modal Functionality
    const deleteModal = document.getElementById('deleteConfirmation');
    const confirmDeleteBtn = document.getElementById('confirmDelete');
    const cancelDeleteBtn = document.getElementById('cancelDelete');
    const deleteForm = document.getElementById('deleteCourseForm');
    const deleteMessage = document.getElementById('deleteMessage');

    // Delete button functionality
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('delete-btn')) {
            const courseId = e.target.getAttribute('data-course-id');
            const courseCode = e.target.getAttribute('data-course-code');
            const courseTitle = e.target.getAttribute('data-course-title');
            
            // Set delete message
            deleteMessage.textContent = `Are you sure you want to archive the course "${courseCode} - ${courseTitle}"? This course will be moved to archived records.`;
            
            // Set delete form values
            document.getElementById('deleteCourseId').value = courseId;
            
            // Show delete confirmation modal
            deleteModal.style.display = 'flex';
            setTimeout(() => {
                deleteModal.style.opacity = '1';
            }, 10);
        }
    });

    // Delete confirmation
    confirmDeleteBtn.addEventListener('click', function() {
        deleteForm.submit();
    });

    // Cancel delete
    cancelDeleteBtn.addEventListener('click', function() {
        deleteModal.style.opacity = '0';
        setTimeout(() => {
            deleteModal.style.display = 'none';
        }, 300);
    });

    // Close modal when clicking outside
    deleteModal.addEventListener('click', function(event) {
        if (event.target === deleteModal) {
            deleteModal.style.opacity = '0';
            setTimeout(() => {
                deleteModal.style.display = 'none';
            }, 300);
        }
    });

    // Close modals when clicking outside
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
            const openModals = document.querySelectorAll('.modal[style*="display: block"]');
            openModals.forEach(modal => {
                closeModal(modal.id);
            });
            
            // Also close delete confirmation
            deleteModal.style.opacity = '0';
            setTimeout(() => {
                deleteModal.style.display = 'none';
            }, 300);
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
    // Get current filter parameters
    const urlParams = new URLSearchParams(window.location.search);
    const showArchived = urlParams.get('show_archived') === 'true';
    const search = urlParams.get('search') || '';
    const department = urlParams.get('department') || '';

    // Build export URL
    let exportUrl = `course_export_${type}.php?`;

    if (showArchived) {
        exportUrl += 'show_archived=true&';
    }

    if (search) {
        exportUrl += `search=${encodeURIComponent(search)}&`;
    }

    if (department) {
        exportUrl += `department=${department}&`;
    }

    // Remove trailing & or ?
    exportUrl = exportUrl.replace(/[&?]$/, '');

    // Open export in new window
    window.open(exportUrl, '_blank');
}

// Function to populate duplicate errors in the modal
function populateDuplicateErrors(errors) {
    const errorList = document.getElementById('duplicateCourseErrorList');
    if (errorList && errors) {
        errorList.innerHTML = '';
        errors.forEach(error => {
            const li = document.createElement('li');
            li.className = 'error-item';
            li.innerHTML = `<i class="fas fa-exclamation-triangle"></i> ${error}`;
            errorList.appendChild(li);
        });
    }
}

// Function to go back to course form from duplicate modal
function goBackToCourseForm() {
    closeModal('duplicate-course-modal');
    openModal('add-course-modal');
}

// Logout Modal Event Listeners
document.addEventListener('DOMContentLoaded', function() {
    const logoutModal = document.getElementById('logoutConfirmation');
    const confirmLogoutBtn = document.getElementById('confirmLogout');
    const cancelLogoutBtn = document.getElementById('cancelLogout');

    if (confirmLogoutBtn) {
        confirmLogoutBtn.addEventListener('click', function() {
            window.location.href = '?logout=true';
        });
    }

    if (cancelLogoutBtn) {
        cancelLogoutBtn.addEventListener('click', function() {
            closeLogoutModal();
        });
    }

    if (logoutModal) {
        // Close modal when clicking outside
        logoutModal.addEventListener('click', function(event) {
            if (event.target === logoutModal) {
                closeLogoutModal();
            }
        });
    }
});

// Logout Modal Functions
function openLogoutModal() {
    const modal = document.getElementById('logoutConfirmation');
    modal.style.display = 'flex';
    setTimeout(() => {
        modal.style.opacity = '1';
    }, 10);
}

function closeLogoutModal() {
    const modal = document.getElementById('logoutConfirmation');
    modal.style.opacity = '0';
    setTimeout(() => {
        modal.style.display = 'none';
    }, 300);
}