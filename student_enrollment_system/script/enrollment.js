// enrollment.js - Enrollment Management System JavaScript

const EnrollmentManager = {
  // Initialize the enrollment management system
  init: function(isEditing = false, showArchived = false) {
      this.initializeModal();
      this.initializeSearch();
      this.initializeDeleteConfirmation();
      
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

  // Search functionality
  initializeSearch: function() {
      this.setupSearchEvents();
  },

  // Delete confirmation functionality
  initializeDeleteConfirmation: function() {
      this.deleteModal = document.getElementById("deleteConfirmation");
      this.confirmDeleteBtn = document.getElementById("confirmDelete");
      this.cancelDeleteBtn = document.getElementById("cancelDelete");
      this.deleteForm = document.getElementById("deleteEnrollmentForm");
      this.deleteMessage = document.getElementById("deleteMessage");

      this.setupDeleteEvents();
  },

  setupDeleteEvents: function() {
      // Delete button functionality
      document.addEventListener('click', (e) => {
          if (e.target.classList.contains('delete-btn')) {
              const enrollmentId = e.target.getAttribute('data-enrollment-id');
              const courseCode = e.target.getAttribute('data-course-code');
              const courseTitle = e.target.getAttribute('data-course-title');
              const studentName = e.target.getAttribute('data-student-name');
              
              // Set delete message
              this.deleteMessage.textContent = `Are you sure you want to delete the enrollment for "${courseCode} - ${courseTitle}" for student "${studentName}"? This action will move the enrollment to archived records.`;
              
              // Set delete form values
              document.getElementById('deleteEnrollmentId').value = enrollmentId;
              
              // Show delete confirmation modal
              this.showDeleteModal();
          }
      });

      // Delete confirmation
      if (this.confirmDeleteBtn) {
          this.confirmDeleteBtn.addEventListener('click', () => {
              this.deleteForm.submit();
          });
      }

      // Cancel delete
      if (this.cancelDeleteBtn) {
          this.cancelDeleteBtn.addEventListener('click', () => {
              this.hideDeleteModal();
          });
      }

      // Close modal when clicking outside
      if (this.deleteModal) {
          this.deleteModal.addEventListener('click', (event) => {
              if (event.target === this.deleteModal) {
                  this.hideDeleteModal();
              }
          });
      }
  },

  showDeleteModal: function() {
      if (this.deleteModal) {
          this.deleteModal.style.display = 'flex';
          setTimeout(() => {
              this.deleteModal.style.opacity = '1';
          }, 10);
      }
  },

  hideDeleteModal: function() {
      if (this.deleteModal) {
          this.deleteModal.style.opacity = '0';
          setTimeout(() => {
              this.deleteModal.style.display = 'none';
          }, 300);
      }
  },

  setupSearchEvents: function() {
      // Auto-submit form when student or course select changes
      const studentSelect = document.querySelector('select[name="student"]');
      const courseSelect = document.querySelector('select[name="course"]');
      
      if (studentSelect) {
          studentSelect.addEventListener('change', () => {
              this.submitSearchForm();
          });
      }
      
      if (courseSelect) {
          courseSelect.addEventListener('change', () => {
              this.submitSearchForm();
          });
      }
      
      // Clear search when reset button is clicked
      const resetBtn = document.querySelector('.btn-outline');
      if (resetBtn) {
          resetBtn.addEventListener('click', (e) => {
              e.preventDefault();
              this.clearSearch();
          });
      }
      
      // Focus on search input when page loads if there's a search term
      const searchInput = document.querySelector('input[name="search"]');
      if (searchInput && searchInput.value) {
          searchInput.focus();
          searchInput.select();
      }

      // Add keyboard shortcut for search (Ctrl/Cmd + F)
      document.addEventListener('keydown', (e) => {
          if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
              e.preventDefault();
              if (searchInput) {
                  searchInput.focus();
                  searchInput.select();
              }
          }
      });

      // Auto-submit search on Enter key in search input
      if (searchInput) {
          searchInput.addEventListener('keypress', (e) => {
              if (e.key === 'Enter') {
                  e.preventDefault();
                  this.submitSearchForm();
              }
          });
      }
  },

  submitSearchForm: function() {
      const searchForm = document.getElementById('searchForm');
      if (searchForm) {
          searchForm.submit();
      }
  },

  clearSearch: function() {
      const urlParams = new URLSearchParams(window.location.search);
      const showArchived = urlParams.get('show_archived');
      
      if (showArchived) {
          window.location.href = '?page=enrollments&show_archived=true';
      } else {
          window.location.href = '?page=enrollments';
      }
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
              const urlParams = new URLSearchParams(window.location.search);
              urlParams.delete('edit_id');
              window.history.replaceState({}, document.title, window.location.pathname + '?' + urlParams.toString());
          }
      }
  },

  // Utility functions for SweetAlert notifications
  showSuccessMessage: function(message) {
      Swal.fire({
          icon: 'success',
          title: 'Success',
          text: message,
          timer: 3000,
          showConfirmButton: false,
          toast: true,
          position: 'top-end'
      });
  },

  showErrorMessage: function(message) {
      Swal.fire({
          icon: 'error',
          title: 'Error',
          text: message,
          timer: 5000,
          showConfirmButton: true,
          toast: false,
          position: 'center'
      });
  },

  showToast: function(message, type) {
      Swal.fire({
          icon: type,
          title: type.charAt(0).toUpperCase() + type.slice(1),
          text: message,
          timer: type === 'error' ? 5000 : 3000,
          showConfirmButton: false,
          toast: true,
          position: 'top-end'
      });
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
  },

  // Search utility functions
  getSearchParams: function() {
      const urlParams = new URLSearchParams(window.location.search);
      return {
          search: urlParams.get('search') || '',
          student: urlParams.get('student') || '',
          course: urlParams.get('course') || '',
          show_archived: urlParams.get('show_archived') || ''
      };
  },

  hasActiveSearch: function() {
      const params = this.getSearchParams();
      return params.search || params.student || params.course;
  }
};

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Check if we're in editing mode
    const urlParams = new URLSearchParams(window.location.search);
    const isEditing = urlParams.has('edit_id');
    const showArchived = urlParams.get('show_archived') === 'true';
    
    EnrollmentManager.init(isEditing, showArchived);
    
    // Auto-hide existing toasts after 5 seconds
    const existingToasts = document.querySelectorAll('.toast');
    existingToasts.forEach(toast => {
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

    // Initialize search result highlighting
    highlightSearchResults();
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

// Global toast notification function with 5-second timeout
function showToast(message, type = 'success') {
    const toastContainer = document.getElementById('toastContainer');
    if (!toastContainer) return;

    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    
    // Set icon based on type
    let icon = 'check-circle';
    if (type === 'error') icon = 'exclamation-circle';
    if (type === 'warning') icon = 'exclamation-triangle';
    if (type === 'info') icon = 'info-circle';
    
    toast.innerHTML = `
        <i class="fas fa-${icon}"></i>
        ${message}
    `;
    
    toastContainer.appendChild(toast);
    
    // Auto-hide toast after 5 seconds with smooth animation
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(100%)';
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 300);
    }, 5000);
}

