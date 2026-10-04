const D = window.DASH, $ = s => document.querySelector(s);
const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const DAYS = ['Esmaspäev','Teisipäev','Kolmapäev','Neljapäev','Reede','Laupäev','Pühapäev'];
const NEG = ['külm','maitsetu','väike portsjon','liiga soolane','kuiv'];
const fmt = v => v === null || v === undefined ? '—' : String(v).replace('.', ',');

async function load() {
  const sid = $('#dSchool').value, days = $('#dDays').value;
  $('#csv').href = `api/export.php?school_id=${sid}&days=${days}`;
  $('#title').textContent = $('#dSchool').selectedOptions[0].text;
  $('#sub').textContent = `Sööklaraport · viimased ${days} päeva · koostatud ${new Date().toLocaleDateString('et-EE')}`;
  const r = await fetch(`api/dashboard.php?school_id=${sid}&days=${days}`, { credentials: 'same-origin' });
  const j = await r.json();
  $('#out').innerHTML = r.ok ? render(j) : `<p class="err">${esc(j.error)}</p>`;
}

function delta(a, b, lowerBetter, unit = '') {
  if (a === null || b === null || b === undefined) return '<span class="d">eelmise perioodiga võrdlus puudub</span>';
  const x = Math.round((a - b) * 10) / 10; if (!x) return '<span class="d">sama mis eelmisel perioodil</span>';
  const good = lowerBetter ? x < 0 : x > 0;
  return `<span class="d ${good ? 'good' : 'bad'}">${x > 0 ? '▲' : '▼'} ${fmt(Math.abs(x))}${unit} eelmise perioodiga võrreldes</span>`;
}
const kpi = (l, v, sub) => `<div class="kpi"><small>${l}</small><b>${v}</b>${sub}</div>`;
const rows = (a, key) => a.length ? `<table class="tbl">${a.map(m => `<tr><td>${esc(m.meal)}</td><td>${m.n}×</td><td>${key === 'waste' ? m.waste + '% jääb' : fmt(m.r) + '/5'}</td></tr>`).join('')}</table>`
  : '<p class="note">Vähemalt 2 hinnangut toidu kohta on vaja, et siin midagi näidata.</p>';

function trendSvg(t) {
  if (t.length < 2) return '<p class="note">Trendi jaoks on vaja hinnanguid vähemalt kahelt päevalt.</p>';
  const x = i => 30 + i * (550 / (t.length - 1)), y = r => 140 - (r - 1) / 4 * 120;
  const pts = t.map((p, i) => `${x(i)},${y(p.r)}`).join(' ');
  return `<svg class="chart" viewBox="0 0 600 170" role="img" aria-label="Keskmine hinne päevade kaupa">
    ${[1,3,5].map(g => `<line x1="30" x2="590" y1="${y(g)}" y2="${y(g)}" stroke="var(--line)"/><text x="4" y="${y(g) + 4}">${g}</text>`).join('')}
    <polyline points="${pts}" fill="none" stroke="var(--red)" stroke-width="3"/>
    ${t.map((p, i) => `<circle cx="${x(i)}" cy="${y(p.r)}" r="4" fill="var(--red)"><title>${p.d}: ${fmt(p.r)}/5 (${p.n} hinnangut)</title></circle>`).join('')}
    <text x="30" y="165">${t[0].d}</text><text x="590" y="165" text-anchor="end">${t[t.length - 1].d}</text></svg>`;
}

function insights(d) {
  const out = [], w = d.week.filter(x => x.waste !== null).sort((a, b) => b.waste - a.waste)[0];
  if (w) out.push(`<b>${DAYS[w.w]}ti</b> läheb kõige rohkem toitu prügikasti (${w.waste}% jääb söömata). Vaata selle päeva menüü üle.`);
  if (d.worst[0] && d.worst[0].r < 3) out.push(`Kõige nõrgemalt hinnati <b>${esc(d.worst[0].meal)}</b> (${fmt(d.worst[0].r)}/5, ${d.worst[0].n} hinnangut).`);
  if (d.best[0] && d.best[0].r >= 4) out.push(`Õpilastele meeldib <b>${esc(d.best[0].meal)}</b> (${fmt(d.best[0].r)}/5). Sobib sagedamini menüüsse.`);
  const neg = Object.entries(d.tags).find(([t]) => NEG.includes(t));
  if (neg) out.push(`Sagedaseim kaebus on „<b>${esc(neg[0])}</b>“ (${neg[1]} korda).`);
  if (d.market.waste !== null && d.kpi.waste !== null) out.push(d.kpi.waste > d.market.waste ? `Teie söömata jäänud toit (${d.kpi.waste}%) on kõrgem kui kõigi koolide keskmine (${d.market.waste}%).` : `Teie söömata jäänud toit (${d.kpi.waste}%) on madalam kui kõigi koolide keskmine (${d.market.waste}%).`);
  return out.length ? out.map(i => `<li>${i}</li>`).join('') : '<li>Soovitusi hakkab tulema, kui hinnanguid koguneb rohkem.</li>';
}

