const K = window.KK, $ = s => document.querySelector(s), $$ = s => [...document.querySelectorAll(s)];
const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const FACES = ['', '😖', '😕', '😐', '🙂', '😍'];
let offset = 0, schoolId = 0;

async function api(path, body) {
  const r = await fetch(path, body ? { method: 'POST', body, headers: { 'X-CSRF-Token': K.csrf }, credentials: 'same-origin' } : { credentials: 'same-origin' });
  const j = await r.json().catch(() => ({ error: 'Serveri viga' }));
  if (!r.ok) throw new Error(j.error || 'Midagi läks valesti');
  return j;
}
function toast(t) { const el = $('#toast'); el.textContent = t; el.classList.add('show'); setTimeout(() => el.classList.remove('show'), 2600); }
const need = () => { if (!K.me) { $('#authDlg').showModal(); return false; } return true; };

/* Edetabel + päeva hitt/lurr */
async function loadBoard() {
  const d = await api('api/leaderboard.php');
  const fill = (id, x) => { if (x) { $('#' + id + 'Name').textContent = x.meal_name; $('#' + id + 'Sub').textContent = `${x.school} · ${x.a}/5 (${x.c} hinnangut)`; } };
  fill('hit', d.hit); fill('lurr', d.lurr);
  $('#wasteNum').textContent = d.stats.waste === null ? '—' : d.stats.waste + '%';
  $('#totalSub').textContent = d.stats.total ? `${d.stats.total} hinnangut kokku` : 'Ole esimene hindaja';
  const opts = d.schools.map(s => `<option value="${s.id}">${esc(s.name)}</option>`).join('');
  $('#schoolFilter').innerHTML = '<option value="0">Kõik koolid</option>' + opts; $('#schoolFilter').value = schoolId;
  const own = K.me && K.me.role !== 'admin' ? d.schools.filter(s => s.id == K.me.school_id) : d.schools;
  $('#addSchool').innerHTML = own.map(s => `<option value="${s.id}">${esc(s.name)}</option>`).join(''); if (K.me?.school_id) $('#addSchool').value = K.me.school_id;
  $('#board').innerHTML = d.board.length ? d.board.map((s, i) => `<li data-s="${s.id}"><div class="rank">${['🥇','🥈','🥉'][i] || i + 1}</div>
    <div><b>${esc(s.name)}</b><div class="meta">${s.count} hinnangut · keskmine ${s.avg}${s.waste !== null ? ` · ${s.waste}% läheb prügikasti` : ''}</div></div>
    <div class="score">${s.score}</div></li>`).join('') : '<p class="note">Edetabel ilmub kohe, kui esimesed hinnangud tulevad.</p>';
}

/* Voog */
function card(r) {
  return `<article class="rev" data-id="${r.id}">
    <div class="rev-h"><h3>${esc(r.meal)}</h3><span class="face" title="${r.rating}/5">${FACES[r.rating]}</span></div>
    <div class="meta">${esc(r.school)} · ${esc(r.date)} · <a href="profile.php?u=${r.uid}">${esc(r.author)}</a>${r.again ? ' · sööks uuesti' : ''}</div>
    ${r.image ? `<img src="${esc(r.image)}" alt="Foto toidust" loading="lazy">` : ''}
    ${r.tags.map(t => `<span class="tag">${esc(t)}</span>`).join('')}
    ${r.eaten !== null ? `<div class="meter"><i style="width:${r.eaten}%"></i></div><div class="meta">${r.eaten}% söödud</div>` : ''}
    ${r.comment ? `<p>${esc(r.comment)}</p>` : ''}
    <div class="votes"><button data-v="like" class="${r.mine === 'like' ? 'on' : ''}">👍 <span>${r.likes}</span></button>
    <button data-v="dislike" class="${r.mine === 'dislike' ? 'on' : ''}">👎 <span>${r.dislikes}</span></button>${r.own ? '' : '<button data-report class="rep" title="Teata sobimatust sisust">⚑</button>'}</div></article>`;
}
async function loadFeed(reset) {
  if (reset) { offset = 0; $('#feed').innerHTML = ''; }
  const d = await api(`api/feed.php?school_id=${schoolId}&offset=${offset}`);
  $('#feed').insertAdjacentHTML('beforeend', d.reviews.map(card).join(''));
  offset += d.reviews.length; $('#more').hidden = !d.more;
  if (!offset) $('#feed').innerHTML = '<p class="note">Siin on veel tühi. Vajuta „Hinda toitu“ ja ole esimene.</p>';
}

/* Sündmused */
$$('.tabs button').forEach(b => b.onclick = () => { $$('.tabs button').forEach(x => x.classList.toggle('on', x === b)); $$('.pane').forEach(p => p.classList.toggle('on', p.id === 'tab-' + b.dataset.tab)); });
$('#schoolFilter').onchange = e => { schoolId = +e.target.value; loadFeed(true); };
$('#board').onclick = e => { const li = e.target.closest('li'); if (!li) return; schoolId = +li.dataset.s; $('#schoolFilter').value = schoolId; $$('.tabs button')[0].click(); loadFeed(true); };
$('#more').onclick = () => loadFeed(false);
$('#theme').onclick = () => { const d = document.documentElement; d.dataset.theme = d.dataset.theme === 'dark' ? '' : 'dark'; localStorage.kkTheme = d.dataset.theme || 'light'; };

