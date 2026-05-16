<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: slogin.php");
    exit;
}

$conn = new mysqli("localhost", "System_User", "gcs_db2026", "gcs_db");

// ── FILTERS ──
$role       = $_GET['role']        ?? '';
$action     = $_GET['action']      ?? '';
$search     = $_GET['search']      ?? '';
$date_from  = $_GET['date_from']   ?? '';
$date_to    = $_GET['date_to']     ?? '';
$page       = max(1, (int)($_GET['page'] ?? 1));
$per_page   = 20;
$offset     = ($page - 1) * $per_page;

// ── BUILD WHERE CLAUSE ──
$where   = [];
$params  = [];
$types   = '';

if ($role)   { $where[] = "al.role = ?";        $params[] = $role;   $types .= 's'; }
if ($action) { $where[] = "al.action_type = ?";  $params[] = $action; $types .= 's'; }
if ($search) {
    $where[]  = "(al.description LIKE ? OR al.table_name LIKE ? OR al.user_id LIKE ?)";
    $like     = "%$search%";
    $params[] = $like; $params[] = $like; $params[] = $like;
    $types   .= 'sss';
}
if ($date_from) { $where[] = "DATE(al.action_time) >= ?"; $params[] = $date_from; $types .= 's'; }
if ($date_to)   { $where[] = "DATE(al.action_time) <= ?"; $params[] = $date_to;   $types .= 's'; }

$sql_where = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// ── TOTAL COUNT ──
$count_sql  = "SELECT COUNT(*) AS c FROM audit_log al $sql_where";
$count_stmt = $conn->prepare($count_sql);
if ($types) $count_stmt->bind_param($types, ...$params);
$count_stmt->execute();
$total_rows = $count_stmt->get_result()->fetch_assoc()['c'];
$total_pages = max(1, ceil($total_rows / $per_page));
$count_stmt->close();

// ── FETCH LOGS ──
$logs_sql  = "SELECT al.* FROM audit_log al $sql_where ORDER BY al.action_time DESC LIMIT ? OFFSET ?";
$logs_stmt = $conn->prepare($logs_sql);
$page_types  = $types . 'ii';
$page_params = array_merge($params, [$per_page, $offset]);
$logs_stmt->bind_param($page_types, ...$page_params);
$logs_stmt->execute();
$logs = $logs_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$logs_stmt->close();

// ── SUMMARY COUNTS (for header cards) ──
$summary = [];
$res = $conn->query("SELECT role, COUNT(*) AS c FROM audit_log GROUP BY role");
while ($r = $res->fetch_assoc()) $summary[$r['role']] = $r['c'];

$actionRes = $conn->query("SELECT action_type, COUNT(*) AS c FROM audit_log GROUP BY action_type");
$actionCounts = [];
while ($r = $actionRes->fetch_assoc()) $actionCounts[$r['action_type']] = $r['c'];

$conn->close();

// ── HELPERS ──
function badge_role(string $role): string {
    return match($role) {
        'admin'     => '<span class="aAudit-badge aAudit-badge--admin">Admin</span>',
        'counselor' => '<span class="aAudit-badge aAudit-badge--counselor">Counselor</span>',
        'student'   => '<span class="aAudit-badge aAudit-badge--student">Student</span>',
        default     => "<span class='aAudit-badge'>$role</span>",
    };
}

function badge_action(string $action): string {
    return match($action) {
        'INSERT' => '<span class="aAudit-badge aAudit-badge--insert">INSERT</span>',
        'UPDATE' => '<span class="aAudit-badge aAudit-badge--update">UPDATE</span>',
        'DELETE' => '<span class="aAudit-badge aAudit-badge--delete">DELETE</span>',
        default  => "<span class='aAudit-badge'>$action</span>",
    };
}

