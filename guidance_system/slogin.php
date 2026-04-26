<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Account Activation</title>
\
<link rel="stylesheet" href="styles.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">


<body>

<!-- TRIGGER BUTTON (demo only) -->
<div style="padding:40px;">
  <button onclick="activateAccount()">Activate Account</button>
</div>

<!-- MODAL -->
<div class="reset-overlay" id="modal">
  <div class="reset-box">

    <h2>Set Your Password</h2>

    <p>
      Password must include:<br>
      ✔ 1 uppercase letter<br>
      ✔ 1 number<br>
      ✔ 1 symbol
    </p>

    <input type="password" id="password" placeholder="Create password">

    <p id="error" style="color:red; font-size:13px;"></p>

    <button onclick="savePassword()">Save Password</button>

  </div>
</div>

<script>
/* --------------------------
   PASSWORD VALIDATION
---------------------------*/
function isStrongPassword(password) {
  const regex = /^(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*(),.?":{}|<>]).{6,}$/;
  return regex.test(password);
}

/* --------------------------
   FIRST TIME ACTIVATION
---------------------------*/
function activateAccount() {
  const modal = document.getElementById("modal");

  if (!localStorage.getItem("accountActivated")) {
    modal.style.display = "flex";
  } else {
    alert("Account already activated.");
  }
}

/* --------------------------
   SAVE PASSWORD
---------------------------*/
function savePassword() {
  const password = document.getElementById("password").value.trim();
  const error = document.getElementById("error");

  error.textContent = "";

  if (!password) {
    error.textContent = "Password is required.";
    return;
  }

  if (!isStrongPassword(password)) {
    error.textContent =
      "Must include uppercase, number, and symbol (min 6 chars).";
    return;
  }

  localStorage.setItem("adminPassword", password);
  localStorage.setItem("accountActivated", "true");

  document.getElementById("modal").style.display = "none";

  alert("Account activated successfully!");
}
</script>

</body>
</html>