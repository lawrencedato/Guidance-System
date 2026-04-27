<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>UNITYCARE | Announcements</title>

<link rel="stylesheet" href="styles.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

<body class="body">

<!-- SIDEBAR -->
<aside class="sidebar">
  <div class="sidebar-logoBar">

    <div class="sidebar-logo">
      <img src="logo.png" alt="logo">
      <span class="sidebar-logoText">UNITYCARE</span>
    </div>

    <div class="sidebar-settings">
      <button class="sidebar-settingsButton" onclick="toggleSettings()">
        <i class="fa fa-gear"></i>
      </button>

      <div class="sidebar-settingsDropdown" id="settingsMenu">
        <a href="profile.html"><i class="fa fa-user"></i> Profile</a>
        <a href="chistory.html"><i class="fa fa-clock"></i> Session History</a>
        <button onclick="toggleTheme()"><i class="fa fa-moon"></i> Theme</button>
        <button onclick="logout()"><i class="fa fa-right-from-bracket"></i> Logout</button>
      </div>
    </div>

  </div>

  <nav class="sidebar-menu">
    <a href="counselor.php"><i class="fa fa-gauge"></i> Dashboard</a>

    <p class="sidebar-title">SESSIONS</p>
    <a href="cappointments.html"><i class="fa fa-calendar-plus"></i> Appointment Requests</a>
    <a href="cconcerns.html"><i class="fa fa-triangle-exclamation"></i> Student Concerns</a>

    <p class="sidebar-title">STUDENTS</p>
    <a href="students.html"><i class="fa fa-users"></i> Students</a>

    <p class="sidebar-title">REPORTS</p>
    <a href="creports.php"><i class="fa fa-file"></i> Reports</a>

    <p class="sidebar-title">INFORMATION</p>
    <a href="cannouncements.php" class="active"><i class="fa fa-bullhorn"></i> Announcements</a>
    <a href="creferral.php"><i class="fa fa-route"></i> Referrals</a>
  </nav>
</aside>

<!-- ================= TOPBAR ================= -->
<header class="topbar">
  <div class="topbar-left">
    <h2>Announcement</h2>
  </div>

  <div class="topbar-right">

    <!-- FEEDBACK NOTIFICATIONS -->
<div class="topbar-icon" onclick="toggleDropdown('feedbackDropdown', event)">
  <i class="fa fa-envelope"></i>
  <span class="badge" id="feedbackCount">0</span>

  <div class="icon-dropdown" id="feedbackDropdown">
    <div class="notif-header">New Feedback</div>
    <div id="notifList">
      <div class="notif-empty">No new feedback</div>
    </div>
  </div>
</div>

<!-- BELL NOTIFICATIONS -->
<div class="topbar-icon" onclick="toggleDropdown('notifDropdown', event)">
  <i class="fa fa-bell"></i>
  <span class="badge">4</span>

  <div class="icon-dropdown" id="notifDropdown">
    <p>No new notifications</p>
  </div>
</div>

    <div class="topbar-user">
      <img src="counselor.jpg" alt="user">
      <div>
        <strong>Dr. Lawrence Dato</strong>
        <p>lawrencedato@gmail.com</p>
      </div>
    </div>

  </div>
</header>

<!-- MAIN -->
<main class="cAnnouncements-main">

  <div class="cAnnouncements-card">

    <h2>Create Announcement</h2>

    <input id="title" placeholder="Announcement Title" class="cAnnouncements-input">

    <textarea id="message" placeholder="Write announcement..." class="cAnnouncements-textarea"></textarea>

    <input type="file" id="imageFile" accept="image/*" class="cAnnouncements-input">

    <button class="cAnnouncements-btn" onclick="postAnnouncement()">
      Post Announcement
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
   FIREBASE CONFIG (PUT YOURS HERE)
========================= */
const firebaseConfig = {
  apiKey: "YOUR_API_KEY",
  authDomain: "YOUR_PROJECT.firebaseapp.com",
  projectId: "YOUR_PROJECT_ID",
  storageBucket: "YOUR_PROJECT.appspot.com",
  messagingSenderId: "XXXX",
  appId: "XXXX"
};

firebase.initializeApp(firebaseConfig);

const db = firebase.firestore();
const storage = firebase.storage();

/* =========================
   POST ANNOUNCEMENT WITH IMAGE
========================= */

function postAnnouncement(){

  const title = document.getElementById("title").value;
  const message = document.getElementById("message").value;
  const file = document.getElementById("imageFile").files[0];

  if(!title || !message){
    alert("Please fill all fields");
    return;
  }

  // NO IMAGE CASE
  if(!file){
    db.collection("announcements").add({
      title,
      message,
      imageUrl: "",
      status: "active",
      createdAt: new Date()
    });

    alert("Announcement posted!");
    return;
  }

  // IMAGE UPLOAD
  const storageRef = storage.ref("announcements/" + Date.now() + "_" + file.name);
  const uploadTask = storageRef.put(file);

  uploadTask.on("state_changed",
    null,
    (error) => {
      alert("Upload failed");
    },
    () => {
      uploadTask.snapshot.ref.getDownloadURL().then((url) => {

        db.collection("announcements").add({
          title,
          message,
          imageUrl: url,
          status: "active",
          createdAt: new Date()
        });

        alert("Announcement posted!");

        document.getElementById("title").value = "";
        document.getElementById("message").value = "";
        document.getElementById("imageFile").value = "";
      });
    }
  );
}
</script>

</body>
</html>