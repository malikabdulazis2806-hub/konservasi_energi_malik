<?php if (!empty($__win)): ?></div><?php endif; ?>
</main>
</div>
<script>
(function () {
  var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  // 1) Riak saat menekan tombol, menu, dan akun tersimpan
  if (!reduce) {
    document.addEventListener('pointerdown', function (e) {
      var t = e.target.closest('.btn, .saved-item, .forget, .eye, .avatar, .menu-nav a');
      if (!t) return;
      var r = t.getBoundingClientRect(), d = Math.max(r.width, r.height) * 2.2;
      var s = document.createElement('span');
      s.className = 'ripple';
      s.style.width = s.style.height = d + 'px';
      s.style.left = (e.clientX - r.left - d / 2) + 'px';
      s.style.top = (e.clientY - r.top - d / 2) + 'px';
      t.appendChild(s);
      s.addEventListener('animationend', function () { s.remove(); });
    });
  }

  // 2) Halaman memudar keluar saat pindah lewat tautan
  document.addEventListener('click', function (e) {
    if (reduce || e.defaultPrevented || e.button || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
    var a = e.target.closest('a[href]');
    if (!a || a.target || a.hasAttribute('download')) return;
    var u = new URL(a.href, location.href);
    if (u.origin !== location.origin) return;
    if (u.pathname === location.pathname && u.search === location.search) return;
    e.preventDefault();
    document.body.classList.add('leaving');
    setTimeout(function () { location.href = a.href; }, 200);
  });

  // 3) Tombol kirim menampilkan putaran memuat
  document.addEventListener('submit', function (e) {
    setTimeout(function () {
      if (e.defaultPrevented) return;
      var b = e.submitter || e.target.querySelector('button[type=submit]');
      if (b) b.classList.add('loading');
    }, 0);
  });

  // 4) Menu akun
  var ub = document.getElementById('userbtn'), um = document.getElementById('usermenu');
  if (ub && um) {
    ub.addEventListener('click', function (e) {
      e.stopPropagation();
      var o = um.classList.toggle('open');
      ub.setAttribute('aria-expanded', o);
    });
    document.addEventListener('click', function () { um.classList.remove('open'); ub.setAttribute('aria-expanded', 'false'); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { um.classList.remove('open'); ub.setAttribute('aria-expanded', 'false'); } });
  }

  // Tombol Kembali di browser: bersihkan keadaan animasi
  window.addEventListener('pageshow', function () {
    document.body.classList.remove('leaving');
    document.querySelectorAll('.loading').forEach(function (b) { b.classList.remove('loading'); });
  });
})();
</script>
</body>
</html>
