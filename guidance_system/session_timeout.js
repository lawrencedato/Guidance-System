document.addEventListener('DOMContentLoaded', function () {
  'use strict';

  if (window.__sessionTimeoutLoaded) return;
  window.__sessionTimeoutLoaded = true;

  const TIMEOUT_MS = 15 * 60 * 1000;   
  const WARNING_MS =  1 * 60 * 1000;   
  const PING_MS    = 60 * 1000;        
  const LOGOUT_URL = 'logout.php?role=' + (window.SESSION_ROLE || 'student');
  const CHECK_URL  = 'session_check.php';

  let idleTimer      = null;
  let warningTimer   = null;
  let countdownTimer = null;
  let pingTimer      = null;
  let warningVisible = false;

  // style
  const css = `
    #st-overlay {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(17, 63, 103, 0.25);
      backdrop-filter: blur(6px);
      z-index: 99998;
      pointer-events: none;
    }
    #st-overlay.st-show {
      display: block;
      pointer-events: auto;
    }

    #st-modal {
      display: none;
      position: fixed;
      inset: 0;
      justify-content: center;
      align-items: center;
      z-index: 99999;
      pointer-events: none;
    }
    #st-modal.st-show {
      display: flex;
      pointer-events: auto;
    }

    #st-box {
      position: relative;
      width: 420px;
      padding: 32px 28px 24px;
      background: rgba(255, 255, 255, 0.9);
      backdrop-filter: blur(18px);
      border: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: 20px;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.18);
      text-align: center;
      animation: st-pop 0.25s ease;
      pointer-events: auto;
    }

    @keyframes st-pop {
      from { opacity: 0; transform: scale(0.95); }
      to   { opacity: 1; transform: scale(1); }
    }

    #st-icon {
      width: 60px;
      height: 60px;
      border-radius: 50%;
      background: rgba(234, 179, 8, 0.12);
      border: 1px solid rgba(234, 179, 8, 0.30);
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 16px;
      font-size: 26px;
      line-height: 1;
    }

    #st-box h3 {
      margin: 0 0 8px;
      font-size: 17px;
      font-weight: 700;
      color: #113f67;
      font-family: "Poppins", system-ui, sans-serif;
    }

    #st-box p {
      font-size: 13px;
      color: #64748b;
      margin: 0 0 6px;
      line-height: 1.6;
      font-family: "Poppins", system-ui, sans-serif;
    }

    #st-countdown-wrap {
      font-size: 13px;
      color: #94a3b8;
      margin-bottom: 22px;
      font-family: "Poppins", system-ui, sans-serif;
    }

    #st-secs {
      font-weight: 700;
      color: #c05621;
      font-size: 15px;
    }

    #st-actions {
      display: flex;
      gap: 10px;
      justify-content: center;
    }

    #st-btn-stay {
      flex: 1;
      padding: 10px 22px;
      border: none;
      border-radius: 10px;
      background: linear-gradient(135deg, #113F67, #4988C4);
      color: #fff;
      font-weight: 600;
      font-size: 13px;
      cursor: pointer;
      box-shadow: 0 6px 16px rgba(73, 136, 196, 0.30);
      transition: 0.2s ease;
      font-family: "Poppins", system-ui, sans-serif;
      position: relative;
      z-index: 100000;
    }
    #st-btn-stay:hover {
      transform: translateY(-1px);
      box-shadow: 0 8px 20px rgba(73, 136, 196, 0.42);
    }

    #st-btn-logout {
      flex: 1;
      padding: 10px 22px;
      border: 1px solid rgba(15, 23, 42, 0.14);
      border-radius: 10px;
      background: transparent;
      color: #64748b;
      font-weight: 600;
      font-size: 13px;
      cursor: pointer;
      transition: 0.2s ease;
      font-family: "Poppins", system-ui, sans-serif;
      position: relative;
      z-index: 100000;
    }
    #st-btn-logout:hover {
      border-color: #c05621;
      color: #c05621;
    }

    @media (max-width: 480px) {
      #st-box        { width: 92% !important; padding: 22px 16px; }
      #st-actions    { flex-direction: column; }
      #st-btn-stay,
      #st-btn-logout { width: 100%; }
    }

    [data-theme="dark"] #st-overlay {
      background: rgba(2, 6, 23, 0.72);
      backdrop-filter: blur(8px);
    }
    [data-theme="dark"] #st-box {
      background: rgba(10, 18, 35, 0.97);
      border: 1px solid rgba(255, 255, 255, 0.09);
      box-shadow: 0 22px 60px rgba(0, 0, 0, 0.60);
    }
    [data-theme="dark"] #st-box h3  { color: #e2eaf4; }
    [data-theme="dark"] #st-box p   { color: #8ba4be; }
    [data-theme="dark"] #st-countdown-wrap { color: #6e8ea8; }
    [data-theme="dark"] #st-secs    { color: #fb923c; }
    [data-theme="dark"] #st-icon    { background: rgba(234,179,8,0.10); border-color: rgba(234,179,8,0.25); }
    [data-theme="dark"] #st-btn-stay {
      background: linear-gradient(135deg, #0d3254, #3a7ab8);
      box-shadow: 0 6px 16px rgba(0,0,0,0.40);
    }
    [data-theme="dark"] #st-btn-stay:hover { box-shadow: 0 8px 20px rgba(0,0,0,0.52); }
    [data-theme="dark"] #st-btn-logout {
      border-color: rgba(255,255,255,0.10);
      color: #94a3b8;
      background: rgba(255,255,255,0.04);
    }
    [data-theme="dark"] #st-btn-logout:hover {
      border-color: rgba(239,68,68,0.55);
      color: #fca5a5;
      background: rgba(239,68,68,0.06);
    }
  `;

  const styleEl = document.createElement('style');
  styleEl.textContent = css;
  document.head.appendChild(styleEl);

  // html
  const tpl = document.createElement('div');
  tpl.innerHTML = `
    <div id="st-overlay"></div>
    <div id="st-modal" role="dialog" aria-modal="true" aria-labelledby="st-title">
      <div id="st-box">
        <div id="st-icon">
          <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#b45309"
               stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"/>
            <polyline points="12 6 12 12 16 14"/>
          </svg>
        </div>
        <h3 id="st-title">Session Expiring Soon</h3>
        <p>You've been inactive for a while. For your security, you'll be logged out automatically.</p>
        <div id="st-countdown-wrap">
          Logging out in <span id="st-secs">60</span> second<span id="st-plural">s</span>
        </div>
        <div id="st-actions">
          <button id="st-btn-stay">Stay Logged In</button>
          <button id="st-btn-logout">Log Out Now</button>
        </div>
      </div>
    </div>
  `;
  document.body.appendChild(tpl);

  const overlay  = document.getElementById('st-overlay');
  const modal    = document.getElementById('st-modal');
  const secsEl   = document.getElementById('st-secs');
  const pluralEl = document.getElementById('st-plural');
  const btnStay  = document.getElementById('st-btn-stay');
  const btnOut   = document.getElementById('st-btn-logout');
  const box      = document.getElementById('st-box');

  function showWarning() {
    if (warningVisible) return;
    warningVisible = true;
    overlay.classList.add('st-show');
    modal.classList.add('st-show');

    let secs = Math.round(WARNING_MS / 1000);
    secsEl.textContent   = secs;
    pluralEl.textContent = secs === 1 ? '' : 's';

    countdownTimer = setInterval(() => {
      secs--;
      secsEl.textContent   = secs;
      pluralEl.textContent = secs === 1 ? '' : 's';
      if (secs <= 0) {
        clearInterval(countdownTimer);
        doLogout();
      }
    }, 1000);
  }

  function hideWarning() {
    warningVisible = false;
    overlay.classList.remove('st-show');
    modal.classList.remove('st-show');
    clearInterval(countdownTimer);
  }

  function doLogout() {
    clearAll();
    try { localStorage.setItem('st_logout', Date.now()); } catch (_) {}
    window.location.href = LOGOUT_URL;
  }

  function ping() {
    fetch(CHECK_URL, { method: 'POST', credentials: 'same-origin' }).catch(() => {});
  }

  function resetTimer() {
    clearTimeout(idleTimer);
    clearTimeout(warningTimer);

    if (warningVisible) {
      hideWarning();
      ping();
    }

    warningTimer = setTimeout(showWarning, TIMEOUT_MS - WARNING_MS);
    idleTimer    = setTimeout(doLogout,    TIMEOUT_MS);

    try { localStorage.setItem('st_activity', Date.now()); } catch (_) {}
  }

  function clearAll() {
    clearTimeout(idleTimer);
    clearTimeout(warningTimer);
    clearInterval(countdownTimer);
    clearInterval(pingTimer);
  }

  ['mousedown', 'keydown', 'touchstart', 'scroll', 'click']
    .forEach(ev => document.addEventListener(ev, resetTimer, { passive: true }));

  box.addEventListener('click', e => e.stopPropagation());

  btnStay.addEventListener('click', e => {
    e.stopPropagation();
    hideWarning();
    resetTimer();
    ping();
  });

  btnOut.addEventListener('click', e => {
    e.stopPropagation();
    doLogout();
  });

  overlay.addEventListener('click', () => {
    hideWarning();
    resetTimer();
    ping();
  });

  window.addEventListener('storage', e => {
    if (e.key === 'st_activity') resetTimer();
    if (e.key === 'st_logout')   doLogout();
  });

  pingTimer = setInterval(() => { if (!warningVisible) ping(); }, PING_MS);

  resetTimer();

}); 
