<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>UNITYCARE | Guidance System</title>

<link rel="stylesheet" href="style.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<style>
  /* disable scroll*/
  html, body {
  margin: 0;
  padding: 0;
  overflow: hidden;
  height: 100%;
}
  /* =========================
   BACKGROUND
========================= */
.uc-bg {
  position: fixed;
  inset: 0;
  z-index: -3;

  background: linear-gradient(-45deg,
    #0b3a63,
    #1f5f9a,
    #eaf3ff,
    #dbeaff,
    #f7fbff,
    #113F67
  );

  background-size: 400% 400%;
  animation: ucGradient 20s ease infinite;
}

@keyframes ucGradient {
  0% { background-position: 0% 50%; }
  50% { background-position: 100% 50%; }
  100% { background-position: 0% 50%; }
}

/* glow */
.uc-glow {
  position: fixed;
  width: 500px;
  height: 500px;
  border-radius: 50%;
  filter: blur(100px);
  z-index: -2;
  opacity: 0.6;
  pointer-events: none;
}

.uc-glow--one {
  top: -180px;
  left: -180px;
  background: rgba(73,136,196,0.25);
  animation: ucFloat1 12s ease-in-out infinite;
}

.uc-glow--two {
  bottom: -200px;
  right: -200px;
  background: rgba(31,95,154,0.2);
  animation: ucFloat2 14s ease-in-out infinite;
}

@keyframes ucFloat1 {
  0%,100% { transform: translate(0,0); }
  50% { transform: translate(40px,-30px); }
}

@keyframes ucFloat2 {
  0%,100% { transform: translate(0,0); }
  50% { transform: translate(-35px,30px); }
}

/* =========================
   NAV
========================= */
.uc-nav {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 22px 8%;
}

.uc-logo {
  font-weight: 800;
  font-size: 20px;
  color: var(--primary-hover);
  letter-spacing: 1px;
}

.uc-nav__actions {
  display: flex;
  gap: 12px;
}

/* =========================
   BUTTON SYSTEM
========================= */
.uc-btn {
  padding: 10px 18px;
  border-radius: var(--radius);
  border: none;
  cursor: pointer;
  font-weight: 500;
  transition: 0.25s ease;
}

.uc-btn--primary {
  background: var(--primary-hover);
  color: var(--text-inverse);
}

.uc-btn--primary:hover {
  opacity: 0.9;
}

.uc-btn--ghost {
  background: transparent;
  border: 1px solid var(--primary-hover);
  color: var(--primary-hover);
}

.uc-btn--ghost:hover {
  background: var(--primary-hover);
  color: var(--text-inverse);
}

/* =========================
   HERO LAYOUT
========================= */
.uc-hero {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 40px 8%;
  min-height: calc(100vh - 80px);
  gap: 40px;
}

/* TEXT */
.uc-hero__content {
  max-width: 520px;
}

.uc-badge {
  display: inline-block;
  padding: 6px 14px;
  background: rgba(73,136,196,0.1);
  color: var(--primary-hover);
  border-radius: 999px;
  font-size: 12px;
  margin-bottom: 18px;
}

.uc-title {
  font-size: 46px;
  line-height: 1.1;
  margin-bottom: 14px;
  color: var(--text);
}

.uc-description {
  font-size: 14px;
  color: var(--text-muted);
  margin-bottom: 26px;
  line-height: 1.7;
}

/* CTA */
.uc-cta {
  padding: 14px 28px;
  border-radius: var(--radius);
  border: none;
  background: linear-gradient(135deg, var(--primary-hover), var(--primary));
  color: var(--text-inverse);
  cursor: pointer;
  transition: 0.25s ease;
}

.uc-cta:hover {
  transform: translateY(-2px);
}

/* VISUAL */
.uc-hero__visual {
  flex: 1;
  display: flex;
  justify-content: center;
  align-items: center;
}

.uc-hero__visual img {
  width: 420px;
  max-width: 100%;
  border-radius: var(--radius-lg);
  animation: ucFloat 6s ease-in-out infinite;
}

@keyframes ucFloat {
  0%,100% { transform: translateY(0); }
  50% { transform: translateY(-12px); }
}
</style>
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
    <svg viewBox="0 0 420 340" xmlns="http://www.w3.org/2000/svg" style="width:420px; max-width:100%;">
  <rect x="40" y="40" width="340" height="240" rx="18" fill="#e8f0fb"/>
  <rect x="70" y="70" width="280" height="180" rx="12" fill="#ffffff" stroke="#c3d6f5" stroke-width="1.5"/>
  <rect x="100" y="100" width="180" height="14" rx="7" fill="#113f67"/>
  <rect x="100" y="124" width="220" height="10" rx="5" fill="#c3d6f5"/>
  <rect x="100" y="142" width="200" height="10" rx="5" fill="#c3d6f5"/>
  <rect x="100" y="160" width="160" height="10" rx="5" fill="#c3d6f5"/>
  <rect x="100" y="186" width="70" height="28" rx="8" fill="#113f67"/>
  <text x="135" y="205" text-anchor="middle" font-size="11" fill="#ffffff" font-family="sans-serif">Submit</text>
  <circle cx="340" cy="90" r="28" fill="#34699A"/>
  <text x="340" y="96" text-anchor="middle" font-size="20" fill="#ffffff" font-family="sans-serif">✓</text>
  <circle cx="60" cy="220" r="18" fill="#e8f0fb" stroke="#113f67" stroke-width="1.5"/>
  <text x="60" y="226" text-anchor="middle" font-size="14" fill="#113f67" font-family="sans-serif">🎓</text>
  <circle cx="380" cy="200" r="14" fill="#e8f0fb" stroke="#34699A" stroke-width="1.5"/>
  <text x="380" y="206" text-anchor="middle" font-size="11" fill="#34699A" font-family="sans-serif">♥</text>
</svg>
  </section>

</main>

<script>
function goLogin(){
  window.location.href = "slogin.php";
}

function goActivate(){
  window.location.href = "activate.php";
}
</script>

</body>
</html>