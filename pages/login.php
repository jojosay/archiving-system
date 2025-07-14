<?php
// Login page doesn't need the full layout since it's before authentication
// Include config to get branding constants
require_once __DIR__ . '/../config/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo defined('BRAND_APP_NAME') ? BRAND_APP_NAME : APP_NAME; ?> - Login</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: <?php echo defined('BRAND_FONT_FAMILY') ? BRAND_FONT_FAMILY : "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif"; ?>;
            background: linear-gradient(135deg, <?php echo defined('BRAND_PRIMARY_COLOR') ? BRAND_PRIMARY_COLOR : '#2C3E50'; ?> 0%, <?php echo defined('BRAND_SIDEBAR_COLOR') ? BRAND_SIDEBAR_COLOR : '#34495E'; ?> 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            line-height: 1.6;
        }
        .login-container {
            background: white;
            padding: 3rem;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 450px;
            position: relative;
            overflow: hidden;
        }
        
        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, <?php echo defined('BRAND_SECONDARY_COLOR') ? BRAND_SECONDARY_COLOR : '#F39C12'; ?>, <?php echo defined('BRAND_ACCENT_COLOR') ? BRAND_ACCENT_COLOR : '#E67E22'; ?>);
        }
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .login-header h1 {
            color: #2C3E50;
            margin-bottom: 0.5rem;
            font-size: 1.8rem;
            font-weight: 600;
        }
        
        .login-header p {
            color: #7F8C8D;
            font-size: 0.95rem;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #2C3E50;
            font-weight: 500;
        }
        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #BDC3C7;
            border-radius: 4px;
            font-size: 1rem;
            box-sizing: border-box;
        }
        .form-group input:focus {
            outline: none;
            border-color: #3498DB;
        }
        .login-btn {
            width: 100%;
            background: <?php echo defined('BRAND_SECONDARY_COLOR') ? BRAND_SECONDARY_COLOR : '#F39C12'; ?>;
            color: white;
            padding: 0.75rem;
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            cursor: pointer;
            margin-top: 1rem;
        }
        .login-btn:hover {
            background: <?php echo defined('BRAND_ACCENT_COLOR') ? BRAND_ACCENT_COLOR : '#E67E22'; ?>;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        .back-link {
            text-align: center;
            margin-top: 1.5rem;
        }
        .back-link a {
            color: #7F8C8D;
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }
        .back-link a:hover {
            color: #3498DB;
        }
        
        @media (max-width: 480px) {
            .login-container {
                margin: 1rem;
                padding: 2rem;
            }
        }
        .default-credentials {
            background: #F8F9FA;
            padding: 1rem;
            border-radius: 4px;
            margin-top: 1rem;
            font-size: 0.9rem;
        }
        .default-credentials strong {
            color: #E74C3C;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1><?php echo defined('BRAND_APP_NAME') ? BRAND_APP_NAME : APP_NAME; ?></h1>
            <p>Please sign in to continue</p>
        </div>

        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>

            <button type="submit" class="login-btn">Sign In</button>
        </form>

        <div class="default-credentials">
            <strong>Default Login: Set Up When Installed Initially</strong><br>
            Username: ----<br>
            Password: ----

        <div class="back-link">
            <a href="index.php">← Back to Dashboard</a>
        </div>
    </div>

    <?php
    // Handle login form submission
    $error_message = '';
    $success_message = '';
    
    if ($_POST) {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            $error_message = 'Please enter both username and password.';
        } else {
            // Include required files
            require_once 'includes/database.php';
            require_once 'includes/auth.php';
            
            // Attempt login
            $database = new Database();
            $auth = new Auth($database);
            
            if ($auth->login($username, $password)) {
                $success_message = 'Login successful! Redirecting...';
                echo "<script>setTimeout(function(){ window.location.href = 'index.php'; }, 1500);</script>";
            } else {
                $error_message = 'Invalid username or password.';
            }
        }
    }
    
    // Display messages
    if ($error_message) {
        echo "<script>alert('Error: $error_message');</script>";
    }
    if ($success_message) {
        echo "<script>alert('$success_message');</script>";
    }
    ?>
</body>
</html>