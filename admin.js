const STORAGE_KEY = "koolikriitik_reviews_v3";
const THEME_KEY = "koolikriitik_theme";

const kTotal = document.getElementById("kTotal");
const kAvg = document.getElementById("kAvg");
const kAgain = document.getElementById("kAgain");
const kSchools = document.getElementById("kSchools");
const table = document.getElementById("adminTable");
const exportBtn = document.getElementById("exportBtn");
const clearBtn = document.getElementById("clearBtn");
const themeToggle = document.getElementById("themeToggle");

let reviews = loadReviews();

initTheme();
render();

themeToggle.addEventListener("click", () => {
  const isDark = document.documentElement.classList.toggle("dark");
  localStorage.setItem(THEME_KEY, isDark ? "dark" : "light");
  themeToggle.textContent = isDark ? "☀️" : "🌙";
});

exportBtn.addEventListener("click", () => {
  const blob = new Blob([JSON.stringify(reviews, null, 2)], { type: "application/json" });
  const url = URL.createObjectURL(blob);
  const a = document.createElement("a");
  a.href = url;
  a.download = "koolikriitik-reviews.json";
  a.click();
  URL.revokeObjectURL(url);
});

clearBtn.addEventListener("click", () => {
  const ok = confirm("Kas oled kindel, et soovid kõik andmed kustutada?");
  if (!ok) return;
  localStorage.removeItem(STORAGE_KEY);
  reviews = [];
  render();
});

function loadReviews() {
  const raw = localStorage.getItem(STORAGE_KEY);
  return raw ? JSON.parse(raw) : [];
}

function initTheme() {
  const stored = localStorage.getItem(THEME_KEY);
  const darkPreferred = window.matchMedia("(prefers-color-scheme: dark)").matches;
  const useDark = stored ? stored === "dark" : darkPreferred;
  document.documentElement.classList.toggle("dark", useDark);
  themeToggle.textContent = useDark ? "☀️" : "🌙";
}

function render() {
  const total = reviews.length;
  const avg = total ? (reviews.reduce((a, b) => a + b.rating, 0) / total) : 0;
  const againPct = total ? Math.round((reviews.filter((r) => r.wouldEatAgain).length / total) * 100) : 0;
  const schools = new Set(reviews.map((r) => r.school)).size;

  kTotal.textContent = String(total);
  kAvg.textContent = avg.toFixed(1);
  kAgain.textContent = `${againPct}%`;
  kSchools.textContent = String(schools);

  table.innerHTML = reviews.map((r) => `
    <tr>
      <td>${new Date(r.createdAt).toLocaleString("et-EE")}</td>
      <td>${esc(r.school)}</td>
      <td>${esc(r.mealName)}</td>
      <td>${r.rating}/5</td>
      <td>${r.wouldEatAgain ? "Jah" : "Ei"}</td>
      <td>${esc(r.comment)}</td>
    </tr>
  `).join("");
}

function esc(s) {
  return String(s)
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#039;");
}