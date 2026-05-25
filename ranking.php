<?php
require_once(__DIR__ . '/includes/ranking_functions.php');

$ranking_entries = add_ranking_display_ranks(get_top_ranking_entries(10));
?>

<!DOCTYPE html>
<html lang="ja">

<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>得点ランキング</title>
  <link rel="stylesheet" href="css/reset.css">
  <link rel="stylesheet" href="css/common.css">
  <link rel="stylesheet" href="css/ranking.css">
</head>

<body>
  <header class="header">
    <div class="header__inner">
      <a class="header__logo" href="index.php">
        Quiz
      </a>
    </div>
  </header>

  <main class="ranking-page">
    <section class="ranking-board" aria-labelledby="ranking-title">
      <p class="ranking-board__label">ARCADE SCORE</p>
      <h1 class="ranking-board__title" id="ranking-title">得点ランキング</h1>
      <p class="ranking-board__text">本選クリア後に登録されたスコア一覧です。同得点の場合は同じ順位で表示されます。</p>

      <div class="ranking-table" data-ranking-list>
        <?php if (!empty($ranking_entries)) : ?>
          <?php foreach ($ranking_entries as $entry) : ?>
            <div class="ranking-row">
              <span class="ranking-row__rank"><?php echo htmlspecialchars($entry['rank'], ENT_QUOTES); ?></span>
              <span class="ranking-row__name"><?php echo htmlspecialchars($entry['name'], ENT_QUOTES); ?></span>
              <strong class="ranking-row__score"><?php echo htmlspecialchars($entry['score'], ENT_QUOTES); ?>点</strong>
            </div>
          <?php endforeach; ?>
        <?php else : ?>
          <p class="ranking-empty">まだ登録されたスコアはありません。</p>
        <?php endif; ?>
      </div>

      <div class="ranking-actions">
        <a class="ranking-action ranking-action--primary" href="game.php?start=1">挑戦する</a>
        <a class="ranking-action" href="index.php">ホームに戻る</a>
      </div>
    </section>
  </main>

  <script src="js/ranking.js"></script>
</body>

</html>