$('#feed').onclick = async e => {
  const b = e.target.closest('[data-v]'); if (!b || !need()) return;
  const art = b.closest('.rev'), fd = new FormData(); fd.set('review_id', art.dataset.id); fd.set('vote_type', b.dataset.v);
  try {
    const j = await api('api/vote.php', fd);
    const [l, d] = art.querySelectorAll('.votes button');
    l.querySelector('span').textContent = j.likes; d.querySelector('span').textContent = j.dislikes;
    l.classList.toggle('on', j.mine === 'like'); d.classList.toggle('on', j.mine === 'dislike');
  } catch (er) { toast(er.message); }
};

/* Teata sobimatust */
$('#feed').addEventListener('click', async e => {
  const b = e.target.closest('[data-report]'); if (!b || !need()) return;
  if (!await kkConfirm({ title: 'Teata sobimatust sisust?', text: 'Admin vaatab hinnangu üle.', ok: 'Teata', danger: true })) return;
  const fd = new FormData(); fd.set('review_id', b.closest('.rev').dataset.id);
  try { await api('api/report.php', fd); toast('Aitäh, teatasime adminile.'); } catch (er) { toast(er.message); }
});

/* Õpilaste edetabel */
async function loadPeople() {
  const m = $('#pM').value, d = await api(`api/people.php?p=${$('#pP').value}&m=${m}`);
  $('#people').innerHTML = d.people.length ? d.people.map((u, i) => `<li><div class="rank">${['🥇','🥈','🥉'][i] || i + 1}</div>
    <div><a href="profile.php?u=${u.id}"><b>${esc(u.name)}</b></a><div class="meta">${esc(u.school || '')}</div></div>
    <div class="score">${m === 'liked' ? u.likes + ' 👍' : u.n + '×'}</div></li>`).join('') : '<p class="note">Siin on veel tühi.</p>';
}
$$('.tabs button')[2].addEventListener('click', loadPeople);
$('#pP').onchange = $('#pM').onchange = loadPeople;

/* Tänane menüü ja toidu soovitused */
async function loadMenu() {
  if (!K.me?.school_id) return;
  const d = await api(`api/menu.php?school_id=${K.me.school_id}`); if (!d.days.length) return;
  const today = new Date().toISOString().slice(0, 10), t = d.days.find(x => x.date === today), n = t || d.days[0];
  $('#menuLine').hidden = false; $('#menuLine').textContent = `Menüü ${t ? 'täna' : n.date}: ` + n.meals.join(' · ');
  if (t) $('#mealList').innerHTML = t.meals.map(m => `<option value="${esc(m)}">`).join('');
}

/* Auth */
$('#openAuth')?.addEventListener('click', () => $('#authDlg').showModal());
$$('.seg button').forEach(b => b.onclick = () => {
  $$('.seg button').forEach(x => x.classList.toggle('on', x === b));
  $('#authForm [name=action]').value = b.dataset.a;
  const reg = b.dataset.a === 'register';
  $$('[data-reg]').forEach(w => { w.hidden = !reg; (w.matches('input,select') ? [w] : [...w.querySelectorAll('input')]).forEach(i => i.required = reg); });
});
$('#authForm').onsubmit = async e => {
  e.preventDefault();
  const btn = e.target.querySelector('.pri'), old = btn.textContent; btn.disabled = true; $('#authErr').textContent = '';
  if (new FormData(e.target).get('action') === 'register') btn.textContent = 'Kontrollin õpilaspiletit…';
  try { await api('api/auth.php', new FormData(e.target)); location.reload(); }
  catch (er) { $('#authErr').textContent = er.message; btn.disabled = false; btn.textContent = old; }
};
$('#logout')?.addEventListener('click', async () => { const f = new FormData(); f.set('action', 'logout'); await api('api/auth.php', f); location.reload(); });

/* Õpilaspileti eelvaade (pilt jääb brauserisse, serverisse läheb alles registreerimisel) */
document.querySelector('input[name=card]')?.addEventListener('change', e => {
  const f = e.target.files[0], lbl = e.target.closest('label'), span = lbl.querySelector('span');
  let img = lbl.querySelector('img');
  if (!f) { span.textContent = '🪪 Lisa foto õpilaspiletist'; if (img) img.hidden = true; return; }
  if (!img) { img = document.createElement('img'); img.alt = 'Õpilaspileti eelvaade'; img.style.cssText = 'display:block;max-width:100%;max-height:170px;margin:8px auto 0;border-radius:10px'; lbl.append(img); }
  img.src = URL.createObjectURL(f); img.hidden = false; span.textContent = '✅ ' + f.name;
});

/* Lisa hinnang */
$('#openAdd').onclick = () => { if (need()) { $('#mealDate').value = $('#mealDate').max = new Date().toISOString().slice(0, 10); $('#addDlg').showModal(); } };
$('#img').onchange = e => { const f = e.target.files[0]; if (!f) return; $('#prev').src = URL.createObjectURL(f); $('#prev').hidden = false; $('#imgLbl').textContent = f.name; };
$('#addForm').onsubmit = async e => {
  e.preventDefault(); $('#addErr').textContent = '';
  try {
    const j = await api('api/reviews_create.php', new FormData(e.target));
    $('#addDlg').close(); e.target.reset(); $('#prev').hidden = true; toast(j.pending ? 'Pilt ootab kontrolli, hinnang ilmub varsti.' : 'Postitatud. Aitäh!');
    loadFeed(true); loadBoard();
  } catch (er) { $('#addErr').textContent = er.message; }
};

loadBoard().then(() => loadFeed(true)).catch(er => toast(er.message));
loadMenu().catch(() => {});
