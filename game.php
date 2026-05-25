<?php
session_start();

require_once(__DIR__ . '/data/question_loader.php');
require_once(__DIR__ . '/includes/common_functions.php');
require_once(__DIR__ . '/includes/validation.php');
require_once(__DIR__ . '/includes/quiz_functions.php');
require_once(__DIR__ . '/includes/ranking_functions.php');

$stages = get_quiz_stages();
$feedback = null;
$is_game_clear = false;
$blank_input_question_code = null;
$score_saved = false;
$score_error = '';

$request_method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if (isset($_GET['start']) || empty($_SESSION['quiz_game'])) {
  unset($_SESSION['clear_score'], $_SESSION['score_registered'], $_SESSION['game_over_sound_played']);
  start_quiz_game();
}

$is_score_submission = $request_method === 'POST' && isset($_POST['score_name']);

function get_fast_bonus_limit($question_type)
{
  if ($question_type === 'choice') {
    return 3;
  }

  if ($question_type === 'input' || $question_type === 'sort' || $question_type === 'multiple_selection') {
    return 5;
  }

  return 0;
}

function calculate_earned_points($is_correct, $question_type, $elapsed_seconds, $timeout)
{
  if (!$is_correct) {
    return 0;
  }

  $base_points = 1;
  $bonus_limit = get_fast_bonus_limit($question_type);
  if ($timeout !== '1' && $bonus_limit > 0 && $elapsed_seconds <= $bonus_limit) {
    return $base_points * 2;
  }

  return $base_points;
}

if ($is_score_submission) {
  $clear_score_to_save = $_SESSION['clear_score'] ?? null;
  if (!$clear_score_to_save) {
    $score_error = '登録できるスコアがありません。';
  } elseif (!empty($_SESSION['score_registered'])) {
    $score_saved = true;
  } else {
    $score_saved = save_ranking_entry(
      $_POST['score_name'] ?? '',
      $clear_score_to_save['score'],
      $clear_score_to_save['prelim_correct'],
      $clear_score_to_save['final_correct']
    );

    if ($score_saved) {
      $_SESSION['score_registered'] = true;
    } else {
      $score_error = 'スコアの登録に失敗しました。';
    }
  }
}

if ($request_method === 'POST' && !$is_score_submission) {
  $answer_code = isset($_POST['answer_code']) ? h($_POST['answer_code']) : null;
  $option = isset($_POST['option']) ? h($_POST['option']) : null;
  $typed_answer = isset($_POST['typed_answer']) ? trim($_POST['typed_answer']) : '';
  $posted_question_type = isset($_POST['question_type']) ? $_POST['question_type'] : 'choice';
  $timeout = isset($_POST['timeout']) ? $_POST['timeout'] : '0';
  $elapsed_seconds = isset($_POST['elapsed_seconds']) ? (float) $_POST['elapsed_seconds'] : 999;

  if (is_blank_input_answer($posted_question_type, $typed_answer, $timeout)) {
    $blank_input_question_code = $answer_code;
  } else {

    if (!has_submitted_answer($option, $typed_answer, $timeout, $posted_question_type)) {
      header('Location: index.php');
      exit;
    }

    $answered_question = find_question_by_code($status_codes, $answer_code);
    if (!$answered_question) {
      header('Location: index.php');
      exit;
    }

    $accepted_answers = get_accepted_answers($answered_question);
    $correct_answer = $accepted_answers[0] ?? $answered_question['code'];
    $is_correct = is_quiz_answer_correct($posted_question_type, $correct_answer, $option, $typed_answer, $timeout, $accepted_answers);
    $resolved_question_type = $answered_question['question_type'] ?? $posted_question_type;
    $earned_points = calculate_earned_points($is_correct, $resolved_question_type, $elapsed_seconds, $timeout);
    $game_result = update_quiz_game($is_correct, $earned_points);

    update_mistakes($answered_question['code'], $answered_question, $is_correct, false);

    $feedback = [
      'is_correct' => $is_correct,
      'is_timeout' => $timeout === '1',
      'correct_label' => get_correct_answer_label($answered_question),
      'explanation' => $answered_question['explanation'] ?? '',
      'earned_points' => $earned_points,
      'is_bonus' => $earned_points >= 2,
      'game_result' => $game_result,
    ];

    if (!empty($game_result['next_href']) && $game_result['next_href'] === 'preliminary.php') {
      $_SESSION['last_feedback_sound'] = $is_correct ? 'correct' : 'incorrect';
      header('Location: preliminary.php');
      exit;
    }

    if (!empty($game_result['next_href']) && $game_result['next_href'] === 'game_over.php') {
      $_SESSION['last_feedback_sound'] = $is_correct ? 'correct' : 'incorrect';
      header('Location: game_over.php');
      exit;
    }
  }
}

