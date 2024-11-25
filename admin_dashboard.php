<?php
// admin_dashboard.php

// Start the session (if not already started)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Database connection parameters
$hostname = 'localhost';
$username = 'root';
$password = '';
$database = 'login_register';

// Create a new MySQLi connection
$conn = new mysqli($hostname, $username, $password, $database);

// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize status messages
$productAdded = false;
$productUpdated = false;
$productDeleted = false;
$orderUpdated = false;

// Fetch total products and total orders
$totalProductsResult = $conn->query("SELECT COUNT(*) as count FROM products");
$totalProducts = ($totalProductsResult && $totalProductsResult->num_rows > 0) ? $totalProductsResult->fetch_assoc()['count'] : 0;

$totalOrdersResult = $conn->query("SELECT COUNT(*) as count FROM orders");
$totalOrders = ($totalOrdersResult && $totalOrdersResult->num_rows > 0) ? $totalOrdersResult->fetch_assoc()['count'] : 0;

// Fetch distinct categories for filter options
$categoriesResult = $conn->query("SELECT DISTINCT name FROM categories");

// Handle form submission for adding a product
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_product'])) {
    $productName     = $_POST['product_name'];
    $productPrice    = $_POST['product_price'];
    $productDescription = $_POST['product_description'];
    $productCategory = $_POST['product_category'];
    $productStock    = $_POST['product_stock'];
    $productImage    = $_FILES['product_image'];

    // Handle image upload
    if ($productImage['error'] === UPLOAD_ERR_OK) {
        $imageTmpPath = $productImage['tmp_name'];
        $imageName = time() . '_' . basename($productImage['name']);
        $imageSize = $productImage['size'];
        $imageType = mime_content_type($imageTmpPath);
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];

        // Validate image type
        if (in_array($imageType, $allowedTypes)) {
            $targetDir = 'uploads/';
            $targetFile = $targetDir . $imageName;

            if (move_uploaded_file($imageTmpPath, $targetFile)) {
                // Get category_id from categories table
                $stmtCat = $conn->prepare("SELECT id FROM categories WHERE name = ?");
                $stmtCat->bind_param("s", $productCategory);
                $stmtCat->execute();
                $stmtCat->bind_result($categoryId);
                $stmtCat->fetch();
                $stmtCat->close();

                if (!$categoryId) {
                    // If category doesn't exist, insert it
                    $stmtInsertCat = $conn->prepare("INSERT INTO categories (name) VALUES (?)");
                    $stmtInsertCat->bind_param("s", $productCategory);
                    $stmtInsertCat->execute();
                    $categoryId = $stmtInsertCat->insert_id;
                    $stmtInsertCat->close();
                }

                // Insert new product into the database
                $stmt = $conn->prepare("INSERT INTO products (name, description, price, category_id, image, stock) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssdisi", $productName, $productDescription, $productPrice, $categoryId, $imageName, $productStock);
                if ($stmt->execute()) {
                    $productAdded = true;
                }
                $stmt->close();
            }
        }
    }
}

// Handle form submission for updating a product
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_product'])) {
    $productId        = $_POST['product_id'];
    $editProductName  = $_POST['edit_product_name'];
    $editProductPrice = $_POST['edit_product_price'];
    $editProductDescription = $_POST['edit_product_description'];
    $editProductCategory = $_POST['edit_product_category'];
    $editProductStock    = $_POST['edit_product_stock'];
    $editProductImage    = $_FILES['edit_product_image'];

    // Get category_id from categories table
    $stmtCat = $conn->prepare("SELECT id FROM categories WHERE name = ?");
    $stmtCat->bind_param("s", $editProductCategory);
    $stmtCat->execute();
    $stmtCat->bind_result($editCategoryId);
    $stmtCat->fetch();
    $stmtCat->close();

    if (!$editCategoryId) {
        // If category doesn't exist, insert it
        $stmtInsertCat = $conn->prepare("INSERT INTO categories (name) VALUES (?)");
        $stmtInsertCat->bind_param("s", $editProductCategory);
        $stmtInsertCat->execute();
        $editCategoryId = $stmtInsertCat->insert_id;
        $stmtInsertCat->close();
    }

    // Check if a new image is uploaded
    if ($editProductImage['error'] === UPLOAD_ERR_OK) {
        $imageTmpPath = $editProductImage['tmp_name'];
        $imageName = time() . '_' . basename($editProductImage['name']);
        $imageSize = $editProductImage['size'];
        $imageType = mime_content_type($imageTmpPath);
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];

        // Validate image type
        if (in_array($imageType, $allowedTypes)) {
            $targetDir = 'uploads/';
            $targetFile = $targetDir . $imageName;

            if (move_uploaded_file($imageTmpPath, $targetFile)) {
                // Update product with new image
                $stmt = $conn->prepare("UPDATE products SET name = ?, description = ?, price = ?, category_id = ?, image = ?, stock = ? WHERE id = ?");
                $stmt->bind_param("ssdisii", $editProductName, $editProductDescription, $editProductPrice, $editCategoryId, $imageName, $editProductStock, $productId);
                if ($stmt->execute()) {
                    $productUpdated = true;
                }
                $stmt->close();
            }
        }
    } else {
        // Update product without changing image
        $stmt = $conn->prepare("UPDATE products SET name = ?, description = ?, price = ?, category_id = ?, stock = ? WHERE id = ?");
        $stmt->bind_param("ssdisi", $editProductName, $editProductDescription, $editProductPrice, $editCategoryId, $editProductStock, $productId);
        if ($stmt->execute()) {
            $productUpdated = true;
        }
        $stmt->close();
    }
}

