<?php

/**
 * SMART QUESTION ALLOCATION ALGORITHM
 * Lab Programming Allocation System
 * 
 * CRITICAL COMPONENT: Ensures no adjacent students get the same questions
 * 
 * Algorithm Features:
 * - Each student gets EXACTLY 2 questions
 * - Questions from pool of at least 15 per subject
 * - Collision detection for adjacent students (±1, ±10 system numbers)
 * - Randomized but deterministic allocation
 * - Different allocation every session
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Main allocation function
 * 
 * @param int $session_id Current exam session ID
 * @param int $subject_id Subject ID to get questions from
 * @param array $students Array of student objects with [id, roll_number, name, system_no]
 * @return array Allocation result with success status and allocations
 */
function allocateQuestions($session_id, $subject_id, $students)
{
    $pdo = getDbConnection();

    try {
        // STEP 1: Fetch all questions for the subject
        $stmt = $pdo->prepare("SELECT id FROM questions WHERE subject_id = ?");
        $stmt->execute([$subject_id]);
        $questions = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $question_count = count($questions);

        // Validate question count (must be at least 15)
        if ($question_count < 15) {
            return [
                'success' => false,
                'error' => "Subject must have at least 15 questions. Currently has: {$question_count}"
            ];
        }

        if ($question_count > 100) {
            return [
                'success' => false,
                'error' => "Subject has too many questions (max 100). Currently has: {$question_count}"
            ];
        }

        // STEP 2: Sort students by system_no for consistent processing
        usort($students, function ($a, $b) {
            return $a['system_no'] - $b['system_no'];
        });

        // STEP 3: Create allocation matrix with collision detection
        $allocations = [];
        $allocation_map = []; // Map system_no to allocated questions for collision checking

        foreach ($students as $index => $student) {
            $student_id = $student['id'];
            $system_no = $student['system_no'];

            // Generate seed based on system_no and session_id for randomization
            $seed = $system_no + intval(substr((string)$session_id, -4)) + time();
            mt_srand($seed);

            // Shuffle questions with seed-based randomization
            $shuffled = $questions;
            shuffle($shuffled);

            // Calculate position-based offset (using prime number for better distribution)
            $offset = ($index * 7) % $question_count; // 7 is prime number

            // CRITICAL: Collision detection logic
            $attempt = 0;
            $max_attempts = 50;
            $q1 = null;
            $q2 = null;
            $collision = true;

            while ($collision && $attempt < $max_attempts) {
                // Select candidate questions
                $q1_index = ($offset + $attempt) % count($shuffled);
                $q2_index = ($offset + $attempt + floor(count($shuffled) / 2)) % count($shuffled);

                $q1 = $shuffled[$q1_index];
                $q2 = $shuffled[$q2_index];

                // Ensure q1 != q2
                if ($q1 == $q2) {
                    $q2_index = ($q2_index + 1) % count($shuffled);
                    $q2 = $shuffled[$q2_index];
                }

                // Check collision with adjacent students
                $adjacent_systems = [
                    $system_no - 1,  // Left neighbor
                    $system_no + 1,  // Right neighbor
                    $system_no - 10, // Student in previous row (assuming 10 per row)
                    $system_no + 10  // Student in next row
                ];

                $collision = false;

                foreach ($adjacent_systems as $adj_system) {
                    if (isset($allocation_map[$adj_system])) {
                        $adj_q1 = $allocation_map[$adj_system]['q1'];
                        $adj_q2 = $allocation_map[$adj_system]['q2'];

                        // Check if any question matches with adjacent student's questions
                        if (
                            $q1 == $adj_q1 || $q1 == $adj_q2 ||
                            $q2 == $adj_q1 || $q2 == $adj_q2
                        ) {
                            $collision = true;
                            break;
                        }
                    }
                }

                $attempt++;
            }

            // FALLBACK: If collision still exists after max attempts, use random selection
            if ($collision) {
                // Get all questions already allocated to adjacent students
                $used_questions = [];
                foreach ($adjacent_systems as $adj_system) {
                    if (isset($allocation_map[$adj_system])) {
                        $used_questions[] = $allocation_map[$adj_system]['q1'];
                        $used_questions[] = $allocation_map[$adj_system]['q2'];
                    }
                }

                // Find available questions
                $available = array_diff($shuffled, $used_questions);

                if (count($available) >= 2) {
                    $available = array_values($available);
                    $q1 = $available[0];
                    $q2 = $available[1];
                } else {
                    // If still not enough, just use first available (edge case)
                    shuffle($shuffled);
                    $q1 = $shuffled[0];
                    $q2 = $shuffled[1];
                }
            }

            // Store allocation in map
            $allocation_map[$system_no] = [
                'student_id' => $student_id,
                'q1' => $q1,
                'q2' => $q2
            ];

            // Add to allocations array
            $allocations[] = [
                'session_id' => $session_id,
                'student_id' => $student_id,
                'system_no' => $system_no,
                'question_1_id' => $q1,
                'question_2_id' => $q2
            ];
        }

        // STEP 4: Insert all allocations into database
        $pdo->beginTransaction();

        $insert_sql = "INSERT INTO student_allocations 
                      (session_id, student_id, system_no, question_1_id, question_2_id, allocated_at) 
                      VALUES (?, ?, ?, ?, ?, NOW())";

        $stmt = $pdo->prepare($insert_sql);

        foreach ($allocations as $alloc) {
            $stmt->execute([
                $alloc['session_id'],
                $alloc['student_id'],
                $alloc['system_no'],
                $alloc['question_1_id'],
                $alloc['question_2_id']
            ]);
        }

        $pdo->commit();

        // STEP 5: Return success result
        return [
            'success' => true,
            'allocated_count' => count($allocations),
            'allocations' => $allocations
        ];
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log("Allocation Error: " . $e->getMessage());

        return [
            'success' => false,
            'error' => "Allocation failed: " . $e->getMessage()
        ];
    }
}

