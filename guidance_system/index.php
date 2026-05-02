<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>UNITYCARE | Guidance System</title>

<link rel="stylesheet" href="styles.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>

<body>

<!-- BACKGROUND -->
<div class="uc-bg"></div>
<div class="uc-glow uc-glow--one"></div>
<div class="uc-glow uc-glow--two"></div>

<!-- NAV -->
<header class="uc-nav">
  <div class="uc-logo">UNITYCARE</div>

  <div class="uc-nav__actions">
    <button class="uc-btn uc-btn--ghost" onclick="goActivate()">Activate Account</button>
    <button class="uc-btn uc-btn--primary" onclick="goLogin()">Login</button>
  </div>
</header>

<!-- HERO -->
<main class="uc-hero">

  <!-- TEXT SECTION -->
  <section class="uc-hero__content">

    <div class="uc-badge">
      Guidance • Feedback • Counseling Support
    </div>

    <h1 class="uc-title">
      Student Feedback<br>
      Made Safe & Confidential
    </h1>

    <p class="uc-description">
      UNITYCARE provides a secure space where students can submit feedback,
      request guidance, and connect with counselors privately and respectfully.
    </p>

    <button class="uc-cta" onclick="goLogin()">
      Get Started
    </button>

  </section>

  <!-- IMAGE SECTION -->
  <section class="uc-hero__visual">
    <img 
      src="https://img.freepik.com/free-vector/feedback-concept-illustration_114360-1395.jpg"
      alt="Student Feedback Illustration"
    >
  </section>

</main>

  <script>
    function handleRoleSelection(role) {
      localStorage.setItem("userRole", role);

      switch (role) {
        case "student":
          window.location.href = "login.html";
          break;
        case "counselor":
          window.location.href = "clogin.html";
          break;
        case "admin":
        default:
          window.location.href = "admin.html";
          break;
      }
    }
  </script>

</body>
</html>