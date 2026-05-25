<?php
session_start();
require_once(__DIR__ . '/../../data/question_loader.php');
require_once(__DIR__ . '/../../includes/common_functions.php');

$review_history = $_SESSION['review_history'] ?? [];
$selected_history = null;
$selected_log_token = isset($_GET['log']) ? $_GET['log'] : null;

if ($selected_log_token && isset($_SESSION['review_log_links'][$selected_log_token])) {
  $selected_code = $_SESSION['review_log_links'][$selected_log_token];
  $selected_history = isset($review_history[$selected_code]) ? $review_history[$selected_code] : null;

  if ($selected_history && empty($selected_history['description'])) {
    $question = find_question_by_code($status_codes, $selected_code);
    $selected_history['description'] = $question['description'] ?? '';
  }
}

if (!$selected_history) {
  header('Location: history.php');
  exit;
}

$review_token = bin2hex(random_bytes(16));
$_SESSION['review_links'][$review_token] = $selected_code;
$reviewed_at_list = isset($selected_history['reviewed_at_list']) && is_array($selected_history['reviewed_at_list'])
  ? $selected_history['reviewed_at_list']
  : [];
?>

<!DOCTYPE html>
<html lang="ja">

<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>復習日時</title>
  <link rel="stylesheet" href="../../css/reset.css">
  <link rel="stylesheet" href="../../css/common.css">
  <link rel="stylesheet" href="../../css/review_main.css">
</head>

<body>
  <header class="header">
    <div class="header__inner">
      <a class="header__logo" href="../../index.php">
        Quiz
      </a>
    </div>
  </header>

  <main>
    <div class="review__content">
      <h2 class="review-title">復習日時</h2>
      <p class="history-question">
        <?php echo h($selected_history['description']); ?>
      </p>
      <p class="history-count">
        復習回数: <?php echo h(count($reviewed_at_list)); ?>回
      </p>

      <div class="history-date-list">
        <?php foreach (array_reverse($reviewed_at_list) as $reviewed_at) : ?>
          <div class="history-date">
            <span class="history-date__label">復習日時</span>
            <span class="history-date__text"><?php echo h($reviewed_at); ?></span>
          </div>
        <?php endforeach; ?>
      </div>

      <a class="review-link" href="../../review.php?review=<?php echo h($review_token); ?>">この問題を復習する</a>
      <a class="review-link review-link--secondary" href="history.php">復習履歴に戻る</a>
    </div>
  </main>
</body>

</html>
