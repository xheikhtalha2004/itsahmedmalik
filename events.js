(function () {
  const EVENT_COLLECTIONS = (window.PORTFOLIO_DATA || {}).events || [];

  const grid = document.getElementById("events-collections-grid");
  const lightbox = document.getElementById("events-lightbox");

  if (!grid || !lightbox) return;

  const lightboxTitle = document.getElementById("events-lightbox-title");
  const lightboxCaption = document.getElementById("events-lightbox-caption");
  const lightboxCounter = document.getElementById("events-lightbox-counter");
  const lightboxImage = document.getElementById("events-lightbox-image");
  const thumbnailsStrip = document.getElementById("events-lightbox-thumbnails");
  const prevButton = document.getElementById("events-lightbox-prev");
  const nextButton = document.getElementById("events-lightbox-next");
  const stage = document.getElementById("events-lightbox-stage");

  const state = {
    collectionIndex: 0,
    imageIndex: 0,
  };

  const LOWERCASE_WORDS = new Set([
    "and",
    "or",
    "x",
    "at",
    "by",
    "for",
    "from",
    "of",
    "on",
    "to",
    "with",
    "in",
  ]);

  const ACRONYMS = new Set(["ai", "stem", "comsats"]);

  function formatWord(word, isFirstWord, asHtml) {
    const normalized = word.toLowerCase();

    if (!word) return "";
    if (/^\d/.test(word)) return word;
    if (normalized === "startup.exe") return "Startup.exe";

    if (ACRONYMS.has(normalized)) {
      const value = normalized.toUpperCase();
      return asHtml ? `<span class="events-acronym">${value}</span>` : value;
    }

    if (!isFirstWord && LOWERCASE_WORDS.has(normalized)) {
      return normalized;
    }

    return normalized.charAt(0).toUpperCase() + normalized.slice(1);
  }

  function formatEventTitle(slug, asHtml = false) {
    return slug
      .split("_")
      .map((word, index) => formatWord(word, index === 0, asHtml))
      .join(" ");
  }

  function renderCards() {
    grid.innerHTML = "";

    EVENT_COLLECTIONS.forEach((collection, index) => {
      const formattedTitleText = formatEventTitle(collection.slug);
      const formattedTitleHtml = formatEventTitle(collection.slug, true);
      const button = document.createElement("button");
      button.type = "button";
      button.className = "events-collection-card";
      button.setAttribute("aria-label", `Open ${formattedTitleText} gallery`);
      button.innerHTML = `
        <span class="events-card-media">
          <img src="${collection.cover}" alt="${formattedTitleText}" loading="lazy" decoding="async" />
        </span>
        <span class="events-card-overlay"></span>
        <span class="events-card-content">
          <strong class="events-card-title">${formattedTitleHtml}</strong>
          <span class="events-card-meta">${collection.images.length} images</span>
        </span>
      `;
      button.addEventListener("click", () => openGallery(index));
      grid.appendChild(button);
    });
  }

  function renderActiveImage() {
    const collection = EVENT_COLLECTIONS[state.collectionIndex];
    const currentImage = collection.images[state.imageIndex];
    const formattedTitleText = formatEventTitle(collection.slug);
    const formattedTitleHtml = formatEventTitle(collection.slug, true);

    if (!(lightboxImage instanceof HTMLImageElement)) return;

    lightboxImage.src = currentImage;
    lightboxImage.alt = `${formattedTitleText} image ${state.imageIndex + 1}`;

    if (lightboxTitle) lightboxTitle.innerHTML = formattedTitleHtml;
    if (lightboxCaption) {
      lightboxCaption.textContent = `${collection.images.length} images in this gallery.`;
    }
    if (lightboxCounter) {
      lightboxCounter.textContent = `${state.imageIndex + 1} / ${collection.images.length}`;
    }

    renderThumbnails(collection);
  }

  function renderThumbnails(collection) {
    if (!thumbnailsStrip) return;

    thumbnailsStrip.innerHTML = "";

    collection.images.forEach((image, index) => {
      const button = document.createElement("button");
      button.type = "button";
      button.className = "events-thumb-button";
      if (index === state.imageIndex) {
        button.classList.add("is-active");
      }
      button.setAttribute("aria-label", `Show image ${index + 1}`);
      button.innerHTML = `<img src="${image}" alt="" loading="lazy" decoding="async" />`;
      button.addEventListener("click", () => {
        state.imageIndex = index;
        renderActiveImage();
      });
      thumbnailsStrip.appendChild(button);
    });

    const activeThumb = thumbnailsStrip.querySelector(".events-thumb-button.is-active");
    activeThumb?.scrollIntoView({ behavior: "smooth", block: "nearest", inline: "center" });
  }

  function openGallery(collectionIndex) {
    state.collectionIndex = collectionIndex;
    state.imageIndex = 0;
    renderActiveImage();
    lightbox.hidden = false;
    lightbox.setAttribute("aria-hidden", "false");
    document.body.classList.add("events-gallery-open");
  }

  function closeGallery() {
    lightbox.hidden = true;
    lightbox.setAttribute("aria-hidden", "true");
    document.body.classList.remove("events-gallery-open");
  }

  function shiftImage(direction) {
    const collection = EVENT_COLLECTIONS[state.collectionIndex];
    const nextIndex = state.imageIndex + direction;
    const maxIndex = collection.images.length - 1;

    if (nextIndex < 0) {
      state.imageIndex = maxIndex;
    } else if (nextIndex > maxIndex) {
      state.imageIndex = 0;
    } else {
      state.imageIndex = nextIndex;
    }

    renderActiveImage();
  }

  function handleKeyboard(event) {
    if (lightbox.hidden) return;

    if (event.key === "Escape") {
      closeGallery();
    }

    if (event.key === "ArrowLeft") {
      shiftImage(-1);
    }

    if (event.key === "ArrowRight") {
      shiftImage(1);
    }
  }

  function bindSwipe() {
    if (!stage) return;

    let startX = 0;
    let endX = 0;

    stage.addEventListener("pointerdown", (event) => {
      startX = event.clientX;
      endX = event.clientX;
    });

    stage.addEventListener("pointermove", (event) => {
      endX = event.clientX;
    });

    stage.addEventListener("pointerup", () => {
      const distance = endX - startX;
      if (Math.abs(distance) < 50) return;
      shiftImage(distance > 0 ? -1 : 1);
    });
  }

  lightbox.querySelectorAll("[data-events-close]").forEach((node) => {
    node.addEventListener("click", closeGallery);
  });

  prevButton?.addEventListener("click", () => shiftImage(-1));
  nextButton?.addEventListener("click", () => shiftImage(1));
  document.addEventListener("keydown", handleKeyboard);

  renderCards();
  bindSwipe();
})();
