// student.js - Updated for Modal Forms

// Modal Functions
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
        
        // Clear form when opening add modal
        if (modalId === 'add-student-modal') {
            document.getElementById('addStudentForm').reset();
        }
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}

// Print Function
function printStudentTable() {
    window.print();
}

// Edit Student Function
function editStudent(studentId) {
    // Show loading state
    const submitBtn = document.querySelector('#editStudentForm button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Loading...';
    submitBtn.disabled = true;

    // Fetch student data via AJAX
    fetch(`?page=students&get_student=1&student_id=${studentId}`)
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success && data.student) {
            const student = data.student;
            
            // Populate the edit form fields
            document.getElementById('edit_student_id').value = student.student_id;
            document.getElementById('edit_student_no').value = student.student_no;
            document.getElementById('edit_last_name').value = student.last_name;
            document.getElementById('edit_first_name').value = student.first_name;
            document.getElementById('edit_email').value = student.email;
            document.getElementById('edit_gender').value = student.gender;
            document.getElementById('edit_birthdate').value = student.birthdate;
            document.getElementById('edit_year_level').value = student.year_level;
            document.getElementById('edit_program_id').value = student.program_id;
            
            // Open the modal
            openModal('edit-student-modal');
        } else {
            showPopup(data.message || 'Error loading student data', 'error');
        }
    })
    .catch(error => {
        console.error('Error fetching student data:', error);
        showPopup('Error loading student data. Please try again.', 'error');
    })
    .finally(() => {
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    });
}

// Bulk Actions Validation
function validateBulkActions() {
    const selectedStudents = document.querySelectorAll('.row-select:checked');
    const bulkAction = document.getElementById('bulkActionSelect').value;

    if (selectedStudents.length === 0) {
        alert('Please select at least one student');
        return false;
    }

    if (!bulkAction) {
        alert('Please select a bulk action');
        return false;
    }

    if (bulkAction === 'delete') {
        if (!confirm(`Are you sure you want to delete ${selectedStudents.length} selected student(s)?`)) {
            return false;
        }
    }
    
    return true;
}

// Auto-hide messages after 5 seconds
function autoHideMessages() {
    const messages = document.querySelectorAll('.toast');
    messages.forEach(message => {
        setTimeout(() => {
            message.style.opacity = '0';
            setTimeout(() => {
                if (message.parentNode) {
                    message.parentNode.removeChild(message);
                }
            }, 300);
        }, 5000);
    });
}

// Initialize the page
document.addEventListener('DOMContentLoaded', function() {
    console.log('Student management system initialized');

    // Close modal when clicking outside
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeModal(modal.id);
            }
        });
    });

    // Escape key to close modals
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const openModals = document.querySelectorAll('.modal[style*="display: block"]');
            openModals.forEach(modal => {
                closeModal(modal.id);
            });
        }
    });

    // Select all functionality
    const selectAllCheckboxes = document.querySelectorAll('.select-all');
    selectAllCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const rowSelects = document.querySelectorAll('.row-select');
            rowSelects.forEach(rowSelect => {
                rowSelect.checked = this.checked;
            });
        });
    });

    // Bulk actions form submission
    const bulkActionsForm = document.getElementById('bulkActionsForm');
    if (bulkActionsForm) {
        bulkActionsForm.addEventListener('submit', function(e) {
            if (!validateBulkActions()) {
                e.preventDefault();
            }
        });
    }

    // Auto-hide success/error messages
    autoHideMessages();
});

