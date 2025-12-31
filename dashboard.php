<?php
require 'auth_check.php';
require 'db_config.php';

$pdo = getPDO();
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$username = isset($_SESSION['username']) ? $_SESSION['username'] : '';
if (!$username) {
    $stmt = $pdo->prepare("SELECT username FROM users WHERE id = :id");
    $stmt->execute(['id' => $user_id]);
    $row = $stmt->fetch();
    $username = $row ? $row['username'] : '';
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_task'])) {
        $title = $_POST['title'];
        $description = $_POST['description'];
        $stmt = $pdo->prepare("INSERT INTO tasks (title, description, user_id) VALUES (:title, :description, :user_id)");
        $stmt->execute(['title' => $title, 'description' => $description, 'user_id' => $user_id]);
    } elseif (isset($_POST['edit_task'])) {
        $task_id = $_POST['task_id'];
        $title = $_POST['title'];
        $description = $_POST['description'];
        if ($role == 'admin' || isOwner($pdo, $task_id, $user_id)) {
            $stmt = $pdo->prepare("UPDATE tasks SET title = :title, description = :description WHERE id = :id");
            $stmt->execute(['title' => $title, 'description' => $description, 'id' => $task_id]);
        }
    } elseif (isset($_POST['delete_task'])) {
        $task_id = $_POST['task_id'];
        if ($role == 'admin' || isOwner($pdo, $task_id, $user_id)) {
            $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = :id");
            $stmt->execute(['id' => $task_id]);
        }
    } elseif (isset($_POST['complete_task'])) {
        $task_id = $_POST['task_id'];
        if ($role == 'admin' || isOwner($pdo, $task_id, $user_id)) {
            $stmt = $pdo->prepare("UPDATE tasks SET status = 'completed' WHERE id = :id");
            $stmt->execute(['id' => $task_id]);
        }
    }
}

function isOwner($pdo, $task_id, $user_id) {
    $stmt = $pdo->prepare("SELECT user_id FROM tasks WHERE id = :id");
    $stmt->execute(['id' => $task_id]);
    $task = $stmt->fetch();
    return $task && $task['user_id'] == $user_id;
}

$search = isset($_GET['search']) ? $_GET['search'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';

$query = "SELECT t.*, u.username FROM tasks t JOIN users u ON t.user_id = u.id WHERE 1=1";
$params = [];

if ($role != 'admin') {
    $query .= " AND t.user_id = :user_id";
    $params['user_id'] = $user_id;
}

if ($search) {
    $query .= " AND (t.title LIKE :search OR t.description LIKE :search)";
    $params['search'] = "%$search%";
}

if ($status) {
    $query .= " AND t.status = :status";
    $params['status'] = $status;
}

$query .= " ORDER BY t.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$tasks = $stmt->fetchAll();

if ($role == 'admin') {
    $total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $total_tasks = $pdo->query("SELECT COUNT(*) FROM tasks")->fetchColumn();
    $completed_tasks = $pdo->query("SELECT COUNT(*) FROM tasks WHERE status = 'completed'")->fetchColumn();
    $pending_tasks = $total_tasks - $completed_tasks;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/styles.css" rel="stylesheet">
</head>
<body>
    <div class="container-centered">
        <div class="card-centered">
            <div class="header-row mb-3">
                <h2 class="mb-0">Welcome, <?php echo htmlspecialchars($username ?: ($role == 'admin' ? 'Admin' : 'User')); ?></h2>
                <div>
                    <a href="logout.php" class="btn btn-danger">Logout</a>
                    <?php if ($role == 'admin'): ?>
                        <a href="users.php" class="btn btn-primary">Manage Users</a>
                    <?php endif; ?>
                </div>
            </div>
    <?php if ($role == 'admin'): ?>

        <h3>Admin Dashboard Stats</h3>
        <ul>
            <li>Total Users: <?php echo $total_users; ?></li>
            <li>Total Tasks: <?php echo $total_tasks; ?></li>
            <li>Completed Tasks: <?php echo $completed_tasks; ?></li>
            <li>Pending Tasks: <?php echo $pending_tasks; ?></li>
        </ul>
    <?php endif; ?>

    <h3>Task Management</h3>
    <!-- Add Task Form -->
    <form action="" method="POST" class="mb-4">
        <input type="hidden" name="add_task" value="1">
        <div class="mb-3">
            <label for="title" class="form-label">Title</label>
            <input type="text" class="form-control" id="title" name="title" required>
        </div>
        <div class="mb-3">
            <label for="description" class="form-label">Description</label>
            <textarea class="form-control" id="description" name="description"></textarea>
        </div>
        <button type="submit" class="btn btn-success">Add Task</button>
    </form>

    <form action="" method="GET" class="mb-4">
        <div class="row">
            <div class="col">
                <input type="text" class="form-control" name="search" placeholder="Search tasks" value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col">
                <select class="form-select" name="status">
                    <option value="">All Status</option>
                    <option value="pending" <?php echo $status == 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="completed" <?php echo $status == 'completed' ? 'selected' : ''; ?>>Completed</option>
                </select>
            </div>
            <div class="col">
                <button type="submit" class="btn btn-primary">Filter</button>
            </div>
        </div>
    </form>

    <table class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Description</th>
                <th>Status</th>
                <th>User</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($tasks as $task): ?>
                <tr>
                    <td><?php echo $task['id']; ?></td>
                    <td><?php echo htmlspecialchars($task['title']); ?></td>
                    <td><?php echo htmlspecialchars($task['description']); ?></td>
                    <td><?php echo $task['status']; ?></td>
                    <td><?php echo $task['username']; ?></td>
                    <td>
                        <?php if ($role == 'admin' || $task['user_id'] == $user_id): ?>
                            
                            <form action="" method="POST" class="d-inline">
                                <input type="hidden" name="edit_task" value="1">
                                <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                <input type="text" name="title" value="<?php echo htmlspecialchars($task['title']); ?>" required>
                                <textarea name="description"><?php echo htmlspecialchars($task['description']); ?></textarea>
                                <button type="submit" class="btn btn-sm btn-warning">Edit</button>
                            </form>
                            <form action="" method="POST" class="d-inline">
                                <input type="hidden" name="delete_task" value="1">
                                <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                            </form>
                            <?php if ($task['status'] == 'pending'): ?>
                                <form action="" method="POST" class="d-inline">
                                    <input type="hidden" name="complete_task" value="1">
                                    <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-success">Complete</button>
                                </form>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
            <div class="footer-note">Logged in as <?php echo htmlspecialchars($username); ?></div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>