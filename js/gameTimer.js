function setupSortQuiz(form) {
  const sortAnswer = document.getElementById('sort-answer');
  const sortAnswerList = document.querySelector('[data-sort-answer-list]');
  const sortQuiz = document.querySelector('[data-sort-quiz]');
  const selectedSortItems = [];

  if (!form || !sortAnswer || !sortAnswerList || !sortQuiz) {
    return;
  }

  function updateSortAnswer() {
    sortAnswer.value = selectedSortItems.map((item) => item.value).join(',');
    sortAnswerList.innerHTML = '';

    selectedSortItems.forEach((item) => {
      const listItem = document.createElement('li');
      listItem.textContent = item.label;
      sortAnswerList.appendChild(listItem);
    });
  }

  form.addEventListener('submit', (event) => {
    const timeoutInput = document.getElementById('timeout');
    const isTimeout = timeoutInput && timeoutInput.value === '1';
    const sortTotal = Number(sortQuiz.dataset.sortTotal || 0);

    if (!isTimeout && selectedSortItems.length < sortTotal) {
      event.preventDefault();
    }
  });

  document.querySelectorAll('[data-sort-value]').forEach((button) => {
    button.addEventListener('click', () => {
      const selectedIndex = selectedSortItems.findIndex((item) => item.value === button.dataset.sortValue);

      if (selectedIndex !== -1) {
        selectedSortItems.splice(selectedIndex, 1);
        button.classList.remove('sort-quiz__item--selected');
        updateSortAnswer();
        return;
      }

      selectedSortItems.push({
        value: button.dataset.sortValue,
        label: button.textContent.trim(),
      });
      button.classList.add('sort-quiz__item--selected');
      updateSortAnswer();
    });
  });

  const sortReset = document.querySelector('[data-sort-reset]');
  if (sortReset) {
    sortReset.addEventListener('click', () => {
      selectedSortItems.splice(0, selectedSortItems.length);
      document.querySelectorAll('[data-sort-value]').forEach((button) => {
        button.classList.remove('sort-quiz__item--selected');
      });
      updateSortAnswer();
    });
  }

  document.addEventListener('keydown', (event) => {
    if (event.key !== 'Enter') {
      return;
    }

    const sortTotal = Number(sortQuiz.dataset.sortTotal || 0);
    if (sortTotal > 0 && selectedSortItems.length === sortTotal) {
      event.preventDefault();
      form.requestSubmit();
    }
  });
}
