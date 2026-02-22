---

**Lab Programming Allocation System**  
*SRINATH | 23AI152 | B.Sc. CS (AI & DS) | Gobi Arts & Science College, Gobichettypalayam*

---

# APPENDIX C  
# SAMPLE CODE SNIPPETS

This appendix contains representative code snippets from the Lab Programming Allocation System.

---

## C.1 Smart Allocation Algorithm — Collision Detection

*File: `includes/allocation_algorithm.php`*

Ensures no two adjacent students (by `system_no`) receive the same question set. Neighbors are defined as left (`system_no - 1`), right (`system_no + 1`), front row (`system_no - 10`), and back row (`system_no + 10`).

```php
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
```

---

## C.2 Database Connection (PDO)

*File: `config/database.php`*

```php
function getDbConnection(): PDO {
    $host = 'localhost';
    $dbname = 'lab_allocation_system';
    $username = 'root';
    $password = '';

    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];

    return new PDO($dsn, $username, $password, $options);
}
```

---

## C.3 Secure Password Hashing (Registration/Login)

*File: typical usage in auth or student creation*

```php
// Hashing password before storing
$hashed = password_hash($plain_password, PASSWORD_BCRYPT);

// Verifying on login
if (password_verify($input_password, $stored_hash)) {
    // Login successful
}
```

---

## C.4 Session and Role Check (Access Control)

*File: `includes/security.php` or similar*

```php
// Start secure session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
session_regenerate_id(true);

// Role-based redirect
$role = $_SESSION['role'] ?? null;
if ($role === 'admin') {
    header('Location: admin/dashboard.php');
} elseif ($role === 'staff') {
    header('Location: staff/dashboard.php');
} elseif ($role === 'student') {
    header('Location: student/dashboard.php');
}
```

---

## C.5 Prepared Statement (SQL Injection Prevention)

*File: typical pattern in `includes/allocation_algorithm.php`*

```php
$stmt = $pdo->prepare("SELECT id FROM questions WHERE subject_id = ?");
$stmt->execute([$subject_id]);
$questions = $stmt->fetchAll(PDO::FETCH_COLUMN);
```

---

*End of Appendix C*
