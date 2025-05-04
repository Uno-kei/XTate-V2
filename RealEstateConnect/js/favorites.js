
/**
 * Real Estate Listing System
 * Favorites JavaScript File
 */

document.addEventListener('DOMContentLoaded', function() {
    // Handle favorite button clicks
    const favoriteButtons = document.querySelectorAll('.favorite-btn');
    
    if (favoriteButtons.length > 0) {
        favoriteButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Get property ID from data attribute
                const propertyId = this.getAttribute('data-property-id');
                if (!propertyId) {
                    console.error('No property ID found on favorite button');
                    return;
                }
                
                // Show loading state
                const originalText = this.innerHTML;
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                this.disabled = true;
                
                // Create form data
                const formData = new FormData();
                formData.append('property_id', propertyId);
                formData.append('action', 'toggle');
                
                // Send request to server
                fetch('api/favorite.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Favorite toggled:', data);
                    
                    // Reset button state
                    this.disabled = false;
                    
                    if (data.success) {
                        if (data.action === 'added') {
                            // Update UI for favorited state
                            this.classList.remove('btn-outline-danger');
                            this.classList.add('btn-danger', 'favorited');
                            this.innerHTML = '<i class="fas fa-heart"></i> Remove from Favorites';
                            
                            const notification = document.createElement('div');
                            notification.className = 'alert alert-success position-fixed top-0 start-50 translate-middle-x mt-3';
                            notification.style.zIndex = '9999';
                            notification.innerHTML = `
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-check-circle me-2"></i>
                                    <span>Property added to favorites</span>
                                    <button type="button" class="btn-close ms-3" onclick="this.parentElement.parentElement.remove()"></button>
                                </div>
                            `;
                            document.body.appendChild(notification);
                            setTimeout(() => notification.remove(), 3000);
                        } else if (data.action === 'removed') {
                            // Update UI for unfavorited state
                            this.classList.remove('btn-danger', 'favorited');
                            this.classList.add('btn-outline-danger');
                            this.innerHTML = '<i class="far fa-heart"></i> Add to Favorites';
                            
                            const notification = document.createElement('div');
                            notification.className = 'alert alert-info position-fixed top-0 start-50 translate-middle-x mt-3';
                            notification.style.zIndex = '9999';
                            notification.innerHTML = `
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-check-circle me-2"></i>
                                    <span>Property removed from favorites</span>
                                    <button type="button" class="btn-close ms-3" onclick="this.parentElement.parentElement.remove()"></button>
                                </div>
                            `;
                            document.body.appendChild(notification);
                            setTimeout(() => notification.remove(), 3000);
                        }
                    } else {
                        // Error handling
                        this.innerHTML = originalText;
                        
                        if (data.redirect) {
                            if (confirm('You need to log in first. Go to login page?')) {
                                window.location.href = data.redirect;
                            }
                        } else {
                            const notification = document.createElement('div');
                            notification.className = 'alert alert-danger position-fixed top-0 start-50 translate-middle-x mt-3';
                            notification.style.zIndex = '9999';
                            notification.innerHTML = `
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-exclamation-circle me-2"></i>
                                    <span>${data.message || 'An error occurred while toggling favorite status'}</span>
                                    <button type="button" class="btn-close ms-3" onclick="this.parentElement.parentElement.remove()"></button>
                                </div>
                            `;
                            document.body.appendChild(notification);
                            setTimeout(() => notification.remove(), 3000);
                        }
                    }
                })
                .catch(error => {
                    console.error('Error toggling favorite:', error);
                    this.disabled = false;
                    this.innerHTML = originalText;
                    
                    const notification = document.createElement('div');
                    notification.className = 'alert alert-danger position-fixed top-0 start-50 translate-middle-x mt-3';
                    notification.style.zIndex = '9999';
                    notification.innerHTML = `
                        <div class="d-flex align-items-center">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <span>An error occurred. Please try again later.</span>
                            <button type="button" class="btn-close ms-3" onclick="this.parentElement.parentElement.remove()"></button>
                        </div>
                    `;
                    document.body.appendChild(notification);
                    setTimeout(() => notification.remove(), 3000);
                });
            });
        });
    }
});