// Popup Message Function
function showPopup(message, type = 'info') {
    const popupOverlay = document.createElement('div');
    popupOverlay.className = 'popup-overlay';
    popupOverlay.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 2000;
    `;

    const popupContent = document.createElement('div');
    popupContent.className = `popup-content popup-${type}`;
    popupContent.style.cssText = `
        background: white;
        padding: 2rem;
        border-radius: 10px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        text-align: center;
        min-width: 300px;
        max-width: 400px;
    `;

    const messageElement = document.createElement('div');
    messageElement.style.cssText = `
        font-size: 1.1rem;
        font-weight: 600;
        margin-bottom: 1.5rem;
        color: #333;
    `;
    messageElement.textContent = message;

    const okButton = document.createElement('button');
    okButton.textContent = 'OK';
    okButton.style.cssText = `
        background: #4361ee;
        color: white;
        border: none;
        padding: 0.75rem 2rem;
        border-radius: 5px;
        font-weight: 600;
        cursor: pointer;
        font-size: 1rem;
    `;

    okButton.onclick = function() {
        document.body.removeChild(popupOverlay);
    };

    popupContent.appendChild(messageElement);
    popupContent.appendChild(okButton);
    popupOverlay.appendChild(popupContent);
    document.body.appendChild(popupOverlay);

    popupOverlay.onclick = function(e) {
        if (e.target === popupOverlay) {
            document.body.removeChild(popupOverlay);
        }
    };
}

// Delete Confirmation Modal Functionality
document.addEventListener('DOMContentLoaded', function() {
    const deleteModal = document.getElementById('deleteConfirmation');
    const confirmDeleteBtn = document.getElementById('confirmDelete');
    const cancelDeleteBtn = document.getElementById('cancelDelete');
    const deleteForm = document.getElementById('deleteStudentForm');
    const deleteMessage = document.getElementById('deleteMessage');

    // Delete button functionality
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('delete-btn')) {
            const studentId = e.target.getAttribute('data-student-id');
            const studentNo = e.target.getAttribute('data-student-no');
            const studentName = e.target.getAttribute('data-student-name');
            
            // Set delete message
            deleteMessage.textContent = `Are you sure you want to delete student "${studentNo} - ${studentName}"? This action cannot be undone.`;
            
            // Set delete form values
            document.getElementById('deleteStudentId').value = studentId;
            
            // Show delete confirmation modal
            showDeleteModal();
        }
    });

    // Delete confirmation
    confirmDeleteBtn.addEventListener('click', function() {
        deleteForm.submit();
    });

    // Cancel delete
    cancelDeleteBtn.addEventListener('click', function() {
        hideDeleteModal();
    });

    // Close modal when clicking outside
    deleteModal.addEventListener('click', function(event) {
        if (event.target === deleteModal) {
            hideDeleteModal();
        }
    });

    function showDeleteModal() {
        deleteModal.style.display = 'flex';
        setTimeout(() => {
            deleteModal.style.opacity = '1';
        }, 10);
    }

    function hideDeleteModal() {
        deleteModal.style.opacity = '0';
        setTimeout(() => {
            deleteModal.style.display = 'none';
        }, 300);
    }
});

// Update delete confirmation messages
function setupDeleteButtons() {
    const deleteButtons = document.querySelectorAll('.delete-btn');
    const deleteConfirmation = document.getElementById('deleteConfirmation');
    const deleteMessage = document.getElementById('deleteMessage');
    const confirmDelete = document.getElementById('confirmDelete');
    const cancelDelete = document.getElementById('cancelDelete');
    const deleteStudentForm = document.getElementById('deleteStudentForm');
    const deleteStudentId = document.getElementById('deleteStudentId');

    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const studentId = this.getAttribute('data-student-id');
            const studentNo = this.getAttribute('data-student-no');
            const studentName = this.getAttribute('data-student-name');
            
            deleteMessage.textContent = `Are you sure you want to archive student ${studentNo} - ${studentName}? This student will be moved to archived records.`;
            deleteStudentId.value = studentId;
            deleteConfirmation.style.display = 'flex';
        });
    });

    confirmDelete.addEventListener('click', function() {
        deleteStudentForm.submit();
    });

    cancelDelete.addEventListener('click', function() {
        deleteConfirmation.style.display = 'none';
    });
}