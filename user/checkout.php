<?php
session_start();
include '../db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    // Fetch Cart
    $cart_sql = "SELECT c.product_id, c.quantity, p.product_name, p.price, p.image_url, p.gst_rate, p.shipping_cost 
                 FROM cart c
                 JOIN products p ON c.product_id = p.id
                 WHERE c.client_id = :client_id";
    $stmt = $conn->prepare($cart_sql);
    $stmt->execute([':client_id' => $user_id]);
    $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $products = [];
    $total_amount = 0;
    $total_gst = 0;
    $total_shipping = 0;
    $total_quantity = 0;

    if (count($cart_items) > 0) {
        foreach ($cart_items as $row) {
            $subtotal = $row['price'] * $row['quantity'];
            $gst_rate = isset($row['gst_rate']) ? $row['gst_rate'] : 18.00; // Default to 18% if null
            $item_gst = $subtotal * ($gst_rate / 100);

            // Per unit shipping cost
            $unit_shipping = isset($row['shipping_cost']) ? $row['shipping_cost'] : 50.00;
            $item_shipping = $unit_shipping * $row['quantity'];

            $products[] = [
                'product_id' => $row['product_id'],
                'product_name' => $row['product_name'],
                'quantity' => $row['quantity'],
                'price' => $row['price'],
                'image_url' => $row['image_url'],
                'subtotal' => $subtotal,
                'gst_rate' => $gst_rate,
                'gst_amount' => $item_gst,
                'shipping_cost' => $item_shipping
            ];
            $total_amount += $subtotal;
            $total_gst += $item_gst;
            $total_shipping += $item_shipping;
            $total_quantity += $row['quantity'];
        }
    }

    // Calculations
    $grand_total = $total_amount + $total_gst + $total_shipping;

    // Handle Order Placement
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $first_name = $_POST['first_name'];
        $last_name = $_POST['last_name'];
        $phone = $_POST['phone'];
        $email = $_POST['email'];
        $address = $_POST['address'];
        $city = $_POST['city'];
        $district = $_POST['district'];
        $postcode = $_POST['postcode'];
        $country = $_POST['country'];

        foreach ($products as $product) {
            $item_shipping_share = $product['shipping_cost'];
            $item_final_total = $product['subtotal'] + $product['gst_amount'] + $item_shipping_share;

            // Insert Order
            $stmt = $conn->prepare("INSERT INTO orders (client_id, product_id, quantity, price, subtotal, total, first_name, last_name, phone, email, address, city, district, postcode, country) VALUES (:client_id, :product_id, :quantity, :price, :subtotal, :total, :first_name, :last_name, :phone, :email, :address, :city, :district, :postcode, :country)");

            $stmt->execute([
                ':client_id' => $user_id,
                ':product_id' => $product['product_id'],
                ':quantity' => $product['quantity'],
                ':price' => $product['price'],
                ':subtotal' => $product['subtotal'],
                ':total' => $item_final_total,
                ':first_name' => $first_name,
                ':last_name' => $last_name,
                ':phone' => $phone,
                ':email' => $email,
                ':address' => $address,
                ':city' => $city,
                ':district' => $district,
                ':postcode' => $postcode,
                ':country' => $country
            ]);

            // Update Stock
            $update_stmt = $conn->prepare("UPDATE products SET stock = stock - :quantity WHERE id = :id");
            $update_stmt->execute([':quantity' => $product['quantity'], ':id' => $product['product_id']]);
        }

        // Clear Cart
        $delete_stmt = $conn->prepare("DELETE FROM cart WHERE client_id = :client_id");
        $delete_stmt->execute([':client_id' => $user_id]);

        header("Location: orderplaced.php");
        exit();
    }

    // User Info for Nav
    $first_name_user = "";
    $last_name_user = "";
    $user_stmt = $conn->prepare("SELECT first_name, last_name FROM clients WHERE id = :id");
    $user_stmt->execute([':id' => $user_id]);
    $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        $first_name_user = $user['first_name'];
        $last_name_user = $user['last_name'];
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
    <title>Checkout - Constructo</title>
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

        .container {
            max-width: 1200px;
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
            flex: 1;
        }

        .page-title {
            font-size: 2rem;
            color: var(--primary);
            font-weight: 700;
            margin-bottom: 2rem;
        }

        .checkout-layout {
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            gap: 3rem;
        }

        /* Form Section */
        .form-card {
            background: white;
            border-radius: var(--radius-lg);
            padding: 2rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .section-title i {
            color: var(--accent);
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        label {
            font-size: 0.9rem;
            font-weight: 500;
            color: var(--text-main);
        }

        input {
            padding: 0.75rem;
            border: 1px solid #cbd5e1;
            border-radius: var(--radius-md);
            font-family: inherit;
            font-size: 0.95rem;
            transition: var(--transition);
        }

        input:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.1);
        }

        input[readonly] {
            background-color: #f1f5f9;
            cursor: not-allowed;
        }

        /* Order Summary */
        .summary-card {
            background: white;
            border-radius: var(--radius-lg);
            padding: 2rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid rgba(0, 0, 0, 0.05);
            height: fit-content;
            position: sticky;
            top: 120px;
        }

        .order-items {
            margin-bottom: 2rem;
            max-height: 300px;
            overflow-y: auto;
            padding-right: 0.5rem;
        }

        .order-item {
            display: flex;
            gap: 1rem;
            padding: 1rem 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .item-img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: var(--radius-md);
            background: #f1f5f9;
        }

        .item-info {
            flex: 1;
        }

        .item-name {
            font-weight: 600;
            font-size: 0.95rem;
            color: var(--primary);
            margin-bottom: 0.25rem;
        }

        .item-meta {
            font-size: 0.85rem;
            color: var(--text-light);
        }

        .item-price {
            font-weight: 600;
            color: var(--text-main);
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.75rem;
            color: var(--text-main);
            font-size: 0.95rem;
        }

        .summary-row.total {
            border-top: 2px solid #f1f5f9;
            padding-top: 1rem;
            margin-top: 1rem;
            font-weight: 700;
            font-size: 1.25rem;
            color: var(--primary);
        }

        .place-order-btn {
            display: block;
            width: 100%;
            padding: 1rem;
            background: var(--accent);
            color: white;
            border: none;
            border-radius: var(--radius-md);
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            margin-top: 1.5rem;
            transition: var(--transition);
        }

        .place-order-btn:hover {
            background: var(--accent-hover);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
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
            .checkout-layout {
                grid-template-columns: 1fr;
            }

            .summary-card {
                position: static;
                order: -1;
                margin-bottom: 2rem;
            }
        }

        @media (max-width: 600px) {
            .form-grid {
                grid-template-columns: 1fr;
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
            <?php if (!empty($first_name_user)): ?>
                <div class="profile-menu">
                    <div class="profile-circle" onclick="toggleDropdown()">
                        <?php echo strtoupper(substr($first_name_user, 0, 1)) . strtoupper(substr($last_name_user, 0, 1)); ?>
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
        <h1 class="page-title">Checkout</h1>

        <form method="POST" action="">
            <div class="checkout-layout">
                <!-- Shipping Form -->
                <div class="form-card">
                    <div class="section-title"><i class="fas fa-map-marker-alt"></i> Shipping Information</div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>First Name</label>
                            <input type="text" name="first_name" required
                                value="<?php echo htmlspecialchars($first_name_user); ?>">
                        </div>
                        <div class="form-group">
                            <label>Last Name</label>
                            <input type="text" name="last_name" required
                                value="<?php echo htmlspecialchars($last_name_user); ?>">
                        </div>
                        <div class="form-group">
                            <label>Phone Number</label>
                            <input type="tel" name="phone" required>
                        </div>
                        <div class="form-group">
                            <label>Email Address</label>
                            <input type="email" name="email" required>
                        </div>
                        <div class="form-group full-width">
                            <label>Address</label>
                            <input type="text" name="address" placeholder="Street address, P.O. box, etc." required>
                        </div>
                        <div class="form-group">
                            <label>Town / City</label>
                            <input type="text" name="city" required>
                        </div>
                        <div class="form-group">
                            <label>District</label>
                            <input type="text" name="district" required>
                        </div>
                        <div class="form-group">
                            <label>Postcode / ZIP</label>
                            <input type="text" name="postcode" required>
                        </div>
                        <div class="form-group">
                            <label>Country</label>
                            <input type="text" name="country" value="India" readonly>
                        </div>
                    </div>
                </div>

                <!-- Order Summary -->
                <div class="summary-card">
                    <div class="section-title"><i class="fas fa-shopping-bag"></i> Order Summary</div>

                    <div class="order-items">
                        <?php foreach ($products as $product):
                            $imgUrl = $product['image_url'];
                            if (!filter_var($imgUrl, FILTER_VALIDATE_URL)) {
                                $imgUrl = str_replace(['uploaded_images/', 'admin/'], '', $imgUrl);
                                $imgUrl = STORAGE_URL . $imgUrl;
                            }
                            ?>
                            <div class="order-item">
                                <img src="<?php echo htmlspecialchars($imgUrl); ?>" alt="Product" class="item-img">
                                <div class="item-info">
                                    <div class="item-name"><?php echo htmlspecialchars($product['product_name']); ?></div>
                                    <div class="item-meta">Qty: <?php echo $product['quantity']; ?></div>
                                </div>
                                <div class="item-price">₹<?php echo number_format($product['subtotal'], 2); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span>₹<?php echo number_format($total_amount, 2); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>GST (Calculated)</span>
                        <span>₹<?php echo number_format($total_gst, 2); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Shipping</span>
                        <span>₹<?php echo number_format($total_shipping, 2); ?></span>
                    </div>
                    <div class="summary-row total">
                        <span>Total</span>
                        <span>₹<?php echo number_format($grand_total, 2); ?></span>
                    </div>

                    <div
                        style="margin-top: 1rem; padding: 0.75rem; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: var(--radius-md); font-size: 0.9rem; color: #166534; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-money-bill-wave"></i>
                        <span>Payment Method: <strong>Cash on Delivery (COD) Only</strong></span>
                    </div>
                    <button type="submit" class="place-order-btn">Place Order</button>
                    <p style="text-align: center; margin-top: 1rem; font-size: 0.85rem; color: var(--text-light);">
                        <i class="fas fa-lock"></i> Secure Checkout
                    </p>
                </div>
            </div>
        </form>
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