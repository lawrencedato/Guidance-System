<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard - UNITYCARE</title>

<link rel="stylesheet" href="styles.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>

<!-- ================= SIDEBAR ================= -->
<nav class="sidebar">

  <div class="logo-bar">
    <div class="logo">
      <img src="logo.png">
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
    <a href="aappointments.php"><i class="fa fa-calendar"></i> Appointments</a>

    <p class="menu-title">SYSTEM</p>

    <a class="active" href="areports.php"><i class="fa fa-chart-line"></i> Reports</a>

  </div>

  <button class="btn btn-error btn-sm" onclick="logout()">
    <i class="fa fa-right-from-bracket"></i> Logout
  </button>

</nav>
</body>
</html>