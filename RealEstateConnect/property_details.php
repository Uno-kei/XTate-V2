<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'inc/db.php';
require_once 'inc/functions.php';
require_once 'inc/auth.php';

// Start session
startSession();

// Check if property ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirect('search.php');
}

$propertyId = (int)$_GET['id'];

// Get property details
$property = getPropertyById($propertyId);

// If property not found or not active, redirect to search page
if (!$property || $property['status'] !== 'active') {
    redirect('search.php');
}

// Get property images
$propertyImages = getPropertyImages($propertyId);

// Check if property is favorited by logged in user
$isFavorited = false;
if (isLoggedIn() && $_SESSION['user_role'] === 'buyer') {
    // Direct database query to check if property is favorited
    try {
        $conn = connectDB();
        if ($conn) {
            $userId = $_SESSION['user_id'];
            
            // Check favorites table
            $stmt = $conn->prepare("SELECT id FROM favorites WHERE property_id = ? AND buyer_id = ?");
            $stmt->bind_param("ii", $propertyId, $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $isFavorited = true;
            } else {
                // Check buyer_favorites table as fallback
                $stmt = $conn->prepare("SELECT id FROM buyer_favorites WHERE property_id = ? AND buyer_id = ?");
                $stmt->bind_param("ii", $propertyId, $userId);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $isFavorited = true;
                }
            }
            $stmt->close();
            closeDB($conn);
        }
    } catch (Exception $e) {
        error_log("Error checking favorites: " . $e->getMessage());
    }
}

// Process inquiry form
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['inquiry_submit'])) {
    // Check if user is logged in and is a buyer
    if (!isLoggedIn()) {
        redirect('login.php?redirect=' . urlencode('property_details.php?id=' . $propertyId));
    } elseif ($_SESSION['user_role'] !== 'buyer') {
        $errors[] = 'Only buyers can submit inquiries';
    }
    
    $message = sanitizeInput($_POST['inquiry_message'] ?? '');
    
    // Validate message
    if (empty($message)) {
        $errors[] = 'Please enter your message';
    }
    
    // If no errors, save inquiry
    if (empty($errors)) {
        $sql = "INSERT INTO inquiries (property_id, buyer_id, message, status, created_at) 
                VALUES (?, ?, ?, 'pending', NOW())";
        
        $inquiryId = insertData($sql, "iis", [
            $propertyId,
            $_SESSION['user_id'],
            $message
        ]);
        
        if ($inquiryId) {
            $success = 'Your inquiry has been submitted successfully. The seller will respond to you soon.';
        } else {
            $errors[] = 'Failed to submit inquiry. Please try again.';
        }
    }
}

include 'inc/header.php';
?>

