(function () {
  'use strict';

  const dataElement = document.getElementById('events-data');
  const grid = document.getElementById('events-collections-grid');
  const lightbox = document.getElementById('events-lightbox');

  if (!dataElement || !grid || !lightbox) return;

  let collections;
  try {
    collections = JSON.parse(dataElement.textContent || '[]');
  } catch (_) {
    return;
  }

  if (!Array.isArray(collections) || collections.length === 0) return;

  collections = collections
    .map(collection => ({
      title: String(collection.title || 'Event gallery'),
      caption: String(collection.caption || ''),
      cover: String(collection.cover || collection.images?.[0]?.src || ''),
      images: Array.isArray(collection.images)
        ? collection.images
            .map(image => ({
              src: String(image.src || ''),
              alt: String(image.alt || ''),
              caption: String(image.caption || ''),
            }))
            .filter(image => image.src)
        : [],
    }))
    .filter(collection => collection.cover && collection.images.length);

  if (collections.length === 0) return;

  const title = document.getElementById('events-lightbox-title');
  const caption = document.getElementById('events-lightbox-caption');
  const counter = document.getElementById('events-lightbox-counter');
  const image = document.getElementById('events-lightbox-image');
  const thumbnails = document.getElementById('events-lightbox-thumbnails');
  const previous = document.getElementById('events-lightbox-prev');
  const next = document.getElementById('events-lightbox-next');
  const stage = document.getElementById('events-lightbox-stage');
  const closeButton = lightbox.querySelector('[data-events-close]');
  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const focusableSelector = 'button:not([disabled]):not([hidden]), a[href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

  const state = {
    collectionIndex: 0,
    imageIndex: 0,
    opener: null,
  };

  function appendTitle(node, value) {
    node.replaceChildren();
    value.split(/(\s+)/).forEach(part => {
      if (/^(?:AI|STEM|COMSATS)$/i.test(part)) {
        const acronym = document.createElement('span');
        acronym.className = 'events-acronym';
        acronym.textContent = part.toUpperCase();
        node.appendChild(acronym);
      } else {
        node.appendChild(document.createTextNode(part));
      }
    });
  }

  function renderThumbnails(collection) {
    if (!thumbnails) return;

    thumbnails.replaceChildren();
    thumbnails.hidden = collection.images.length <= 1;
    if (thumbnails.hidden) return;

    const fragment = document.createDocumentFragment();
    collection.images.forEach((item, index) => {
      const button = document.createElement('button');
      button.type = 'button';
      button.className = 'events-thumb-button';
      button.classList.toggle('is-active', index === state.imageIndex);
      button.setAttribute('aria-label', `Show image ${index + 1}`);
      button.setAttribute('aria-current', index === state.imageIndex ? 'true' : 'false');

      const thumbnail = document.createElement('img');
      thumbnail.src = item.src;
      thumbnail.alt = '';
      thumbnail.loading = 'lazy';
      thumbnail.decoding = 'async';
      button.appendChild(thumbnail);
      button.addEventListener('click', () => {
        state.imageIndex = index;
        renderActiveImage();
      });
      fragment.appendChild(button);
    });
    thumbnails.appendChild(fragment);

    const active = thumbnails.querySelector('.events-thumb-button.is-active');
    active?.scrollIntoView({
      behavior: reducedMotion ? 'auto' : 'smooth',
      block: 'nearest',
      inline: 'center',
    });
  }

  function renderActiveImage() {
    const collection = collections[state.collectionIndex];
    const current = collection.images[state.imageIndex];
    if (!(image instanceof HTMLImageElement) || !current) return;

    image.src = current.src;
    image.alt = current.alt || `${collection.title}, image ${state.imageIndex + 1}`;
    if (title) appendTitle(title, collection.title);
    if (caption) {
      caption.textContent = current.caption || collection.caption || `${collection.images.length} images in this gallery.`;
    }
    if (counter) counter.textContent = `${state.imageIndex + 1} / ${collection.images.length}`;

    const hasMultipleImages = collection.images.length > 1;
    if (previous) previous.hidden = !hasMultipleImages;
    if (next) next.hidden = !hasMultipleImages;
    renderThumbnails(collection);
  }

  function setPageInert(value) {
    document.querySelectorAll('body > header, body > main, body > footer').forEach(element => {
      if (value) element.setAttribute('inert', '');
      else element.removeAttribute('inert');
    });
  }

  function openGallery(collectionIndex, opener) {
    state.collectionIndex = collectionIndex;
    state.imageIndex = 0;
    state.opener = opener;
    lightbox.hidden = false;
    lightbox.setAttribute('aria-hidden', 'false');
    document.body.classList.add('events-gallery-open');
    setPageInert(true);
    renderActiveImage();
    closeButton?.focus();
  }

  function closeGallery() {
    if (lightbox.hidden) return;
    lightbox.hidden = true;
    lightbox.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('events-gallery-open');
    setPageInert(false);
    state.opener?.focus();
  }

  function shiftImage(direction) {
    const collection = collections[state.collectionIndex];
    if (collection.images.length <= 1) return;
    state.imageIndex = (state.imageIndex + direction + collection.images.length) % collection.images.length;
    renderActiveImage();
  }

  function bindCards() {
    grid.querySelectorAll('.events-collection-card').forEach((button, fallbackIndex) => {
      const requestedIndex = Number.parseInt(button.dataset.eventIndex || '', 10);
      const collectionIndex = Number.isInteger(requestedIndex) ? requestedIndex : fallbackIndex;
      if (!collections[collectionIndex]) return;
      button.addEventListener('click', () => openGallery(collectionIndex, button));
    });
  }

  function trapFocus(event) {
    const focusable = Array.from(lightbox.querySelectorAll(focusableSelector));
    if (!focusable.length) return;
    const first = focusable[0];
    const last = focusable[focusable.length - 1];
    if (event.shiftKey && document.activeElement === first) {
      event.preventDefault();
      last.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
      event.preventDefault();
      first.focus();
    }
  }

  function handleKeyboard(event) {
    if (lightbox.hidden) return;
    if (event.key === 'Escape') {
      event.preventDefault();
      closeGallery();
    } else if (event.key === 'ArrowLeft') {
      event.preventDefault();
      shiftImage(-1);
    } else if (event.key === 'ArrowRight') {
      event.preventDefault();
      shiftImage(1);
    } else if (event.key === 'Tab') {
      trapFocus(event);
    }
  }

  function bindSwipe() {
    if (!stage) return;
    let pointerId = null;
    let startX = 0;

    stage.addEventListener('pointerdown', event => {
      pointerId = event.pointerId;
      startX = event.clientX;
    });
    stage.addEventListener('pointerup', event => {
      if (event.pointerId !== pointerId) return;
      const distance = event.clientX - startX;
      pointerId = null;
      if (Math.abs(distance) >= 50) shiftImage(distance > 0 ? -1 : 1);
    });
    stage.addEventListener('pointercancel', () => {
      pointerId = null;
    });
  }

  lightbox.querySelectorAll('[data-events-close]').forEach(node => {
    node.addEventListener('click', closeGallery);
  });
  previous?.addEventListener('click', () => shiftImage(-1));
  next?.addEventListener('click', () => shiftImage(1));
  document.addEventListener('keydown', handleKeyboard);

  bindCards();
  bindSwipe();
})();
