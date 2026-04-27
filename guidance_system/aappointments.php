<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Appointments - UNITYCARE</title>

  <link rel="stylesheet" href="styles.css">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>

<body>

  <!-- ================= SIDEBAR ================= -->
  <nav class="sidebar">
    <div class="logo-bar">
      <div class="logo">
        <img src="logo.png" alt="UNITYCARE logo">
        <h3>UNITYCARE</h3>
      </div>
    </div>

    <div class="menu">
      <a href="admin.php">
        <i class="fa fa-gauge"></i> Dashboard
      </a>

      <p class="menu-title">MANAGEMENT</p>

      <a href="ausers.php"><i class="fa fa-users"></i> Users</a>
      <a href="astudents.php"><i class="fa fa-user-graduate"></i> Students</a>
      <a href="acounselors.php"><i class="fa fa-user-doctor"></i> Counselors</a>
      <a class="active" href="aappointments.php"><i class="fa fa-calendar"></i> Appointments</a>

      <p class="menu-title">SYSTEM</p>
      <a href="areports.php"><i class="fa fa-chart-line"></i> Reports</a>
    </div>

    <button class="btn btn-error btn-sm" onclick="logout()">
      <i class="fa fa-right-from-bracket"></i> Logout
    </button>
  </nav>

  <main class="main">
    <!-- TOPBAR -->
    <div class="topbar" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:15px;">
      <div>
        <h2>Appointments</h2>
        <p class="muted">Review appointment status only. Student names are confidential and not shown here.</p>
      </div>

      <div class="filter-bar" style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
        <button class="btn-filter active" data-status="all" onclick="filterAppointments('all')">All</button>
        <button class="btn-filter" data-status="pending" onclick="filterAppointments('pending')">Pending</button>
        <button class="btn-filter" data-status="approved" onclick="filterAppointments('approved')">Approved</button>
        <button class="btn-filter" data-status="rejected" onclick="filterAppointments('rejected')">Rejected</button>
      </div>
    </div>

    <!-- STATUS SUMMARY -->
    <section class="status-summary" style="margin-top:15px;">
      <div class="summary-card">
        <h3>Pending</h3>
        <p class="large-count" id="pendingCount">2</p>
        <p class="muted">Waiting for approval</p>
      </div>
      <div class="summary-card">
        <h3>Approved</h3>
        <p class="large-count" id="approvedCount">2</p>
        <p class="muted">Confirmed sessions</p>
      </div>
      <div class="summary-card">
        <h3>Rejected</h3>
        <p class="large-count" id="rejectedCount">1</p>
        <p class="muted">Declined requests</p>
      </div>
    </section>

    <!-- APPOINTMENTS CARD -->
    <section class="card" style="margin-top:15px;">
      <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
        <div>
          <h3 style="margin:0;">Appointment Status Overview</h3>
          <p class="muted" style="margin:0;">View appointment statuses and details</p>
        </div>
      </div>

      <div style="overflow-x:auto; margin-top:15px;">
        <table class="table" id="appointmentsTable" style="width:100%; border-collapse:collapse;">
          <thead>
            <tr>
              <th>Reference</th>
              <th>Counselor</th>
              <th>Date & Time</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <tr data-status="pending">
              <td>APPT-001</td>
              <td>Carl Reyes</td>
              <td>Apr 29, 2026 · 10:00 AM</td>
              <td><span class="tag tag-warning">Pending</span></td>
            </tr>
            <tr data-status="approved">
              <td>APPT-002</td>
              <td>Anna Cruz</td>
              <td>Apr 28, 2026 · 2:00 PM</td>
              <td><span class="tag tag-success">Approved</span></td>
            </tr>
            <tr data-status="rejected">
              <td>APPT-003</td>
              <td>Michael Tan</td>
              <td>Apr 30, 2026 · 11:30 AM</td>
              <td><span class="tag tag-error">Rejected</span></td>
            </tr>
            <tr data-status="pending">
              <td>APPT-004</td>
              <td>Emma Santos</td>
              <td>May 01, 2026 · 9:30 AM</td>
              <td><span class="tag tag-warning">Pending</span></td>
            </tr>
            <tr data-status="approved">
              <td>APPT-005</td>
              <td>Samuel Ortiz</td>
              <td>May 02, 2026 · 1:00 PM</td>
              <td><span class="tag tag-success">Approved</span></td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>
  </main>

  <script>
    function updateAppointmentCounts() {
      const rows = document.querySelectorAll('#appointmentsTable tbody tr');
      const counts = { pending: 0, approved: 0, rejected: 0 };

      rows.forEach(row => {
        const status = row.dataset.status;
        if (counts[status] !== undefined) {
          counts[status] += 1;
        }
      });

      document.getElementById('pendingCount').textContent = counts.pending;
      document.getElementById('approvedCount').textContent = counts.approved;
      document.getElementById('rejectedCount').textContent = counts.rejected;
    }

    function filterAppointments(status) {
      const rows = document.querySelectorAll('#appointmentsTable tbody tr');
      const buttons = document.querySelectorAll('.btn-filter');

      buttons.forEach(button => {
        button.classList.toggle('active', button.dataset.status === status);
      });

      rows.forEach(row => {
        const rowStatus = row.dataset.status;
        row.style.display = status === 'all' || rowStatus === status ? '' : 'none';
      });
    }

    function logout() {
      window.location.href = 'login.html';
    }

    document.addEventListener('DOMContentLoaded', () => {
      updateAppointmentCounts();
      filterAppointments('all');
    });
  </script>

  <style>
    .muted {
      color: #6b7f93;
      font-size: 0.95rem;
      margin-top: 4px;
    }

    .filter-bar {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
    }

    .btn-filter {
      border: 1px solid #d1d5db;
      background: #ffffff;
      color: var(--text);
      padding: 10px 16px;
      border-radius: 10px;
      font-weight: 600;
      cursor: pointer;
      transition: var(--transition);
      white-space: nowrap;
    }

    .btn-filter:hover,
    .btn-filter.active {
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      color: #ffffff;
      border-color: transparent;
    }

    .status-summary {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 16px;
    }

    .summary-card {
      background: var(--card);
      border: 1px solid var(--border-light);
      border-radius: var(--radius);
      padding: 18px;
      box-shadow: 0 10px 24px rgba(17, 63, 103, 0.06);
    }

    .summary-card h3 {
      margin-bottom: 6px;
      font-size: 15px;
      color: var(--primary);
    }

    .summary-card p {
      margin: 4px 0;
    }

    .large-count {
      font-size: 32px;
      font-weight: 700;
      margin: 8px 0;
      color: var(--text);
    }

    .card {
      background: var(--card);
      border-radius: var(--radius);
      box-shadow: 0 14px 34px rgba(17, 63, 103, 0.08);
      padding: 20px;
      border: 1px solid rgba(226, 232, 240, 0.85);
      position: relative;
    }

   /* ================= TABLE STYLING ================= */