// Function to open enrollment modal (for the no-records link)
function openEnrollmentModal() {
    const openBtn = document.getElementById('openEnrollmentModal');
    if (openBtn) {
        openBtn.click();
    }
}

// Search result highlighting function
function highlightSearchResults() {
    const searchParams = new URLSearchParams(window.location.search);
    const searchTerm = searchParams.get('search');
    
    if (!searchTerm) return;
    
    const table = document.querySelector('table');
    if (!table) return;
    
    const rows = table.querySelectorAll('tbody tr');
    const searchTerms = searchTerm.toLowerCase().split(' ').filter(term => term.length > 2);
    
    if (searchTerms.length === 0) return;
    
    rows.forEach(row => {
        let rowText = '';
        const cells = row.querySelectorAll('td');
        
        cells.forEach(cell => {
            rowText += ' ' + cell.textContent.toLowerCase();
        });
        
        let hasMatch = false;
        searchTerms.forEach(term => {
            if (rowText.includes(term)) {
                hasMatch = true;
            }
        });
        
        if (hasMatch) {
            row.style.backgroundColor = 'rgba(67, 97, 238, 0.1)';
            row.style.borderLeft = '4px solid var(--primary)';
            
            // Add highlight to matching text in cells
            cells.forEach(cell => {
                const originalText = cell.innerHTML;
                let highlightedText = originalText;
                
                searchTerms.forEach(term => {
                    const regex = new RegExp(`(${term})`, 'gi');
                    highlightedText = highlightedText.replace(regex, '<mark class="search-highlight">$1</mark>');
                });
                
                cell.innerHTML = highlightedText;
            });
        }
    });
}

