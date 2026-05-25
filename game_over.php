<?php
session_start();
require_once(__DIR__ . '/includes/quiz_functions.php');
require_once(__DIR__ . '/includes/common_functions.php');

$stages = get_quiz_stages();
$game = $_SESSION['quiz_game'] ?? null;

if (!is_array($game) || empty($game['finished']) || !isset($stages[$game['stage']])) {
  header('Location: index.php');
  exit;
}

$stage = $stages[$game['stage']];
$answered = (int) ($game['answered'] ?? 0);
$correct = (int) ($game['correct'] ?? 0);
$miss_count = max(0, $answered - $correct);
$points = (int) ($game['points'] ?? 0);
$prelim_result = $game['prelim_result'] ?? null;
$should_play_game_over_sound = empty($_SESSION['game_over_sound_played']);
$_SESSION['game_over_sound_played'] = true;
?>

<!DOCTYPE html>
<html lang="ja">

<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ゲームオーバー</title>
  <link rel="stylesheet" href="css/reset.css">
  <link rel="stylesheet" href="css/common.css">
  <link rel="stylesheet" href="css/game_over.css">
</head>

<body>
  <header class="header">
    <div class="header__inner">
      <a class="header__logo" href="index.php">
        Quiz
      </a>
    </div>
  </header>

  <main class="game-over">
    <section class="game-over__panel" aria-labelledby="game-over-title">
      <p class="game-over__label"><?php echo h($stage['name']); ?> FAILED</p>
      <h1 class="game-over__title" id="game-over-title">GAME OVER</h1>
      <p class="game-over__text">
        <?php echo h($stage['name']); ?>で3問以上失敗したため、ゲーム終了です。
      </p>

      <div class="game-over__score" aria-label="結果">
        <div class="game-over__score-item">
          <span>解答数</span>
          <strong><?php echo h($answered); ?> / <?php echo h($stage['total']); ?></strong>
        </div>
        <div class="game-over__score-item">
          <span>正解</span>
          <strong><?php echo h($correct); ?>問</strong>
        </div>
        <div class="game-over__score-item game-over__score-item--danger">
          <span>失敗</span>
          <strong><?php echo h($miss_count); ?>問</strong>
        </div>
        <div class="game-over__score-item">
          <span>得点</span>
          <strong><?php echo h($points); ?>点</strong>
        </div>
      </div>

      <?php if ($prelim_result && $game['stage'] === 'final') : ?>
        <p class="game-over__subtext">
          予選結果: <?php echo h($prelim_result['correct'] ?? 0); ?> / <?php echo h($prelim_result['total'] ?? 5); ?>問正解
        </p>
      <?php endif; ?>

      <div class="game-over__actions">
        <a class="game-over__button game-over__button--primary" href="game.php?start=1">もう一度挑戦</a>
        <a class="game-over__button" href="index.php">ホームに戻る</a>
      </div>
    </section>
  </main>
  <?php if ($should_play_game_over_sound) : ?>
    <script>
      function playGameOverSound() {
        const AudioContext = window.AudioContext || window.webkitAudioContext;
        if (!AudioContext) {
          return;
        }

        const audioContext = new AudioContext();
        const now = audioContext.currentTime;
        const notes = [
          { frequency: 392.0, start: 0, duration: 0.18, type: 'square', volume: 0.1 },
          { frequency: 293.66, start: 0.16, duration: 0.2, type: 'square', volume: 0.1 },
          { frequency: 220.0, start: 0.34, duration: 0.34, type: 'triangle', volume: 0.11 },
          { frequency: 146.83, start: 0.62, duration: 0.42, type: 'sine', volume: 0.08 }
        ];

        notes.forEach((note) => {
          const oscillator = audioContext.createOscillator();
          const gain = audioContext.createGain();

          oscillator.type = note.type;
          oscillator.frequency.setValueAtTime(note.frequency, now + note.start);
          gain.gain.setValueAtTime(0.0001, now + note.start);
          gain.gain.exponentialRampToValueAtTime(note.volume, now + note.start + 0.02);
          gain.gain.exponentialRampToValueAtTime(0.0001, now + note.start + note.duration);

          oscillator.connect(gain);
          gain.connect(audioContext.destination);
          oscillator.start(now + note.start);
          oscillator.stop(now + note.start + note.duration + 0.02);
        });
      }

      playGameOverSound();
    </script>
  <?php endif; ?>
</body>

</html>
