// enrollment.js - Enrollment Management System JavaScript

const EnrollmentManager = {
  // Initialize the enrollment management system
  init: function(isEditing = false) {
      this.initializeModal();
      this.initializeNotifications();
      
      if (isEditing) {
          this.openEditModal();
      }
  },

  // Modal functionality
  initializeModal: function() {
      this.modal = document.getElementById("enrollmentModal");
      this.openBtn = document.getElementById("openEnrollmentModal");
      this.closeBtn = document.querySelector(".close");
      this.cancelBtn = document.getElementById("cancelEnrollment");
      this.gradeField = document.querySelector('.grade-field');

      this.setupModalEvents();
  },

  setupModalEvents: function() {
      // Open modal for adding new enrollment
      if (this.openBtn) {
          this.openBtn.addEventListener('click', () => {
              this.openNewEnrollmentModal();
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
      const enrollmentForm = document.getElementById('enrollmentForm');
      if (enrollmentForm) {
          enrollmentForm.addEventListener('submit', () => {
              setTimeout(() => {
                  this.closeModal();
              }, 1000);
          });
      }
  },

  openNewEnrollmentModal: function() {
      // Reset form for new enrollment
      const enrollmentForm = document.getElementById('enrollmentForm');
      if (enrollmentForm) {
          enrollmentForm.reset();
      }
      
      // Update modal title
      const modalTitle = document.querySelector('.modal-header h2');
      if (modalTitle) {
          modalTitle.textContent = 'Add New Enrollment';
      }
      
      // Hide grade field for new enrollment
      if (this.gradeField) {
          this.gradeField.style.display = 'none';
      }
      
      // Show student dropdown for new enrollment
      const studentField = document.querySelector('.form-group:has(#student_id)');
      if (studentField) {
          studentField.style.display = 'block';
      }
      
      // Remove any existing student display
      const existingDisplay = document.querySelector('.student-display');
      if (existingDisplay) {
          existingDisplay.remove();
      }
      
      // Show add button, hide update button
      this.toggleFormButtons('add');
      
      this.showModal();
  },

  openEditModal: function() {
      // Show grade field for editing
      if (this.gradeField) {
          this.gradeField.style.display = 'block';
      }
      
      // Show update button, hide add button
      this.toggleFormButtons('update');
      
      this.showModal();
  },

  toggleFormButtons: function(mode) {
      const addButton = document.querySelector('button[name="add_enrollment"]');
      const updateButton = document.querySelector('button[name="update_enrollment"]');
      
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
  },

  // Utility functions
  showSuccessMessage: function(message) {
      this.showNotification(message, 'success');
  },

  showErrorMessage: function(message) {
      this.showNotification(message, 'error');
  },

  showNotification: function(message, type) {
      // Create notification element
      const notification = document.createElement('div');
      notification.className = `notification ${type}`;
      notification.innerHTML = `
          <div class="notification-content">
              <span class="notification-icon">${type === 'success' ? '✓' : '⚠'}</span>
              <span class="notification-message">${message}</span>
              <button class="notification-close">&times;</button>
          </div>
          <div class="notification-progress"></div>
      `;

      document.body.appendChild(notification);

      // Add event listener to close button
      notification.querySelector('.notification-close').addEventListener('click', () => {
          this.closeNotification(notification);
      });

      // Show and auto-hide
      setTimeout(() => {
          notification.classList.add('show');
      }, 100);

      setTimeout(() => {
          this.closeNotification(notification);
      }, 5000);
  },

  // Student display functionality
  updateStudentDisplay: function(studentId) {
      const studentSelect = document.getElementById('student_id');
      const selectedOption = studentSelect.options[studentSelect.selectedIndex];
      const studentInfo = selectedOption.textContent.split(' - ');
      
      if (studentInfo.length === 2 && studentId) {
          const studentDisplay = document.querySelector('.student-display');
          if (!studentDisplay) {
              this.createStudentDisplay(studentInfo[1], studentInfo[0]);
          }
      }
  },

  createStudentDisplay: function(studentName, studentNo) {
      const studentDisplay = document.createElement('div');
      studentDisplay.className = 'student-display';
      studentDisplay.innerHTML = `
          <div class="student-info-card">
              <h3>Selected Student</h3>
              <div class="student-details">
                  <div class="detail-item">
                      <span class="label">Student Name:</span>
                      <span class="value">${studentName}</span>
                  </div>
                  <div class="detail-item">
                      <span class="label">Student No:</span>
                      <span class="value">${studentNo}</span>
                  </div>
              </div>
          </div>
      `;
      
      const form = document.getElementById('enrollmentForm');
      form.parentNode.insertBefore(studentDisplay, form);
      
      // Hide student dropdown
      const studentField = document.querySelector('.form-group:has(#student_id)');
      if (studentField) {
          studentField.style.display = 'none';
      }
  }
};

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Check if we're in editing mode
    const urlParams = new URLSearchParams(window.location.search);
    const isEditing = urlParams.has('edit_id');
    
    EnrollmentManager.init(isEditing);
});

// Add event listener for student selection in modal
document.addEventListener('DOMContentLoaded', function() {
    const studentSelect = document.getElementById('student_id');
    if (studentSelect) {
        studentSelect.addEventListener('change', function(e) {
            if (e.target.value) {
                EnrollmentManager.updateStudentDisplay(e.target.value);
            } else {
                const studentDisplay = document.querySelector('.student-display');
                if (studentDisplay) {
                    studentDisplay.remove();
                    // Show student dropdown again
                    const studentField = document.querySelector('.form-group:has(#student_id)');
                    if (studentField) {
                        studentField.style.display = 'block';
                    }
                }
            }
        });
    }
});

// Delete Confirmation Modal Functionality
document.addEventListener('DOMContentLoaded', function() {
    const deleteModal = document.getElementById('deleteConfirmation');
    const confirmDeleteBtn = document.getElementById('confirmDelete');
    const cancelDeleteBtn = document.getElementById('cancelDelete');
    const deleteForm = document.getElementById('deleteEnrollmentForm');
    const deleteMessage = document.getElementById('deleteMessage');

    // Delete button functionality
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('delete-btn')) {
            const enrollmentId = e.target.getAttribute('data-enrollment-id');
            const courseCode = e.target.getAttribute('data-course-code');
            const courseTitle = e.target.getAttribute('data-course-title');
            const studentName = e.target.getAttribute('data-student-name');
            
            // Set delete message
            deleteMessage.textContent = `Are you sure you want to delete the enrollment for "${courseCode} - ${courseTitle}" for student "${studentName}"? This action cannot be undone.`;
            
            // Set delete form values
            document.getElementById('deleteEnrollmentId').value = enrollmentId;
            
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