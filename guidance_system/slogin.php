<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>UNITYCARE | Student Login</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="styles.css">
</head>

<body class="auth-body">

<div class="auth-container">

  <!-- LEFT -->
  <section class="auth-left">
    <div class="auth-left-overlay"></div>

    <div class="auth-brand">
      <img class="auth-brand-logo" src="logo.png" alt="logo">
      <h1 class="auth-brand-title">UNITYCARE</h1>
      <p class="auth-brand-subtitle">Support • Care • Connection</p>
    </div>
  </section>

  <!-- RIGHT -->
  <section class="auth-right">

    <div class="auth-box">

      <h2 class="auth-title">Student Login</h2>
      <p class="auth-subtitle">Welcome back! Please sign in.</p>

      <!-- FORM -->
      <form class="auth-form" onsubmit="event.preventDefault(); loginStudent();">

        <label class="auth-label">Email</label>
        <input class="auth-input" id="email" type="email" placeholder="Enter your email" required>

        <label class="auth-label">Password</label>
        <input class="auth-input" id="password" type="password" placeholder="Enter your password" required>

        <!-- CAPTCHA -->
        <div style="margin: 10px 0;">
          <div class="g-recaptcha" data-sitekey="YOUR_SITE_KEY_HERE"></div>
        </div>

        <button class="auth-btn" type="submit">Login</button>

        <div id="error" class="auth-error"></div>


      </form>

      <!-- FOOTER -->
      <div class="auth-footer">
        <div class="auth-footer-text">Don’t have an account?</div>
        <a class="auth-footer-link" href="activate.html">Activate</a>
      </div>

    </div>

  </section>

</div>

<script>
function loginStudent() {
  const email = document.getElementById("email").value.trim();
  const password = document.getElementById("password").value.trim();
  const error = document.getElementById("regError");

  error.style.color = "red";
  error.textContent = "";

  const savedEmail = localStorage.getItem("registeredEmail");
  const tempPassword = localStorage.getItem("tempPassword");
  const finalPassword = localStorage.getItem("finalPassword");

  if (!email || !password) {
    error.textContent = "Please fill in all fields.";
    return;
  }

  if (email !== savedEmail) {
    error.textContent = "Invalid email.";
    return;
  }
  
    // CHECK CAPTCHA
  const captchaResponse = grecaptcha.getResponse();
  if (!captchaResponse || captchaResponse.length === 0) {
    error.textContent = "Please complete reCAPTCHA.";
    return;
  }

  /* =========================
     FIRST LOGIN FLOW
  ========================= */
  if (!finalPassword) {
    if (password !== tempPassword) {
      error.textContent = "Invalid temporary password.";
      return;
    }

    localStorage.setItem("firstLogin", "true");
    localStorage.setItem("passwordChanged", "false");
  }

  /* =========================
     AFTER RESET PASSWORD
  ========================= */
  if (finalPassword && password !== finalPassword) {
    error.textContent = "Invalid password.";
    return;
  }

  error.style.color = "green";
  error.textContent = "Login successful...";

  setTimeout(() => {
    window.location.href = "dashboard.html";
  }, 800);
}
</script>

</body>
</html>