// Handle form submission for deleting a product
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_product'])) {
    $deleteProductId = $_POST['product_id'];

    // Fetch the image name to delete the file
    $stmtFetch = $conn->prepare("SELECT image FROM products WHERE id = ?");
    $stmtFetch->bind_param("i", $deleteProductId);
    $stmtFetch->execute();
    $stmtFetch->bind_result($imageName);
    $stmtFetch->fetch();
    $stmtFetch->close();

    // Delete the product from the database
    $stmtDelete = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmtDelete->bind_param("i", $deleteProductId);
    if ($stmtDelete->execute()) {
        // Delete the image file from the server
        if (file_exists("uploads/" . $imageName)) {
            unlink("uploads/" . $imageName);
        }
        $productDeleted = true;
    }
    $stmtDelete->close();
}

// Fetch products with optional category filter
$selectedCategory = '';
if (isset($_GET['category']) && $_GET['category'] != '') {
    $selectedCategory = $_GET['category'];
    $stmtProducts = $conn->prepare("SELECT products.*, categories.name AS category_name FROM products LEFT JOIN categories ON products.category_id = categories.id WHERE categories.name = ?");
    $stmtProducts->bind_param("s", $selectedCategory);
} else {
    $stmtProducts = $conn->prepare("SELECT products.*, categories.name AS category_name FROM products LEFT JOIN categories ON products.category_id = categories.id");
}

$stmtProducts->execute();
$resultProducts = $stmtProducts->get_result();
$stmtProducts->close();

// Fetch all orders
$ordersResult = $conn->query("SELECT orders.*, users.full_name AS user_name, users.email AS user_email FROM orders LEFT JOIN users ON orders.user_id = users.id ORDER BY orders.created_at DESC");

// Fetch all categories for the add product form
$allCategoriesResult = $conn->query("SELECT name FROM categories ORDER BY name ASC");