table {
  border-collapse: collapse;
  width: 100%;
}

table thead tr {
  text-align: left;
  border-bottom: 1.5px solid #e2e8f0;
  background: #f8fafc;
}

table th {
  padding: 12px 14px;
  font-weight: 600;
  font-size: 13px;
  color: #475569;
}

table td {
  padding: 12px 14px;
  font-size: 14px;
  color: #334155;
  border-bottom: 1px solid #f1f5f9;
}

table tbody tr:hover {
  background: #f8fafc;
}

table .btn {
  padding: 6px 12px;
  font-size: 12px;
}

    /* Fix column shifting when scrollbar appears/disappears */
    div[style*="overflow-x"] {
      scrollbar-gutter: stable;
    }

    @media (max-width: 1000px) {
      .status-summary {
        grid-template-columns: repeat(2, 1fr);
      }
    }

    @media (max-width: 650px) {
      .status-summary {
        grid-template-columns: 1fr;
      }

      .topbar {
        flex-direction: column;
        align-items: flex-start;
      }

      .filter-bar {
        width: 100%;
        justify-content: flex-start;
      }
    }

    /* ================= SCROLLBAR ================= */

/* width (kept slim but usable) */
::-webkit-scrollbar {
  width: 8px;
}

/* track */
::-webkit-scrollbar-track {
  background: transparent;
}

/* default thumb (very subtle) */
::-webkit-scrollbar-thumb {
  background: rgba(52, 105, 154, 0.15);
  border-radius: 10px;
  transition: 0.3s ease;
}

/* hover state (becomes visible) */
::-webkit-scrollbar-thumb:hover {
  background: rgba(52, 105, 154, 0.35);
}

/* smooth scrolling */
html {
  scroll-behavior: smooth;
}


  </style>
</body>
</html>