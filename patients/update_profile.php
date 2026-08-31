<?php
require_once "../config/auth.php";
checkRole("patient");
require_once "../config/db.php";

$user_id = $_SESSION["user_id"];
$current_page = "update_profile";

// Fetch existing profile data
$query = "SELECT first_name, last_name, email, phone_number, date_of_birth, gender, address FROM users WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$patient = $result->fetch_assoc();

// Handle profile update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = trim($_POST["first_name"] ?? "");
    $last_name = trim($_POST["last_name"] ?? "");
    $phone_number = trim($_POST["phone_number"]);
    $date_of_birth = $_POST["date_of_birth"];
    $gender = $_POST["gender"];
    $address = trim($_POST["address"]);

    $latest_allowed_dob = "2010-12-31";
    $today = date("Y-m-d");

    if ($date_of_birth !== "" && $date_of_birth > $today) {
        $error = "Date of birth cannot be in the future.";
    } elseif ($date_of_birth !== "" && $date_of_birth > $latest_allowed_dob) {
        $error = "Date of birth must be on or before 31 December 2010.";
    } else {
        $stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, phone_number = ?, date_of_birth = ?, gender = ?, address = ? WHERE user_id = ?");
        $stmt->bind_param("ssssssi", $first_name, $last_name, $phone_number, $date_of_birth, $gender, $address, $user_id);
        $stmt->execute();

        $success = "Profile updated successfully!";
        // refresh $patient so the form reflects the saved values
        $patient['first_name'] = $first_name;
        $patient['last_name'] = $last_name;
        $patient['phone_number'] = $phone_number;
        $patient['date_of_birth'] = $date_of_birth;
        $patient['gender'] = $gender;
        $patient['address'] = $address;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Profile — AfyaBora</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<?php include "sidebar.php"; ?>

<div class="ab-main-wrap">
    <header class="ab-topbar">
        <div class="ab-topbar-left">
            <div class="ab-greeting">Update Profile</div>
            <div class="ab-subgreeting">Keep your contact and personal details current.</div>
        </div>
        <div class="ab-topbar-right">
            <div class="ab-user-chip">
                <div class="ab-user-avatar"><?= htmlspecialchars(strtoupper(substr($_SESSION["full_name"] ?? "P", 0, 1))) ?></div>
                <div class="ab-user-name"><?= htmlspecialchars($_SESSION["full_name"] ?? "") ?></div>
            </div>
        </div>
    </header>

    <main class="ab-content">
        <div class="ab-center-viewport">
        <div class="ab-page-title">Update Profile</div>
        <div class="ab-page-sub">Changes here also update what your doctors and reception see.</div>

        <div class="ab-card">
            <?php if (isset($error)): ?><div class="ab-alert ab-alert-danger"><i class="fas fa-circle-exclamation"></i> <?php echo htmlspecialchars($error); ?></div><?php endif; ?>
            <?php if (isset($success)): ?><div class="ab-alert ab-alert-success"><i class="fas fa-circle-check"></i> <?php echo htmlspecialchars($success); ?></div><?php endif; ?>
            <form method="POST" action="update_profile.php">
                <div class="ab-form-row">
                    <div class="ab-form-group">
                        <label class="ab-label">First Name <span class="req">*</span></label>
                        <input type="text" name="first_name" class="ab-input" value="<?php echo htmlspecialchars($patient['first_name'] ?? ''); ?>" required>
                    </div>
                    <div class="ab-form-group">
                        <label class="ab-label">Last Name <span class="req">*</span></label>
                        <input type="text" name="last_name" class="ab-input" value="<?php echo htmlspecialchars($patient['last_name'] ?? ''); ?>" required>
                    </div>
                </div>
                <div class="ab-form-group">
                    <label class="ab-label">Phone Number</label>
                    <input type="text" name="phone_number" class="ab-input" value="<?php echo htmlspecialchars($patient['phone_number'] ?? ''); ?>">
                </div>
                <div class="ab-form-group">
                    <label class="ab-label">Date of Birth</label>
                    <input type="date" name="date_of_birth" class="ab-input" max="2010-12-31" value="<?php echo htmlspecialchars($patient['date_of_birth'] ?? ''); ?>">
                </div>
                <div class="ab-form-group">
                    <label class="ab-label">Gender</label>
                    <select name="gender" class="ab-input">
                        <option value="male" <?php echo (($patient['gender'] ?? '') == 'male') ? 'selected' : ''; ?>>Male</option>
                        <option value="female" <?php echo (($patient['gender'] ?? '') == 'female') ? 'selected' : ''; ?>>Female</option>
                        <option value="other" <?php echo (($patient['gender'] ?? '') == 'other') ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>
                <div class="ab-form-group">
                    <label class="ab-label">Address</label>
                    <textarea name="address" class="ab-input" rows="3"><?php echo htmlspecialchars($patient['address'] ?? ''); ?></textarea>
                </div>
                <button type="submit" class="ab-btn ab-btn-primary" style="width:100%;justify-content:center"><i class="fas fa-floppy-disk"></i> Save Changes</button>
            </form>
        </div>
        </div>
    </main>
</div>

</body>
</html>
