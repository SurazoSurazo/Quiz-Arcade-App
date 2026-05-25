<?php
session_start();
require_once(__DIR__ . '/../../includes/common_functions.php');
require_once(__DIR__ . '/../../includes/quiz_functions.php');

$mistakes = $_SESSION['mistakes'] ?? [];
$review_link_tokens = [];

if (!empty($mistakes)) {
  $mistakes = normalize_mistakes($mistakes);
  $_SESSION['mistakes'] = $mistakes;
}

foreach ($mistakes as $code => $mistake) {
  $link_token = bin2hex(random_bytes(16));
  $_SESSION['review_links'][$link_token] = $code;
  $review_link_tokens[$code] = $link_token;
}
?>

<!DOCTYPE html>
<html lang="ja">

<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>間違えた問題リスト</title>
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
      <h2 class="review-title">間違えた問題リスト</h2>

      <?php if (empty($mistakes)) : ?>
        <p class="review-empty">復習問題はありません。</p>
      <?php else : ?>
        <p class="review-empty">以下の問題から復習したいものを選んでください。</p>
        <ul class="mistake-list">
          <?php $question_number = 1; ?>
          <?php foreach ($mistakes as $code => $mistake) : ?>
            <li class="mistake-item">
              <a href="../../review.php?review=<?php echo h($review_link_tokens[$code]); ?>">
                <?php echo $question_number; ?>.
                <?php echo h($mistake['description']); ?>
              </a>
            </li>
            <?php $question_number++; ?>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>

      <a class="review-link" href="history.php">復習履歴</a>
      <a class="review-link review-link--secondary" href="../../index.php">ホームに戻る</a>
    </div>
  </main>
</body>

</html>
