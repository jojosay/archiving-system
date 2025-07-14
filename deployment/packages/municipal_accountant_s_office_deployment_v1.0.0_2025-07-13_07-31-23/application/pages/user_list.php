<?php
// Check if user is admin
if (!$auth->hasRole('admin')) {
    header('Location: index.php?page=dashboard');
    exit;
}

require_once 'includes/layout.php';
require_once 'includes/user_manager.php';
$userManager = new UserManager($database);

// Handle password reset
$message = '';
$message_type = '';

if ($_POST && isset($_POST['action']) && $_POST['action'] === 'reset_password') {
    $user_id = $_POST['user_id'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    
    if (!empty($user_id) && !empty($new_password)) {
        $result = $userManager->resetPassword($user_id, $new_password);
        $message = $result['message'];
        $message_type = $result['success'] ? 'success' : 'error';
    } else {
        $message = 'User ID and new password are required.';
        $message_type = 'error';
    }
}

// Get all users
$users = $userManager->getAllUsers();

renderPageStart('User Management', 'user_list');
?>

<div class="page-header">
    <h1>User Management</h1>
    <p>Manage system users and their permissions</p>
</div>
<style>
        .users-table {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #ECF0F1;
        }
        th {
            background: #2C3E50;
            color: white;
            font-weight: 500;
        }
        tr:hover {
            background: #F8F9FA;
        }
        .role-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        .role-admin {
            background: #E74C3C;
            color: white;
        }
        .role-staff {
            background: #3498DB;
            color: white;
        }
        .status-active {
            color: #27AE60;
            font-weight: 500;
        }
        .status-inactive {
            color: #E74C3C;
            font-weight: 500;
        }
        .action-btn {
            background: #F39C12;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
            margin-right: 0.5rem;
        }
        .action-btn:hover {
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
            margin-top: 2rem;
        }
        .back-link a {
            color: #3498DB;
            text-decoration: none;
            font-size: 1.1rem;
        }
        .add-user-btn {
            background: #27AE60;
            color: white;
            padding: 0.75rem 1.5rem;
            text-decoration: none;
            border-radius: 4px;
            display: inline-block;
            margin-bottom: 1rem;
        }
        .add-user-btn:hover {
            background: #229954;
        }
</style>

        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <a href="?page=user_register" class="add-user-btn">+ Add New User</a>

        <div class="users-table">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <span class="role-badge role-<?php echo $user['role']; ?>">
                                    <?php echo ucfirst($user['role']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-<?php echo $user['is_active'] ? 'active' : 'inactive'; ?>">
                                    <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                            <td>
                                <button class="action-btn" onclick="resetPassword(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')">
                                    Reset Password
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="back-link">
        <a href="index.php?page=dashboard">Back to Dashboard</a>
    </div>

<script>
    function resetPassword(userId, username) {
        const newPassword = prompt(`Enter new password for user "${username}":`);
        if (newPassword && newPassword.length >= <?php echo PASSWORD_MIN_LENGTH; ?>) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" name="user_id" value="${userId}">
                <input type="hidden" name="new_password" value="${newPassword}">
            `;
            document.body.appendChild(form);
            form.submit();
        } else if (newPassword) {
            alert('Password must be at least <?php echo PASSWORD_MIN_LENGTH; ?> characters long.');
        }
    }
</script>

<?php renderPageEnd(); ?>