<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: slogin.php");
    exit;
}

$conn = new mysqli("localhost", "System_User", "gcs_db2026", "gcs_db");
if ($conn->connect_error) {
    die(json_encode(["error" => "Connection failed: " . $conn->connect_error]));
}

/*
  REQUIRED: Run these SQL statements once on your database before using this file.

  ALTER TABLE students ADD COLUMN IF NOT EXISTS archived TINYINT(1) NOT NULL DEFAULT 0;
  ALTER TABLE students ADD COLUMN IF NOT EXISTS graduated_at DATETIME NULL DEFAULT NULL;
*/

// ================= HANDLE GET: NEXT STUDENT ID =================
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'next_id') {
    header('Content-Type: application/json');

    $year_level = $_GET['year_level'] ?? '';

    $prefix_map = [
        '4th Year' => '22',
        '3rd Year' => '23',
        '2nd Year' => '24',
        '1st Year' => '25',
    ];

    if (!isset($prefix_map[$year_level])) {
        echo json_encode(["success" => false, "message" => "Invalid year level."]);
        exit;
    }

    $prefix = $prefix_map[$year_level];

    $result = $conn->query("
        SELECT MAX(student_id) AS max_id
        FROM students
        WHERE student_id LIKE '{$prefix}%'
    ");

    $row    = $result->fetch_assoc();
    $max_id = $row['max_id'];

    if ($max_id) {
        $next_id = $max_id + 1;
    } else {
        $next_id = intval($prefix . '0001');
    }

    echo json_encode(["success" => true, "next_id" => $next_id]);
    exit;
}

// ================= HANDLE GET: FETCH ACTIVE STUDENTS =================
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'fetch') {

    header('Content-Type: application/json');

    $search     = $conn->real_escape_string($_GET['search'] ?? '');
    $course     = $conn->real_escape_string($_GET['course'] ?? 'All Courses');
    $year_level = $conn->real_escape_string($_GET['year_level'] ?? 'All Years');
    $sort_col   = $_GET['sort_col'] ?? 'student_id';
    $sort_dir   = ($_GET['sort_dir'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';

    $allowed_cols = ['student_id', 'last_name', 'year_level'];
    if (!in_array($sort_col, $allowed_cols)) {
        $sort_col = 'student_id';
    }

    $where = "WHERE archived = 0";

    if (!empty($search)) {
        $where .= " AND (
            student_id LIKE '%$search%' OR
            first_name LIKE '%$search%' OR
            last_name  LIKE '%$search%'
        )";
    }

    if ($course !== "All Courses") {
        $where .= " AND course = '$course'";
    }

    if ($year_level !== "All Years") {
        $where .= " AND year_level = '$year_level'";
    }

    $sql = "SELECT *, TIMESTAMPDIFF(YEAR, birthday, CURDATE()) AS age
            FROM students
            $where
            ORDER BY $sort_col $sort_dir";

    $result = $conn->query($sql);

    if (!$result) {
        echo json_encode(["success" => false, "message" => "Query failed: " . $conn->error]);
        exit;
    }

    $students = [];
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }

    echo json_encode(["success" => true, "data" => $students]);
    exit;
}

// ================= HANDLE GET: FETCH ARCHIVED STUDENTS =================
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'fetch_archived') {

    header('Content-Type: application/json');

    $search = $conn->real_escape_string($_GET['search'] ?? '');

    $where = "WHERE archived = 1";

    if (!empty($search)) {
        $where .= " AND (
            student_id LIKE '%$search%' OR
            first_name LIKE '%$search%' OR
            last_name  LIKE '%$search%'
        )";
    }

    $sql = "SELECT *, TIMESTAMPDIFF(YEAR, birthday, CURDATE()) AS age
            FROM students
            $where
            ORDER BY graduated_at DESC, student_id ASC";

    $result = $conn->query($sql);

    if (!$result) {
        echo json_encode(["success" => false, "message" => "Query failed: " . $conn->error]);
        exit;
    }

    $students = [];
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }

    echo json_encode(["success" => true, "data" => $students]);
    exit;
}

