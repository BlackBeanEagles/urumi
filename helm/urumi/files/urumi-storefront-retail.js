document.querySelectorAll('[data-urumi-lookbook]').forEach((lookbook) => {
  const tabs = Array.from(lookbook.querySelectorAll('[role="tab"]'));
  const panels = Array.from(lookbook.querySelectorAll('[role="tabpanel"]'));
  const activate = (index, focus = false) => {
    tabs.forEach((tab, position) => {
      const selected = position === index;
      tab.setAttribute('aria-selected', selected ? 'true' : 'false');
      tab.tabIndex = selected ? 0 : -1;
      tab.classList.toggle('is-active', selected);
      panels[position].hidden = !selected;
    });
    if (focus) tabs[index].focus();
  };
  tabs.forEach((tab, index) => {
    tab.addEventListener('click', () => activate(index));
    tab.addEventListener('keydown', (event) => {
      let next;
      if (event.key === 'ArrowRight' || event.key === 'ArrowDown') next = (index + 1) % tabs.length;
      else if (event.key === 'ArrowLeft' || event.key === 'ArrowUp') next = (index - 1 + tabs.length) % tabs.length;
      else if (event.key === 'Home') next = 0;
      else if (event.key === 'End') next = tabs.length - 1;
      else return;
      event.preventDefault();
      activate(next, true);
    });
  });
});

document.querySelectorAll('[data-urumi-filters]').forEach((filters) => {
  const buttons = Array.from(filters.querySelectorAll('button[data-category]'));
  const cards = Array.from(document.querySelectorAll('.urumi-product-card[data-urumi-category]'));
  const status = document.getElementById('urumi-filter-status');
  buttons.forEach((button) => {
    button.addEventListener('click', () => {
      const selected = button.dataset.category;
      let visible = 0;
      buttons.forEach((item) => item.setAttribute('aria-pressed', item === button ? 'true' : 'false'));
      cards.forEach((card) => {
        card.hidden = selected !== 'all' && card.dataset.urumiCategory !== selected;
        if (!card.hidden) visible += 1;
      });
      if (status) status.textContent = `Showing ${visible} ${selected === 'all' ? 'featured' : button.textContent.trim()} products.`;
    });
  });
});

document.querySelectorAll('[data-urumi-gallery]').forEach((gallery) => {
  const main = gallery.querySelector('[data-urumi-main]');
  const frame = gallery.querySelector('[data-urumi-enlarge]');
  const dialog = gallery.querySelector('dialog');
  const dialogImage = dialog?.querySelector('img');
  const views = Array.from(gallery.querySelectorAll('[data-urumi-view]'));
  views.forEach((view) => view.addEventListener('click', () => {
    views.forEach((item) => item.setAttribute('aria-pressed', item === view ? 'true' : 'false'));
    main.src = view.dataset.src;
    main.alt = view.dataset.alt;
    frame.classList.toggle('is-cropped', view.dataset.crop === 'true');
    if (dialogImage) {
      dialogImage.src = view.dataset.src;
      dialogImage.alt = view.dataset.alt;
    }
  }));
  frame?.addEventListener('click', () => dialog?.showModal());
  dialog?.querySelector('[data-urumi-close]')?.addEventListener('click', () => dialog.close());
  dialog?.addEventListener('click', (event) => {
    if (event.target === dialog) dialog.close();
  });
});

document.querySelector('[data-urumi-buy]')?.addEventListener('click', () => {
  const form = document.querySelector('.wc-block-add-to-cart-form form.cart');
  if (!form) return;
  form.scrollIntoView({ behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'instant' : 'smooth', block: 'center' });
  window.setTimeout(() => form.querySelector('input.qty, select, .single_add_to_cart_button')?.focus({ preventScroll: true }), 350);
});

document.querySelectorAll('.urumi-catalog-controls').forEach((form) => {
  form.addEventListener('submit', () => {
    const button = form.querySelector('button[type="submit"]');
    if (!button) return;
    button.setAttribute('aria-busy', 'true');
    button.textContent = 'Finding products…';
  });
});

document.querySelectorAll('[data-urumi-add-to-bag]').forEach((link) => {
  link.addEventListener('click', (event) => {
    if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
    link.setAttribute('aria-busy', 'true');
    link.textContent = 'Adding to bag…';
  });
});

document.querySelectorAll('.wc-block-add-to-cart-form form.cart').forEach((form) => {
  form.addEventListener('submit', () => {
    const button = form.querySelector('.single_add_to_cart_button');
    if (!button || !form.checkValidity()) return;
    button.setAttribute('aria-busy', 'true');
    button.textContent = 'Adding to bag…';
  });
});

window.addEventListener('pageshow', () => {
  document.querySelectorAll('[data-urumi-add-to-bag][aria-busy]').forEach((link) => {
    link.removeAttribute('aria-busy');
    link.textContent = 'Add to bag +';
  });
  document.querySelectorAll('.urumi-catalog-controls button[aria-busy]').forEach((button) => {
    button.removeAttribute('aria-busy');
    button.textContent = 'Show products ↗';
  });
  document.querySelectorAll('.wc-block-add-to-cart-form .single_add_to_cart_button[aria-busy]').forEach((button) => {
    button.removeAttribute('aria-busy');
    button.textContent = 'Add to bag';
  });
});
