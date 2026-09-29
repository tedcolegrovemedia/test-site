// Any section can be hidden from the CMS, so every feature checks that its
// elements exist before wiring them up.
const $ = (sel) => document.querySelector(sel);
const $$ = (sel) => [...document.querySelectorAll(sel)];

// ---------- Theme toggle ----------
const root = document.documentElement;

function getStoredTheme() {
  try { return localStorage.getItem('theme'); } catch { return null; }
}

const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
root.dataset.theme = getStoredTheme() || (prefersDark ? 'dark' : 'light');

$('#theme-toggle')?.addEventListener('click', () => {
  root.dataset.theme = root.dataset.theme === 'dark' ? 'light' : 'dark';
  try { localStorage.setItem('theme', root.dataset.theme); } catch { /* storage unavailable */ }
});

// ---------- Mobile menu ----------
const menuToggle = $('#menu-toggle');
const navLinks = $('#nav-links');

function setMenu(open) {
  if (!menuToggle) return;
  navLinks.classList.toggle('open', open);
  menuToggle.setAttribute('aria-expanded', String(open));
  menuToggle.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
}

menuToggle?.addEventListener('click', () => setMenu(!navLinks.classList.contains('open')));
navLinks.addEventListener('click', (e) => { if (e.target.closest('a')) setMenu(false); });
document.addEventListener('keydown', (e) => { if (e.key === 'Escape') setMenu(false); });

// ---------- Header shadow + active nav link ----------
const header = $('.site-header');
window.addEventListener('scroll', () => {
  header.classList.toggle('scrolled', window.scrollY > 8);
}, { passive: true });

const navAnchors = navLinks.querySelectorAll('a[href^="#"]:not(.btn)');
const sectionObserver = new IntersectionObserver((entries) => {
  entries.forEach((entry) => {
    if (!entry.isIntersecting) return;
    navAnchors.forEach((a) => a.classList.toggle('active', a.getAttribute('href') === `#${entry.target.id}`));
  });
}, { rootMargin: '-45% 0px -50% 0px' });
$$('main section[id]').forEach((s) => sectionObserver.observe(s));

// ---------- Scroll reveal ----------
const revealObserver = new IntersectionObserver((entries, obs) => {
  entries.forEach((entry) => {
    if (entry.isIntersecting) {
      entry.target.classList.add('visible');
      obs.unobserve(entry.target);
    }
  });
}, { threshold: 0.12 });
$$('.reveal').forEach((el, i) => {
  el.style.transitionDelay = `${(i % 3) * 80}ms`;
  revealObserver.observe(el);
});

// ---------- Animated counters ----------
function formatCount(value, decimals, suffix) {
  return value.toLocaleString(undefined, {
    minimumFractionDigits: decimals,
    maximumFractionDigits: decimals,
  }) + suffix;
}

function animateCount(el) {
  const target = parseFloat(el.dataset.count);
  const decimals = parseInt(el.dataset.decimals || '0', 10);
  const suffix = el.dataset.suffix || '';
  if (Number.isNaN(target)) return;
  const duration = 1400;
  const start = performance.now();

  function tick(now) {
    const progress = Math.min((now - start) / duration, 1);
    const eased = 1 - Math.pow(1 - progress, 3);
    el.textContent = formatCount(target * eased, decimals, suffix);
    if (progress < 1) requestAnimationFrame(tick);
  }
  requestAnimationFrame(tick);
}

const counterObserver = new IntersectionObserver((entries, obs) => {
  entries.forEach((entry) => {
    if (entry.isIntersecting) {
      animateCount(entry.target);
      obs.unobserve(entry.target);
    }
  });
}, { threshold: 0.5 });
$$('[data-count]').forEach((el) => counterObserver.observe(el));

