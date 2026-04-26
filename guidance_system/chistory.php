<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Counselor Login | UNITYCARE</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="styles.css">

<script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>

<body class="auth-body">

<div class="auth-container">

  <!-- LEFT -->
  <section class="auth-left">
    <div class="auth-overlay"></div>

    <div class="auth-brand">
      <img class="auth-brand-logo" src="logo.png">
      <h1 class="auth-brand-title">UNITYCARE</h1>
      <p class="auth-brand-subtitle">Support • Care • Connection</p>
    </div>
  </section>

  <!-- RIGHT -->
  <section class="auth-right">

    <div class="auth-box">

      <h2 class="auth-title">Counselor Login</h2>
      <p class="auth-subtitle">Secure access required</p>

      <form class="auth-form" onsubmit="event.preventDefault(); loginCounselor();">

        <label>Name</label>
        <input type="text" id="name" class="auth-input" required placeholder="Enter your full name">

        <label>Password</label>
        <input type="password" id="password" class="auth-input" required placeholder="Enter password">

        <!-- CAPTCHA -->
        <div style="margin: 10px 0;">
          <div class="g-recaptcha" data-sitekey="YOUR_SITE_KEY_HERE"></div>
        </div>

        <button class="auth-btn" type="submit">Login</button>

        <div id="error" class="auth-error"></div>

      </form>

    </div>

  </section>

</div>

<script>
/* -------------------------------
   DEMO: ADMIN SETS PASSWORD
   (run once in console or admin page)
   localStorage.setItem("adminPassword", "yourChosenPassword");
----------------------------------*/

// LOGIN FUNCTION
function loginCounselor() {
  const name = document.getElementById("name").value.trim();
  const password = document.getElementById("password").value.trim();
  const error = document.getElementById("error");

  error.style.color = "red";
  error.textContent = "";

  const storedPassword = localStorage.getItem("adminPassword");

  // CHECK INPUTS
  if (!name || !password) {
    error.textContent = "Please fill in all fields.";
    return;
  }

  // CHECK IF ADMIN HAS SET PASSWORD
  if (!storedPassword) {
    error.textContent = "No admin password set yet.";
    return;
  }

  // CHECK PASSWORD MATCH
  if (password !== storedPassword) {
    error.textContent = "Incorrect password.";
    return;
  }

  // CHECK CAPTCHA
  const captchaResponse = grecaptcha.getResponse();
  if (!captchaResponse || captchaResponse.length === 0) {
    error.textContent = "Please complete reCAPTCHA.";
    return;
  }

  // SUCCESS
  localStorage.setItem("counselorName", name);

  error.style.color = "green";
  error.textContent = "Login successful...";

  setTimeout(() => {
    window.location.href = "counselor.html";
  }, 800);
}
</script>

</body>
</html>