<div class="container py-5">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item"><a href="search.php">Properties</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= $property['title'] ?></li>
        </ol>
    </nav>
    
    <!-- Property Details -->
    <div class="row">
        <div class="col-lg-8">
            <div class="property-details">
                <?php if (!empty($propertyImages)): ?>
                    <!-- Property Images Carousel -->
                    <div id="propertyCarousel" class="carousel slide" data-bs-ride="carousel">
                        <div class="carousel-inner">
                            <?php foreach ($propertyImages as $index => $image): ?>
                                <?php
                                    echo "<!-- DEBUG Image Path Info START -->";
                                    
                                    // Get original path from database
                                    $imagePath = $image['image_path'];
                                    echo "<!-- ORIGINAL DB PATH: " . $imagePath . " -->";
                                    
                                    // Remove leading slash if present (makes JS and CSS paths work)
                                    if (substr($imagePath, 0, 1) === '/') {
                                        $imagePath = substr($imagePath, 1);
                                    }
                                    
                                    // Handle protocol-relative URLs (//example.com/image.jpg)
                                    if (substr($imagePath, 0, 2) === '//') {
                                        $imagePath = 'https:' . $imagePath;
                                    }
                                    
                                    // Handle absolute URLs (already have http or https)
                                    else if (substr($imagePath, 0, 4) === 'http') {
                                        // Do nothing, already complete URL
                                    }
                                    
                                    // Handle local server paths
                                    else {
                                        // If it has ../uploads, remove the ../
                                        if (strpos($imagePath, '../uploads/') === 0) {
                                            $imagePath = substr($imagePath, 3);
                                        }
                                        
                                        // Ensure the path is absolute from web root for browser to find it
                                        if (substr($imagePath, 0, 1) !== '/') {
                                            $imagePath = '/' . $imagePath;
                                        }
                                    }
                                    
                                    echo "<!-- FINAL PATH: " . $imagePath . " -->";
                                    echo "<!-- DEBUG Image Path Info END -->";
                                ?>
                                <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                                    <?php
                                    // Check if it's a URL or local path and format accordingly
                                    if (substr($imagePath, 0, 4) === 'http') {
                                        // External URL - use as is
                                        echo "<div class=\"property-slider\" style=\"background-image: url('{$imagePath}')\"></div>";
                                    } else {
                                        // Local file - ensure it exists and use relative path for better caching
                                        $relativePath = ltrim($imagePath, '/');
                                        echo "<div class=\"property-slider\" style=\"background-image: url('{$relativePath}')\"></div>";
                                    }
                                    ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <?php if (count($propertyImages) > 1): ?>
                            <button class="carousel-control-prev" type="button" data-bs-target="#propertyCarousel" data-bs-slide="prev">
                                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Previous</span>
                            </button>
                            <button class="carousel-control-next" type="button" data-bs-target="#propertyCarousel" data-bs-slide="next">
                                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Next</span>
                            </button>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <!-- Default Property Image -->
                    <div class="property-slider" style="background-image: url('https://images.unsplash.com/photo-1560518883-ce09059eeffa')"></div>
                <?php endif; ?>
                
                <div class="property-details-content">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h1 class="property-details-title"><?= $property['title'] ?></h1>
                            <div class="property-details-location">
                                <i class="fas fa-map-marker-alt"></i> <?= $property['address'] ?>, <?= $property['city'] ?>, <?= $property['state'] ?> <?= $property['zip_code'] ?>
                            </div>
                        </div>
                        <div class="property-details-price"><?= formatCurrency($property['price']) ?></div>
                    </div>
                    
                    <!-- Property Features -->
                    <div class="property-details-features">
                        <div class="feature">
                            <i class="fas fa-bed"></i> <?= $property['bedrooms'] ?> Bedrooms
                        </div>
                        <div class="feature">
                            <i class="fas fa-bath"></i> <?= $property['bathrooms'] ?> Bathrooms
                        </div>
                        <div class="feature">
                            <i class="fas fa-ruler-combined"></i> <?= number_format($property['area']) ?> sqft
                        </div>
                        <div class="feature">
                            <i class="fas fa-home"></i> <?= $property['property_type_name'] ?>
                        </div>
                        <div class="feature">
                            <i class="fas fa-calendar-alt"></i> Built in <?= $property['year_built'] ?>
                        </div>
                    </div>
                    
                    <!-- Property Description -->
                    <div class="property-description mt-4">
                        <h2>Description</h2>
                        <p><?= nl2br($property['description']) ?></p>
                    </div>
                    
                    <!-- Property Amenities -->
                    <div class="property-amenities mt-4">
                        <h2>Amenities</h2>
                        <ul>
                            <?php 
                            $amenities = [
                                'garage' => 'Garage',
                                'air_conditioning' => 'Air Conditioning',
                                'swimming_pool' => 'Swimming Pool',
                                'backyard' => 'Backyard',
                                'gym' => 'Gym',
                                'fireplace' => 'Fireplace',
                                'security_system' => 'Security System',
                                'washer_dryer' => 'Washer/Dryer'
                            ];
                            
                            foreach ($amenities as $key => $name):
                                if (isset($property[$key]) && $property[$key] == 1):
                            ?>
                                <li><i class="fas fa-check"></i> <?= $name ?></li>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Google Map (Placeholder) -->
            <div class="mt-4">
                <h2>Location</h2>
                <div class="bg-light" style="height: 300px; display: flex; align-items: center; justify-content: center;">
                    <p class="text-muted">Map showing <?= $property['address'] ?>, <?= $property['city'] ?>, <?= $property['state'] ?> <?= $property['zip_code'] ?></p>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <!-- Seller Information -->
            <div class="seller-info">
                <h3 class="seller-info-title">Seller Information</h3>
                <div class="seller-info-content">
                    <div class="seller-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div>
                        <div class="seller-name"><?= $property['seller_name'] ?></div>
                        <div class="text-muted">Property Seller</div>
                    </div>
                </div>
                <div class="seller-contact">
                    <div><i class="fas fa-envelope"></i> <?= $property['seller_email'] ?></div>
                    <div><i class="fas fa-phone"></i> <?= $property['seller_phone'] ?></div>
                </div>
                
                <?php if (isLoggedIn() && $_SESSION['user_role'] === 'buyer'): ?>
                    <div class="d-grid gap-2 mt-3">
                        <a href="buyer/messages.php?user=<?= $property['seller_id'] ?>" class="btn btn-outline-primary">
                            <i class="fas fa-comments"></i> Message Seller
                        </a>
                        
                        <button class="btn <?= $isFavorited ? 'btn-danger favorited' : 'btn-outline-danger' ?> favorite-btn" data-property-id="<?= $property['id'] ?>" data-bs-toggle="tooltip" title="<?= $isFavorited ? 'Remove from Favorites' : 'Add to Favorites' ?>">
                            <i class="<?= $isFavorited ? 'fas' : 'far' ?> fa-heart"></i> <?= $isFavorited ? 'Remove from Favorites' : 'Add to Favorites' ?>
                        </button>
                        <!-- Simple fallback form for non-JS users -->
                        <form method="post" action="api/favorites_fix.php" style="display:none;">
                            <input type="hidden" name="property_id" value="<?= $property['id'] ?>">
                            <input type="hidden" name="action" value="<?= $isFavorited ? 'remove' : 'add' ?>">
                            <button type="submit" class="btn btn-sm btn-secondary mt-2">Fallback: <?= $isFavorited ? 'Remove' : 'Add' ?></button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Inquiry Form -->
            <div class="contact-form mt-4">
                <h3>Interested in this property?</h3>
                
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?= $error ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success">
                        <?= $success ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isLoggedIn() && $_SESSION['user_role'] === 'buyer'): ?>
                    <form id="inquiryForm" method="POST" action="<?= $_SERVER['PHP_SELF'] ?>?id=<?= $propertyId ?>" novalidate>
                        <div class="mb-3">
                            <label for="inquiry_message" class="form-label">Your Message</label>
                            <textarea class="form-control" id="inquiry_message" name="inquiry_message" rows="5" required placeholder="I am interested in this property and would like to schedule a viewing..."></textarea>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" name="inquiry_submit" class="btn btn-primary">Send Inquiry</button>
                        </div>
                    </form>
                <?php elseif (isLoggedIn() && $_SESSION['user_role'] === 'seller'): ?>
                    <div class="alert alert-info">
                        As a seller, you cannot submit inquiries. <a href="seller/dashboard.php">Go to your dashboard</a> to manage your properties.
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        Please <a href="login.php?redirect=<?= urlencode('property_details.php?id=' . $propertyId) ?>">login</a> or <a href="register.php">register</a> as a buyer to submit an inquiry.
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Share Property -->
            <div class="mt-4 p-3 bg-light rounded">
                <h3>Share this Property</h3>
                <div class="d-flex gap-2 mt-3">
                    <a href="https://www.facebook.com/" class="btn btn-outline-primary">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <a href="https://x.com/home" class="btn btn-outline-info">
                        <i class="fab fa-twitter"></i>
                    </a>
                    <a href="https://www.instagram.com/" class="btn btn-outline-success">
                        <i class="fab fa-instagram"></i>
                    </a>
                    <a href="https://www.tiktok.com/" class="btn btn-outline-dark">
                    <i class="fa-brands fa-tiktok"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="js/property.js"></script>

<script src="js/favorites_fix.js"></script>
<?php include 'inc/footer.php'; ?>