// ---------- Testimonial slider ----------
const slides = $('#slides');
const dotsWrap = $('#dots');
if (slides && dotsWrap) {
  const slideCount = slides.children.length;
  let current = 0;
  let autoplay;

  const goTo = (index) => {
    current = (index + slideCount) % slideCount;
    slides.style.transform = `translateX(-${current * 100}%)`;
    [...dotsWrap.children].forEach((d, i) => d.setAttribute('aria-selected', String(i === current)));
  };

  const restartAutoplay = () => {
    clearInterval(autoplay);
    autoplay = setInterval(() => goTo(current + 1), 6000);
  };

  for (let i = 0; i < slideCount; i++) {
    const dot = document.createElement('button');
    dot.setAttribute('role', 'tab');
    dot.setAttribute('aria-label', `Show testimonial ${i + 1}`);
    dot.addEventListener('click', () => { goTo(i); restartAutoplay(); });
    dotsWrap.appendChild(dot);
  }

  $('#prev').addEventListener('click', () => { goTo(current - 1); restartAutoplay(); });
  $('#next').addEventListener('click', () => { goTo(current + 1); restartAutoplay(); });
  goTo(0);
  restartAutoplay();
}

// ---------- Pricing toggle ----------
const billing = $('#billing');
billing?.addEventListener('change', () => {
  const period = billing.checked ? 'yearly' : 'monthly';
  $$('.amount').forEach((el) => {
    el.textContent = `${el.dataset.currency}${el.dataset[period]}`;
  });
});

// ---------- Form submission ----------
async function postForm(form) {
  const res = await fetch(form.action, {
    method: 'POST',
    body: new FormData(form),
    headers: { Accept: 'application/json' },
  });
  let data = {};
  try { data = await res.json(); } catch { /* non-JSON error page */ }
  if (!res.ok || !data.ok) {
    throw new Error(data.error || 'Something went wrong. Please try again.');
  }
  return data;
}

// ---------- Contact form ----------
const form = $('#contact-form');
if (form) {
  const status = $('#form-status');

  const validateField = (input) => {
    const field = input.closest('.field');
    const error = field.querySelector('.error');
    let message = '';

    if (input.validity.valueMissing) message = 'This field is required.';
    else if (input.validity.typeMismatch) message = 'Please enter a valid email address.';
    else if (input.validity.tooShort) message = `Please enter at least ${input.minLength} characters.`;

    field.classList.toggle('invalid', Boolean(message));
    error.textContent = message;
    return !message;
  };

  const fields = [...form.querySelectorAll('.field input, .field textarea')];
  fields.forEach((input) => {
    input.addEventListener('blur', () => validateField(input));
    input.addEventListener('input', () => {
      if (input.closest('.field').classList.contains('invalid')) validateField(input);
    });
  });

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    status.textContent = '';
    status.classList.remove('is-error');
    if (!fields.map(validateField).every(Boolean)) return;

    const button = form.querySelector('button[type="submit"]');
    const label = button.textContent;
    button.disabled = true;
    button.textContent = 'Sending…';
    try {
      await postForm(form);
      form.reset();
      status.textContent = form.dataset.success || 'Thanks! Your message was sent.';
    } catch (err) {
      status.textContent = err.message;
      status.classList.add('is-error');
    } finally {
      button.disabled = false;
      button.textContent = label;
    }
  });
}

// ---------- Newsletter ----------
const newsletter = $('#newsletter');
newsletter?.addEventListener('submit', async (e) => {
  e.preventDefault();
  const btn = newsletter.querySelector('button');
  const label = btn.textContent;
  btn.disabled = true;
  try {
    await postForm(newsletter);
    newsletter.reset();
    btn.textContent = 'Subscribed ✓';
  } catch (err) {
    btn.textContent = 'Try again';
    newsletter.querySelector('input[type="email"]').setCustomValidity(err.message);
    newsletter.reportValidity();
    newsletter.querySelector('input[type="email"]').setCustomValidity('');
  } finally {
    btn.disabled = false;
    setTimeout(() => { btn.textContent = label; }, 2500);
  }
});