function icon_table(string $t): string {
    return match(true) {
        str_contains($t, 'student')     => 'fa-user-graduate',
        str_contains($t, 'counselor')   => 'fa-user-doctor',
        str_contains($t, 'appointment') => 'fa-calendar',
        str_contains($t, 'concern')     => 'fa-comment-dots',
        str_contains($t, 'wellness')    => 'fa-heart-pulse',
        str_contains($t, 'feedback')    => 'fa-star',
        str_contains($t, 'referral')    => 'fa-file-medical',
        str_contains($t, 'announce')    => 'fa-bullhorn',
        str_contains($t, 'admin')       => 'fa-shield',
        str_contains($t, 'session')     => 'fa-notes-medical',
        default                         => 'fa-database',
    };
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>UNITYCARE | Audit Logs</title>
<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="logout.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
/* ========== AUDIT LOGS PAGE STYLES ========== */
.aAudit-main {
  background: var(--bg);
  margin-left: 280px;
  padding: var(--spacing-xl);
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  gap: var(--spacing-lg);
  box-sizing: border-box;
}

/* ── Summary Cards ── */
.aAudit-summary {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
  gap: 16px;
}

.aAudit-summary-card {
  position: relative;
  background: var(--card);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 18px 20px;
  box-shadow: var(--shadow);
  backdrop-filter: blur(14px);
  overflow: hidden;
  transition: var(--transition);
}

.aAudit-summary-card::after {
  content: "";
  position: absolute;
  inset: 0;
  background: radial-gradient(circle at top left, var(--glow), transparent 60%);
  pointer-events: none;
}

.aAudit-summary-card:hover {
  transform: translateY(-3px);
  box-shadow: var(--shadow-hover);
}

.aAudit-summary-card .aAudit-sc-label {
  font-size: 12px;
  color: var(--text-muted);
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.4px;
  margin-bottom: 6px;
  display: flex;
  align-items: center;
  gap: 6px;
}

.aAudit-summary-card .aAudit-sc-num {
  font-size: 28px;
  font-weight: 700;
  color: var(--text);
  line-height: 1;
}

/* ── Filter Bar ── */
.aAudit-filter-card {
  position: relative;
  background: var(--card);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  padding: var(--spacing-lg);
  box-shadow: var(--shadow);
  backdrop-filter: blur(14px);
  overflow: hidden;
}

.aAudit-filter-card::after {
  content: "";
  position: absolute;
  inset: 0;
  background: radial-gradient(circle at top left, var(--glow), transparent 60%);
  pointer-events: none;
}

.aAudit-filter-row {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
  align-items: flex-end;
}

.aAudit-filter-row .aAudit-fg {
  display: flex;
  flex-direction: column;
  gap: 5px;
  flex: 1;
  min-width: 140px;
}

.aAudit-filter-row .aAudit-fg label {
  font-size: 11px;
  font-weight: 700;
  color: var(--text-muted);
  text-transform: uppercase;
  letter-spacing: 0.4px;
}

.aAudit-filter-row input,
.aAudit-filter-row select {
  padding: 10px 12px;
  border-radius: var(--radius);
  border: 1px solid var(--border);
  background: var(--bg);
  color: var(--text);
  font-size: 13px;
  outline: none;
  transition: var(--transition);
  width: 100%;
}

.aAudit-filter-row input:focus,
.aAudit-filter-row select:focus {
  border-color: var(--primary);
  box-shadow: 0 0 0 3px var(--focus);
}

.aAudit-filter-btns {
  display: flex;
  gap: 8px;
  align-items: flex-end;
  padding-bottom: 1px;
}

.aAudit-btn-apply {
  padding: 10px 18px;
  border-radius: var(--radius);
  border: none;
  background: linear-gradient(135deg, #113F67, #4988C4);
  color: white;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: var(--transition);
  white-space: nowrap;
}

.aAudit-btn-apply:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow);
}

.aAudit-btn-clear {
  padding: 10px 14px;
  border-radius: var(--radius);
  border: 1px solid var(--border);
  background: transparent;
  color: var(--text-muted);
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: var(--transition);
  white-space: nowrap;
}

.aAudit-btn-clear:hover {
  background: var(--hover);
  color: var(--text);
}

/* ── Table Card ── */
.aAudit-table-card {
  position: relative;
  background: var(--card);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  padding: var(--spacing-lg);
  box-shadow: var(--shadow);
  backdrop-filter: blur(14px);
  overflow: hidden;
}

.aAudit-table-card::after {
  content: "";
  position: absolute;
  inset: 0;
  background: radial-gradient(circle at top left, var(--glow), transparent 60%);
  pointer-events: none;
}

.aAudit-table-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
  flex-wrap: wrap;
  gap: 10px;
}

.aAudit-table-header h3 {
  font-size: 16px;
  font-weight: 700;
  color: var(--text);
  margin: 0;
}

.aAudit-table-header span {
  font-size: 13px;
  color: var(--text-muted);
}

.aAudit-table-wrapper {
  overflow-x: auto;
}

.aAudit-table {
  width: 100%;
  border-collapse: collapse;
  min-width: 750px;
}

.aAudit-table thead {
  background: rgba(37, 99, 235, 0.04);
}

