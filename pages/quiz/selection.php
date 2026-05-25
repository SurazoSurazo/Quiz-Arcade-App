<div class="quiz-form__item">
  <?php foreach ($options as $option_code => $option_text) : ?>
    <div class="quiz-form__group">
      <?php if (is_array($option_text)) : ?>
        <?php $option_value = $option_text['code']; ?>
        <?php $option_label = $option_text['description'] ?? $option_text['code']; ?>
      <?php else : ?>
        <?php $option_value = $option_code; ?>
        <?php $option_label = $option_text; ?>
      <?php endif; ?>
      <input class="quiz-form__radio" id="option-<?php echo h($option_value); ?>" type="radio" name="option" value="<?php echo h($option_value); ?>">
      <label class="quiz-form__label" for="option-<?php echo h($option_value); ?>">
        <?php echo h($option_label); ?>
      </label>
    </div>
  <?php endforeach; ?>
</div>
