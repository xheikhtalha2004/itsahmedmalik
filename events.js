(function () {
  const EVENT_COLLECTIONS = [
    {
      slug: "comsats_university_islamabad_visit",
      cover: "images/events/comsats_university_islamabad_visit/WhatsApp Image 2026-03-30 at 10.44.59 PM.jpeg",
      images: [
        "images/events/comsats_university_islamabad_visit/WhatsApp Image 2026-03-30 at 10.44.59 PM.jpeg",
        "images/events/comsats_university_islamabad_visit/WhatsApp Image 2026-03-30 at 10.45.02 PM (1).jpeg",
        "images/events/comsats_university_islamabad_visit/WhatsApp Image 2026-03-30 at 10.45.02 PM (2).jpeg",
        "images/events/comsats_university_islamabad_visit/WhatsApp Image 2026-03-30 at 10.45.02 PM (3).jpeg",
        "images/events/comsats_university_islamabad_visit/WhatsApp Image 2026-03-30 at 10.45.02 PM.jpeg",
        "images/events/comsats_university_islamabad_visit/WhatsApp Image 2026-03-30 at 10.45.03 PM.jpeg",
      ],
    },
    {
      slug: "ed_tech_future_forum_x_ai_summit",
      cover: "images/events/ed_tech_future_forum_x_ai_summit/WhatsApp Image 2026-03-30 at 10.46.40 PM.jpeg",
      images: [
        "images/events/ed_tech_future_forum_x_ai_summit/WhatsApp Image 2026-03-30 at 10.46.40 PM.jpeg",
        "images/events/ed_tech_future_forum_x_ai_summit/WhatsApp Image 2026-03-30 at 10.46.41 PM (1).jpeg",
        "images/events/ed_tech_future_forum_x_ai_summit/WhatsApp Image 2026-03-30 at 10.46.41 PM.jpeg",
        "images/events/ed_tech_future_forum_x_ai_summit/WhatsApp Image 2026-03-30 at 10.46.42 PM (1).jpeg",
        "images/events/ed_tech_future_forum_x_ai_summit/WhatsApp Image 2026-03-30 at 10.46.42 PM (2).jpeg",
        "images/events/ed_tech_future_forum_x_ai_summit/WhatsApp Image 2026-03-30 at 10.46.42 PM.jpeg",
        "images/events/ed_tech_future_forum_x_ai_summit/WhatsApp Image 2026-03-30 at 10.46.43 PM.jpeg",
        "images/events/ed_tech_future_forum_x_ai_summit/WhatsApp Image 2026-03-30 at 10.46.44 PM (1).jpeg",
        "images/events/ed_tech_future_forum_x_ai_summit/WhatsApp Image 2026-03-30 at 10.46.44 PM.jpeg",
        "images/events/ed_tech_future_forum_x_ai_summit/WhatsApp Image 2026-03-30 at 10.46.45 PM.jpeg",
        "images/events/ed_tech_future_forum_x_ai_summit/WhatsApp Image 2026-03-30 at 10.46.46 PM (1).jpeg",
        "images/events/ed_tech_future_forum_x_ai_summit/WhatsApp Image 2026-03-30 at 10.46.46 PM.jpeg",
        "images/events/ed_tech_future_forum_x_ai_summit/WhatsApp Image 2026-03-30 at 10.46.47 PM (1).jpeg",
        "images/events/ed_tech_future_forum_x_ai_summit/WhatsApp Image 2026-03-30 at 10.46.47 PM.jpeg",
        "images/events/ed_tech_future_forum_x_ai_summit/WhatsApp Image 2026-03-30 at 10.52.53 PM.jpeg",
      ],
    },
    {
      slug: "indus_ai_week",
      cover: "images/events/indus_ai_week/WhatsApp Image 2026-03-30 at 10.47.35 PM (1).jpeg",
      images: [
        "images/events/indus_ai_week/WhatsApp Image 2026-03-30 at 10.47.35 PM (1).jpeg",
        "images/events/indus_ai_week/WhatsApp Image 2026-03-30 at 10.47.35 PM.jpeg",
        "images/events/indus_ai_week/WhatsApp Image 2026-03-30 at 10.47.36 PM (1).jpeg",
        "images/events/indus_ai_week/WhatsApp Image 2026-03-30 at 10.47.36 PM.jpeg",
        "images/events/indus_ai_week/WhatsApp Image 2026-03-30 at 10.47.37 PM.jpeg",
      ],
    },
    {
      slug: "industrial_visit_from_comsats_university",
      cover: "images/events/industrial_visit_from_comsats_university/WhatsApp Image 2026-03-30 at 10.46.30 PM.jpeg",
      images: [
        "images/events/industrial_visit_from_comsats_university/WhatsApp Image 2026-03-30 at 10.46.30 PM.jpeg",
        "images/events/industrial_visit_from_comsats_university/WhatsApp Image 2026-03-30 at 10.46.31 PM (1).jpeg",
        "images/events/industrial_visit_from_comsats_university/WhatsApp Image 2026-03-30 at 10.46.31 PM (2).jpeg",
        "images/events/industrial_visit_from_comsats_university/WhatsApp Image 2026-03-30 at 10.46.31 PM.jpeg",
        "images/events/industrial_visit_from_comsats_university/WhatsApp Image 2026-03-30 at 10.46.32 PM (1).jpeg",
        "images/events/industrial_visit_from_comsats_university/WhatsApp Image 2026-03-30 at 10.46.32 PM.jpeg",
        "images/events/industrial_visit_from_comsats_university/WhatsApp Image 2026-03-30 at 10.46.33 PM (1).jpeg",
        "images/events/industrial_visit_from_comsats_university/WhatsApp Image 2026-03-30 at 10.46.33 PM.jpeg",
        "images/events/industrial_visit_from_comsats_university/WhatsApp Image 2026-03-30 at 10.46.34 PM.jpeg",
      ],
    },
    {
      slug: "innovate_4.0",
      cover: "images/events/innovate_4.0/WhatsApp Image 2026-03-30 at 10.47.02 PM (1).jpeg",
      images: [
        "images/events/innovate_4.0/WhatsApp Image 2026-03-30 at 10.47.02 PM (1).jpeg",
        "images/events/innovate_4.0/WhatsApp Image 2026-03-30 at 10.47.02 PM (2).jpeg",
        "images/events/innovate_4.0/WhatsApp Image 2026-03-30 at 10.47.02 PM (3).jpeg",
        "images/events/innovate_4.0/WhatsApp Image 2026-03-30 at 10.47.02 PM.jpeg",
      ],
    },
    {
      slug: "job_fair_at_iqra_university",
      cover: "images/events/job_fair_at_iqra_university/WhatsApp Image 2026-03-30 at 10.46.35 PM.jpeg",
      images: [
        "images/events/job_fair_at_iqra_university/WhatsApp Image 2026-03-30 at 10.46.35 PM.jpeg",
        "images/events/job_fair_at_iqra_university/WhatsApp Image 2026-03-30 at 10.46.36 PM (1).jpeg",
        "images/events/job_fair_at_iqra_university/WhatsApp Image 2026-03-30 at 10.46.36 PM.jpeg",
        "images/events/job_fair_at_iqra_university/WhatsApp Image 2026-03-30 at 10.46.37 PM.jpeg",
        "images/events/job_fair_at_iqra_university/WhatsApp Image 2026-03-30 at 10.46.38 PM (1).jpeg",
        "images/events/job_fair_at_iqra_university/WhatsApp Image 2026-03-30 at 10.46.38 PM.jpeg",
        "images/events/job_fair_at_iqra_university/WhatsApp Image 2026-03-30 at 10.46.39 PM (1).jpeg",
        "images/events/job_fair_at_iqra_university/WhatsApp Image 2026-03-30 at 10.46.39 PM (2).jpeg",
        "images/events/job_fair_at_iqra_university/WhatsApp Image 2026-03-30 at 10.46.39 PM.jpeg",
        "images/events/job_fair_at_iqra_university/WhatsApp Image 2026-03-30 at 10.46.40 PM.jpeg",
      ],
    },
    {
      slug: "nature_with_friends",
      cover: "images/events/nature_with_friends/WhatsApp Image 2026-03-30 at 10.55.19 PM (1).jpeg",
      images: [
        "images/events/nature_with_friends/WhatsApp Image 2026-03-30 at 10.55.19 PM (1).jpeg",
        "images/events/nature_with_friends/WhatsApp Image 2026-03-30 at 10.55.19 PM (2).jpeg",
        "images/events/nature_with_friends/WhatsApp Image 2026-03-30 at 10.55.19 PM (3).jpeg",
        "images/events/nature_with_friends/WhatsApp Image 2026-03-30 at 10.55.19 PM (4).jpeg",
        "images/events/nature_with_friends/WhatsApp Image 2026-03-30 at 10.55.19 PM (5).jpeg",
        "images/events/nature_with_friends/WhatsApp Image 2026-03-30 at 10.55.19 PM (6).jpeg",
        "images/events/nature_with_friends/WhatsApp Image 2026-03-30 at 10.55.19 PM.jpeg",
        "images/events/nature_with_friends/WhatsApp Image 2026-03-30 at 11.00.28 PM.jpeg",
        "images/events/nature_with_friends/WhatsApp Image 2026-03-30 at 11.01.29 PM.jpeg",
        "images/events/nature_with_friends/WhatsApp Image 2026-03-30 at 11.01.33 PM (1).jpeg",
        "images/events/nature_with_friends/WhatsApp Image 2026-03-30 at 11.01.33 PM.jpeg",
        "images/events/nature_with_friends/WhatsApp Image 2026-03-30 at 11.01.34 PM (1).jpeg",
        "images/events/nature_with_friends/WhatsApp Image 2026-03-30 at 11.01.34 PM.jpeg",
        "images/events/nature_with_friends/WhatsApp Image 2026-03-30 at 11.01.35 PM (1).jpeg",
        "images/events/nature_with_friends/WhatsApp Image 2026-03-30 at 11.01.35 PM.jpeg",
        "images/events/nature_with_friends/WhatsApp Image 2026-03-30 at 11.01.36 PM (1).jpeg",
        "images/events/nature_with_friends/WhatsApp Image 2026-03-30 at 11.01.36 PM (2).jpeg",
        "images/events/nature_with_friends/WhatsApp Image 2026-03-30 at 11.01.36 PM.jpeg",
        "images/events/nature_with_friends/WhatsApp Image 2026-03-30 at 11.01.37 PM.jpeg",
        "images/events/nature_with_friends/WhatsApp Image 2026-03-30 at 11.01.38 PM (1).jpeg",
        "images/events/nature_with_friends/WhatsApp Image 2026-03-30 at 11.01.38 PM.jpeg",
      ],
    },
    {
      slug: "open_house_and_job_fair_at_iqra_university_islamabad",
      cover: "images/events/open_house_and_job_fair_at_iqra_university_islamabad/WhatsApp Image 2026-03-30 at 10.22.26 PM (1).jpeg",
      images: [
        "images/events/open_house_and_job_fair_at_iqra_university_islamabad/WhatsApp Image 2026-03-30 at 10.22.26 PM (1).jpeg",
        "images/events/open_house_and_job_fair_at_iqra_university_islamabad/WhatsApp Image 2026-03-30 at 10.22.26 PM (2).jpeg",
        "images/events/open_house_and_job_fair_at_iqra_university_islamabad/WhatsApp Image 2026-03-30 at 10.22.26 PM.jpeg",
        "images/events/open_house_and_job_fair_at_iqra_university_islamabad/WhatsApp Image 2026-03-30 at 10.22.27 PM (1).jpeg",
        "images/events/open_house_and_job_fair_at_iqra_university_islamabad/WhatsApp Image 2026-03-30 at 10.22.27 PM.jpeg",
      ],
    },
    {
      slug: "paklaunch",
      cover: "images/events/paklaunch/WhatsApp Image 2026-03-30 at 10.45.42 PM.jpeg",
      images: [
        "images/events/paklaunch/WhatsApp Image 2026-03-30 at 10.45.42 PM.jpeg",
        "images/events/paklaunch/WhatsApp Image 2026-03-30 at 10.45.43 PM.jpeg",
      ],
    },
    {
      slug: "startup.exe_by_design_peeps",
      cover: "images/events/startup.exe_by_design_peeps/WhatsApp Image 2026-03-30 at 10.42.52 PM.jpeg",
      images: [
        "images/events/startup.exe_by_design_peeps/WhatsApp Image 2026-03-30 at 10.42.52 PM.jpeg",
        "images/events/startup.exe_by_design_peeps/WhatsApp Image 2026-03-30 at 10.42.54 PM (1).jpeg",
        "images/events/startup.exe_by_design_peeps/WhatsApp Image 2026-03-30 at 10.42.54 PM (2).jpeg",
        "images/events/startup.exe_by_design_peeps/WhatsApp Image 2026-03-30 at 10.42.54 PM.jpeg",
        "images/events/startup.exe_by_design_peeps/WhatsApp Image 2026-03-30 at 10.42.56 PM (1).jpeg",
        "images/events/startup.exe_by_design_peeps/WhatsApp Image 2026-03-30 at 10.42.56 PM (2).jpeg",
        "images/events/startup.exe_by_design_peeps/WhatsApp Image 2026-03-30 at 10.42.56 PM (3).jpeg",
        "images/events/startup.exe_by_design_peeps/WhatsApp Image 2026-03-30 at 10.42.56 PM (4).jpeg",
        "images/events/startup.exe_by_design_peeps/WhatsApp Image 2026-03-30 at 10.42.56 PM (5).jpeg",
        "images/events/startup.exe_by_design_peeps/WhatsApp Image 2026-03-30 at 10.42.56 PM.jpeg",
      ],
    },
    {
      slug: "synergy_x_metaverse_deviser",
      cover: "images/events/synergy_x_metaverse_deviser/WhatsApp Image 2026-03-30 at 10.46.29 PM.jpeg",
      images: [
        "images/events/synergy_x_metaverse_deviser/WhatsApp Image 2026-03-30 at 10.46.29 PM.jpeg",
        "images/events/synergy_x_metaverse_deviser/WhatsApp Image 2026-03-30 at 10.46.30 PM.jpeg",
        "images/events/synergy_x_metaverse_deviser/WhatsApp Image 2026-03-30 at 10.46.33 PM.jpeg",
      ],
    },
    {
      slug: "team_medieval_empires",
      cover: "images/events/team_medieval_empires/WhatsApp Image 2026-03-30 at 10.53.37 PM (1).jpeg",
      images: [
        "images/events/team_medieval_empires/WhatsApp Image 2026-03-30 at 10.53.37 PM (1).jpeg",
        "images/events/team_medieval_empires/WhatsApp Image 2026-03-30 at 10.53.37 PM (2).jpeg",
        "images/events/team_medieval_empires/WhatsApp Image 2026-03-30 at 10.53.37 PM (3).jpeg",
        "images/events/team_medieval_empires/WhatsApp Image 2026-03-30 at 10.53.37 PM (4).jpeg",
        "images/events/team_medieval_empires/WhatsApp Image 2026-03-30 at 10.53.37 PM.jpeg",
      ],
    },
    {
      slug: "tik_tok_stem_feed",
      cover: "images/events/tik_tok_stem_feed/WhatsApp Image 2026-03-30 at 10.43.33 PM (1).jpeg",
      images: [
        "images/events/tik_tok_stem_feed/WhatsApp Image 2026-03-30 at 10.43.33 PM (1).jpeg",
        "images/events/tik_tok_stem_feed/WhatsApp Image 2026-03-30 at 10.43.33 PM (2).jpeg",
        "images/events/tik_tok_stem_feed/WhatsApp Image 2026-03-30 at 10.43.33 PM (3).jpeg",
        "images/events/tik_tok_stem_feed/WhatsApp Image 2026-03-30 at 10.43.33 PM (4).jpeg",
        "images/events/tik_tok_stem_feed/WhatsApp Image 2026-03-30 at 10.43.33 PM (5).jpeg",
        "images/events/tik_tok_stem_feed/WhatsApp Image 2026-03-30 at 10.43.33 PM.jpeg",
        "images/events/tik_tok_stem_feed/WhatsApp Image 2026-03-30 at 10.43.34 PM.jpeg",
      ],
    },
  ];

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
