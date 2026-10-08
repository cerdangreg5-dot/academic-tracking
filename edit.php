<?php
session_start();
if (!isset($_SESSION['user'])) { 
    header("Location: login.php"); 
    exit(); 
}
require __DIR__ . '/database.php';

$id = $_GET['id'] ?? null;
if (!$id) { 
    header("Location: index.php"); 
    exit(); 
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("UPDATE track_enrollments SET 
        enrollment_status = ?, 
        midterm_grade = ?, 
        final_grade = ?, 
        attendance_rate = ?, 
        financial_clearance = ?, 
        administrative_remarks = ? 
        WHERE enrollment_id = ?");

    $stmt->execute([
        $_POST['enrollment_status'],
        $_POST['midterm_grade'],
        $_POST['final_grade'],
        $_POST['attendance_rate'],
        isset($_POST['financial_clearance']) ? 1 : 0,
        $_POST['administrative_remarks'],
        $id
    ]);

    header("Location: index.php");
    exit();
}

$stmt = $pdo->prepare("SELECT e.*, s.first_name, s.last_name, c.course_code, c.course_name 
                       FROM track_enrollments e 
                       JOIN track_students s ON e.student_id = s.student_id 
                       JOIN track_courses c ON e.course_id = c.course_id 
                       WHERE e.enrollment_id = ?");
$stmt->execute([$id]);
$record = $stmt->fetch();

if (!$record) { 
    header("Location: index.php"); 
    exit(); 
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Enrollment Record</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="form-body">
    <div class="form-card">
        <h2>Edit Record #<?= $record['enrollment_id'] ?></h2>
        <p class="record-info"><strong>Student:</strong> <?= htmlspecialchars($record['first_name'] . ' ' . $record['last_name']) ?></p>
        <p class="record-info"><strong>Course:</strong> <?= htmlspecialchars($record['course_code'] . ' - ' . $record['course_name']) ?></p>
        <hr><br>
        
        <form method="POST">
            <div class="form-group">
                <label>Status</label>
                <select name="enrollment_status">
                    <?php foreach (['Enrolled', 'Completed', 'Dropped', 'Withdrawn'] as $status): ?>
                        <option value="<?= $status ?>" <?= $record['enrollment_status'] === $status ? 'selected' : '' ?>><?= $status ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="row">
                <div class="form-group">
                    <label>Midterm Grade</label>
                    <input type="number" step="0.01" min="0" max="100" name="midterm_grade" value="<?= $record['midterm_grade'] ?>" required>
                </div>
                <div class="form-group">
                    <label>Final Grade</label>
                    <input type="number" step="0.01" min="0" max="100" name="final_grade" value="<?= $record['final_grade'] ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label>Attendance Rate (%)</label>
                <input type="number" min="0" max="100" name="attendance_rate" value="<?= $record['attendance_rate'] ?>" required>
            </div>

            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="financial_clearance" value="1" <?= $record['financial_clearance'] ? 'checked' : '' ?>> Financial Clearance
                </label>
            </div>

            <div class="form-group">
                <label>Administrative Remarks</label>
                <textarea name="administrative_remarks" rows="3"><?= htmlspecialchars($record['administrative_remarks'] ?? '') ?></textarea>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-add">Update Record</button>
                <a href="index.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</body>
</html>