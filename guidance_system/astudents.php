<?php
// ================= DB CONNECTION =================
$host = "localhost";
$db   = "gcs_db";
$user = "root";
$pass = "";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die(json_encode(["error" => "Connection failed: " . $conn->connect_error]));
}

// ================= HANDLE AJAX REQUESTS =================
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

        $sql = "INSERT INTO students (student_id, first_name, last_name, email, gender, birthday, year_level, course)
                VALUES ($student_id, '$first_name', '$last_name', '$email', '$gender', '$birthday', '$year_level', '$course')";

        if ($conn->query($sql)) {
            echo json_encode(["success" => true, "message" => "Student added successfully."]);
        } else {
            echo json_encode(["success" => false, "message" => "Failed to add student: " . $conn->error]);
        }
        exit;
    }

    // ---------- EDIT STUDENT ----------
    if ($action === 'edit') {
        $student_id     = intval($_POST['student_id']);
        $new_student_id = intval($_POST['new_student_id']);
        $first_name     = $conn->real_escape_string(trim($_POST['first_name']));
        $last_name      = $conn->real_escape_string(trim($_POST['last_name']));
        $email          = $conn->real_escape_string(trim($_POST['email']));
        $gender         = $conn->real_escape_string($_POST['gender']);
        $birthday       = $conn->real_escape_string($_POST['birthday']);
        $year_level     = $conn->real_escape_string($_POST['year_level']);
        $course         = $conn->real_escape_string($_POST['course']);

        if ($student_id !== $new_student_id) {
            $check = $conn->query("SELECT student_id FROM students WHERE student_id = $new_student_id");
            if ($check->num_rows > 0) {
                echo json_encode(["success" => false, "message" => "New Student ID already in use."]);
                exit;
            }
        }

        $emailCheck = $conn->query("SELECT student_id FROM students WHERE email = '$email' AND student_id != $student_id");
        if ($emailCheck->num_rows > 0) {
            echo json_encode(["success" => false, "message" => "Email already in use by another student."]);
            exit;
        }

        $sql = "UPDATE students
                SET student_id = $new_student_id,
                    first_name = '$first_name',
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

    echo json_encode(["success" => false, "message" => "Unknown action."]);
    exit;
}

