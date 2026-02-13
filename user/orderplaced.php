<?php
session_start();
include '../db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    // Fetch recent order details (last 5 minutes to be safe)
    $order_sql = "
        SELECT o.id, o.first_name, o.last_name, o.email, o.phone, p.product_name, o.total, o.created_at
        FROM orders o
        JOIN products p ON o.product_id = p.id
        WHERE o.client_id = :client_id AND o.created_at >= NOW() - INTERVAL '5 minute'
        ORDER BY o.id DESC
    ";
    $stmt = $conn->prepare($order_sql);
    $stmt->execute([':client_id' => $user_id]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Placed - Constructo</title>
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
            --success: #22c55e;
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
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .success-card {
            background: white;
            border-radius: var(--radius-lg);
            padding: 3rem;
            box-shadow: var(--shadow-md);
            text-align: center;
            max-width: 600px;
            width: 100%;
            animation: slideUp 0.5s ease-out;
        }

        /* Success Animation */
        .checkmark-circle {
            width: 80px;
            height: 80px;
            position: relative;
            display: inline-block;
            vertical-align: top;
            margin-bottom: 1.5rem;
        }

        .checkmark-circle .background {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: #dcfce7;
            position: absolute;
            left: 0;
            top: 0;
        }

        .checkmark-circle .checkmark {
            border-radius: 5px;
            display: block;
            position: absolute;
            opacity: 0;
            width: 80px;
            height: 80px;
            stroke-width: 2;
            stroke: var(--success);
            stroke-miterlimit: 10;
            box-shadow: inset 0px 0px 0px var(--success);
            animation: fill .4s ease-in-out .4s forwards, scale .3s ease-in-out .9s both;
        }

        .checkmark-circle .checkmark__circle {
            stroke-dasharray: 166;
            stroke-dashoffset: 166;
            stroke-width: 2;
            stroke-miterlimit: 10;
            stroke: var(--success);
            fill: none;
            animation: stroke 0.6s cubic-bezier(0.65, 0, 0.45, 1) forwards;
        }

        .checkmark-circle .checkmark__check {
            transform-origin: 50% 50%;
            stroke-dasharray: 48;
            stroke-dashoffset: 48;
            animation: stroke 0.3s cubic-bezier(0.65, 0, 0.45, 1) 0.8s forwards;
        }

        @keyframes stroke {
            100% {
                stroke-dashoffset: 0;
            }
        }

        @keyframes scale {

            0%,
            100% {
                transform: none;
            }

            50% {
                transform: scale3d(1.1, 1.1, 1);
            }
        }

        @keyframes fill {
            100% {
                box-shadow: inset 0px 0px 0px 30px #dcfce7;
            }
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .success-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .success-message {
            color: var(--text-light);
            font-size: 1.1rem;
            margin-bottom: 2rem;
        }

        .order-summary {
            background: #f8fafc;
            border-radius: var(--radius-md);
            padding: 1.5rem;
            margin-bottom: 2rem;
            text-align: left;
        }

        .order-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }

        .order-row span:first-child {
            color: var(--text-light);
        }

        .order-row span:last-child {
            font-weight: 600;
            color: var(--primary);
        }

        .actions {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .btn-primary {
            display: inline-block;
            width: 100%;
            padding: 1rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: var(--radius-md);
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-primary:hover {
            background: var(--primary-light);
            transform: translateY(-2px);
        }

        .btn-secondary {
            display: inline-block;
            width: 100%;
            padding: 1rem;
            background: transparent;
            color: var(--text-main);
            border: 1px solid #cbd5e1;
            border-radius: var(--radius-md);
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: var(--transition);
        }

        .btn-secondary:hover {
            background: #f1f5f9;
            border-color: var(--text-main);
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
            <div class="profile-menu">
                <div class="profile-circle" onclick="toggleDropdown()">
                    <i class="fas fa-user"></i>
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
        <div class="success-card">
            <div class="checkmark-circle">
                <div class="background"></div>
                <svg class="checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
                    <circle class="checkmark__circle" cx="26" cy="26" r="25" fill="none" />
                    <path class="checkmark__check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8" />
                </svg>
            </div>

            <h1 class="success-title">Order Placed Successfully!</h1>
            <p class="success-message">Thank you for your purchase. Your order has been confirmed.</p>

            <?php if (!empty($orders)): ?>
                <div class="order-summary">
                    <div class="order-row">
                        <span>Order ID</span>
                        <span>#<?php echo htmlspecialchars($orders[0]['id']); ?></span>
                    </div>
                    <div class="order-row">
                        <span>Date</span>
                        <span><?php echo date('M d, Y', strtotime($orders[0]['created_at'])); ?></span>
                    </div>
                    <div class="order-row">
                        <span>Total Items</span>
                        <span><?php echo count($orders); ?></span>
                    </div>
                    <div class="order-row" style="margin-top: 1rem; padding-top: 0.5rem; border-top: 1px solid #e2e8f0;">
                        <span>Payment Method</span>
                        <span>Cash on Delivery</span>
                    </div>
                </div>

                <div class="actions">
                    <form action="generate_pdf.php" method="POST" style="width: 100%;">
                        <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user_id); ?>">
                        <?php foreach ($orders as $order): ?>
                            <input type="hidden" name="order_ids[]" value="<?php echo htmlspecialchars($order['id']); ?>">
                        <?php endforeach; ?>
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-file-invoice"></i> Download Invoice
                        </button>
                    </form>

                    <a href="orders.php" class="btn-secondary">View My Orders</a>
                    <a href="index.php" class="btn-secondary" style="border: none; color: var(--accent);">Continue
                        Shopping</a>
                </div>
            <?php else: ?>
                <p>No recent orders found.</p>
                <a href="index.php" class="btn-primary">Start Shopping</a>
            <?php endif; ?>
        </div>
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