// Add CSS for search highlighting
const searchHighlightStyle = document.createElement('style');
searchHighlightStyle.textContent = `
    .search-highlight {
        background-color: #ffeb3b;
        padding: 0.1rem 0.2rem;
        border-radius: 3px;
        font-weight: bold;
    }
    
    .search-active-row {
        background-color: rgba(67, 97, 238, 0.1) !important;
        border-left: 4px solid var(--primary) !important;
    }
`;
document.head.appendChild(searchHighlightStyle);

// Quick search function (can be called from browser console for testing)
function quickSearch(term) {
    const searchInput = document.querySelector('input[name="search"]');
    if (searchInput) {
        searchInput.value = term;
        document.getElementById('searchForm').submit();
    }
}

// Export search data function
function exportSearchData(format) {
    const searchParams = new URLSearchParams(window.location.search);
    let exportUrl = `enrollment_export_${format}.php?`;
    
    if (searchParams.get('search')) {
        exportUrl += `search=${encodeURIComponent(searchParams.get('search'))}&`;
    }
    
    if (searchParams.get('student')) {
        exportUrl += `student=${searchParams.get('student')}&`;
    }
    
    if (searchParams.get('course')) {
        exportUrl += `course=${searchParams.get('course')}&`;
    }
    
    if (searchParams.get('show_archived')) {
        exportUrl += `show_archived=${searchParams.get('show_archived')}&`;
    }
    
    // Remove trailing & or ?
    exportUrl = exportUrl.replace(/[&?]$/, '');
    
    window.open(exportUrl, '_blank');
}

// Display search info
function displaySearchInfo() {
    const searchParams = EnrollmentManager.getSearchParams();
    const hasSearch = EnrollmentManager.hasActiveSearch();
    
    if (hasSearch) {
        console.log('Active Search Filters:', searchParams);
        
        // You could display this info in a small badge near the search form
        const searchInfo = document.createElement('div');
        searchInfo.className = 'search-info';
        searchInfo.style.cssText = `
            background: var(--info);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius);
            margin: 0 2rem 1rem;
            font-size: 0.9rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        `;
        
        let infoText = 'Active filters: ';
        const filters = [];
        
        if (searchParams.search) filters.push(`Search: "${searchParams.search}"`);
        if (searchParams.student) {
            const studentSelect = document.querySelector('select[name="student"]');
            const selectedOption = studentSelect?.options[studentSelect.selectedIndex];
            if (selectedOption) {
                filters.push(`Student: ${selectedOption.textContent.split(' (')[0]}`);
            }
        }
        if (searchParams.course) {
            const courseSelect = document.querySelector('select[name="course"]');
            const selectedOption = courseSelect?.options[courseSelect.selectedIndex];
            if (selectedOption) {
                filters.push(`Course: ${selectedOption.textContent}`);
            }
        }
        if (searchParams.show_archived) {
            filters.push('View: Archived');
        }
        
        infoText += filters.join(', ');
        
        searchInfo.innerHTML = `
            <span>${infoText}</span>
            <button onclick="EnrollmentManager.clearSearch()" style="background: none; border: none; color: white; cursor: pointer;">
                <i class="fas fa-times"></i>
            </button>
        `;
        
        const searchContainer = document.querySelector('.search-container');
        if (searchContainer) {
            searchContainer.parentNode.insertBefore(searchInfo, searchContainer.nextSibling);
        }
    }
}

