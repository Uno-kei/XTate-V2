
document.addEventListener("DOMContentLoaded", function () {
    const notification = document.getElementById('notification');

    function showNotification(type, message) {
        notification.classList.remove('d-none');
        notification.className = `alert alert-${type} position-fixed top-0 start-50 translate-middle-x mt-3`;
        notification.innerHTML = `
            <div class="d-flex align-items-center">
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
                <span>${message}</span>
            </div>
        `;
        setTimeout(() => {
            notification.classList.add('d-none');
        }, 3000);
    }

    // Status update handling
    document.querySelectorAll(".status-action").forEach((button) => {
        button.addEventListener("click", function (e) {
            e.preventDefault();
            const userId = this.dataset.userId;
            const status = this.dataset.status;

            fetch("../api/users.php?action=update_status", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({
                    user_id: userId,
                    status: status
                }),
            })
            .then((response) => response.json())
            .then((data) => {
                if (data.success) {
                    const statusBtn = this.closest('.dropdown').querySelector('.dropdown-toggle');
                    if (statusBtn) {
                        statusBtn.textContent = status.charAt(0).toUpperCase() + status.slice(1);
                        statusBtn.className = `btn btn-sm ${status === 'active' ? 'btn-success' : 'btn-warning'} dropdown-toggle`;
                    }
                    showNotification('success', data.message);
                } else {
                    showNotification('danger', data.message || 'Failed to update status');
                }
            })
            .catch((error) => {
                console.error("Error:", error);
                showNotification('danger', 'An error occurred while updating status');
            });
        });
    });

    // User details modal handling
    document.querySelectorAll(".btn-info").forEach((button) => {
        button.addEventListener("click", function (e) {
            e.preventDefault();
            const userId = this.dataset.userId;

            fetch(`../api/users.php?action=get_user_details&user_id=${userId}`)
                .then((response) => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then((data) => {
                    if (data.success && data.user) {
                        const userDetailsModal = new bootstrap.Modal(document.getElementById('userDetailsModal'));
                        const modalContent = document.querySelector("#userDetailsModal .modal-content");
                        const user = data.user;
                        const activity = data.activity || [];

                        modalContent.innerHTML = `
                            <div class="modal-header">
                                <h5 class="modal-title">User Details</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="user-info mb-4">
                                    <h6 class="border-bottom pb-2">Basic Information</h6>
                                    <p><strong>Name:</strong> ${user.full_name || 'N/A'}</p>
                                    <p><strong>Email:</strong> ${user.email || 'N/A'}</p>
                                    <p><strong>Phone:</strong> ${user.phone || 'N/A'}</p>
                                    <p><strong>Joined:</strong> ${user.created_at || 'N/A'}</p>
                                    <p><strong>Last Login:</strong> ${user.last_login || 'N/A'}</p>
                                </div>
                                <div class="user-activity">
                                    <h6 class="border-bottom pb-2">Recent Activity</h6>
                                    ${activity.length ? `
                                        <div class="list-group">
                                            ${activity.map(item => `
                                                <div class="list-group-item">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div>${item.action}</div>
                                                        <small class="text-muted">${item.date}</small>
                                                    </div>
                                                </div>
                                            `).join('')}
                                        </div>
                                    ` : '<p class="text-muted">No recent activity</p>'}
                                </div>
                            </div>
                        `;
                        userDetailsModal.show();
                    } else {
                        showNotification('danger', 'Failed to load user details');
                    }
                })
                .catch((error) => {
                    console.error("Error:", error);
                    showNotification('danger', 'An error occurred while loading user details');
                });
        });
    });
});
