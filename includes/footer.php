  </div><!-- /.page-body -->
</main><!-- /.main-content -->

</div><!-- /.app-wrapper -->

<script>
// ── Mobile sidebar toggle ──
const sidebar = document.getElementById('sidebar');
document.getElementById('menu-toggle')?.addEventListener('click', () => {
  sidebar.classList.toggle('open');
});

// ── Cerrar sidebar al hacer click fuera (móvil) ──
document.addEventListener('click', (e) => {
  if (window.innerWidth <= 900 && !sidebar.contains(e.target) && !e.target.closest('#menu-toggle')) {
    sidebar.classList.remove('open');
  }
});

// ── Fade-in inicial ──
document.querySelectorAll('.fade-in').forEach((el, i) => {
  el.style.animationDelay = (i * 0.04) + 's';
});
</script>
</body>
</html>