// Enhanced keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Escape key to close modals
    if (e.key === 'Escape') {
        // Close enrollment modal
        const enrollmentModal = document.getElementById('enrollmentModal');
        if (enrollmentModal && enrollmentModal.style.display === 'block') {
            EnrollmentManager.closeModal();
        }
        
        // Close delete confirmation modal
        const deleteModal = document.getElementById('deleteConfirmation');
        if (deleteModal && deleteModal.style.display === 'flex') {
            EnrollmentManager.hideDeleteModal();
        }
    }
    
    // Ctrl/Cmd + N to open new enrollment modal (only when not in archived view)
    if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
        e.preventDefault();
        const urlParams = new URLSearchParams(window.location.search);
        const showArchived = urlParams.get('show_archived');
        if (!showArchived) {
            const openBtn = document.getElementById('openEnrollmentModal');
            if (openBtn) {
                openBtn.click();
            }
        }
    }
});

// Auto-refresh functionality (optional)
function autoRefresh(interval = 30000) {
    setInterval(() => {
        // Only refresh if no modal is open and user is not actively interacting
        const enrollmentModal = document.getElementById('enrollmentModal');
        const deleteModal = document.getElementById('deleteConfirmation');
        
        if ((!enrollmentModal || enrollmentModal.style.display === 'none') && 
            (!deleteModal || deleteModal.style.display === 'none')) {
            window.location.reload();
        }
    }, interval);
}

// Uncomment the line below to enable auto-refresh every 30 seconds
// autoRefresh(30000);

// Utility function to format dates
function formatDate(dateString) {
    const options = { year: 'numeric', month: 'short', day: 'numeric' };
    return new Date(dateString).toLocaleDateString(undefined, options);
}

