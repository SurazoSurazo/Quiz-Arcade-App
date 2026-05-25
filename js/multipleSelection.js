function setupMultipleSelectionQuiz(form) {
  const quiz = document.querySelector('[data-multiple-selection]');
  const answerInput = document.getElementById('multiple-selection-answer');
  const countElement = document.querySelector('[data-multiple-selection-count]');
  const selectedValues = [];

  if (!form || !quiz || !answerInput) {
    return;
  }

  const selectCount = Number(quiz.dataset.selectCount || 0);

  function updateSelection() {
    answerInput.value = selectedValues.join(',');
    if (countElement) {
      countElement.textContent = selectedValues.length;
    }

    document.querySelectorAll('[data-multiple-selection-value]').forEach((button) => {
      const isSelected = selectedValues.includes(button.dataset.multipleSelectionValue);
      button.classList.toggle('multiple-selection__option--selected', isSelected);
      button.disabled = !isSelected && selectedValues.length >= selectCount;
    });
  }

  document.querySelectorAll('[data-multiple-selection-value]').forEach((button) => {
    button.addEventListener('click', () => {
      const value = button.dataset.multipleSelectionValue;
      const selectedIndex = selectedValues.indexOf(value);

      if (selectedIndex !== -1) {
        selectedValues.splice(selectedIndex, 1);
        updateSelection();
        return;
      }

      if (selectedValues.length >= selectCount) {
        return;
      }

      selectedValues.push(value);
      updateSelection();
    });
  });

  const resetButton = document.querySelector('[data-multiple-selection-reset]');
  if (resetButton) {
    resetButton.addEventListener('click', () => {
      selectedValues.splice(0, selectedValues.length);
      updateSelection();
    });
  }

  document.addEventListener('keydown', (event) => {
    if (event.key !== 'Enter') {
      return;
    }

    if (selectCount > 0 && selectedValues.length === selectCount) {
      event.preventDefault();
      form.requestSubmit();
    }
  });

  updateSelection();
}
