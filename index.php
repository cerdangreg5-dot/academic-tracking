<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}
require __DIR__ . '/database.php';

// Pagination settings
$limit = 10; // Number of items per page
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) {
    $page = 1;
}
$offset = ($page - 1) * $limit;

$search = $_GET['search'] ?? '';

// Where clause setup for search
$whereClause = "";
$params = [];

if ($search) {
    $whereClause = " WHERE s.first_name LIKE ? OR s.last_name LIKE ? OR c.course_code LIKE ? OR e.enrollment_status LIKE ?";
    $term = "%$search%";
    $params = [$term, $term, $term, $term];
}

// 1. Count total records for pagination calculation
$countSql = "SELECT COUNT(*) 
             FROM track_enrollments e 
             JOIN track_students s ON e.student_id = s.student_id 
             JOIN track_courses c ON e.course_id = c.course_id" . $whereClause;

$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$total_records = $countStmt->fetchColumn();

$total_pages = ceil($total_records / $limit);
if ($total_pages > 0 && $page > $total_pages) {
    $page = $total_pages;
    $offset = ($page - 1) * $limit;
}

// 2. Fetch records for the current page
$sql = "SELECT e.*, s.first_name, s.last_name, c.course_code, c.course_name 
        FROM track_enrollments e 
        JOIN track_students s ON e.student_id = s.student_id 
        JOIN track_courses c ON e.course_id = c.course_id" 
        . $whereClause . 
        " ORDER BY e.enrollment_id DESC LIMIT $limit OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$records = $stmt->fetchAll();

// Metrics calculation
$total_students = $pdo->query("SELECT COUNT(*) FROM track_students")->fetchColumn();
$total_courses = $pdo->query("SELECT COUNT(*) FROM track_courses")->fetchColumn();
$total_enrollments = $pdo->query("SELECT COUNT(*) FROM track_enrollments")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Academic Tracking System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">Academic Tracking System</div>
        <div>
            <span>Welcome, <strong><?= htmlspecialchars($_SESSION['user']) ?></strong></span>
            <a href="logout.php" style="color: #ef4444;">Logout</a>
        </div>
    </nav>

    <div class="container">
        <div class="metrics-grid">
            <div class="metric-card">
                <h3>Total Students</h3>
                <p><?= $total_students ?></p>
            </div>
            <div class="metric-card">
                <h3>Total Courses</h3>
                <p><?= $total_courses ?></p>
            </div>
            <div class="metric-card">
                <h3>Total Enrollments</h3>
                <p><?= $total_enrollments ?></p>
            </div>
        </div>

        <div class="header-actions">
            <a href="add.php" class="btn btn-add">+ Add Record</a>
            <form method="GET" class="search-form">
                <input type="text" name="search" placeholder="Search student, course, status..." value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="btn">Search</button>
            </form>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Student Name</th>
                    <th>Course</th>
                    <th>Status</th>
                    <th>Midterm</th>
                    <th>Final</th>
                    <th>Attendance</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($records)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center;">No enrollment records found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($records as $r): ?>
                        <tr>
                            <td><?= $r['enrollment_id'] ?></td>
                            <td><?= htmlspecialchars($r['last_name'] . ', ' . $r['first_name']) ?></td>
                            <td><?= htmlspecialchars($r['course_code']) ?></td>
                            <td>
                                <span class="badge <?= strtolower($r['enrollment_status']) ?>">
                                    <?= htmlspecialchars($r['enrollment_status']) ?>
                                </span>
                            </td>
                            <td><?= number_format($r['midterm_grade'], 2) ?></td>
                            <td><?= number_format($r['final_grade'], 2) ?></td>
                            <td><?= $r['attendance_rate'] ?>%</td>
                            <td class="actions-cell">
                                <a href="edit.php?id=<?= $r['enrollment_id'] ?>" class="btn-sm btn-edit">Edit</a>
                                <a href="delete.php?id=<?= $r['enrollment_id'] ?>" class="btn-sm btn-delete" onclick="return confirm('Are you sure you want to delete this record?')">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- PAGINATION SECTION -->
        <?php if ($total_pages > 1): ?>
            <?php
            // Helper function to build page links while retaining the search parameter
            function page_url($p, $search) {
                $params = ['page' => $p];
                if (!empty($search)) {
                    $params['search'] = $search;
                }
                return 'index.php?' . http_build_query($params);
            }

            // Determine page numbers to display with ellipses
            $range = 2;
            $pages_to_show = [];
            for ($i = 1; $i <= $total_pages; $i++) {
                if ($i == 1 || $i == $total_pages || ($i >= $page - $range && $i <= $page + $range)) {
                    $pages_to_show[] = $i;
                }
            }
            ?>
            <div class="pagination">
                <!-- Previous Button -->
                <?php if ($page > 1): ?>
                    <a href="<?= page_url($page - 1, $search) ?>" class="page-nav">&lt;</a>
                <?php else: ?>
                    <span class="page-nav disabled">&lt;</span>
                <?php endif; ?>

                <!-- Page Numbers -->
                <?php 
                $prev = 0;
                foreach ($pages_to_show as $p): 
                    if ($prev && $p - $prev > 1): ?>
                        <span class="dots">&hellip;</span>
                    <?php endif; ?>
                    <a href="<?= page_url($p, $search) ?>" class="page-num <?= $p === $page ? 'active' : '' ?>">
                        <?= $p ?>
                    </a>
                <?php 
                    $prev = $p;
                endforeach; 
                ?>

                <!-- Next Button -->
                <?php if ($page < $total_pages): ?>
                    <a href="<?= page_url($page + 1, $search) ?>" class="page-nav">&gt;</a>
                <?php else: ?>
                    <span class="page-nav disabled">&gt;</span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>