.aAudit-table th {
  text-align: left;
  padding: 12px 14px;
  font-size: 12px;
  font-weight: 700;
  color: var(--text-muted);
  text-transform: uppercase;
  letter-spacing: 0.4px;
  border-bottom: 1px solid var(--border);
}

.aAudit-table td {
  padding: 13px 14px;
  font-size: 13px;
  color: var(--text);
  border-bottom: 1px solid var(--border-light);
  vertical-align: middle;
}

.aAudit-table tbody tr {
  transition: var(--transition-fast);
}

.aAudit-table tbody tr:hover {
  background: var(--hover);
}

.aAudit-table tbody tr:last-child td {
  border-bottom: none;
}

/* ── Table icon + description ── */
.aAudit-desc-row {
  display: flex;
  align-items: center;
  gap: 8px;
}

.aAudit-desc-icon {
  width: 28px;
  height: 28px;
  border-radius: 8px;
  background: rgba(73, 136, 196, 0.12);
  color: var(--primary);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  flex-shrink: 0;
}

.aAudit-table-tag {
  font-size: 11px;
  color: var(--text-muted);
  margin-top: 2px;
}

/* ── Badges ── */
.aAudit-badge {
  display: inline-flex;
  align-items: center;
  padding: 4px 10px;
  border-radius: 999px;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.2px;
  text-transform: uppercase;
}

