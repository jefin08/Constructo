<?php
session_start();

// Database connection settings
include 'db_connect.php';

// Initialize variables
$message = "";
$email = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect form data
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email format.";
    } else {
        try {
            // Prepare statement to check credentials in both tables
            $sql = "SELECT password, id, 'admin' AS user_type FROM admin WHERE email = :email 
                    UNION ALL 
                    SELECT password, id, 'client' AS user_type FROM clients WHERE email = :email2 AND status = 'active'";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':email2', $email);
            $stmt->execute();

            // Check if the user exists in either table
            if ($stmt->rowCount() > 0) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                $password_hashed = $row['password'];
                $user_id = $row['id'];
                $user_type = $row['user_type'];

                // Verify the password
                if (password_verify($password, $password_hashed)) {
                    $_SESSION['email'] = $email; // Store email in session
                    if ($user_type === 'admin') {
                        header("Location: admin/dashboard.php"); // Redirect to admin page
                    } else {
                        $_SESSION['user_id'] = $user_id; // Store client ID in session
                        header("Location: user/index.php"); // Redirect to client page
                    }
                    exit();
                } else {
                    $message = "Incorrect password. Please try again.";
                }
            } else {
                // If the user does not exist or is inactive
                $inactive_check = $conn->prepare("SELECT status FROM clients WHERE email = :email");
                $inactive_check->bindParam(':email', $email);
                $inactive_check->execute();

                if ($inactive_check->rowCount() > 0) {
                    // Check the status of the client
                    $row = $inactive_check->fetch(PDO::FETCH_ASSOC);
                    $status = $row['status'];

                    if ($status !== 'active') {
                        $message = "Your account is inactive. Please contact support.";
                    } else {
                        $message = "Invalid login credentials.";
                    }
                } else {
                    $message = "No account found with this email.";
                }
            }
        } catch (PDOException $e) {
            $message = "Error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Constructo</title>
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

        /* Login Section */
        .login-wrapper {
            flex-grow: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 8rem 1.5rem 4rem;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        }

        .login-card {
            background: var(--bg-card);
            padding: 3rem;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            width: 100%;
            max-width: 450px;
            text-align: center;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .login-card h2 {
            font-size: 2rem;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .login-card .welcome-message {
            color: var(--text-light);
            margin-bottom: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
            text-align: left;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-main);
        }

        .form-group input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #cbd5e1;
            border-radius: var(--radius-md);
            font-family: inherit;
            font-size: 1rem;
            transition: var(--transition);
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.1);
        }

        .message {
            margin-top: 1.5rem;
            padding: 0.75rem;
            border-radius: var(--radius-md);
            background-color: #fee2e2;
            color: #ef4444;
            font-size: 0.9rem;
            text-align: center;
        }

        .auth-footer {
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e2e8f0;
            font-size: 0.95rem;
            color: var(--text-light);
        }

        .auth-footer a {
            color: var(--accent);
            text-decoration: none;
            font-weight: 600;
        }

        .auth-footer a:hover {
            text-decoration: underline;
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
            .nav-links {
                display: none;
            }

            .login-card {
                padding: 2rem;
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
                <a href="messages.php">Contact Us</a>
            </div>
            <div class="auth-buttons">
                <a href="signup.php" class="btn btn-accent" style="width: auto; padding: 0.5rem 1.25rem;">Sign Up</a>
            </div>
        </div>
    </nav>

    <div class="login-wrapper">
        <div class="login-card">
            <h2>Welcome Back</h2>
            <p class="welcome-message">Please enter your details to sign in.</p>

            <form action="" method="post">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>"
                        placeholder="name@example.com" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="••••••••" required>
                </div>

                <button type="submit" class="btn btn-primary">Sign In</button>
            </form>

            <?php if (!empty($message)): ?>
                <div class="message">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <div class="auth-footer">
                <p>Don't have an account? <a href="signup.php">Create account</a></p>
            </div>
        </div>
    </div>

    <footer>
        <div class="container">
            <div class="copyright">
                <p>&copy; <?php echo date("Y"); ?> Constructo. All rights reserved.</p>
            </div>
        </div>
    </footer>

</body>

</html>