document.addEventListener('DOMContentLoaded', () => {
  /* 
    All JavaScript for your site, including the modern chat panel logic
    and inline code comments to explain each part.
  */
  // Example: Navbar links for active state highlight
  const navLinks = document.querySelectorAll('.nav-link');

  // ========== 6. NAVBAR ACTIVE LINK LOGIC ==========
  navLinks.forEach(link => {
    link.addEventListener('click', function () {
      navLinks.forEach(el => el.classList.remove('active'));
      this.classList.add('active');
    });

      // Parallax wave effect
  document.addEventListener("mousemove", (e) => {
    const wave = document.getElementById("hero-wave");
    if (!wave) return;

    const x = (e.clientX / window.innerWidth - 0.5) * 30;
    const y = (e.clientY / window.innerHeight - 0.5) * 20;

    wave.style.transform = `translate(${x}px, ${y}px)`;
  });

  });

});
