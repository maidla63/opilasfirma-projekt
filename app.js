const STORAGE_KEY = "koolikriitik_reviews_v4";
const THEME_KEY = "koolikriitik_theme_v4";

const form = document.getElementById("reviewForm");
const feedEl = document.getElementById("feed");
const leaderboardEl = document.getElementById("leaderboard");
const ratingEl = document.getElementById("rating");
const ratingValueEl = document.getElementById("ratingValue");
const schoolFilterEl = document.getElementById("schoolFilter");
const photoEl = document.getElementById("photo");
const previewEl = document.getElementById("preview");
const themeToggle = document.getElementById("themeToggle");

let reviews = loadReviews();
initTheme();
renderAll();

ratingEl.addEventListener("input", () => ratingValueEl.textContent = ratingEl.value);

photoEl.addEventListener("change", (e) => {
  const file = e.target.files?.[0];
  if (!file) {
    previewEl.classList.add("hidden");
    previewEl.src = "";
    return;
  }
  const reader = new FileReader();
  reader.onload = () => {
    previewEl.src = reader.result;
    previewEl.classList.remove("hidden");
  };
  reader.readAsDataURL(file);
});

schoolFilterEl.addEventListener("change", renderFeed);

form.addEventListener("submit", (e) => {
  e.preventDefault();
  const school = document.getElementById("school").value.trim();
  const mealName = document.getElementById("mealName").value.trim();
  const rating = Number(ratingEl.value);
  const wouldEatAgain = document.getElementById("wouldEatAgain").checked;
  const comment = document.getElementById("comment").value.trim();
  const image = previewEl.src && !previewEl.classList.contains("hidden") ? previewEl.src : "";

  reviews.unshift({
    id: crypto.randomUUID(),
    school,
    mealName,
    rating,
    wouldEatAgain,
    comment,
    image,
    likes: 0,
    dislikes: 0,
    createdAt: new Date().toISOString(),
  });

  saveReviews();
  form.reset();
  ratingEl.value = "3";
  ratingValueEl.textContent = "3";
  previewEl.src = "";
  previewEl.classList.add("hidden");
  renderAll();
});

themeToggle.addEventListener("click", () => {
  const isDark = document.documentElement.classList.toggle("dark");
  localStorage.setItem(THEME_KEY, isDark ? "dark" : "light");
  themeToggle.textContent = isDark ? "☀️" : "🌙";
});

function initTheme() {
  const stored = localStorage.getItem(THEME_KEY);
  const darkPreferred = window.matchMedia("(prefers-color-scheme: dark)").matches;
  const useDark = stored ? stored === "dark" : darkPreferred;
  document.documentElement.classList.toggle("dark", useDark);
  themeToggle.textContent = useDark ? "☀️" : "🌙";
}

function loadReviews() {
  const raw = localStorage.getItem(STORAGE_KEY);
  if (raw) return JSON.parse(raw);

  const seed = [
    {
      id: crypto.randomUUID(),
      school: "Tallinna 21. Kool",
      mealName: "Kanapasta",
      rating: 4,
      wouldEatAgain: true,
      comment: "Täitsa hea, söödav ja soe.",
      image: "",
      likes: 2,
      dislikes: 0,
      createdAt: new Date().toISOString(),
    },
    {
      id: crypto.randomUUID(),
      school: "Tartu Raatuse Kool",
      mealName: "Kalapulk + kartulipuder",
      rating: 2,
      wouldEatAgain: false,
      comment: "Täna ei läinud peale.",
      image: "",
      likes: 0,
      dislikes: 3,
      createdAt: new Date().toISOString(),
    },
  ];
  localStorage.setItem(STORAGE_KEY, JSON.stringify(seed));
  return seed;
}

function saveReviews() {
  localStorage.setItem(STORAGE_KEY, JSON.stringify(reviews));
}

function renderAll() {
  renderFilterOptions();
  renderFeed();
  renderLeaderboard();
}

function renderFilterOptions() {
  const schools = [...new Set(reviews.map((r) => r.school))].sort();
  const current = schoolFilterEl.value;
  schoolFilterEl.innerHTML = `<option value="">Kõik koolid</option>`;
  for (const s of schools) {
    const opt = document.createElement("option");
    opt.value = s;
    opt.textContent = s;
    if (s === current) opt.selected = true;
    schoolFilterEl.appendChild(opt);
  }
}

function renderFeed() {
  const selectedSchool = schoolFilterEl.value;
  const list = reviews.filter((r) => !selectedSchool || r.school === selectedSchool);

  if (!list.length) {
    feedEl.innerHTML = `<p class="meta">Selle filtriga hinnanguid ei leitud.</p>`;
    return;
  }

  feedEl.innerHTML = list.map((r) => `
    <article class="review">
      <div class="review-top">
        <h3>${esc(r.mealName)}</h3>
        <span class="meta">${new Date(r.createdAt).toLocaleString("et-EE")}</span>
      </div>
      <div class="meta">${esc(r.school)}</div>
      <div class="stars">${stars(r.rating)} (${r.rating}/5)</div>
      <div>${r.wouldEatAgain ? "✅ Sööks uuesti" : "❌ Ei sööks uuesti"}</div>
      <p>${esc(r.comment)}</p>
      ${r.image ? `<img src="${r.image}" alt="Toidupilt" />` : ""}
      <div class="review-actions">
        <button class="icon-btn" onclick="vote('${r.id}','like')">👍 ${r.likes}</button>
        <button class="icon-btn" onclick="vote('${r.id}','dislike')">👎 ${r.dislikes}</button>
      </div>
    </article>
  `).join("");
}

window.vote = function vote(id, type) {
  const row = reviews.find((r) => r.id === id);
  if (!row) return;
  if (type === "like") row.likes++;
  if (type === "dislike") row.dislikes++;
  saveReviews();
  renderFeed();
};

function renderLeaderboard() {
  const map = new Map();
  for (const r of reviews) {
    const prev = map.get(r.school) ?? { sum: 0, count: 0 };
    map.set(r.school, { sum: prev.sum + r.rating, count: prev.count + 1 });
  }

  const rows = [...map.entries()]
    .map(([school, v]) => ({ school, avg: v.sum / v.count, count: v.count }))
    .sort((a, b) => b.avg - a.avg);

  leaderboardEl.innerHTML = rows.map((x, i) => `
    <li>
      <strong>#${i + 1} ${esc(x.school)}</strong><br />
      <span class="meta">${x.avg.toFixed(1)}/5 • ${x.count} hinnangut</span>
    </li>
  `).join("");
}

function stars(n) {
  return "★".repeat(n) + "☆".repeat(5 - n);
}

function esc(s) {
  return String(s)
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#039;");
}