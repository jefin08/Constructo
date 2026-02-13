<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Constructo - Premium Construction Materials</title>
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

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg-body);
            color: var(--text-main);
            line-height: 1.6;
            overflow-x: hidden;
        }

        /* Utilities */
        .container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 1.5rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem 1.5rem;
            border-radius: var(--radius-md);
            font-weight: 600;
            text-decoration: none;
            transition: var(--transition);
            cursor: pointer;
            border: none;
            gap: 0.5rem;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-light);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .btn-accent {
            background: var(--accent);
            color: white;
        }

        .btn-accent:hover {
            background: var(--accent-hover);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .section-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary);
            text-align: center;
            margin-bottom: 3rem;
            position: relative;
        }

        .section-title::after {
            content: '';
            display: block;
            width: 60px;
            height: 4px;
            background: var(--accent);
            margin: 1rem auto 0;
            border-radius: 2px;
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
            padding: 1rem 0;
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

        .auth-buttons {
            display: flex;
            gap: 1rem;
        }

        /* Hero */
        .hero {
            padding: 8rem 0 5rem;
            min-height: 90vh;
            display: flex;
            align-items: center;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: -10%;
            right: -5%;
            width: 50%;
            height: 80%;
            background: radial-gradient(circle, rgba(245, 158, 11, 0.1) 0%, transparent 70%);
            z-index: 0;
        }

        .hero-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            align-items: center;
            position: relative;
            z-index: 1;
        }

        .hero-text h1 {
            font-size: 3.5rem;
            line-height: 1.1;
            margin-bottom: 1.5rem;
            color: var(--primary);
        }

        .hero-text p {
            font-size: 1.25rem;
            color: var(--text-light);
            margin-bottom: 2.5rem;
            max-width: 500px;
        }

        .hero-image img {
            width: 100%;
            height: auto;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            transform: perspective(1000px) rotateY(-5deg);
            transition: var(--transition);
        }

        .hero-image:hover img {
            transform: perspective(1000px) rotateY(0deg);
        }

        /* Sections */
        .section {
            padding: 5rem 0;
        }

        /* About */
        .about-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            align-items: center;
        }

        .about-text p {
            margin-bottom: 1.5rem;
            font-size: 1.1rem;
            color: var(--text-light);
        }

        .about-img img {
            width: 60%;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            display: block;
            margin: 0 auto;
        }

        /* Categories & Vendors (Scroll/Grid) */
        .scroll-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            padding: 1rem;
        }

        .card {
            background: var(--bg-card);
            border-radius: var(--radius-md);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
            text-align: center;
            padding-bottom: 1rem;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .card img {
            width: 100%;
            height: 160px;
            object-fit: cover;
        }

        #vendor .card img {
            object-fit: contain;
            padding: 10px;
        }

        .card-title {
            font-weight: 600;
            margin-top: 1rem;
            color: var(--primary);
        }

        /* Products */
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
        }

        .product-card {
            background: var(--bg-card);
            border-radius: var(--radius-md);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
            display: flex;
            flex-direction: column;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .product-img {
            width: 100%;
            height: 120px;
            object-fit: contain;
            padding: 8px;
            background: #fff;
        }

        .product-info {
            padding: 0.75rem;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .product-name {
            font-size: 1rem;
            font-weight: 700;
            margin-bottom: 0.2rem;
            color: var(--primary);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .product-desc {
            font-size: 0.8rem;
            color: var(--text-light);
            margin-bottom: 0.4rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            line-height: 1.3;
            height: 2.6em;
        }

        .product-rating {
            color: #fbbf24;
            font-size: 0.75rem;
            margin-bottom: 0.25rem;
        }

        .product-price {
            font-size: 1.1rem;
            color: var(--accent);
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .product-info .btn {
            margin-top: auto;
            width: 100%;
            padding: 0.5rem;
        }

        /* Footer */
        footer {
            background: var(--primary);
            color: white;
            padding: 4rem 0 2rem;
            margin-top: 4rem;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 3rem;
            margin-bottom: 3rem;
        }

        .footer-col h3 {
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            color: var(--accent);
        }

        .footer-links a {
            display: block;
            color: #94a3b8;
            text-decoration: none;
            margin-bottom: 0.75rem;
            transition: var(--transition);
        }

        .footer-links a:hover {
            color: white;
            padding-left: 5px;
        }

        .copyright {
            text-align: center;
            padding-top: 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            color: #64748b;
        }

        /* Mobile */
        @media (max-width: 768px) {

            .hero-content,
            .about-grid {
                grid-template-columns: 1fr;
                text-align: center;
            }

            .hero-text h1 {
                font-size: 2.5rem;
            }

            .hero-text p {
                margin: 0 auto 2rem;
            }

            .nav-links {
                display: none;
            }

            /* Simplified for this task, ideally a hamburger menu */
            .hero-image img {
                transform: none;
            }
        }
    </style>
</head>

<body>

    <nav>
        <div class="container nav-container">
            <div class="logo">
                <img src="images/logo.png" alt="Constructo Logo">
                Constructo
            </div>
            <div class="nav-links">
                <a href="#">Home</a>
                <a href="about_us.html">About Us</a>
                <a href="#category">Categories</a>
                <a href="#products">Products</a>
                <a href="messages.php">Contact Us</a>
            </div>
            <div class="auth-buttons">
                <a href="login.php" class="btn btn-primary">Login</a>
                <a href="signup.php" class="btn btn-accent">Sign Up</a>
            </div>
        </div>
    </nav>

    <header class="hero">
        <div class="container hero-content">
            <div class="hero-text">
                <h1>Build Your Dreams with Constructo</h1>
                <p>Your premium online platform for all construction materials. Quality, reliability, and efficiency
                    delivered directly to your site.</p>
                <div class="hero-actions">
                    <a href="signup.php" class="btn btn-accent"><i class="fas fa-arrow-right"></i> Get Started</a>
                </div>
            </div>
            <div class="hero-image">
                <img src="images/index1.png" alt="Construction Site">
            </div>
        </div>
    </header>

    <main>
        <!-- About Us Section -->
        <section class="section" id="about-us">
            <div class="container">
                <h2 class="section-title">Who We Are</h2>
                <div class="about-grid">
                    <div class="about-text">
                        <p>At Constructo, we connect you with the best online resources for all your construction
                            material needs. Whether you're a contractor, architect, or DIY enthusiast, our platform
                            provides a comprehensive selection of materials at competitive prices.</p>
                        <p>Our user-friendly interface allows you to search for materials quickly, compare prices, and
                            read customer reviews. With partnerships with leading suppliers, we ensure that you receive
                            high-quality products delivered directly to your site.</p>
                        <a href="about_us.html" class="btn btn-primary">Learn More</a>
                    </div>
                    <div class="about-img">
                        <img src="images/index5.jpeg" alt="Construction Materials">
                    </div>
                </div>
            </div>
        </section>

        <!-- Category Section -->
        <section class="section" id="category" style="background-color: #f1f5f9;">
            <div class="container">
                <h2 class="section-title">Explore Categories</h2>
                <div class="scroll-container">
                    <?php
                    include 'db_connect.php';
                    try {
                        $sql = "SELECT category_name, image_url FROM categories";
                        $stmt = $conn->query($sql);
                        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

                        if (count($categories) > 0) {
                            foreach ($categories as $row) {
                                $imgUrl = $row["image_url"];
                                if (!filter_var($imgUrl, FILTER_VALIDATE_URL)) {
                                    $imgUrl = ltrim($imgUrl, '/');
                                    $imgUrl = STORAGE_URL . $imgUrl;
                                }
                                echo '<div class="card">
                                            <img src="' . htmlspecialchars($imgUrl) . '" alt="' . htmlspecialchars($row["category_name"]) . '">
                                            <div class="card-title">' . htmlspecialchars($row["category_name"]) . '</div>
                                          </div>';
                            }
                        } else {
                            echo "<div class='card'>No categories found</div>";
                        }
                    } catch (PDOException $e) {
                        echo "Error loading categories.";
                    }
                    ?>
                </div>
            </div>
        </section>

        <!-- Product Section -->
        <section class="section" id="products">
            <div class="container">
                <h2 class="section-title">Featured Products</h2>
                <div class="product-grid">
                    <?php
                    try {
                        $sql = "SELECT product_name, description, image_url, price, rating FROM products ORDER BY RANDOM() LIMIT 15";
                        $stmt = $conn->query($sql);
                        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

                        if (count($products) > 0) {
                            foreach ($products as $row) {
                                $imgUrl = $row["image_url"];
                                if (!filter_var($imgUrl, FILTER_VALIDATE_URL)) {
                                    $imgUrl = str_replace(['uploaded_images/', 'admin/'], '', $imgUrl);
                                    $imgUrl = STORAGE_URL . $imgUrl;
                                }

                                $rating = isset($row['rating']) ? (float) $row['rating'] : 0;
                                $stars = '';
                                for ($i = 0; $i < 5; $i++) {
                                    $stars .= ($i < round($rating)) ? '★' : '☆';
                                }

                                echo '<div class="product-card">
                                            <img src="' . htmlspecialchars($imgUrl) . '" alt="' . htmlspecialchars($row["product_name"]) . '" class="product-img">
                                            <div class="product-info">
                                                <div class="product-name">' . htmlspecialchars($row["product_name"]) . '</div>
                                                <div class="product-desc">' . htmlspecialchars($row["description"]) . '</div>
                                                <div class="product-rating">' . $stars . ' <span style="color: var(--text-light); font-size: 0.8rem;">(' . number_format($rating, 1) . ')</span></div>
                                                <div class="product-price">₹' . htmlspecialchars($row["price"]) . '</div>
                                                <a href="login.php" class="btn btn-primary">View Details</a>
                                            </div>
                                          </div>';
                            }
                        } else {
                            echo "<div class='product-card'>No products found</div>";
                        }
                    } catch (PDOException $e) {
                        echo "Error loading products.";
                    }
                    ?>
                </div>
                <div style="text-align: center; margin-top: 3rem;">
                    <a href="login.php" class="btn btn-accent">See More Products</a>
                </div>
            </div>
        </section>

        <!-- Vendor Section -->
        <section class="section" id="vendor" style="background-color: #f1f5f9;">
            <div class="container">
                <h2 class="section-title">Our Trusted Vendors</h2>
                <div class="scroll-container">
                    <?php
                    try {
                        $sql = "SELECT vendor_name, vendor_image FROM vendors";
                        $stmt = $conn->query($sql);
                        $vendors = $stmt->fetchAll(PDO::FETCH_ASSOC);

                        if (count($vendors) > 0) {
                            foreach ($vendors as $row) {
                                $imgUrl = $row["vendor_image"];
                                $localPath = 'images/' . $imgUrl;

                                if (file_exists($localPath)) {
                                    $imgUrl = $localPath;
                                } elseif (!filter_var($imgUrl, FILTER_VALIDATE_URL)) {
                                    $imgUrl = str_replace(['uploaded_images/', 'admin/'], '', $imgUrl);
                                    $imgUrl = STORAGE_URL . $imgUrl;
                                }

                                echo '<div class="card">
                                            <img src="' . htmlspecialchars($imgUrl) . '" alt="' . htmlspecialchars($row["vendor_name"]) . '">
                                            <div class="card-title">' . htmlspecialchars($row["vendor_name"]) . '</div>
                                          </div>';
                            }
                        } else {
                            echo "<div class='card'>No vendors found</div>";
                        }
                    } catch (PDOException $e) {
                        echo "Error loading vendors.";
                    }
                    ?>
                </div>
            </div>
        </section>
    </main>

    <footer id="contact-us">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <h3>Constructo</h3>
                    <p style="color: #94a3b8;">Your one-stop platform for all construction materials. Quality and
                        reliability you can trust.</p>
                </div>
                <div class="footer-col">
                    <h3>Quick Links</h3>
                    <div class="footer-links">
                        <a href="#">Home</a>
                        <a href="about_us.html">About Us</a>
                        <a href="#category">Categories</a>
                        <a href="contact_us.html">Contact Us</a>
                    </div>
                </div>
                <div class="footer-col">
                    <h3>Contact Info</h3>
                    <div class="footer-links">
                        <a href="#"><i class="fas fa-envelope"></i> info@constructo.com</a>
                        <a href="#"><i class="fas fa-phone"></i> (123) 456-7890</a>
                        <a href="#"><i class="fas fa-map-marker-alt"></i> 123 Construction Ave, City</a>
                    </div>
                </div>
            </div>
            <div class="copyright">
                <p>&copy; <?php echo date("Y"); ?> Constructo. All rights reserved.</p>
            </div>
        </div>
    </footer>

</body>

</html>