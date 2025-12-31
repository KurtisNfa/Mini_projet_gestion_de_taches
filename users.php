<?php
require 'auth_check.php';
if ($_SESSION['role'] != 'admin') {
    header('Location: dashboard.php');
    exit;
}
require 'db_config.php';

$pdo = getPDO();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_user'])) {
        $username = $_POST['username'];
        $password = sha1($_POST['password']);
        $role = $_POST['role'];
        $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (:username, :password, :role)");
        $stmt->execute(['username' => $username, 'password' => $password, 'role' => $role]);
    } elseif (isset($_POST['edit_user'])) {
        $user_id = $_POST['user_id'];
        $username = $_POST['username'];
        $password = !empty($_POST['password']) ? sha1($_POST['password']) : null;
        $role = $_POST['role'];
        $query = "UPDATE users SET username = :username, role = :role";
        $params = ['username' => $username, 'role' => $role, 'id' => $user_id];
        if ($password) {
            $query .= ", password = :password";
            $params['password'] = $password;
        }
        $query .= " WHERE id = :id";
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
    } elseif (isset($_POST['delete_user'])) {
        $user_id = $_POST['user_id'];
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
        $stmt->execute(['id' => $user_id]);
    }
}

$stmt = $pdo->query("SELECT * FROM users");
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/styles.css" rel="stylesheet">
</head>
<body>
    <div class="container-centered">
        <div class="card-centered">
            <div class="header-row mb-3">
                <h2 class="mb-0">User Management</h2>
                <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
            </div>

    <form action="" method="POST" class="mb-4">
        <input type="hidden" name="add_user" value="1">
        <div class="mb-3">
            <label for="username" class="form-label">Username</label>
            <input type="text" class="form-control" id="username" name="username" required>
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input type="password" class="form-control" id="password" name="password" required>
        </div>
        <div class="mb-3">
            <label for="role" class="form-label">Role</label>
            <select class="form-select" id="role" name="role">
                <option value="user">User</option>
                <option value="admin">Admin</option>
            </select>
        </div>
        <button type="submit" class="btn btn-success">Add User</button>
    </form>

    <table class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Username</th>
                <th>Role</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><?php echo $user['id']; ?></td>
                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                    <td><?php echo $user['role']; ?></td>
                    <td>
                        
                        <form action="" method="POST" class="d-inline">
                            <input type="hidden" name="edit_user" value="1">
                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                            <input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                            <input type="password" name="password" placeholder="New Password (optional)">
                            <select name="role">
                                <option value="user" <?php echo $user['role'] == 'user' ? 'selected' : ''; ?>>User</option>
                                <option value="admin" <?php echo $user['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                            </select>
                            <button type="submit" class="btn btn-sm btn-warning">Edit</button>
                        </form>
                        <form action="" method="POST" class="d-inline">
                            <input type="hidden" name="delete_user" value="1">
                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?');">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
            <div class="footer-note">Manage users for the application.</div>
        </div>
    </div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>