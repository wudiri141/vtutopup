document.addEventListener('DOMContentLoaded', () => {
  const menuToggle = document.getElementById('menuToggle');
  const sidebar = document.getElementById('sidebar');
  const mainContent = document.getElementById('mainContent');

  if (!menuToggle || !sidebar || !mainContent) return;

  // Toggle sidebar
  menuToggle.addEventListener('click', (e) => {
    e.stopPropagation();

    if (window.innerWidth <= 768) {
      // Mobile
      sidebar.classList.toggle('active');
    } else {
      // Desktop
      sidebar.classList.toggle('collapsed');
      mainContent.classList.toggle('expanded');
    }
  });

  // Close mobile sidebar on outside click
  document.addEventListener('click', (e) => {
    if (
      window.innerWidth <= 768 &&
      !sidebar.contains(e.target) &&
      !menuToggle.contains(e.target)
    ) {
      sidebar.classList.remove('active');
    }
  });

  // Prevent sidebar click closing
  sidebar.addEventListener('click', (e) => e.stopPropagation());

  // Active menu link
  document.querySelectorAll('.menu ul li a').forEach(link => {
    link.addEventListener('click', function () {
      document.querySelectorAll('.menu ul li a').forEach(i => i.classList.remove('active'));
      this.classList.add('active');
    });
  });

  // Generate account button
  const generateBtn = document.querySelector('.generate-account-btn');
  if (generateBtn) {
    generateBtn.addEventListener('click', () => {
      alert('Virtual account generation in progress...');
    });
  }
});

document.addEventListener('DOMContentLoaded', () => {
  const menuToggle = document.getElementById('menuToggle');
  const sidebar = document.getElementById('sidebar');
  const mainContent = document.getElementById('mainContent');

  if (!menuToggle || !sidebar || !mainContent) return;

  menuToggle.addEventListener('click', (e) => {
    e.stopPropagation();
    if (window.innerWidth <= 768) {
      sidebar.classList.toggle('active');
    } else {
      sidebar.classList.toggle('collapsed');
      mainContent.classList.toggle('expanded');
    }
  });

  document.addEventListener('click', (e) => {
    if (window.innerWidth <= 768 &&
        !sidebar.contains(e.target) &&
        !menuToggle.contains(e.target)) {
      sidebar.classList.remove('active');
    }
  });

  sidebar.addEventListener('click', (e) => e.stopPropagation());

  // Active menu
  document.querySelectorAll('.menu ul li a').forEach(link => {
    link.addEventListener('click', function () {
      document.querySelectorAll('.menu ul li a').forEach(i => i.classList.remove('active'));
      this.classList.add('active');
    });
  });

  // Generate account button
  const generateBtn = document.querySelector('.generate-account-btn');
  if (generateBtn) {
    generateBtn.addEventListener('click', () => {
      alert('Virtual account generation in progress...');
    });
  }
});
