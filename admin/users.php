<?php
require_once "admin_auth.php";
require_once "../config/db.php";


$result = $conn->query("SELECT user_id, full_name, email, phone_number, role, created_at FROM users ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage Users</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>

<?php include "navbar.php"; ?>

<div class="container mt-5">
  <h2 class="mb-4">All Users</h2>
  <a href="add_user.php" class="btn btn-success mb-3">Add New User</a>
  <table class="table table-bordered">
    <thead class="table-primary">
      <tr>
        <th>ID</th>
        <th>Full Name</th>
        <th>Email</th>
        <th>Phone</th>
        <th>Role</th>
        <th>Created</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php while($row = $result->fetch_assoc()): ?>
        <tr>
          <td><?= $row["user_id"] ?></td>
          <td><?= $row["full_name"] ?></td>
          <td><?= $row["email"] ?></td>
          <td><?= $row["phone_number"] ?></td>
          <td><?= ucfirst($row["role"]) ?></td>
          <td><?= $row["created_at"] ?></td>
          <td>
            <a href="edit_user.php?id=<?= $row["user_id"] ?>" class="btn btn-sm btn-warning">Edit</a>
            <a href="delete_user.php?id=<?= $row["user_id"] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this user?');">Delete</a>
          </td>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>

<?php include "../partials/footer.php"; ?>

</body>
</html>
