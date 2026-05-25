<?php

function get_quiz_stages()
{
    return [
        'prelim' => [
            'name' => '予選',
            'total' => 5,
            'pass_score' => 3,
            'pass_message' => '本選進出',
            'fail_message' => 'ゲームオーバー',
        ],
        'final' => [
            'name' => '本選',
            'total' => 10,
            'pass_score' => 8,
            'pass_message' => 'ゲームクリア',
            'fail_message' => 'ゲームオーバー',
        ],
    ];
}

function start_quiz_game()
{
    $_SESSION['quiz_game'] = [
        'stage' => 'prelim',
        'answered' => 0,
        'correct' => 0,
        'points' => 0,
        'finished' => false,
        'asked_codes' => [],
        'asked_types' => [],
        'awaiting_final' => false,
        'prelim_result' => null,
    ];
}

function prepare_quiz_game()
{
    if (isset($_GET['start']) || empty($_SESSION['quiz_game']) || !empty($_SESSION['quiz_game']['finished'])) {
        start_quiz_game();
    }

    return $_SESSION['quiz_game'];
}

function update_quiz_game($is_correct, $earned_points = 0)
{
    $stages = get_quiz_stages();
    $game = $_SESSION['quiz_game'] ?? null;

    if (!is_array($game) || !empty($game['finished']) || !isset($stages[$game['stage']])) {
        return null;
    }

    $stage = $stages[$game['stage']];
    if (!isset($game['points'])) {
        $game['points'] = 0;
    }
    $game['answered']++;

    if ($is_correct) {
        $game['correct']++;
        $game['points'] += (int) $earned_points;
    }

    $result = [
        'stage' => $stage,
        'answered' => $game['answered'],
        'correct' => $game['correct'],
        'points' => $game['points'],
        'earned_points' => (int) $earned_points,
        'message' => '',
        'message_class' => '',
        'next_label' => '次の問題へ',
        'next_href' => 'game.php',
    ];

    $incorrect_count = $game['answered'] - $game['correct'];
    $game_over_miss_limit = $stage['total'] - $stage['pass_score'] + 1;

    if ($incorrect_count >= $game_over_miss_limit) {
        $result['message'] = $stage['fail_message'];
        $result['message_class'] = 'game-result__message--danger';
        $game['finished'] = true;
        $result['next_label'] = 'もう一度挑戦';
        $result['next_href'] = 'game_over.php';
    } elseif ($game['answered'] >= $stage['total']) {
        if ($game['correct'] >= $stage['pass_score']) {
            $result['message'] = $stage['pass_message'];
            $result['message_class'] = 'game-result__message--success';

            if ($game['stage'] === 'prelim') {
                $asked_codes = isset($game['asked_codes']) && is_array($game['asked_codes']) ? $game['asked_codes'] : [];
                $game = [
                    'stage' => 'final',
                    'answered' => 0,
                    'correct' => 0,
                    'points' => 0,
                    'finished' => false,
                    'asked_codes' => $asked_codes,
                    'asked_types' => [],
                    'awaiting_final' => true,
                    'prelim_result' => [
                        'answered' => $result['answered'],
                        'correct' => $result['correct'],
                        'points' => $result['points'],
                        'total' => $stage['total'],
                        'pass_score' => $stage['pass_score'],
                    ],
                ];
                $result['next_label'] = '本選へ進む';
                $result['next_href'] = 'preliminary.php';
            } else {
                $game['finished'] = true;
                $result['next_label'] = 'もう一度挑戦';
                $result['next_href'] = 'game.php?start=1';
            }
        } else {
            $result['message'] = $stage['fail_message'];
            $result['message_class'] = 'game-result__message--danger';
            $game['finished'] = true;
            $result['next_label'] = 'もう一度挑戦';
            $result['next_href'] = 'game_over.php';
        }
    }

    $_SESSION['quiz_game'] = $game;

    return $result;
}

