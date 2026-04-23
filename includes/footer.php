    </div><!-- /.page-container -->
  </div><!-- /.main-content -->
</div><!-- /.layout -->

<script>
// Close sidebar on mobile when clicking outside
document.addEventListener('click', function(e) {
  const sidebar = document.getElementById('sidebar');
  const toggle = document.querySelector('.menu-toggle');
  if (sidebar && sidebar.classList.contains('open') && !sidebar.contains(e.target) && e.target !== toggle) {
    sidebar.classList.remove('open');
  }
});

// Modal helpers
function openModal(id) {
  const modal = document.getElementById(id);
  if (modal) {
    modal.classList.add('show');
    document.body.style.overflow = 'hidden';
  }
}
function closeModal(id) {
  const modal = document.getElementById(id);
  if (modal) {
    modal.classList.remove('show');
    document.body.style.overflow = '';
  }
}

// Close modal on backdrop click
document.addEventListener('click', function(e) {
  if (e.target.classList.contains('modal-backdrop')) {
    e.target.classList.remove('show');
    document.body.style.overflow = '';
  }
});

// Auto-dismiss alerts after 5 seconds
document.querySelectorAll('.alert').forEach(function(el) {
  setTimeout(function() {
    el.style.transition = 'opacity .4s ease, transform .4s ease';
    el.style.opacity = '0';
    el.style.transform = 'translateY(-10px)';
    setTimeout(function() { el.remove(); }, 400);
  }, 5000);
});
</script>
</body>
</html>
