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
    $sortOrder = isset($_GET['sort']) && $_GET['sort'] === 'asc' ? 'ASC' : 'DESC';
    $sql = "SELECT products.*, COALESCE(categories.category_name, 'Uncategorized') AS category_name 
            FROM products 
            LEFT JOIN categories ON products.category_id = categories.id
            ORDER BY products.id $sortOrder";
    $stmt = $conn->query($sql);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch Categories for Filter
    $catStmt = $conn->query("SELECT category_name FROM categories ORDER BY category_name ASC");
    $filterCategories = $catStmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch Unique Brands for Filter
    $brandStmt = $conn->query("SELECT DISTINCT brand FROM products WHERE brand IS NOT NULL AND brand != '' ORDER BY brand ASC");
    $filterBrands = $brandStmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
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
    <link rel="stylesheet" href="../style.css">
    <style>
        /* Specific overrides for this page if needed */
        .product-img {
            width: 48px;
            height: 48px;
            border-radius: var(--radius-md);
            object-fit: cover;
            background: #f1f5f9;
        }
    </style>
</head>

<body>

    <aside class="sidebar">
        <div class="sidebar-header">
            <img src="../images/logo.png" alt="Logo">
            Constructo
        </div>
        <nav class="nav-menu">
            <a href="dashboard.php" class="nav-item"><i class="fas fa-th-large"></i> Dashboard</a>
            <a href="view_products.php" class="nav-item active"><i class="fas fa-box"></i> Products</a>
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
                    <a href="../logout.php" class="dropdown-item text-red"><i class="fas fa-sign-out-alt"></i>
                        Logout</a>
                </div>
            </div>
        </header>

        <div class="content-wrapper">
            <div class="admin-page-header" style="flex-direction: column; align-items: stretch; gap: 1.5rem;">
                <!-- Row 1: Search and Stats -->
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                    <div class="search-bar" style="max-width: 400px; flex-grow: 1; box-shadow: var(--shadow-sm); border: 1px solid #e2e8f0;">
                        <i class="fas fa-search" style="color: #94a3b8;"></i>
                        <input type="text" id="searchInput" placeholder="Search by name, category, or brand..." onkeyup="filterTable()">
                    </div>
                    
                    <div style="background: white; padding: 0.6rem 1.25rem; border-radius: 2rem; border: 1px solid #e2e8f0; font-weight: 600; color: var(--primary); white-space: nowrap; box-shadow: var(--shadow-sm); display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-box-open" style="color: var(--accent);"></i>
                        <span>Total: <?php echo count($products); ?> Products</span>
                    </div>
                </div>

                <!-- Row 2: Filters and Actions -->
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                    <div style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
                        <select id="categoryFilter" onchange="filterTable()"
                            style="padding: 0.6rem 2rem 0.6rem 1rem; border-radius: var(--radius-md); border: 1px solid #e2e8f0; outline: none; cursor: pointer; background: white; color: var(--text-main); font-family: inherit; font-size: 0.9rem; box-shadow: var(--shadow-sm); min-width: 140px;">
                            <option value="">All Categories</option>
                            <?php if (count($filterCategories) > 0): ?>
                                <?php foreach ($filterCategories as $cat): ?>
                                    <option value="<?php echo htmlspecialchars($cat['category_name']); ?>">
                                        <?php echo htmlspecialchars($cat['category_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>

                        <select id="brandFilter" onchange="filterTable()"
                            style="padding: 0.6rem 2rem 0.6rem 1rem; border-radius: var(--radius-md); border: 1px solid #e2e8f0; outline: none; cursor: pointer; background: white; color: var(--text-main); font-family: inherit; font-size: 0.9rem; box-shadow: var(--shadow-sm); min-width: 130px;">
                            <option value="">All Brands</option>
                            <?php if (count($filterBrands) > 0): ?>
                                <?php foreach ($filterBrands as $brand): ?>
                                    <option value="<?php echo htmlspecialchars($brand); ?>">
                                        <?php echo htmlspecialchars($brand); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>

                        <select id="stockFilter" onchange="filterTable()"
                            style="padding: 0.6rem 2rem 0.6rem 1rem; border-radius: var(--radius-md); border: 1px solid #e2e8f0; outline: none; cursor: pointer; background: white; color: var(--text-main); font-family: inherit; font-size: 0.9rem; box-shadow: var(--shadow-sm); min-width: 140px;">
                            <option value="">All Stock Status</option>
                            <option value="In Stock">In Stock</option>
                            <option value="Low">Low Stock</option>
                            <option value="Out of Stock">Out of Stock</option>
                        </select>
                    </div>

                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <select id="sortFilter" onchange="window.location.search = '?sort=' + this.value"
                            style="padding: 0.6rem 2rem 0.6rem 1rem; border-radius: var(--radius-md); border: 1px solid #e2e8f0; outline: none; cursor: pointer; background: white; color: var(--text-main); font-family: inherit; font-size: 0.9rem; box-shadow: var(--shadow-sm);">
                            <option value="desc" <?php echo (isset($_GET['sort']) && $_GET['sort'] === 'desc') ? 'selected' : ''; ?>>Newest First</option>
                            <option value="asc" <?php echo (isset($_GET['sort']) && $_GET['sort'] === 'asc') ? 'selected' : ''; ?>>Oldest First</option>
                        </select>

                        <a href="add_product.php" class="btn btn-primary" style="padding: 0.6rem 1.25rem; box-shadow: var(--shadow-md); white-space: nowrap;">
                            <i class="fas fa-plus"></i> Add Product
                        </a>
                    </div>
                </div>
            </div>

            <div class="admin-card">
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
                                    $stockClass = 'status-delivered'; // Green
                                    $stockText = 'In Stock';
                                    if ($row['stock'] == 0) {
                                        $stockClass = 'status-cancelled';
                                        $stockText = 'Out of Stock';
                                    } elseif ($row['stock'] < 10) {
                                        $stockClass = 'status-warning';
                                        $stockText = 'Low (' . $row['stock'] . ')';
                                    } else {
                                        $stockText = $row['stock'] . ' Units';
                                    }
                                    ?>
                                    <tr>
                                        <td>#<?php echo $row['id']; ?></td>
                                        <td>
                                            <?php
                                            $imageUrl = $row['image_url'];
                                            if (!filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                                                $filename = str_replace(['uploaded_images/', 'admin/'], '', $imageUrl);
                                                $imageUrl = STORAGE_URL . $filename;
                                            }
                                            $placeholder = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI0OCIgaGVpZ2h0PSI0OCIgdmlld0JveD0iMCAwIDQ4IDQ4Ij48cmVjdCB3aWR0aD0iNDgiIGhlaWdodD0iNDgiIGZpbGw9IiNmMWY1ZjkiLz48dGV4dCB4PSI1MCUiIHk9IjUwJSIgZm9udC1mYW1pbHk9InNhbnMtc2VyaWYiIGZvbnQtc2l6ZT0iMTIiIGZpbGw9IiM5NGEzYjgiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIj5Ob3QgRm91bmQ8L3RleHQ+PC9zdmc+';
                                            ?>
                                            <img src="<?php echo htmlspecialchars($imageUrl); ?>" class="product-img"
                                                alt="Product" onerror="this.src='<?php echo $placeholder; ?>'">
                                        </td>
                                        <td>
                                            <div style="font-weight: 500; color: var(--primary);">
                                                <?php echo htmlspecialchars($row['product_name']); ?>
                                            </div>
                                            <div style="font-size: 0.8rem; color: var(--text-light);">
                                                <?php echo htmlspecialchars($row['brand']); ?>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                                        <td style="font-weight: 600;">₹<?php echo number_format($row['price'], 2); ?></td>
                                        <td><span
                                                class="status-badge <?php echo $stockClass; ?>"><?php echo $stockText; ?></span>
                                        </td>
                                        <td>
                                            <a href="edit_product.php?id=<?php echo $row['id']; ?>"
                                                class="table-action-btn btn-edit"><i class="fas fa-edit"></i> Edit</a>
                                            <a href="delete_product.php?id=<?php echo $row['id']; ?>"
                                                class="table-action-btn btn-delete"
                                                onclick="return confirm('Are you sure you want to delete this product?');"><i
                                                    class="fas fa-trash"></i></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" style="text-align:center; padding: 2rem;">No products found.</td>
                                </tr>
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

        window.onclick = function (event) {
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
            
            const categorySelect = document.getElementById("categoryFilter");
            const categoryFilter = categorySelect.value.toUpperCase();
            
            const brandSelect = document.getElementById("brandFilter");
            const brandFilter = brandSelect.value.toUpperCase();
            
            const stockSelect = document.getElementById("stockFilter");
            const stockFilter = stockSelect.value; // Keep case sensitive for precise matching logic below if needed, or upper

            const table = document.getElementById("productsTable");
            const tr = table.getElementsByTagName("tr");

            for (let i = 1; i < tr.length; i++) {
                const tds = tr[i].getElementsByTagName("td");
                if (tds.length < 5) continue;

                // Name is in col 2 (index 2), Brand is also in col 2 inside a div
                // Let's inspect the structure again:
                // col 2: div(Name) div(Brand)
                // col 3: Category
                // col 5: Stock Status (span text)

                const tdNameCol = tds[2];
                const tdCategory = tds[3];
                const tdStock = tds[5];

                if (tdNameCol && tdCategory && tdStock) {
                    const nameText = tdNameCol.getElementsByTagName("div")[0]?.textContent || "";
                    const brandText = tdNameCol.getElementsByTagName("div")[1]?.textContent || "";
                    const categoryText = tdCategory.textContent || tdCategory.innerText;
                    const stockText = tdStock.textContent || tdStock.innerText;

                    // 1. Search Filter (Name or Category)
                    const matchesSearch = nameText.toUpperCase().indexOf(filter) > -1 || 
                                          categoryText.toUpperCase().indexOf(filter) > -1 ||
                                          brandText.toUpperCase().indexOf(filter) > -1;

                    // 2. Category Filter
                    const matchesCategory = categoryFilter === "" || categoryText.toUpperCase().trim() === categoryFilter.trim();

                    // 3. Brand Filter
                    const matchesBrand = brandFilter === "" || brandText.toUpperCase().trim() === brandFilter.trim();

                    // 4. Stock Filter
                    let matchesStock = true;
                    if (stockFilter !== "") {
                        const sText = stockText.trim();
                        if (stockFilter === "In Stock") {
                            // Matches if not Low and not Out of Stock. 
                            // i.e. it shows "N Units"
                             if (sText === "Out of Stock" || sText.startsWith("Low")) {
                                 matchesStock = false;
                             }
                        } else if (stockFilter === "Low") {
                            matchesStock = sText.startsWith("Low");
                        } else if (stockFilter === "Out of Stock") {
                            matchesStock = sText === "Out of Stock";
                        }
                    }

                    if (matchesSearch && matchesCategory && matchesBrand && matchesStock) {
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