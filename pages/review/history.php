<?php
session_start();
require_once(__DIR__ . '/../../data/question_loader.php');
require_once(__DIR__ . '/../../includes/common_functions.php');
require_once(__DIR__ . '/../../includes/quiz_functions.php');

$review_history = $_SESSION['review_history'] ?? [];
$review_link_tokens = [];
$log_link_tokens = [];

if (!empty($review_history)) {
  $review_history = normalize_review_history($review_history);

  foreach ($review_history as $code => $history) {
    if (empty($history['description'])) {
      $question = find_question_by_code($status_codes, $code);
      $review_history[$code]['description'] = $question['description'] ?? '';
    }
  }

  $_SESSION['review_history'] = $review_history;
}

foreach ($review_history as $code => $history) {
  $review_token = bin2hex(random_bytes(16));
  $_SESSION['review_links'][$review_token] = $code;
  $review_link_tokens[$code] = $review_token;

  $log_token = bin2hex(random_bytes(16));
  $_SESSION['review_log_links'][$log_token] = $code;
  $log_link_tokens[$code] = $log_token;
}
?>

<!DOCTYPE html>
<html lang="ja">

<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>復習履歴</title>
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
      <h2 class="review-title">復習履歴</h2>

      <?php if (empty($review_history)) : ?>
        <p class="review-empty">まだ復習履歴はありません。</p>
      <?php else : ?>
        <?php foreach ($review_history as $code => $history) : ?>
          <div class="history-item">
            <a class="history-question history-question--link" href="../../review.php?review=<?php echo h($review_link_tokens[$code]); ?>">
              <?php echo h($history['description']); ?>
            </a>
            <p class="history-count">
              復習回数: <?php echo h($history['count']); ?>回
            </p>
            <div class="history-actions">
              <a href="../../review.php?review=<?php echo h($review_link_tokens[$code]); ?>">この問題を復習する</a>
              <a href="log.php?log=<?php echo h($log_link_tokens[$code]); ?>">日時を見る</a>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>

      <a class="review-link" href="mistakes.php">間違えた問題リスト</a>
      <a class="review-link review-link--secondary" href="../../index.php">ホームに戻る</a>
    </div>
  </main>
</body>

</html>
