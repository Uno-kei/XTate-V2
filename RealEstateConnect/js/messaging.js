/**
 * Real Estate Listing System
 * Messaging JavaScript File
 */

document.addEventListener('DOMContentLoaded', function() {
    // Check if we're on a messaging page
    const messageContainer = document.getElementById('messageContainer');
    const messageForm = document.getElementById('messageForm');
    
    if (messageContainer && messageForm) {
        // Scroll to bottom of message container
        scrollToBottom();
        
        // Initialize WebSocket for real-time messaging
        initializeWebSocket();
        
        // Handle message form submission
        messageForm.addEventListener('submit', function(e) {
            e.preventDefault(); // Prevent default form submission
            
            const messageInput = this.querySelector('textarea[name="message"]');
            const message = messageInput.value.trim();
            const urlParams = new URLSearchParams(window.location.search);
            const partnerId = urlParams.get('user');
            
            if (message && partnerId) {
                // Send via WebSocket if available, fallback to Ajax
                if (typeof wsManager !== 'undefined' && wsManager.isConnected()) {
                    wsManager.sendMessage({
                        type: 'message',
                        receiver_id: partnerId,
                        message: message,
                        timestamp: new Date().toISOString()
                    });
                    
                    // Create temporary message element
                    const tempMessage = {
                        id: 'temp_' + new Date().getTime(),
                        message: message,
                        is_sent_by_me: true,
                        formatted_time: 'Sending...'
                    };
                    
                    // Display temporary message
                    displayMessages([tempMessage]);
                } else {
                    // Fallback to Ajax
                    sendMessageViaAjax(partnerId, message);
                }
                
                // Clear the message input
                messageInput.value = '';
                messageInput.style.height = 'auto';
            }
        });
        
        // Auto-expand textarea
        const textarea = messageForm.querySelector('textarea');
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
        
        // Handle loading messages on page load
        const urlParams = new URLSearchParams(window.location.search);
        const partnerId = urlParams.get('user');
        
        if (partnerId) {
            // Mark messages as read
            markMessagesAsRead(partnerId);
            
            // Load initial messages
            loadMessages(partnerId);
            
            // If WebSocket is not available, fallback to polling
            if (typeof wsManager === 'undefined' || !wsManager.isConnected()) {
                console.log('WebSocket not available, using polling fallback');
                // Load new messages every 5 seconds
                setInterval(function() {
                    loadMessages(partnerId);
                }, 5000);
            }
        }
    }
    
    // Check for unread message count periodically
    setInterval(updateUnreadMessageCount, 10000);
});

/**
 * Load messages from a specific partner
 * @param {number} partnerId - The ID of the conversation partner
 */
