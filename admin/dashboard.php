<?php
session_start();

// Check if the admin is logged in
if (!isset($_SESSION['email'])) {
    header("Location: ../login.php");
    exit();
}

include '../db_connect.php';

// Initialize variables
$firstName = '';
$email = $_SESSION['email'];

try {
    // Fetch Admin Name
    $stmt = $conn->prepare("SELECT name FROM admin WHERE email = :email");
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    $firstName = $stmt->fetchColumn();

    // Fetch Stats
    // 1. Total Products
    $stmt = $conn->query("SELECT COUNT(*) FROM products");
    $totalProducts = $stmt->fetchColumn();

    // 2. Total Users
    $stmt = $conn->query("SELECT COUNT(*) FROM clients");
    $totalUsers = $stmt->fetchColumn();

    // 3. Total Orders
    $stmt = $conn->query("SELECT COUNT(*) FROM orders");
    $totalOrders = $stmt->fetchColumn();

    // 4. Total Revenue (Assuming 'subtotal' or 'total' column exists and is populated)
    // Using subtotal for now as per previous context
    $stmt = $conn->query("SELECT SUM(subtotal) FROM orders WHERE status != 'Cancelled'");
    $totalRevenue = $stmt->fetchColumn() ?: 0;

    // Fetch Recent Orders (Limit 5)
    $recentOrdersQuery = "
        SELECT o.id, o.first_name, o.last_name, o.subtotal, o.status, o.created_at, p.product_name 
        FROM orders o
        LEFT JOIN products p ON o.product_id = p.id
        ORDER BY o.created_at DESC 
        LIMIT 5
    ";
    $stmt = $conn->query($recentOrdersQuery);
    $recentOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

$initial = !empty($firstName) ? strtoupper($firstName[0]) : 'A';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Constructo</title>
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
            --bg-body: #f1f5f9;
            --bg-card: #ffffff;
            --sidebar-width: 260px;
            --header-height: 70px;
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
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: var(--sidebar-width);
            background: var(--primary);
            color: white;
            position: fixed;
            top: 0; bottom: 0; left: 0;
            display: flex; flex-direction: column;
            z-index: 100;
            transition: var(--transition);
        }
        .sidebar-header {
            height: var(--header-height);
            display: flex; align-items: center; padding: 0 1.5rem;
            font-size: 1.25rem; font-weight: 700; color: white;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .sidebar-header img { height: 60px; width: auto; margin-right: 0.75rem; }
        .sidebar-header i { margin-right: 0.75rem; color: var(--accent); }
        
        .nav-menu { padding: 1.5rem 1rem; flex: 1; overflow-y: auto; }
        .nav-item {
            display: flex; align-items: center;
            padding: 0.75rem 1rem;
            color: #94a3b8;
            text-decoration: none;
            border-radius: var(--radius-md);
            margin-bottom: 0.5rem;
            transition: var(--transition);
            font-weight: 500;
        }
        .nav-item:hover, .nav-item.active {
            background: rgba(255,255,255,0.1);
            color: white;
        }
        .nav-item i { width: 24px; margin-right: 0.75rem; }
        
        .sidebar-footer {
            padding: 1.5rem;
            border-top: 1px solid rgba(255,255,255,0.1);
        }
        .logout-btn {
            display: flex; align-items: center; justify-content: center;
            width: 100%; padding: 0.75rem;
            background: rgba(255,255,255,0.1);
            color: white; border: none; border-radius: var(--radius-md);
            cursor: pointer; transition: var(--transition);
            text-decoration: none; font-weight: 600;
        }
        .logout-btn:hover { background: #ef4444; }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            display: flex; flex-direction: column;
        }

        /* Top Header */
        .top-header {
            height: var(--header-height);
            background: white;
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 2rem;
            box-shadow: var(--shadow-sm);
            position: sticky; top: 0; z-index: 90;
        }
        .header-title { font-size: 1.25rem; font-weight: 600; color: var(--primary); }
        
        .user-profile {
            display: flex; align-items: center; gap: 1rem;
            cursor: pointer; position: relative;
        }
        .profile-info { text-align: right; }
        .profile-name { font-weight: 600; color: var(--primary); font-size: 0.9rem; }
        .profile-role { font-size: 0.8rem; color: var(--text-light); }
        .profile-avatar {
            width: 40px; height: 40px;
            background: var(--accent); color: white;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700;
        }
        
        /* Profile Dropdown */
        .profile-dropdown {
            position: absolute; top: 120%; right: 0;
            background: white; width: 180px;
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-md);
            border: 1px solid #f1f5f9;
            display: none; flex-direction: column;
            overflow: hidden; z-index: 1000;
        }
        .profile-dropdown.show { display: flex; animation: fadeIn 0.2s ease; }
        .dropdown-item {
            padding: 0.75rem 1rem; color: var(--text-main);
            text-decoration: none; font-size: 0.9rem;
            display: flex; align-items: center; gap: 0.5rem;
            transition: background 0.2s;
        }
        .dropdown-item:hover { background: #f8fafc; color: var(--primary); }
        .dropdown-item.text-red { color: #ef4444; }
        .dropdown-item.text-red:hover { background: #fef2f2; }
        
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        /* Dashboard Content */
        .content-wrapper { padding: 2rem; }

        /* Stats Grid */
        .stats-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem; margin-bottom: 2rem;
        }
        .stat-card {
            background: white; padding: 1.5rem;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            display: flex; align-items: center; gap: 1.5rem;
            transition: var(--transition);
        }
        .stat-card:hover { transform: translateY(-5px); box-shadow: var(--shadow-md); }
        
        .stat-icon {
            width: 60px; height: 60px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem;
        }
        .icon-blue { background: #e0f2fe; color: #0284c7; }
        .icon-green { background: #dcfce7; color: #166534; }
        .icon-purple { background: #f3e8ff; color: #9333ea; }
        .icon-orange { background: #ffedd5; color: #ea580c; }
        
        .stat-info h3 { font-size: 1.75rem; font-weight: 700; color: var(--primary); margin-bottom: 0.25rem; }
        .stat-info p { color: var(--text-light); font-size: 0.9rem; font-weight: 500; }

        /* Recent Orders & Quick Actions */
        .grid-container {
            display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem;
        }
        @media (max-width: 1024px) { .grid-container { grid-template-columns: 1fr; } }

        .card {
            background: white; border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm); overflow: hidden;
        }
        .card-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid #f1f5f9;
            display: flex; justify-content: space-between; align-items: center;
        }
        .card-title { font-weight: 600; color: var(--primary); }
        .view-all { color: var(--accent); text-decoration: none; font-size: 0.9rem; font-weight: 500; }
        
        .table-responsive { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 1rem 1.5rem; text-align: left; border-bottom: 1px solid #f1f5f9; font-size: 0.9rem; }
        th { background: #f8fafc; color: var(--text-light); font-weight: 600; }
        td { color: var(--text-main); }
        
        .status-badge {
            padding: 0.25rem 0.6rem; border-radius: 20px;
            font-size: 0.75rem; font-weight: 600;
        }
        .status-processing { background: #e0f2fe; color: #0369a1; }
        .status-delivered { background: #dcfce7; color: #166534; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }

        /* Quick Actions */
        .actions-grid {
            padding: 1.5rem;
            display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem;
        }
        .action-btn {
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            padding: 1.5rem; background: #f8fafc; border-radius: var(--radius-md);
            text-decoration: none; color: var(--text-main);
            transition: var(--transition); border: 1px solid transparent;
        }
        .action-btn:hover {
            background: white; border-color: var(--accent);
            transform: translateY(-3px); box-shadow: var(--shadow-sm);
            color: var(--accent);
        }
        .action-btn i { font-size: 1.5rem; margin-bottom: 0.75rem; color: var(--primary); }
        .action-btn:hover i { color: var(--accent); }
        .action-btn span { font-weight: 500; font-size: 0.9rem; }

    </style>
</head>
<body>

    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <img src="../images/logo.png" alt="Logo">
            Constructo
        </div>
        <nav class="nav-menu">
            <a href="dashboard.php" class="nav-item active"><i class="fas fa-th-large"></i> Dashboard</a>
            <a href="view_products.php" class="nav-item"><i class="fas fa-box"></i> Products</a>
            <a href="csv_upload.php" class="nav-item"><i class="fas fa-file-csv"></i> Bulk Upload</a>
            <a href="view_orders.php" class="nav-item"><i class="fas fa-shopping-cart"></i> Orders</a>
            <a href="view_users.php" class="nav-item"><i class="fas fa-users"></i> Users</a>
            <a href="add_category.php" class="nav-item"><i class="fas fa-tags"></i> Categories</a>
            <a href="add_vendors.php" class="nav-item"><i class="fas fa-store"></i> Vendors</a>
            <a href="messages.php" class="nav-item"><i class="fas fa-envelope"></i> Messages</a>
        </nav>
        <div class="sidebar-footer">
            <a href="../logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Header -->
        <header class="top-header">
            <div class="header-title">Dashboard Overview</div>
            <div class="user-profile" onclick="toggleProfileDropdown()">
                <div class="profile-info">
                    <div class="profile-name"><?php echo htmlspecialchars($firstName); ?></div>
                    <div class="profile-role">Administrator</div>
                </div>
                <div class="profile-avatar">
                    <?php echo $initial; ?>
                </div>
                <div class="profile-dropdown" id="profileDropdown">
                    <a href="../logout.php" class="dropdown-item text-red"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
        </header>

        <!-- Content -->
        <div class="content-wrapper">
            
            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon icon-blue"><i class="fas fa-shopping-bag"></i></div>
                    <div class="stat-info">
                        <h3><?php echo number_format($totalOrders); ?></h3>
                        <p>Total Orders</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon icon-green"><i class="fas fa-rupee-sign"></i></div>
                    <div class="stat-info">
                        <h3>₹<?php echo number_format($totalRevenue); ?></h3>
                        <p>Total Revenue</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon icon-purple"><i class="fas fa-users"></i></div>
                    <div class="stat-info">
                        <h3><?php echo number_format($totalUsers); ?></h3>
                        <p>Total Users</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon icon-orange"><i class="fas fa-box-open"></i></div>
                    <div class="stat-info">
                        <h3><?php echo number_format($totalProducts); ?></h3>
                        <p>Total Products</p>
                    </div>
                </div>
            </div>

            <div class="grid-container">
                <!-- Recent Orders -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Recent Orders</div>
                        <a href="view_orders.php" class="view-all">View All</a>
                    </div>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Product</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($recentOrders) > 0): ?>
                                        <?php foreach ($recentOrders as $order):
                                            $statusClass = 'status-processing';
                                            if (strtolower($order['status']) == 'delivered')
                                                $statusClass = 'status-delivered';
                                            if (strtolower($order['status']) == 'cancelled')
                                                $statusClass = 'status-cancelled';
                                            ?>
                                            <tr>
                                                <td>#<?php echo $order['id']; ?></td>
                                                <td><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></td>
                                                <td><?php echo htmlspecialchars($order['product_name']); ?></td>
                                                <td>₹<?php echo number_format($order['subtotal'], 2); ?></td>
                                                <td><span class="status-badge <?php echo $statusClass; ?>"><?php echo htmlspecialchars($order['status']); ?></span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                <?php else: ?>
                                        <tr><td colspan="5" style="text-align:center;">No recent orders found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Quick Actions</div>
                    </div>
                    <div class="actions-grid">
                        <a href="add_product.php" class="action-btn">
                            <i class="fas fa-plus-circle"></i>
                            <span>Add Product</span>
                        </a>
                        <a href="add_category.php" class="action-btn">
                            <i class="fas fa-tags"></i>
                            <span>Add Category</span>
                        </a>
                        <a href="csv_upload.php" class="action-btn">
                            <i class="fas fa-file-csv"></i>
                            <span>Bulk Upload</span>
                        </a>
                        <a href="add_vendors.php" class="action-btn">
                            <i class="fas fa-store"></i>
                            <span>Add Vendor</span>
                        </a>
                        <a href="view_users.php" class="action-btn">
                            <i class="fas fa-user-plus"></i>
                            <span>Manage Users</span>
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script>
        function toggleProfileDropdown() {
            const dropdown = document.getElementById('profileDropdown');
            dropdown.classList.toggle('show');
        }

        // Close dropdown when clicking outside
        window.onclick = function(event) {
            if (!event.target.closest('.user-profile')) {
                const dropdown = document.getElementById('profileDropdown');
                if (dropdown && dropdown.classList.contains('show')) {
                    dropdown.classList.remove('show');
                }
            }
        }
    </script>
</body>
</html>
