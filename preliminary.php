<?php
session_start();

require_once(__DIR__ . '/includes/common_functions.php');
require_once(__DIR__ . '/includes/quiz_functions.php');

if (empty($_SESSION['quiz_game']) || !is_waiting_for_final()) {
  header('Location: game.php');
  exit;
}

$game = $_SESSION['quiz_game'];
$prelim_result = $game['prelim_result'] ?? [
  'answered' => 5,
  'correct' => 0,
  'total' => 5,
  'pass_score' => 3,
];
$feedback_sound = $_SESSION['last_feedback_sound'] ?? null;
unset($_SESSION['last_feedback_sound']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  start_final_stage();
  header('Location: game.php');
  exit;
}
?>

<!DOCTYPE html>
<html lang="ja">

<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>本選進出</title>
  <link rel="stylesheet" href="css/reset.css">
  <link rel="stylesheet" href="css/common.css">
  <link rel="stylesheet" href="css/preliminary_clear.css">
</head>

<body>
  <header class="header">
    <div class="header__inner">
      <a class="header__logo" href="index.php">
        Quiz
      </a>
    </div>
  </header>

  <main class="clear">
    <section class="clear-panel" aria-labelledby="clear-title">
      <p class="clear-panel__label">予選クリア</p>
      <h1 class="clear-panel__title" id="clear-title">本選進出</h1>
      <p class="clear-panel__score">
        <?php echo h($prelim_result['total']); ?>問中<?php echo h($prelim_result['correct']); ?>問正解
      </p>
      <p class="clear-panel__text">
        本選は10問中8問正解でゲームクリアです。
      </p>

      <form action="preliminary.php" method="post">
        <button class="clear-panel__button" type="submit">
          本選へ進む
        </button>
      </form>
      <a class="clear-panel__link" href="index.php">ホームに戻る</a>
    </section>
  </main>
  <?php if ($feedback_sound) : ?>
    <script>
      const feedbackResult = <?php echo json_encode($feedback_sound); ?>;

      function playFeedbackSound(result) {
        const AudioContext = window.AudioContext || window.webkitAudioContext;
        if (!AudioContext || !result) {
          return;
        }

        const audioContext = new AudioContext();
        const now = audioContext.currentTime;
        const notes = result === 'correct'
          ? [{ frequency: 659.25, start: 0 }, { frequency: 880, start: 0.11 }]
          : [{ frequency: 220, start: 0 }, { frequency: 164.81, start: 0.13 }];

        notes.forEach((note) => {
          const oscillator = audioContext.createOscillator();
          const gain = audioContext.createGain();

          oscillator.type = result === 'correct' ? 'sine' : 'square';
          oscillator.frequency.setValueAtTime(note.frequency, now + note.start);
          gain.gain.setValueAtTime(0.0001, now + note.start);
          gain.gain.exponentialRampToValueAtTime(0.12, now + note.start + 0.02);
          gain.gain.exponentialRampToValueAtTime(0.0001, now + note.start + 0.18);

          oscillator.connect(gain);
          gain.connect(audioContext.destination);
          oscillator.start(now + note.start);
          oscillator.stop(now + note.start + 0.2);
        });
      }

      playFeedbackSound(feedbackResult);
    </script>
  <?php endif; ?>
</body>

</html>