function is_waiting_for_final()
{
    return !empty($_SESSION['quiz_game']['awaiting_final']);
}

function start_final_stage()
{
    if (empty($_SESSION['quiz_game']) || $_SESSION['quiz_game']['stage'] !== 'final') {
        return false;
    }

    $_SESSION['quiz_game']['awaiting_final'] = false;

    return true;
}

function get_unasked_questions($questions)
{
    $game = $_SESSION['quiz_game'] ?? [];
    $asked_codes = isset($game['asked_codes']) && is_array($game['asked_codes']) ? $game['asked_codes'] : [];

    return array_values(array_filter($questions, function ($question) use ($asked_codes) {
        return isset($question['code']) && !in_array($question['code'], $asked_codes, true);
    }));
}

function remember_asked_question($question_code)
{
    if (empty($_SESSION['quiz_game']) || empty($question_code)) {
        return;
    }

    if (!isset($_SESSION['quiz_game']['asked_codes']) || !is_array($_SESSION['quiz_game']['asked_codes'])) {
        $_SESSION['quiz_game']['asked_codes'] = [];
    }

    if (!in_array($question_code, $_SESSION['quiz_game']['asked_codes'], true)) {
        $_SESSION['quiz_game']['asked_codes'][] = $question_code;
    }
}

function normalize_review_history($review_history)
{
    $normalized_review_history = [];

    foreach ($review_history as $key => $history) {
        $history_code = isset($history['code']) ? $history['code'] : $key;
        if (empty($history_code)) {
            continue;
        }

        if (!isset($normalized_review_history[$history_code])) {
            $normalized_review_history[$history_code] = [
                'description' => isset($history['description']) ? $history['description'] : '',
                'count' => 0,
                'reviewed_at_list' => [],
            ];
        }

        if (!empty($history['description'])) {
            $normalized_review_history[$history_code]['description'] = $history['description'];
        }

        if (!empty($history['reviewed_at_list']) && is_array($history['reviewed_at_list'])) {
            foreach ($history['reviewed_at_list'] as $reviewed_at) {
                $normalized_review_history[$history_code]['reviewed_at_list'][] = $reviewed_at;
            }
        } elseif (!empty($history['reviewed_at'])) {
            $normalized_review_history[$history_code]['reviewed_at_list'][] = $history['reviewed_at'];
        }

        $normalized_review_history[$history_code]['count'] = count($normalized_review_history[$history_code]['reviewed_at_list']);
    }

    return $normalized_review_history;
}

function record_review_history($code, $description)
{
    $_SESSION['review_history'] = normalize_review_history($_SESSION['review_history'] ?? []);

    if (!isset($_SESSION['review_history'][$code])) {
        $_SESSION['review_history'][$code] = [
            'description' => $description,
            'count' => 0,
            'reviewed_at_list' => [],
        ];
    }

    $_SESSION['review_history'][$code]['description'] = $description;
    $_SESSION['review_history'][$code]['reviewed_at_list'][] = date('Y年m月d日 H:i:s');
    $_SESSION['review_history'][$code]['count'] = count($_SESSION['review_history'][$code]['reviewed_at_list']);
}

function normalize_mistakes($mistakes)
{
    $normalized_mistakes = [];

    foreach ($mistakes as $mistake) {
        if (isset($mistake['code'])) {
            $normalized_mistakes[$mistake['code']] = $mistake;
        }
    }

    return $normalized_mistakes;
}

function update_mistakes($code, $question, $is_correct, $is_review)
{
    $_SESSION['mistakes'] = normalize_mistakes($_SESSION['mistakes'] ?? []);

    if ($is_correct) {
        if ($is_review && isset($_SESSION['mistakes'][$code])) {
            unset($_SESSION['mistakes'][$code]);
        }

        return;
    }

    if (!empty($question)) {
        $_SESSION['mistakes'][$code] = $question;
    }
}
