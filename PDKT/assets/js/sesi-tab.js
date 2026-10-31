(function () {
  'use strict';
  const base = new URL(document.currentScript.src).pathname.replace(/\/assets\/js\/sesi-tab\.js$/, '');
  const key = 'pdkt-tab:' + base;
  const random = () => Array.from(crypto.getRandomValues(new Uint8Array(32)), n => n.toString(16).padStart(2, '0')).join('');
  let state = window.__PDKT_TAB__;
  function fail(message) {
    document.body.removeAttribute('aria-busy');
    let box = document.getElementById('status-sesi');
    if (!box) { box = document.createElement('p'); box.id = 'status-sesi'; box.setAttribute('role', 'alert'); document.body.prepend(box); }
    box.textContent = message;
  }
  if (!state) {
    try {
      state = { token: sessionStorage.getItem(key) || random(), owner: random(), ready: false, expires: 0, csrf: '', busy: false };
      if (!/^[a-f0-9]{64}$/.test(state.token)) state.token = random();
      sessionStorage.setItem(key, state.token);
      window.__PDKT_TAB__ = state;
    } catch (_) {
      fail('Sila benarkan storan sesi pelayar untuk log masuk pada tab ini.');
      return;
    }
    // Tab pendua boleh mewarisi sessionStorage. Semak pemilik sebelum menggunakan token.
    if (typeof BroadcastChannel === 'undefined') {
      fail('Pelayar ini tidak menyokong sesi tab. Sila gunakan Chrome, Edge atau Firefox versi terkini.');
      return;
    }
    state.channel = new BroadcastChannel('pdkt-tab-owner:' + base);
    state.channel.onmessage = event => {
      const message = event.data;
      if (!message || message.owner === state.owner || message.token !== state.token) return;
      if (message.type === 'probe' && (state.ready || state.owner < message.owner)) {
        state.channel.postMessage({type: 'occupied', token: state.token, owner: state.owner, target: message.owner});
      }
      if (message.type === 'occupied' && message.target === state.owner && !state.ready) state.occupied = true;
    };
    state.initialise = (async () => {
      do {
        state.occupied = false;
        state.channel.postMessage({type: 'probe', token: state.token, owner: state.owner});
        await new Promise(resolve => setTimeout(resolve, 200));
        if (state.occupied) { state.token = random(); sessionStorage.setItem(key, state.token); }
      } while (state.occupied);
      state.ready = true;
    })();
  }
  const pageKey = value => { const url = new URL(value, location.href); return url.origin + url.pathname + url.search; };
  function scrollSection(hash, smooth = false) {
    if (!hash || hash === '#') { window.scrollTo({top: 0, behavior: 'auto'}); return; }
    let id;
    try { id = decodeURIComponent(hash.slice(1)); } catch (_) { return; }
    const target = document.getElementById(id);
    if (!target) return;
    const nav = document.querySelector('nav');
    const position = nav ? getComputedStyle(nav).position : '';
    const offset = nav && (position === 'sticky' || position === 'fixed') ? nav.getBoundingClientRect().height : 0;
    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    window.scrollTo({top: Math.max(0, window.scrollY + target.getBoundingClientRect().top - offset - 12), behavior: smooth && !reduce ? 'smooth' : 'auto'});
  }
  async function navigate(url, options = {}) {
    if (state.busy) return;
    state.busy = true;
    document.body.setAttribute('aria-busy', 'true');
    try {
      await state.initialise;
      const response = await fetch(url, {
        method: options.method || 'GET', body: options.body,
        headers: {'X-PDKT-Tab': state.token}, credentials: 'same-origin', cache: 'no-store'
      });
      if (new URL(response.url).origin !== location.origin) throw new Error('Unexpected redirect');
      const html = await response.text();
      if (!(response.headers.get('Content-Type') || '').includes('text/html')) throw new Error('Unexpected content');
      state.csrf = response.headers.get('X-PDKT-CSRF') || '';
      state.expires = Number(response.headers.get('X-PDKT-Expires') || 0) * 1000;
      state.lastTouch = Date.now(); state.lastSent = state.lastTouch;
      const requested = new URL(url, location.href);
      const destination = new URL(response.url);
      // Kekalkan bahagian #sasaran apabila fetch tidak mengalih ke halaman lain.
      if (pageKey(requested.href) === pageKey(destination.href)) destination.hash = requested.hash;
      state.renderedUrl = pageKey(destination.href);
      if (options.push !== false && destination.href !== location.href) history.pushState(null, '', destination.href);
      else if (options.push === false && destination.href !== location.href) history.replaceState(null, '', destination.href);
      state.busy = false;
      // Respons telah disahkan di pelayan dengan token tab DAN kuki HttpOnly.
      document.open(); document.write(html); document.close();
    } catch (_) {
      state.busy = false;
      fail('Halaman tidak dapat dimuatkan. Semak sambungan dan muat semula untuk mencuba lagi.');
    }
  }
  // document.open() menggantikan pendengar lama. Ikat semula selepas setiap navigasi.
  if (state.controller) state.controller.abort();
  state.controller = new AbortController();
  const binding = {signal: state.controller.signal};
  document.addEventListener('click', event => {
    const link = event.target.closest('a[href]');
    if (!link || event.defaultPrevented || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || link.hasAttribute('download') || (link.target && link.target !== '_self')) return;
    const url = new URL(link.href, location.href);
    if (url.origin !== location.origin || !url.pathname.startsWith(base + '/') || /\.(?:zip|jpg|png|pdf|css|js)$/i.test(url.pathname)) return;
    if (pageKey(url.href) === pageKey(location.href) && link.getAttribute('href').includes('#')) {
      event.preventDefault();
      if (url.href !== location.href) history.pushState(null, '', url.href);
      scrollSection(url.hash, true);
      return;
    }
    event.preventDefault(); navigate(url.href);
  }, binding);
  document.addEventListener('submit', event => {
    const form = event.target;
    if (event.defaultPrevented || (form.target && form.target !== '_self')) return;
    const url = new URL(form.action || location.href, location.href);
    if (url.origin !== location.origin || !url.pathname.startsWith(base + '/')) return;
    event.preventDefault();
    const data = new FormData(form);
    if (event.submitter && event.submitter.name) data.append(event.submitter.name, event.submitter.value);
    if (form.method.toLowerCase() === 'get') {
      url.search = new URLSearchParams(data).toString(); navigate(url.href);
    } else { navigate(url.href, {method: 'POST', body: data}); }
  }, binding);
  window.addEventListener('popstate', () => {
    if (pageKey(location.href) === state.renderedUrl) scrollSection(location.hash);
    else navigate(location.href, {push: false});
  }, binding);
  window.addEventListener('hashchange', () => scrollSection(location.hash), binding);
  for (const event of ['pointerdown', 'keydown', 'input', 'scroll']) {
    document.addEventListener(event, () => { state.lastTouch = Date.now(); }, {...binding, passive: true});
  }
  if (state.timer) clearInterval(state.timer);
  state.timer = setInterval(async () => {
    if (!state.expires || state.busy || state.polling) return;
    if (Date.now() >= state.expires) { navigate(base + '/login.php'); return; }
    state.polling = true;
    try {
      const active = !document.hidden && state.lastTouch > state.lastSent;
      const touched = state.lastTouch;
      const response = await fetch(base + '/session.php', {
        method: active ? 'POST' : 'GET',
        headers: {'X-PDKT-Tab': state.token}, credentials: 'same-origin', cache: 'no-store',
        body: active ? new URLSearchParams({csrf: state.csrf}) : undefined
      });
      const result = await response.json();
      if (result.aktif) {
        state.expires = Number(result.tamat) * 1000;
        if (active) state.lastSent = touched;
      } else if (response.status === 401) navigate(base + '/login.php');
    } catch (_) { /* Tamat tempoh pelayan tetap dikuatkuasakan pada permintaan seterusnya. */ }
    finally { state.polling = false; }
  }, 15000);
  window.addEventListener('pageshow', event => {
    if (event.persisted) navigate(location.href, {push: false});
  }, binding);
  if (document.getElementById('status-sesi')) navigate(location.href, {push: false});
  else if (location.hash) requestAnimationFrame(() => scrollSection(location.hash));
})();
