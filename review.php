<?php
session_start();
require_once(__DIR__ . '/data/question_loader.php');
require_once(__DIR__ . '/includes/common_functions.php');

$selected_question = null;
$selected_review_token = isset($_GET['review']) ? $_GET['review'] : null;

if ($selected_review_token && isset($_SESSION['review_links'][$selected_review_token])) {
  $selected_code = $_SESSION['review_links'][$selected_review_token];
  $selected_question = find_question_by_code($status_codes, $selected_code);
}

if (!$selected_question) {
  header('Location: pages/review/mistakes.php');
  exit;
}

$options = build_quiz_options($status_codes, $selected_question, 5);
$question_type = $selected_question['question_type'] ?? (question_has_choices($selected_question) ? 'choice' : 'input');
$answer_placeholder = question_has_choices($selected_question) ? '例: A または 選択肢の内容' : '答えを入力';
$review_token = bin2hex(random_bytes(16));
$_SESSION['review_questions'][$review_token] = $selected_question['code'];
?>

<!DOCTYPE html>
<html lang="ja">

<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Quiz</title>
  <link rel="stylesheet" href="css/reset.css">
  <link rel="stylesheet" href="css/common.css">
  <link rel="stylesheet" href="css/game.css">
  <link rel="stylesheet" href="css/review_main.css">
  <link rel="stylesheet" href="css/multiple_selection.css">
</head>

<body>
  <header class="header">
    <div class="header__inner">
      <a class="header__logo" href="index.php">
        Quiz
      </a>
    </div>
  </header>

  <main>
    <div class="quiz__content">
      <div class="question">
        <?php if ($question_type === 'input') : ?>
          <p class="question__text">Q. 復習問題です。以下の問題の答えを入力してください</p>
        <?php elseif ($question_type === 'sort') : ?>
          <p class="question__text">Q. 復習問題です。正しい順番に並べ替えてください</p>
        <?php elseif ($question_type === 'multiple_selection') : ?>
          <p class="question__text">Q. 復習問題です。正解だと思うものを<?php echo h($selected_question['select_count'] ?? count($selected_question['answer'] ?? [])); ?>つ選んでください</p>
        <?php else : ?>
          <p class="question__text">Q. 復習問題です。以下の問題の答えを選んでください</p>
        <?php endif; ?>
        <p class="question__text">
          <?php echo h($selected_question['description']); ?>
        </p>
      </div>
      <form class="quiz-form" action="result.php" method="post">
        <input type="hidden" name="review_token" value="<?php echo h($review_token); ?>">
        <input type="hidden" name="is_review" value="1">
        <input type="hidden" name="question_type" value="<?php echo h($question_type); ?>">
        <?php if ($question_type === 'input') : ?>
          <?php require(__DIR__ . '/pages/quiz/input.php'); ?>
        <?php elseif ($question_type === 'sort') : ?>
          <?php require(__DIR__ . '/pages/quiz/sort.php'); ?>
        <?php elseif ($question_type === 'multiple_selection') : ?>
          <?php $question = $selected_question; ?>
          <?php require(__DIR__ . '/pages/quiz/multiple_selection.php'); ?>
        <?php else : ?>
          <?php require(__DIR__ . '/pages/quiz/selection.php'); ?>
        <?php endif; ?>
      </form>
      <?php if ($question_type === 'sort') : ?>
        <script src="js/gameTimer.js"></script>
        <script>
          setupSortQuiz(document.querySelector('.quiz-form'));
        </script>
      <?php elseif ($question_type === 'multiple_selection') : ?>
        <script src="js/multipleSelection.js"></script>
        <script>
          setupMultipleSelectionQuiz(document.querySelector('.quiz-form'));
        </script>
      <?php else : ?>
        <script>
          document.querySelectorAll('.quiz-form__radio').forEach((radio) => {
            radio.addEventListener('change', () => {
              radio.form.submit();
            });
          });
        </script>
      <?php endif; ?>
      <div class="review-back">
        <a href="pages/review/mistakes.php">間違えた問題リストに戻る</a>
      </div>
    </div>
  </main>
</body>

</html>
