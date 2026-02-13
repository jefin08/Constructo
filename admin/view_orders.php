<?php
session_start();
// Check if admin is logged in
if (!isset($_SESSION['email'])) {
    header("Location: ../login.php");
    exit();
}

// Database connection
include '../db_connect.php';

// Handle status updates
$success_message = "";
$error_message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action']) && isset($_POST['order_id'])) {
        $orderId = $_POST['order_id'];
        $newStatus = $_POST['status'];

        try {
            $sql = "UPDATE orders SET status = :status WHERE id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':status', $newStatus);
            $stmt->bindParam(':id', $orderId);

            if ($stmt->execute()) {
                $success_message = "Order #$orderId status updated to " . htmlspecialchars($newStatus) . ".";
            } else {
                $error_message = "Failed to update status.";
            }
        } catch (PDOException $e) {
            $error_message = "Error: " . $e->getMessage();
        }
    }
}

// Fetch Orders
try {
    $sql = "SELECT o.id, o.client_id, o.product_id, o.quantity, o.total, o.created_at, o.status, 
                   o.first_name, o.last_name, o.email, o.address, o.city, o.phone,
                   p.product_name, p.image_url
            FROM orders o
            LEFT JOIN products p ON o.product_id = p.id
            ORDER BY o.created_at DESC";
    $stmt = $conn->query($sql);
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
    <title>View Orders - Admin Dashboard</title>
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
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 260px;
            background: var(--primary);
            color: white;
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100vh;
            z-index: 100;
        }

        .sidebar-header {
            padding: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-header img {
            height: 60px;
        }

        .sidebar-header h2 {
            font-size: 1.25rem;
            font-weight: 600;
            color: white;
        }

        .nav-links {
            flex: 1;
            padding: 1.5rem 1rem;
            overflow-y: auto;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            color: #94a3b8;
            text-decoration: none;
            border-radius: var(--radius-md);
            margin-bottom: 0.5rem;
            transition: var(--transition);
        }

        .nav-item:hover,
        .nav-item.active {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .nav-item i {
            width: 20px;
            text-align: center;
        }

        /* Main Content */
        .main-content {
            margin-left: 260px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        /* Top Header */
        .top-header {
            background: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--shadow-sm);
            position: sticky;
            top: 0;
            z-index: 90;
        }

        .page-title h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 1rem;
            cursor: pointer;
            position: relative;
        }

        .avatar {
            width: 40px;
            height: 40px;
            background: var(--accent);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }

        .dropdown-menu {
            position: absolute;
            top: 120%;
            right: 0;
            background: white;
            width: 180px;
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-md);
            border: 1px solid #e2e8f0;
            display: none;
            overflow: hidden;
        }

        .dropdown-menu.show {
            display: block;
        }

        .dropdown-item {
            display: block;
            padding: 0.75rem 1rem;
            color: var(--text-main);
            text-decoration: none;
            transition: var(--transition);
        }

        .dropdown-item:hover {
            background: #f1f5f9;
            color: var(--primary);
        }

        /* Content Area */
        .content-area {
            padding: 2rem;
            max-width: 1400px;
            margin: 0 auto;
            width: 100%;
        }

        /* Search Bar */
        .search-container {
            margin-bottom: 1.5rem;
            display: flex;
            gap: 1rem;
        }

        .search-input {
            flex: 1;
            max-width: 400px;
            padding: 0.75rem 1rem;
            border: 1px solid #cbd5e1;
            border-radius: var(--radius-md);
            font-family: 'Outfit', sans-serif;
            font-size: 0.95rem;
            transition: var(--transition);
        }

        .search-input:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.1);
        }

        /* Table Card */
        .table-card {
            background: white;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }

        .table-header {
            padding: 1.5rem;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .table-header h3 {
            font-size: 1.1rem;
            color: var(--primary);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #f8fafc;
            padding: 1rem 1.5rem;
            text-align: left;
            font-weight: 600;
            color: var(--text-light);
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        td {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #f1f5f9;
            color: var(--text-main);
            vertical-align: middle;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover {
            background: #f8fafc;
        }

        /* Status Badges */
        .badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .badge-pending {
            background: #fff7ed;
            color: #c2410c;
            border: 1px solid #ffedd5;
        }

        .badge-shipped {
            background: #e0f2fe;
            color: #0369a1;
            border: 1px solid #bae6fd;
        }

        .badge-delivered {
            background: #ecfdf5;
            color: #15803d;
            border: 1px solid #bbf7d0;
        }

        .badge-cancelled {
            background: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }

        /* Actions */
        .status-form select {
            padding: 0.4rem;
            border: 1px solid #cbd5e1;
            border-radius: var(--radius-md);
            font-size: 0.85rem;
            margin-right: 0.5rem;
        }

        .status-form button {
            padding: 0.4rem 0.8rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: var(--radius-md);
            cursor: pointer;
            font-size: 0.85rem;
            transition: var(--transition);
        }

        .status-form button:hover {
            background: var(--primary-light);
        }

        /* Alerts */
        .alert {
            padding: 1rem;
            border-radius: var(--radius-md);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .alert-success {
            background: #ecfdf5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .alert-error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        /* Customer details tooltip or small text */
        .customer-details {
            font-size: 0.85rem;
            color: var(--text-light);
            margin-top: 0.25rem;
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s;
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .table-card {
                overflow-x: auto;
            }
        }
    </style>
</head>

<body>

    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <img src="../images/logo.png" alt="Logo">
            <h2>Constructo</h2>
        </div>
        <nav class="nav-links">
            <a href="dashboard.php" class="nav-item">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a href="view_products.php" class="nav-item">
                <i class="fas fa-box"></i> Products
            </a>
            <a href="view_orders.php" class="nav-item active">
                <i class="fas fa-shopping-cart"></i> Orders
            </a>
            <a href="view_users.php" class="nav-item">
                <i class="fas fa-users"></i> Users
            </a>
            <a href="messages.php" class="nav-item">
                <i class="fas fa-envelope"></i> Messages
            </a>
            <a href="add_category.php" class="nav-item">
                <i class="fas fa-tags"></i> Categories
            </a>
            <a href="add_vendors.php" class="nav-item">
                <i class="fas fa-store"></i> Vendors
            </a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Top Header -->
        <header class="top-header">
            <div class="page-title">
                <h1>Manage Orders</h1>
            </div>
            <div class="user-profile" onclick="document.getElementById('profileDropdown').classList.toggle('show')">
                <div class="avatar">A</div>
                <span>Admin</span>
                <i class="fas fa-chevron-down" style="font-size: 0.8rem; color: var(--text-light);"></i>
                <div class="dropdown-menu" id="profileDropdown">
                    <a href="../logout.php" class="dropdown-item" style="color: #ef4444;"><i
                            class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
        </header>

        <!-- Content Area -->
        <div class="content-area">
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <div class="search-container">
                <input type="text" id="searchInput" onkeyup="filterTable()"
                    placeholder="Search by customer name, order ID or product..." class="search-input">
            </div>

            <div class="table-card">
                <div class="table-header">
                    <h3>All Orders</h3>
                    <span style="color: var(--text-light); font-size: 0.9rem;">Total:
                        <?php echo count($orders); ?>
                    </span>
                </div>
                <table id="ordersTable">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer Info</th>
                            <th>Product Details</th>
                            <th>Total</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Update Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($orders) > 0): ?>
                            <?php foreach ($orders as $row):
                                $status = strtolower($row['status'] ?? 'pending');
                                $badgeClass = 'badge-pending';
                                if ($status == 'shipped')
                                    $badgeClass = 'badge-shipped';
                                if ($status == 'delivered')
                                    $badgeClass = 'badge-delivered';
                                if ($status == 'cancelled')
                                    $badgeClass = 'badge-cancelled';
                                ?>
                                <tr>
                                    <td>#
                                        <?php echo $row["id"]; ?>
                                    </td>
                                    <td>
                                        <div style="font-weight: 500;">
                                            <?php echo htmlspecialchars($row["first_name"] . ' ' . $row["last_name"]); ?>
                                        </div>
                                        <div class="customer-details">
                                            <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($row["email"]); ?><br>
                                            <i class="fas fa-phone"></i> <?php echo htmlspecialchars($row["phone"]); ?><br>
                                            <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($row["address"] . ', ' . $row["city"]); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="font-weight: 500;">
                                            <?php echo htmlspecialchars($row["product_name"] ?? 'Product #' . $row['product_id']); ?>
                                        </div>
                                        <div class="customer-details">
                                            Qty:
                                            <?php echo $row["quantity"]; ?>
                                        </div>
                                    </td>
                                    <td style="font-weight: 600;">₹
                                        <?php echo number_format($row["total"], 2); ?>
                                    </td>
                                    <td style="color: var(--text-light);">
                                        <?php echo date('M d, Y', strtotime($row["created_at"])); ?>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $badgeClass; ?>">
                                            <?php echo ucfirst($row["status"] ?? 'Pending'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <form method="POST" class="status-form">
                                            <input type="hidden" name="order_id" value="<?php echo $row["id"]; ?>">
                                            <input type="hidden" name="action" value="update_status">
                                            <select name="status">
                                                <option value="Pending" <?php if (strtolower($row["status"] ?? '') == 'pending')
                                                    echo 'selected'; ?>>Pending</option>
                                                <option value="Shipped" <?php if (strtolower($row["status"] ?? '') == 'shipped')
                                                    echo 'selected'; ?>>Shipped</option>
                                                <option value="Delivered" <?php if (strtolower($row["status"] ?? '') == 'delivered')
                                                    echo 'selected'; ?>>Delivered</option>
                                                <option value="Cancelled" <?php if (strtolower($row["status"] ?? '') == 'cancelled')
                                                    echo 'selected'; ?>>Cancelled</option>
                                            </select>
                                            <button type="submit">Update</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 3rem; color: var(--text-light);">
                                    <i class="fas fa-box-open"
                                        style="font-size: 2rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                                    <p>No orders found.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script>
        function filterTable() {
            const input = document.getElementById("searchInput");
            const filter = input.value.toUpperCase();
            const table = document.getElementById("ordersTable");
            const tr = table.getElementsByTagName("tr");

            for (let i = 1; i < tr.length; i++) {
                const tdId = tr[i].getElementsByTagName("td")[0];
                const tdCustomer = tr[i].getElementsByTagName("td")[1];
                const tdProduct = tr[i].getElementsByTagName("td")[2];

                if (tdId || tdCustomer || tdProduct) {
                    const txtValueId = tdId ? (tdId.textContent || tdId.innerText) : "";
                    const txtValueCustomer = tdCustomer ? (tdCustomer.textContent || tdCustomer.innerText) : "";
                    const txtValueProduct = tdProduct ? (tdProduct.textContent || tdProduct.innerText) : "";

                    if (txtValueId.toUpperCase().indexOf(filter) > -1 ||
                        txtValueCustomer.toUpperCase().indexOf(filter) > -1 ||
                        txtValueProduct.toUpperCase().indexOf(filter) > -1) {
                        tr[i].style.display = "";
                    } else {
                        tr[i].style.display = "none";
                    }
                }
            }
        }

        // Close dropdown when clicking outside
        window.onclick = function (event) {
            if (!event.target.closest('.user-profile')) {
                var dropdowns = document.getElementsByClassName("dropdown-menu");
                for (var i = 0; i < dropdowns.length; i++) {
                    var openDropdown = dropdowns[i];
                    if (openDropdown.classList.contains('show')) {
                        openDropdown.classList.remove('show');
                    }
                }
            }
        }
    </script>
</body>

</html>