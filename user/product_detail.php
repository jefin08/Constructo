<?php
session_start();
include '../db_connect.php';

// Fetch product details based on product ID
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$in_wishlist = false; // Initialize to avoid undefined variable warning

try {
    // Fetch product details
    $sql = "
        SELECT p.*, c.category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE p.id = :id
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':id', $product_id);
    $stmt->execute();
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        echo "Product not found.";
        exit;
    }

    // Fetch Related Products (Same Category, exclude current)
    $related_sql = "
        SELECT * FROM products 
        WHERE category_id = :cat_id AND id != :pid 
        LIMIT 4
    ";
    $related_stmt = $conn->prepare($related_sql);
    $related_stmt->bindParam(':cat_id', $product['category_id']);
    $related_stmt->bindParam(':pid', $product_id);
    $related_stmt->execute();
    $related_products = $related_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch user info for header
    $first_name = "";
    $last_name = "";
    if (isset($_SESSION['user_id'])) {
        $user_stmt = $conn->prepare("SELECT first_name, last_name FROM clients WHERE id = :id");
        $user_stmt->bindParam(':id', $_SESSION['user_id']);
        $user_stmt->execute();
        $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            $first_name = $user['first_name'];
            $last_name = $user['last_name'];
        }

        // Check if product is in wishlist
        $wish_check = $conn->prepare("SELECT 1 FROM wishlist WHERE user_id = :uid AND product_id = :pid");
        $wish_check->execute([':uid' => $_SESSION['user_id'], ':pid' => $product_id]);
        $in_wishlist = $wish_check->rowCount() > 0;
    } else {
        $in_wishlist = false;
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['product_name']); ?> - Constructo</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Modern Design System (Matches index.php) */
        :root {
            --primary: #0f172a;
            --primary-light: #1e293b;
            --accent: #f59e0b;
            --accent-hover: #d97706;
            --text-main: #334155;
            --text-light: #64748b;
            --bg-body: #f8fafc;
            --bg-card: #ffffff;
            --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.12), 0 1px 2px rgba(0, 0, 0, 0.24);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --radius-md: 0.75rem;
            --radius-lg: 1rem;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg-body);
            color: var(--text-main);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Utilities */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1.5rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem 1.5rem;
            border-radius: var(--radius-md);
            font-weight: 600;
            text-decoration: none;
            transition: var(--transition);
            cursor: pointer;
            border: none;
            gap: 0.5rem;
            font-size: 1rem;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-light);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .btn-accent {
            background: var(--accent);
            color: white;
        }

        .btn-accent:hover {
            background: var(--accent-hover);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .btn-outline {
            border: 1px solid #cbd5e1;
            color: var(--text-main);
            background: white;
        }

        .btn-outline:hover {
            border-color: var(--primary);
            color: var(--primary);
        }

        /* Navbar */
        nav {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 0.75rem 0;
        }

        .nav-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
        }

        .logo img {
            height: 60px;
            width: auto;
        }

        .nav-links {
            display: flex;
            gap: 2rem;
            align-items: center;
        }

        .nav-links a {
            color: var(--text-main);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
        }

        .nav-links a:hover {
            color: var(--accent);
        }

        /* Profile Dropdown */
        .profile-menu {
            position: relative;
            cursor: pointer;
        }

        .profile-circle {
            width: 40px;
            height: 40px;
            background: var(--accent);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1rem;
            box-shadow: var(--shadow-sm);
        }

        .dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            top: 110%;
            background-color: white;
            min-width: 160px;
            box-shadow: var(--shadow-lg);
            border-radius: var(--radius-md);
            z-index: 1;
            overflow: hidden;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .dropdown-content.show {
            display: block;
            animation: fadeIn 0.2s ease;
        }

        .dropdown-content a {
            color: var(--text-main);
            padding: 12px 16px;
            text-decoration: none;
            display: block;
            font-size: 0.9rem;
        }

        .dropdown-content a:hover {
            background-color: #f1f5f9;
            color: var(--primary);
        }

        /* Main Content */
        .main-content {
            margin-top: 100px;
            padding-bottom: 4rem;
        }

        /* Breadcrumbs */
        .breadcrumbs {
            margin-bottom: 2rem;
            color: var(--text-light);
            font-size: 0.9rem;
        }

        .breadcrumbs a {
            color: var(--text-light);
            text-decoration: none;
            transition: var(--transition);
        }

        .breadcrumbs a:hover {
            color: var(--accent);
        }

        .breadcrumbs span {
            margin: 0 0.5rem;
            opacity: 0.5;
        }

        .breadcrumbs .current {
            color: var(--primary);
            font-weight: 500;
        }

        /* Product Detail Grid */
        .product-detail-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            margin-bottom: 4rem;
        }

        /* Image Section */
        .product-gallery {
            background: white;
            border-radius: var(--radius-lg);
            padding: 2rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .product-gallery img {
            max-width: 100%;
            max-height: 500px;
            object-fit: contain;
            transition: transform 0.3s ease;
        }

        .product-gallery img:hover {
            transform: scale(1.05);
        }

        /* Info Section */
        .product-info h1 {
            font-size: 2.5rem;
            color: var(--primary);
            line-height: 1.2;
            margin-bottom: 0.5rem;
        }

        .product-meta {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
            color: var(--text-light);
            font-size: 0.9rem;
        }

        .rating {
            color: #fbbf24;
            font-size: 1rem;
        }

        .stock-status {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .in-stock {
            background: #dcfce7;
            color: #166534;
        }

        .out-of-stock {
            background: #fee2e2;
            color: #991b1b;
        }

        .price-block {
            margin-bottom: 2rem;
        }

        .current-price {
            font-size: 2rem;
            font-weight: 700;
            color: var(--accent);
        }

        .actions {
            display: flex;
            gap: 1rem;
            margin-bottom: 2.5rem;
        }

        .actions .btn {
            flex: 1;
        }

        /* Tabs */
        .tabs {
            margin-bottom: 2rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .tab-btn {
            background: none;
            border: none;
            padding: 1rem 1.5rem;
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-light);
            cursor: pointer;
            border-bottom: 2px solid transparent;
            transition: var(--transition);
        }

        .tab-btn.active {
            color: var(--primary);
            border-bottom-color: var(--accent);
        }

        .tab-content {
            display: none;
            animation: fadeIn 0.3s ease;
        }

        .tab-content.active {
            display: block;
        }

        .spec-table {
            width: 100%;
            border-collapse: collapse;
        }

        .spec-table th,
        .spec-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #f1f5f9;
        }

        .spec-table th {
            width: 30%;
            color: var(--text-light);
            font-weight: 500;
        }

        .spec-table td {
            color: var(--primary);
            font-weight: 600;
        }

        /* Related Products */
        .related-section {
            margin-top: 4rem;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 1.5rem;
        }

        .related-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.5rem;
        }

        .product-card {
            background: var(--bg-card);
            border-radius: var(--radius-md);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
            border: 1px solid rgba(0, 0, 0, 0.05);
            display: flex;
            flex-direction: column;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .product-img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background: #f1f5f9;
        }

        .card-info {
            padding: 1rem;
        }

        .card-title {
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 0.5rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .card-price {
            color: var(--accent);
            font-weight: 700;
        }

        /* Footer */
        footer {
            background: var(--primary);
            color: white;
            padding: 2rem 0;
            text-align: center;
            margin-top: auto;
        }

        .copyright {
            color: #94a3b8;
            font-size: 0.9rem;
        }

        @media (max-width: 900px) {
            .product-detail-grid {
                grid-template-columns: 1fr;
                gap: 2rem;
            }

            .related-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 600px) {
            .related-grid {
                grid-template-columns: 1fr;
            }

            .actions {
                flex-direction: column;
            }
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Toast Notification */
        .toast-container {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            z-index: 2000;
            display: flex;
            flex-direction: column;
            gap: 1rem;
            pointer-events: none;
        }

        .toast {
            background: white;
            padding: 1rem 1.5rem;
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-lg);
            display: flex;
            align-items: center;
            gap: 1rem;
            transform: translateX(120%);
            transition: transform 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            pointer-events: auto;
            border-left: 4px solid var(--accent);
            min-width: 300px;
        }

        .toast.show {
            transform: translateX(0);
        }

        .toast-icon {
            width: 24px;
            height: 24px;
            background: #ecfdf5;
            color: #10b981;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
        }

        .toast-icon.error {
            background: #fef2f2;
            color: #ef4444;
        }

        .toast-content {
            flex: 1;
        }

        .toast-title {
            font-weight: 600;
            font-size: 0.95rem;
            color: var(--primary);
            margin-bottom: 0.1rem;
        }

        .toast-message {
            font-size: 0.85rem;
            color: var(--text-light);
        }

        .toast-close {
            cursor: pointer;
            color: #94a3b8;
            transition: color 0.2s;
        }

        .toast-close:hover {
            color: var(--primary);
        }
    </style>
    <script>
        function toggleDropdown() {
            document.getElementById("dropdown").classList.toggle("show");
        }
        window.onclick = function (event) {
            if (!event.target.matches('.profile-circle')) {
                var dropdowns = document.getElementsByClassName("dropdown-content");
                for (var i = 0; i < dropdowns.length; i++) {
                    var openDropdown = dropdowns[i];
                    if (openDropdown.classList.contains('show')) openDropdown.classList.remove('show');
                }
            }
        }
        function switchTab(tabId) {
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.getElementById(tabId).classList.add('active');
            event.target.classList.add('active');
        }

        function toggleWishlist(productId, btn) {
            fetch('wishlist_action.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=toggle&product_id=' + productId
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const icon = btn.querySelector('i');
                        if (data.status === 'added') {
                            btn.style.background = '#fee2e2';
                            btn.style.color = '#ef4444';
                            btn.style.borderColor = '#ef4444';
                            icon.classList.remove('far');
                            icon.classList.add('fas');
                        } else {
                            btn.style.background = 'white';
                            btn.style.color = 'var(--text-main)';
                            btn.style.borderColor = '#cbd5e1';
                            icon.classList.remove('fas');
                            icon.classList.add('far');
                        }
                    } else {
                        alert(data.message);
                    }
                });
        }

        function showToast(title, message, type = 'success') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            toast.className = 'toast';

            const iconClass = type === 'success' ? 'fas fa-check' : 'fas fa-exclamation';
            const iconStyle = type === 'success' ? '' : 'error';

            toast.innerHTML = `
                <div class="toast-icon ${iconStyle}"><i class="${iconClass}"></i></div>
                <div class="toast-content">
                    <div class="toast-title">${title}</div>
                    <div class="toast-message">${message}</div>
                </div>
                <i class="fas fa-times toast-close" onclick="this.parentElement.remove()"></i>
            `;

            container.appendChild(toast);

            // Trigger reflow
            void toast.offsetWidth;

            toast.classList.add('show');

            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 400);
            }, 3000);
        }

        function addToCart(productId) {
            fetch('add_to_cart.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ product_id: productId, quantity: 1 })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        showToast('Added to Cart', 'Product has been added to your cart.');
                    } else {
                        showToast('Error', data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('Error', 'Something went wrong. Please try again.', 'error');
                });
        }
    </script>
