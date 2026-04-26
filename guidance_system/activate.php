<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>UNITYCARE | ACCOUNT ACTIVATION</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="styles.css">
</head>

<body class="auth-body">

<div class="auth-container">

  <!-- LEFT -->
  <section class="auth-left">
    <div class="auth-left-overlay"></div>

    <div class="auth-brand">
      <img class="auth-brand-logo" src="logo.png">
      <h1 class="auth-brand-title">UNITYCARE</h1>
      <p class="auth-brand-subtitle">Support • Care • Connection</p>
    </div>
  </section>

  <!-- RIGHT -->
  <section class="auth-right">

    <div class="auth-box">

      <h2 class="auth-title">Activate your account</h2>
      <p class="auth-subtitle">Please activate your account to access the system.</p>

      <form class="auth-form" onsubmit="event.preventDefault(); login();">

        <label class="auth-label">Email</label>
        <input class="auth-input" id="email" type="email">

        <label class="auth-label">Password</label>
        <input class="auth-input" id="password" type="password">

        <button class="auth-btn" type="submit">
          Login
        </button>

        <div class="auth-message" id="regError"></div>

      </form>

      <!-- TEMP PASSWORD BOX -->
      <div class="auth-temp-box" id="tempBox" style="display:none;">
        <p class="auth-temp-title">Temporary Password Sent</p>
        <p class="auth-temp-password" id="tempPass"></p>
        <p class="auth-temp-note">Save this password before leaving.</p>
      </div>

      <div class="auth-footer">
        <div class="auth-footer-text">Already have an account?</div>
        <a class="auth-footer-link" href="login.html">Login</a>
      </div>

    </div>

  </section>

</div>

<script>
function generateTempPassword() {
  return Math.random().toString(36).slice(-8) + "A1!";
}

function login() {
  const email = document.getElementById("email").value.trim();
  const password = document.getElementById("password").value.trim();
  const error = document.getElementById("regError");

  error.style.color = "red";
  error.textContent = "";

  if (!email || !password) {
    error.textContent = "Please fill in all fields.";
    return;
  }

  // Save account (simulate registration/activation)
  const tempPassword = generateTempPassword();

  localStorage.setItem("registeredEmail", email);
  localStorage.setItem("userPassword", tempPassword);

  // show message
  error.style.color = "green";
  error.textContent = "Temporary password sent to your account.";

  // show temp password box
  document.getElementById("tempBox").style.display = "block";
  document.getElementById("tempPass").textContent = tempPassword;

  // simulate redirect after showing password
  setTimeout(() => {
    error.textContent = "You can now log in using the temporary password.";
  }, 1500);
}
</script>

</body>
</html>