.aAudit-badge--admin     { background: rgba(239,68,68,0.12);  color: #b91c1c; }
.aAudit-badge--counselor { background: rgba(245,158,11,0.12); color: #b45309; }
.aAudit-badge--student   { background: rgba(34,197,94,0.12);  color: #15803d; }
.aAudit-badge--insert    { background: rgba(34,197,94,0.12);  color: #15803d; }
.aAudit-badge--update    { background: rgba(59,130,246,0.12); color: #1d4ed8; }
.aAudit-badge--delete    { background: rgba(239,68,68,0.12);  color: #b91c1c; }

/* ── Timestamp ── */
.aAudit-time {
  font-size: 12px;
  color: var(--text-muted);
  white-space: nowrap;
}

/* ── Empty State ── */
.aAudit-empty {
  text-align: center;
  padding: 48px 20px;
  color: var(--text-muted);
}

.aAudit-empty i {
  font-size: 36px;
  margin-bottom: 12px;
  opacity: 0.4;
  display: block;
}

/* ── Pagination ── */
.aAudit-pagination {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 18px;
  flex-wrap: wrap;
  gap: 10px;
}

.aAudit-pagination-info {
  font-size: 13px;
  color: var(--text-muted);
}

.aAudit-pagination-btns {
  display: flex;
  gap: 6px;
}

.aAudit-page-btn {
  width: 34px;
  height: 34px;
  border-radius: var(--radius-sm);
  border: 1px solid var(--border);
  background: var(--card);
  color: var(--text);
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: var(--transition-fast);
  text-decoration: none;
}

.aAudit-page-btn:hover {
  background: var(--hover);
  border-color: var(--primary);
}

.aAudit-page-btn.active {
  background: linear-gradient(135deg, #113F67, #4988C4);
  color: white;
  border-color: transparent;
}

.aAudit-page-btn.disabled {
  opacity: 0.4;
  pointer-events: none;
}

[data-theme="dark"] .aAudit-filter-row input,
[data-theme="dark"] .aAudit-filter-row select {
  background: rgba(255,255,255,0.05);
  border-color: var(--border);
  color: var(--text);
}

[data-theme="dark"] .aAudit-desc-icon {
  background: rgba(96,165,250,0.12);
  color: #60a5fa;
}

@media (max-width: 768px) {
  .aAudit-main { margin-left: 0; }
  .aAudit-filter-row { flex-direction: column; }
  .aAudit-filter-btns { width: 100%; }
  .aAudit-btn-apply,
  .aAudit-btn-clear { flex: 1; justify-content: center; }
}
</style>
</head>

<body class="body">

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
    <a href="astudents.php"><i class="fa fa-user-graduate"></i> Students</a>
    <a href="acounselors.php"><i class="fa fa-user-doctor"></i> Counselors</a>
    <a href="aappointments.php"><i class="fa fa-calendar"></i> Appointments</a>
    <p class="sidebar-title">SYSTEM</p>
    <a href="areports.php"><i class="fa fa-chart-line"></i> Reports</a>
    <a href="aauditlogs.php" class="active"><i class="fa fa-clipboard-list"></i> Audit Logs</a>
  </nav>
</aside>

<!-- ================= TOPBAR ================= -->
<header class="topbar">
  <div class="topbar-left">
    <h2>Audit Logs</h2>
    <p class="topbar-muted">Track every action taken across the system — who did what and when</p>
  </div>
  <div class="aDashboard-live-status">
    <span class="aDashboard-pulse"></span>
    System Active
  </div>
</header>

<!-- ================= MAIN ================= -->
<main class="aAudit-main">

  <!-- ── Summary Cards ── -->
  <div class="aAudit-summary">
    <div class="aAudit-summary-card">
      <div class="aAudit-sc-label"><i class="fa fa-list-check"></i> Total Logs</div>
      <div class="aAudit-sc-num"><?= number_format($total_rows) ?></div>
    </div>
    <div class="aAudit-summary-card">
      <div class="aAudit-sc-label"><i class="fa fa-user-graduate"></i> By Students</div>
      <div class="aAudit-sc-num"><?= number_format($summary['student'] ?? 0) ?></div>
    </div>
    <div class="aAudit-summary-card">
      <div class="aAudit-sc-label"><i class="fa fa-user-doctor"></i> By Counselors</div>
      <div class="aAudit-sc-num"><?= number_format($summary['counselor'] ?? 0) ?></div>
    </div>
    <div class="aAudit-summary-card">
      <div class="aAudit-sc-label"><i class="fa fa-shield"></i> By Admins</div>
      <div class="aAudit-sc-num"><?= number_format($summary['admin'] ?? 0) ?></div>
    </div>
    <div class="aAudit-summary-card">
      <div class="aAudit-sc-label"><i class="fa fa-plus-circle"></i> Inserts</div>
      <div class="aAudit-sc-num"><?= number_format($actionCounts['INSERT'] ?? 0) ?></div>
    </div>
    <div class="aAudit-summary-card">
      <div class="aAudit-sc-label"><i class="fa fa-pen"></i> Updates</div>
      <div class="aAudit-sc-num"><?= number_format($actionCounts['UPDATE'] ?? 0) ?></div>
    </div>
  </div>

  <!-- ── Filter Bar ── -->
  <div class="aAudit-filter-card">
    <form method="GET" action="aauditlogs.php">
      <div class="aAudit-filter-row">

        <div class="aAudit-fg" style="flex:2; min-width:200px;">
          <label>Search</label>
          <input type="text" name="search" placeholder="Description, table, or user ID…"
                 value="<?= htmlspecialchars($search) ?>">
        </div>

        <div class="aAudit-fg">
          <label>Role</label>
          <select name="role">
            <option value="">All Roles</option>
            <option value="student"   <?= $role === 'student'   ? 'selected' : '' ?>>Student</option>
            <option value="counselor" <?= $role === 'counselor' ? 'selected' : '' ?>>Counselor</option>
            <option value="admin"     <?= $role === 'admin'     ? 'selected' : '' ?>>Admin</option>
          </select>
        </div>

        <div class="aAudit-fg">
          <label>Action</label>
          <select name="action">
            <option value="">All Actions</option>
            <option value="INSERT" <?= $action === 'INSERT' ? 'selected' : '' ?>>INSERT</option>
            <option value="UPDATE" <?= $action === 'UPDATE' ? 'selected' : '' ?>>UPDATE</option>
            <option value="DELETE" <?= $action === 'DELETE' ? 'selected' : '' ?>>DELETE</option>
          </select>
        </div>

        <div class="aAudit-fg">
          <label>Date From</label>
          <input type="date" name="date_from" value="<?= htmlspecialchars($date_from) ?>">
        </div>

        <div class="aAudit-fg">
          <label>Date To</label>
          <input type="date" name="date_to" value="<?= htmlspecialchars($date_to) ?>">
        </div>

        <div class="aAudit-filter-btns">
          <button type="submit" class="aAudit-btn-apply"><i class="fa fa-filter"></i> Filter</button>
          <a href="aauditlogs.php" class="aAudit-btn-clear">Clear</a>
        </div>

      </div>
    </form>
  </div>

  <!-- ── Logs Table ── -->
  <div class="aAudit-table-card">

    <div class="aAudit-table-header">
      <h3><i class="fa fa-clipboard-list" style="color:var(--primary); margin-right:8px;"></i>Activity Log</h3>
      <span>
        Showing <?= number_format(min($offset + 1, $total_rows)) ?>–<?= number_format(min($offset + $per_page, $total_rows)) ?>
        of <?= number_format($total_rows) ?> entries
      </span>
    </div>

    <div class="aAudit-table-wrapper">
      <table class="aAudit-table">
        <thead>
          <tr>
            <th>#</th>
            <th>User ID</th>
            <th>Role</th>
            <th>Action</th>
            <th>Description</th>
            <th>Timestamp</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($logs)): ?>
            <tr>
              <td colspan="6">
                <div class="aAudit-empty">
                  <i class="fa fa-magnifying-glass"></i>
                  No log entries found for the current filters.
                </div>
              </td>
            </tr>
          <?php else: foreach ($logs as $i => $log): ?>
            <tr>
              <td style="color:var(--text-muted); font-size:12px;"><?= $log['log_id'] ?></td>

              <td>
                <strong><?= htmlspecialchars($log['user_id'] ?? '—') ?></strong>
              </td>

              <td><?= badge_role($log['role'] ?? '') ?></td>

              <td><?= badge_action($log['action_type'] ?? '') ?></td>

              <td>
                <div class="aAudit-desc-row">
                  <div class="aAudit-desc-icon">
                    <i class="fa <?= icon_table($log['table_name'] ?? '') ?>"></i>
                  </div>
                  <div>
                    <div><?= htmlspecialchars($log['description'] ?? '—') ?></div>
                    <div class="aAudit-table-tag"><?= htmlspecialchars($log['table_name'] ?? '') ?> · record #<?= htmlspecialchars($log['record_id'] ?? '—') ?></div>
                  </div>
                </div>
              </td>

              <td class="aAudit-time">
                <?php
                  $dt = new DateTime($log['action_time']);
                  echo $dt->format('M d, Y') . '<br>' . $dt->format('h:i A');
                ?>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>

    <!-- ── Pagination ── -->
    <?php if ($total_pages > 1): ?>
    <div class="aAudit-pagination">
      <div class="aAudit-pagination-info">
        Page <?= $page ?> of <?= $total_pages ?>
      </div>
      <div class="aAudit-pagination-btns">
        <?php
          // Build query string preserving filters
          $base = http_build_query(array_filter([
            'search'    => $search,
            'role'      => $role,
            'action'    => $action,
            'date_from' => $date_from,
            'date_to'   => $date_to,
          ]));

          // Prev
          $prev_class = $page <= 1 ? 'disabled' : '';
          echo "<a href='?$base&page=" . max(1, $page - 1) . "' class='aAudit-page-btn $prev_class'><i class='fa fa-chevron-left'></i></a>";

          // Page numbers (window of 5)
          $start = max(1, $page - 2);
          $end   = min($total_pages, $page + 2);
          if ($start > 1) echo "<span class='aAudit-page-btn disabled'>…</span>";
          for ($p = $start; $p <= $end; $p++) {
              $active = $p === $page ? 'active' : '';
              echo "<a href='?$base&page=$p' class='aAudit-page-btn $active'>$p</a>";
          }
          if ($end < $total_pages) echo "<span class='aAudit-page-btn disabled'>…</span>";

          // Next
          $next_class = $page >= $total_pages ? 'disabled' : '';
          echo "<a href='?$base&page=" . min($total_pages, $page + 1) . "' class='aAudit-page-btn $next_class'><i class='fa fa-chevron-right'></i></a>";
        ?>
      </div>
    </div>
    <?php endif; ?>

  </div>

</main>

<!-- ── Logout Modal ── -->
<div class="logout-overlay" id="logoutOverlay">
  <div class="logout-modal">
    <div class="logout-icon"><i class="fa fa-right-from-bracket"></i></div>
    <h3>Logout</h3>
    <p>Are you sure you want to logout?</p>
    <div class="logout-actions">
      <button class="logout-btn logout-btn--cancel" onclick="closeLogout()">Cancel</button>
      <button class="logout-btn logout-btn--confirm" onclick="confirmLogout()">Yes, Logout</button>
    </div>
  </div>
</div>

<script>
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

function logout() { document.getElementById('logoutOverlay').classList.add('show'); }
function closeLogout() { document.getElementById('logoutOverlay').classList.remove('show'); }
function confirmLogout() { window.location.href = 'logout.php?role=admin'; }

document.getElementById('logoutOverlay').addEventListener('click', function(e) {
  if (e.target === this) closeLogout();
});

document.addEventListener("click", e => {
  const menu = document.getElementById("settingsDropdown");
  const btn  = document.querySelector(".sidebar-settingsButton");
  if (menu && !menu.contains(e.target) && !btn.contains(e.target)) {
    menu.classList.remove("show");
  }
});
</script>
<script>var SESSION_ROLE = 'admin';</script>
<script src="session_timeout.js"></script>
</body>
</html>