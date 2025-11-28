<?php
session_start();
if (!isset($_SESSION['email'])) {
    header("Location: ../login.php");
    exit();
}

include '../db_connect.php';

// Fetch Admin Name
$email = $_SESSION['email'];
$stmt = $conn->prepare("SELECT name FROM admin WHERE email = :email");
$stmt->bindParam(':email', $email);
$stmt->execute();
$firstName = $stmt->fetchColumn();
$initial = !empty($firstName) ? strtoupper($firstName[0]) : 'A';

try {
    $sql = "SELECT products.*, COALESCE(categories.category_name, 'Uncategorized') AS category_name 
            FROM products 
            LEFT JOIN categories ON products.category_id = categories.id
            ORDER BY products.id DESC";
    $stmt = $conn->query($sql);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - Constructo Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Reuse CSS from dashboard.php */
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

        /* Sidebar & Header (Same as dashboard) */
        .sidebar {
            width: var(--sidebar-width);
            background: var(--primary);
            color: white;
            position: fixed; top: 0; bottom: 0; left: 0;
            display: flex; flex-direction: column;
            z-index: 100; transition: var(--transition);
        }
        .sidebar-header {
            height: var(--header-height);
            display: flex; align-items: center; padding: 0 1.5rem;
            font-size: 1.25rem; font-weight: 700; color: white;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .sidebar-header img { height: 32px; width: auto; margin-right: 0.75rem; }
        .sidebar-header i { margin-right: 0.75rem; color: var(--accent); }
        .nav-menu { padding: 1.5rem 1rem; flex: 1; overflow-y: auto; }
        .nav-item {
            display: flex; align-items: center; padding: 0.75rem 1rem;
            color: #94a3b8; text-decoration: none; border-radius: var(--radius-md);
            margin-bottom: 0.5rem; transition: var(--transition); font-weight: 500;
        }
        .nav-item:hover, .nav-item.active { background: rgba(255,255,255,0.1); color: white; }
        .nav-item i { width: 24px; margin-right: 0.75rem; }
        .sidebar-footer { padding: 1.5rem; border-top: 1px solid rgba(255,255,255,0.1); }
        .logout-btn {
            display: flex; align-items: center; justify-content: center;
            width: 100%; padding: 0.75rem; background: rgba(255,255,255,0.1);
            color: white; border: none; border-radius: var(--radius-md);
            cursor: pointer; transition: var(--transition); text-decoration: none; font-weight: 600;
        }
        .logout-btn:hover { background: #ef4444; }

        .main-content { flex: 1; margin-left: var(--sidebar-width); display: flex; flex-direction: column; }
        .top-header {
            height: var(--header-height); background: white;
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 2rem; box-shadow: var(--shadow-sm);
            position: sticky; top: 0; z-index: 90;
        }
        .header-title { font-size: 1.25rem; font-weight: 600; color: var(--primary); }
        .user-profile { display: flex; align-items: center; gap: 1rem; cursor: pointer; position: relative; }
        .profile-info { text-align: right; }
        .profile-name { font-weight: 600; color: var(--primary); font-size: 0.9rem; }
        .profile-role { font-size: 0.8rem; color: var(--text-light); }
        .profile-avatar {
            width: 40px; height: 40px; background: var(--accent); color: white;
            border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700;
        }
        .profile-dropdown {
            position: absolute; top: 120%; right: 0; background: white; width: 180px;
            border-radius: var(--radius-md); box-shadow: var(--shadow-md); border: 1px solid #f1f5f9;
            display: none; flex-direction: column; overflow: hidden; z-index: 1000;
        }
        .profile-dropdown.show { display: flex; animation: fadeIn 0.2s ease; }
        .dropdown-item {
            padding: 0.75rem 1rem; color: var(--text-main); text-decoration: none; font-size: 0.9rem;
            display: flex; align-items: center; gap: 0.5rem; transition: background 0.2s;
        }
        .dropdown-item:hover { background: #f8fafc; color: var(--primary); }
        .dropdown-item.text-red { color: #ef4444; }
        .dropdown-item.text-red:hover { background: #fef2f2; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        /* Page Specific Styles */
        .content-wrapper { padding: 2rem; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .search-bar {
            background: white; border: 1px solid #e2e8f0; border-radius: var(--radius-md);
            padding: 0.5rem 1rem; display: flex; align-items: center; gap: 0.5rem;
            width: 300px; transition: var(--transition);
        }
        .search-bar:focus-within { border-color: var(--accent); box-shadow: 0 0 0 2px rgba(245, 158, 11, 0.1); }
        .search-bar input { border: none; outline: none; width: 100%; color: var(--text-main); }
        
        .btn-primary {
            background: var(--accent); color: white; padding: 0.75rem 1.5rem;
            border-radius: var(--radius-md); text-decoration: none; font-weight: 600;
            display: inline-flex; align-items: center; gap: 0.5rem; transition: var(--transition);
        }
        .btn-primary:hover { background: var(--accent-hover); transform: translateY(-2px); }

        .table-card { background: white; border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); overflow: hidden; }
        .table-responsive { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 1rem 1.5rem; text-align: left; border-bottom: 1px solid #f1f5f9; font-size: 0.9rem; }
        th { background: #f8fafc; color: var(--text-light); font-weight: 600; white-space: nowrap; }
        td { color: var(--text-main); vertical-align: middle; }
        tr:hover { background: #f8fafc; }
        
        .product-img { width: 48px; height: 48px; border-radius: var(--radius-md); object-fit: cover; background: #f1f5f9; }
        .action-btn {
            padding: 0.4rem 0.8rem; border-radius: var(--radius-md); font-size: 0.85rem;
            text-decoration: none; font-weight: 500; display: inline-block; margin-right: 0.25rem;
        }
        .btn-edit { background: #e0f2fe; color: #0284c7; }
        .btn-edit:hover { background: #bae6fd; }
        .btn-delete { background: #fee2e2; color: #ef4444; }
        .btn-delete:hover { background: #fecaca; }
        
        .stock-badge {
            padding: 0.25rem 0.6rem; border-radius: 20px; font-size: 0.75rem; font-weight: 600;
        }
        .stock-in { background: #dcfce7; color: #166534; }
        .stock-low { background: #ffedd5; color: #c2410c; }
        .stock-out { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="sidebar-header">
            <img src="https://yyeploxrzxwhhsnffexp.supabase.co/storage/v1/object/public/Construct_image/logo.png" alt="Logo">
            Constructo
        </div>
        <nav class="nav-menu">
            <a href="dashboard.php" class="nav-item"><i class="fas fa-th-large"></i> Dashboard</a>
            <a href="view_products.php" class="nav-item active"><i class="fas fa-box"></i> Products</a>
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

    <div class="main-content">
        <header class="top-header">
            <div class="header-title">Product Management</div>
            <div class="user-profile" onclick="toggleProfileDropdown()">
                <div class="profile-info">
                    <div class="profile-name"><?php echo htmlspecialchars($firstName); ?></div>
                    <div class="profile-role">Administrator</div>
                </div>
                <div class="profile-avatar"><?php echo $initial; ?></div>
                <div class="profile-dropdown" id="profileDropdown">
                    <a href="../logout.php" class="dropdown-item text-red"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
        </header>

        <div class="content-wrapper">
            <div class="page-header">
                <div class="search-bar">
                    <i class="fas fa-search" style="color: #94a3b8;"></i>
                    <input type="text" id="searchInput" placeholder="Search products..." onkeyup="filterTable()">
                </div>
                <a href="add_product.php" class="btn-primary"><i class="fas fa-plus"></i> Add Product</a>
            </div>

            <div class="table-card">
                <div class="table-responsive">
                    <table id="productsTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Image</th>
                                <th>Product Name</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($products) > 0): ?>
                                <?php foreach ($products as $row): 
                                    $stockClass = 'stock-in';
                                    $stockText = 'In Stock';
                                    if ($row['stock'] == 0) { $stockClass = 'stock-out'; $stockText = 'Out of Stock'; }
                                    elseif ($row['stock'] < 10) { $stockClass = 'stock-low'; $stockText = 'Low Stock (' . $row['stock'] . ')'; }
                                    else { $stockText = $row['stock'] . ' Units'; }
                                ?>
                                <tr>
                                    <td>#<?php echo $row['id']; ?></td>
                                    <td>
                                        <?php 
                                            $imageUrl = $row['image_url'];
                                            if (!filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                                                // Clean up the path to get just the filename
                                                $filename = str_replace(['uploaded_images/', 'admin/'], '', $imageUrl);
                                                // Construct Supabase URL
                                                $imageUrl = SUPABASE_STORAGE_URL . $filename;
                                            }
                                            $placeholder = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI0OCIgaGVpZ2h0PSI0OCIgdmlld0JveD0iMCAwIDQ4IDQ4Ij48cmVjdCB3aWR0aD0iNDgiIGhlaWdodD0iNDgiIGZpbGw9IiNmMWY1ZjkiLz48dGV4dCB4PSI1MCUiIHk9IjUwJSIgZm9udC1mYW1pbHk9InNhbnMtc2VyaWYiIGZvbnQtc2l6ZT0iMTIiIGZpbGw9IiM5NGEzYjgiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIj5Ob3QgRm91bmQ8L3RleHQ+PC9zdmc+';
                                        ?>
                                        <img src="<?php echo htmlspecialchars($imageUrl); ?>" class="product-img" alt="Product" onerror="this.src='<?php echo $placeholder; ?>'">
                                    </td>
                                    <td>
                                        <div style="font-weight: 500; color: var(--primary);"><?php echo htmlspecialchars($row['product_name']); ?></div>
                                        <div style="font-size: 0.8rem; color: var(--text-light);"><?php echo htmlspecialchars($row['brand']); ?></div>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                                    <td style="font-weight: 600;">₹<?php echo number_format($row['price'], 2); ?></td>
                                    <td><span class="stock-badge <?php echo $stockClass; ?>"><?php echo $stockText; ?></span></td>
                                    <td>
                                        <a href="edit_product.php?id=<?php echo $row['id']; ?>" class="action-btn btn-edit"><i class="fas fa-edit"></i> Edit</a>
                                        <a href="delete_product.php?id=<?php echo $row['id']; ?>" class="action-btn btn-delete" onclick="return confirm('Are you sure you want to delete this product?');"><i class="fas fa-trash"></i></a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="7" style="text-align:center; padding: 2rem;">No products found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleProfileDropdown() {
            const dropdown = document.getElementById('profileDropdown');
            dropdown.classList.toggle('show');
        }

        window.onclick = function(event) {
            if (!event.target.closest('.user-profile')) {
                const dropdown = document.getElementById('profileDropdown');
                if (dropdown && dropdown.classList.contains('show')) {
                    dropdown.classList.remove('show');
                }
            }
        }

        function filterTable() {
            const input = document.getElementById("searchInput");
            const filter = input.value.toUpperCase();
            const table = document.getElementById("productsTable");
            const tr = table.getElementsByTagName("tr");

            for (let i = 1; i < tr.length; i++) {
                const tds = tr[i].getElementsByTagName("td");
                // Skip rows that don't have enough columns (e.g., "No products found" message)
                if (tds.length < 4) continue;

                const tdName = tds[2]; // Product Name column
                const tdCategory = tds[3]; // Category column
                
                if (tdName && tdCategory) {
                    const txtValueName = tdName.textContent || tdName.innerText;
                    const txtValueCategory = tdCategory.textContent || tdCategory.innerText;
                    if (txtValueName.toUpperCase().indexOf(filter) > -1 || txtValueCategory.toUpperCase().indexOf(filter) > -1) {
                        tr[i].style.display = "";
                    } else {
                        tr[i].style.display = "none";
                    }
                }
            }
        }
    </script>
</body>
</html>
