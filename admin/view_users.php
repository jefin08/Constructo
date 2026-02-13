<?php
session_start();
// Check if admin is logged in
if (!isset($_SESSION['email'])) {
    header("Location: ../login.php");
    exit();
}

// Database connection
include '../db_connect.php';

// Handle activation, deactivation, and deletion requests
$success_message = "";
$error_message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        $userId = $_POST['user_id'];

        try {
            $sql = "";
            $msg = "";
            switch ($_POST['action']) {
                case 'activate':
                    $sql = "UPDATE clients SET status = 'active' WHERE id = :id";
                    $msg = "User activated successfully.";
                    break;
                case 'deactivate':
                    $sql = "UPDATE clients SET status = 'inactive' WHERE id = :id";
                    $msg = "User deactivated successfully.";
                    break;
                case 'delete':
                    $sql = "DELETE FROM clients WHERE id = :id";
                    $msg = "User deleted successfully.";
                    break;
                default:
                    break;
            }

            if (!empty($sql)) {
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(':id', $userId);
                if ($stmt->execute()) {
                    $success_message = $msg;
                } else {
                    $error_message = "Action failed.";
                }
            }
        } catch (PDOException $e) {
            $error_message = "Error: " . $e->getMessage();
        }
    }
}

// Query to get users
try {
    $sql = "SELECT id, first_name, last_name, email, created_at, status FROM clients ORDER BY id DESC";
    $stmt = $conn->query($sql);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Users - Admin Dashboard</title>
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

        .badge-active {
            background: #ecfdf5;
            color: #059669;
            border: 1px solid #a7f3d0;
        }

        .badge-inactive {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        /* Action Buttons */
        .action-btn {
            padding: 0.5rem;
            border-radius: var(--radius-md);
            border: 1px solid transparent;
            cursor: pointer;
            transition: var(--transition);
            background: transparent;
            color: var(--text-light);
        }

        .action-btn:hover {
            background: #f1f5f9;
            color: var(--primary);
        }

        .btn-activate {
            color: #059669;
            background: #ecfdf5;
            border-color: #a7f3d0;
            padding: 0.4rem 0.8rem;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .btn-activate:hover {
            background: #d1fae5;
        }

        .btn-deactivate {
            color: #d97706;
            background: #fffbeb;
            border-color: #fde68a;
            padding: 0.4rem 0.8rem;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .btn-deactivate:hover {
            background: #fef3c7;
        }

        .btn-delete {
            color: #dc2626;
            background: #fef2f2;
            border-color: #fecaca;
            padding: 0.4rem 0.8rem;
            font-size: 0.85rem;
            font-weight: 500;
            margin-left: 0.5rem;
        }

        .btn-delete:hover {
            background: #fee2e2;
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
            <a href="view_orders.php" class="nav-item">
                <i class="fas fa-shopping-cart"></i> Orders
            </a>
            <a href="view_users.php" class="nav-item active">
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
                <h1>Manage Users</h1>
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
                    <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <div class="search-container">
                <input type="text" id="searchInput" onkeyup="filterTable()" placeholder="Search by name or email..."
                    class="search-input">
            </div>

            <div class="table-card">
                <div class="table-header">
                    <h3>Registered Users</h3>
                    <span style="color: var(--text-light); font-size: 0.9rem;">Total:
                        <?php echo count($users); ?></span>
                </div>
                <table id="usersTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Date Joined</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($users) > 0): ?>
                            <?php foreach ($users as $row): ?>
                                <tr>
                                    <td>#<?php echo $row["id"]; ?></td>
                                    <td style="font-weight: 500;">
                                        <?php echo htmlspecialchars($row["first_name"] . ' ' . $row["last_name"]); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($row["email"]); ?></td>
                                    <td style="color: var(--text-light);">
                                        <?php echo date('M d, Y', strtotime($row["created_at"])); ?>
                                    </td>
                                    <td>
                                        <span
                                            class="badge <?php echo ($row["status"] == 'active') ? 'badge-active' : 'badge-inactive'; ?>">
                                            <?php echo ucfirst($row["status"]); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($row["status"] == 'active'): ?>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="user_id" value="<?php echo $row["id"]; ?>">
                                                <input type="hidden" name="action" value="deactivate">
                                                <button type="submit" class="action-btn btn-deactivate" title="Deactivate User">
                                                    <i class="fas fa-ban"></i> Deactivate
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="user_id" value="<?php echo $row["id"]; ?>">
                                                <input type="hidden" name="action" value="activate">
                                                <button type="submit" class="action-btn btn-activate" title="Activate User">
                                                    <i class="fas fa-check"></i> Activate
                                                </button>
                                            </form>
                                        <?php endif; ?>

                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="user_id" value="<?php echo $row["id"]; ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <button type="submit" class="action-btn btn-delete"
                                                onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.');"
                                                title="Delete User">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 3rem; color: var(--text-light);">
                                    <i class="fas fa-users-slash"
                                        style="font-size: 2rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                                    <p>No users found.</p>
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
            const table = document.getElementById("usersTable");
            const tr = table.getElementsByTagName("tr");

            for (let i = 1; i < tr.length; i++) {
                const tdName = tr[i].getElementsByTagName("td")[1];
                const tdEmail = tr[i].getElementsByTagName("td")[2];

                if (tdName || tdEmail) {
                    const txtValueName = tdName.textContent || tdName.innerText;
                    const txtValueEmail = tdEmail.textContent || tdEmail.innerText;

                    if (txtValueName.toUpperCase().indexOf(filter) > -1 || txtValueEmail.toUpperCase().indexOf(filter) > -1) {
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