function render(d) {
  if (!d.kpi.n) return '<div class="box"><p>Sellel perioodil pole veel hinnanguid. Julgusta õpilasi hindama, et siia andmed tekiksid.</p></div>';
  const maxW = Math.max(1, ...d.week.map(x => x.waste ?? 0));
  const wk = [0,1,2,3,4].map(i => { const x = d.week.find(v => v.w === i); return `<div><span>${x && x.waste !== null ? x.waste + '%' : ''}</span><i style="height:${x && x.waste !== null ? x.waste / maxW * 110 : 0}px"></i><b>${DAYS[i].slice(0, 3)}</b><small>${x ? fmt(x.r) + '★' : '—'}</small></div>`; }).join('');
  const tagMax = Math.max(1, ...Object.values(d.tags));
  const tags = Object.entries(d.tags).slice(0, 6).map(([t, n]) => `<div class="tagbar"><span style="min-width:120px">${esc(t)}</span><i style="width:${n / tagMax * 100}px"></i>${n}</div>`).join('') || '<p class="note">Tagid puuduvad.</p>';
  return `
  <div class="kpis">
    ${kpi('Hinnanguid', d.kpi.n, delta(d.kpi.n, d.prev.n, false))}
    ${kpi('Keskmine hinne', fmt(d.kpi.rating) + '/5', delta(d.kpi.rating, d.prev.rating, false))}
    ${kpi('Jääb söömata', d.kpi.waste === null ? '—' : d.kpi.waste + '%', delta(d.kpi.waste, d.prev.waste, true, '%'))}
    ${kpi('Sööks uuesti', d.kpi.again === null ? '—' : d.kpi.again + '%', delta(d.kpi.again, d.prev.again, false, '%'))}
    ${kpi('Koht edetabelis', d.rank.pos ? '#' + d.rank.pos : '—', `<span class="d">${d.rank.of} kooli seas</span>`)}
  </div>
  <div class="box insights"><h2>Mida sellest järeldada</h2><ul>${insights(d)}</ul></div>
  <div class="box"><h2>Keskmine hinne päeviti</h2>${trendSvg(d.trend)}</div>
  <div class="two">
    <div class="box"><h2>Söömata jäänud toit nädalapäeviti</h2><div class="wk">${wk}</div></div>
    <div class="box"><h2>Mille üle õpilased kurdavad ja kiidavad</h2>${tags}</div>
  </div>
  <div class="two">
    <div class="box"><h2>Madalaima hindega road</h2>${rows(d.worst)}</div>
    <div class="box"><h2>Kõrgeima hindega road</h2>${rows(d.best)}</div>
  </div>
  <div class="box"><h2>Road, mis jäävad kõige sagedamini söömata</h2>${rows(d.wasted, 'waste')}</div>
  <p class="note">Andmed on õpilaste anonüümsed hinnangud. Toidu kohta kuvatakse tulemus alates 2 hinnangust. Üksikuid kommentaare ega kasutajaid raportis ei näidata.</p>`;
}

if ($('#dSchool')) { $('#dSchool').onchange = $('#dDays').onchange = load; load(); }

/* Menüü import */
async function menuPost(fd) {
  const r = await fetch('api/menu.php', { method: 'POST', body: fd, headers: { 'X-CSRF-Token': D.csrf }, credentials: 'same-origin' });
  const j = await r.json().catch(() => ({ error: 'Serveri viga' })); if (!r.ok) throw new Error(j.error); return j;
}
$('#menuRead')?.addEventListener('click', async () => {
  const f = $('#menuFile').files[0]; if (!f) { $('#menuErr').textContent = 'Vali fail.'; return; }
  const fd = new FormData(); fd.set('mode', 'read'); fd.set('school_id', $('#dSchool').value); fd.set('file', f); $('#menuErr').textContent = 'Loen menüüd…';
  try {
    const j = await menuPost(fd);
    $('#menuPrev').innerHTML = j.items.map(x => `<div class="mrow"><input type="date" value="${esc(x.date)}"><textarea rows="3">${esc((x.meals || []).join('\n'))}</textarea></div>`).join('');
    $('#menuSave').hidden = !j.items.length; $('#menuErr').textContent = j.items.length ? '' : 'Menüüd ei leitud.';
  } catch (e) { $('#menuErr').textContent = e.message; }
});
$('#menuSave')?.addEventListener('click', async () => {
  const items = [...document.querySelectorAll('.mrow')].map(r => ({ date: r.querySelector('input').value, meals: r.querySelector('textarea').value.split('\n').map(s => s.trim()).filter(Boolean) }));
  const fd = new FormData(); fd.set('mode', 'save'); fd.set('school_id', $('#dSchool').value); fd.set('items', JSON.stringify(items));
  try { await menuPost(fd); $('#menuErr').textContent = 'Menüü salvestatud ✅'; $('#menuPrev').innerHTML = ''; $('#menuSave').hidden = true; } catch (e) { $('#menuErr').textContent = e.message; }
});
