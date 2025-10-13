// room.js - Room Management System JavaScript

const RoomManager = {
    // Initialize the room management system
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
        this.roomModal = document.getElementById("roomModal");
        this.deleteConfirmation = document.getElementById("deleteConfirmation");
        this.openBtn = document.getElementById("openRoomModal");
        this.closeBtns = document.querySelectorAll(".close");
        this.cancelBtns = document.querySelectorAll(".btn-cancel");
    },

    initializeDeleteConfirmation: function() {
        this.confirmDeleteBtn = document.getElementById('confirmDelete');
        this.cancelDeleteBtn = document.getElementById('cancelDelete');
        this.deleteRoomId = null;
        this.deleteRoomCode = null;

        this.setupDeleteConfirmationEvents();
    },

    setupEventListeners: function() {
        // Open add room modal
        if (this.openBtn) {
            this.openBtn.addEventListener('click', () => {
                this.openAddRoomModal();
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
            if (event.target === this.roomModal) {
                this.closeModals();
            }
            if (event.target === this.deleteConfirmation) {
                this.hideDeleteConfirmation();
            }
        });

        // Edit button events
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('edit-btn')) {
                this.openEditRoomModal(e.target);
            }
        });

        // Delete button events
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('delete-btn')) {
                const roomId = e.target.getAttribute('data-room-id');
                const roomCode = e.target.getAttribute('data-room-code');
                this.showDeleteConfirmation(roomId, roomCode);
            }
        });

        // Close modals on successful form submission
        const roomForm = document.getElementById('roomForm');
        if (roomForm) {
            roomForm.addEventListener('submit', () => {
                setTimeout(() => {
                    this.closeModals();
                }, 1000);
            });
        }

        const deleteForm = document.getElementById('deleteRoomForm');
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

    openAddRoomModal: function() {
        // Reset form
        const roomForm = document.getElementById('roomForm');
        if (roomForm) {
            roomForm.reset();
        }
        
        // Update modal title and buttons
        document.getElementById('roomModalTitle').textContent = 'Add New Room';
        document.getElementById('addRoomBtn').style.display = 'block';
        document.getElementById('updateRoomBtn').style.display = 'none';
        
        // Clear hidden room_id
        document.getElementById('room_id').value = '';
        
        this.showModal(this.roomModal);
    },

    openEditRoomModal: function(button) {
        // Get room data from data attributes
        const roomId = button.getAttribute('data-room-id');
        const building = button.getAttribute('data-building');
        const roomCode = button.getAttribute('data-room-code');
        const capacity = button.getAttribute('data-capacity');

        // Fill form with room data
        document.getElementById('room_id').value = roomId;
        document.getElementById('building').value = building;
        document.getElementById('room_code').value = roomCode;
        document.getElementById('capacity').value = capacity;

        // Update modal title and buttons
        document.getElementById('roomModalTitle').textContent = 'Edit Room';
        document.getElementById('addRoomBtn').style.display = 'none';
        document.getElementById('updateRoomBtn').style.display = 'block';
        
        this.showModal(this.roomModal);
    },

    showDeleteConfirmation: function(roomId, roomCode) {
        this.deleteRoomId = roomId;
        this.deleteRoomCode = roomCode;
        
        const message = document.getElementById('deleteMessage');
        if (message) {
            message.textContent = `Are you sure you want to delete "${roomCode}"? This action cannot be undone.`;
        }

        if (this.deleteConfirmation) {
            this.deleteConfirmation.classList.add('show');
        }
    },

    hideDeleteConfirmation: function() {
        this.deleteRoomId = null;
        this.deleteRoomCode = null;
        
        if (this.deleteConfirmation) {
            this.deleteConfirmation.classList.remove('show');
        }
    },

    executeDelete: function() {
        if (this.deleteRoomId) {
            // Set the room ID in the hidden form and submit it
            const deleteRoomId = document.getElementById('deleteRoomId');
            const deleteForm = document.getElementById('deleteRoomForm');
            
            if (deleteRoomId && deleteForm) {
                deleteRoomId.value = this.deleteRoomId;
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
        // Close room modal
        if (this.roomModal) {
            this.roomModal.classList.remove('show');
            setTimeout(() => {
                this.roomModal.style.display = "none";
            }, 300);
        }
        
        // Also close delete confirmation
        this.hideDeleteConfirmation();
    },

    // Search functionality
    initializeSearch: function() {
        this.searchInput = document.getElementById('searchRooms');
        this.searchButton = document.getElementById('searchButton');
        this.clearSearchBtn = document.getElementById('clearSearch');
        this.searchStats = document.getElementById('searchStats');
        this.roomRows = document.querySelectorAll('tbody tr');
        this.totalRooms = this.roomRows.length;

        this.setupSearchEvents();
        this.updateSearchStats(this.totalRooms, this.totalRooms);
    },

    setupSearchEvents: function() {
        // Search input event (real-time search)
        if (this.searchInput) {
            this.searchInput.addEventListener('input', (e) => {
                this.filterRooms(e.target.value);
            });
            
            // Also allow Enter key to trigger search
            this.searchInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    this.filterRooms(e.target.value);
                }
            });
        }

        // Search button event
        if (this.searchButton) {
            this.searchButton.addEventListener('click', () => {
                this.filterRooms(this.searchInput.value);
            });
        }

        // Building filter events
        const filterButtons = document.querySelectorAll('.filter-btn');
        filterButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                const filter = e.target.dataset.filter;
                this.applyBuildingFilter(filter, e.target);
            });
        });

        // Clear search event
        if (this.clearSearchBtn) {
            this.clearSearchBtn.addEventListener('click', () => {
                this.clearSearch();
            });
        }
    },

    filterRooms: function(searchTerm) {
        const term = searchTerm.toLowerCase().trim();
        let visibleCount = 0;

        this.roomRows.forEach(row => {
            const building = row.cells[0].textContent.toLowerCase();
            const roomCode = row.cells[1].textContent.toLowerCase();
            const capacity = row.cells[2].textContent.toLowerCase();

            const matches = building.includes(term) || roomCode.includes(term) || capacity.includes(term);
            
            if (matches) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        this.updateSearchStats(visibleCount, this.totalRooms);
        this.toggleNoResults(visibleCount === 0 && term.length > 0);
        this.toggleClearSearch(term.length > 0);
        
        // Focus the search input after filtering
        if (this.searchInput) {
            this.searchInput.focus();
        }
    },

    applyBuildingFilter: function(filter, button) {
        // Remove active class from all filter buttons
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.classList.remove('active');
        });

        // Add active class to clicked button
        button.classList.add('active');

        let visibleCount = 0;

        this.roomRows.forEach(row => {
            const buildingCell = row.cells[0];
            const buildingNameElement = buildingCell.querySelector('.building-name');
            const buildingText = buildingNameElement ? 
                buildingNameElement.textContent.trim() : 
                buildingCell.textContent.trim();
            
            let matches = true;

            if (filter !== 'all') {
                matches = buildingText === filter;
            }
            // 'all' filter shows everything

            if (matches) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        this.updateSearchStats(visibleCount, this.totalRooms);
        this.toggleNoResults(visibleCount === 0);
        this.clearSearchInput();
    },

    clearSearch: function() {
        if (this.searchInput) {
            this.searchInput.value = '';
        }
        
        // Show all rows
        this.roomRows.forEach(row => {
            row.style.display = '';
        });

        // Remove active class from filter buttons
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.classList.remove('active');
        });

        // Set 'all' filter as active
        const allFilter = document.querySelector('.filter-btn[data-filter="all"]');
        if (allFilter) {
            allFilter.classList.add('active');
        }

        this.updateSearchStats(this.totalRooms, this.totalRooms);
        this.toggleNoResults(false);
        this.toggleClearSearch(false);
        
        // Focus the search input after clearing
        if (this.searchInput) {
            this.searchInput.focus();
        }
    },

    clearSearchInput: function() {
        if (this.searchInput) {
            this.searchInput.value = '';
        }
        this.toggleClearSearch(false);
    },

    updateSearchStats: function(visible, total) {
        if (this.searchStats) {
            this.searchStats.textContent = `Showing ${visible} of ${total} rooms`;
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
                        <h3>No rooms found</h3>
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
    if (typeof RoomManager !== 'undefined') {
        RoomManager.init();
    }
});

// Add keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Escape key to close modals
    if (e.key === 'Escape') {
        const roomModal = document.getElementById('roomModal');
        const deleteConfirmation = document.getElementById('deleteConfirmation');
        
        if (roomModal && roomModal.style.display === 'block') {
            RoomManager.closeModals();
        } else if (deleteConfirmation && deleteConfirmation.classList.contains('show')) {
            RoomManager.hideDeleteConfirmation();
        }
    }
    
    // Ctrl/Cmd + F to focus search
    if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
        e.preventDefault();
        const searchInput = document.getElementById('searchRooms');
        if (searchInput) {
            searchInput.focus();
        }
    }
    
    // Ctrl/Cmd + N to open new room modal
    if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
        e.preventDefault();
        const openBtn = document.getElementById('openRoomModal');
        if (openBtn) {
            openBtn.click();
        }
    }
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
    let exportUrl = `room_export_${type}.php`;
    
    console.log('Export URL:', exportUrl); // Debug log
    
    // Open export in new window
    window.open(exportUrl, '_blank');
}