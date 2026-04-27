<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>UNITYCARE | Appointment Booking</title>

<link rel="stylesheet" href="styles.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>

<body class="body">

<!-- SIDEBAR -->
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
        <a href="profile.html"><i class="fa fa-user"></i> Profile</a>
        <a href="history.html"><i class="fa fa-clock"></i> Session History</a>
        <button onclick="toggleTheme()"><i class="fa fa-moon"></i> Theme</button>
        <button onclick="logout()"><i class="fa fa-right-from-bracket"></i> Logout</button>
      </div>
    </div>

  </div>

  <nav class="sidebar-menu">
    <a href="dashboard.php"><i class="fa fa-gauge"></i> Dashboard</a>

    <p class="sidebar-title">SERVICES</p>
    <a href="booking.html" class="active"><i class="fa fa-calendar"></i> Book Appointment</a>
    <a href="sconcerns.html"><i class="fa fa-headset"></i> Submit Concern</a>
    <a href="wellness.html"><i class="fa fa-heart"></i> Wellness Check</a>
    <a href="referral.html"><i class="fa fa-route"></i> Referral</a>

    <p class="sidebar-title">UPDATES</p>
    <a href="announcements.html"><i class="fa fa-bullhorn"></i> Announcements</a>

    <p class="sidebar-title">RECORDS</p>
    <a href="sreports.php"><i class="fa fa-ticket"></i> Reports</a>

    <p class="sidebar-title">SYSTEM</p>
    <a href="feedback.html"><i class="fa fa-comment"></i> Session Feedback</a>
  </nav>
</aside>

<!-- TOPBAR -->
<header class="topbar">
  <div class="topbar-left">
    <h1>Book Appointment</h1>
  </div>

  <div class="topbar-right">

   <div class="topbar-user">
      <img src="student.jpg" alt="user">
      <div>
        <strong>Vincent Aldolf Sablay</strong>
        <p>vincentsablay@gmail.com</p>
      </div>
    </div>

  </div>
</header>

<!-- MAIN -->
<main class="sBooking-main">


<!-- BOOKING -->
<div class="sBooking-card sBooking-booking">

  <h3>Schedule Appointment</h3>

  <!-- AVAILABLE SLOTS -->
  <div class="sBooking-formGroup">
    <label>Available Slots</label>
    <div id="slots" class="sBooking-slots"></div>
  </div>

  <div class="sBooking-grid">

    <!-- PRIORITY -->
    <div class="sBooking-priority">
      <label>Priority</label>
      <select id="priority">
        <option>Low</option>
        <option>Medium</option>
        <option>High</option>
      </select>
    </div>

    <!-- MESSAGE -->
    <div class="sBooking-message">
      <label>Message</label>
      <textarea id="message" placeholder="Describe your concern..."></textarea>
    </div>

    <!-- DATE & TIME FIXED -->
    <div class="sBooking-datetime">

      <div class="sBooking-date">
        <label>Date</label>
        <input type="date" id="date">
      </div>

      <div class="sBooking-time">
        <label>Time</label>
        <input type="time" id="time">
      </div>

    </div>

  </div>

  <!-- CONFIRM -->
  <button class="sBooking-button" onclick="bookAppointment()">
    Confirm Booking
  </button>

  <div id="bookingResult"></div>

</div>

<!-- ✅ INTEGRATED UPLOAD SECTION -->
<div class="sBooking-card">

  <h3>Upload Documents</h3>
  <p>If you have supporting documents for your appointment, you may upload them below.</p>

  <input type="file" id="fileInput" onchange="showFileName(event)">
  <p id="fileName"></p>

  <button class="sBooking-button" onclick="uploadFile()">
    Upload File
  </button>

</div>

</main>

<script>
function toggleSettingsMenu(e){
  e.stopPropagation();
  document.getElementById("settingsDropdown").classList.toggle("show");
}

function toggleTheme(){
  const html = document.documentElement;
  html.setAttribute(
    "data-theme",
    html.getAttribute("data-theme") === "light" ? "dark" : "light"
  );
}

function logout(){
  localStorage.clear();
  window.location.href = "login.html";
}

document.addEventListener("click", e => {
  const menu = document.getElementById("settingsDropdown");
  const btn = document.querySelector(".sidebar-settingsButton");

  if (!menu.contains(e.target) && !btn.contains(e.target)) {
    menu.classList.remove("show");
  }
});

/* =========================
   BOOKING SYSTEM
========================= */

/* 24-hour internal storage ONLY */
function generateSlots() {
  return [
    "10:00",
    "11:00",
    "14:00",
    "15:00",
    "16:00",
    "17:00"
  ];
}

const defaultSlots = generateSlots();

let bookedSlots = [];
let selectedSlotBtn = null;

/* =========================
   AM/PM FORMAT (DISPLAY ONLY)
========================= */
function formatTime(time) {
  const [h, m] = time.split(":");
  let hour = parseInt(h);

  const ampm = hour >= 12 ? "PM" : "AM";
  hour = hour % 12 || 12;

  return `${hour}:${m} ${ampm}`;
}

/* =========================
   RENDER SLOTS
========================= */
function renderSlots() {
  const container = document.getElementById("slots");
  container.innerHTML = "";

  defaultSlots.forEach(time => {
    const btn = document.createElement("button");
    btn.className = "sBooking-slotBtn";

    if (bookedSlots.includes(time)) {
      btn.textContent = formatTime(time) + " ❌ Taken";
      btn.disabled = true;
      btn.style.opacity = "0.5";
      btn.style.cursor = "not-allowed";
    } else {
      btn.textContent = formatTime(time);

      btn.onclick = () => {
        document.getElementById("time").value = time; // KEEP 24H FOR INPUT

        if (selectedSlotBtn) {
          selectedSlotBtn.classList.remove("active-slot");
        }

        btn.classList.add("active-slot");
        selectedSlotBtn = btn;
      };
    }

    container.appendChild(btn);
  });
}

function bookAppointment() {
  const d = document.getElementById("date").value;
  const t = document.getElementById("time").value;

  if (!t || !d) {
    alert("Please select date and time.");
    return;
  }

  let appointments = JSON.parse(localStorage.getItem("appointments")) || [];

  const newAppointment = {
    id: "APP-" + Math.floor(Math.random() * 100000),
    date: d,
    time: t,
    status: "pending"
  };

  appointments.push(newAppointment);
  localStorage.setItem("appointments", JSON.stringify(appointments));

  alert("Appointment submitted. Waiting for counselor approval.");
}
/* INIT */
window.onload = renderSlots;
</script>

</body>
</html>