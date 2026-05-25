<?php
session_start();
require_once(__DIR__ . '/data/question_loader.php');

$question_count = count($status_codes);
$mistake_count = count($_SESSION['mistakes'] ?? []);
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
  <link rel="stylesheet" href="css/index.css">
</head>

<body>
  <header class="header">
    <div class="header__inner">
      <a class="header__logo" href="index.php">
        Quiz
      </a>
    </div>
  </header>

  <main class="home">
    <div class="home__lights" aria-hidden="true">
      <span></span>
      <span></span>
      <span></span>
      <span></span>
      <span></span>
    </div>
    <section class="home__hero" aria-labelledby="home-title">
      <div class="home__body">
        <p class="home__label">ARCADE QUIZ</p>
        <h1 class="home__title" id="home-title">QUIZ QUEST</h1>
        <p class="home__text">
          予選を突破し、本選でゲームクリアを狙え。知識と反射神経でスコアを積み上げるクイズバトル。
        </p>

        <div class="home__actions" aria-label="メニュー">
          <a href="game.php?start=1" class="button button--primary">PRESS START</a>
          <a href="pages/review/mistakes.php" class="button button--secondary">復習モード</a>
          <a href="ranking.php" class="button button--ranking">ランキング</a>
        </div>

        <div class="home__rules" aria-label="突破条件">
          <div class="home__rule">
            <span class="home__rule-stage">予選</span>
            <span class="home__rule-score">5問中3問</span>
          </div>
          <div class="home__rule">
            <span class="home__rule-stage">本選</span>
            <span class="home__rule-score">10問中8問</span>
          </div>
        </div>
      </div>

      <div class="home__arcade" aria-hidden="true">
        <div class="home__marquee">READY?</div>
        <div class="home__cabinet">
          <div class="home__screen">
            <div class="home__screen-grid"></div>
            <div class="home__screen-title">QUIZ</div>
            <div class="home__screen-subtitle">BATTLE</div>
            <div class="home__screen-meter">
              <span></span>
              <span></span>
              <span></span>
              <span></span>
            </div>
          </div>
          <div class="home__controls">
            <span class="home__stick"></span>
            <span class="home__button home__button--red"></span>
            <span class="home__button home__button--yellow"></span>
            <span class="home__button home__button--blue"></span>
          </div>
        </div>
      </div>

      <div class="home__panel" aria-label="学習状況">
        <div class="home__status">
          <span class="home__status-label">QUESTIONS</span>
          <span class="home__status-value"><?php echo htmlspecialchars($question_count, ENT_QUOTES); ?></span>
        </div>
        <div class="home__status">
          <span class="home__status-label">RETRY</span>
          <span class="home__status-value"><?php echo htmlspecialchars($mistake_count, ENT_QUOTES); ?></span>
        </div>
      </div>
    </section>
  </main>
</body>

</html>