function loadMessages(partnerId) {
    fetch(`../api/messages.php?action=get_messages&partner_id=${partnerId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayMessages(data.data.messages);
            }
        })
        .catch(error => {
            console.error('Error loading messages:', error);
        });
}

/**
 * Display messages in the container
 * @param {Array} messages - The messages to display
 */
function displayMessages(messages) {
    const messageContainer = document.getElementById('messageContainer');
    
    if (!messageContainer || !messages || messages.length === 0) {
        return;
    }
    
    // Get the ID of the last message currently displayed
    const existingMessages = messageContainer.querySelectorAll('.message-item');
    const lastMessageId = existingMessages.length > 0 
        ? parseInt(existingMessages[existingMessages.length - 1].dataset.messageId || 0) 
        : 0;
    
    // Get current scroll position
    const isScrolledToBottom = messageContainer.scrollHeight - messageContainer.clientHeight <= messageContainer.scrollTop + 100;
    
    // Filter for only new messages
    const newMessages = messages.filter(msg => parseInt(msg.id) > lastMessageId);
    
    if (newMessages.length === 0) {
        return;
    }
    
    // Append new messages
    newMessages.forEach(message => {
        const messageClass = message.is_sent_by_me ? 'message-sent' : 'message-received';
        
        const messageElement = document.createElement('div');
        messageElement.className = `message-item ${messageClass}`;
        messageElement.dataset.messageId = message.id;
        
        messageElement.innerHTML = `
            <div class="message-content">
                <p>${message.message.replace(/\n/g, '<br>')}</p>
                <small class="message-time">${message.formatted_time}</small>
            </div>
        `;
        
        messageContainer.appendChild(messageElement);
    });
    
    // Scroll to bottom if already at bottom
    if (isScrolledToBottom) {
        scrollToBottom();
    }
}

/**
 * Format message time
 * @param {string} dateTime - The date and time to format
 * @returns {string} - Formatted time string
 */
function formatMessageTime(dateTime) {
    const date = new Date(dateTime);
    const now = new Date();
    const diff = Math.floor((now - date) / 1000); // Difference in seconds
    
    // Less than 1 minute
    if (diff < 60) {
        return "Just now";
    }
    
    // Less than 1 hour
    if (diff < 3600) {
        const minutes = Math.floor(diff / 60);
        return `${minutes} min${minutes > 1 ? 's' : ''} ago`;
    }
    
    // Less than 24 hours
    if (diff < 86400) {
        const hours = Math.floor(diff / 3600);
        return `${hours} hour${hours > 1 ? 's' : ''} ago`;
    }
    
    // Less than 7 days
    if (diff < 604800) {
        const days = Math.floor(diff / 86400);
        if (days === 1) {
            return `Yesterday at ${date.getHours()}:${String(date.getMinutes()).padStart(2, '0')}`;
        }
        
        const weekdays = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        return `${weekdays[date.getDay()]} at ${date.getHours()}:${String(date.getMinutes()).padStart(2, '0')}`;
    }
    
    // More than 7 days
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    return `${months[date.getMonth()]} ${date.getDate()}, ${date.getFullYear()} at ${date.getHours()}:${String(date.getMinutes()).padStart(2, '0')}`;
}

/**
 * Mark messages as read
 * @param {number} partnerId - The ID of the conversation partner
 */
function markMessagesAsRead(partnerId) {
    const formData = new FormData();
    formData.append('action', 'mark_read');
    formData.append('sender_id', partnerId);
    
    fetch('../api/messages.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update unread message count
            updateUnreadMessageCount();
        }
    })
    .catch(error => {
        console.error('Error marking messages as read:', error);
    });
}

/**
 * Send a message via AJAX
 * @param {number} receiverId - The ID of the message receiver
 * @param {string} message - The message content
 */
function sendMessageViaAjax(receiverId, message) {
    const formData = new FormData();
    formData.append('action', 'send_message');
    formData.append('receiver_id', receiverId);
    formData.append('message', message);
    
    fetch('../api/messages.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Reload messages
            const urlParams = new URLSearchParams(window.location.search);
            const partnerId = urlParams.get('user');
            
            if (partnerId) {
                loadMessages(partnerId);
            }
        }
    })
    .catch(error => {
        console.error('Error sending message:', error);
    });
}

/**
 * Update unread message count
 */
function updateUnreadMessageCount() {
    fetch('../api/messages.php?action=unread_count')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const count = data.data.unread_count;
                const badges = document.querySelectorAll('.unread-message-badge');
                
                badges.forEach(badge => {
                    if (count > 0) {
                        badge.textContent = count;
                        badge.style.display = 'inline-block';
                    } else {
                        badge.style.display = 'none';
                    }
                });
            }
        })
        .catch(error => {
            console.error('Error updating unread count:', error);
        });
}

/**
 * Scroll to bottom of message container
 */
function scrollToBottom() {
    const messageContainer = document.getElementById('messageContainer');
    if (messageContainer) {
        messageContainer.scrollTop = messageContainer.scrollHeight;
    }
}

/**
 * Initialize WebSocket connection
 */
function initializeWebSocket() {
    // Check if WebSocket manager exists (defined in websocket.js)
    if (typeof wsManager === 'undefined') {
        console.warn('WebSocket manager not found, real-time messaging unavailable');
        return;
    }
    
    // Connect to WebSocket server
    wsManager.connect();
    
    // Register message handler
    wsManager.registerHandler('message', function(data) {
        // Handle incoming messages
        if (data.type === 'message') {
            // Get current conversation partner
            const urlParams = new URLSearchParams(window.location.search);
            const currentPartnerId = urlParams.get('user');
            
            // If this message is from/to the current conversation partner, display it
            if ((data.sender_id == currentPartnerId) || (data.receiver_id == currentPartnerId)) {
                // Create message object
                const message = {
                    id: data.id || ('temp_' + new Date().getTime()),
                    message: data.message,
                    is_sent_by_me: data.sender_id !== currentPartnerId,
                    formatted_time: data.formatted_time || formatMessageTime(data.timestamp)
                };
                
                // Display message
                displayMessages([message]);
                
                // Mark message as read if it's from current partner
                if (data.sender_id == currentPartnerId) {
                    markMessagesAsRead(currentPartnerId);
                }
            }
            
            // Update unread message count
            updateUnreadMessageCount();
        }
    });
}