const $ = (sel, root = document) => root.querySelector(sel);
const $$ = (sel, root = document) => [...root.querySelectorAll(sel)];

function toast(message, type = 'success') {
  $$('.toast').forEach((t) => t.remove());
  const el = document.createElement('div');
  el.className = `toast toast-${type}`;
  el.setAttribute('role', 'status');
  el.textContent = message;
  document.body.appendChild(el);
  setTimeout(() => el.remove(), 4200);
}

// ---------- Shared: confirmations and local times ----------
$$('form[data-confirm]').forEach((form) => {
  form.addEventListener('submit', (e) => {
    if (!confirm(form.dataset.confirm)) e.preventDefault();
  });
});

$$('time[data-local-time]').forEach((el) => {
  const d = new Date(el.getAttribute('datetime'));
  if (!Number.isNaN(d.getTime())) {
    el.textContent = d.toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' });
  }
});

// ---------- Editor ----------
const editor = $('#editor');
if (editor) initEditor();

function initEditor() {
  const form = $('#editor-form');
  const status = $('#save-status');
  const saveBtn = $('#save-btn');
  const sectionInput = $('#current-section');
  const preview = $('#preview');
  const frame = $('#preview-frame');
  const links = $$('.section-link');
  const panels = $$('.panel');
  const narrow = window.matchMedia('(max-width: 1180px)');
  let dirty = false;
  let current = 'settings';

  // Where each editor section lives on the public page.
  const previewTarget = {
    settings: null, hero: null, logos: '.logos', newsletter: '.site-footer',
  };

  function scrollPreview(section) {
    try {
      const doc = frame.contentDocument;
      if (!doc) return;
      const sel = section in previewTarget ? previewTarget[section] : `#${section}`;
      const target = sel && doc.querySelector(sel);
      if (target) {
        target.scrollIntoView({ block: 'start' });
        doc.querySelectorAll('.reveal').forEach((el) => el.classList.add('visible'));
      } else {
        frame.contentWindow.scrollTo(0, 0);
      }
    } catch { /* preview not ready */ }
  }

  // ---- Section switching ----
  function showSection(key, { updateHash = true } = {}) {
    if (!panels.some((p) => p.dataset.panel === key)) key = 'settings';
    current = key;
    sectionInput.value = key;
    links.forEach((l) => l.classList.toggle('active', l.dataset.section === key));
    // On phones the section list scrolls sideways; keep the active one visible.
    const active = links.find((l) => l.dataset.section === key);
    const nav = active.parentElement;
    if (nav.scrollWidth > nav.clientWidth) nav.scrollLeft = active.offsetLeft - nav.offsetLeft - 8;
    panels.forEach((p) => p.classList.toggle('active', p.dataset.panel === key));
    if (updateHash) history.replaceState(null, '', `#${key}`);
    scrollPreview(key);
  }

  links.forEach((link) => link.addEventListener('click', () => {
    showSection(link.dataset.section);
    $(`[data-panel="${link.dataset.section}"]`).scrollTop = 0;
  }));
  showSection(decodeURIComponent(location.hash.slice(1)) || 'settings', { updateHash: false });
  frame.addEventListener('load', () => {
    preview.classList.remove('loading');
    scrollPreview(current);
  });

  // ---- Dirty tracking ----
  function setDirty(value) {
    dirty = value;
    status.classList.toggle('dirty', value);
    status.classList.remove('error');
    if (value) status.textContent = 'Unsaved changes';
  }
  form.addEventListener('input', () => setDirty(true));
  form.addEventListener('change', () => setDirty(true));
  window.addEventListener('beforeunload', (e) => {
    if (dirty) { e.preventDefault(); e.returnValue = ''; }
  });

  // Sidebar "Hidden" tags follow the visibility checkboxes.
  function refreshHiddenTags() {
    links.forEach((link) => {
      const box = $(`input[type="checkbox"][name="content[${link.dataset.section}][visible]"]`);
      if (!box) return;
      link.classList.toggle('is-hidden', !box.checked);
      let tag = $('.tag', link);
      if (!box.checked && !tag) {
        tag = document.createElement('span');
        tag.className = 'tag';
        tag.textContent = 'Hidden';
        link.appendChild(tag);
      } else if (box.checked && tag) {
        tag.remove();
      }
    });
  }
  form.addEventListener('change', (e) => {
    if (e.target.name?.endsWith('[visible]')) refreshHiddenTags();
  });

  // ---- Color inputs show their hex value ----
  form.addEventListener('input', (e) => {
    if (e.target.type === 'color') e.target.nextElementSibling.textContent = e.target.value;
  });

  // ---- Repeatable lists ----
  let uid = 0;

  function refreshList(list) {
    const items = $$(':scope > [data-items] > [data-item]', list);
    const max = parseInt(list.dataset.max, 10);
    $('.list-count', list).textContent = `${items.length} / ${max}`;
    $(':scope > [data-add]', list).disabled = items.length >= max;
    items.forEach((item, i) => {
      $('[data-move="-1"]', item).disabled = i === 0;
      $('[data-move="1"]', item).disabled = i === items.length - 1;
    });
  }
  $$('[data-list]').forEach(refreshList);

  form.addEventListener('click', (e) => {
    const btn = e.target.closest('button');
    if (!btn) return;
    const list = btn.closest('[data-list]');

    if (btn.matches('[data-add]')) {
      const html = $(':scope > template', list).innerHTML.replaceAll('__i__', `n${Date.now()}${uid++}`);
      const itemsWrap = $(':scope > [data-items]', list);
      itemsWrap.insertAdjacentHTML('beforeend', html);
      const item = itemsWrap.lastElementChild;
      item.classList.add('flash');
      $('input, textarea', item)?.focus();
      refreshList(list);
      setDirty(true);
      return;
    }

    const item = btn.closest('[data-item]');
    if (!item) return;

    if (btn.matches('[data-remove]')) {
      e.preventDefault();
      const hasContent = $$('input:not([type=hidden]), textarea', item).some((f) => f.type !== 'checkbox' && f.value.trim());
      if (hasContent && !confirm('Remove this item?')) return;
      item.remove();
      refreshList(list);
      setDirty(true);
    } else if (btn.matches('[data-move]')) {
      e.preventDefault();
      const sibling = btn.dataset.move === '-1' ? item.previousElementSibling : item.nextElementSibling;
      if (!sibling) return;
      if (btn.dataset.move === '-1') sibling.before(item); else sibling.after(item);
      item.classList.remove('flash');
      void item.offsetWidth; // restart animation
      item.classList.add('flash');
      refreshList(list);
      setDirty(true);
    }
  });

  // Keep collapsed item titles in sync with their first field.
  form.addEventListener('input', (e) => {
    const title = e.target.id && $(`[data-title-from="${CSS.escape(e.target.id)}"]`, form);
    if (title) title.textContent = e.target.value.trim() || 'Untitled';
  });

  // ---- Saving ----
  async function save() {
    saveBtn.disabled = true;
    saveBtn.textContent = 'Publishing…';
    try {
      const res = await fetch(form.action, {
        method: 'POST',
        body: new FormData(form),
        headers: { Accept: 'application/json' },
      });
      let data = {};
      try { data = await res.json(); } catch { /* not JSON */ }
      if (res.redirected && res.url.includes('login.php')) throw new Error('Your session expired. Open a new tab, log in, then publish again.');
      if (!res.ok || !data.ok) throw new Error(data.error || `Save failed (HTTP ${res.status}).`);

      setDirty(false);
      status.textContent = `Published at ${new Date().toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' })}`;
      toast('Changes published');
      preview.classList.add('loading');
      frame.contentWindow.location.reload();
    } catch (err) {
      status.textContent = err.message;
      status.classList.add('error');
      toast(err.message, 'error');
    } finally {
      saveBtn.disabled = false;
      saveBtn.textContent = 'Publish changes';
    }
  }

  form.addEventListener('submit', (e) => {
    e.preventDefault();
    save();
  });
  document.addEventListener('keydown', (e) => {
    if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 's') {
      e.preventDefault();
      save();
    }
  });

  // ---- Preview panel ----
  $('#toggle-preview').addEventListener('click', () => {
    if (narrow.matches) {
      editor.classList.add('show-preview');
      scrollPreview(current);
    } else {
      editor.classList.toggle('no-preview');
    }
  });
  $('#close-preview').addEventListener('click', () => editor.classList.remove('show-preview'));

  $$('[data-device]').forEach((btn) => btn.addEventListener('click', () => {
    $$('[data-device]').forEach((b) => b.classList.toggle('active', b === btn));
    preview.classList.toggle('mobile', btn.dataset.device === 'mobile');
  }));
}
