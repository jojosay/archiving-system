<?php
// Check if user is admin
if (!$auth->hasRole('admin')) {
    header('Location: index.php?page=dashboard');
    exit;
}

require_once 'includes/layout.php';

// Handle form submission
$message = '';
$message_type = '';

if ($_POST) {
    require_once 'includes/user_manager.php';
    $userManager = new UserManager($database);
    
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $role = $_POST['role'] ?? 'staff';
    
    // Validation
    if (empty($username) || empty($email) || empty($password) || empty($first_name) || empty($last_name)) {
        $message = 'All fields are required.';
        $message_type = 'error';
    } elseif ($password !== $confirm_password) {
        $message = 'Passwords do not match.';
        $message_type = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Invalid email address.';
        $message_type = 'error';
    } else {
        $result = $userManager->createUser($username, $email, $password, $first_name, $last_name, $role);
        $message = $result['message'];
        $message_type = $result['success'] ? 'success' : 'error';
    }
}

renderPageStart('Register User', 'user_register');
?>

<div class="page-header">
    <h1>Register New User</h1>
    <p>Create a new user account for the system</p>
</div>

<style>
    .form-card {
        background: white;
        padding: 2rem;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        max-width: 600px;
        margin: 0 auto;
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
    .form-group input, .form-group select {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid #BDC3C7;
        border-radius: 4px;
        font-size: 1rem;
        box-sizing: border-box;
    }
    .form-group input:focus, .form-group select:focus {
        outline: none;
        border-color: #3498DB;
    }
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
    }
    .submit-btn {
        width: 100%;
        background: #F39C12;
        color: white;
        padding: 0.75rem;
        border: none;
        border-radius: 4px;
        font-size: 1rem;
        cursor: pointer;
        margin-top: 1rem;
    }
    .submit-btn:hover {
        background: #E67E22;
    }
    .message {
        padding: 1rem;
        border-radius: 4px;
        margin-bottom: 1rem;
    }
    .message.success {
        background: #D5EDDA;
        color: #155724;
        border: 1px solid #C3E6CB;
    }
    .message.error {
        background: #F8D7DA;
        color: #721C24;
        border: 1px solid #F5C6CB;
    }
    .back-link {
        text-align: center;
        margin-top: 1rem;
    }
    .back-link a {
        color: #3498DB;
        text-decoration: none;
    }
</style>

<div class="form-card">
    <?php if ($message): ?>
        <div class="message <?php echo $message_type; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-row">
            <div class="form-group">
                <label for="first_name">First Name</label>
                <input type="text" id="first_name" name="first_name" required value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="last_name">Last Name</label>
                <input type="text" id="last_name" name="last_name" required value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>">
            </div>
        </div>

        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" required value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
        </div>

        <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
        </div>

        <div class="form-group">
            <label for="role">Role</label>
            <select id="role" name="role" required>
                <option value="staff" <?php echo ($_POST['role'] ?? '') === 'staff' ? 'selected' : ''; ?>>Staff</option>
                <option value="admin" <?php echo ($_POST['role'] ?? '') === 'admin' ? 'selected' : ''; ?>>Admin</option>
            </select>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
        </div>

        <button type="submit" class="submit-btn">Create User</button>
    </form>

    <div class="back-link">
        <a href="index.php?page=dashboard">Back to Dashboard</a>
    </div>
</div>

<?php renderPageEnd(); ?>