// Utility function to format time
function formatTime(timeString) {
    if (!timeString) return 'N/A';
    
    const time = new Date(`2000-01-01T${timeString}`);
    return time.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

// Initialize tooltips for better UX
function initializeTooltips() {
    const tooltipElements = document.querySelectorAll('[data-tooltip]');
    
    tooltipElements.forEach(element => {
        element.addEventListener('mouseenter', function(e) {
            const tooltipText = this.getAttribute('data-tooltip');
            const tooltip = document.createElement('div');
            tooltip.className = 'tooltip';
            tooltip.textContent = tooltipText;
            tooltip.style.cssText = `
                position: absolute;
                background: rgba(0, 0, 0, 0.8);
                color: white;
                padding: 0.5rem;
                border-radius: 4px;
                font-size: 0.8rem;
                z-index: 10000;
                white-space: nowrap;
                pointer-events: none;
            `;
            
            document.body.appendChild(tooltip);
            
            const rect = this.getBoundingClientRect();
            tooltip.style.left = rect.left + 'px';
            tooltip.style.top = (rect.top - tooltip.offsetHeight - 5) + 'px';
            
            this._tooltip = tooltip;
        });
        
        element.addEventListener('mouseleave', function() {
            if (this._tooltip) {
                this._tooltip.remove();
                this._tooltip = null;
            }
        });
    });
}

// Call this function to initialize tooltips
// initializeTooltips();

// Enhanced error handling
window.addEventListener('error', function(e) {
    console.error('JavaScript Error:', e.error);
    showToast('An error occurred. Please check the console for details.', 'error');
});

// Performance monitoring
function logPerformance() {
    if (window.performance) {
        const navigation = performance.getEntriesByType('navigation')[0];
        if (navigation) {
            console.log('Page Load Time:', navigation.loadEventEnd - navigation.loadEventStart, 'ms');
            console.log('DOM Content Loaded:', navigation.domContentLoadedEventEnd - navigation.domContentLoadedEventStart, 'ms');
        }
    }
}

// Call this function to log performance metrics
// logPerformance();
// Export search data function
function exportSearchData(format) {
    const searchParams = new URLSearchParams(window.location.search);
    let exportUrl = `enrollment_export_${format}.php?`;
    
    if (searchParams.get('search')) {
        exportUrl += `search=${encodeURIComponent(searchParams.get('search'))}&`;
    }
    
    if (searchParams.get('student')) {
        exportUrl += `student=${searchParams.get('student')}&`;
    }
    
    if (searchParams.get('course')) {
        exportUrl += `course=${searchParams.get('course')}&`;
    }
    
    // Remove trailing & or ?
    exportUrl = exportUrl.replace(/[&?]$/, '');
    
    window.open(exportUrl, '_blank');
}

// Display search info
function displaySearchInfo() {
    const searchParams = EnrollmentManager.getSearchParams();
    const hasSearch = EnrollmentManager.hasActiveSearch();
    
    if (hasSearch) {
        console.log('Active Search Filters:', searchParams);
        
        // You could display this info in a small badge near the search form
        const searchInfo = document.createElement('div');
        searchInfo.className = 'search-info';
        searchInfo.style.cssText = `
            background: var(--info);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius);
            margin: 0 2rem 1rem;
            font-size: 0.9rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        `;
        
        let infoText = 'Active filters: ';
        const filters = [];
        
        if (searchParams.search) filters.push(`Search: "${searchParams.search}"`);
        if (searchParams.student) {
            const studentSelect = document.querySelector('select[name="student"]');
            const selectedOption = studentSelect?.options[studentSelect.selectedIndex];
            if (selectedOption) {
                filters.push(`Student: ${selectedOption.textContent.split(' (')[0]}`);
            }
        }
        if (searchParams.course) {
            const courseSelect = document.querySelector('select[name="course"]');
            const selectedOption = courseSelect?.options[courseSelect.selectedIndex];
            if (selectedOption) {
                filters.push(`Course: ${selectedOption.textContent}`);
            }
        }
        
        infoText += filters.join(', ');
        
        searchInfo.innerHTML = `
            <span>${infoText}</span>
            <button onclick="EnrollmentManager.clearSearch()" style="background: none; border: none; color: white; cursor: pointer;">
                <i class="fas fa-times"></i>
            </button>
        `;
        
        const searchContainer = document.querySelector('.search-container');
        if (searchContainer) {
            searchContainer.parentNode.insertBefore(searchInfo, searchContainer.nextSibling);
        }
    }
}

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

// Add click animations to cards
document.addEventListener('DOMContentLoaded', function() {
    // Logout modal buttons
    document.getElementById('confirmLogout').addEventListener('click', function() {
        window.location.href = '?logout=true';
    });

    document.getElementById('cancelLogout').addEventListener('click', function() {
        closeLogoutModal();
    });

    // Close modal when clicking outside
    document.getElementById('logoutConfirmation').addEventListener('click', function(event) {
        if (event.target === this) {
            closeLogoutModal();
        }
    });

    const cards = document.querySelectorAll('.stat-card, .enrollment-card, .action-card');
    cards.forEach(card => {
        card.addEventListener('click', function() {
            this.style.transform = 'scale(0.98)';
            setTimeout(() => {
                this.style.transform = '';
            }, 150);
        });
    });
});


// Call this function to display search info
// displaySearchInfo();