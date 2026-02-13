<?php
session_start();
include '../db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user info
$first_name = "";
$last_name = "";
$user_stmt = $conn->prepare("SELECT first_name, last_name FROM clients WHERE id = :id");
$user_stmt->execute([':id' => $user_id]);
$user = $user_stmt->fetch(PDO::FETCH_ASSOC);
if ($user) {
    $first_name = $user['first_name'];
    $last_name = $user['last_name'];
}

// Fetch Wishlist Items
try {
    $sql = "
        SELECT p.*, w.created_at as added_on 
        FROM wishlist w 
        JOIN products p ON w.product_id = p.id 
        WHERE w.user_id = :uid 
        ORDER BY w.created_at DESC
    ";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':uid' => $user_id]);
    $wishlist_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $wishlist_items = [];
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Wishlist - Constructo</title>
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

        .page-header {
            margin-bottom: 2rem;
        }

        .page-title {
            font-size: 2rem;
            color: var(--primary);
            font-weight: 700;
        }

        /* Wishlist Grid */
        .wishlist-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2rem;
        }

        @media (max-width: 992px) {
            .wishlist-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 600px) {
            .wishlist-grid {
                grid-template-columns: 1fr;
            }
        }

        .product-card {
            background: var(--bg-card);
            border-radius: var(--radius-md);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
            border: 1px solid rgba(0, 0, 0, 0.05);
            display: flex;
            flex-direction: column;
            position: relative;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .product-img {
            width: 100%;
            height: 220px;
            object-fit: cover;
            background: #f1f5f9;
        }

        .card-info {
            padding: 1.5rem;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .card-title {
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 0.5rem;
            font-size: 1.1rem;
        }

        .card-price {
            color: var(--accent);
            font-weight: 700;
            font-size: 1.2rem;
            margin-bottom: 1rem;
        }

        .card-actions {
            margin-top: auto;
            display: flex;
            gap: 0.5rem;
        }

        .btn {
            flex: 1;
            padding: 0.6rem;
            border-radius: var(--radius-md);
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            text-decoration: none;
            font-size: 0.9rem;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-light);
        }

        .btn-danger {
            background: #fee2e2;
            color: #991b1b;
        }

        .btn-danger:hover {
            background: #fecaca;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
        }

        .empty-icon {
            font-size: 4rem;
            color: #cbd5e1;
            margin-bottom: 1.5rem;
        }

        .empty-text {
            color: var(--text-light);
            margin-bottom: 2rem;
            font-size: 1.1rem;
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

        /* Custom Modal */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 3000;
            display: none;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(4px);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .modal-overlay.show {
            display: flex;
            opacity: 1;
        }

        .modal-box {
            background: white;
            padding: 2rem;
            border-radius: var(--radius-lg);
            width: 90%;
            max-width: 400px;
            text-align: center;
            transform: scale(0.9);
            transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            box-shadow: var(--shadow-lg);
        }

        .modal-overlay.show .modal-box {
            transform: scale(1);
        }

        .modal-icon {
            width: 60px;
            height: 60px;
            background: #fee2e2;
            color: #ef4444;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin: 0 auto 1.5rem;
        }

        .modal-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .modal-text {
            color: var(--text-light);
            margin-bottom: 2rem;
        }

        .modal-actions {
            display: flex;
            gap: 1rem;
        }

        .modal-btn {
            flex: 1;
            padding: 0.75rem;
            border-radius: var(--radius-md);
            border: none;
            font-weight: 600;
            cursor: pointer;
            font-size: 1rem;
            transition: var(--transition);
        }

        .modal-btn-cancel {
            background: #f1f5f9;
            color: var(--text-main);
        }

        .modal-btn-cancel:hover {
            background: #e2e8f0;
        }

        .modal-btn-confirm {
            background: #ef4444;
            color: white;
        }

        .modal-btn-confirm:hover {
            background: #dc2626;
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
            void toast.offsetWidth; // Trigger reflow
            toast.classList.add('show');

            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 400);
            }, 3000);
        }

        let itemToRemoveId = null;
        let itemToRemoveBtn = null;

        function confirmRemove(productId, btn) {
            itemToRemoveId = productId;
            itemToRemoveBtn = btn;
            const modal = document.getElementById('confirm-modal');
            modal.classList.add('show');
        }

        function closeModal() {
            const modal = document.getElementById('confirm-modal');
            modal.classList.remove('show');
            itemToRemoveId = null;
            itemToRemoveBtn = null;
        }

        function proceedRemove() {
            if (!itemToRemoveId) return;

            fetch('wishlist_action.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=remove&product_id=' + itemToRemoveId
            })
                .then(response => response.json())
                .then(data => {
                    closeModal();
                    if (data.success) {
                        showToast('Removed', 'Item removed from wishlist');
                        const card = itemToRemoveBtn.closest('.product-card');
                        card.style.opacity = '0';
                        setTimeout(() => {
                            card.remove();
                            if (document.querySelectorAll('.product-card').length === 0) {
                                location.reload();
                            }

                        }, 300);
                    } else {
                        showToast('Error', data.message, 'error');
                    }
                })
                .catch(error => {
                    closeModal();
                    showToast('Error', 'Something went wrong', 'error');
                });
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
            <h1 class="page-title">My Wishlist</h1>
        </div>

        <?php if (count($wishlist_items) > 0): ?>
            <div class="wishlist-grid">
                <?php foreach ($wishlist_items as $item): ?>
                    <?php
                    $imgUrl = $item['image_url'];
                    if (!filter_var($imgUrl, FILTER_VALIDATE_URL)) {
                        $imgUrl = str_replace(['uploaded_images/', 'admin/'], '', $imgUrl);
                        $imgUrl = STORAGE_URL . $imgUrl;
                    }
                    ?>
                    <div class="product-card">
                        <a href="product_detail.php?id=<?php echo $item['id']; ?>">
                            <img src="<?php echo htmlspecialchars($imgUrl); ?>"
                                alt="<?php echo htmlspecialchars($item['product_name']); ?>" class="product-img">
                        </a>
                        <div class="card-info">
                            <div class="card-title"><?php echo htmlspecialchars($item['product_name']); ?></div>
                            <div class="card-price">₹<?php echo htmlspecialchars($item['price']); ?></div>
                            <div class="card-actions">
                                <a href="product_detail.php?id=<?php echo $item['id']; ?>" class="btn btn-primary">
                                    <i class="fas fa-shopping-cart"></i> View
                                </a>
                                <button class="btn btn-danger" onclick="confirmRemove(<?php echo $item['id']; ?>, this)">
                                    <i class="fas fa-trash"></i> Remove
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="far fa-heart empty-icon"></i>
                <h2>Your wishlist is empty</h2>
                <p class="empty-text">Save items you love to your wishlist and review them later.</p>
                <a href="index.php" class="btn btn-primary" style="display: inline-flex; width: auto;">Browse Products</a>
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

    <div id="toast-container" class="toast-container"></div>

    <!-- Confirmation Modal -->
    <div id="confirm-modal" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-icon">
                <i class="fas fa-trash-alt"></i>
            </div>
            <h3 class="modal-title">Remove Item?</h3>
            <p class="modal-text">Are you sure you want to remove this item from your wishlist?</p>
            <div class="modal-actions">
                <button class="modal-btn modal-btn-cancel" onclick="closeModal()">Cancel</button>
                <button class="modal-btn modal-btn-confirm" onclick="proceedRemove()">Yes, Remove</button>
            </div>
        </div>
    </div>
</body>

</html>