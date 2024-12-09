<?php
// Load the courses data
$courses_file = 'courses.json';
if (!file_exists($courses_file)) {
    die("Courses file not found.");
}

$courses = json_decode(file_get_contents($courses_file), true);

// Retrieve user input
$instructors = $_POST['instructors'] ?? [];
$areas = $_POST['areas'] ?? [];
$availability = [];
if (isset($_POST['days'], $_POST['start_time'], $_POST['end_time'])) {
    foreach ($_POST['days'] as $index => $day) {
        $availability[] = [
            'day' => $day,
            'start_time' => $_POST['start_time'][$index],
            'end_time' => $_POST['end_time'][$index],
        ];
    }
}
$credits = intval($_POST['credits'] ?? 3);

// Constraints
$hard_constraints = [
    'non_conflicting_times' => true,
    'max_courses_per_day' => 2,
];
$soft_constraints = [
    'student_availability' => $availability,
    'preferred_instructors' => $instructors,
    'preferred_areas' => $areas,
];

// Scoring system
function score_course($course, $soft_constraints) {
    $score = 0;

    // Preferred instructors
    if (in_array($course['instructor'], $soft_constraints['preferred_instructors'])) {
        $score += 2;
    }

    // Preferred areas
    if (in_array($course['area'], $soft_constraints['preferred_areas'])) {
        $score += 1;
    }

    // Availability match
    foreach ($soft_constraints['student_availability'] as $availability) {
        if ($availability['day'] === $course['day'] &&
            $availability['start_time'] <= $course['time_start'] &&
            $availability['end_time'] >= $course['time_end']) {
            $score += 3;
            break;
        }
    }

    return $score;
}

// Schedule generation with hard constraints (including time overlap)
function generate_schedule($courses, $hard_constraints, $soft_constraints, $credits) {
    $selected_courses = [];
    $daily_course_count = [];

    foreach ($courses as $course) {
        // Check hard constraints
        $day = $course['day'];
        if (!isset($daily_course_count[$day])) {
            $daily_course_count[$day] = 0;
        }

        if ($daily_course_count[$day] >= $hard_constraints['max_courses_per_day']) {
            continue;
        }

        // Check time conflicts
        $conflict = false;
        foreach ($selected_courses as $selected_course) {
            if ($selected_course['day'] === $course['day'] &&
                $selected_course['time_end'] > $course['time_start'] &&
                $selected_course['time_start'] < $course['time_end']) {
                $conflict = true;
                break;
            }
        }

        if ($conflict) {
            continue;
        }

        // Add course and update counters
        $selected_courses[] = $course;
        $daily_course_count[$day]++;

        // Stop when enough credits are selected
        if (count($selected_courses) >= $credits) {
            break;
        }
    }

    return $selected_courses;
}

// Calculate scores and sort courses
foreach ($courses as &$course) {
    $course['score'] = score_course($course, $soft_constraints);
}
unset($course);

// Sort courses in descending order of scores
usort($courses, function ($a, $b) {
    return $b['score'] <=> $a['score'];
});

// Generate the suggested schedule
$suggested_schedule = generate_schedule($courses, $hard_constraints, $soft_constraints, $credits);

// Identify remaining courses
$remaining_courses = array_udiff($courses, $suggested_schedule, function ($a, $b) {
    return strcmp($a['course_code'] . $a['section'], $b['course_code'] . $b['section']);
});

// Display the results
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Schedule Suggestions</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
<div class="container mt-5">
    <h1 class="mb-4">Course Schedule Suggestions</h1>

    <!-- Suggested Schedule Table -->
    <h2 class="mb-3 text-primary">Suggested Schedule</h2>
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
        <?php foreach ($suggested_schedule as $course): ?>
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

    <!-- Other Courses Table -->
    <h2 class="mt-5 mb-3 text-success">Other Courses</h2>
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
        <?php foreach ($remaining_courses as $course): ?>
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
