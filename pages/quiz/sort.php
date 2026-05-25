<div class="sort-quiz" data-sort-quiz data-sort-total="<?php echo h(count($options)); ?>">
  <input type="hidden" name="typed_answer" id="sort-answer" value="">

  <div class="sort-quiz__pool" aria-label="並べ替える項目">
    <?php foreach ($options as $option_code => $option_text) : ?>
      <?php if (is_array($option_text)) : ?>
        <?php $option_value = $option_text['code']; ?>
        <?php $option_label = $option_text['description'] ?? $option_text['code']; ?>
      <?php else : ?>
        <?php $option_value = $option_code; ?>
        <?php $option_label = $option_text; ?>
      <?php endif; ?>
      <button class="sort-quiz__item" type="button" data-sort-value="<?php echo h($option_value); ?>">
        <?php echo h($option_label); ?>
      </button>
    <?php endforeach; ?>
  </div>

  <div class="sort-quiz__answer" aria-label="選択した順番">
    <p class="sort-quiz__answer-label">選択した順番</p>
    <ol class="sort-quiz__answer-list" data-sort-answer-list></ol>
  </div>

  <div class="quiz-form__button sort-quiz__actions">
    <button class="quiz-form__button-submit" type="submit">
      回答
    </button>
    <button class="sort-quiz__reset" type="button" data-sort-reset>
      リセット
    </button>
  </div>
</div>
