
<?php
require_once '../inc/db.php';
require_once '../inc/functions.php';
require_once '../inc/auth.php';

// Check if user is admin
checkPermission(['admin']);

// Get all properties with seller information
$sql = "SELECT p.*, u.full_name as seller_name, u.email as seller_email,
        (SELECT image_path FROM property_images WHERE property_id = p.id AND is_primary = 1 LIMIT 1) as primary_image
        FROM properties p
        JOIN users u ON p.seller_id = u.id
        ORDER BY p.created_at DESC";
$properties = fetchAll($sql);

include '../inc/header.php';
?>

<div class="container py-5">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-lg-3 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Admin Dashboard</h5>
                </div>
                <div class="list-group list-group-flush">
                    <a href="dashboard.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                    </a>
                    <a href="reports.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-flag me-2"></i> Reports
                    </a>
                    <a href="users.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-users me-2"></i> Users
                    </a>
                    <a href="properties.php" class="list-group-item list-group-item-action active">
                        <i class="fas fa-home me-2"></i> Properties
                    </a>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-lg-9">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Property Management</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Image</th>
                                    <th>Title</th>
                                    <th>Seller</th>
                                    <th>Price</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($properties as $property): ?>
                                <tr>
                                    <td>
                                        <?php
                                        $imagePath = $property['primary_image'];
                                        if (!empty($imagePath)) {
                                            if (substr($imagePath, 0, 4) !== 'http') {
                                                $imagePath = '../uploads/properties/' . basename($imagePath);
                                            }
                                        } else {
                                            $imagePath = '../uploads/properties/default.jpg';
                                        }
                                        ?>
                                        <img src="<?= htmlspecialchars($imagePath) ?>" 
                                             alt="Property" class="img-thumbnail" 
                                             style="width: 80px; height: 60px; object-fit: cover;">
                                    </td>
                                    <td>
                                        <a href="../property_details.php?id=<?= $property['id'] ?>" target="_blank">
                                            <?= htmlspecialchars($property['title']) ?>
                                        </a>
                                        <div class="small text-muted">
                                            <?= $property['bedrooms'] ?> beds • <?= $property['bathrooms'] ?> baths • <?= number_format($property['area']) ?> sqft
                                        </div>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($property['seller_name']) ?>
                                        <div class="small text-muted"><?= htmlspecialchars($property['seller_email']) ?></div>
                                    </td>
                                    <td><?= formatCurrency($property['price']) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $property['status'] === 'active' ? 'success' : 'secondary' ?>">
                                            <?= ucfirst($property['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="../property_details.php?id=<?= $property['id'] ?>" 
                                           class="btn btn-sm btn-primary" target="_blank">
                                            View
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../inc/footer.php'; ?>
