const form = document.getElementById("reviewForm");
const feed = document.getElementById("feed");
const leaderboardEl = document.getElementById("leaderboard");
const ratingInput = document.getElementById("rating");
const ratingValue = document.getElementById("ratingValue");

const STORAGE_KEY = "koolikriitik_reviews";

const initialData = [
  {
    id: crypto.randomUUID(),
    school: "Tallinna 21. Kool",
    mealName: "Kanapasta",
    rating: 4,
    wouldEatAgain: true,
    comment: "Üllatavalt norm.",
    createdAt: new Date().toISOString(),
  },
  {
    id: crypto.randomUUID(),
    school: "Tartu Raatuse Kool",
    mealName: "Kalapulk + kartulipuder",
    rating: 2,
    wouldEatAgain: false,
    comment: "Puder oli kuiv 😬",
    createdAt: new Date().toISOString(),
  },
];

let reviews = loadReviews();

function loadReviews() {
  const raw = localStorage.getItem(STORAGE_KEY);
  if (raw) return JSON.parse(raw);
  localStorage.setItem(STORAGE_KEY, JSON.stringify(initialData));
  return initialData;
}

function saveReviews() {
  localStorage.setItem(STORAGE_KEY, JSON.stringify(reviews));
}

function stars(n) {
  return "★".repeat(n) + "☆".repeat(5 - n);
}

function renderFeed() {
  feed.innerHTML = "";
  reviews.forEach((r) => {
    const div = document.createElement("div");
    div.className = "review";
    div.innerHTML = `
      <strong>${escapeHtml(r.mealName)}</strong>
      <div class="meta">${escapeHtml(r.school)} • ${new Date(r.createdAt).toLocaleString("et-EE")}</div>
      <div class="stars">${stars(r.rating)}</div>
      <div>${r.wouldEatAgain ? "✅ Sööks uuesti" : "❌ Ei sööks uuesti"}</div>
      <p>${escapeHtml(r.comment)}</p>
    `;
    feed.appendChild(div);
  });
}

function renderLeaderboard() {
  const map = new Map();

  for (const r of reviews) {
    const prev = map.get(r.school) || { total: 0, count: 0 };
    map.set(r.school, { total: prev.total + r.rating, count: prev.count + 1 });
  }

  const sorted = [...map.entries()]
    .map(([school, v]) => ({ school, avg: v.total / v.count, count: v.count }))
    .sort((a, b) => b.avg - a.avg);

  leaderboardEl.innerHTML = "";
  sorted.forEach((s, i) => {
    const li = document.createElement("li");
    li.textContent = `#${i + 1} ${s.school} — ${s.avg.toFixed(1)}/5 (${s.count})`;
    leaderboardEl.appendChild(li);
  });
}

function escapeHtml(str) {
  return str
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#039;");
}

ratingInput.addEventListener("input", () => {
  ratingValue.textContent = ratingInput.value;
});

form.addEventListener("submit", (e) => {
  e.preventDefault();

  const school = document.getElementById("school").value.trim();
  const mealName = document.getElementById("mealName").value.trim();
  const rating = Number(document.getElementById("rating").value);
  const wouldEatAgain = document.getElementById("wouldEatAgain").checked;
  const comment = document.getElementById("comment").value.trim();

  reviews.unshift({
    id: crypto.randomUUID(),
    school,
    mealName,
    rating,
    wouldEatAgain,
    comment,
    createdAt: new Date().toISOString(),
  });

  saveReviews();
  renderFeed();
  renderLeaderboard();
  form.reset();
  ratingInput.value = "3";
  ratingValue.textContent = "3";
});

renderFeed();
renderLeaderboard();