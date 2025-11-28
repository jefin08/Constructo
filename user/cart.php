<?php
session_start();
include '../db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['cart_id']) && isset($_POST['quantity'])) {
        $cart_id = intval($_POST['cart_id']);
        $quantity = intval($_POST['quantity']);
        if($quantity < 1) $quantity = 1;

        $stmt = $conn->prepare("UPDATE cart SET quantity = :quantity WHERE id = :id AND client_id = :client_id");
        $stmt->execute([':quantity' => $quantity, ':id' => $cart_id, ':client_id' => $user_id]);
    } elseif (isset($_POST['remove_cart_id'])) {
        $remove_cart_id = intval($_POST['remove_cart_id']);
        $stmt = $conn->prepare("DELETE FROM cart WHERE id = :id AND client_id = :client_id");
        $stmt->execute([':id' => $remove_cart_id, ':client_id' => $user_id]);
    }
}

// Fetch Cart
$cart_sql = "SELECT c.id AS cart_id, c.product_id, c.quantity, 
                    p.product_name, p.brand, p.price, p.image_url, p.stock
             FROM cart c
             JOIN products p ON c.product_id = p.id
             WHERE c.client_id = :client_id";
$stmt = $conn->prepare($cart_sql);
$stmt->execute([':client_id' => $user_id]);
$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$subtotal = 0;
foreach ($cart_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

$shipping = count($cart_items) > 0 ? 59 : 0;
$gst = $subtotal * 0.10;
$grand_total = $subtotal + $shipping + $gst;

// User Info for Nav
$first_name = "";
$last_name = "";
$user_stmt = $conn->prepare("SELECT first_name, last_name FROM clients WHERE id = :id");
$user_stmt->execute([':id' => $user_id]);
$user = $user_stmt->fetch(PDO::FETCH_ASSOC);
if ($user) {
    $first_name = $user['first_name'];
    $last_name = $user['last_name'];
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
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.12), 0 1px 2px rgba(0,0,0,0.24);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --radius-md: 0.75rem;
            --radius-lg: 1rem;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg-body);
            color: var(--text-main);
            line-height: 1.6;
            min-height: 100vh;
            display: flex; flex-direction: column;
        }

        /* Navbar */
        nav {
            position: fixed; top: 0; left: 0; right: 0; z-index: 1000;
            background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(0,0,0,0.05); padding: 0.75rem 0;
        }
        .container { max-width: 1200px; margin: 0 auto; padding: 0 1.5rem; }
        .nav-container { display: flex; justify-content: space-between; align-items: center; }
        .logo { display: flex; align-items: center; gap: 0.75rem; font-size: 1.5rem; font-weight: 700; color: var(--primary); }
        .logo img { height: 36px; width: auto; }
        .nav-links { display: flex; gap: 2rem; align-items: center; }
        .nav-links a { color: var(--text-main); text-decoration: none; font-weight: 500; transition: var(--transition); }
        .nav-links a:hover { color: var(--accent); }

        /* Profile Dropdown */
        .profile-menu { position: relative; cursor: pointer; }
        .profile-circle {
            width: 40px; height: 40px; background: var(--accent); color: white;
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 1rem; box-shadow: var(--shadow-sm);
        }
        .dropdown-content {
            display: none; position: absolute; right: 0; top: 110%;
            background-color: white; min-width: 160px;
            box-shadow: var(--shadow-lg); border-radius: var(--radius-md);
            z-index: 1; overflow: hidden; border: 1px solid rgba(0,0,0,0.05);
        }
        .dropdown-content.show { display: block; animation: fadeIn 0.2s ease; }
        .dropdown-content a {
            color: var(--text-main); padding: 12px 16px; text-decoration: none;
            display: block; font-size: 0.9rem;
        }
        .dropdown-content a:hover { background-color: #f1f5f9; color: var(--primary); }

        /* Main Content */
        .main-content { margin-top: 100px; padding-bottom: 4rem; flex: 1; }
        .page-title { font-size: 2rem; color: var(--primary); font-weight: 700; margin-bottom: 2rem; }

        .cart-layout {
            display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;
        }

        /* Cart Items */
        .cart-items { display: flex; flex-direction: column; gap: 1.5rem; }
        .cart-item {
            background: white; border-radius: var(--radius-md); padding: 1.5rem;
            display: flex; gap: 1.5rem; align-items: center;
            box-shadow: var(--shadow-sm); border: 1px solid rgba(0,0,0,0.05);
        }
        .item-img {
            width: 100px; height: 100px; object-fit: cover; border-radius: var(--radius-md);
            background: #f1f5f9;
        }
        .item-details { flex: 1; }
        .item-name { font-weight: 600; color: var(--primary); font-size: 1.1rem; margin-bottom: 0.25rem; }
        .item-brand { color: var(--text-light); font-size: 0.9rem; margin-bottom: 0.5rem; }
        .item-price { color: var(--accent); font-weight: 700; font-size: 1.1rem; }

        .item-actions { display: flex; align-items: center; gap: 2rem; }
        .qty-form { display: flex; align-items: center; gap: 0.5rem; }
        .qty-input {
            width: 60px; padding: 0.4rem; border: 1px solid #cbd5e1; border-radius: var(--radius-md);
            text-align: center; font-family: inherit;
        }
        .btn-update {
            background: none; border: none; color: var(--primary); font-weight: 600;
            cursor: pointer; font-size: 0.9rem; text-decoration: underline;
        }
        .btn-remove {
            background: none; border: none; color: #ef4444; cursor: pointer;
            display: flex; align-items: center; gap: 0.4rem; font-size: 0.9rem;
            transition: var(--transition);
        }
        .btn-remove:hover { color: #b91c1c; }

        /* Order Summary */
        .order-summary {
            background: white; border-radius: var(--radius-lg); padding: 2rem;
            box-shadow: var(--shadow-sm); border: 1px solid rgba(0,0,0,0.05);
            height: fit-content; position: sticky; top: 120px;
        }
        .summary-title { font-size: 1.25rem; font-weight: 700; color: var(--primary); margin-bottom: 1.5rem; }
        .summary-row { display: flex; justify-content: space-between; margin-bottom: 1rem; color: var(--text-main); }
        .summary-row.total {
            border-top: 1px solid #e2e8f0; padding-top: 1rem; margin-top: 1rem;
            font-weight: 700; font-size: 1.2rem; color: var(--primary);
        }
        .checkout-btn {
            display: block; width: 100%; padding: 1rem; background: var(--accent);
            color: white; text-align: center; text-decoration: none; border-radius: var(--radius-md);
            font-weight: 600; margin-top: 1.5rem; transition: var(--transition);
            border: none; cursor: pointer;
        }
        .checkout-btn:hover { background: var(--accent-hover); transform: translateY(-2px); box-shadow: var(--shadow-md); }
        .continue-link {
            display: block; text-align: center; margin-top: 1rem; color: var(--text-light);
            text-decoration: none; font-size: 0.9rem;
        }
        .continue-link:hover { color: var(--primary); text-decoration: underline; }

        .empty-cart {
            text-align: center; padding: 4rem 2rem; background: white;
            border-radius: var(--radius-lg); grid-column: 1 / -1;
        }

        /* Footer */
        footer { background: var(--primary); color: white; padding: 2rem 0; text-align: center; margin-top: auto; }
        .copyright { color: #94a3b8; font-size: 0.9rem; }

        @media (max-width: 900px) {
            .cart-layout { grid-template-columns: 1fr; }
            .order-summary { position: static; }
        }
        @media (max-width: 600px) {
            .cart-item { flex-direction: column; align-items: flex-start; }
            .item-actions { width: 100%; justify-content: space-between; margin-top: 1rem; }
        }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
    <script>
        function toggleDropdown() {
            document.getElementById("dropdown").classList.toggle("show");
        }
        window.onclick = function(event) {
            if (!event.target.matches('.profile-circle')) {
                var dropdowns = document.getElementsByClassName("dropdown-content");
                for (var i = 0; i < dropdowns.length; i++) {
                    var openDropdown = dropdowns[i];
                    if (openDropdown.classList.contains('show')) openDropdown.classList.remove('show');
                }
            }
        }
    </script>
</head>
<body>

    <nav>
        <div class="container nav-container">
            <div class="logo">
                <img src="https://yyeploxrzxwhhsnffexp.supabase.co/storage/v1/object/public/Construct_image/logo.png" alt="Constructo Logo">
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
        <h1 class="page-title">Your Shopping Cart</h1>

        <?php if (count($cart_items) > 0): ?>
        <div class="cart-layout">
            <!-- Cart Items List -->
            <div class="cart-items">
                <?php foreach ($cart_items as $item): ?>
                    <?php
                        $imgUrl = $item['image_url'];
                        if (!filter_var($imgUrl, FILTER_VALIDATE_URL)) {
                            $imgUrl = str_replace(['uploaded_images/', 'admin/'], '', $imgUrl);
                            $imgUrl = SUPABASE_STORAGE_URL . $imgUrl;
                        }
                    ?>
                    <div class="cart-item">
                        <img src="<?php echo htmlspecialchars($imgUrl); ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>" class="item-img">
                        <div class="item-details">
                            <div class="item-name"><?php echo htmlspecialchars($item['product_name']); ?></div>
                            <div class="item-brand"><?php echo htmlspecialchars($item['brand']); ?></div>
                            <div class="item-price">₹<?php echo number_format($item['price'], 2); ?></div>
                        </div>
                        <div class="item-actions">
                            <form method="POST" class="qty-form">
                                <input type="hidden" name="cart_id" value="<?php echo $item['cart_id']; ?>">
                                <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" min="1" class="qty-input" onchange="this.form.submit()">
                            </form>
                            <form method="POST">
                                <input type="hidden" name="remove_cart_id" value="<?php echo $item['cart_id']; ?>">
                                <button type="submit" class="btn-remove"><i class="fas fa-trash-alt"></i> Remove</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Order Summary -->
            <div class="order-summary">
                <h3 class="summary-title">Order Summary</h3>
                <div class="summary-row">
                    <span>Subtotal</span>
                    <span>₹<?php echo number_format($subtotal, 2); ?></span>
                </div>
                <div class="summary-row">
                    <span>GST (10%)</span>
                    <span>₹<?php echo number_format($gst, 2); ?></span>
                </div>
                <div class="summary-row">
                    <span>Shipping</span>
                    <span>₹<?php echo number_format($shipping, 2); ?></span>
                </div>
                <div class="summary-row total">
                    <span>Total</span>
                    <span>₹<?php echo number_format($grand_total, 2); ?></span>
                </div>
                
                <a href="checkout.php" class="checkout-btn">Proceed to Checkout</a>
                <a href="index.php" class="continue-link">Continue Shopping</a>
            </div>
        </div>
        <?php else: ?>
            <div class="empty-cart">
                <i class="fas fa-shopping-cart" style="font-size: 4rem; color: #cbd5e1; margin-bottom: 1.5rem;"></i>
                <h2>Your cart is empty</h2>
                <p style="color: var(--text-light); margin-bottom: 2rem;">Looks like you haven't added anything to your cart yet.</p>
                <a href="index.php" class="checkout-btn" style="width: auto; display: inline-block; padding: 0.75rem 2rem;">Start Shopping</a>
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

</body>
</html>
