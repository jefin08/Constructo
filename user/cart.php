<?php
session_start();
include '../db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$first_name = '';
$last_name = '';

// Fetch User Info
try {
    $stmt = $conn->prepare("SELECT first_name, last_name FROM clients WHERE id = :id");
    $stmt->execute([':id' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        $first_name = $user['first_name'];
        $last_name = $user['last_name'];
    }
} catch (PDOException $e) {
    // Handle error silently
}

// Fetch Cart Items
$cart_items = [];
$total_price = 0;

try {
    $sql = "SELECT c.product_id, c.quantity, p.product_name, p.price, p.image_url, p.stock 
            FROM cart c 
            JOIN products p ON c.product_id = p.id 
            WHERE c.client_id = :client_id";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':client_id' => $user_id]);
    $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($cart_items as $item) {
        $total_price += $item['price'] * $item['quantity'];
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
    <title>My Cart - Constructo</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
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
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* Navbar */
        nav {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 1.5rem;
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
            height: 40px;
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
            box-shadow: var(--shadow-md);
            border-radius: var(--radius-md);
            z-index: 1000;
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

        /* Cart Page */
        .main-content {
            padding: 4rem 0;
            flex: 1;
        }

        .page-header {
            margin-bottom: 2rem;
        }

        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .cart-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }

        .cart-items {
            background: var(--bg-card);
            border-radius: var(--radius-lg);
            padding: 2rem;
            box-shadow: var(--shadow-sm);
        }

        .cart-item {
            display: flex;
            align-items: center;
            padding: 1.5rem 0;
            border-bottom: 1px solid #f1f5f9;
            gap: 1.5rem;
        }

        .cart-item:last-child {
            border-bottom: none;
        }

        .item-img {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: var(--radius-md);
            background: #f1f5f9;
        }

        .item-details {
            flex: 1;
        }

        .item-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .item-price {
            font-weight: 700;
            color: var(--accent);
            font-size: 1.1rem;
        }

        .item-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-top: 0.5rem;
        }

        .qty-input {
            width: 60px;
            padding: 0.5rem;
            border: 1px solid #cbd5e1;
            border-radius: var(--radius-md);
            text-align: center;
        }

        .btn-remove {
            color: #ef4444;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
            transition: color 0.2s;
        }

        .btn-remove:hover {
            color: #dc2626;
            text-decoration: underline;
        }

        .cart-summary {
            background: var(--bg-card);
            border-radius: var(--radius-lg);
            padding: 2rem;
            box-shadow: var(--shadow-sm);
            height: fit-content;
            position: sticky;
            top: 100px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
            color: var(--text-main);
        }

        .summary-total {
            border-top: 2px solid #f1f5f9;
            padding-top: 1rem;
            margin-top: 1rem;
            font-weight: 700;
            font-size: 1.25rem;
            color: var(--primary);
        }

        .btn-checkout {
            display: block;
            width: 100%;
            padding: 1rem;
            background: var(--primary);
            color: white;
            text-align: center;
            border-radius: var(--radius-md);
            text-decoration: none;
            font-weight: 600;
            margin-top: 2rem;
            transition: var(--transition);
        }

        .btn-checkout:hover {
            background: var(--primary-light);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        /* Empty State */
        .empty-cart {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--text-light);
        }

        .empty-cart i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #cbd5e1;
        }

        /* Footer */
        footer {
            background: var(--primary);
            color: white;
            padding: 2rem 0;
            text-align: center;
            margin-top: auto;
        }

        @media (max-width: 900px) {
            .cart-grid {
                grid-template-columns: 1fr;
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

        async function updateQuantity(productId, quantity) {
            if (quantity < 1) return;

            try {
                const response = await fetch('update_cart.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ product_id: productId, quantity: quantity })
                });

                const data = await response.json();
                if (data.status === 'success') {
                    location.reload();
                } else {
                    alert(data.message);
                }
            } catch (error) {
                console.error('Error:', error);
            }
        }

        async function removeItem(productId) {
            if (!confirm('Are you sure you want to remove this item?')) return;

            try {
                const response = await fetch('remove_from_cart.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ product_id: productId })
                });

                const data = await response.json();
                if (data.status === 'success') {
                    location.reload();
                } else {
                    alert(data.message);
                }
            } catch (error) {
                console.error('Error:', error);
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
        </div>
    </nav>

    <main class="container main-content">
        <header class="page-header">
            <h1 class="page-title">Shopping Cart</h1>
            <p style="color: var(--text-light);">Review your items before checkout.</p>
        </header>

        <?php if (count($cart_items) > 0): ?>
            <div class="cart-grid">
                <!-- Cart Items List -->
                <div class="cart-items">
                    <?php foreach ($cart_items as $item):
                        $imgUrl = $item['image_url'];
                        if (!filter_var($imgUrl, FILTER_VALIDATE_URL)) {
                            $filename = str_replace(['uploaded_images/', 'admin/'], '', $imgUrl);
                            $imgUrl = STORAGE_URL . $filename;
                        }
                        ?>
                        <div class="cart-item">
                            <img src="<?php echo htmlspecialchars($imgUrl); ?>" alt="Product" class="item-img">
                            <div class="item-details">
                                <div class="item-name">
                                    <?php echo htmlspecialchars($item['product_name']); ?>
                                </div>
                                <div class="item-price">₹
                                    <?php echo number_format($item['price'], 2); ?>
                                </div>
                                <div class="item-actions">
                                    <input type="number" class="qty-input" value="<?php echo $item['quantity']; ?>" min="1"
                                        max="<?php echo $item['stock']; ?>"
                                        onchange="updateQuantity(<?php echo $item['product_id']; ?>, this.value)">
                                    <button class="btn-remove" onclick="removeItem(<?php echo $item['product_id']; ?>)">
                                        <i class="fas fa-trash"></i> Remove
                                    </button>
                                </div>
                            </div>
                            <div style="font-weight: 600; color: var(--text-main);">
                                ₹
                                <?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Order Summary -->
                <div class="cart-summary">
                    <h3 style="margin-bottom: 1.5rem; font-size: 1.25rem; color: var(--primary);">Summary</h3>
                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span>₹
                            <?php echo number_format($total_price, 2); ?>
                        </span>
                    </div>
                    <div class="summary-row">
                        <span>Shipping & Taxes</span>
                        <span style="font-size: 0.9em; color: var(--text-light);">Calculated at Checkout</span>
                    </div>
                    <div class="summary-total summary-row">
                        <span>Subtotal</span>
                        <span>₹<?php echo number_format($total_price, 2); ?></span>
                    </div>
                    <p style="font-size: 0.85rem; color: var(--text-light); margin-bottom: 1.5rem;">
                        Taxes calculated at checkout.
                    </p>
                    <a href="checkout.php" class="btn-checkout">Proceed to Checkout <i class="fas fa-arrow-right"></i></a>
                    <a href="index.php"
                        style="display: block; text-align: center; margin-top: 1rem; color: var(--text-light); text-decoration: none;">Continue
                        Shopping</a>
                </div>
            </div>
        <?php else: ?>
            <div class="empty-cart">
                <i class="fas fa-shopping-cart"></i>
                <h2>Your cart is empty</h2>
                <p>Looks like you haven't added anything yet.</p>
                <a href="index.php" class="btn-checkout" style="max-width: 200px; margin: 2rem auto;">Start Shopping</a>
            </div>
        <?php endif; ?>

    </main>

    <footer>
        <div class="container copyright">
            <p>&copy;
                <?php echo date("Y"); ?> Constructo. All rights reserved.
            </p>
        </div>
    </footer>

</body>

</html>