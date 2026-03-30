/* ============================================
   ELITEFOLIO — JavaScript Interactions
   ============================================ */

// ===== MOBILE NAV MENU TOGGLE =====
;(function () {
  document.addEventListener('DOMContentLoaded', () => {
    const hamburger = document.getElementById('nav-hamburger');
    const mobileMenu = document.getElementById('nav-mobile-menu');
    const mobileLinks = document.querySelectorAll('.nav-mobile-link');
    
    if (hamburger && mobileMenu) {
      hamburger.addEventListener('click', () => {
        mobileMenu.classList.toggle('active');
        const isExpanded = mobileMenu.classList.contains('active');
        hamburger.setAttribute('aria-expanded', isExpanded);
        
        // Optional: animate hamburger lines to form X
        const spans = hamburger.querySelectorAll('span');
        if (spans.length === 3) {
          if (isExpanded) {
            spans[0].style.transform = 'translateY(7px) rotate(45deg)';
            spans[1].style.opacity = '0';
            spans[2].style.transform = 'translateY(-7px) rotate(-45deg)';
          } else {
            spans[0].style.transform = 'translateY(0) rotate(0)';
            spans[1].style.opacity = '1';
            spans[2].style.transform = 'translateY(0) rotate(0)';
          }
        }
      });
      
      // Close menu when a link is clicked
      mobileLinks.forEach(link => {
        link.addEventListener('click', () => {
          mobileMenu.classList.remove('active');
          hamburger.setAttribute('aria-expanded', 'false');
          
          const spans = hamburger.querySelectorAll('span');
          if (spans.length === 3) {
            spans[0].style.transform = 'translateY(0) rotate(0)';
            spans[1].style.opacity = '1';
            spans[2].style.transform = 'translateY(0) rotate(0)';
          }
        });
      });
    }
  });
})();

// ===== HEADER HIDE/SHOW ON SCROLL =====
;(function () {
  const header = document.getElementById('header');
  let lastScroll = 0;
  let ticking = false;

  window.addEventListener('scroll', () => {
    if (!ticking) {
      requestAnimationFrame(() => {
        const currentScroll = window.pageYOffset;
        
        if (currentScroll <= 80) {
          header.classList.remove('hidden');
        } else if (currentScroll > lastScroll) {
          // Scrolling down
          header.classList.add('hidden');
        } else {
          // Scrolling up
          header.classList.remove('hidden');
        }
        
        lastScroll = currentScroll;
        ticking = false;
      });
      ticking = true;
    }
  });
})();

// ===== FAQ ACCORDION =====
function toggleFaq(id) {
  const item = document.getElementById(id);
  if (!item) return;

  const isOpen = item.classList.contains('open');

  // Close all first
  document.querySelectorAll('.faq-item.open').forEach(el => {
    el.classList.remove('open');
  });

  // Open the clicked one if it wasn't open
  if (!isOpen) {
    item.classList.add('open');
  }
}

// ===== SCROLL ANIMATIONS (Intersection Observer) =====
;(function () {
  const elements = document.querySelectorAll(
    '.service-row, .project-card, .exp-card, .testimonial-card, .blog-card, .value-item, .faq-item'
  );

  elements.forEach((el, i) => {
    el.classList.add('animate-on-scroll');
  });

  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
        }
      });
    },
    {
      threshold: 0.1,
      rootMargin: '-50px 0px'
    }
  );

  elements.forEach(el => observer.observe(el));
})();

// ===== PROJECT HOVER GLOW EFFECT =====
;(function () {
  const cards = document.querySelectorAll('.project-card');
  
  cards.forEach(card => {
    card.addEventListener('mousemove', (e) => {
      const rect = card.getBoundingClientRect();
      const x = e.clientX - rect.left;
      const y = e.clientY - rect.top;
      const centerX = rect.width / 2;
      const centerY = rect.height / 2;
      
      const rotateX = ((y - centerY) / centerY) * -4;
      const rotateY = ((x - centerX) / centerX) * 4;
      
      card.style.transform = `perspective(800px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) translateZ(8px)`;
    });
    
    card.addEventListener('mouseleave', () => {
      card.style.transform = '';
    });
  });
})();

// ===== VALUE ITEM ACCORDION (click to expand) =====
;(function () {
  const valueItems = document.querySelectorAll('.value-item');
  
  // Show first item's description by default
  const first = document.getElementById('value-leadership');
  if (first) {
    first.classList.add('active');
  }

  valueItems.forEach(item => {
    item.addEventListener('click', () => {
      // Remove active from all
      valueItems.forEach(v => v.classList.remove('active'));
      // Add active to clicked
      item.classList.add('active');
    });
  });
})();

