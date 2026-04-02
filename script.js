/* ============================================
   ELITEFOLIO — JavaScript Interactions
   ============================================ */

// ===== MOBILE NAV MENU TOGGLE =====
;(function () {
  document.addEventListener('DOMContentLoaded', () => {
    const insertEventsLinks = () => {
      const navLeft = document.querySelector('.nav-links-left');
      if (navLeft && !navLeft.querySelector('a[href="events.html"]')) {
        const divider = document.createElement('span');
        divider.className = 'nav-divider';
        divider.textContent = '/';

        const link = document.createElement('a');
        link.href = 'events.html';
        link.className = 'nav-link';
        link.textContent = 'Events';

        navLeft.appendChild(divider);
        navLeft.appendChild(link);
      }

      const mobileMenu = document.getElementById('nav-mobile-menu');
      const mobileWorkLink = mobileMenu?.querySelector('a[href="work.html"]');
      if (mobileMenu && mobileWorkLink && !mobileMenu.querySelector('a[href="events.html"]')) {
        const link = document.createElement('a');
        link.href = 'events.html';
        link.className = 'nav-mobile-link';
        link.textContent = 'Events';
        mobileWorkLink.insertAdjacentElement('afterend', link);
      }

      const footerFeatures = Array.from(document.querySelectorAll('.footer-col')).find((column) => {
        return column.querySelector('.footer-col-title')?.textContent?.trim() === 'Features';
      });
      const footerWorkLink = footerFeatures?.querySelector('a[href="work.html"]');

      if (footerFeatures && footerWorkLink && !footerFeatures.querySelector('a[href="events.html"]')) {
        const link = document.createElement('a');
        link.href = 'events.html';
        link.className = 'footer-link';
        link.textContent = 'Events';
        footerWorkLink.insertAdjacentElement('afterend', link);
      }
    };

    insertEventsLinks();

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
    '.service-row, .project-card, .exp-card, .testimonial-card, .blog-card, .blog-feature-card, .blog-archive-card, .value-item, .faq-item, .dashboard-panel, .dashboard-projects-panel, .dashboard-project-slide, .events-intro-shell, .events-collection-card'
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

// ===== INTRO VIDEO PLAY/PAUSE ON SECTION VISIBILITY =====
;(function () {
  const iframe = document.getElementById('intro-video-player');
  const section = document.querySelector('.intro-video-section');

  if (!iframe || !section) return;

  let player = null;
  let playerReady = false;
  let shouldPlay = false;

  const syncPlayback = () => {
    if (!playerReady || !player) return;

    if (shouldPlay) {
      player.playVideo();
    } else {
      player.pauseVideo();
    }
  };

  const observer = new IntersectionObserver((entries) => {
    const entry = entries[0];
    shouldPlay = Boolean(entry && entry.isIntersecting && entry.intersectionRatio >= 0.45);
    syncPlayback();
  }, {
    threshold: [0.15, 0.45, 0.75]
  });

  observer.observe(section);

  document.addEventListener('visibilitychange', () => {
    if (document.hidden && playerReady && player) {
      player.pauseVideo();
    } else {
      syncPlayback();
    }
  });

  const bootPlayer = () => {
    player = new window.YT.Player('intro-video-player', {
      events: {
        onReady: (event) => {
          playerReady = true;
          event.target.setVolume(100);
          event.target.mute();
          syncPlayback();
        }
      }
    });
  };

  const loadYouTubeApi = () => {
    try {
      const url = new URL(iframe.src);
      url.searchParams.set('origin', window.location.origin);
      iframe.src = url.toString();
    } catch (error) {
      // Ignore URL parsing issues and fall back to the existing embed URL.
    }

    if (window.YT && typeof window.YT.Player === 'function') {
      bootPlayer();
      return;
    }

    const previousReady = window.onYouTubeIframeAPIReady;
    window.onYouTubeIframeAPIReady = () => {
      if (typeof previousReady === 'function') {
        previousReady();
      }
      bootPlayer();
    };

    if (!document.querySelector('script[src="https://www.youtube.com/iframe_api"]')) {
      const script = document.createElement('script');
      script.src = 'https://www.youtube.com/iframe_api';
      document.head.appendChild(script);
    }
  };

  loadYouTubeApi();
})();

// ===== TESTIMONIALS INFINITE MARQUEE (row 1 scrolls left, row 2 right) =====
;(function () {
  const grid = document.querySelector('.testimonials-grid');
  if (!grid) return;

  // We'll just let the static grid display — marquee is a bonus enhancement
  // For now, the grid is properly displayed
})();

// ===== DASHBOARD PROJECT CAROUSEL =====
;(function () {
  const carousel = document.getElementById('dashboard-carousel');
  const track = document.getElementById('dashboard-carousel-track');
  const prevBtn = document.getElementById('dashboard-prev');
  const nextBtn = document.getElementById('dashboard-next');
  const dotsContainer = document.getElementById('dashboard-dots');

  if (!carousel || !track || !prevBtn || !nextBtn || !dotsContainer) return;

  const slides = Array.from(track.querySelectorAll('.dashboard-project-slide'));
  if (!slides.length) return;

  let currentIndex = 0;

  const updateControls = () => {
    dotsContainer.querySelectorAll('.dashboard-dot').forEach((dot, index) => {
      dot.classList.toggle('active', index === currentIndex);
      dot.setAttribute('aria-current', index === currentIndex ? 'true' : 'false');
    });

    prevBtn.disabled = currentIndex === 0;
    nextBtn.disabled = currentIndex === slides.length - 1;
  };

  const showIndex = (index) => {
    currentIndex = Math.max(0, Math.min(slides.length - 1, index));
    track.style.transform = `translateX(-${slides[currentIndex].offsetLeft}px)`;
    updateControls();
  };

  slides.forEach((_, index) => {
    const dot = document.createElement('button');
    dot.type = 'button';
    dot.className = 'dashboard-dot';
    dot.setAttribute('aria-label', `Go to project ${index + 1}`);
    dot.addEventListener('click', () => {
      showIndex(index);
    });
    dotsContainer.appendChild(dot);
  });

  prevBtn.addEventListener('click', () => {
    showIndex(currentIndex - 1);
  });

  nextBtn.addEventListener('click', () => {
    showIndex(currentIndex + 1);
  });

  window.addEventListener('resize', () => {
    showIndex(currentIndex);
  });

  showIndex(0);
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

// ===== CERTIFICATIONS FOCUS RAIL =====
;(function () {
  const rail = document.getElementById('certifications-rail');
  const track = document.getElementById('certifications-track');
  const prevBtn = document.getElementById('certifications-prev');
  const nextBtn = document.getElementById('certifications-next');
  const dotsContainer = document.getElementById('certifications-dots');
  const badge = document.getElementById('certifications-badge');
  const title = document.getElementById('certifications-title');
  const description = document.getElementById('certifications-desc');
  const counter = document.getElementById('certifications-counter');
  const ambienceImage = document.getElementById('certifications-ambience-image');
  const expandBtn = document.getElementById('certifications-expand');
  const modal = document.getElementById('certifications-modal');
  const modalImage = document.getElementById('certifications-modal-image');
  const modalTitle = document.getElementById('certifications-modal-title');
  const modalBadge = document.getElementById('certifications-modal-badge');
  const closeModalBtn = document.getElementById('close-certifications-modal');

  if (!rail || !track || !prevBtn || !nextBtn || !dotsContainer) return;

  const cards = Array.from(track.querySelectorAll('.cert-focus-card'));
  if (!cards.length) return;

  let currentIndex = 0;
  let lastWheelTime = 0;
  let touchStartX = 0;
  let touchStartY = 0;

  const wrapIndex = (value) => (value + cards.length) % cards.length;

  const getShortestOffset = (index) => {
    let offset = index - currentIndex;
    const half = Math.floor(cards.length / 2);

    if (offset > half) offset -= cards.length;
    if (offset < -half) offset += cards.length;

    return offset;
  };

  const goTo = (index) => {
    currentIndex = wrapIndex(index);
    updateRail();
  };

  const getCardMeta = (card) => {
    if (!card) return null;

    const image = card.dataset.certImage || card.querySelector('img')?.getAttribute('src') || '';
    const alt = card.querySelector('img')?.getAttribute('alt') || '';

    return {
      badge: card.dataset.certBadge || '',
      title: card.dataset.certTitle || '',
      description: card.dataset.certDesc || '',
      image,
      alt
    };
  };

  const openModal = (card) => {
    if (!modal) return;

    const meta = getCardMeta(card);
    if (!meta) return;

    if (modalImage) {
      modalImage.setAttribute('src', meta.image);
      modalImage.setAttribute('alt', meta.alt || meta.title);
    }

    if (modalTitle) modalTitle.textContent = meta.title;
    if (modalBadge) modalBadge.textContent = meta.badge;

    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
  };

  const closeModal = () => {
    if (!modal) return;
    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
  };

  const updateRail = () => {
    const activeCard = cards[currentIndex];
    if (!activeCard) return;

    const activeMeta = getCardMeta(activeCard);
    if (!activeMeta) return;

    const activeImage = activeMeta.image;
    if (ambienceImage && activeImage) {
      ambienceImage.setAttribute('src', activeImage);
    }

    if (badge) badge.textContent = activeMeta.badge;
    if (title) title.textContent = activeMeta.title;
    if (description) description.textContent = activeMeta.description;
    if (counter) {
      counter.textContent = `${String(currentIndex + 1).padStart(2, '0')} / ${String(cards.length).padStart(2, '0')}`;
    }

    cards.forEach((card, index) => {
      const offset = getShortestOffset(index);
      const abs = Math.abs(offset);
      const scale = offset === 0 ? 1 : abs === 1 ? 0.86 : abs === 2 ? 0.72 : 0.62;
      const opacity = offset === 0 ? 1 : abs === 1 ? 0.58 : abs === 2 ? 0.18 : 0;
      const blur = offset === 0 ? '0px' : abs === 1 ? '1.5px' : abs === 2 ? '5px' : '8px';
      const brightness = offset === 0 ? '1' : abs === 1 ? '0.7' : abs === 2 ? '0.42' : '0.28';
      const isVisible = abs <= 2;

      card.classList.toggle('is-active', offset === 0);
      card.classList.toggle('is-hidden', !isVisible);
      card.style.setProperty('--offset', String(offset));
      card.style.setProperty('--abs', String(Math.min(abs, 3)));
      card.style.setProperty('--scale', String(scale));
      card.style.setProperty('--opacity', String(opacity));
      card.style.setProperty('--blur', blur);
      card.style.setProperty('--brightness', brightness);
      card.style.zIndex = String(offset === 0 ? 50 : 40 - abs);
      card.setAttribute('aria-hidden', offset === 0 ? 'false' : 'true');
    });

    dotsContainer.querySelectorAll('.cert-focus-dot').forEach((dot, index) => {
      dot.classList.toggle('active', index === currentIndex);
      dot.setAttribute('aria-current', index === currentIndex ? 'true' : 'false');
    });
  };

  cards.forEach((card, index) => {
    card.addEventListener('click', () => {
      const offset = getShortestOffset(index);
      if (offset === 0) {
        openModal(card);
        return;
      }
      goTo(currentIndex + offset);
    });
  });

  cards.forEach((_, index) => {
    const dot = document.createElement('button');
    dot.type = 'button';
    dot.className = 'cert-focus-dot';
    dot.setAttribute('aria-label', `Go to certification ${index + 1}`);
    dot.addEventListener('click', () => {
      goTo(index);
    });
    dotsContainer.appendChild(dot);
  });

  prevBtn.addEventListener('click', () => {
    goTo(currentIndex - 1);
  });

  nextBtn.addEventListener('click', () => {
    goTo(currentIndex + 1);
  });

  if (expandBtn) {
    expandBtn.addEventListener('click', () => {
      openModal(cards[currentIndex]);
    });
  }

  if (closeModalBtn) {
    closeModalBtn.addEventListener('click', closeModal);
  }

  if (modal) {
    modal.querySelectorAll('[data-close-certifications-modal]').forEach((element) => {
      element.addEventListener('click', closeModal);
    });
  }

  rail.addEventListener('keydown', (event) => {
    if (event.key === 'ArrowLeft') {
      event.preventDefault();
      goTo(currentIndex - 1);
    }

    if (event.key === 'ArrowRight') {
      event.preventDefault();
      goTo(currentIndex + 1);
    }
  });

  rail.addEventListener('wheel', (event) => {
    const now = Date.now();
    if (now - lastWheelTime < 400) return;

    const isHorizontal = Math.abs(event.deltaX) > Math.abs(event.deltaY);
    if (!isHorizontal || Math.abs(event.deltaX) < 20) return;

    event.preventDefault();
    if (event.deltaX > 0) {
      goTo(currentIndex + 1);
    } else {
      goTo(currentIndex - 1);
    }

    lastWheelTime = now;
  }, { passive: false });

  rail.addEventListener('touchstart', (event) => {
    const touch = event.changedTouches[0];
    if (!touch) return;
    touchStartX = touch.clientX;
    touchStartY = touch.clientY;
  }, { passive: true });

  rail.addEventListener('touchend', (event) => {
    const touch = event.changedTouches[0];
    if (!touch) return;

    const deltaX = touch.clientX - touchStartX;
    const deltaY = touch.clientY - touchStartY;

    if (Math.abs(deltaX) > 45 && Math.abs(deltaX) > Math.abs(deltaY)) {
      if (deltaX < 0) {
        goTo(currentIndex + 1);
      } else {
        goTo(currentIndex - 1);
      }
    }
  }, { passive: true });

  updateRail();

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && modal?.classList.contains('is-open')) {
      closeModal();
    }
  });
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