// ================= HANDLE POST REQUESTS =================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    $action = $_POST['action'];

    // ---------- ADD STUDENT ----------
    if ($action === 'add') {
        $student_id = intval($_POST['student_id']);
        $first_name = $conn->real_escape_string(trim($_POST['first_name']));
        $last_name  = $conn->real_escape_string(trim($_POST['last_name']));
        $email      = $conn->real_escape_string(trim($_POST['email']));
        $gender     = $conn->real_escape_string($_POST['gender']);
        $birthday   = $conn->real_escape_string($_POST['birthday']);
        $year_level = $conn->real_escape_string($_POST['year_level']);
        $course     = $conn->real_escape_string($_POST['course']);

        $check = $conn->query("SELECT student_id FROM students WHERE student_id = $student_id OR email = '$email'");
        if ($check->num_rows > 0) {
            echo json_encode(["success" => false, "message" => "Student ID or Email already exists."]);
            exit;
        }

        // Use direct INSERT instead of stored procedure to avoid multi-result-set issues
        $stmt = $conn->prepare("
            INSERT INTO students (student_id, first_name, last_name, email, gender, birthday, year_level, course, archived)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0)
        ");
        $stmt->bind_param("isssssss",
            $student_id,
            $first_name,
            $last_name,
            $email,
            $gender,
            $birthday,
            $year_level,
            $course
        );
        $ok = $stmt->execute();
        $err = $stmt->error;
        $stmt->close();

        echo $ok
            ? json_encode(["success" => true,  "message" => "Student added successfully."])
            : json_encode(["success" => false, "message" => "Failed to add student: " . $err]);
        exit;
    }

    // ---------- EDIT STUDENT ----------
    if ($action === 'edit') {
        $student_id = intval($_POST['student_id']);
        $first_name = $conn->real_escape_string(trim($_POST['first_name']));
        $last_name  = $conn->real_escape_string(trim($_POST['last_name']));
        $email      = $conn->real_escape_string(trim($_POST['email']));
        $gender     = $conn->real_escape_string($_POST['gender']);
        $birthday   = $conn->real_escape_string($_POST['birthday']);
        $year_level = $conn->real_escape_string($_POST['year_level']);
        $course     = $conn->real_escape_string($_POST['course']);

        $emailCheck = $conn->query("SELECT student_id FROM students WHERE email = '$email' AND student_id != $student_id");
        if ($emailCheck->num_rows > 0) {
            echo json_encode(["success" => false, "message" => "Email already in use by another student."]);
            exit;
        }

        $sql = "UPDATE students
                SET first_name = '$first_name',
                    last_name  = '$last_name',
                    email      = '$email',
                    gender     = '$gender',
                    birthday   = '$birthday',
                    year_level = '$year_level',
                    course     = '$course'
                WHERE student_id = $student_id";

        if ($conn->query($sql)) {
            echo json_encode(["success" => true, "message" => "Student updated successfully."]);
        } else {
            echo json_encode(["success" => false, "message" => "Failed to update student: " . $conn->error]);
        }
        exit;
    }

    // ---------- PROMOTE ALL STUDENTS ----------
    if ($action === 'promote_all') {
        $conn->begin_transaction();

        try {
            $graduateResult = $conn->query("
                UPDATE students
                SET archived      = 1,
                    graduated_at  = NOW()
                WHERE year_level  = '4th Year'
                AND   archived    = 0
            ");
            if (!$graduateResult) throw new Exception($conn->error);
            $graduated = $conn->affected_rows;

            $r2 = $conn->query("UPDATE students SET year_level = '4th Year' WHERE year_level = '3rd Year' AND archived = 0");
            if (!$r2) throw new Exception($conn->error);

            $r3 = $conn->query("UPDATE students SET year_level = '3rd Year' WHERE year_level = '2nd Year' AND archived = 0");
            if (!$r3) throw new Exception($conn->error);

            $r4 = $conn->query("UPDATE students SET year_level = '2nd Year' WHERE year_level = '1st Year' AND archived = 0");
            if (!$r4) throw new Exception($conn->error);

            $conn->commit();

            echo json_encode([
                "success"   => true,
                "message"   => "Promotion complete! {$graduated} student(s) graduated and archived. All other year levels have been promoted.",
                "graduated" => $graduated
            ]);

        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(["success" => false, "message" => "Promotion failed: " . $e->getMessage()]);
        }
        exit;
    }

    // ---------- PROMOTE SELECTED STUDENTS ----------
    if ($action === 'promote_selected') {
        $ids_raw = $_POST['student_ids'] ?? '';
        $ids = array_filter(array_map('intval', explode(',', $ids_raw)));

        if (empty($ids)) {
            echo json_encode(["success" => false, "message" => "No students selected."]);
            exit;
        }

        $ids_str = implode(',', $ids);
        $conn->begin_transaction();

        try {
            // Graduate selected 4th year students
            $conn->query("
                UPDATE students
                SET archived = 1, graduated_at = NOW()
                WHERE student_id IN ($ids_str)
                AND year_level = '4th Year'
                AND archived = 0
            ");
            $graduated = $conn->affected_rows;

            // Promote 3rd → 4th
            $conn->query("UPDATE students SET year_level = '4th Year' WHERE student_id IN ($ids_str) AND year_level = '3rd Year' AND archived = 0");
            // Promote 2nd → 3rd
            $conn->query("UPDATE students SET year_level = '3rd Year' WHERE student_id IN ($ids_str) AND year_level = '2nd Year' AND archived = 0");
            // Promote 1st → 2nd
            $conn->query("UPDATE students SET year_level = '2nd Year' WHERE student_id IN ($ids_str) AND year_level = '1st Year' AND archived = 0");

            $conn->commit();

            $total = count($ids);
            echo json_encode([
                "success"   => true,
                "message"   => "Done! {$graduated} student(s) graduated & archived. " . ($total - $graduated) . " student(s) promoted to next year.",
                "graduated" => $graduated
            ]);

        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(["success" => false, "message" => "Promotion failed: " . $e->getMessage()]);
        }
        exit;
    }

    // ---------- ARCHIVE SINGLE STUDENT ----------
    if ($action === 'archive_student') {
        $student_id = intval($_POST['student_id']);

        $ok = $conn->query("
            UPDATE students
            SET archived     = 1,
                graduated_at = NOW()
            WHERE student_id = $student_id
        ");

        echo $ok
            ? json_encode(["success" => true,  "message" => "Student archived successfully."])
            : json_encode(["success" => false, "message" => "Database error: " . $conn->error]);
        exit;
    }

    // ---------- UNARCHIVE / RESTORE STUDENT ----------
    if ($action === 'unarchive_student') {
        $student_id = intval($_POST['student_id']);

        $ok = $conn->query("
            UPDATE students
            SET archived     = 0,
                graduated_at = NULL
            WHERE student_id = $student_id
        ");

        echo $ok
            ? json_encode(["success" => true,  "message" => "Student restored successfully."])
            : json_encode(["success" => false, "message" => "Database error: " . $conn->error]);
        exit;
    }

    // ---------- IMPORT CSV ----------
    if ($action === 'import_csv') {

        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(["success" => false, "message" => "No file uploaded or upload error."]);
            exit;
        }

        $file = fopen($_FILES['csv_file']['tmp_name'], 'r');
        if (!$file) {
            echo json_encode(["success" => false, "message" => "Failed to open CSV file."]);
            exit;
        }

        $rowCount = 0;
        $skipped  = 0;

        fgetcsv($file); // skip header

        while (($data = fgetcsv($file, 1000, ",")) !== FALSE) {
            if (count($data) < 8) {
                $skipped++;
                continue;
            }

            $student_id = intval($data[0]);
            $last_name  = $conn->real_escape_string(trim($data[1]));
            $first_name = $conn->real_escape_string(trim($data[2]));
            $email      = $conn->real_escape_string(trim($data[3]));
            $gender     = $conn->real_escape_string(trim($data[4]));
            $birthday   = $conn->real_escape_string(trim($data[5]));
            $year_level = $conn->real_escape_string(trim($data[6]));
            $course     = $conn->real_escape_string(trim($data[7]));

            $check = $conn->query("SELECT student_id FROM students WHERE student_id = $student_id OR email = '$email'");
            if ($check->num_rows > 0) {
                $skipped++;
                continue;
            }

            $sql = "INSERT INTO students (student_id, first_name, last_name, email, gender, birthday, year_level, course, archived)
                    VALUES ($student_id, '$first_name', '$last_name', '$email', '$gender', '$birthday', '$year_level', '$course', 0)";

            if ($conn->query($sql)) {
                $rowCount++;
            } else {
                $skipped++;
            }
        }

        fclose($file);

        echo json_encode([
            "success" => true,
            "message" => "$rowCount students imported successfully. $skipped skipped."
        ]);
        exit;
    }

    echo json_encode(["success" => false, "message" => "Unknown action."]);
    exit;
}

// ── Count archived for badge ──
$archivedCountRes = $conn->query("SELECT COUNT(*) AS cnt FROM students WHERE archived = 1");
$archivedCount    = $archivedCountRes ? $archivedCountRes->fetch_assoc()['cnt'] : 0;
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Students - UNITYCARE</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="logout.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        /* ── Sort dropdown ── */
        .aStudents-sort-wrapper { position: relative; display: inline-block; }
        .aStudents-sort-dropdown {
            display: none;
            position: absolute;
            top: 55px;
            right: 0;
            width: 200px;
            background: #fff;
            border-radius: 12px;
            padding: 10px 0;
            box-shadow: 0 15px 40px rgba(0,0,0,0.2);
            z-index: 999;
            overflow: hidden;
        }
        .aStudents-sort-dropdown.show { display: block; }
        .aStudents-sort-dropdown-label {
            font-size: 12px;
            font-weight: 600;
            color: #666;
            padding: 6px 15px 2px;
        }
        .aStudents-sort-option {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 9px 15px;
            font-size: 13px;
            color: var(--text);
            cursor: pointer;
            transition: var(--transition);
        }
        .aStudents-sort-option:hover  { background: rgba(73,136,196,0.08); }
        .aStudents-sort-option.active { background: rgba(17,63,103,0.1); color: #113F67; font-weight: 600; }
        .aStudents-sort-option i      { width: 14px; text-align: center; font-size: 0.75rem; }

        /* ── Student ID field ── */
        .aStudents-id-field { position: relative; }
        .aStudents-id-field input[readonly] { background: var(--input-bg, #f0f2f5); cursor: not-allowed; color: #555; }
        .aStudents-id-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 11px;
            color: #888;
            margin-top: 4px;
        }
        .aStudents-id-badge i { font-size: 10px; }

        /* ── Utilities ── */
        .aStudents-hidden        { display: none !important; }
        .aStudents-table-loading { text-align: center; padding: 20px; }
        .aStudents-table-empty   { text-align: center; padding: 20px; color: #888; }
        .aStudents-table-error   { text-align: center; padding: 20px; color: red; }

        /* ── Archive header button ── */
        .aStudents-archive-btn {
            background: #f3f4f6;
            color: #6b7280;
            border: 1px solid #e5e7eb;
            padding: 10px 16px;
            border-radius: var(--radius-md, 10px);
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.875rem;
        }
        .aStudents-archive-btn:hover { background: #e5e7eb; color: #374151; transform: translateY(-2px); }
        .aStudents-archive-btn .archive-count {
            background: #9ca3af;
            color: #fff;
            font-size: 0.7rem;
            font-weight: 700;
            padding: 1px 6px;
            border-radius: 999px;
            min-width: 18px;
            text-align: center;
        }

        /* ── Promote button ── */
        .aStudents-promote-btn {
            background: linear-gradient(135deg, #15803d, #22c55e);
            color: #fff;
            border: none;
            padding: 10px 16px;
            border-radius: var(--radius-md, 10px);
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.875rem;
        }
        .aStudents-promote-btn:hover { opacity: 0.9; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(21,128,61,0.25); }

        /* ── Add button ── */
        .aStudents-add-btn {
            background: linear-gradient(135deg, #113F67, #4988C4);
            color: #fff;
            border: none;
            padding: 10px 16px;
            border-radius: var(--radius-md, 10px);
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.875rem;
        }
        .aStudents-add-btn:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(17,63,103,0.25); }

        /* ── CSV buttons ── */
        .aStudents-csv-actions { display: flex; gap: 8px; }
        .aStudents-btn-import, .aStudents-btn-export {
            padding: 10px 14px;
            border-radius: var(--radius-md, 10px);
            font-weight: 600;
            cursor: pointer;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: 0.2s;
        }
        .aStudents-btn-import { background: #f0fdf4; color: #15803d; border: 1px solid #86efac; }
        .aStudents-btn-import:hover { background: #dcfce7; }
        .aStudents-btn-export { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
        .aStudents-btn-export:hover { background: #dbeafe; }

        /* ── Toast ── */
        .aStudents-toast {
            position: fixed;
            bottom: 24px;
            right: 24px;
            background: #113F67;
            color: #fff;
            padding: 12px 20px;
            border-radius: 10px;
            font-size: 0.88rem;
            font-weight: 500;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s, transform 0.3s;
            transform: translateY(8px);
            z-index: 99999;
            max-width: 340px;
        }
        .aStudents-toast.show { opacity: 1; transform: translateY(0); }

        /* ── Modal ── */
        .aStudents-modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(17,63,103,0.25);
            backdrop-filter: blur(6px);
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }
        .aStudents-modal.open { display: flex; }
        .aStudents-modal-content {
            width: 92%;
            max-width: 700px;
            background: rgba(255,255,255,0.9);
            backdrop-filter: blur(18px);
            border-radius: 18px;
            padding: 24px;
            border: 1px solid rgba(37,99,235,0.12);
            box-shadow: 0 20px 60px rgba(17,63,103,0.18);
            animation: aModalPop 0.22s ease;
        }
        .aStudents-modal-content.wide { max-width: 1000px; }
        @keyframes aModalPop {
            from { transform: scale(0.95); opacity: 0; }
            to   { transform: scale(1);    opacity: 1; }
        }
        .aStudents-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }
        .aStudents-modal-header h3 { margin: 0; font-size: 1.1rem; font-weight: 700; color: #113F67; }
        .aStudents-modal-header p  { margin: 4px 0 0; font-size: 0.83rem; color: var(--text-light); }
        .aStudents-modal-close {
            background: rgba(17,63,103,0.07);
            border: 1px solid rgba(17,63,103,0.12);
            width: 32px; height: 32px;
            border-radius: 9px;
            cursor: pointer;
            font-size: 0.85rem;
            color: #113F67;
            flex-shrink: 0;
        }
        .aStudents-modal-close:hover { background: rgba(17,63,103,0.14); }

        /* ── Modal footer ── */
        .aStudents-modal-footer {
            margin-top: 22px;
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        .aStudents-modal-footer .left-actions { margin-right: auto; display: flex; gap: 8px; }

        .aStudents-sec-label {
            font-size: 0.72rem;
            font-weight: 700;
            color: #4988C4;
            letter-spacing: 0.07em;
            margin: 4px 0 12px;
        }
        .aStudents-field-grid { display: grid; grid-template-columns: repeat(2,1fr); gap: 14px; }
        .aStudents-field.full { grid-column: span 2; }
        .aStudents-field label { display: block; font-size: 0.8rem; font-weight: 600; color: var(--text); margin-bottom: 5px; }
        .aStudents-field input,
        .aStudents-field select {
            width: 100%;
            padding: 10px 12px;
            border-radius: 10px;
            border: 1px solid rgba(37,99,235,0.18);
            outline: none;
            background: rgba(255,255,255,0.9);
            font-size: 0.9rem;
            color: var(--text);
            box-sizing: border-box;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .aStudents-field input:focus,
        .aStudents-field select:focus { border-color: #4988C4; box-shadow: 0 0 0 3px rgba(73,136,196,0.15); }
        .aStudents-field input[readonly],
        .aStudents-field input.readonly-field {
            background: rgba(243,244,246,0.8);
            color: var(--text-light);
            cursor: default;
        }
        .aStudents-field input.editable-field {
            background: rgba(255,255,255,0.9);
            color: var(--text);
            cursor: text;
        }

        /* ── Buttons ── */
        .aStudents-btn-cancel {
            padding: 9px 15px;
            border-radius: 10px;
            border: 1px solid rgba(0,0,0,0.1);
            background: #f3f4f6;
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--text);
            transition: background 0.15s;
        }
        .aStudents-btn-cancel:hover { background: #e5e7eb; }

        .aStudents-btn-save {
            padding: 9px 18px;
            border-radius: 10px;
            border: none;
            background: linear-gradient(135deg, #113F67, #4988C4);
            color: #fff;
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 600;
            transition: opacity 0.15s, transform 0.15s;
        }
        .aStudents-btn-save:hover { opacity: 0.9; transform: translateY(-1px); }

        .aStudents-btn-danger {
            padding: 9px 15px;
            border-radius: 10px;
            border: 1px solid #fca5a5;
            background: #fff0f0;
            color: #b91c1c;
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 500;
            transition: background 0.15s;
        }
        .aStudents-btn-danger:hover { background: #fee2e2; }

        /* ── View button in table ── */
        .aStudents-btn-view {
            background: #eef4ff; color: #113F67; border: 1px solid #c7d8f5;
            padding: 5px 12px; border-radius: var(--radius-sm, 6px);
            font-size: 12px; font-weight: 600; cursor: pointer;
            transition: 0.15s; display: inline-flex; align-items: center; gap: 5px;
        }
        .aStudents-btn-view:hover { background: #dbe9ff; }

        /* ── Restore button in archive table ── */
        .aStudents-btn-restore {
            background: #f0fdf4; color: #15803d; border: 1px solid #86efac;
            padding: 5px 12px; border-radius: var(--radius-sm, 6px);
            font-size: 12px; font-weight: 600; cursor: pointer;
            transition: 0.15s; display: inline-flex; align-items: center; gap: 5px;
        }
        .aStudents-btn-restore:hover { background: #dcfce7; }

        /* ── Badge ── */
        .aBadge { display: inline-block; padding: 3px 10px; border-radius: 999px; font-size: 0.78rem; font-weight: 600; }
        .aBadge-archived { background: #f3f4f6; color: #6b7280; }

        /* ── Archive empty state ── */
        .aArchive-empty {
            text-align: center;
            padding: 40px 20px;
            color: var(--text-light);
        }
        .aArchive-empty i { font-size: 2.5rem; opacity: 0.3; margin-bottom: 12px; display: block; }
        .aArchive-empty p { margin: 0; font-size: 0.95rem; }

        /* ── Archive search ── */
        .aArchive-search {
            width: 100%;
            padding: 9px 14px;
            border-radius: 10px;
            border: 1px solid rgba(37,99,235,0.18);
            outline: none;
            font-size: 0.9rem;
            margin-bottom: 16px;
            box-sizing: border-box;
            color: var(--text);
            background: rgba(255,255,255,0.9);
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .aArchive-search:focus { border-color: #4988C4; box-shadow: 0 0 0 3px rgba(73,136,196,0.15); }

        /* ── Promote confirm box ── */
        .promote-confirm-box {
            background: #eff6ff;
            border: 1px solid #93c5fd;
            border-radius: 12px;
            padding: 16px 18px;
            margin-bottom: 18px;
        }
        .promote-confirm-box .promote-title {
            font-weight: 700;
            color: #1e40af;
            margin-bottom: 10px;
            font-size: 0.95rem;
        }
        .promote-flow {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 10px;
        }
        .promote-chip {
            padding: 5px 12px;
            border-radius: 999px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .chip-graduate { background: #fee2e2; color: #b91c1c; }
        .chip-promote  { background: #dbeafe; color: #1d4ed8; }
        .chip-arrow    { color: #9ca3af; font-size: 0.75rem; }
        .promote-warning {
            margin-top: 12px;
            font-size: 0.82rem;
            color: #92400e;
            display: flex;
            align-items: flex-start;
            gap: 6px;
        }
        .promote-warning i { margin-top: 2px; flex-shrink: 0; }

        /* ── Pagination ── */
        .aStudents-pagination {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 18px;
            flex-wrap: wrap;
            gap: 10px;
        }
        .aStudents-pagination-info {
            font-size: 0.85rem;
            color: var(--text-light);
        }
        .aStudents-pagination-controls {
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .aStudents-page-btn {
            min-width: 34px;
            height: 34px;
            padding: 0 10px;
            border-radius: 8px;
            border: 1px solid rgba(37,99,235,0.15);
            background: #fff;
            color: var(--text);
            font-size: 0.85rem;
            font-weight: 500;
            cursor: pointer;
            transition: 0.15s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .aStudents-page-btn:hover:not(:disabled) { background: rgba(73,136,196,0.1); border-color: #4988C4; color: #113F67; }
        .aStudents-page-btn.active { background: linear-gradient(135deg, #113F67, #4988C4); color: #fff; border-color: transparent; font-weight: 700; }
        .aStudents-page-btn:disabled { opacity: 0.35; cursor: not-allowed; }

        /* ── Archive table states ── */
        .archive-loading { text-align: center; padding: 30px; color: #888; }
        .archive-empty   { text-align: center; padding: 30px; color: #888; }
        .archive-error   { text-align: center; padding: 30px; color: red; }

        button:disabled { opacity: 0.4; cursor: not-allowed !important; transform: none !important; }

        /* ============================================================
           CHECKBOX & BULK SELECTION
           ============================================================ */
        .aStudents-cb {
            width: 16px;
            height: 16px;
            accent-color: #113F67;
            cursor: pointer;
            flex-shrink: 0;
        }

        th.aStudents-cb-col,
        td.aStudents-cb-col {
            width: 40px;
            text-align: center;
            padding-left: 12px !important;
            padding-right: 4px !important;
        }

        tr.row-selected {
            background: rgba(73,136,196,0.08) !important;
        }
        tr.row-selected:hover {
            background: rgba(73,136,196,0.13) !important;
        }

        /* ── Bulk Action Toolbar ── */
        .aStudents-bulk-toolbar {
            display: none;
            align-items: center;
            gap: 12px;
            background: linear-gradient(135deg, #113F67, #1a5496);
            color: #fff;
            border-radius: 12px;
            padding: 10px 18px;
            margin-bottom: 14px;
            box-shadow: 0 4px 16px rgba(17,63,103,0.22);
            animation: toolbarSlide 0.2s ease;
        }
        .aStudents-bulk-toolbar.visible { display: flex; }
        @keyframes toolbarSlide {
            from { opacity: 0; transform: translateY(-6px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .bulk-toolbar-count {
            font-size: 0.88rem;
            font-weight: 700;
            white-space: nowrap;
        }
        .bulk-toolbar-count span {
            background: rgba(255,255,255,0.2);
            border-radius: 999px;
            padding: 2px 9px;
            margin-right: 4px;
            font-size: 0.82rem;
        }
        .bulk-toolbar-spacer { flex: 1; }
        .bulk-promote-btn {
            background: #fff;
            color: #15803d;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 0.85rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: 0.15s;
            white-space: nowrap;
        }
        .bulk-promote-btn:hover { background: #f0fdf4; transform: translateY(-1px); }
        .bulk-deselect-btn {
            background: rgba(255,255,255,0.15);
            color: #fff;
            border: 1px solid rgba(255,255,255,0.3);
            padding: 8px 14px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.82rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: 0.15s;
        }
        .bulk-deselect-btn:hover { background: rgba(255,255,255,0.25); }

        /* Promote selected modal summary list */
        .promote-selected-list {
            max-height: 180px;
            overflow-y: auto;
            border: 1px solid #dbeafe;
            border-radius: 10px;
            padding: 10px 14px;
            margin-bottom: 14px;
            background: #f8faff;
        }
        .promote-selected-list li {
            font-size: 0.84rem;
            color: #374151;
            padding: 3px 0;
            border-bottom: 1px solid #e5e7eb;
            list-style: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .promote-selected-list li:last-child { border-bottom: none; }
        .psl-year {
            font-size: 0.72rem;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 999px;
        }
        .psl-4th { background: #fee2e2; color: #b91c1c; }
        .psl-3rd { background: #dbeafe; color: #1d4ed8; }
        .psl-2nd { background: #dcfce7; color: #15803d; }
        .psl-1st { background: #fef9c3; color: #92400e; }
    </style>
</head>
<body>

<!-- ================= SIDEBAR ================= -->
<aside class="sidebar">
  <div class="sidebar-logoBar">
    <div class="sidebar-logo">
      <img src="logo.png" alt="logo">
      <span class="sidebar-logoText">UNITYCARE</span>
    </div>
    <div class="sidebar-settings">
      <button class="sidebar-settingsButton" onclick="toggleSettingsMenu(event)">
        <i class="fa fa-gear"></i>
      </button>
      <div class="sidebar-settingsDropdown" id="settingsDropdown">
        <button onclick="toggleTheme()"><i class="fa fa-moon"></i> Theme</button>
        <button onclick="logout()"><i class="fa fa-right-from-bracket"></i> Logout</button>
      </div>
    </div>
  </div>

  <nav class="sidebar-menu">
    <a href="admin.php"><i class="fa fa-gauge"></i> Dashboard</a>
    <p class="sidebar-title">MANAGEMENT</p>
    <a href="ausers.php"><i class="fa fa-users"></i> Users</a>
    <a href="astudents.php" class="active"><i class="fa fa-user-graduate"></i> Students</a>
    <a href="acounselors.php"><i class="fa fa-user-doctor"></i> Counselors</a>
    <a href="aappointments.php"><i class="fa fa-calendar"></i> Appointments</a>
    <p class="sidebar-title">SYSTEM</p>
    <a href="areports.php"><i class="fa fa-chart-line"></i> Reports</a>
    <a href="aauditlogs.php"><i class="fa fa-clipboard-list"></i> Audit Logs</a>
  </nav>
</aside>

<!-- ================= TOPBAR ================= -->
<header class="topbar">
    <div class="topbar-left">
        <h2>Student Records</h2>
        <p class="topbar-muted">Manage registered student accounts.</p>
    </div>
    <div class="topbar-actions">
        <input type="text" id="searchInput" class="topbar-search-input" placeholder="Search student ID or name">
        <div class="filter-wrapper">
            <button onclick="toggleFilter(event)" class="btn btn-secondary">
                <i class="fa fa-filter"></i> Filter
            </button>
            <div id="filterBox">
                <p>Course</p>
                <select id="filterCourse" onchange="loadStudents()">
                    <option value="All Courses">All Courses</option>
                    <option>AB Psychology</option>
                    <option>BSBA</option>
                    <option>BSA</option>
                    <option>BS Entrep</option>
                    <option>BEEd</option>
                    <option>BSEd</option>
                    <option>BSHM</option>
                    <option>BSIT</option>
                    <option>BSCS</option>
                    <option>BSN</option>
                    <option>BSECE</option>
                </select>
                <p>Year Level</p>
                <select id="filterYear" onchange="loadStudents()">
                    <option value="All Years">All Years</option>
                    <option>1st Year</option>
                    <option>2nd Year</option>
                    <option>3rd Year</option>
                    <option>4th Year</option>
                </select>
            </div>
        </div>
        <div class="aStudents-sort-wrapper">
            <button onclick="toggleSortDropdown(event)" class="btn btn-secondary">
                <i class="fa fa-arrow-up-wide-short"></i> Sort: <span id="sortLabel">ID (Asc)</span>
            </button>
            <div class="aStudents-sort-dropdown" id="sortDropdown">
                <div class="aStudents-sort-dropdown-label">Student ID</div>
                <div class="aStudents-sort-option active" id="sortOpt-student_id-ASC" onclick="setSort('student_id','ASC')">
                    <i class="fa fa-arrow-up"></i> ID — Ascending
                </div>
                <div class="aStudents-sort-option" id="sortOpt-student_id-DESC" onclick="setSort('student_id','DESC')">
                    <i class="fa fa-arrow-down"></i> ID — Descending
                </div>
                <div class="aStudents-sort-dropdown-label">Name</div>
                <div class="aStudents-sort-option" id="sortOpt-last_name-ASC" onclick="setSort('last_name','ASC')">
                    <i class="fa fa-arrow-up"></i> Name — A to Z
                </div>
                <div class="aStudents-sort-option" id="sortOpt-last_name-DESC" onclick="setSort('last_name','DESC')">
                    <i class="fa fa-arrow-down"></i> Name — Z to A
                </div>
                <div class="aStudents-sort-dropdown-label">Year Level</div>
                <div class="aStudents-sort-option" id="sortOpt-year_level-ASC" onclick="setSort('year_level','ASC')">
                    <i class="fa fa-arrow-up"></i> Year — 1st to 4th
                </div>
                <div class="aStudents-sort-option" id="sortOpt-year_level-DESC" onclick="setSort('year_level','DESC')">
                    <i class="fa fa-arrow-down"></i> Year — 4th to 1st
                </div>
            </div>
        </div>
    </div>
</header>

<!-- ================= MAIN ================= -->
<main class="aStudents-main">
    <section class="aStudents-card">
        <div class="aStudents-header">
            <div>
                <h3 class="aStudents-title">Student Records</h3>
                <p class="aStudents-muted">Complete list of registered students</p>
            </div>
            <div class="aStudents-record-actions">
                <button onclick="openArchivesModal()" class="aStudents-archive-btn">
                    <i class="fa fa-box-archive"></i>
                    Graduated
                    <span class="archive-count" id="archiveCountBadge"><?= $archivedCount ?></span>
                </button>

                <button onclick="openPromoteModal()" class="aStudents-promote-btn">
                    <i class="fa fa-angles-up"></i> Promote All Students
                </button>

                <div class="aStudents-csv-actions">
                    <button class="aStudents-btn-import" onclick="triggerImportCsv()">
                        <i class="fa fa-file-import"></i> Import CSV
                    </button>
                    <button class="aStudents-btn-export" onclick="exportStudentCsv()">
                        <i class="fa fa-file-export"></i> Export CSV
                    </button>
                </div>

                <button onclick="openAddStudentModal()" class="aStudents-add-btn">
                    <i class="fa fa-user-plus"></i> Add Student
                </button>
            </div>
        </div>

        <!-- ── Bulk Action Toolbar ── -->
        <div class="aStudents-bulk-toolbar" id="bulkToolbar">
            <div class="bulk-toolbar-count">
                <span id="bulkCount">0</span> student(s) selected
            </div>
            <div class="bulk-toolbar-spacer"></div>
            <button class="bulk-promote-btn" onclick="openPromoteSelectedModal()">
                <i class="fa fa-angles-up"></i> Promote Selected
            </button>
            <button class="bulk-deselect-btn" onclick="clearAllSelections()">
                <i class="fa fa-xmark"></i> Deselect All
            </button>
        </div>

        <div class="aStudents-table-wrapper">
            <table class="aStudents-table">
                <thead>
                    <tr>
                        <th class="aStudents-cb-col">
                            <input type="checkbox" class="aStudents-cb" id="selectAllCb" title="Select all on this page" onchange="toggleSelectAll(this)">
                        </th>
                        <th>Student ID</th>
                        <th>Last Name</th>
                        <th>First Name</th>
                        <th>Email</th>
                        <th>Gender</th>
                        <th>Birthday</th>
                        <th>Age</th>
                        <th>Year</th>
                        <th>Course</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="studentsTableBody">
                    <tr>
                        <td colspan="11" class="aStudents-table-loading">
                            <i class="fa fa-spinner fa-spin"></i> Loading students...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- ── Pagination ── -->
        <div class="aStudents-pagination" id="paginationWrapper" style="display:none;">
            <div class="aStudents-pagination-info" id="paginationInfo"></div>
            <div class="aStudents-pagination-controls" id="paginationControls"></div>
        </div>

    </section>
</main>


<!-- ================= ADD STUDENT MODAL ================= -->
<div id="studentModal" class="aStudents-modal">
    <div class="aStudents-modal-content">
        <div class="aStudents-modal-header">
            <div>
                <h3><i class="fa fa-user-graduate" style="margin-right:6px;opacity:.7"></i>Add New Student</h3>
                <p>Fill in all the student's information below</p>
            </div>
            <button class="aStudents-modal-close" onclick="closeStudentModal()">✕</button>
        </div>

        <div class="aStudents-sec-label">PERSONAL INFORMATION</div>
        <div class="aStudents-field-grid">
            <div class="aStudents-field">
                <label>First Name</label>
                <input type="text" id="firstName" placeholder="e.g. Juan">
            </div>
            <div class="aStudents-field">
                <label>Last Name</label>
                <input type="text" id="lastName" placeholder="e.g. Dela Cruz">
            </div>
            <div class="aStudents-field">
                <label>Gender</label>
                <select id="gender">
                    <option value="" disabled selected>Select Gender</option>
                    <option>Male</option>
                    <option>Female</option>
                    <option>Prefer not to say</option>
                </select>
            </div>
            <div class="aStudents-field">
                <label>Birthday</label>
                <input type="date" id="birthday">
            </div>
            <div class="aStudents-field">
                <label>Age</label>
                <input type="number" id="studentAge" readonly>
            </div>
        </div>

        <div class="aStudents-sec-label">ACADEMIC INFORMATION</div>
        <div class="aStudents-field-grid">
            <div class="aStudents-field">
                <label>Year Level</label>
                <select id="yearLevel">
                    <option value="" disabled selected>Select Year</option>
                    <option>1st Year</option>
                    <option>2nd Year</option>
                    <option>3rd Year</option>
                    <option>4th Year</option>
                </select>
            </div>
            <div class="aStudents-field aStudents-id-field">
                <label>Student ID</label>
                <input type="text" id="studentId" placeholder="Select a year level first" readonly>
                <span class="aStudents-id-badge" id="studentIdBadge">
                    <i class="fa fa-circle-info"></i> Auto-generated based on year level
                </span>
            </div>
            <div class="aStudents-field full">
                <label>Course</label>
                <select id="course">
                    <option value="" disabled selected>Select Course</option>
                    <option>AB Psychology</option>
                    <option>BSBA</option>
                    <option>BSA</option>
                    <option>BEEd</option>
                    <option>BSEd</option>
                    <option>BSHM</option>
                    <option>BSIT</option>
                    <option>BSCS</option>
                    <option>BSN</option>
                    <option>BSECE</option>
                </select>
            </div>
            <div class="aStudents-field full">
                <label>Email Address</label>
                <input type="email" id="email" placeholder="e.g. juan@email.com">
            </div>
        </div>

        <div class="aStudents-modal-footer">
            <button class="aStudents-btn-cancel" onclick="closeStudentModal()">Cancel</button>
            <button class="aStudents-btn-save" onclick="saveStudent()">
                <i class="fa fa-plus"></i> Save Student
            </button>
        </div>
    </div>
</div>

<input type="file" id="importCsvInput" accept=".csv" class="aStudents-hidden">


<!-- ================= VIEW / EDIT STUDENT MODAL ================= -->
<div id="viewStudentModal" class="aStudents-modal">
    <div class="aStudents-modal-content">
        <div class="aStudents-modal-header">
            <div>
                <h3><i class="fa fa-user-graduate" style="margin-right:6px;opacity:.7"></i>Student Details</h3>
                <p id="viewModalSubtitle">Viewing student information</p>
            </div>
            <button class="aStudents-modal-close" onclick="closeViewModal()">✕</button>
        </div>

        <input type="hidden" id="originalStudentId">

        <div class="aStudents-sec-label">PERSONAL INFORMATION</div>
        <div class="aStudents-field-grid">
            <div class="aStudents-field">
                <label>First Name</label>
                <input type="text" id="viewFirstName" class="readonly-field" readonly>
            </div>
            <div class="aStudents-field">
                <label>Last Name</label>
                <input type="text" id="viewLastName" class="readonly-field" readonly>
            </div>
            <div class="aStudents-field">
                <label>Gender</label>
                <input type="text" id="viewGender" class="readonly-field" readonly>
                <select id="editGender" class="aStudents-hidden">
                    <option>Male</option>
                    <option>Female</option>
                    <option>Prefer not to say</option>
                </select>
            </div>
            <div class="aStudents-field">
                <label>Birthday</label>
                <input type="text" id="viewBirthday" class="readonly-field" readonly>
                <input type="date" id="editBirthday" class="aStudents-hidden">
            </div>
            <div class="aStudents-field">
                <label>Age</label>
                <input type="text" id="viewAge" class="readonly-field" readonly>
            </div>
        </div>

        <div class="aStudents-sec-label">ACADEMIC INFORMATION</div>
        <div class="aStudents-field-grid">
            <div class="aStudents-field">
                <label>Student ID</label>
                <input type="text" id="viewStudentId" class="readonly-field" readonly>
            </div>
            <div class="aStudents-field">
                <label>Year Level</label>
                <input type="text" id="viewYear" class="readonly-field" readonly>
            </div>
            <div class="aStudents-field">
                <label>Course</label>
                <input type="text" id="viewCourse" class="readonly-field" readonly>
                <select id="editCourse" class="aStudents-hidden">
                    <option>AB Psychology</option>
                    <option>BSBA</option>
                    <option>BSA</option>
                    <option>BEEd</option>
                    <option>BSEd</option>
                    <option>BSHM</option>
                    <option>BSIT</option>
                    <option>BSCS</option>
                    <option>BSN</option>
                    <option>BSECE</option>
                </select>
            </div>
            <div class="aStudents-field">
                <label>Email Address</label>
                <input type="text" id="viewEmail" class="readonly-field" readonly>
            </div>
        </div>

        <div class="aStudents-modal-footer">
            <div class="left-actions">
                <button class="aStudents-btn-danger" id="archiveSingleBtn" onclick="archiveSingleStudent()">
                    <i class="fa fa-box-archive"></i> Archive
                </button>
            </div>
            <button class="aStudents-btn-cancel" onclick="closeViewModal()">Close</button>
            <button class="aStudents-btn-cancel" id="editBtn" onclick="enableEdit()">
                <i class="fa fa-pen"></i> Edit
            </button>
            <button class="aStudents-btn-save aStudents-hidden" id="saveEditBtn" onclick="saveEdit()">
                <i class="fa fa-floppy-disk"></i> Save
            </button>
        </div>
    </div>
</div>


<!-- ================= PROMOTE ALL MODAL ================= -->
<div id="promoteModal" class="aStudents-modal">
    <div class="aStudents-modal-content">
        <div class="aStudents-modal-header">
            <div>
                <h3><i class="fa fa-angles-up" style="margin-right:6px;opacity:.7"></i>Promote All Students</h3>
                <p>Please review what will happen before confirming</p>
            </div>
            <button class="aStudents-modal-close" onclick="closePromoteModal()">✕</button>
        </div>

        <div class="promote-confirm-box">
            <div class="promote-title"><i class="fa fa-triangle-exclamation"></i> This action will affect all active students</div>
            <div class="promote-flow">
                <span class="promote-chip chip-graduate">4th Year → Graduated &amp; Archived</span>
                <span class="chip-arrow">•</span>
                <span class="promote-chip chip-promote">3rd Year → 4th Year</span>
                <span class="chip-arrow">•</span>
                <span class="promote-chip chip-promote">2nd Year → 3rd Year</span>
                <span class="chip-arrow">•</span>
                <span class="promote-chip chip-promote">1st Year → 2nd Year</span>
            </div>
            <div class="promote-warning">
                <i class="fa fa-circle-info"></i>
                <span>4th Year students will be moved to the <strong>Graduated</strong> archive automatically.
                New incoming 1st Year students for the upcoming school year should be added manually
                using the <strong>Add Student</strong> button (they will receive the new ID prefix automatically).</span>
            </div>
        </div>

        <div class="aStudents-modal-footer">
            <button class="aStudents-btn-cancel" onclick="closePromoteModal()">Cancel</button>
            <button class="aStudents-promote-btn" id="confirmPromoteBtn" onclick="confirmPromote()">
                <i class="fa fa-angles-up"></i> Yes, Promote All
            </button>
        </div>
    </div>
</div>


<!-- ================= PROMOTE SELECTED MODAL ================= -->
<div id="promoteSelectedModal" class="aStudents-modal">
    <div class="aStudents-modal-content">
        <div class="aStudents-modal-header">
            <div>
                <h3><i class="fa fa-angles-up" style="margin-right:6px;opacity:.7"></i>Promote Selected Students</h3>
                <p id="promoteSelectedSubtitle">Review the selected students before confirming</p>
            </div>
            <button class="aStudents-modal-close" onclick="closePromoteSelectedModal()">✕</button>
        </div>

        <div class="promote-confirm-box">
            <div class="promote-title"><i class="fa fa-triangle-exclamation"></i> Each student will be promoted based on their current year level</div>
            <div class="promote-flow">
                <span class="promote-chip chip-graduate">4th Year → Graduated &amp; Archived</span>
                <span class="chip-arrow">•</span>
                <span class="promote-chip chip-promote">3rd → 4th Year</span>
                <span class="chip-arrow">•</span>
                <span class="promote-chip chip-promote">2nd → 3rd Year</span>
                <span class="chip-arrow">•</span>
                <span class="promote-chip chip-promote">1st → 2nd Year</span>
            </div>
        </div>

        <div style="font-size:0.8rem;font-weight:700;color:#4988C4;letter-spacing:.06em;margin-bottom:8px;">
            SELECTED STUDENTS
        </div>
        <ul class="promote-selected-list" id="promoteSelectedList"></ul>

        <div class="aStudents-modal-footer">
            <button class="aStudents-btn-cancel" onclick="closePromoteSelectedModal()">Cancel</button>
            <button class="aStudents-promote-btn" id="confirmPromoteSelectedBtn" onclick="confirmPromoteSelected()">
                <i class="fa fa-angles-up"></i> Yes, Promote Selected
            </button>
        </div>
    </div>
</div>


<!-- ================= ARCHIVES (GRADUATED) MODAL ================= -->
<div id="archivesModal" class="aStudents-modal">
    <div class="aStudents-modal-content wide">
        <div class="aStudents-modal-header">
            <div>
                <h3><i class="fa fa-box-archive" style="margin-right:6px;opacity:.7"></i>Graduated Students</h3>
                <p>These students have graduated. You can restore them to active records if needed.</p>
            </div>
            <button class="aStudents-modal-close" onclick="closeArchivesModal()">✕</button>
        </div>

        <input type="text" class="aArchive-search" id="archiveSearch" placeholder="Search by ID or name..." oninput="searchArchive()">

        <div class="aStudents-table-wrapper">
            <table class="aStudents-table" id="archivesTable">
                <thead>
                    <tr>
                        <th>Student ID</th>
                        <th>Last Name</th>
                        <th>First Name</th>
                        <th>Course</th>
                        <th>Gender</th>
                        <th>Birthday</th>
                        <th>Graduated</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="archivesTableBody">
                    <tr>
                        <td colspan="9" class="archive-loading">
                            <i class="fa fa-spinner fa-spin"></i> Loading...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="aStudents-modal-footer">
            <button class="aStudents-btn-cancel" onclick="closeArchivesModal()">Close</button>
        </div>
    </div>
</div>


<!-- ================= TOAST ================= -->
<div class="aStudents-toast" id="toast"></div>

<div class="logout-overlay" id="logoutOverlay">
  <div class="logout-modal">
    <div class="logout-icon">
      <i class="fa fa-right-from-bracket"></i>
    </div>
    <h3>Logout</h3>
    <p>Are you sure you want to logout?</p>
    <div class="logout-actions">
      <button class="logout-btn logout-btn--cancel" onclick="closeLogout()">Cancel</button>
      <button class="logout-btn logout-btn--confirm" onclick="confirmLogout()">Yes, Logout</button>
    </div>
  </div>
</div>


<!-- ================= SCRIPT ================= -->
<script>

// ================= SIDEBAR / THEME =================
(function() {
  const saved = localStorage.getItem("theme") || "light";
  document.documentElement.setAttribute("data-theme", saved);
})();

function toggleSettingsMenu(e) {
    e.stopPropagation();
    document.getElementById("settingsDropdown").classList.toggle("show");
}
function toggleTheme() {
  const html = document.documentElement;
  const newTheme = html.getAttribute("data-theme") === "light" ? "dark" : "light";
  html.setAttribute("data-theme", newTheme);
  localStorage.setItem("theme", newTheme);
}
function logout() {
    document.getElementById('logoutOverlay').classList.add('show');
}
function closeLogout() {
    document.getElementById('logoutOverlay').classList.remove('show');
}
function confirmLogout() {
    window.location.href = 'logout.php?role=admin';
}
document.getElementById('logoutOverlay').addEventListener('click', function(e) {
    if (e.target === this) closeLogout();
});
document.addEventListener("click", e => {
    const menu = document.getElementById("settingsDropdown");
    const btn  = document.querySelector(".sidebar-settingsButton");
    if (!menu.contains(e.target) && !btn.contains(e.target)) menu.classList.remove("show");
});

// ================= DATE FORMATTER =================
function formatBirthday(dateStr) {
    if (!dateStr) return '—';
    const [y, m, d] = dateStr.split('-');
    if (!y || !m || !d) return dateStr;
    return `${m}-${d}-${y}`;
}

// ================= TOAST =================
function showToast(msg, type = 'success') {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.style.background = type === 'error' ? '#b91c1c' : '#113F67';
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 3400);
}

// ================= SORT =================
let currentSortCol = 'student_id';
let currentSortDir = 'ASC';

const sortLabels = {
    'student_id-ASC':  'ID (Asc)',
    'student_id-DESC': 'ID (Desc)',
    'last_name-ASC':   'Name A→Z',
    'last_name-DESC':  'Name Z→A',
    'year_level-ASC':  'Year 1st→4th',
    'year_level-DESC': 'Year 4th→1st'
};

function toggleSortDropdown(e) {
    e.stopPropagation();
    document.getElementById('sortDropdown').classList.toggle('show');
}

function setSort(col, dir) {
    currentSortCol = col;
    currentSortDir = dir;
    document.getElementById('sortLabel').innerText = sortLabels[`${col}-${dir}`] || '';
    document.querySelectorAll('.aStudents-sort-option').forEach(o => o.classList.remove('active'));
    const active = document.getElementById(`sortOpt-${col}-${dir}`);
    if (active) active.classList.add('active');
    document.getElementById('sortDropdown').classList.remove('show');
    loadStudents();
}

document.addEventListener('click', e => {
    const dd = document.getElementById('sortDropdown');
    if (!e.target.closest('.aStudents-sort-wrapper')) dd.classList.remove('show');
});

// ================= SELECTION STATE =================
let selectedStudentIds = new Set();

function updateBulkToolbar() {
    const count   = selectedStudentIds.size;
    const toolbar = document.getElementById('bulkToolbar');
    document.getElementById('bulkCount').textContent = count;
    toolbar.classList.toggle('visible', count > 0);
}

function syncSelectAllCheckbox() {
    const cbs = document.querySelectorAll('#studentsTableBody .row-cb');
    if (!cbs.length) {
        document.getElementById('selectAllCb').checked       = false;
        document.getElementById('selectAllCb').indeterminate = false;
        return;
    }
    const checkedCount = [...cbs].filter(c => c.checked).length;
    const allCb = document.getElementById('selectAllCb');
    if (checkedCount === 0) {
        allCb.checked = false;
        allCb.indeterminate = false;
    } else if (checkedCount === cbs.length) {
        allCb.checked = true;
        allCb.indeterminate = false;
    } else {
        allCb.checked = false;
        allCb.indeterminate = true;
    }
}

function toggleSelectAll(masterCb) {
    const cbs = document.querySelectorAll('#studentsTableBody .row-cb');
    cbs.forEach(cb => {
        cb.checked = masterCb.checked;
        const row = cb.closest('tr');
        if (masterCb.checked) {
            selectedStudentIds.add(cb.dataset.id);
            row.classList.add('row-selected');
        } else {
            selectedStudentIds.delete(cb.dataset.id);
            row.classList.remove('row-selected');
        }
    });
    updateBulkToolbar();
}

function onRowCheckboxChange(cb) {
    const row = cb.closest('tr');
    if (cb.checked) {
        selectedStudentIds.add(cb.dataset.id);
        row.classList.add('row-selected');
    } else {
        selectedStudentIds.delete(cb.dataset.id);
        row.classList.remove('row-selected');
    }
    syncSelectAllCheckbox();
    updateBulkToolbar();
}

function clearAllSelections() {
    selectedStudentIds.clear();
    document.querySelectorAll('#studentsTableBody .row-cb').forEach(cb => {
        cb.checked = false;
        cb.closest('tr').classList.remove('row-selected');
    });
    const allCb = document.getElementById('selectAllCb');
    allCb.checked = false;
    allCb.indeterminate = false;
    updateBulkToolbar();
}

// ================= PAGINATION =================
const PAGE_SIZE = 20;
let allStudentsData = [];
let currentPage = 1;

function renderPage(page) {
    currentPage = page;
    const tbody = document.getElementById('studentsTableBody');
    tbody.innerHTML = '';

    const total = allStudentsData.length;
    const totalPages = Math.ceil(total / PAGE_SIZE);
    const start = (page - 1) * PAGE_SIZE;
    const end   = Math.min(start + PAGE_SIZE, total);
    const pageData = allStudentsData.slice(start, end);

    if (pageData.length === 0) {
        tbody.innerHTML = `<tr><td colspan="11" class="aStudents-table-empty">No students found.</td></tr>`;
        document.getElementById('paginationWrapper').style.display = 'none';
        syncSelectAllCheckbox();
        return;
    }

    pageData.forEach(s => {
        const isChecked = selectedStudentIds.has(String(s.student_id));
        const row = document.createElement('tr');
        if (isChecked) row.classList.add('row-selected');

        row.dataset.id        = s.student_id;
        row.dataset.firstName = s.first_name;
        row.dataset.lastName  = s.last_name;
        row.dataset.email     = s.email;
        row.dataset.gender    = s.gender;
        row.dataset.birthday  = s.birthday;
        row.dataset.age       = s.age;
        row.dataset.year      = s.year_level;
        row.dataset.course    = s.course;

        row.innerHTML = `
            <td class="aStudents-cb-col">
                <input type="checkbox" class="aStudents-cb row-cb"
                    data-id="${s.student_id}"
                    data-name="${s.first_name} ${s.last_name}"
                    data-year="${s.year_level}"
                    ${isChecked ? 'checked' : ''}
                    onchange="onRowCheckboxChange(this)">
            </td>
            <td>${s.student_id}</td>
            <td>${s.last_name}</td>
            <td>${s.first_name}</td>
            <td>${s.email}</td>
            <td>${s.gender}</td>
            <td>${formatBirthday(s.birthday)}</td>
            <td>${s.age}</td>
            <td>${s.year_level}</td>
            <td>${s.course}</td>
            <td>
                <button class="aStudents-btn-view" onclick="viewStudent(this)">
                    <i class="fa fa-eye"></i> View
                </button>
            </td>`;
        tbody.appendChild(row);
    });

    syncSelectAllCheckbox();
    updateBulkToolbar();

    const wrapper = document.getElementById('paginationWrapper');
    if (total <= PAGE_SIZE) {
        wrapper.style.display = 'none';
        return;
    }
    wrapper.style.display = 'flex';

    document.getElementById('paginationInfo').textContent =
        `Showing ${start + 1}–${end} of ${total} student${total !== 1 ? 's' : ''}`;

    const controls = document.getElementById('paginationControls');
    controls.innerHTML = '';

    const prevBtn = document.createElement('button');
    prevBtn.className = 'aStudents-page-btn';
    prevBtn.innerHTML = '<i class="fa fa-chevron-left"></i>';
    prevBtn.disabled  = page === 1;
    prevBtn.onclick   = () => renderPage(page - 1);
    controls.appendChild(prevBtn);

    const makePageBtn = (num) => {
        const btn = document.createElement('button');
        btn.className = 'aStudents-page-btn' + (num === page ? ' active' : '');
        btn.textContent = num;
        btn.onclick = () => renderPage(num);
        controls.appendChild(btn);
    };
    const makeEllipsis = () => {
        const span = document.createElement('span');
        span.textContent = '…';
        span.style.cssText = 'padding:0 4px;color:var(--text-light);font-size:.85rem;align-self:center;';
        controls.appendChild(span);
    };

    if (totalPages <= 7) {
        for (let i = 1; i <= totalPages; i++) makePageBtn(i);
    } else {
        makePageBtn(1);
        if (page > 3) makeEllipsis();
        const rangeStart = Math.max(2, page - 1);
        const rangeEnd   = Math.min(totalPages - 1, page + 1);
        for (let i = rangeStart; i <= rangeEnd; i++) makePageBtn(i);
        if (page < totalPages - 2) makeEllipsis();
        makePageBtn(totalPages);
    }

    const nextBtn = document.createElement('button');
    nextBtn.className = 'aStudents-page-btn';
    nextBtn.innerHTML = '<i class="fa fa-chevron-right"></i>';
    nextBtn.disabled  = page === totalPages;
    nextBtn.onclick   = () => renderPage(page + 1);
    controls.appendChild(nextBtn);
}

// ================= LOAD ACTIVE STUDENTS =================
let searchTimer = null;

function loadStudents() {
    const search    = document.getElementById('searchInput').value.trim();
    const course    = document.getElementById('filterCourse').value;
    const yearLevel = document.getElementById('filterYear').value;

    const params = new URLSearchParams({
        action:     'fetch',
        search,
        course,
        year_level: yearLevel,
        sort_col:   currentSortCol,
        sort_dir:   currentSortDir
    });

    const tbody = document.getElementById('studentsTableBody');
    tbody.innerHTML = `<tr><td colspan="11" class="aStudents-table-loading">
        <i class="fa fa-spinner fa-spin"></i> Loading...</td></tr>`;
    document.getElementById('paginationWrapper').style.display = 'none';

    fetch(`astudents.php?${params.toString()}`)
        .then(res => res.json())
        .then(json => {
            if (!json.success) {
                tbody.innerHTML = `<tr><td colspan="11" class="aStudents-table-empty">No students found.</td></tr>`;
                allStudentsData = [];
                return;
            }
            allStudentsData = json.data || [];
            renderPage(1);
        })
        .catch(() => {
            tbody.innerHTML = `<tr><td colspan="11" class="aStudents-table-error">Failed to load students.</td></tr>`;
        });
}

document.getElementById('searchInput').addEventListener('input', () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(loadStudents, 350);
});

document.addEventListener('DOMContentLoaded', loadStudents);

// ================= ADD STUDENT MODAL =================
function openAddStudentModal() {
    ['firstName', 'lastName', 'email', 'studentAge'].forEach(id => document.getElementById(id).value = '');
    document.getElementById('gender').value    = '';
    document.getElementById('birthday').value  = '';
    document.getElementById('yearLevel').value = '';
    document.getElementById('course').value    = '';
    document.getElementById('studentId').value       = '';
    document.getElementById('studentId').placeholder = 'Select a year level first';
    document.getElementById('studentIdBadge').innerHTML =
        '<i class="fa fa-circle-info"></i> Auto-generated based on year level';
    document.getElementById('studentModal').classList.add('open');
}
function closeStudentModal() {
    document.getElementById('studentModal').classList.remove('open');
}
document.getElementById('studentModal').addEventListener('click', function(e) {
    if (e.target === this) closeStudentModal();
});

// ================= AUTO-GENERATE STUDENT ID =================
document.getElementById('yearLevel').addEventListener('change', function () {
    const yearLevel = this.value;
    if (!yearLevel) return;

    const idField = document.getElementById('studentId');
    const badge   = document.getElementById('studentIdBadge');

    idField.value       = '';
    idField.placeholder = 'Generating...';
    badge.innerHTML     = '<i class="fa fa-spinner fa-spin"></i> Fetching next ID...';

    fetch(`astudents.php?action=next_id&year_level=${encodeURIComponent(yearLevel)}`)
        .then(res => res.json())
        .then(json => {
            if (json.success) {
                idField.value       = json.next_id;
                idField.placeholder = '';
                badge.innerHTML     = '<i class="fa fa-lock"></i> Auto-assigned — not editable';
            } else {
                idField.placeholder = 'Error generating ID';
                badge.innerHTML     = '<i class="fa fa-triangle-exclamation"></i> Could not generate ID';
            }
        })
        .catch(() => {
            idField.placeholder = 'Error generating ID';
            badge.innerHTML     = '<i class="fa fa-triangle-exclamation"></i> Could not generate ID';
        });
});

// ================= AGE COMPUTE =================
document.getElementById('birthday').addEventListener('change', function () {
    const age = calcAge(this.value);
    if (age === null) return;
    if (age < 17) {
        alert("Student must be at least 17 years old.");
        this.value = '';
        document.getElementById('studentAge').value = '';
        return;
    }
    document.getElementById('studentAge').value = age;
});

function calcAge(birthdayStr) {
    if (!birthdayStr) return null;
    const birthDate = new Date(birthdayStr);
    const today     = new Date();
    let age = today.getFullYear() - birthDate.getFullYear();
    const m = today.getMonth() - birthDate.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) age--;
    return isNaN(age) ? null : age;
}

// ================= SAVE STUDENT =================
function saveStudent() {
    const firstName = document.getElementById('firstName').value.trim();
    const lastName  = document.getElementById('lastName').value.trim();
    const gender    = document.getElementById('gender').value;
    const birthday  = document.getElementById('birthday').value;
    const age       = document.getElementById('studentAge').value;
    const studentId = document.getElementById('studentId').value.trim();
    const yearLevel = document.getElementById('yearLevel').value;
    const course    = document.getElementById('course').value;
    const email     = document.getElementById('email').value.trim();

    if (!yearLevel) { showToast("Please select a year level so a Student ID can be generated.", 'error'); return; }
    if (!studentId) { showToast("Student ID has not been generated yet. Please wait or re-select the year level.", 'error'); return; }
    if (!firstName || !lastName || !gender || !birthday || !course || !email) { showToast("Please fill in all required fields.", 'error'); return; }
    if (!age) { showToast("Please enter a valid birthday.", 'error'); return; }

    const formData = new FormData();
    formData.append('action',     'add');
    formData.append('student_id', studentId);
    formData.append('first_name', firstName);
    formData.append('last_name',  lastName);
    formData.append('email',      email);
    formData.append('gender',     gender);
    formData.append('birthday',   birthday);
    formData.append('year_level', yearLevel);
    formData.append('course',     course);

    fetch('astudents.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(json => {
            showToast(json.message, json.success ? 'success' : 'error');
            if (json.success) { closeStudentModal(); loadStudents(); }
        })
        .catch(() => showToast("Error saving student.", 'error'));
}

// ================= FILTER =================
function toggleFilter(event) {
    event.stopPropagation();
    document.getElementById('filterBox').classList.toggle('show');
}
document.addEventListener('click', e => {
    const box = document.getElementById('filterBox');
    if (!box.contains(e.target) && !e.target.closest('.filter-wrapper')) {
        box.classList.remove('show');
    }
});

// ================= CSV =================
function triggerImportCsv() {
    document.getElementById('importCsvInput').click();
}
function exportStudentCsv() {
    if (!allStudentsData.length) { showToast('No data to export.', 'error'); return; }
    const headers = ['Student ID','Last Name','First Name','Email','Gender','Birthday','Age','Year Level','Course'];
    const rows = [headers, ...allStudentsData.map(s => [
        s.student_id, s.last_name, s.first_name, s.email,
        s.gender, formatBirthday(s.birthday), s.age, s.year_level, s.course
    ])];
    const csv  = rows.map(r => r.map(v => `"${String(v).replace(/"/g,'""')}"`).join(',')).join('\r\n');
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'students.csv';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// ================= VIEW STUDENT =================
function viewStudent(btn) {
    const row = btn.closest('tr');

    document.getElementById('originalStudentId').value = row.dataset.id;
    document.getElementById('viewStudentId').value     = row.dataset.id;
    document.getElementById('viewFirstName').value     = row.dataset.firstName;
    document.getElementById('viewLastName').value      = row.dataset.lastName;
    document.getElementById('viewEmail').value         = row.dataset.email;
    document.getElementById('viewGender').value        = row.dataset.gender;
    document.getElementById('viewBirthday').value      = formatBirthday(row.dataset.birthday);
    document.getElementById('viewAge').value           = row.dataset.age;
    document.getElementById('viewYear').value          = row.dataset.year;
    document.getElementById('viewCourse').value        = row.dataset.course;

    setViewMode();
    document.getElementById('viewModalSubtitle').innerText =
        `Viewing info for ${row.dataset.firstName} ${row.dataset.lastName}`;
    document.getElementById('viewStudentModal').classList.add('open');
}

function setViewMode() {
    document.getElementById('viewGender').classList.remove('aStudents-hidden');
    document.getElementById('editGender').classList.add('aStudents-hidden');
    document.getElementById('viewBirthday').classList.remove('aStudents-hidden');
    document.getElementById('editBirthday').classList.add('aStudents-hidden');
    document.getElementById('viewCourse').classList.remove('aStudents-hidden');
    document.getElementById('editCourse').classList.add('aStudents-hidden');

    ['viewFirstName','viewLastName','viewEmail','viewGender','viewBirthday','viewAge','viewCourse'].forEach(id => {
        const el = document.getElementById(id);
        el.readOnly = true;
        el.classList.remove('editable-field');
        el.classList.add('readonly-field');
    });

    document.getElementById('editBtn').classList.remove('aStudents-hidden');
    document.getElementById('saveEditBtn').classList.add('aStudents-hidden');
    document.getElementById('viewModalSubtitle').innerText = 'Viewing student information';
}

function enableEdit() {
    document.getElementById('editGender').value   = document.getElementById('viewGender').value;
    const bdParts = document.getElementById('viewBirthday').value.split('/');
    document.getElementById('editBirthday').value = bdParts.length === 3
        ? `${bdParts[2]}-${bdParts[0]}-${bdParts[1]}`
        : document.getElementById('viewBirthday').value;
    document.getElementById('editCourse').value   = document.getElementById('viewCourse').value;

    document.getElementById('viewGender').classList.add('aStudents-hidden');
    document.getElementById('editGender').classList.remove('aStudents-hidden');
    document.getElementById('viewBirthday').classList.add('aStudents-hidden');
    document.getElementById('editBirthday').classList.remove('aStudents-hidden');
    document.getElementById('viewCourse').classList.add('aStudents-hidden');
    document.getElementById('editCourse').classList.remove('aStudents-hidden');

    ['viewFirstName','viewLastName','viewEmail'].forEach(id => {
        const el = document.getElementById(id);
        el.readOnly = false;
        el.classList.remove('readonly-field');
        el.classList.add('editable-field');
    });

    document.getElementById('editBtn').classList.add('aStudents-hidden');
    document.getElementById('saveEditBtn').classList.remove('aStudents-hidden');
    document.getElementById('viewModalSubtitle').innerText = 'Editing student information';
}

// ================= SAVE EDIT =================
function saveEdit() {
    const firstName  = document.getElementById('viewFirstName').value.trim();
    const lastName   = document.getElementById('viewLastName').value.trim();
    const email      = document.getElementById('viewEmail').value.trim();
    const gender     = document.getElementById('editGender').value;
    const birthday   = document.getElementById('editBirthday').value;
    const yearLevel  = document.getElementById('viewYear').value;
    const course     = document.getElementById('editCourse').value;
    const originalId = document.getElementById('originalStudentId').value;

    if (!firstName || !lastName || !email || !gender || !birthday || !yearLevel || !course) {
        showToast("Please fill in all fields.", 'error');
        return;
    }

    const formData = new FormData();
    formData.append('action',     'edit');
    formData.append('student_id', originalId);
    formData.append('first_name', firstName);
    formData.append('last_name',  lastName);
    formData.append('email',      email);
    formData.append('gender',     gender);
    formData.append('birthday',   birthday);
    formData.append('year_level', yearLevel);
    formData.append('course',     course);

    fetch('astudents.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(json => {
            showToast(json.message, json.success ? 'success' : 'error');
            if (json.success) { closeViewModal(); loadStudents(); }
        })
        .catch(() => showToast("Error updating student.", 'error'));
}

function closeViewModal() {
    document.getElementById('viewStudentModal').classList.remove('open');
    setViewMode();
}
document.getElementById('viewStudentModal').addEventListener('click', function(e) {
    if (e.target === this) closeViewModal();
});

// ================= ARCHIVE SINGLE STUDENT =================
function archiveSingleStudent() {
    const id   = document.getElementById('originalStudentId').value;
    const name = document.getElementById('viewFirstName').value + ' ' + document.getElementById('viewLastName').value;

    if (!confirm(`Archive "${name}"?\nThis will move the student to the Graduated archive.\nYou can restore them anytime.`)) return;

    const formData = new FormData();
    formData.append('action',     'archive_student');
    formData.append('student_id', id);

    fetch('astudents.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(json => {
            showToast(json.message, json.success ? 'success' : 'error');
            if (json.success) {
                closeViewModal();
                loadStudents();
                refreshArchiveCount();
            }
        })
        .catch(() => showToast("Error archiving student.", 'error'));
}

// ================= PROMOTE ALL MODAL =================
function openPromoteModal()  { document.getElementById('promoteModal').classList.add('open'); }
function closePromoteModal() { document.getElementById('promoteModal').classList.remove('open'); }
document.getElementById('promoteModal').addEventListener('click', function(e) {
    if (e.target === this) closePromoteModal();
});

function confirmPromote() {
    const btn = document.getElementById('confirmPromoteBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Processing...';

    const formData = new FormData();
    formData.append('action', 'promote_all');

    fetch('astudents.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(json => {
            showToast(json.message, json.success ? 'success' : 'error');
            if (json.success) {
                closePromoteModal();
                clearAllSelections();
                loadStudents();
                refreshArchiveCount();
            }
        })
        .catch(() => showToast("Promotion failed.", 'error'))
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa fa-angles-up"></i> Yes, Promote All';
        });
}

// ================= PROMOTE SELECTED MODAL =================
const yearBadgeClass = { '4th Year':'psl-4th','3rd Year':'psl-3rd','2nd Year':'psl-2nd','1st Year':'psl-1st' };

function openPromoteSelectedModal() {
    if (selectedStudentIds.size === 0) {
        showToast("No students selected.", 'error');
        return;
    }

    const selectedData = allStudentsData.filter(s => selectedStudentIds.has(String(s.student_id)));

    const list = document.getElementById('promoteSelectedList');
    list.innerHTML = '';
    selectedData.forEach(s => {
        const cls = yearBadgeClass[s.year_level] || '';
        const li = document.createElement('li');
        li.innerHTML = `
            <span class="psl-year ${cls}">${s.year_level}</span>
            <strong>${s.last_name}, ${s.first_name}</strong>
            <span style="color:#9ca3af;font-size:.78rem;">${s.student_id}</span>`;
        list.appendChild(li);
    });

    document.getElementById('promoteSelectedSubtitle').textContent =
        `${selectedStudentIds.size} student${selectedStudentIds.size !== 1 ? 's' : ''} selected for promotion`;

    document.getElementById('promoteSelectedModal').classList.add('open');
}

function closePromoteSelectedModal() {
    document.getElementById('promoteSelectedModal').classList.remove('open');
}
document.getElementById('promoteSelectedModal').addEventListener('click', function(e) {
    if (e.target === this) closePromoteSelectedModal();
});

function confirmPromoteSelected() {
    const btn = document.getElementById('confirmPromoteSelectedBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Processing...';

    const formData = new FormData();
    formData.append('action',      'promote_selected');
    formData.append('student_ids', [...selectedStudentIds].join(','));

    fetch('astudents.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(json => {
            showToast(json.message, json.success ? 'success' : 'error');
            if (json.success) {
                closePromoteSelectedModal();
                clearAllSelections();
                loadStudents();
                refreshArchiveCount();
            }
        })
        .catch(() => showToast("Promotion failed.", 'error'))
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa fa-angles-up"></i> Yes, Promote Selected';
        });
}

// ================= ARCHIVE COUNT BADGE =================
function refreshArchiveCount() {
    fetch('astudents.php?action=fetch_archived&search=')
        .then(res => res.json())
        .then(json => {
            if (json.success) {
                document.getElementById('archiveCountBadge').textContent = json.data.length;
            }
        })
        .catch(() => {});
}

// ================= ARCHIVES MODAL =================
let archiveSearchTimer = null;

function openArchivesModal() {
    document.getElementById('archivesModal').classList.add('open');
    document.getElementById('archiveSearch').value = '';
    loadArchivedStudents('');
}
function closeArchivesModal() {
    document.getElementById('archivesModal').classList.remove('open');
}
document.getElementById('archivesModal').addEventListener('click', function(e) {
    if (e.target === this) closeArchivesModal();
});

function searchArchive() {
    clearTimeout(archiveSearchTimer);
    archiveSearchTimer = setTimeout(() => {
        loadArchivedStudents(document.getElementById('archiveSearch').value.trim());
    }, 350);
}

function loadArchivedStudents(search) {
    const tbody = document.getElementById('archivesTableBody');
    tbody.innerHTML = `<tr><td colspan="9" class="archive-loading"><i class="fa fa-spinner fa-spin"></i> Loading...</td></tr>`;

    fetch(`astudents.php?action=fetch_archived&search=${encodeURIComponent(search)}`)
        .then(res => res.json())
        .then(json => {
            tbody.innerHTML = '';

            if (!json.success || json.data.length === 0) {
                tbody.innerHTML = `<tr><td colspan="9" class="archive-empty">
                    <i class="fa fa-box-archive" style="opacity:.3;font-size:1.5rem;display:block;margin-bottom:8px;"></i>
                    No graduated students found.
                </td></tr>`;
                return;
            }

            json.data.forEach(s => {
                const graduatedDate = s.graduated_at
                    ? new Date(s.graduated_at).toLocaleDateString('en-PH', { year:'numeric', month:'short', day:'numeric' })
                    : '—';

                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${s.student_id}</td>
                    <td>${s.last_name}</td>
                    <td>${s.first_name}</td>
                    <td>${s.course}</td>
                    <td>${s.gender}</td>
                    <td>${formatBirthday(s.birthday)}</td>
                    <td>${graduatedDate}</td>
                    <td><span class="aBadge aBadge-archived">Graduated</span></td>
                    <td>
                        <button class="aStudents-btn-restore"
                            onclick="restoreStudent(${s.student_id}, '${s.first_name.replace(/'/g,"\\'")} ${s.last_name.replace(/'/g,"\\'")}', this)">
                            <i class="fa fa-rotate-left"></i> Restore
                        </button>
                    </td>`;
                tbody.appendChild(row);
            });
        })
        .catch(() => {
            tbody.innerHTML = `<tr><td colspan="9" class="archive-error">Failed to load archived students.</td></tr>`;
        });
}

function restoreStudent(studentId, name, btn) {
    if (!confirm(`Restore "${name}" back to active student records?`)) return;

    btn.disabled  = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Restoring...';

    const formData = new FormData();
    formData.append('action',     'unarchive_student');
    formData.append('student_id', studentId);

    fetch('astudents.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(json => {
            showToast(json.message, json.success ? 'success' : 'error');
            if (json.success) {
                loadStudents();
                loadArchivedStudents(document.getElementById('archiveSearch').value.trim());
                refreshArchiveCount();
            } else {
                btn.disabled  = false;
                btn.innerHTML = '<i class="fa fa-rotate-left"></i> Restore';
            }
        })
        .catch(() => {
            showToast("Restore failed.", 'error');
            btn.disabled  = false;
            btn.innerHTML = '<i class="fa fa-rotate-left"></i> Restore';
        });
}

// ================= CSV IMPORT =================
document.getElementById('importCsvInput').addEventListener('change', function () {
    const file = this.files[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('action',   'import_csv');
    formData.append('csv_file', file);

    fetch('astudents.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(json => {
            showToast(json.message, json.success ? 'success' : 'error');
            if (json.success) loadStudents();
        })
        .catch(() => showToast("CSV import failed.", 'error'));
    this.value = '';
});
</script>
</body>
</html>