// Close the database connection at the end
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --success-color: #4bb543;
            --danger-color: #dc3545;
            --background-color: #f8f9fa;
            --text-color: #333;
            --border-radius: 10px;
            --box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background-color: var(--background-color);
            color: var(--text-color);
            display: flex;
            min-height: 100vh;
        }

        /* Topbar Styling */
        .topbar {
            position: fixed;
            top: 0;
            width: 100%;
            height: 70px;
            background: white;
            box-shadow: var(--box-shadow);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 30px;
            z-index: 1000;
        }

        .topbar h1 {
            font-size: 24px;
            color: var(--primary-color);
            font-weight: 600;
        }

        .toggle-btn {
            background: none;
            border: none;
            cursor: pointer;
            padding: 10px;
        }

        .bar {
            display: block;
            width: 25px;
            height: 3px;
            margin: 5px 0;
            background-color: var(--primary-color);
            border-radius: 3px;
            transition: 0.3s;
        }

        /* Sidebar Styling */
        .sidebar {
            width: 250px;
            height: 100vh;
            background: white;
            padding-top: 80px;
            position: fixed;
            left: -250px;
            transition: 0.3s;
            box-shadow: var(--box-shadow);
        }

        .sidebar.open {
            left: 0;
        }

        .sidebar a {
            display: flex;
            align-items: center;
            color: var(--text-color);
            padding: 15px 30px;
            text-decoration: none;
            font-size: 16px;
            transition: 0.2s;
        }

        .sidebar a i {
            margin-right: 10px;
            font-size: 20px;
        }

        .sidebar a:hover {
            background: var(--primary-color);
            color: white;
        }

        /* Main Content Styling */
        .main {
            flex: 1;
            margin-left: 0;
            padding: 90px 30px 30px;
            transition: 0.3s;
        }

        .main.shifted {
            margin-left: 250px;
        }

        /* Form Styling */
        .form-section {
            background: white;
            padding: 30px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 30px;
        }

        .form-section h2 {
            color: var(--primary-color);
            margin-bottom: 20px;
            font-size: 24px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            transition: 0.3s;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            font-weight: 500;
            transition: 0.3s;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background: var(--secondary-color);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .btn-danger {
            background: var(--danger-color);
            color: white;
        }

        .btn-danger:hover {
            background: #bb2d3b;
        }

        /* Product Grid Styling */
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }

        .product-item {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
            transition: 0.3s;
        }

        .product-item:hover {
            transform: translateY(-5px);
        }

        .product-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .product-details {
            padding: 20px;
        }

        .product-name {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .product-description {
            font-size: 14px;
            color: #555;
            margin-bottom: 10px;
            height: 40px;
            overflow: hidden;
        }

        .product-category {
            font-size: 14px;
            color: #777;
            margin-bottom: 10px;
        }

        .product-price {
            color: var(--primary-color);
            font-size: 16px;
            font-weight: 500;
            margin-bottom: 15px;
        }

        .product-stock {
            font-size: 14px;
            color: #555;
            margin-bottom: 15px;
        }

        .product-actions {
            display: flex;
            gap: 10px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .main.shifted {
                margin-left: 0;
            }

            .sidebar {
                width: 100%;
                max-width: 300px;
            }

            .product-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            }
        }

        /* Loading Animation */
        .loading {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.8);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 2000;
        }

        .loading-spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid var(--primary-color);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Toast Notification */
        .toast {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: white;
            padding: 15px 25px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            display: none;
            z-index: 3000;
        }

        .toast.success {
            border-left: 4px solid var(--success-color);
        }

        .toast.error {
            border-left: 4px solid var(--danger-color);
        }

        /* Dashboard Stats */
        .dashboard-stats {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .stat-item {
            flex: 1;
            background: white;
            padding: 20px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            display: flex;
            align-items: center;
            min-width: 200px;
        }

        .stat-item i {
            font-size: 40px;
            margin-right: 15px;
            color: var(--primary-color);
        }

        .stat-item h3 {
            margin: 0;
            font-size: 28px;
        }

        .stat-item p {
            margin: 0;
            color: #777;
        }

        /* Filter Section */
        .filter-section {
            margin-bottom: 20px;
        }

        .filter-section label {
            margin-right: 10px;
            font-weight: 500;
        }

        .filter-section select {
            padding: 8px;
            border-radius: var(--border-radius);
            border: 1px solid #ddd;
        }

        /* Modal Styling */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 3000;
        }

        .modal-content {
            background: white;
            padding: 30px;
            border-radius: var(--border-radius);
            width: 500px;
            max-width: 90%;
            position: relative;
        }

        .close-button {
            position: absolute;
            top: 15px;
            right: 15px;
            font-size: 24px;
            cursor: pointer;
        }

        /* Orders Section */
        .order-section {
            margin-top: 50px;
        }

        .order-table {
            width: 100%;
            border-collapse: collapse;
        }

        .order-table th, .order-table td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: left;
        }

        .order-table th {
            background: var(--primary-color);
            color: white;
        }

        .order-table tr:nth-child(even) {
            background: #f9f9f9;
        }
    </style>
