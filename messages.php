<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Constructo</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Modern CSS Reset & Variables (Matching index.php) */
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
            width: 100%;
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

        /* Header */
        .page-header {
            padding: 8rem 0 4rem;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            text-align: center;
        }

        .page-header h1 {
            font-size: 3rem;
            color: var(--primary);
            margin-bottom: 1rem;
        }

        .page-header p {
            font-size: 1.25rem;
            color: var(--text-light);
            max-width: 600px;
            margin: 0 auto;
        }

        /* Contact Section */
        .contact-section {
            padding: 4rem 0;
        }

        .contact-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
        }

        .contact-card {
            background: var(--bg-card);
            padding: 2.5rem;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .contact-card h2 {
            font-size: 1.75rem;
            color: var(--primary);
            margin-bottom: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-main);
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #cbd5e1;
            border-radius: var(--radius-md);
            font-family: inherit;
            font-size: 1rem;
            transition: var(--transition);
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.1);
        }

        .info-item {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            align-items: flex-start;
        }

        .info-icon {
            width: 48px;
            height: 48px;
            background: rgba(245, 158, 11, 0.1);
            color: var(--accent);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            flex-shrink: 0;
        }

        .info-content h3 {
            font-size: 1.1rem;
            color: var(--primary);
            margin-bottom: 0.25rem;
        }

        .info-content p {
            color: var(--text-light);
        }

        .alert {
            padding: 1rem;
            border-radius: var(--radius-md);
            margin-bottom: 2rem;
            font-weight: 500;
            text-align: center;
        }

        .alert-success {
            background-color: #dcfce7;
            color: #166534;
        }

        .alert-error {
            background-color: #fee2e2;
            color: #991b1b;
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

        @media (max-width: 768px) {
            .contact-grid {
                grid-template-columns: 1fr;
            }

            .nav-links {
                display: none;
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
                <a href="index.php">Home</a>
                <a href="about_us.html">About Us</a>
                <a href="index.php#category">Categories</a>
                <a href="messages.php" style="color: var(--accent);">Contact Us</a>
            </div>
            <div class="auth-buttons">
                <a href="login.php" class="btn btn-primary" style="width: auto; padding: 0.5rem 1.25rem;">Login</a>
            </div>
        </div>
    </nav>

    <header class="page-header">
        <div class="container">
            <h1>Get in Touch</h1>
            <p>We'd love to hear from you. Send us a message and we'll respond as soon as possible.</p>
        </div>
    </header>

    <main class="contact-section">
        <div class="container">
            <div class="contact-grid">
                <!-- Contact Form -->
                <div class="contact-card">
                    <h2>Send a Message</h2>
                    <?php
                    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                        include 'db_connect.php';
                        try {
                            $name = $_POST['name'];
                            $email = $_POST['email'];
                            $message = $_POST['message'];

                            $sql = "INSERT INTO contact_messages (name, email, message) VALUES (:name, :email, :message)";
                            $stmt = $conn->prepare($sql);
                            $stmt->bindParam(':name', $name);
                            $stmt->bindParam(':email', $email);
                            $stmt->bindParam(':message', $message);

                            if ($stmt->execute()) {
                                echo "<div class='alert alert-success'><i class='fas fa-check-circle'></i> Message sent successfully!</div>";
                            } else {
                                echo "<div class='alert alert-error'><i class='fas fa-exclamation-circle'></i> Error sending message.</div>";
                            }
                        } catch (PDOException $e) {
                            echo "<div class='alert alert-error'>Error: " . $e->getMessage() . "</div>";
                        }
                    }
                    ?>
                    <form action="" method="POST">
                        <div class="form-group">
                            <label for="name">Full Name</label>
                            <input type="text" id="name" name="name" placeholder="John Doe" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" placeholder="john@example.com" required>
                        </div>
                        <div class="form-group">
                            <label for="message">Message</label>
                            <textarea id="message" name="message" rows="5" placeholder="How can we help you?"
                                required></textarea>
                        </div>
                        <button type="submit" class="btn btn-accent">Send Message</button>
                    </form>
                </div>

                <!-- Contact Info -->
                <div class="contact-info">
                    <div class="contact-card" style="height: 100%;">
                        <h2>Contact Information</h2>
                        <div class="info-item">
                            <div class="info-icon"><i class="fas fa-envelope"></i></div>
                            <div class="info-content">
                                <h3>Email Us</h3>
                                <p>info@constructo.com</p>
                                <p>support@constructo.com</p>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-icon"><i class="fas fa-phone"></i></div>
                            <div class="info-content">
                                <h3>Call Us</h3>
                                <p>+1 (234) 567-8901</p>
                                <p>Mon-Fri, 9am - 6pm</p>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-icon"><i class="fas fa-map-marker-alt"></i></div>
                            <div class="info-content">
                                <h3>Visit Us</h3>
                                <p>123 Construction Ave</p>
                                <p>City, State, ZIP</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
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