<?php
// Load the courses data from courses2.json
$courses_file = 'courses2.json';
if (!file_exists($courses_file)) {
    die("Courses file not found.");
}

$courses = json_decode(file_get_contents($courses_file), true);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Courses</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
<div class="container mt-5">
    <h1 class="mb-4 text-success">All Courses</h1>

    <table class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th>Course Code</th>
                <th>Title</th>
                <th>Instructor</th>
                <th>Day</th>
                <th>Time</th>
                <th>Description</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($courses as $course): ?>
            <tr>
                <td><?= htmlspecialchars($course['course_code']) ?></td>
                <td><?= htmlspecialchars($course['title']) ?></td>
                <td><?= htmlspecialchars($course['instructor']) ?></td>
                <td><?= htmlspecialchars($course['day']) ?></td>
                <td><?= htmlspecialchars($course['time_start'] . ' - ' . $course['time_end']) ?></td>
                <td><?= htmlspecialchars($course['description']) ?></td>
                <td><button class="btn btn-success save-btn">Save</button></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
    $(document).ready(function () {
        // Toggle save button colors
        $('.save-btn').click(function () {
            if ($(this).hasClass('btn-success')) {
                $(this).removeClass('btn-success').addClass('btn-danger').text('Saved');
            } else {
                $(this).removeClass('btn-danger').addClass('btn-success').text('Save');
            }
        });
    });
</script>
</body>
</html>