</head>
<body>
    <!-- Topbar -->
    <div class="topbar">
        <button class="toggle-btn" onclick="toggleSidebar()">
            <span class="bar"></span>
            <span class="bar"></span>
            <span class="bar"></span>
        </button>
        <h1>Admin Dashboard</h1>
        <div class="user-info">
            <i class="fas fa-user-circle"></i>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <a href="#dashboard"><i class="fas fa-home"></i> Dashboard</a>
        <a href="#manage-products"><i class="fas fa-box"></i> Manage Products</a>
        <a href="#orders"><i class="fas fa-shopping-cart"></i> Orders</a>
        <a href="#"><i class="fas fa-cog"></i> Settings</a>
        <a href="index.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <!-- Main Content -->
    <div class="main" id="main">
        <!-- Dashboard Stats -->
        <div class="dashboard-stats" id="dashboard">
            <div class="stat-item">
                <i class="fas fa-box"></i>
                <div>
                    <h3><?php echo htmlspecialchars($totalProducts); ?></h3>
                    <p>Total Products</p>
                </div>
            </div>
            <div class="stat-item">
                <i class="fas fa-shopping-cart"></i>
                <div>
                    <h3><?php echo htmlspecialchars($totalOrders); ?></h3>
                    <p>Total Orders</p>
                </div>
            </div>
        </div>

        <!-- Add Product Form -->
        <div class="form-section" id="manage-products">
            <h2>Add New Product</h2>
            <form action="admin_dashboard.php" method="post" enctype="multipart/form-data" onsubmit="showLoading()">
                <div class="form-group">
                    <label for="product_name">Product Name</label>
                    <input type="text" name="product_name" id="product_name" required>
                </div>

                <div class="form-group">
                    <label for="product_description">Product Description</label>
                    <textarea name="product_description" id="product_description" rows="4" required></textarea>
                </div>

                <div class="form-group">
                    <label for="product_price">Product Price ($)</label>
                    <input type="number" step="0.01" name="product_price" id="product_price" required>
                </div>

                <div class="form-group">
                    <label for="product_stock">Stock Quantity</label>
                    <input type="number" name="product_stock" id="product_stock" required>
                </div>

                <div class="form-group">
                    <label for="product_category">Product Category</label>
                    <input list="categories" name="product_category" id="product_category" required>
                    <datalist id="categories">
                        <?php while ($catRow = $allCategoriesResult->fetch_assoc()) { ?>
                            <option value="<?php echo htmlspecialchars($catRow['name']); ?>">
                        <?php } ?>
                    </datalist>
                </div>

                <div class="form-group">
                    <label for="product_image">Product Image</label>
                    <input type="file" name="product_image" id="product_image" accept="image/*" required>
                </div>

                <button type="submit" name="add_product" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add Product
                </button>
            </form>
        </div>

        <!-- Product List -->
        <div class="product-section">
            <h2>Product List</h2>
            <div class="filter-section">
                <form action="admin_dashboard.php#manage-products" method="get">
                    <label for="category_filter">Filter by Category:</label>
                    <select name="category" id="category_filter" onchange="this.form.submit()">
                        <option value="">All Categories</option>
                        <?php
                        // Reset the categoriesResult pointer
                        $categoriesResult->data_seek(0);
                        while ($catRow = $categoriesResult->fetch_assoc()) { ?>
                            <option value="<?php echo htmlspecialchars($catRow['name']); ?>" <?php if ($selectedCategory == $catRow['name']) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($catRow['name']); ?>
                            </option>
                        <?php } ?>
                    </select>
                </form>
            </div>
            <div class="product-grid">
                <?php if ($resultProducts && $resultProducts->num_rows > 0) { ?>
                    <?php while ($product = $resultProducts->fetch_assoc()) { ?>
                        <div class="product-item" data-id="<?php echo htmlspecialchars($product['id']); ?>">
                            <img src="uploads/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-image">
                            <div class="product-details">
                                <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                                <p class="product-description"><?php echo htmlspecialchars(substr($product['description'], 0, 100)); ?>...</p>
                                <p class="product-category"><strong>Category:</strong> <?php echo htmlspecialchars($product['category_name']); ?></p>
                                <p class="product-price"><strong>Price:</strong> $<?php echo number_format($product['price'], 2); ?></p>
                                <p class="product-stock"><strong>Stock:</strong> <?php echo htmlspecialchars($product['stock']); ?></p>
                                <div class="product-actions">
                                    <button type="button" class="btn btn-secondary" onclick="showEditForm(<?php echo htmlspecialchars($product['id']); ?>)">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <form action="admin_dashboard.php" method="post" onsubmit="return confirmDelete()">
                                        <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product['id']); ?>">
                                        <button type="submit" name="delete_product" class="btn btn-danger">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                <?php } else { ?>
                    <p>No products found.</p>
                <?php } ?>
            </div>
        </div>

        <!-- Orders Section -->
        <div class="order-section" id="orders">
            <h2>Order List</h2>
            <?php if ($ordersResult && $ordersResult->num_rows > 0) { ?>
                <table class="order-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Order Date</th>
                            <th>Customer Name</th>
                            <th>Customer Email</th>
                            <th>Total Amount ($)</th>
                            <th>Status</th>
                            <th>Shipping Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($order = $ordersResult->fetch_assoc()) { ?>
                            <tr>
                                <td><?php echo htmlspecialchars($order['id']); ?></td>
                                <td><?php echo htmlspecialchars($order['created_at']); ?></td>
                                <td><?php echo htmlspecialchars($order['user_name']); ?></td>
                                <td><?php echo htmlspecialchars($order['user_email']); ?></td>
                                <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                                <td><?php echo htmlspecialchars($order['status']); ?></td>
                                <td><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            <?php } else { ?>
                <p>No orders found.</p>
            <?php } ?>
        </div>
    </div>

    <!-- Edit Product Modal -->
    <div id="editProductModal" class="modal">
        <div class="modal-content">
            <span class="close-button" onclick="closeEditForm()">&times;</span>
            <h2>Edit Product</h2>
            <form action="admin_dashboard.php" method="post" enctype="multipart/form-data" onsubmit="showLoading()">
                <input type="hidden" name="product_id" id="edit_product_id">
                <div class="form-group">
                    <label for="edit_product_name">Product Name</label>
                    <input type="text" name="edit_product_name" id="edit_product_name" required>
                </div>

                <div class="form-group">
                    <label for="edit_product_description">Product Description</label>
                    <textarea name="edit_product_description" id="edit_product_description" rows="4" required></textarea>
                </div>

                <div class="form-group">
                    <label for="edit_product_price">Product Price ($)</label>
                    <input type="number" step="0.01" name="edit_product_price" id="edit_product_price" required>
                </div>

                <div class="form-group">
                    <label for="edit_product_stock">Stock Quantity</label>
                    <input type="number" name="edit_product_stock" id="edit_product_stock" required>
                </div>

                <div class="form-group">
                    <label for="edit_product_category">Product Category</label>
                    <input list="edit_categories" name="edit_product_category" id="edit_product_category" required>
                    <datalist id="edit_categories">
                        <?php
                        // Fetch all categories again for the edit form
                        $allCategoriesResultEdit = $conn->query("SELECT name FROM categories ORDER BY name ASC");
                        while ($catRowEdit = $allCategoriesResultEdit->fetch_assoc()) { ?>
                            <option value="<?php echo htmlspecialchars($catRowEdit['name']); ?>">
                        <?php } ?>
                    </datalist>
                </div>

                <div class="form-group">
                    <label for="edit_product_image">Product Image (Leave blank to keep current image)</label>
                    <input type="file" name="edit_product_image" id="edit_product_image" accept="image/*">
                </div>

                <button type="submit" name="update_product" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </form>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div id="loading" class="loading" style="display: none;">
        <div class="loading-spinner"></div>
    </div>

    <!-- Toast Notification -->
    <div id="toast" class="toast"></div>

    <script>
        // Toggle Sidebar
        function toggleSidebar() {
            const sidebar = document.getElementById("sidebar");
            const main = document.getElementById("main");
            sidebar.classList.toggle("open");
            main.classList.toggle("shifted");
        }

        // Show Loading Overlay
        function showLoading() {
            document.getElementById("loading").style.display = "flex";
        }

        // Confirm Delete
        function confirmDelete() {
            return confirm("Are you sure you want to delete this product?");
        }

        // Show Toast Messages
        function showToast(message, type) {
            const toast = document.getElementById("toast");
            toast.textContent = message;
            toast.className = `toast ${type}`;
            toast.style.display = "block";
            setTimeout(() => {
                toast.style.display = "none";
            }, 3000);
        }

        // Display Toast Messages Based on PHP Status
        <?php if ($productAdded) { ?>
            showToast("Product added successfully!", "success");
        <?php } ?>

        <?php if ($productUpdated) { ?>
            showToast("Product updated successfully!", "success");
        <?php } ?>

        <?php if ($productDeleted) { ?>
            showToast("Product deleted successfully!", "success");
        <?php } ?>

        // Edit Product Function
        function showEditForm(productId) {
            // Find the product item
            var productItem = document.querySelector('.product-item[data-id="' + productId + '"]');

            if (productItem) {
                // Get product details
                var productName = productItem.querySelector('.product-name').textContent;
                var productDescription = productItem.querySelector('.product-description').textContent.replace('...', '');
                var productPrice = productItem.querySelector('.product-price').textContent.replace('$', '');
                var productStock = productItem.querySelector('.product-stock').textContent.replace('Stock: ', '');
                var productCategory = productItem.querySelector('.product-category').textContent.replace('Category: ', '');

                // Set form values
                document.getElementById('edit_product_id').value = productId;
                document.getElementById('edit_product_name').value = productName;
                document.getElementById('edit_product_description').value = productDescription;
                document.getElementById('edit_product_price').value = productPrice;
                document.getElementById('edit_product_stock').value = productStock;
                document.getElementById('edit_product_category').value = productCategory;

                // Show the modal
                document.getElementById('editProductModal').style.display = 'flex';
            }
        }

        // Close Edit Form Modal
        function closeEditForm() {
            document.getElementById('editProductModal').style.display = 'none';
        }

        // Close modal when clicking outside the modal content
        window.onclick = function(event) {
            var modal = document.getElementById('editProductModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>
<?php
// Close the database connection
$conn->close();
?>
