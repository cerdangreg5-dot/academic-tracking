<?php
session_start();
if (!isset($_SESSION['user'])) { 
    header("Location: login.php"); 
    exit(); 
}
require __DIR__ . '/database.php';

$students = $pdo->query("SELECT student_id, first_name, last_name FROM track_students ORDER BY last_name ASC")->fetchAll();
$courses = $pdo->query("SELECT course_id, course_code, course_name FROM track_courses ORDER BY course_code ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("INSERT INTO track_enrollments 
        (student_id, course_id, enrollment_date, enrollment_status, midterm_grade, final_grade, attendance_rate, financial_clearance, administrative_remarks) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $stmt->execute([
        $_POST['student_id'],
        $_POST['course_id'],
        $_POST['enrollment_date'],
        $_POST['enrollment_status'],
        $_POST['midterm_grade'],
        $_POST['final_grade'],
        $_POST['attendance_rate'],
        isset($_POST['financial_clearance']) ? 1 : 0,
        $_POST['administrative_remarks']
    ]);

    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Enrollment Record</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="form-body">
    <div class="form-card">
        <h2>Create New Enrollment Record</h2>
        <form method="POST" action="add.php">
            <div class="form-group">
                <label>Select Student</label>
                <select name="student_id" required>
                    <?php foreach ($students as $s): ?>
                        <option value="<?= $s['student_id'] ?>"><?= htmlspecialchars($s['last_name'] . ', ' . $s['first_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Select Course</label>
                <select name="course_id" required>
                    <?php foreach ($courses as $c): ?>
                        <option value="<?= $c['course_id'] ?>"><?= htmlspecialchars($c['course_code'] . ' - ' . $c['course_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="row">
                <div class="form-group">
                    <label>Enrollment Date</label>
                    <input type="date" name="enrollment_date" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="form-group">
                    <label>Enrollment Status</label>
                    <select name="enrollment_status">
                        <option value="Enrolled">Enrolled</option>
                        <option value="Completed">Completed</option>
                        <option value="Dropped">Dropped</option>
                        <option value="Withdrawn">Withdrawn</option>
                    </select>
                </div>
            </div>

            <div class="row">
                <div class="form-group">
                    <label>Midterm Grade (0-100)</label>
                    <input type="number" step="0.01" min="0" max="100" name="midterm_grade" value="85.00" required>
                </div>
                <div class="form-group">
                    <label>Final Grade (0-100)</label>
                    <input type="number" step="0.01" min="0" max="100" name="final_grade" value="88.00" required>
                </div>
            </div>

            <div class="form-group">
                <label>Attendance Rate (%)</label>
                <input type="number" min="0" max="100" name="attendance_rate" value="95" required>
            </div>

            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="financial_clearance" value="1" checked> Financial Clearance Approved
                </label>
            </div>

            <div class="form-group">
                <label>Administrative Remarks</label>
                <textarea name="administrative_remarks" rows="3">Standard Registration</textarea>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-add">Save Record</button>
                <a href="index.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</body>
</html>