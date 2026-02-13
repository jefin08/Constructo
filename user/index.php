<?php
session_start();

// Database connection settings
include '../db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php"); // Redirect to login page if not logged in
    exit();
}

$user_id = $_SESSION['user_id'];

// Initialize compare array in session if not exists
if (!isset($_SESSION['compare_products'])) {
    $_SESSION['compare_products'] = array();
}

try {
    // Fetch user information
    $stmt = $conn->prepare("SELECT first_name, last_name FROM clients WHERE id = :id");
    $stmt->bindParam(':id', $user_id);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        $first_name = $user['first_name'];
        $last_name = $user['last_name'];
    }

    // Initialize filters
    $search_query = "";
    $category_filter = "";
    $sort_by = "";
    $min_rating = 0;

    if (isset($_GET['search'])) {
        $search_query = $_GET['search'];
    }

    if (isset($_GET['category'])) {
        $category_filter = $_GET['category'];
    }

    if (isset($_GET['sort'])) {
        $sort_by = $_GET['sort'];
    }

    if (isset($_GET['rating'])) {
        $min_rating = (int) $_GET['rating'];
    }

    $min_price = isset($_GET['min_price']) && $_GET['min_price'] !== '' ? (float) $_GET['min_price'] : null;
    $max_price = isset($_GET['max_price']) && $_GET['max_price'] !== '' ? (float) $_GET['max_price'] : null;

    $brand_filters = isset($_GET['brands']) ? $_GET['brands'] : [];
    $availability_filter = isset($_GET['availability']) ? $_GET['availability'] : '';

    // Fetch categories
    $categories_stmt = $conn->query("SELECT * FROM categories");
    $categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Build product query with filters
    $product_sql = "SELECT * FROM products WHERE 1=1";
    $params = array();

    if (!empty($search_query)) {
        $product_sql .= " AND product_name ILIKE :search_query";
        $params[':search_query'] = "%$search_query%";
    }

    if (!empty($category_filter)) {
        $product_sql .= " AND category_id = :category_id";
        $params[':category_id'] = $category_filter;
    }

    if ($min_rating > 0) {
        $product_sql .= " AND rating >= :min_rating";
        $params[':min_rating'] = $min_rating;
    }

    if ($min_price !== null) {
        $product_sql .= " AND price >= :min_price";
        $params[':min_price'] = $min_price;
    }

    if ($max_price !== null) {
        $product_sql .= " AND price <= :max_price";
        $params[':max_price'] = $max_price;
    }

    if (!empty($brand_filters)) {
        $brand_placeholders = [];
        foreach ($brand_filters as $index => $brand) {
            $key = ":brand_" . $index;
            $brand_placeholders[] = $key;
            $params[$key] = $brand;
        }
        $product_sql .= " AND brand IN (" . implode(',', $brand_placeholders) . ")";
    }

    if ($availability_filter === 'in_stock') {
        $product_sql .= " AND stock > 0";
    }

    // Add sorting
    if ($sort_by == 'price_low') {
        $product_sql .= " ORDER BY price ASC";
    } elseif ($sort_by == 'price_high') {
        $product_sql .= " ORDER BY price DESC";
    } elseif ($sort_by == 'rating') {
        $product_sql .= " ORDER BY rating DESC";
    } else {
        $product_sql .= " ORDER BY id DESC";
    }

    $product_stmt = $conn->prepare($product_sql);
    foreach ($params as $key => $value) {
        $product_stmt->bindValue($key, $value);
    }
    $product_stmt->execute();
    $products = $product_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch unique brands
    $brands_stmt = $conn->query("SELECT DISTINCT brand FROM products WHERE brand IS NOT NULL");
    $brands = $brands_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Constructo</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Modern CSS Reset & Variables */
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
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
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

        /* Utilities */
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 1.5rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.5rem 1rem;
            border-radius: var(--radius-md);
            font-weight: 600;
            text-decoration: none;
            transition: var(--transition);
            cursor: pointer;
            border: none;
            gap: 0.5rem;
            font-size: 0.9rem;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-light);
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }

        .btn-accent {
            background: var(--accent);
            color: white;
        }

        .btn-accent:hover {
            background: var(--accent-hover);
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }

        .btn-outline {
            border: 1px solid #cbd5e1;
            color: var(--text-main);
            background: white;
        }

        .btn-outline:hover {
            border-color: var(--primary);
            color: var(--primary);
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

        /* Hero */
        .dashboard-hero {
            padding: 8rem 0 4rem;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }

        .hero-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            z-index: 1;
        }

        .hero-text {
            max-width: 600px;
        }

        .hero-text h1 {
            font-size: 3rem;
            color: var(--primary);
            margin-bottom: 1rem;
            line-height: 1.2;
        }

        .hero-text p {
            color: var(--text-light);
            font-size: 1.25rem;
        }

        .hero-image img {
            max-height: 400px;
            width: auto;
            filter: drop-shadow(0 10px 20px rgba(0, 0, 0, 0.1));
            animation: slideIn 0.8s ease-out;
        }

        .hero-search {
            margin-top: 2rem;
            max-width: 400px;
        }

        .hero-search form {
            display: flex;
            gap: 0.5rem;
            background: white;
            padding: 0.5rem;
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-md);
        }

        .hero-search input {
            border: none;
            flex-grow: 1;
            padding: 0.5rem;
            font-size: 1rem;
            outline: none;
        }

        .hero-search button {
            padding: 0.5rem 1.5rem;
        }

        /* Dashboard Grid */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 220px 1fr 250px;
            gap: 2rem;
            padding-bottom: 4rem;
        }

        /* Modern Sidebar Filters */
        .filter-sidebar {
            background: transparent;
            padding: 0;
            box-shadow: none;
            position: sticky;
            top: 90px;
            height: calc(100vh - 110px);
            overflow-y: auto;
            padding-right: 0.5rem;
        }

        .filter-sidebar::-webkit-scrollbar {
            width: 4px;
        }

        .filter-sidebar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 2px;
        }

        .filter-card {
            background: var(--bg-card);
            padding: 1.5rem;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            margin-bottom: 1.5rem;
            border: 1px solid #f1f5f9;
        }

        .filter-title {
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--text-light);
            margin-bottom: 1.25rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .filter-list li {
            margin-bottom: 0.5rem;
        }

        .filter-list label {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: var(--text-main);
            cursor: pointer;
            font-size: 0.95rem;
            transition: var(--transition);
            padding: 0.5rem;
            border-radius: var(--radius-md);
        }

        .filter-list label:hover {
            background-color: #f8fafc;
            color: var(--primary);
        }

        .filter-list input[type="radio"] {
            appearance: none;
            width: 18px;
            height: 18px;
            border: 2px solid #cbd5e1;
            border-radius: 50%;
            position: relative;
            transition: var(--transition);
        }

        .filter-list input[type="radio"]:checked {
            border-color: var(--accent);
            background-color: var(--accent);
        }

        .filter-list input[type="radio"]:checked::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 8px;
            height: 8px;
            background: white;
            border-radius: 50%;
        }

        /* Product Grid */
        /* Product Grid */
        .product-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
        }

        @media (max-width: 1200px) {
            .product-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 600px) {
            .product-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Top Bar */
        .products-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            background: white;
            padding: 1rem;
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-sm);
        }

        .sort-dropdown {
            padding: 0.5rem;
            border: 1px solid #cbd5e1;
            border-radius: var(--radius-md);
            color: var(--text-main);
            font-family: 'Outfit', sans-serif;
        }

        /* Price Range Inputs */
        /* Price Range Inputs */
        .price-inputs {
            display: flex;
            gap: 0.5rem;
            align-items: center;
            margin-bottom: 1rem;
        }

        .price-inputs input {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #cbd5e1;
            border-radius: var(--radius-md);
            font-size: 0.9rem;
        }

        /* Price Slider */
        .price-slider-container {
            margin: 1.5rem 0.5rem;
            position: relative;
            height: 6px;
            background: #e2e8f0;
            border-radius: 3px;
        }

        .price-slider-track {
            position: absolute;
            height: 100%;
            background: var(--accent);
            border-radius: 3px;
        }

        .price-slider-handle {
            position: absolute;
            top: 50%;
            width: 18px;
            height: 18px;
            background: white;
            border: 2px solid var(--accent);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            cursor: pointer;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            z-index: 2;
        }

        .price-slider-handle:hover {
            transform: translate(-50%, -50%) scale(1.1);
        }

        /* Checkbox List */
        .checkbox-list {
            max-height: 200px;
            overflow-y: auto;
            padding-right: 0.5rem;
        }

        .checkbox-list::-webkit-scrollbar {
            width: 4px;
        }

        .checkbox-list::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 2px;
        }

        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0.5rem;
            cursor: pointer;
            font-size: 0.95rem;
            color: var(--text-main);
        }

        .checkbox-item input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: var(--accent);
            border-radius: 4px;
            cursor: pointer;
        }

        /* Collapsible Filters */
        .filter-header {
            cursor: pointer;
        }

        .filter-content {
            overflow: hidden;
            transition: max-height 0.3s ease-out;
        }

        .filter-content.collapsed {
            max-height: 0;
        }

        .filter-toggle-icon {
            transition: transform 0.3s;
        }

        .filter-header.collapsed .filter-toggle-icon {
            transform: rotate(-90deg);
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
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .product-img {
            width: 100%;
            height: 140px;
            object-fit: contain;
            background: #fff;
            padding: 10px;
        }

        .product-info {
            padding: 1.25rem;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .product-name {
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 0.5rem;
            line-height: 1.4;
        }

        .product-price {
            font-size: 1.25rem;
            color: var(--accent);
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .product-rating {
            color: #fbbf24;
            font-size: 0.85rem;
            margin-bottom: 1rem;
        }

        .product-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: auto;
        }

        .product-actions .btn {
            flex: 1;
        }

        /* Modern Compare Sidebar */
        .compare-sidebar {
            background: var(--bg-card);
            padding: 1.5rem;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            height: fit-content;
            position: sticky;
            top: 90px;
            border: 1px solid #f1f5f9;
        }

        .compare-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #f1f5f9;
        }

        .compare-header h3 {
            font-size: 1rem;
            font-weight: 700;
            color: var(--primary);
            margin: 0;
        }

        .clear-compare {
            font-size: 0.8rem;
            color: #ef4444;
            cursor: pointer;
            font-weight: 500;
            transition: var(--transition);
        }

        .clear-compare:hover {
            text-decoration: underline;
        }

        .compare-item {
            background: #fff;
            padding: 0.75rem;
            border-radius: var(--radius-md);
            margin-bottom: 0.75rem;
            display: flex;
            gap: 0.75rem;
            align-items: center;
            border: 1px solid #e2e8f0;
            transition: var(--transition);
        }

        .compare-item:hover {
            border-color: var(--accent);
            transform: translateX(2px);
        }

        .compare-item img {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border-radius: 6px;
        }

        .compare-details {
            flex-grow: 1;
            min-width: 0;
        }

        .compare-name {
            font-weight: 600;
            font-size: 0.85rem;
            color: var(--primary);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-bottom: 0.1rem;
        }

        .compare-price {
            color: var(--accent);
            font-weight: 700;
            font-size: 0.85rem;
        }

        .remove-compare {
            color: #94a3b8;
            cursor: pointer;
            transition: color 0.2s;
            font-size: 0.9rem;
        }

        .remove-compare:hover {
            color: #ef4444;
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
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(20px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @media (max-width: 1200px) {
            .dashboard-grid {
                grid-template-columns: 250px 1fr;
            }

            .compare-sidebar {
                display: none;
            }

            /* Hide compare sidebar on smaller screens for now */
        }

        @media (max-width: 900px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }

            .filter-sidebar {
                display: none;
            }

            /* Ideally make this a collapsible drawer */
            .nav-links {
                display: none;
            }

            .hero-content {
                flex-direction: column;
                text-align: center;
            }

            .hero-image {
                margin-top: 2rem;
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
                    if (openDropdown.classList.contains('show')) {
                        openDropdown.classList.remove('show');
                    }
                }
            }
        }

        let compareProducts = [];

        function addToCompare(productId, productName, productPrice, productImage, productRating, categoryId) {
            if (compareProducts.some(p => p.id === productId)) {
                alert('This product is already in your compare list!');
                return;
            }

            if (compareProducts.length > 0 && compareProducts[0].categoryId !== categoryId) {
                alert('You can only compare products from the same category!');
                return;
            }

            if (compareProducts.length >= 5) {
                alert('You can only compare up to 5 products at a time!');
                return;
            }

            compareProducts.push({
                id: productId,
                name: productName,
                price: productPrice,
                image: productImage,
                rating: productRating,
                categoryId: categoryId
            });
            updateCompareList();
        }


        function removeFromCompare(productId) {
            compareProducts = compareProducts.filter(p => p.id !== productId);
            updateCompareList();
        }

        function clearCompare() {
            compareProducts = [];
            updateCompareList();
        }

        function updateCompareList() {
            const compareList = document.getElementById('compare-list');
            if (compareProducts.length === 0) {
                compareList.innerHTML = `
                    <div style="text-align: center; padding: 2rem 0; color: #94a3b8;">
                        <div style="background: #f1f5f9; width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem;">
                            <i class="fas fa-balance-scale" style="font-size: 1.5rem; color: #cbd5e1;"></i>
                        </div>
                        <p style="font-size: 0.9rem; font-weight: 500;">Ready to Compare?</p>
                        <p style="font-size: 0.8rem; opacity: 0.7;">Select products to see differences</p>
                    </div>`;
            } else {
                let html = `
                    <div class="compare-header">
                        <h3>Compare (${compareProducts.length})</h3>
                        <span class="clear-compare" onclick="clearCompare()">Clear All</span>
                    </div>`;

                compareProducts.forEach(p => {
                    html += `<div class="compare-item">
                                <img src="${p.image}" alt="${p.name}">
                                <div class="compare-details">
                                    <div class="compare-name" title="${p.name}">${p.name}</div>
                                    <div class="compare-price">₹${p.price}</div>
                                </div>
                                <i class="fas fa-times remove-compare" onclick="removeFromCompare(${p.id})"></i>
                             </div>`;
                });
                html += `<div style="margin-top: 1.5rem;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.75rem; font-size: 0.85rem; color: var(--text-light);">
                                <span>Slots Used</span>
                                <span style="font-weight: 600; color: var(--primary);">${compareProducts.length} of 5</span>
                            </div>
                            <div style="width: 100%; height: 6px; background: #f1f5f9; border-radius: 3px; margin-bottom: 1rem; overflow: hidden;">
                                <div style="width: ${(compareProducts.length / 5) * 100}%; height: 100%; background: var(--accent); transition: width 0.3s ease;"></div>
                            </div>
                            ${compareProducts.length >= 2 ? `<a href="compare.php?ids=${compareProducts.map(p => p.id).join(',')}" class="btn btn-primary" style="width: 100%; justify-content: center;">Compare Now <i class="fas fa-arrow-right" style="font-size: 0.8rem;"></i></a>` : '<button class="btn btn-outline" style="width: 100%; justify-content: center; opacity: 0.7; cursor: not-allowed;" disabled>Select 2+ to Compare</button>'}
                         </div>`;
                compareList.innerHTML = html;
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

            // Trigger reflow
            void toast.offsetWidth;

            toast.classList.add('show');

            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 400);
            }, 3000);
        }

        function addToCart(productId) {
            fetch('add_to_cart.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ product_id: productId, quantity: 1 })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        showToast('Added to Cart', 'Product has been added to your cart.');
                    } else {
                        showToast('Error', data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('Error', 'Something went wrong. Please try again.', 'error');
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
                <a href="#">Home</a>
                <a href="#product-list">Products</a>
                <a href="../about_us.html">About Us</a>
                <a href="../messages.php">Contact Us</a>
            </div>
            <?php if (isset($first_name) && isset($last_name)): ?>
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

    <header class="dashboard-hero">
        <div class="container hero-content">
            <div class="hero-text">
                <h1>Welcome back, <?php echo htmlspecialchars($first_name); ?>!</h1>
                <p>Ready to build something amazing? Find the best tools and materials for your next project.</p>
                <div class="hero-search">
                    <form action="" method="GET">
                        <input type="text" name="search" placeholder="Search for materials..."
                            value="<?php echo htmlspecialchars($search_query); ?>">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i></button>
                    </form>
                </div>
            </div>
            <div class="hero-image">
                <img src="../images/index1.png" alt="Construction Site">
            </div>
        </div>
    </header>

    <script>
        function toggleFilter(header) {
            header.classList.toggle('collapsed');
            const content = header.nextElementSibling;
            content.classList.toggle('collapsed');
            if (content.style.maxHeight) {
                content.style.maxHeight = null;
            } else {
                content.style.maxHeight = content.scrollHeight + "px";
            }
        }

        // Initialize filters as open
        document.addEventListener('DOMContentLoaded', function () {
            const contents = document.querySelectorAll('.filter-content');
            contents.forEach(c => c.style.maxHeight = c.scrollHeight + "px");
        });
    </script>
    <div class="container dashboard-grid">
        <!-- Sidebar Filters -->
        <aside class="filter-sidebar">
            <div class="filter-card">
                <div class="filter-title filter-header" onclick="toggleFilter(this)">
                    Categories <i class="fas fa-chevron-down filter-toggle-icon"></i>
                </div>
                <div class="filter-content">
                    <ul class="filter-list">
                        <li>
                            <a href="?category=<?php echo !empty($search_query) ? '&search=' . urlencode($search_query) : ''; ?><?php echo !empty($sort_by) ? '&sort=' . urlencode($sort_by) : ''; ?>"
                                style="text-decoration: none; display: block;">
                                <label>
                                    <input type="radio" name="category" value="" <?php echo empty($category_filter) ? 'checked' : ''; ?> onclick="window.location.href='?category='">
                                    All Categories
                                </label>
                            </a>
                        </li>
                        <?php foreach ($categories as $category): ?>
                            <li>
                                <a href="?category=<?php echo htmlspecialchars($category['id']); ?><?php echo !empty($search_query) ? '&search=' . urlencode($search_query) : ''; ?><?php echo !empty($sort_by) ? '&sort=' . urlencode($sort_by) : ''; ?>"
                                    style="text-decoration: none; display: block;">
                                    <label>
                                        <input type="radio" name="category"
                                            value="<?php echo htmlspecialchars($category['id']); ?>" <?php echo ($category_filter == $category['id']) ? 'checked' : ''; ?>
                                            onclick="window.location.href=this.closest('a').href">
                                        <?php echo htmlspecialchars($category['category_name']); ?>
                                    </label>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

            <div class="filter-card">
                <div class="filter-title filter-header" onclick="toggleFilter(this)">
                    Price Range <i class="fas fa-chevron-down filter-toggle-icon"></i>
                </div>
                <div class="filter-content">
                    <form action="" method="GET">
                        <?php if (!empty($search_query))
                            echo '<input type="hidden" name="search" value="' . htmlspecialchars($search_query) . '">'; ?>
                        <?php if (!empty($category_filter))
                            echo '<input type="hidden" name="category" value="' . htmlspecialchars($category_filter) . '">'; ?>
                        <?php if (!empty($sort_by))
                            echo '<input type="hidden" name="sort" value="' . htmlspecialchars($sort_by) . '">'; ?>

                        <div class="price-slider-container" id="price-slider">
                            <div class="price-slider-track" id="slider-track"></div>
                            <div class="price-slider-handle" id="handle-min" style="left: 0%;"></div>
                            <div class="price-slider-handle" id="handle-max" style="left: 100%;"></div>
                        </div>

                        <div class="price-inputs">
                            <input type="number" name="min_price" id="input-min" placeholder="Min"
                                value="<?php echo $min_price; ?>" min="0">
                            <span>-</span>
                            <input type="number" name="max_price" id="input-max" placeholder="Max"
                                value="<?php echo $max_price; ?>" min="0">
                        </div>
                        <button type="submit" class="btn btn-outline"
                            style="width: 100%; font-size: 0.8rem; padding: 0.4rem;">Apply</button>
                    </form>
                    <script>
                        document.addEventListener('DOMContentLoaded', function () {
                            const slider = document.getElementById('price-slider');
                            const handleMin = document.getElementById('handle-min');
                            const handleMax = document.getElementById('handle-max');
                            const track = document.getElementById('slider-track');
                            const inputMin = document.getElementById('input-min');
                            const inputMax = document.getElementById('input-max');

                            // Set initial max value for slider calculation (e.g., 10000 or dynamic)
                            const maxLimit = 10000;

                            function updateSlider() {
                                let minVal = parseInt(inputMin.value) || 0;
                                let maxVal = parseInt(inputMax.value) || maxLimit;

                                if (minVal > maxVal) [minVal, maxVal] = [maxVal, minVal];
                                if (maxVal > maxLimit) maxVal = maxLimit;

                                const minPercent = (minVal / maxLimit) * 100;
                                const maxPercent = (maxVal / maxLimit) * 100;

                                handleMin.style.left = minPercent + '%';
                                handleMax.style.left = maxPercent + '%';
                                track.style.left = minPercent + '%';
                                track.style.width = (maxPercent - minPercent) + '%';
                            }

                            // Initialize
                            updateSlider();

                            // Drag logic (simplified for brevity, can be enhanced)
                            function onDrag(e, handle, isMin) {
                                e.preventDefault();
                                const rect = slider.getBoundingClientRect();

                                function move(e) {
                                    let clientX = e.clientX || e.touches[0].clientX;
                                    let percent = ((clientX - rect.left) / rect.width) * 100;
                                    percent = Math.max(0, Math.min(100, percent));

                                    let val = Math.round((percent / 100) * maxLimit);

                                    if (isMin) {
                                        inputMin.value = val;
                                    } else {
                                        inputMax.value = val;
                                    }
                                    updateSlider();
                                }

                                function stop() {
                                    document.removeEventListener('mousemove', move);
                                    document.removeEventListener('mouseup', stop);
                                    document.removeEventListener('touchmove', move);
                                    document.removeEventListener('touchend', stop);
                                }

                                document.addEventListener('mousemove', move);
                                document.addEventListener('mouseup', stop);
                                document.addEventListener('touchmove', move);
                                document.addEventListener('touchend', stop);
                            }

                            handleMin.addEventListener('mousedown', (e) => onDrag(e, handleMin, true));
                            handleMin.addEventListener('touchstart', (e) => onDrag(e, handleMin, true));
                            handleMax.addEventListener('mousedown', (e) => onDrag(e, handleMax, false));
                            handleMax.addEventListener('touchstart', (e) => onDrag(e, handleMax, false));

                            inputMin.addEventListener('input', updateSlider);
                            inputMax.addEventListener('input', updateSlider);
                        });
                    </script>
                </div>
            </div>

            <div class="filter-card">
                <div class="filter-title filter-header" onclick="toggleFilter(this)">
                    Availability <i class="fas fa-chevron-down filter-toggle-icon"></i>
                </div>
                <div class="filter-content">
                    <ul class="filter-list">
                        <li>
                            <a href="?availability=in_stock<?php echo !empty($search_query) ? '&search=' . urlencode($search_query) : ''; ?><?php echo !empty($category_filter) ? '&category=' . urlencode($category_filter) : ''; ?>"
                                style="text-decoration: none; display: block;">
                                <label>
                                    <input type="checkbox" name="availability" value="in_stock" <?php echo ($availability_filter == 'in_stock') ? 'checked' : ''; ?>
                                        onclick="window.location.href=this.checked ? this.closest('a').href : window.location.href.replace('availability=in_stock', '')">
                                    In Stock
                                </label>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="filter-card">
                <div class="filter-title filter-header" onclick="toggleFilter(this)">
                    Brand <i class="fas fa-chevron-down filter-toggle-icon"></i>
                </div>
                <div class="filter-content">
                    <div class="checkbox-list">
                        <?php foreach ($brands as $brand_item): ?>
                            <div class="checkbox-item">
                                <label style="width: 100%; cursor: pointer;">
                                    <input type="checkbox" name="brands[]"
                                        value="<?php echo htmlspecialchars($brand_item['brand']); ?>" <?php echo (in_array($brand_item['brand'], $brand_filters)) ? 'checked' : ''; ?>
                                        onchange="updateBrandFilter(this)">
                                    <?php echo htmlspecialchars($brand_item['brand']); ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <script>
                        function updateBrandFilter(checkbox) {
                            const url = new URL(window.location.href);
                            const params = new URLSearchParams(url.search);
                            let brands = params.getAll('brands[]');

                            if (checkbox.checked) {
                                brands.push(checkbox.value);
                            } else {
                                brands = brands.filter(b => b !== checkbox.value);
                            }

                            params.delete('brands[]');
                            brands.forEach(b => params.append('brands[]', b));

                            window.location.href = '?' + params.toString();
                        }
                    </script>
                </div>
            </div>

            <div class="filter-card">
                <div class="filter-title filter-header" onclick="toggleFilter(this)">
                    Rating <i class="fas fa-chevron-down filter-toggle-icon"></i>
                </div>
                <div class="filter-content">
                    <ul class="filter-list">
                        <?php for ($i = 4; $i >= 1; $i--): ?>
                            <li>
                                <a href="?rating=<?php echo $i; ?><?php echo !empty($search_query) ? '&search=' . urlencode($search_query) : ''; ?><?php echo !empty($category_filter) ? '&category=' . urlencode($category_filter) : ''; ?>"
                                    style="text-decoration: none; display: block;">
                                    <label>
                                        <input type="radio" name="rating" value="<?php echo $i; ?>" <?php echo ($min_rating == $i) ? 'checked' : ''; ?>
                                            onclick="window.location.href=this.closest('a').href">
                                        <?php echo $i; ?> Stars & Up
                                    </label>
                                </a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </div>
            </div>
        </aside>

        <!-- Product List -->
        <main id="product-list">
            <div class="products-header">
                <h2 style="font-size: 1.25rem; color: var(--primary); font-weight: 600;">Available Products <span
                        style="color: var(--text-light); font-size: 0.9rem; font-weight: 400; margin-left: 0.5rem;">(<?php echo count($products); ?>)</span>
                </h2>
                <form action="" method="GET" style="margin: 0;">
                    <?php if (!empty($search_query))
                        echo '<input type="hidden" name="search" value="' . htmlspecialchars($search_query) . '">'; ?>
                    <?php if (!empty($category_filter))
                        echo '<input type="hidden" name="category" value="' . htmlspecialchars($category_filter) . '">'; ?>
                    <?php if ($min_rating > 0)
                        echo '<input type="hidden" name="rating" value="' . $min_rating . '">'; ?>
                    <?php if ($min_price !== null)
                        echo '<input type="hidden" name="min_price" value="' . $min_price . '">'; ?>
                    <?php if ($max_price !== null)
                        echo '<input type="hidden" name="max_price" value="' . $max_price . '">'; ?>

                    <select name="sort" class="sort-dropdown" onchange="this.form.submit()">
                        <option value="" <?php echo empty($sort_by) ? 'selected' : ''; ?>>Sort by: Default</option>
                        <option value="price_low" <?php echo ($sort_by == 'price_low') ? 'selected' : ''; ?>>Price: Low to
                            High</option>
                        <option value="price_high" <?php echo ($sort_by == 'price_high') ? 'selected' : ''; ?>>Price: High
                            to Low</option>
                        <option value="rating" <?php echo ($sort_by == 'rating') ? 'selected' : ''; ?>>Rating: High to Low
                        </option>
                    </select>
                </form>
            </div>

            <div class="product-grid">
                <?php if (count($products) > 0): ?>
                    <?php foreach ($products as $product): ?>
                        <?php
                        $imgUrl = $product['image_url'];
                        if (!filter_var($imgUrl, FILTER_VALIDATE_URL)) {
                            $imgUrl = str_replace(['uploaded_images/', 'admin/'], '', $imgUrl);
                            $imgUrl = STORAGE_URL . $imgUrl;
                        }
                        ?>
                        <div class="product-card">
                            <a href="product_detail.php?id=<?php echo htmlspecialchars($product['id']); ?>"
                                style="text-decoration: none; color: inherit;">
                                <img src="<?php echo htmlspecialchars($imgUrl); ?>"
                                    alt="<?php echo htmlspecialchars($product['product_name']); ?>" class="product-img">
                            </a>
                            <div class="product-info">
                                <a href="product_detail.php?id=<?php echo htmlspecialchars($product['id']); ?>"
                                    style="text-decoration: none; color: inherit;">
                                    <div class="product-name"><?php echo htmlspecialchars($product['product_name']); ?></div>
                                </a>
                                <div class="product-price">₹<?php echo htmlspecialchars($product['price']); ?></div>
                                <p class="product-desc"
                                    style="font-size: 0.85rem; color: var(--text-light); margin-bottom: 0.75rem; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; line-height: 1.6; height: 3.2em;">
                                    <?php echo htmlspecialchars($product['description']); ?>
                                </p>
                                <?php if (isset($product['rating']) && $product['rating'] > 0): ?>
                                    <div class="product-rating">
                                        <?php for ($i = 0; $i < 5; $i++) {
                                            echo $i < round($product['rating']) ? '★' : '☆';
                                        } ?>
                                        <span
                                            style="color: var(--text-light); margin-left: 5px;">(<?php echo number_format($product['rating'], 1); ?>)</span>
                                    </div>
                                <?php endif; ?>
                                <div class="product-actions">
                                    <button class="btn btn-outline"
                                        onclick="addToCart(<?php echo htmlspecialchars($product['id']); ?>)"><i
                                            class="fas fa-shopping-cart"></i> Add</button>
                                    <button class="btn btn-primary"
                                        onclick="addToCompare(<?php echo htmlspecialchars($product['id']); ?>, '<?php echo addslashes(htmlspecialchars($product['product_name'])); ?>', '<?php echo htmlspecialchars($product['price']); ?>', '<?php echo htmlspecialchars($imgUrl); ?>', '<?php echo isset($product['rating']) ? number_format($product['rating'], 1) : '0'; ?>', <?php echo htmlspecialchars($product['category_id']); ?>)">Compare</button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="grid-column: 1 / -1; text-align: center; padding: 3rem; color: var(--text-light);">
                        <i class="fas fa-search" style="font-size: 2rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                        <p>No products found matching your criteria.</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>

        <!-- Compare Sidebar -->
        <aside class="compare-sidebar">
            <div id="compare-list">
                <div style="text-align: center; padding: 2rem 0; color: #94a3b8;">
                    <div
                        style="background: #f1f5f9; width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem;">
                        <i class="fas fa-balance-scale" style="font-size: 1.5rem; color: #cbd5e1;"></i>
                    </div>
                    <p style="font-size: 0.9rem; font-weight: 500;">Ready to Compare?</p>
                    <p style="font-size: 0.8rem; opacity: 0.7;">Select products to see differences</p>
                </div>
            </div>
        </aside>
    </div>

    <footer>
        <div class="container">
            <div class="copyright">
                <p>&copy; <?php echo date("Y"); ?> Constructo. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <div id="toast-container" class="toast-container"></div>
</body>

</html>