$game = $_SESSION['quiz_game'];
if (is_waiting_for_final()) {
  header('Location: preliminary.php');
  exit;
}

$is_game_finished = !empty($game['finished']);
$stage = $stages[$game['stage']];
$is_game_clear = $is_game_finished
  && $game['stage'] === 'final'
  && !empty($feedback['game_result']['message'])
  && $feedback['game_result']['message'] === 'ゲームクリア';

if ($is_game_clear) {
  $_SESSION['clear_score'] = build_clear_score($game);
}

if (!$is_game_clear && $is_game_finished && $game['stage'] === 'final' && !empty($_SESSION['clear_score'])) {
  $is_game_clear = true;
}

$clear_score = $is_game_clear ? ($_SESSION['clear_score'] ?? build_clear_score($game)) : null;
$ranking_entries = get_top_ranking_entries(10);
$finish_message = $is_game_clear ? 'ゲームクリア' : ($feedback['game_result']['message'] ?? 'ゲーム終了');
$should_play_clear_sound = $is_game_clear && !$is_score_submission && !empty($feedback['game_result']['message']);
$question_number = $game['answered'] + 1;
$question = null;
$question_type = null;
$options = [];
$answer_placeholder = '答えを入力';
$time_limit = 10;

if (!$is_game_finished) {
  $option_count = 5;
  if ($blank_input_question_code !== null) {
    $question = find_question_by_code($status_codes, $blank_input_question_code);
    $question_type = 'input';
    $question_pool = $question ? [$question] : [];
  } else {
    $question_type = choose_available_question_type($choice_questions, $input_questions, $sort_questions, $multiple_selection_questions);
    if ($question_type === 'input') {
      $question_pool = $input_questions;
    } elseif ($question_type === 'sort') {
      $question_pool = $sort_questions;
    } elseif ($question_type === 'multiple_selection') {
      $question_pool = $multiple_selection_questions;
    } else {
      $question_pool = $choice_questions;
    }
    $question_pool = get_unasked_questions($question_pool);
  }

  if (empty($question_pool)) {
    $question_pool = get_unasked_questions($status_codes);
    $question_type = null;
  }

  if (empty($question_pool)) {
    $_SESSION['quiz_game']['finished'] = true;
    $is_game_finished = true;
  } else {
    if ($question === null) {
      $answer_index = array_rand($question_pool);
      $question = $question_pool[$answer_index];
      remember_asked_question($question['code']);
    }
    $question_type = $question['question_type'] ?? ($question_type ?? (question_has_choices($question) ? 'choice' : 'input'));
    if (!isset($_SESSION['quiz_game']['asked_types']) || !is_array($_SESSION['quiz_game']['asked_types'])) {
      $_SESSION['quiz_game']['asked_types'] = [];
    }
    if (!in_array($question_type, $_SESSION['quiz_game']['asked_types'], true)) {
      $_SESSION['quiz_game']['asked_types'][] = $question_type;
    }
    $options = build_quiz_options($status_codes, $question, $option_count);
    $answer_placeholder = question_has_choices($question) ? '例: A または 選択肢の内容' : '答えを入力';
    if ($question_type === 'choice') {
      $time_limit = 5;
    } else {
      $time_limit = 10;
    }
  }
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
  <link rel="stylesheet" href="css/game.css">
  <link rel="stylesheet" href="css/multiple_selection.css">
  <?php if ($game['stage'] === 'final') : ?>
    <link rel="stylesheet" href="css/final.css">
  <?php else : ?>
    <link rel="stylesheet" href="css/preliminary.css">
  <?php endif; ?>
</head>

<body class="stage-<?php echo h($game['stage']); ?>">
  <header class="header">
    <div class="header__inner">
      <a class="header__logo" href="index.php">
        Quiz
      </a>
    </div>
  </header>

  <main>
    <div class="quiz__content">
      <?php if ($feedback) : ?>
        <div class="quick-feedback <?php echo $feedback['is_correct'] ? 'quick-feedback--correct' : 'quick-feedback--incorrect'; ?> <?php echo !empty($feedback['is_bonus']) ? 'quick-feedback--bonus' : ''; ?>">
          <span class="quick-feedback__mark" aria-hidden="true">
            <?php echo $feedback['is_correct'] ? '○' : '×'; ?>
          </span>
          <span class="quick-feedback__body">
            <span class="quick-feedback__result">
              <?php if (!empty($feedback['is_bonus'])) : ?>
                スピード正解
              <?php else : ?>
                <?php echo $feedback['is_timeout'] ? '時間切れ' : ($feedback['is_correct'] ? '正解' : '不正解'); ?>
              <?php endif; ?>
            </span>
            <span class="quick-feedback__answer">
              正解: <?php echo h($feedback['correct_label']); ?>
              <?php if ($feedback['is_correct']) : ?>
                / +<?php echo h($feedback['earned_points']); ?>点
              <?php endif; ?>
            </span>
            <?php if (!empty($feedback['is_bonus'])) : ?>
              <span class="quick-feedback__bonus">FAST BONUS x2</span>
            <?php endif; ?>
            <?php if (!empty($feedback['game_result']['message'])) : ?>
              <span class="quick-feedback__stage">
                <?php echo h($feedback['game_result']['message']); ?>
              </span>
            <?php endif; ?>
          </span>
        </div>
      <?php endif; ?>

      <div class="game-status">
        <span class="game-status__stage"><?php echo h($stage['name']); ?></span>
        <span class="game-status__score">
          <?php echo h($is_game_finished ? $stage['total'] : $question_number); ?> / <?php echo h($stage['total']); ?>問目
          ・正解 <?php echo h($game['correct']); ?>問
          ・得点 <?php echo h($game['points'] ?? 0); ?>点
          ・突破ライン <?php echo h($stage['pass_score']); ?>問
        </span>
      </div>

      <?php if ($is_game_finished) : ?>
        <div class="game-finish <?php echo $is_game_clear ? 'game-finish--clear' : ''; ?>">
          <?php if ($is_game_clear) : ?>
            <div class="game-finish__rays" aria-hidden="true"></div>
            <div class="game-finish__confetti" aria-hidden="true">
              <span></span>
              <span></span>
              <span></span>
              <span></span>
              <span></span>
              <span></span>
              <span></span>
              <span></span>
              <span></span>
              <span></span>
              <span></span>
              <span></span>
            </div>
            <div class="game-finish__burst" aria-hidden="true"></div>
            <div class="game-finish__badge">ARCADE CLEAR</div>
          <?php endif; ?>
          <h2 class="game-finish__title">
            <?php echo h($finish_message); ?>
          </h2>
          <p class="game-finish__text">
            <?php echo h($stage['name']); ?>の結果: <?php echo h($game['correct']); ?> / <?php echo h($stage['total']); ?>問正解
          </p>
          <?php if ($is_game_clear && $clear_score) : ?>
            <section class="ranking-panel" aria-label="ランキング">
              <div class="ranking-panel__summary">
                <span class="ranking-panel__label">TOTAL SCORE</span>
                <strong class="ranking-panel__score"><?php echo h($clear_score['score']); ?> / 30</strong>
                <span class="ranking-panel__breakdown">
                  予選 <?php echo h($clear_score['prelim_correct']); ?>問 / <?php echo h($clear_score['prelim_points']); ?>点
                  + 本選 <?php echo h($clear_score['final_correct']); ?>問 / <?php echo h($clear_score['final_points']); ?>点
                </span>
              </div>

              <?php if ($score_saved) : ?>
                <p class="ranking-panel__message">スコアを登録しました。</p>
              <?php elseif ($score_error !== '') : ?>
                <p class="ranking-panel__message ranking-panel__message--error"><?php echo h($score_error); ?></p>
              <?php endif; ?>

              <?php if (empty($_SESSION['score_registered'])) : ?>
                <form class="ranking-form" action="game.php" method="post">
                  <label class="ranking-form__label" for="score-name">名前を登録</label>
                  <div class="ranking-form__row">
                    <input
                      class="ranking-form__input"
                      type="text"
                      name="score_name"
                      id="score-name"
                      maxlength="20"
                      autocomplete="nickname"
                      placeholder="PLAYER NAME"
                    >
                    <button class="ranking-form__button" type="submit">登録</button>
                  </div>
                </form>
              <?php endif; ?>

              <div class="ranking-list">
                <h3 class="ranking-list__title">得点ランキング</h3>
                <?php if (!empty($ranking_entries)) : ?>
                  <ol class="ranking-list__items">
                    <?php $display_rank = 0; ?>
                    <?php $previous_score = null; ?>
                    <?php foreach ($ranking_entries as $index => $entry) : ?>
                      <?php if ($previous_score === null || (int) $entry['score'] !== (int) $previous_score) : ?>
                        <?php $display_rank = $index + 1; ?>
                        <?php $previous_score = (int) $entry['score']; ?>
                      <?php endif; ?>
                      <li class="ranking-list__item">
                        <span class="ranking-list__rank"><?php echo h($display_rank); ?></span>
                        <span class="ranking-list__name"><?php echo h($entry['name']); ?></span>
                        <strong class="ranking-list__score"><?php echo h($entry['score']); ?>点</strong>
                      </li>
                    <?php endforeach; ?>
                  </ol>
                <?php else : ?>
                  <p class="ranking-list__empty">まだ登録されたスコアはありません。</p>
                <?php endif; ?>
              </div>
            </section>
          <?php endif; ?>
          <div class="game-finish__actions">
            <a class="game-finish__link" href="game.php?start=1">もう一度挑戦</a>
            <a class="game-finish__link game-finish__link--secondary" href="index.php">ホームに戻る</a>
          </div>
        </div>
      <?php else : ?>
        <div class="question">
          <?php if ($question_type === 'input') : ?>
            <p class="question__text">Q. 以下の問題の答えを入力してください</p>
          <?php elseif ($question_type === 'sort') : ?>
            <p class="question__text">Q. 正しい順番に並べ替えてください</p>
          <?php elseif ($question_type === 'multiple_selection') : ?>
            <p class="question__text">Q. 正解だと思うものを<?php echo h($question['select_count'] ?? count($question['answer'] ?? [])); ?>つ選んでください</p>
          <?php else : ?>
            <p class="question__text">Q. 以下の問題の答えを選んでください</p>
          <?php endif; ?>
          <p class="question__text">
            <?php echo h($question['description']); ?>
          </p>
        </div>
        <form class="quiz-form" action="game.php" method="post">
          <input type="hidden" name="answer_code" value="<?php echo h($question['code']); ?>">
          <input type="hidden" name="question_type" value="<?php echo h($question_type); ?>">
          <input type="hidden" name="timeout" value="0" id="timeout">
          <input type="hidden" name="elapsed_seconds" value="0" id="elapsed-seconds">
          <div id="timer">残り時間: <?php echo h($time_limit); ?>秒</div>
          <?php if ($question_type === 'input') : ?>
            <?php require(__DIR__ . '/pages/quiz/input.php'); ?>
          <?php elseif ($question_type === 'sort') : ?>
            <?php require(__DIR__ . '/pages/quiz/sort.php'); ?>
          <?php elseif ($question_type === 'multiple_selection') : ?>
            <?php require(__DIR__ . '/pages/quiz/multiple_selection.php'); ?>
          <?php else : ?>
            <?php require(__DIR__ . '/pages/quiz/selection.php'); ?>
          <?php endif; ?>
        </form>
      <?php endif; ?>
    </div>
  </main>

  <script src="js/gameTimer.js"></script>
  <script src="js/multipleSelection.js"></script>
  <script>
    const feedbackResult = <?php echo $should_play_clear_sound ? json_encode('clear') : ($feedback ? json_encode(!empty($feedback['is_bonus']) ? 'bonus' : ($feedback['is_correct'] ? 'correct' : 'incorrect')) : 'null'); ?>;
    function playFeedbackSound(result) {
      const AudioContext = window.AudioContext || window.webkitAudioContext;
      if (!AudioContext || !result) {
        return;
      }

      const audioContext = new AudioContext();
      const now = audioContext.currentTime;
      const notes = result === 'clear'
        ? [
            { frequency: 261.63, start: 0, duration: 0.18, type: 'square', volume: 0.08 },
            { frequency: 523.25, start: 0.02, duration: 0.22, type: 'triangle', volume: 0.14 },
            { frequency: 659.25, start: 0.16, duration: 0.2, type: 'triangle', volume: 0.14 },
            { frequency: 783.99, start: 0.3, duration: 0.22, type: 'triangle', volume: 0.14 },
            { frequency: 1046.5, start: 0.48, duration: 0.28, type: 'sine', volume: 0.16 },
            { frequency: 1318.51, start: 0.68, duration: 0.28, type: 'sine', volume: 0.16 },
            { frequency: 1567.98, start: 0.88, duration: 0.42, type: 'sine', volume: 0.14 },
            { frequency: 1046.5, start: 1.04, duration: 0.5, type: 'triangle', volume: 0.1 }
          ]
        : result === 'bonus'
        ? [
            { frequency: 783.99, start: 0, duration: 0.12, type: 'triangle', volume: 0.1 },
            { frequency: 987.77, start: 0.08, duration: 0.12, type: 'triangle', volume: 0.11 },
            { frequency: 1318.51, start: 0.16, duration: 0.18, type: 'sine', volume: 0.1 }
          ]
        : result === 'correct'
        ? [{ frequency: 659.25, start: 0 }, { frequency: 880, start: 0.11 }]
        : [{ frequency: 220, start: 0 }, { frequency: 164.81, start: 0.13 }];

      notes.forEach((note) => {
        const oscillator = audioContext.createOscillator();
        const gain = audioContext.createGain();

        oscillator.type = note.type || (result === 'incorrect' ? 'square' : 'sine');
        oscillator.frequency.setValueAtTime(note.frequency, now + note.start);
        gain.gain.setValueAtTime(0.0001, now + note.start);
        gain.gain.exponentialRampToValueAtTime(note.volume || (result === 'clear' ? 0.16 : 0.12), now + note.start + 0.02);
        gain.gain.exponentialRampToValueAtTime(0.0001, now + note.start + (note.duration || (result === 'clear' ? 0.28 : 0.18)));

        oscillator.connect(gain);
        gain.connect(audioContext.destination);
        oscillator.start(now + note.start);
        oscillator.stop(now + note.start + (note.duration || (result === 'clear' ? 0.3 : 0.2)));
      });
    }

    playFeedbackSound(feedbackResult);

    <?php if (!$is_game_finished) : ?>
      const form = document.querySelector('.quiz-form');
      const typedAnswer = document.getElementById('typed-answer');
      const elapsedSeconds = document.getElementById('elapsed-seconds');
      const questionStartedAt = Date.now();
      function setElapsedSeconds() {
        if (!elapsedSeconds) {
          return;
        }

        elapsedSeconds.value = ((Date.now() - questionStartedAt) / 1000).toFixed(2);
      }

      if (typedAnswer) {
        typedAnswer.focus();
      }

      form.addEventListener('submit', (event) => {
        setElapsedSeconds();
        const isTimeout = document.getElementById('timeout').value === '1';
        if (typedAnswer && !isTimeout && typedAnswer.value.trim() === '') {
          event.preventDefault();
          typedAnswer.focus();
        }

        const sortQuiz = document.querySelector('[data-sort-quiz]');
        const sortAnswer = document.getElementById('sort-answer');
        if (sortQuiz && sortAnswer && !isTimeout) {
          const sortTotal = Number(sortQuiz.dataset.sortTotal || 0);
          const sortAnswered = sortAnswer.value === '' ? 0 : sortAnswer.value.split(',').length;
          if (sortAnswered < sortTotal) {
            event.preventDefault();
          }
        }

        const multipleSelection = document.querySelector('[data-multiple-selection]');
        const multipleAnswer = document.getElementById('multiple-selection-answer');
        if (multipleSelection && multipleAnswer && !isTimeout) {
          const selectCount = Number(multipleSelection.dataset.selectCount || 0);
          const selectedCount = multipleAnswer.value === '' ? 0 : multipleAnswer.value.split(',').length;
          if (selectedCount !== selectCount) {
            event.preventDefault();
          }
        }
      });

      document.querySelectorAll('.quiz-form__radio').forEach((radio) => {
        radio.addEventListener('change', () => {
          setElapsedSeconds();
          form.submit();
        });
      });

      setupSortQuiz(form);
      setupMultipleSelectionQuiz(form);

      let timeLeft = <?php echo h($time_limit); ?>;
      const timerElement = document.getElementById('timer');

      const countdown = setInterval(() => {
        timeLeft--;
        timerElement.textContent = `残り時間: ${timeLeft}秒`;

        if (timeLeft <= 0) {
          clearInterval(countdown);
          document.getElementById('timeout').value = '1';
          setElapsedSeconds();
          form.submit();
        }
      }, 1000);
    <?php endif; ?>
  </script>
</body>

</html>