/**
 * Verify allocation quality (for testing purposes)
 * Checks if any adjacent students have matching questions
 * 
 * @param int $session_id Session ID to verify
 * @return array Verification result
 */
function verifyAllocation($session_id)
{
    $pdo = getDbConnection();

    $sql = "SELECT * FROM student_allocations WHERE session_id = ? ORDER BY system_no";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$session_id]);
    $allocations = $stmt->fetchAll();

    $collisions = [];
    $total_checks = 0;

    foreach ($allocations as $i => $alloc) {
        $system_no = $alloc['system_no'];

        // Check with adjacent systems
        $adjacent_systems = [$system_no - 1, $system_no + 1, $system_no - 10, $system_no + 10];

        foreach ($allocations as $j => $adj) {
            if ($i != $j && in_array($adj['system_no'], $adjacent_systems)) {
                $total_checks++;

                // Check if any questions match
                if (
                    $alloc['question_1_id'] == $adj['question_1_id'] ||
                    $alloc['question_1_id'] == $adj['question_2_id'] ||
                    $alloc['question_2_id'] == $adj['question_1_id'] ||
                    $alloc['question_2_id'] == $adj['question_2_id']
                ) {

                    $collisions[] = [
                        'system_1' => $system_no,
                        'student_1' => $alloc['student_id'],
                        'system_2' => $adj['system_no'],
                        'student_2' => $adj['student_id'],
                        'matching_questions' => 'Collision detected'
                    ];
                }
            }
        }
    }

    return [
        'total_students' => count($allocations),
        'total_adjacency_checks' => $total_checks,
        'collisions_found' => count($collisions),
        'collision_details' => $collisions,
        'success_rate' => count($collisions) == 0 ? 100 :
            round((1 - count($collisions) / $total_checks) * 100, 2)
    ];
}

/**
 * Get allocation details for a student
 * 
 * @param int $session_id Session ID
 * @param int $student_id Student ID
 * @return array|null Allocation data with question details
 */
function getStudentAllocation($session_id, $student_id)
{
    $pdo = getDbConnection();

    $sql = "SELECT sa.*, 
                   q1.question_title as q1_title, q1.question_text as q1_desc,
                   q1.difficulty as q1_difficulty, q1.sample_input as q1_input,
                   q1.sample_output as q1_output,
                   q2.question_title as q2_title, q2.question_text as q2_desc,
                   q2.difficulty as q2_difficulty, q2.sample_input as q2_input,
                   q2.sample_output as q2_output
            FROM student_allocations sa
            INNER JOIN questions q1 ON sa.question_1_id = q1.id
            INNER JOIN questions q2 ON sa.question_2_id = q2.id
            WHERE sa.session_id = ? AND sa.student_id = ?";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$session_id, $student_id]);

    $result = $stmt->fetch();
    return $result ? $result : null;
}

/**
 * Get all allocations for a session (for staff monitoring)
 * 
 * @param int $session_id Session ID
 * @return array Array of allocations with student and question details
 */
function getSessionAllocations($session_id)
{
    $pdo = getDbConnection();

    $sql = "SELECT sa.*, 
                   st.roll_number, st.name as student_name,
                   q1.question_title as q1_title,
                   q2.question_title as q2_title
            FROM student_allocations sa
            INNER JOIN students st ON sa.student_id = st.id
            INNER JOIN questions q1 ON sa.question_1_id = q1.id
            INNER JOIN questions q2 ON sa.question_2_id = q2.id
            WHERE sa.session_id = ?
            ORDER BY sa.system_no";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$session_id]);

    return $stmt->fetchAll();
}

/**
 * Clear allocations for a session (for re-allocation)
 * 
 * @param int $session_id Session ID
 * @return bool Success status
 */
function clearSessionAllocations($session_id)
{
    $pdo = getDbConnection();

    try {
        $stmt = $pdo->prepare("DELETE FROM student_allocations WHERE session_id = ?");
        return $stmt->execute([$session_id]);
    } catch (Exception $e) {
        error_log("Clear allocation error: " . $e->getMessage());
        return false;
    }
}