</head>

<body>

    <nav>
        <div class="container nav-container">
            <div class="logo">
                <img src="../images/logo.png" alt="Constructo Logo">
                Constructo
            </div>
            <div class="nav-links">
                <a href="index.php">Home</a>
                <a href="index.php#product-list">Products</a>
                <a href="../about_us.html">About Us</a>
                <a href="../messages.php">Contact Us</a>
            </div>
            <?php if (!empty($first_name)): ?>
                <div class="profile-menu">
                    <div class="profile-circle" onclick="toggleDropdown()">
                        <?php echo strtoupper(substr($first_name, 0, 1)) . strtoupper(substr($last_name, 0, 1)); ?>
                    </div>
                    <div class="dropdown-content" id="dropdown">
                        <a href="wishlist.php"><i class="fas fa-heart"></i> My Wishlist</a>
                        <a href="cart.php"><i class="fas fa-shopping-cart"></i> My Cart</a>
                        <a href="orders.php"><i class="fas fa-box"></i> My Orders</a>
                        <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </nav>

    <main class="container main-content">
        <!-- Breadcrumbs -->
        <div class="breadcrumbs">
            <a href="index.php">Home</a> <span>/</span>
            <a
                href="index.php?category=<?php echo $product['category_id']; ?>"><?php echo htmlspecialchars($product['category_name']); ?></a>
            <span>/</span>
            <span class="current"><?php echo htmlspecialchars($product['product_name']); ?></span>
        </div>

        <div class="product-detail-grid">
            <!-- Left: Image -->
            <div class="product-gallery">
                <?php
                $imgUrl = $product['image_url'];
                if (!filter_var($imgUrl, FILTER_VALIDATE_URL)) {
                    $imgUrl = str_replace(['uploaded_images/', 'admin/'], '', $imgUrl);
                    $imgUrl = STORAGE_URL . $imgUrl;
                }
                ?>
                <img src="<?php echo htmlspecialchars($imgUrl); ?>"
                    alt="<?php echo htmlspecialchars($product['product_name']); ?>">
            </div>

            <!-- Right: Info -->
            <div class="product-info">
                <h1><?php echo htmlspecialchars($product['product_name']); ?></h1>

                <div class="product-meta">
                    <div class="rating">
                        <?php for ($i = 0; $i < 5; $i++) {
                            echo $i < round($product['rating']) ? '★' : '☆';
                        } ?>
                        <span
                            style="color: var(--text-light); margin-left: 5px;">(<?php echo number_format($product['rating'], 1); ?>)</span>
                    </div>
                    <span>|</span>
                    <span>Brand: <strong><?php echo htmlspecialchars($product['brand']); ?></strong></span>
                    <span>|</span>
                    <span class="stock-status <?php echo $product['stock'] > 0 ? 'in-stock' : 'out-of-stock'; ?>">
                        <?php echo $product['stock'] > 0 ? 'In Stock' : 'Out of Stock'; ?>
                    </span>
                </div>

                <div class="price-block">
                    <div class="current-price">₹<?php echo number_format($product['price'], 2); ?></div>
                    <p style="font-size: 0.9rem; color: var(--text-light);">Inclusive of all taxes</p>
                </div>

                <div class="actions">
                    <button class="btn btn-primary" onclick="addToCart(<?php echo $product_id; ?>)"><i
                            class="fas fa-shopping-cart"></i> Add to Cart</button>
                    <button class="btn btn-outline" onclick="toggleWishlist(<?php echo $product_id; ?>, this)"
                        style="<?php echo $in_wishlist ? 'background: #fee2e2; color: #ef4444; border-color: #ef4444;' : ''; ?>">
                        <i class="<?php echo $in_wishlist ? 'fas' : 'far'; ?> fa-heart"></i> Wishlist
                    </button>
                </div>

                <!-- Tabs -->
                <div class="tabs">
                    <button class="tab-btn active" onclick="switchTab('desc')">Description</button>
                    <button class="tab-btn" onclick="switchTab('specs')">Specifications</button>
                </div>

                <div id="desc" class="tab-content active">
                    <p style="color: var(--text-main); line-height: 1.8;">
                        <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                    </p>
                </div>

                <div id="specs" class="tab-content">
                    <table class="spec-table">
                        <tr>
                            <th>Brand</th>
                            <td><?php echo htmlspecialchars($product['brand']); ?></td>
                        </tr>
                        <tr>
                            <th>Category</th>
                            <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                        </tr>
                        <tr>
                            <th>Weight</th>
                            <td><?php echo htmlspecialchars($product['weight']); ?> kg</td>
                        </tr>
                        <tr>
                            <th>Packing Type</th>
                            <td><?php echo htmlspecialchars($product['type_of_packing']); ?></td>
                        </tr>
                        <tr>
                            <th>Quality</th>
                            <td><?php echo htmlspecialchars($product['quality']); ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- Related Products -->
        <?php if (count($related_products) > 0): ?>
            <div class="related-section">
                <h2 class="section-title">Related Products</h2>
                <div class="related-grid">
                    <?php foreach ($related_products as $rel): ?>
                        <?php
                        $relImg = $rel['image_url'];
                        if (!filter_var($relImg, FILTER_VALIDATE_URL)) {
                            $relImg = str_replace(['uploaded_images/', 'admin/'], '', $relImg);
                            $relImg = STORAGE_URL . $relImg;
                        }
                        ?>
                        <div class="product-card">
                            <a href="product_detail.php?id=<?php echo $rel['id']; ?>"
                                style="text-decoration: none; color: inherit;">
                                <img src="<?php echo htmlspecialchars($relImg); ?>"
                                    alt="<?php echo htmlspecialchars($rel['product_name']); ?>" class="product-img">
                                <div class="card-info">
                                    <div class="card-title"><?php echo htmlspecialchars($rel['product_name']); ?></div>
                                    <div class="card-price">₹<?php echo htmlspecialchars($rel['price']); ?></div>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

    </main>

    <footer>
        <div class="container">
            <div class="copyright">
                <p>&copy; <?php echo date("Y"); ?> Constructo. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <div id="toast-container" class="toast-container"></div>
</body>

</html>