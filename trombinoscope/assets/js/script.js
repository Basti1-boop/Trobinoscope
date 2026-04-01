/* --- Footer year --- */
document.querySelectorAll('.footer-year').forEach(function (el) {
  el.textContent = new Date().getFullYear();
});

/* --- Hamburger nav --- */
const navToggle = document.querySelector('.nav-toggle');
const navLinks  = document.querySelector('.nav-links');
if (navToggle && navLinks) {
  navToggle.addEventListener('click', function () {
    navLinks.classList.toggle('open');
    navToggle.classList.toggle('open');
  });
}

/* --- Flash dismiss --- */
document.querySelectorAll('.flash').forEach(function (flash) {
  const btn = document.createElement('button');
  btn.className   = 'flash-close';
  btn.textContent = '\u00D7';
  btn.setAttribute('aria-label', 'Fermer');
  btn.addEventListener('click', function () { flash.remove(); });
  flash.appendChild(btn);
});

/* --- Delete confirmation --- */
document.querySelectorAll('[data-confirm]').forEach(function (el) {
  el.addEventListener('click', function (e) {
    if (!confirm(el.dataset.confirm)) {
      e.preventDefault();
    }
  });
});

/* --- Avatar preview --- */
const avatarInput = document.getElementById('avatar');
if (avatarInput) {
  avatarInput.addEventListener('change', function (e) {
    const file = e.target.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = function (ev) {
        document.getElementById('preview-avatar').src = ev.target.result;
      };
      reader.readAsDataURL(file);
    }
  });
}

/* --- Copy IP helper --- */
document.querySelectorAll('.copy-ip').forEach(function (btn) {
  btn.addEventListener('click', function () {
    const ip = btn.getAttribute('data-ip') || '';
    if (!ip) { return; }
    if (navigator.clipboard && window.isSecureContext) {
      navigator.clipboard.writeText(ip).then(function () {
        btn.textContent = 'CopiÃ©';
        setTimeout(function () { btn.textContent = 'Copier'; }, 1000);
      }).catch(function () {});
    } else {
      const temp = document.createElement('input');
      temp.value = ip;
      document.body.appendChild(temp);
      temp.select();
      try { document.execCommand('copy'); } catch (e) {}
      document.body.removeChild(temp);
      btn.textContent = 'CopiÃ©';
      setTimeout(function () { btn.textContent = 'Copier'; }, 1000);
    }
  });
});
/* --- Flash auto-dismiss (5s) with AJAX --- */
document.querySelectorAll('.flash').forEach(function (flash) {
  let type = 'info';
  if (flash.classList.contains('flash-success')) { type = 'success'; }
  if (flash.classList.contains('flash-error')) { type = 'error'; }

  fetch('process_message.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ type: type, message: flash.textContent })
  }).catch(function () {});

  setTimeout(function () {
    if (!flash.parentNode) { return; }
    flash.classList.add('flash-hiding');
    setTimeout(function () {
      if (flash.parentNode) { flash.remove(); }
    }, 300);
  }, 10000);
});


