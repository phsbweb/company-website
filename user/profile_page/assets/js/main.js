// Global Scroll & UI Functionality
document.addEventListener("DOMContentLoaded", function () {
  /* --- Scroll to Top Button --- */
  const scrollTopBtn = document.getElementById("scrollTopBtn");
  if (scrollTopBtn) {
    window.addEventListener("scroll", function () {
      if (
        document.body.scrollTop > 300 ||
        document.documentElement.scrollTop > 300
      ) {
        scrollTopBtn.classList.add("show");
      } else {
        scrollTopBtn.classList.remove("show");
      }
    });

    scrollTopBtn.addEventListener("click", function (e) {
      e.preventDefault();
      window.scrollTo({ top: 0, behavior: "smooth" });
    });
  }

  /* --- Hero Opacity Fade (Homepage) --- */
  const hero = document.querySelector(".hero");
  if (hero) {
    function updateHeroOpacity() {
      const scrollPos = window.scrollY;
      const heroHeight = hero.offsetHeight;
      let opacity = 1 - scrollPos / (heroHeight / 2);
      if (opacity < 0) opacity = 0;
      if (opacity > 1) opacity = 1;
      hero.style.opacity = opacity;
    }
    window.addEventListener("scroll", updateHeroOpacity);
    updateHeroOpacity();
  }

  /* --- Global Smooth Scroll Arrow --- */
  const arrows = document.querySelectorAll(".scroll-down-arrow");
  arrows.forEach((arrow) => {
    const targetId = arrow.getAttribute("href");
    const target = document.querySelector(targetId);

    if (target) {
      arrow.addEventListener("click", function (e) {
        e.preventDefault();

        // Temporarily disable scroll snapping to allow the custom slow animation to run
        const originalSnapType = getComputedStyle(
          document.documentElement,
        ).scrollSnapType;
        document.documentElement.style.scrollSnapType = "none";

        const targetPosition =
          target.getBoundingClientRect().top + window.pageYOffset;
        const startPosition = window.pageYOffset;
        const distance = targetPosition - startPosition;
        const duration = 1200; // Increased for an even slower, more graceful glide
        let start = null;

        window.requestAnimationFrame(step);

        function step(timestamp) {
          if (!start) start = timestamp;
          const progress = timestamp - start;
          window.scrollTo(0, ease(progress, startPosition, distance, duration));

          if (progress < duration) {
            window.requestAnimationFrame(step);
          } else {
            // Restore snapping once reached
            document.documentElement.style.scrollSnapType = originalSnapType;
          }
        }

        function ease(t, b, c, d) {
          t /= d / 2;
          if (t < 1) return (c / 2) * t * t + b;
          t--;
          return (-c / 2) * (t * (t - 2) - 1) + b;
        }
      });
    }
  });
});
