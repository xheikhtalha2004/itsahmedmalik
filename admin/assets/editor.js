(() => {
  'use strict';

  document.addEventListener('trix-before-initialize', () => {
    if (!window.Trix?.config?.blockAttributes) return;
    const blocks = window.Trix.config.blockAttributes;
    blocks.default.tagName = 'p';
    blocks.heading2 = { tagName: 'h2', terminal: true, breakOnReturn: true, group: false };
    blocks.heading3 = { tagName: 'h3', terminal: true, breakOnReturn: true, group: false };
  });

  document.addEventListener('trix-initialize', (event) => {
    const toolbar = event.target.toolbarElement;
    const blockTools = toolbar?.querySelector('[data-trix-button-group="block-tools"]');
    const oldHeading = blockTools?.querySelector('[data-trix-attribute="heading1"]');
    if (blockTools && oldHeading) {
      [['heading2', 'H2'], ['heading3', 'H3']].forEach(([attribute, label]) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'trix-button admin-heading-button';
        button.dataset.trixAttribute = attribute;
        button.title = `Heading ${label.slice(1)}`;
        button.tabIndex = -1;
        button.textContent = label;
        blockTools.insertBefore(button, oldHeading);
      });
      oldHeading.remove();
    }
    toolbar?.querySelector('[data-trix-attribute="strike"]')?.remove();
    toolbar?.querySelector('[data-trix-attribute="code"]')?.remove();
    toolbar?.querySelector('[data-trix-button-group="file-tools"]')?.remove();
  });

  document.querySelectorAll('[data-disable-on-submit]').forEach((form) => {
    form.addEventListener('submit', (event) => {
      if (event.submitter?.name === 'action') {
        const action = form.querySelector('input[type="hidden"][name="action"]');
        if (action) action.value = event.submitter.value;
      }
      form.querySelectorAll('button[type="submit"]').forEach((button) => {
        button.disabled = true;
      });
    });
  });

  document.querySelectorAll('[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (event) => {
      if (!window.confirm(form.dataset.confirm)) event.preventDefault();
    });
  });

  document.querySelectorAll('[data-confirm-button]').forEach((button) => {
    button.addEventListener('click', (event) => {
      if (!window.confirm(button.dataset.confirmButton)) event.preventDefault();
    });
  });

  document.addEventListener('trix-file-accept', (event) => event.preventDefault());

  const picker = document.querySelector('[data-event-files]');
  const metadata = document.querySelector('[data-event-metadata]');
  if (picker && metadata) {
    picker.addEventListener('change', () => {
      metadata.replaceChildren();
      [...picker.files].slice(0, 12).forEach((file, index) => {
        const row = document.createElement('div');
        row.className = 'file-metadata';
        const title = document.createElement('strong');
        title.textContent = file.name;
        const alt = document.createElement('input');
        alt.name = 'image_alt[]';
        alt.required = true;
        alt.maxLength = 200;
        alt.placeholder = `Alt text for image ${index + 1}`;
        alt.setAttribute('aria-label', alt.placeholder);
        const caption = document.createElement('input');
        caption.name = 'image_caption[]';
        caption.maxLength = 500;
        caption.placeholder = 'Optional caption';
        caption.setAttribute('aria-label', `Caption for image ${index + 1}`);
        row.append(title, alt, caption);
        metadata.append(row);
      });
    });
  }
})();
