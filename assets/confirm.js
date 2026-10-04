/* Kasutus: if (await kkConfirm({title:'Kustuta hinnang?', text:'Seda ei saa tagasi võtta.', ok:'Kustuta', danger:true})) {...}
   Või HTML-is: <button data-confirm="Kustuta hinnang?" data-danger>Kustuta</button>
   Vana `onclick="return confirm('...')"` ja `onsubmit="return confirm('...')"` asendatakse automaatselt. */
window.kkConfirm = ({ title = 'Oled kindel?', text = '', ok = 'Jah', cancel = 'Tühista', danger = false } = {}) => new Promise(res => {
  const d = document.createElement('dialog'); d.className = 'kkc' + (danger ? ' danger' : '');
  const h = document.createElement('h3'), p = document.createElement('p'), row = document.createElement('div'), no = document.createElement('button'), yes = document.createElement('button');
  h.textContent = title; p.textContent = text; p.hidden = !text; row.className = 'row';
  no.textContent = cancel; yes.textContent = ok; yes.className = 'ok'; no.type = yes.type = 'button';
  row.append(no, yes); d.append(h, p, row); document.body.append(d);
  const done = v => { d.close(); d.remove(); res(v); };
  yes.onclick = () => done(true); no.onclick = () => done(false); d.oncancel = e => { e.preventDefault(); done(false); };
  d.showModal(); (danger ? no : yes).focus();
});
(() => {
  const msg = el => el.dataset.confirm;
  document.querySelectorAll('[onclick*="confirm("],[onsubmit*="confirm("]').forEach(el => {
    for (const a of ['onclick', 'onsubmit']) {
      const m = (el.getAttribute(a) || '').match(/^\s*return\s+confirm\((['"])(.*?)\1\)\s*;?\s*$/);
      if (m) { el.dataset.confirm = m[2]; el.removeAttribute(a); if (/kustuta|eemalda|delete|remove/i.test(m[2])) el.dataset.danger = ''; }
    }
  });
  const ask = el => kkConfirm({ title: msg(el), text: el.dataset.text || '', ok: el.dataset.ok || ('danger' in el.dataset ? 'Kustuta' : 'Jah'), danger: 'danger' in el.dataset });
  document.addEventListener('click', async e => {
    const el = e.target.closest('[data-confirm]:not(form)'); if (!el || el.dataset.passed) return;
    e.preventDefault(); e.stopImmediatePropagation();
    if (await ask(el)) { el.dataset.passed = '1'; el.click(); delete el.dataset.passed; }
  }, true);
  document.addEventListener('submit', async e => {
    const f = e.target; if (!f.dataset.confirm || f.dataset.passed) return;
    e.preventDefault(); if (await ask(f)) { f.dataset.passed = '1'; f.requestSubmit(); }
  }, true);
})();