// ===== TESTIMONIALS INFINITE MARQUEE (row 1 scrolls left, row 2 right) =====
;(function () {
  const grid = document.querySelector('.testimonials-grid');
  if (!grid) return;

  // We'll just let the static grid display — marquee is a bonus enhancement
  // For now, the grid is properly displayed
})();

// ===== SECTION HIGHLIGHTING IN NAV =====
;(function () {
  const sections = document.querySelectorAll('section[id], footer[id]');
  const navLinks = document.querySelectorAll('.nav-link');

  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        const id = entry.target.id;
        navLinks.forEach(link => {
          link.style.color = '';
          if (link.getAttribute('href') === `#${id}`) {
            link.style.color = '#ffffff';
          }
        });
      }
    });
  }, { threshold: 0.4 });

  sections.forEach(section => observer.observe(section));
})();

// ===== HORIZONTAL SCROLL ON VERTICAL SCROLL =====
;(function () {
  const scrollSection = document.querySelector('.horizontal-scroll-section');
  const scrollTrack = document.getElementById('services-track');
  const marquee = document.querySelector('.horizontal-marquee');
  
  if (!scrollSection || !scrollTrack) return;
  
  window.addEventListener('scroll', () => {
    const rect = scrollSection.getBoundingClientRect();
    
    // Wait until the top of the container hits the top of the window
    if (rect.top > 0) {
      scrollTrack.style.transform = `translateX(0px)`;
      if (marquee) marquee.style.setProperty('--fill', '0%');
      return;
    }
    
    const scrollableDistance = rect.height - window.innerHeight;
    const scrolled = -rect.top;
    
    let percentage = scrolled / scrollableDistance;
    percentage = Math.max(0, Math.min(1, percentage));
    
    // Update the text-fill visual progress
    if (marquee) marquee.style.setProperty('--fill', `${percentage * 100}%`);
    
    const trackWidth = scrollTrack.scrollWidth;
    const viewportWidth = window.innerWidth;
    
    if (trackWidth > viewportWidth) {
      const maxTranslate = trackWidth - viewportWidth + window.innerWidth * 0.1; // 10vw padding at end
      scrollTrack.style.transform = `translateX(-${percentage * maxTranslate}px)`;
    }
  });
})();

// ===== NEWSLETTER FORM FEEDBACK =====
;(function () {
  const form = document.querySelector('.newsletter-form');
  if (!form) return;

  form.addEventListener('submit', (e) => {
    e.preventDefault();
    const input = form.querySelector('.newsletter-input');
    const btn = form.querySelector('.newsletter-btn');
    
    if (input.value && input.value.includes('@')) {
      btn.textContent = 'Noted!';
      btn.style.background = '#0ACF83';
      input.value = '';
      
      setTimeout(() => {
        btn.textContent = 'Let me know';
        btn.style.background = '';
      }, 3000);
    }
  });
})();

// ===== TESTIMONIALS SLIDER =====
;(function () {
  const track = document.getElementById('testimonials-track');
  const prevBtn = document.getElementById('testimonial-prev');
  const nextBtn = document.getElementById('testimonial-next');
  const dotsContainer = document.getElementById('testimonial-dots');

  if (!track || !prevBtn || !nextBtn || !dotsContainer) return;

  const slides = Array.from(track.querySelectorAll('.testimonial-card'));
  if (!slides.length) return;

  let currentIndex = 0;

  const updateSlider = () => {
    track.style.transform = `translateX(-${currentIndex * 100}%)`;

    dotsContainer.querySelectorAll('.testimonial-dot').forEach((dot, index) => {
      dot.classList.toggle('active', index === currentIndex);
      dot.setAttribute('aria-current', index === currentIndex ? 'true' : 'false');
    });
  };

  slides.forEach((_, index) => {
    const dot = document.createElement('button');
    dot.type = 'button';
    dot.className = 'testimonial-dot';
    dot.setAttribute('aria-label', `Go to testimonial ${index + 1}`);
    dot.addEventListener('click', () => {
      currentIndex = index;
      updateSlider();
    });
    dotsContainer.appendChild(dot);
  });

  prevBtn.addEventListener('click', () => {
    currentIndex = (currentIndex - 1 + slides.length) % slides.length;
    updateSlider();
  });

  nextBtn.addEventListener('click', () => {
    currentIndex = (currentIndex + 1) % slides.length;
    updateSlider();
  });

  updateSlider();
})();