// ---------- FETCH STUDENTS ----------
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'fetch') {
    header('Content-Type: application/json');

    $where = "1=1";

    if (!empty($_GET['search'])) {
        $search = $conn->real_escape_string($_GET['search']);
        $where .= " AND (student_id LIKE '%$search%' OR first_name LIKE '%$search%' OR last_name LIKE '%$search%')";
    }
    if (!empty($_GET['course']) && $_GET['course'] !== 'All Courses') {
        $course = $conn->real_escape_string($_GET['course']);
        $where .= " AND course = '$course'";
    }
    if (!empty($_GET['year_level']) && $_GET['year_level'] !== 'All Years') {
        $year = $conn->real_escape_string($_GET['year_level']);
        $where .= " AND year_level = '$year'";
    }

    // Sort logic
    $allowed_sort = ['student_id', 'last_name', 'first_name', 'year_level'];
    $allowed_dir  = ['ASC', 'DESC'];
    $sort_col = isset($_GET['sort_col']) && in_array($_GET['sort_col'], $allowed_sort) ? $_GET['sort_col'] : 'student_id';
    $sort_dir = isset($_GET['sort_dir']) && in_array(strtoupper($_GET['sort_dir']), $allowed_dir) ? strtoupper($_GET['sort_dir']) : 'ASC';

    $result = $conn->query("SELECT *, TIMESTAMPDIFF(YEAR, birthday, CURDATE()) AS age FROM students WHERE $where ORDER BY $sort_col $sort_dir");

    $students = [];
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }

    echo json_encode(["success" => true, "data" => $students]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Students - UNITYCARE</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        /* ── aStudents Sort ── */
        .aStudents-sort-wrapper {
            position: relative;
            display: inline-block;
        }
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
        .aStudents-sort-option:hover { background: rgba(73,136,196,0.08); }
        .aStudents-sort-option.active {
            background: rgba(17,63,103,0.1);
            color: #113F67;
            font-weight: 600;
        }
        .aStudents-sort-option i { width: 14px; text-align: center; font-size: 0.75rem; }

        /* ── Utility ── */
        .aStudents-hidden        { display: none; }
        .aStudents-table-loading { text-align: center; padding: 20px; }
        .aStudents-table-empty   { text-align: center; padding: 20px; color: #888; }
        .aStudents-table-error   { text-align: center; padding: 20px; color: red; }
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
                <button onclick="openAddStudentModal()" class="aStudents-add-btn">
                    <i class="fa fa-user-plus"></i> Add Student
                </button>
                <div class="aStudents-csv-actions">
                    <button class="btn-import" onclick="triggerImportCsv()">
                        <i class="fa fa-file-import"></i> Import CSV
                    </button>
                    <button class="btn-export" onclick="exportStudentCsv()">
                        <i class="fa fa-file-export"></i> Export CSV
                    </button>
                </div>
            </div>
        </div>

        <div class="aStudents-table-wrapper">
            <table class="aStudents-table">
                <thead>
                    <tr>
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
                        <td colspan="10" class="aStudents-table-loading">
                            <i class="fa fa-spinner fa-spin"></i> Loading students...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
</main>

<!-- ================= ADD STUDENT MODAL ================= -->
<div id="studentModal" class="aStudents-modal">
    <div class="aStudents-modal-content">
        <div class="aStudents-modal-header">
            <div>
                <h3>Add New Student</h3>
                <p>Fill in all the student's information below</p>
            </div>
            <button class="aStudents-modal-close" onclick="closeStudentModal()">✕</button>
        </div>
        <div class="aStudents-modal-body">
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
                    <label>Student ID</label>
                    <input type="text" id="studentId" placeholder="e.g. 240001">
                </div>
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
        </div>
        <div class="aStudents-modal-footer">
            <button class="aStudents-btn-cancel" onclick="closeStudentModal()">Cancel</button>
            <button class="aStudents-btn-save" onclick="saveStudent()">Save Student</button>
        </div>
    </div>
</div>

<input type="file" id="importCsvInput" accept=".csv" class="aStudents-hidden">

<!-- ================= VIEW / EDIT STUDENT MODAL ================= -->
<div id="viewStudentModal" class="aStudents-modal">
    <div class="aStudents-modal-content">
        <div class="aStudents-modal-header">
            <div>
                <h3>Student Details</h3>
                <p id="viewModalSubtitle">Viewing student information</p>
            </div>
            <button class="aStudents-modal-close" onclick="closeViewModal()">✕</button>
        </div>
        <div class="aStudents-modal-body">

            <input type="hidden" id="originalStudentId">

            <div class="aStudents-sec-label">PERSONAL INFORMATION</div>
            <div class="aStudents-field-grid">
                <div class="aStudents-field">
                    <label>First Name</label>
                    <input type="text" id="viewFirstName" readonly>
                </div>
                <div class="aStudents-field">
                    <label>Last Name</label>
                    <input type="text" id="viewLastName" readonly>
                </div>
                <div class="aStudents-field">
                    <label>Gender</label>
                    <input type="text" id="viewGender" readonly>
                    <select id="editGender" class="aStudents-hidden">
                        <option>Male</option>
                        <option>Female</option>
                        <option>Prefer not to say</option>
                    </select>
                </div>
                <div class="aStudents-field">
                    <label>Birthday</label>
                    <input type="text" id="viewBirthday" readonly>
                    <input type="date" id="editBirthday" class="aStudents-hidden">
                </div>
                <div class="aStudents-field">
                    <label>Age</label>
                    <input type="text" id="viewAge" readonly>
                </div>
            </div>

            <div class="aStudents-sec-label">ACADEMIC INFORMATION</div>
            <div class="aStudents-field-grid">
                <div class="aStudents-field">
                    <label>Student ID</label>
                    <input type="text" id="viewStudentId" readonly>
                </div>
                <div class="aStudents-field">
                    <label>Year Level</label>
                    <input type="text" id="viewYear" readonly>
                    <select id="editYear" class="aStudents-hidden">
                        <option>1st Year</option>
                        <option>2nd Year</option>
                        <option>3rd Year</option>
                        <option>4th Year</option>
                    </select>
                </div>
                <div class="aStudents-field">
                    <label>Course</label>
                    <input type="text" id="viewCourse" readonly>
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
                    <input type="text" id="viewEmail" readonly>
                </div>
            </div>
        </div>

        <div class="aStudents-modal-footer">
            <button class="aStudents-btn-cancel" onclick="closeViewModal()">Close</button>
            <button class="aStudents-btn-cancel" id="editBtn" onclick="enableEdit()">
                <i class="fa fa-pen"></i> Edit
            </button>
            <button class="aStudents-btn-save aStudents-hidden" id="saveEditBtn" onclick="saveEdit()">
                Save Changes
            </button>
        </div>
    </div>
</div>

<!-- ================= SCRIPT ================= -->
<script>

// ================= SIDEBAR / THEME =================
function toggleSettingsMenu(e) {
    e.stopPropagation();
    document.getElementById("settingsDropdown").classList.toggle("show");
}
function toggleTheme() {
    const html = document.documentElement;
    html.setAttribute("data-theme", html.getAttribute("data-theme") === "light" ? "dark" : "light");
}
function logout() {
    localStorage.clear();
    window.location.href = "index.php";
}
document.addEventListener("click", e => {
    const menu = document.getElementById("settingsDropdown");
    const btn  = document.querySelector(".sidebar-settingsButton");
    if (!menu.contains(e.target) && !btn.contains(e.target)) menu.classList.remove("show");
});

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

// Close sort dropdown on outside click
document.addEventListener('click', e => {
    const dd = document.getElementById('sortDropdown');
    if (!e.target.closest('.aStudents-sort-wrapper')) dd.classList.remove('show');
});

// ================= LOAD STUDENTS FROM DB =================
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
    tbody.innerHTML = `<tr><td colspan="10" class="aStudents-table-loading">
        <i class="fa fa-spinner fa-spin"></i> Loading...</td></tr>`;

    fetch(`astudents.php?${params.toString()}`)
        .then(res => res.json())
        .then(json => {
            tbody.innerHTML = '';
            if (!json.success || json.data.length === 0) {
                tbody.innerHTML = `<tr><td colspan="10" class="aStudents-table-empty">No students found.</td></tr>`;
                return;
            }
            json.data.forEach(s => {
                const row = document.createElement('tr');
                // Store all fields as data attributes for easy access in viewStudent()
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
                    <td>${s.student_id}</td>
                    <td>${s.last_name}</td>
                    <td>${s.first_name}</td>
                    <td>${s.email}</td>
                    <td>${s.gender}</td>
                    <td>${s.birthday}</td>
                    <td>${s.age}</td>
                    <td>${s.year_level}</td>
                    <td>${s.course}</td>
                    <td>
                        <button class="aStudents-btn aStudents-btn-sm" onclick="viewStudent(this)">View</button>
                    </td>`;
                tbody.appendChild(row);
            });
        })
        .catch(() => {
            tbody.innerHTML = `<tr><td colspan="10" class="aStudents-table-error">Failed to load students.</td></tr>`;
        });
}

document.getElementById('searchInput').addEventListener('input', () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(loadStudents, 350);
});

document.addEventListener('DOMContentLoaded', loadStudents);

// ================= ADD STUDENT MODAL =================
function openAddStudentModal() {
    ['firstName','lastName','email','studentId','studentAge'].forEach(id => document.getElementById(id).value = '');
    document.getElementById('gender').value    = '';
    document.getElementById('birthday').value  = '';
    document.getElementById('yearLevel').value = '';
    document.getElementById('course').value    = '';
    document.getElementById('studentModal').classList.add('open');
}
function closeStudentModal() {
    document.getElementById('studentModal').classList.remove('open');
}
document.getElementById('studentModal').addEventListener('click', function(e) {
    if (e.target === this) closeStudentModal();
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

    if (!firstName || !lastName || !gender || !birthday || !studentId || !yearLevel || !course || !email) {
        alert("Please fill in all required fields.");
        return;
    }
    if (!age) {
        alert("Please enter a valid birthday.");
        return;
    }

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
            alert(json.message);
            if (json.success) { closeStudentModal(); loadStudents(); }
        })
        .catch(() => alert("Error saving student."));
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
    const table = document.querySelector('.aStudents-table');
    const rows  = Array.from(table.querySelectorAll('thead tr, tbody tr'));
    const csv   = rows.map(row => {
        const cells = Array.from(row.querySelectorAll('th, td')).slice(0, 9); // exclude Actions col
        return cells.map(cell => `"${cell.innerText.replace(/"/g, '""')}"`).join(',');
    }).join('\r\n');
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'students.csv';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// ================= VIEW STUDENT =================
// Reads from data attributes set during row render — no column index guessing.
function viewStudent(btn) {
    const row = btn.closest('tr');

    document.getElementById('originalStudentId').value = row.dataset.id;
    document.getElementById('viewStudentId').value     = row.dataset.id;
    document.getElementById('viewFirstName').value     = row.dataset.firstName;
    document.getElementById('viewLastName').value      = row.dataset.lastName;
    document.getElementById('viewEmail').value         = row.dataset.email;
    document.getElementById('viewGender').value        = row.dataset.gender;
    document.getElementById('viewBirthday').value      = row.dataset.birthday;
    document.getElementById('viewAge').value           = row.dataset.age;
    document.getElementById('viewYear').value          = row.dataset.year;
    document.getElementById('viewCourse').value        = row.dataset.course;

    setViewMode();
    document.getElementById('viewStudentModal').classList.add('open');
}

function setViewMode() {
    document.getElementById('viewGender').classList.remove('aStudents-hidden');
    document.getElementById('editGender').classList.add('aStudents-hidden');
    document.getElementById('viewBirthday').classList.remove('aStudents-hidden');
    document.getElementById('editBirthday').classList.add('aStudents-hidden');
    document.getElementById('viewYear').classList.remove('aStudents-hidden');
    document.getElementById('editYear').classList.add('aStudents-hidden');
    document.getElementById('viewCourse').classList.remove('aStudents-hidden');
    document.getElementById('editCourse').classList.add('aStudents-hidden');

    document.getElementById('viewFirstName').readOnly  = true;
    document.getElementById('viewLastName').readOnly   = true;
    document.getElementById('viewEmail').readOnly      = true;
    document.getElementById('viewStudentId').readOnly  = true;
    document.getElementById('viewAge').readOnly        = true;

    document.getElementById('editBtn').classList.remove('aStudents-hidden');
    document.getElementById('saveEditBtn').classList.add('aStudents-hidden');
    document.getElementById('viewModalSubtitle').innerText = 'Viewing student information';
}

function enableEdit() {
    document.getElementById('editGender').value   = document.getElementById('viewGender').value;
    document.getElementById('editBirthday').value = document.getElementById('viewBirthday').value;
    document.getElementById('editYear').value     = document.getElementById('viewYear').value;
    document.getElementById('editCourse').value   = document.getElementById('viewCourse').value;

    document.getElementById('viewGender').classList.add('aStudents-hidden');
    document.getElementById('editGender').classList.remove('aStudents-hidden');
    document.getElementById('viewBirthday').classList.add('aStudents-hidden');
    document.getElementById('editBirthday').classList.remove('aStudents-hidden');
    document.getElementById('viewYear').classList.add('aStudents-hidden');
    document.getElementById('editYear').classList.remove('aStudents-hidden');
    document.getElementById('viewCourse').classList.add('aStudents-hidden');
    document.getElementById('editCourse').classList.remove('aStudents-hidden');

    document.getElementById('viewFirstName').readOnly  = false;
    document.getElementById('viewLastName').readOnly   = false;
    document.getElementById('viewEmail').readOnly      = false;
    document.getElementById('viewStudentId').readOnly  = false;

    document.getElementById('editBtn').classList.add('aStudents-hidden');
    document.getElementById('saveEditBtn').classList.remove('aStudents-hidden');
    document.getElementById('viewModalSubtitle').innerText = 'Editing student information';
}

// ================= SAVE EDIT =================
function saveEdit() {
    const firstName    = document.getElementById('viewFirstName').value.trim();
    const lastName     = document.getElementById('viewLastName').value.trim();
    const newStudentId = document.getElementById('viewStudentId').value.trim();
    const email        = document.getElementById('viewEmail').value.trim();
    const gender       = document.getElementById('editGender').value;
    const birthday     = document.getElementById('editBirthday').value;
    const yearLevel    = document.getElementById('editYear').value;
    const course       = document.getElementById('editCourse').value;
    const originalId   = document.getElementById('originalStudentId').value;

    if (!firstName || !lastName || !newStudentId || !email || !gender || !birthday || !yearLevel || !course) {
        alert("Please fill in all fields.");
        return;
    }

    const formData = new FormData();
    formData.append('action',         'edit');
    formData.append('student_id',     originalId);
    formData.append('new_student_id', newStudentId);
    formData.append('first_name',     firstName);
    formData.append('last_name',      lastName);
    formData.append('email',          email);
    formData.append('gender',         gender);
    formData.append('birthday',       birthday);
    formData.append('year_level',     yearLevel);
    formData.append('course',         course);

    fetch('astudents.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(json => {
            alert(json.message);
            if (json.success) { closeViewModal(); loadStudents(); }
        })
        .catch(() => alert("Error updating student."));
}

function closeViewModal() {
    document.getElementById('viewStudentModal').classList.remove('open');
    setViewMode();
}
document.getElementById('viewStudentModal').addEventListener('click', function(e) {
    if (e.target === this) closeViewModal();
});

</script>
</body>
</html>