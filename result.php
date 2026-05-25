<?php
session_start();
require_once(__DIR__ . '/data/question_loader.php');
require_once(__DIR__ . '/includes/common_functions.php');
require_once(__DIR__ . '/includes/validation.php');
require_once(__DIR__ . '/includes/quiz_functions.php');

$answer_code = isset($_POST['answer_code']) ? h($_POST['answer_code']) : null;
$option = isset($_POST['option']) ? h($_POST['option']) : null;
$typed_answer = isset($_POST['typed_answer']) ? trim($_POST['typed_answer']) : '';
$question_type = isset($_POST['question_type']) ? $_POST['question_type'] : 'choice';
$timeout = isset($_POST['timeout']) ? $_POST['timeout'] : '0';
$is_review = isset($_POST['is_review']) && $_POST['is_review'] === '1';
$review_token = isset($_POST['review_token']) ? $_POST['review_token'] : null;

if ($is_review && isset($_SESSION['review_questions'][$review_token])) {
    $answer_code = $_SESSION['review_questions'][$review_token];
    unset($_SESSION['review_questions'][$review_token]);
}

if (!has_submitted_answer($option, $typed_answer, $timeout, $question_type)) {
    header('Location: index.php');
    exit;
}

$answer_status_code = find_question_by_code($status_codes, $answer_code);

if (!$answer_status_code) {
    header('Location: index.php');
    exit;
}

$code = $answer_status_code['code'];
$description = $answer_status_code['description'];
$accepted_answers = get_accepted_answers($answer_status_code);
$correct_answer = $accepted_answers[0] ?? $code;
$submitted_answer = get_submitted_answer($question_type, $option, $typed_answer);
$result = is_quiz_answer_correct($question_type, $correct_answer, $option, $typed_answer, $timeout, $accepted_answers);

if ($is_review) {
    record_review_history($code, $description);
}

update_mistakes($code, $answer_status_code, $result, $is_review);

$game_result = $is_review ? null : update_quiz_game($result);
$game_stage = $game_result['stage'] ?? null;
$game_message = $game_result['message'] ?? '';
$game_display_answered = $game_result['answered'] ?? 0;
$game_display_correct = $game_result['correct'] ?? 0;
$game_message_class = $game_result['message_class'] ?? '';
$next_quiz_label = '次の問題へ';
$next_quiz_href = 'game.php';
if ($game_result) {
    $next_quiz_label = $game_result['next_label'];
    $next_quiz_href = $game_result['next_href'];
}
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
  <link rel="stylesheet" href="css/result.css">
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
  <div class="result__content">
    <div class="result">
      <?php if ($result): ?>
      <h2 class="result__text--correct">正解</h2>
      <?php else: ?>
      <h2 class="result__text--incorrect">不正解</h2>
      <?php endif; ?>
    </div>
    <?php if (!$is_review && $game_stage) : ?>
      <div class="game-result">
        <p class="game-result__stage">
          <?php echo h($game_stage['name']); ?>
          <?php echo h($game_display_answered); ?> / <?php echo h($game_stage['total']); ?>問
        </p>
        <p class="game-result__score">
          正解数: <?php echo h($game_display_correct); ?>問
          / 突破ライン: <?php echo h($game_stage['pass_score']); ?>問
        </p>
        <?php if ($game_message !== '') : ?>
          <p class="game-result__message <?php echo h($game_message_class); ?>">
            <?php echo h($game_message); ?>
          </p>
        <?php endif; ?>
      </div>
    <?php endif; ?>
    <div class="answer-table">
      <table class="answer-table__inner">
      <tr class="answer-table__row">
        <th class="answer-table__header">あなたの回答</th>
        <td class="answer-table__text">
        <?php echo $timeout === '1' ? '時間切れ' : h($submitted_answer); ?>
        </td>
      </tr>
      <tr class="answer-table__row">
        <th class="answer-table__header">正解</th>
        <td class="answer-table__text">
        <?php echo h(get_correct_answer_label($answer_status_code)); ?>
        </td>
      </tr>
      <tr class="answer-table__row">
        <th class="answer-table__header">説明</th>
        <td class="answer-table__text">
        <?php echo h($answer_status_code['explanation'] ?? $description); ?>
      </td>
      </tr>
      </table>
    </div>
    <div class="result-actions">
      <?php if ($is_review) : ?>
        <a class="result-actions__link" href="game.php">通常クイズへ</a>
      <?php else : ?>
        <a class="result-actions__link" href="<?php echo h($next_quiz_href); ?>"><?php echo h($next_quiz_label); ?></a>
      <?php endif; ?>
      <a class="result-actions__link" href="pages/review/mistakes.php">復習する</a>
      <a class="result-actions__link" href="pages/review/history.php">復習履歴</a>
    </div>
  </div>
</main>
<?php if ($is_review) : ?>
  <script>
    function playReviewFeedbackSound(isCorrect) {
      const AudioContext = window.AudioContext || window.webkitAudioContext;
      if (!AudioContext) {
        return;
      }

      const audioContext = new AudioContext();
      const now = audioContext.currentTime;
      const notes = isCorrect
        ? [{ frequency: 659.25, start: 0 }, { frequency: 880, start: 0.11 }]
        : [{ frequency: 220, start: 0 }, { frequency: 164.81, start: 0.13 }];

      notes.forEach((note) => {
        const oscillator = audioContext.createOscillator();
        const gain = audioContext.createGain();

        oscillator.type = isCorrect ? 'sine' : 'square';
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

    playReviewFeedbackSound(<?php echo $result ? 'true' : 'false'; ?>);
  </script>
<?php endif; ?>
</body>

</html>
