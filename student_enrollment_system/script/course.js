// course.js

// Modal Functions
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden'; // Prevent background scrolling
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto'; // Restore scrolling
    }
}

// Close modal when clicking outside the modal content
document.addEventListener('DOMContentLoaded', function() {
    // Add click event listeners to all modals
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeModal(modal.id);
            }
        });
    });

    // Add escape key listener to close modals
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const openModals = document.querySelectorAll('.modal[style*="display: block"]');
            openModals.forEach(modal => {
                closeModal(modal.id);
            });
        }
    });

    // Add form submission handlers
    const addCourseForm = document.getElementById('add-course-form');
    if (addCourseForm) {
        addCourseForm.addEventListener('submit', function(e) {
            // You can add form validation here
            console.log('Add course form submitted');
        });
    }

    const editCourseForm = document.getElementById('edit-course-form');
    if (editCourseForm) {
        editCourseForm.addEventListener('submit', function(e) {
            // You can add form validation here
            console.log('Edit course form submitted');
        });
    }
});

// Edit Course Function
function editCourse(courseId) {
    // Show loading overlay
    const loadingOverlay = document.getElementById('editLoadingOverlay');
    if (loadingOverlay) {
        loadingOverlay.style.display = 'flex';
    }

    // Open the modal first
    openModal('edit-course-modal');

    // Simulate API call to fetch course data (replace with actual AJAX call)
    setTimeout(() => {
        // This is a mock implementation - replace with actual data fetching
        fetchCourseData(courseId).then(courseData => {
            // Populate the form fields
            document.getElementById('edit_course_id').value = courseData.course_id;
            document.getElementById('edit_course_code').value = courseData.course_code;
            document.getElementById('edit_course_title').value = courseData.course_title;
            document.getElementById('edit_units').value = courseData.units;
            document.getElementById('edit_lecture_hours').value = courseData.lecture_hours || '';
            document.getElementById('edit_lab_hours').value = courseData.lab_hours || '';
            document.getElementById('edit_dept_id').value = courseData.dept_id;

            // Hide loading overlay
            if (loadingOverlay) {
                loadingOverlay.style.display = 'none';
            }
        }).catch(error => {
            console.error('Error fetching course data:', error);
            showToast('Error loading course data', 'error');
            if (loadingOverlay) {
                loadingOverlay.style.display = 'none';
            }
        });
    }, 500);
}

// Mock function to fetch course data (replace with actual AJAX call)
function fetchCourseData(courseId) {
    return new Promise((resolve, reject) => {
        // This should be replaced with actual API call
        // For now, we'll try to get data from the table row
        const courseRow = document.querySelector(`tr[data-course-id="${courseId}"]`);
        if (courseRow) {
            const cells = courseRow.querySelectorAll('td');
            const courseData = {
                course_id: courseId,
                course_code: courseRow.querySelector('.course-code').textContent,
                course_title: cells[1].textContent,
                units: parseFloat(courseRow.querySelector('.course-units').textContent),
                lecture_hours: cells[3].querySelector('.hours-badge') ? 
                    parseInt(cells[3].querySelector('.hours-badge').textContent) : 0,
                lab_hours: cells[4].querySelector('.hours-badge') ? 
                    parseInt(cells[4].querySelector('.hours-badge').textContent) : 0,
                dept_id: '' // You'll need to get this from a data attribute or make an API call
            };
            resolve(courseData);
        } else {
            reject('Course not found');
        }
    });
}

// Print Function
function printCourseTable() {
    window.print();
}

// Toast Notification Function
function showToast(message, type = 'info') {
    const toastContainer = document.getElementById('toastContainer');
    if (!toastContainer) return;

    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.textContent = message;

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

// Form Validation
function validateCourseForm(formData) {
    const errors = [];

    if (!formData.course_code || formData.course_code.trim() === '') {
        errors.push('Course code is required');
    }

    if (!formData.course_title || formData.course_title.trim() === '') {
        errors.push('Course title is required');
    }

    if (!formData.units || formData.units <= 0) {
        errors.push('Valid units are required');
    }

    if (!formData.dept_id) {
        errors.push('Department is required');
    }

    return errors;
}

// Initialize the page
document.addEventListener('DOMContentLoaded', function() {
    console.log('Course management system initialized');
    
    // Check if there are any URL parameters that might indicate success messages
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('success') === 'true') {
        showToast('Operation completed successfully!', 'success');
    }
});