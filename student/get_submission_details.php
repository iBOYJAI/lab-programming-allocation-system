<?php

/**
 * AJAX - Get Submission Details for Student
 */
require_once '../config/database.php';
require_once '../includes/security.php';
require_once '../includes/functions.php';

startSecureSession();
requireRole(['student'], '../auth.php');

$student_id = $_SESSION['user_id'];
$session_id = $_GET['session_id'] ?? null;

if (!$session_id) {
    die(json_encode(['success' => false, 'message' => 'Session ID required']));
}

try {
    // Get allocation and questions
    $allocation = dbQuery("
        SELECT sa.id as allocation_id, 
               q1.question_title as q1_title, q2.question_title as q2_title,
               q1.id as q1_id, q2.id as q2_id
        FROM student_allocations sa
        INNER JOIN questions q1 ON sa.question_1_id = q1.id
        INNER JOIN questions q2 ON sa.question_2_id = q2.id
        WHERE sa.session_id = ? AND sa.student_id = ?
    ", [$session_id, $student_id])[0] ?? null;

    if (!$allocation) {
        throw new Exception("Allocation not found");
    }

    // Get submissions
    $submissions = dbQuery("
        SELECT * FROM submissions 
        WHERE allocation_id = ?
    ", [$allocation['allocation_id']]);

    $data = [
        'success' => true,
        'q1' => [
            'title' => $allocation['q1_title'],
            'code' => 'No submission found',
            'marks' => 0,
            'remarks' => 'Not evaluated'
        ],
        'q2' => [
            'title' => $allocation['q2_title'],
            'code' => 'No submission found',
            'marks' => 0,
            'remarks' => 'Not evaluated'
        ]
    ];

    foreach ($submissions as $sub) {
        $key = ($sub['question_id'] == $allocation['q1_id']) ? 'q1' : 'q2';
        $data[$key]['code'] = $sub['submitted_code'];
        $data[$key]['marks'] = $sub['marks'];
        $data[$key]['remarks'] = $sub['remarks'] ?: 'No remarks';
    }

    echo json_encode($data);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
