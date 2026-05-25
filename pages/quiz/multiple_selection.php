<?php $select_count = (int) ($question['select_count'] ?? count($question['answer'] ?? [])); ?>

<div class="multiple-selection" data-multiple-selection data-select-count="<?php echo h($select_count); ?>">
  <input type="hidden" name="typed_answer" id="multiple-selection-answer" value="">

  <div class="multiple-selection__status" aria-live="polite">
    <span data-multiple-selection-count>0</span> / <?php echo h($select_count); ?> 選択
  </div>

  <div class="multiple-selection__options">
    <?php foreach ($options as $option_code => $option_text) : ?>
      <?php if (is_array($option_text)) : ?>
        <?php $option_value = $option_text['code']; ?>
        <?php $option_label = $option_text['description'] ?? $option_text['code']; ?>
      <?php else : ?>
        <?php $option_value = $option_code; ?>
        <?php $option_label = $option_text; ?>
      <?php endif; ?>
      <button class="multiple-selection__option" type="button" data-multiple-selection-value="<?php echo h($option_value); ?>">
        <?php echo h($option_label); ?>
      </button>
    <?php endforeach; ?>
  </div>

  <div class="quiz-form__button multiple-selection__actions">
    <button class="quiz-form__button-submit" type="submit">
      回答
    </button>
    <button class="multiple-selection__reset" type="button" data-multiple-selection-reset>
      リセット
    </button>
  </div>
</div>
