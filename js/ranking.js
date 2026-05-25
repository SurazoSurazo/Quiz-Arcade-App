const rankingList = document.querySelector('[data-ranking-list]');

function escapeHtml(value) {
  const element = document.createElement('span');
  element.textContent = String(value);
  return element.innerHTML;
}

function renderRanking(entries) {
  if (!rankingList || !Array.isArray(entries)) {
    return;
  }

  if (entries.length === 0) {
    rankingList.innerHTML = '<p class="ranking-empty">まだ登録されたスコアはありません。</p>';
    return;
  }

  rankingList.innerHTML = entries.map((entry) => `
    <div class="ranking-row">
      <span class="ranking-row__rank">${escapeHtml(entry.rank)}</span>
      <span class="ranking-row__name">${escapeHtml(entry.name)}</span>
      <strong class="ranking-row__score">${escapeHtml(entry.score)}点</strong>
    </div>
  `).join('');
}

fetch('api/getRanking.php')
  .then((response) => response.json())
  .then((data) => {
    if (data && data.ok) {
      renderRanking(data.ranking);
    }
  })
  .catch(() => {});
