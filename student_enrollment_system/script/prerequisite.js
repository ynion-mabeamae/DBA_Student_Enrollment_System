    document.addEventListener('DOMContentLoaded', function() {
        // Modal elements
        const modal = document.getElementById('prerequisiteModal');
        const openModalBtn = document.getElementById('openPrerequisiteModal');
        const closeModalBtn = document.querySelector('.close');
        const cancelBtn = document.getElementById('cancelPrerequisite');
        const addBtn = document.getElementById('addPrerequisiteBtn');
        const updateBtn = document.getElementById('updatePrerequisiteBtn');
        const modalTitle = document.getElementById('prerequisiteModalTitle');
        const form = document.getElementById('prerequisiteForm');

        // Duplicate prerequisite modal elements
        const duplicateModal = document.getElementById('duplicate-prerequisite-modal');
        const duplicateCloseBtn = duplicateModal.querySelector('.close-modal');
        const duplicateCancelBtn = document.getElementById('cancelDuplicateBtn');
        const duplicateErrorsList = document.getElementById('duplicate-errors-list');
        
        // Delete confirmation elements
        const deleteModal = document.getElementById('deleteConfirmation');
        const confirmDeleteBtn = document.getElementById('confirmDelete');
        const cancelDeleteBtn = document.getElementById('cancelDelete');
        const deleteForm = document.getElementById('deletePrerequisiteForm');
        const deleteMessage = document.getElementById('deleteMessage');

        // Open modal for adding new prerequisite
        openModalBtn.addEventListener('click', function() {
            resetForm();
            modal.style.display = 'block';
            setTimeout(() => {
                modal.classList.add('show');
            }, 10);
        });

        // Close modal
        function closeModal() {
            modal.classList.remove('show');
            setTimeout(() => {
                modal.style.display = 'none';
            }, 300);
        }

        closeModalBtn.addEventListener('click', closeModal);
        cancelBtn.addEventListener('click', closeModal);

        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            if (event.target === modal) {
                closeModal();
            }
            if (event.target === deleteModal) {
                hideDeleteModal();
            }
            if (event.target === duplicateModal) {
                hideDuplicateModal();
            }
        });

        // Duplicate modal close functionality
        duplicateCloseBtn.addEventListener('click', hideDuplicateModal);
        duplicateCancelBtn.addEventListener('click', hideDuplicateModal);

        // Go to Form button functionality
        const goToFormBtn = document.getElementById('goToFormBtn');
        goToFormBtn.addEventListener('click', function() {
            hideDuplicateModal();
            // Scroll to the form modal
            const formModal = document.getElementById('prerequisiteModal');
            formModal.scrollIntoView({ behavior: 'smooth', block: 'center' });
        });

        function showDuplicateModal(duplicates) {
            // Clear previous errors
            duplicateErrorsList.innerHTML = '';

            // Add duplicate entries to the list
            duplicates.forEach(duplicate => {
                const li = document.createElement('li');
                li.textContent = `Course: ${duplicate.course_code} Prerequisite: ${duplicate.prereq_course_code}`;
                duplicateErrorsList.appendChild(li);
            });

            // Show modal
            duplicateModal.style.display = 'block';
            setTimeout(() => {
                duplicateModal.classList.add('modal-show');
            }, 10);
        }

        function hideDuplicateModal() {
            duplicateModal.classList.remove('modal-show');
            setTimeout(() => {
                duplicateModal.style.display = 'none';
            }, 300);
        }

        // Edit button functionality
        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', function() {
                const courseId = this.getAttribute('data-course-id');
                const prereqCourseId = this.getAttribute('data-prereq-course-id');
                
                // Set form values
                document.getElementById('course_id').value = courseId;
                document.getElementById('prereq_course_id').value = prereqCourseId;
                document.getElementById('course_id_old').value = courseId;
                document.getElementById('prereq_course_id_old').value = prereqCourseId;
                
                // Update UI for edit mode
                modalTitle.textContent = 'Edit Course Prerequisite';
                addBtn.style.display = 'none';
                updateBtn.style.display = 'inline-block';
                
                // Show modal
                modal.style.display = 'block';
                setTimeout(() => {
                    modal.classList.add('show');
                }, 10);
            });
        });

        // Delete button functionality
        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', function() {
                const courseId = this.getAttribute('data-course-id');
                const prereqCourseId = this.getAttribute('data-prereq-course-id');
                const courseName = this.getAttribute('data-course-name');
                const prereqCourseName = this.getAttribute('data-prereq-course-name');
                
                // Set delete message
                deleteMessage.textContent = `Are you sure you want to delete the prerequisite relationship between "${courseName}" and "${prereqCourseName}"? This action cannot be undone.`;
                
                // Set delete form values
                document.getElementById('deleteCourseId').value = courseId;
                document.getElementById('deletePrereqCourseId').value = prereqCourseId;
                
                // Show delete confirmation
                showDeleteModal();
            });
        });

        // Delete confirmation
        confirmDeleteBtn.addEventListener('click', function() {
            deleteForm.submit();
        });

        cancelDeleteBtn.addEventListener('click', hideDeleteModal);

        function showDeleteModal() {
            deleteModal.style.display = 'flex';
            setTimeout(() => {
                deleteModal.classList.add('show');
            }, 10);
        }

        function hideDeleteModal() {
            deleteModal.classList.remove('show');
            setTimeout(() => {
                deleteModal.style.display = 'none';
            }, 300);
        }

        function resetForm() {
            form.reset();
            modalTitle.textContent = 'Add New Course Prerequisite';
            addBtn.style.display = 'inline-block';
            updateBtn.style.display = 'none';
            document.getElementById('course_id_old').value = '';
            document.getElementById('prereq_course_id_old').value = '';
        }

        // Notification auto-hide
        const notifications = document.querySelectorAll('.notification');
        notifications.forEach(notification => {
            setTimeout(() => {
                notification.classList.add('show');
            }, 100);

            const closeBtn = notification.querySelector('.notification-close');
            closeBtn.addEventListener('click', function() {
                notification.classList.remove('show');
                setTimeout(() => {
                    notification.remove();
                }, 300);
            });

            // Auto-hide after 5 seconds
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.classList.remove('show');
                    setTimeout(() => {
                        if (notification.parentNode) {
                            notification.remove();
                        }
                    }, 300);
                }
            }, 5000);
        });

        // Form submission with duplicate check
        form.addEventListener('submit', function(e) {
            e.preventDefault();

            const courseId = document.getElementById('course_id').value;
            const prereqCourseId = document.getElementById('prereq_course_id').value;
            const courseIdOld = document.getElementById('course_id_old').value;
            const prereqCourseIdOld = document.getElementById('prereq_course_id_old').value;

            // Check for duplicates via AJAX
            const formData = new FormData();
            formData.append('check_duplicates', '1');
            formData.append('course_id', courseId);
            formData.append('prereq_course_id', prereqCourseId);
            if (courseIdOld && prereqCourseIdOld) {
                formData.append('course_id_old', courseIdOld);
                formData.append('prereq_course_id_old', prereqCourseIdOld);
            }

            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(duplicates => {
                if (duplicates.length > 0) {
                    // Show duplicate modal
                    showDuplicateModal(duplicates);
                } else {
                    // No duplicates, submit the form
                    form.submit();
                }
            })
            .catch(error => {
                console.error('Error checking duplicates:', error);
                // If AJAX fails, submit anyway
                form.submit();
            });
        });

        // Search functionality
        const searchInput = document.getElementById('searchPrerequisites');
        const searchButton = document.getElementById('searchButton');
        const clearSearch = document.getElementById('clearSearch');
        const searchStats = document.getElementById('searchStats');
        const tableRows = document.querySelectorAll('tbody tr');

        function performSearch() {
            const searchTerm = searchInput.value.toLowerCase().trim();
            let visibleCount = 0;

            tableRows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            searchStats.textContent = `Showing ${visibleCount} of ${tableRows.length} prerequisites`;
            clearSearch.style.display = searchTerm ? 'block' : 'none';
        }

        searchButton.addEventListener('click', performSearch);
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                performSearch();
            }
        });

        clearSearch.addEventListener('click', function() {
            searchInput.value = '';
            performSearch();
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
let exportUrl = `prerequisite_export_${type}.php`;

console.log('Export URL:', exportUrl); // Debug log

// Open export in new window
window.open(exportUrl, '_blank');
}