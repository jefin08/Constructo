<?php
session_start();
include '../db_connect.php';

// Get product IDs from URL
$ids_string = isset($_GET['ids']) ? $_GET['ids'] : '';
$ids_array = array_filter(explode(',', $ids_string), 'is_numeric');

$products = [];
if (!empty($ids_array)) {
    // Create placeholders for the IN clause
    $placeholders = implode(',', array_fill(0, count($ids_array), '?'));

    $sql = "
        SELECT p.*, c.category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE p.id IN ($placeholders)
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute($ids_array);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

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
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compare Products - Constructo</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Modern Design System */
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
            padding: 0.5rem 1rem;
            border-radius: var(--radius-md);
            font-weight: 600;
            text-decoration: none;
            transition: var(--transition);
            cursor: pointer;
            border: none;
            gap: 0.5rem;
            font-size: 0.9rem;
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

        .page-header {
            margin-bottom: 2rem;
            text-align: center;
        }

        .page-title {
            font-size: 2rem;
            color: var(--primary);
            font-weight: 700;
        }

        .page-subtitle {
            color: var(--text-light);
        }

        /* Comparison Table */
        .compare-container {
            overflow-x: auto;
            background: white;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            border: 1px solid #f1f5f9;
        }

        .compare-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
        }

        .compare-table th,
        .compare-table td {
            padding: 1.5rem;
            text-align: left;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: top;
        }

        .compare-table th {
            width: 200px;
            background: #f8fafc;
            color: var(--text-light);
            font-weight: 600;
            position: sticky;
            left: 0;
            z-index: 10;
            border-right: 1px solid #e2e8f0;
        }

        .compare-table td {
            min-width: 200px;
            border-right: 1px solid #f1f5f9;
        }

        .compare-table tr:last-child td,
        .compare-table tr:last-child th {
            border-bottom: none;
        }

        .product-header {
            text-align: center;
        }

        .product-img {
            width: 150px;
            height: 150px;
            object-fit: contain;
            margin-bottom: 1rem;
            mix-blend-mode: multiply;
        }

        .product-name {
            font-weight: 600;
            color: var(--primary);
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
            display: block;
            text-decoration: none;
        }

        .product-price {
            color: var(--accent);
            font-weight: 700;
            font-size: 1.25rem;
            margin-bottom: 1rem;
        }

        .remove-btn {
            color: #ef4444;
            font-size: 0.85rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            margin-top: 0.5rem;
            text-decoration: none;
        }

        .remove-btn:hover {
            text-decoration: underline;
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

        @media (max-width: 900px) {
            .nav-links {
                display: none;
            }
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

        function removeProduct(productId) {
            const urlParams = new URLSearchParams(window.location.search);
            let ids = urlParams.get('ids').split(',');
            ids = ids.filter(id => id != productId);

            if (ids.length > 0) {
                window.location.href = 'compare.php?ids=' + ids.join(',');
            } else {
                window.location.href = 'index.php';
            }
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
        <div class="page-header">
            <h1 class="page-title">Compare Products</h1>
            <p class="page-subtitle">See the differences side-by-side</p>
        </div>

        <?php if (empty($products)): ?>
            <div
                style="text-align: center; padding: 4rem; background: white; border-radius: var(--radius-lg); box-shadow: var(--shadow-sm);">
                <i class="fas fa-balance-scale" style="font-size: 3rem; color: #cbd5e1; margin-bottom: 1.5rem;"></i>
                <h2 style="color: var(--primary); margin-bottom: 0.5rem;">No Products to Compare</h2>
                <p style="color: var(--text-light); margin-bottom: 2rem;">Select products from the catalog to compare them
                    here.</p>
                <a href="index.php" class="btn btn-primary">Browse Products</a>
            </div>
        <?php else: ?>
            <div class="compare-container">
                <table class="compare-table">
                    <!-- Product Header -->
                    <tr>
                        <th>Product</th>
                        <?php foreach ($products as $product): ?>
                            <?php
                            $imgUrl = $product['image_url'];
                            if (!filter_var($imgUrl, FILTER_VALIDATE_URL)) {
                                $imgUrl = str_replace(['uploaded_images/', 'admin/'], '', $imgUrl);
                                $imgUrl = STORAGE_URL . $imgUrl;
                            }
                            ?>
                            <td class="product-header">
                                <a href="product_detail.php?id=<?php echo $product['id']; ?>">
                                    <img src="<?php echo htmlspecialchars($imgUrl); ?>"
                                        alt="<?php echo htmlspecialchars($product['product_name']); ?>" class="product-img">
                                </a>
                                <a href="product_detail.php?id=<?php echo $product['id']; ?>" class="product-name">
                                    <?php echo htmlspecialchars($product['product_name']); ?>
                                </a>
                                <div class="product-price">₹<?php echo number_format($product['price'], 2); ?></div>
                                <button class="btn btn-primary" onclick="addToCart(<?php echo $product['id']; ?>)">
                                    <i class="fas fa-shopping-cart"></i> Add to Cart
                                </button>
                                <br>
                                <span class="remove-btn" onclick="removeProduct(<?php echo $product['id']; ?>)">
                                    <i class="fas fa-times"></i> Remove
                                </span>
                            </td>
                        <?php endforeach; ?>
                    </tr>

                    <!-- Rating -->
                    <tr>
                        <th>Rating</th>
                        <?php foreach ($products as $product): ?>
                            <td>
                                <div style="color: #fbbf24; font-size: 0.9rem;">
                                    <?php
                                    $rating = isset($product['rating']) ? $product['rating'] : 0;
                                    for ($i = 0; $i < 5; $i++) {
                                        echo $i < round($rating) ? '★' : '☆';
                                    }
                                    ?>
                                    <span
                                        style="color: var(--text-light); margin-left: 5px;">(<?php echo number_format($rating, 1); ?>)</span>
                                </div>
                            </td>
                        <?php endforeach; ?>
                    </tr>

                    <!-- Brand -->
                    <tr>
                        <th>Brand</th>
                        <?php foreach ($products as $product): ?>
                            <td><?php echo htmlspecialchars($product['brand']); ?></td>
                        <?php endforeach; ?>
                    </tr>

                    <!-- Category -->
                    <tr>
                        <th>Category</th>
                        <?php foreach ($products as $product): ?>
                            <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                        <?php endforeach; ?>
                    </tr>

                    <!-- Availability -->
                    <tr>
                        <th>Availability</th>
                        <?php foreach ($products as $product): ?>
                            <td>
                                <?php if ($product['stock'] > 0): ?>
                                    <span
                                        style="color: #166534; background: #dcfce7; padding: 0.2rem 0.6rem; border-radius: 20px; font-size: 0.85rem; font-weight: 600;">In
                                        Stock</span>
                                <?php else: ?>
                                    <span
                                        style="color: #991b1b; background: #fee2e2; padding: 0.2rem 0.6rem; border-radius: 20px; font-size: 0.85rem; font-weight: 600;">Out
                                        of Stock</span>
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>

                    <!-- Weight -->
                    <tr>
                        <th>Weight</th>
                        <?php foreach ($products as $product): ?>
                            <td><?php echo htmlspecialchars($product['weight']); ?> kg</td>
                        <?php endforeach; ?>
                    </tr>

                    <!-- Packing Type -->
                    <tr>
                        <th>Packing Type</th>
                        <?php foreach ($products as $product): ?>
                            <td><?php echo htmlspecialchars($product['type_of_packing']); ?></td>
                        <?php endforeach; ?>
                    </tr>

                    <!-- Quality -->
                    <tr>
                        <th>Quality</th>
                        <?php foreach ($products as $product): ?>
                            <td><?php echo htmlspecialchars($product['quality']); ?></td>
                        <?php endforeach; ?>
                    </tr>

                    <!-- Description -->
                    <tr>
                        <th>Description</th>
                        <?php foreach ($products as $product): ?>
                            <td style="font-size: 0.9rem; color: var(--text-light); line-height: 1.5;">
                                <?php echo nl2br(htmlspecialchars(substr($product['description'], 0, 150))) . (strlen($product['description']) > 150 ? '...' : ''); ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                </table>
            </div>
        <?php endif; ?>
    </main>

    <footer>
        <div class="container" style="text-align: center; padding: 2rem 0; color: #94a3b8;">
            <p>&copy; <?php echo date("Y"); ?> Constructo. All rights reserved.</p>
        </div>
    </footer>

    <div id="toast-container" class="toast-container"></div>
</body>

</html>