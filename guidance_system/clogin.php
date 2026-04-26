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

      <h2 class="auth-title">Login</h2>
      <p class="auth-subtitle">Welcome back!</p>
      <div class="auth-role" id="roleDisplay">
   Role: <b>Not Selected</b>
</div>

      <form class="auth-form" onsubmit="event.preventDefault(); loginCounselor();">

        <label>Email</label>
        <input type="email" id="email" class="auth-input" required>

        <label>Password</label>
        <input type="password" id="password" class="auth-input" required>

        <div class="auth-captcha">
          <div class="g-recaptcha" data-sitekey="YOUR_SITE_KEY_HERE"></div>
        </div>

        <button class="auth-btn" type="submit">Login</button>

        <div id="error" class="auth-error"></div>

      </form>

      <div class="auth-footer">
        <p>Don’t have an account?</p>
        <a href="cregister.html">Create one</a>
      </div>

    </div>

  </section>

</div>

<script>
const role = localStorage.getItem("userRole") || "Not selected";
document.getElementById("roleDisplay").textContent = "Role: " + role;
  if (role === "Not selected") {
    error.textContent = "Please select a role first.";
    return;
  }

function loginCounselor() {
  const email = document.getElementById("email").value;
  const pass = document.getElementById("password").value;
  const error = document.getElementById("error");

  if (!email || !pass) {
    error.textContent = "Please fill in all fields.";
    return;
  }

  if (!grecaptcha.getResponse()) {
    error.textContent = "Complete reCAPTCHA.";
    return;
  }

  error.style.color = "green";
  error.textContent = "Login successful...";

  setTimeout(() => {
    window.location.href = "counselor-dashboard.html";
  }, 1000);
}
</script>

</body>
</html>