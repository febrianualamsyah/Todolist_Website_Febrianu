<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

$user_id = isset($_SESSION['user']['id']) ? intval($_SESSION['user']['id']) : 0;

if ($user_id == 0) {
    die("Error: User ID tidak valid. Silakan login kembali.");
}

$result = $conn->query("SELECT * FROM tasks WHERE user_id='$user_id'");

if (isset($_POST['add_task'])) {
    $task = trim($_POST['task']);
    if (!empty($task)) {
        $stmt = $conn->prepare("INSERT INTO tasks (user_id, task, status) VALUES (?, ?, 'pending')");
        $stmt->bind_param("is", $user_id, $task);
        $stmt->execute();
        $stmt->close();
    }
    header('Location: dashboard.php');
    exit;
}

if (isset($_POST['update_task'])) {
    $task_id = intval($_POST['task_id']);
    $task = trim($_POST['task']);
    if (!empty($task)) {
        $stmt = $conn->prepare("UPDATE tasks SET task=? WHERE id=? AND user_id=?");
        $stmt->bind_param("sii", $task, $task_id, $user_id);
        $stmt->execute();
        $stmt->close();
    }
    header('Location: dashboard.php');
    exit;
}

if (isset($_POST['toggle_task'])) {
    $task_id = intval($_POST['task_id']);
    $current_status = $_POST['current_status'];
    $new_status = ($current_status === 'done') ? 'pending' : 'done';

    $stmt = $conn->prepare("UPDATE tasks SET status=? WHERE id=? AND user_id=?");
    $stmt->bind_param("sii", $new_status, $task_id, $user_id);
    $stmt->execute();
    $stmt->close();

    header('Location: dashboard.php');
    exit;
}

if (isset($_GET['delete'])) {
    $task_id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM tasks WHERE id=? AND user_id=?");
    $stmt->bind_param("ii", $task_id, $user_id);
    $stmt->execute();
    $stmt->close();
    header('Location: dashboard.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - To-Do List</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .done {
            text-decoration: line-through;
            color: gray;
        }
    </style>
</head>
<body style="background-color: #FFF2F2" >

<div class="container my-4 text-white">
    <div class="card shadow-sm" style="background-color: #A9B5DF">
        <div class="card-body">
            <h2 class="text-center mb-3">To-Do List</h2>

            <form method="POST" action="" class="d-flex gap-2">
                <input type="text" name="task" class="form-control" placeholder="Tambah Tugas Baru" required>
                <button type="submit" name="add_task" class="btn btn-primary">Tambah</button>
            </form>

            <hr>

            <?php if ($result->num_rows > 0): ?>
                <ul class="list-group">
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <form method="POST" action="" class="d-flex align-items-center">
                                <input type="hidden" name="task_id" value="<?= $row['id'] ?>">
                                <input type="hidden" name="current_status" value="<?= $row['status'] ?>">
                                <input type="checkbox" name="toggle_task" class="form-check-input me-2" onchange="this.form.submit()" <?= ($row['status'] === 'done') ? 'checked' : '' ?>>
                                <span class="<?= ($row['status'] === 'done') ? 'done' : '' ?>">
                                    <?= htmlspecialchars($row['task']) ?>
                                </span>
                            </form>

                            <div>
                                <button type="button" class="btn btn-sm btn-warning text-white" data-bs-toggle="modal" data-bs-target="#editTaskModal<?= $row['id'] ?>">Edit</button>
                                <a href="dashboard.php?delete=<?= $row['id'] ?>" class="btn btn-sm btn-danger">Hapus</a>
                            </div>
                        </li>

                        
                        <div class="modal fade" id="editTaskModal<?= $row['id'] ?>" tabindex="-1" aria-labelledby="editTaskLabel" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Edit Tugas</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <form method="POST" action="">
                                            <input type="hidden" name="task_id" value="<?= $row['id'] ?>">
                                            <div class="mb-3">
                                                <label for="taskInput<?= $row['id'] ?>" class="form-label">Tugas</label>
                                                <input type="text" class="form-control" id="taskInput<?= $row['id'] ?>" name="task" value="<?= htmlspecialchars($row['task']) ?>" required>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="submit" name="update_task" class="btn btn-primary">Simpan</button>
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                    <?php endwhile; ?>
                </ul>
            <?php else: ?>
                <p class="text-center text-muted">Belum ada tugas.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="text-center mt-3">
        <a href="logout.php" class="btn btn-secondary">Logout</a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
