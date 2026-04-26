<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="UNITYCARE Role Selection Portal">
  <title>UNITYCARE | Account Type</title>

  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="styles.css">
</head>

<body class="accType-body">

  <main class="accType-main-content">

    <section class="accType-section-wrapper">

      <header class="accType-header-brand">
        <figure class="accType-header-logoWrapper">
          <img class="accType-header-logoImage" src="logo.png" alt="UNITYCARE Logo">
          <figcaption>
            <h1 class="accType-header-title">UNITYCARE</h1>
          </figcaption>
        </figure>
      </header>

      <article class="accType-card-container">

        <header class="accType-card-header">
          <h2 class="accType-card-title">Choose account type</h2>
          <p class="accType-card-subtitle">Please choose your account type to continue</p>
        </header>

        <div class="accType-card-buttons">

          <button class="accType-card-button accType-card-button-student"
            onclick="handleRoleSelection('student')">
            Student
          </button>

          <button class="accType-card-button accType-card-button-counselor"
            onclick="handleRoleSelection('counselor')">
            Counselor
          </button>

          <button class="accType-card-button accType-card-button-admin"
            onclick="handleRoleSelection('admin')">
            Administrator
          </button>

        </div>

      </article>

      <footer class="accType-footer">
        <p class="accType-footer-text">© 2026 UNITYCARE. All rights reserved.</p>
      </footer>

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
          window.location.href = "admin-dashboard.html";
          break;
      }
    }
  </script>

</body>
</html>