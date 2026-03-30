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

// ===== INTRO VIDEO VISIBILITY PLAYBACK =====
;(function () {
  const video = document.getElementById('intro-video-player');
  if (!video) return;

  video.muted = true;

  const tryPlay = () => {
    const playPromise = video.play();
    if (playPromise && typeof playPromise.catch === 'function') {
      playPromise.catch(() => {});
    }
  };

  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        tryPlay();
      } else {
        video.pause();
      }
    });
  }, { threshold: 0.45 });

  observer.observe(video);
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

// ===== SCHEDULE A MEETING MODAL =====
;(function () {
  const modal = document.getElementById('meeting-modal');
  const openBtn = document.getElementById('open-meeting-modal');
  const closeBtn = document.getElementById('close-meeting-modal');
  const calendarGrid = document.getElementById('meeting-calendar-grid');
  const monthLabel = document.getElementById('meeting-current-month');
  const prevMonthBtn = document.getElementById('meeting-prev-month');
  const nextMonthBtn = document.getElementById('meeting-next-month');
  const slotsGrid = document.getElementById('meeting-slots-grid');
  const summary = document.getElementById('meeting-summary');
  const confirmBtn = document.getElementById('confirm-meeting');

  if (!modal || !openBtn || !closeBtn || !calendarGrid || !monthLabel || !prevMonthBtn || !nextMonthBtn || !slotsGrid || !summary || !confirmBtn) {
    return;
  }

  const today = new Date();
  today.setHours(0, 0, 0, 0);

  const bookedOffsets = [2, 5, 9, 14, 18];
  const bookedDates = new Set(
    bookedOffsets.map((offset) => {
      const date = new Date(today);
      date.setDate(today.getDate() + offset);
      return date.toDateString();
    })
  );

  const timeSlots = Array.from({ length: 18 }, (_, index) => {
    const totalMinutes = 9 * 60 + index * 30;
    const hours = Math.floor(totalMinutes / 60);
    const minutes = totalMinutes % 60;
    return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}`;
  });

  let currentMonth = new Date(today.getFullYear(), today.getMonth(), 1);
  let selectedDate = null;
  let selectedTime = null;

  const formatSummary = () => {
    if (!selectedDate || !selectedTime) {
      summary.textContent = 'Select a date and time for your meeting.';
      confirmBtn.disabled = true;
      return;
    }

    summary.textContent = `Your meeting is scheduled for ${selectedDate.toLocaleDateString('en-US', {
      weekday: 'long',
      day: 'numeric',
      month: 'long'
    })} at ${selectedTime}.`;
    confirmBtn.disabled = false;
  };

  const renderSlots = () => {
    slotsGrid.innerHTML = '';

    if (!selectedDate) {
      const empty = document.createElement('p');
      empty.className = 'meeting-summary';
      empty.textContent = 'Choose a date to see available meeting times.';
      slotsGrid.appendChild(empty);
      formatSummary();
      return;
    }

    timeSlots.forEach((time) => {
      const button = document.createElement('button');
      button.type = 'button';
      button.className = 'meeting-slot';
      button.textContent = time;

      if (selectedTime === time) {
        button.classList.add('is-selected');
      }

      button.addEventListener('click', () => {
        selectedTime = time;
        renderSlots();
        formatSummary();
      });

      slotsGrid.appendChild(button);
    });

    formatSummary();
  };

  const renderCalendar = () => {
    calendarGrid.innerHTML = '';
    monthLabel.textContent = currentMonth.toLocaleDateString('en-US', {
      month: 'long',
      year: 'numeric'
    });

    const year = currentMonth.getFullYear();
    const month = currentMonth.getMonth();
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const firstWeekday = firstDay.getDay();
    const totalCells = Math.ceil((firstWeekday + lastDay.getDate()) / 7) * 7;

    for (let index = 0; index < totalCells; index += 1) {
      const dayNumber = index - firstWeekday + 1;
      const cellDate = new Date(year, month, dayNumber);
      const button = document.createElement('button');
      button.type = 'button';
      button.className = 'meeting-day';
      button.textContent = String(cellDate.getDate());

      const isCurrentMonth = cellDate.getMonth() === month;
      const isPast = cellDate < today;
      const isWeekend = cellDate.getDay() === 0 || cellDate.getDay() === 6;
      const isBooked = bookedDates.has(cellDate.toDateString());
      const isDisabled = !isCurrentMonth || isPast || isWeekend || isBooked;

      if (!isCurrentMonth) {
        button.classList.add('is-muted');
      }

      if (isDisabled) {
        button.classList.add('is-disabled');
        button.disabled = true;
      }

      if (selectedDate && cellDate.toDateString() === selectedDate.toDateString()) {
        button.classList.add('is-selected');
      }

      if (!isDisabled && isCurrentMonth) {
        button.addEventListener('click', () => {
          selectedDate = new Date(cellDate);
          selectedTime = null;
          renderCalendar();
          renderSlots();
        });
      }

      calendarGrid.appendChild(button);
    }
  };

  const openModal = () => {
    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
  };

  const closeModal = () => {
    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
  };

  openBtn.addEventListener('click', () => {
    openModal();
  });

  closeBtn.addEventListener('click', closeModal);
  modal.querySelectorAll('[data-close-meeting-modal]').forEach((element) => {
    element.addEventListener('click', closeModal);
  });

  prevMonthBtn.addEventListener('click', () => {
    currentMonth = new Date(currentMonth.getFullYear(), currentMonth.getMonth() - 1, 1);
    renderCalendar();
  });

  nextMonthBtn.addEventListener('click', () => {
    currentMonth = new Date(currentMonth.getFullYear(), currentMonth.getMonth() + 1, 1);
    renderCalendar();
  });

  confirmBtn.addEventListener('click', () => {
    if (!selectedDate || !selectedTime) return;
    alert(`Your meeting is scheduled for ${selectedDate.toLocaleDateString('en-US', {
      weekday: 'long',
      day: 'numeric',
      month: 'long'
    })} at ${selectedTime}.`);
    closeModal();
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && modal.classList.contains('is-open')) {
      closeModal();
    }
  });

  renderCalendar();
  renderSlots();
})();
