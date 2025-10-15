// Tab functionality
document.addEventListener('DOMContentLoaded', function() {
// Tab switching
const tabLinks = document.querySelectorAll('.tab-link');
const tabPanes = document.querySelectorAll('.tab-pane');
const menuItems = document.querySelectorAll('.menu-item');
const actionCards = document.querySelectorAll('.action-card');

function switchTab(tabName) {
    // Update tab links
    tabLinks.forEach(link => {
        link.classList.remove('active');
        if (link.getAttribute('data-tab') === tabName) {
            link.classList.add('active');
        }
    });
    
    // Update tab panes
    tabPanes.forEach(pane => {
        pane.classList.remove('active');
        if (pane.id === tabName) {
            pane.classList.add('active');
        }
    });
    
    // Update menu items
    menuItems.forEach(item => {
        item.classList.remove('active');
        if (item.getAttribute('data-tab') === tabName) {
            item.classList.add('active');
        }
    });
    
    // Update URL
    const url = new URL(window.location);
    url.searchParams.set('tab', tabName);
    window.history.replaceState({}, '', url);
}

// Add event listeners to tab links
tabLinks.forEach(link => {
    link.addEventListener('click', function() {
        const tabName = this.getAttribute('data-tab');
        switchTab(tabName);
    });
});

// Add event listeners to menu items
menuItems.forEach(item => {
    item.addEventListener('click', function() {
        const tabName = this.getAttribute('data-tab');
        switchTab(tabName);
    });
});

// Add event listeners to action cards
actionCards.forEach(card => {
    card.addEventListener('click', function() {
        const tabName = this.getAttribute('data-tab');
        switchTab(tabName);
    });
});

// Check if there's a tab parameter in the URL
const urlParams = new URLSearchParams(window.location.search);
const tabParam = urlParams.get('tab');
if (tabParam) {
    switchTab(tabParam);
}
});