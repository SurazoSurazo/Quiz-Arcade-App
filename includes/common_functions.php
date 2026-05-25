<?php

function h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES);
}

function find_question_by_code($questions, $code)
{
    foreach ($questions as $question) {
        if ($question['code'] === $code) {
            return $question;
        }
    }

    return null;
}

function choose_question_type($question_types = ['choice', 'input'])
{
    return $question_types[array_rand($question_types)];
}

function choose_available_question_type($choice_questions, $input_questions, $sort_questions = [], $multiple_selection_questions = [])
{
    $available_question_types = [];

    if (!empty($choice_questions)) {
        $available_question_types[] = 'choice';
    }

    if (!empty($input_questions)) {
        $available_question_types[] = 'input';
    }

    if (!empty($sort_questions)) {
        $available_question_types[] = 'sort';
    }

    if (!empty($multiple_selection_questions)) {
        $available_question_types[] = 'multiple_selection';
    }

    if (!empty($available_question_types)) {
        $asked_types = $_SESSION['quiz_game']['asked_types'] ?? [];
        if (is_array($asked_types)) {
            $unused_question_types = array_values(array_diff($available_question_types, $asked_types));
            if (!empty($unused_question_types)) {
                return choose_question_type($unused_question_types);
            }
        }

        return choose_question_type($available_question_types);
    }

    if (empty($choice_questions)) {
        return 'input';
    }

    if (empty($input_questions)) {
        return 'choice';
    }

    return choose_question_type();
}

function question_has_choices($question)
{
    return !empty($question['choices']) && is_array($question['choices']);
}

function get_question_choices($question)
{
    if (!question_has_choices($question)) {
        return [];
    }

    return $question['choices'];
}

function shuffle_assoc($items)
{
    $keys = array_keys($items);
    shuffle($keys);

    $shuffled = [];
    foreach ($keys as $key) {
        $shuffled[$key] = $items[$key];
    }

    return $shuffled;
}

function get_correct_answer_label($question)
{
    if (!isset($question['answer'])) {
        return $question['code'];
    }

    if (($question['question_type'] ?? '') === 'sort') {
        return implode(' → ', get_sort_answer_labels($question));
    }

    if (($question['question_type'] ?? '') === 'multiple_selection') {
        return implode(' / ', get_multiple_selection_answer_labels($question));
    }

    $answer = is_array($question['answer']) ? (string) $question['answer'][0] : (string) $question['answer'];
    $choices = get_question_choices($question);

    if (isset($choices[$answer])) {
        return $answer . '. ' . $choices[$answer];
    }

    return $answer;
}

function get_multiple_selection_answer_labels($question)
{
    $answers = isset($question['answer']) && is_array($question['answer']) ? $question['answer'] : [$question['answer'] ?? ''];
    $choices = get_question_choices($question);

    return array_map(function ($answer) use ($choices) {
        if (isset($choices[$answer])) {
            return $choices[$answer];
        }

        foreach ($choices as $choice_text) {
            if ((string) $choice_text === (string) $answer) {
                return (string) $choice_text;
            }
        }

        return (string) $answer;
    }, $answers);
}

function get_multiple_selection_answer_codes($question)
{
    $answers = isset($question['answer']) && is_array($question['answer']) ? $question['answer'] : [$question['answer'] ?? ''];
    $choices = get_question_choices($question);

    return array_values(array_map(function ($answer) use ($choices) {
        if (isset($choices[$answer])) {
            return (string) $answer;
        }

        foreach ($choices as $choice_code => $choice_text) {
            if ((string) $choice_text === (string) $answer) {
                return (string) $choice_code;
            }
        }

        return (string) $answer;
    }, $answers));
}

function get_sort_answer_labels($question)
{
    $answers = get_sort_answer_codes($question);
    $choices = get_question_choices($question);

    return array_map(function ($answer) use ($choices) {
        return $choices[$answer] ?? (string) $answer;
    }, $answers);
}

function get_sort_answer_codes($question)
{
    $answers = isset($question['answer']) && is_array($question['answer']) ? $question['answer'] : [$question['answer'] ?? ''];
    $choices = get_question_choices($question);

    return array_values(array_map(function ($answer) use ($choices) {
        if (isset($choices[$answer])) {
            return (string) $answer;
        }

        foreach ($choices as $choice_code => $choice_text) {
            if ((string) $choice_text === (string) $answer) {
                return (string) $choice_code;
            }
        }

        return (string) $answer;
    }, $answers));
}

function get_accepted_answers($question)
{
    if (!isset($question['answer'])) {
        return [$question['code']];
    }

    if (($question['question_type'] ?? '') === 'sort') {
        return get_sort_answer_codes($question);
    }

    if (($question['question_type'] ?? '') === 'multiple_selection') {
        return get_multiple_selection_answer_codes($question);
    }

    $answers = is_array($question['answer']) ? $question['answer'] : [$question['answer']];
    $choices = get_question_choices($question);

    foreach ($answers as $answer) {
        if (isset($choices[$answer])) {
            $answers[] = $choices[$answer];
        }

        foreach ($choices as $choice_code => $choice_text) {
            if ((string) $choice_text === (string) $answer) {
                $answers[] = $choice_code;
            }
        }
    }

    return array_values(array_unique(array_map('strval', $answers)));
}

function build_quiz_options($questions, $answer_question, $option_count = 5)
{
    if (question_has_choices($answer_question)) {
        return shuffle_assoc(get_question_choices($answer_question));
    }

    $options = [$answer_question['code'] => $answer_question];
    $candidate_options = array_filter($questions, function ($question) use ($answer_question) {
        return $question['code'] !== $answer_question['code'];
    });

    $candidate_count = min($option_count - 1, count($candidate_options));
    if ($candidate_count > 0) {
        $candidate_keys = array_rand($candidate_options, $candidate_count);
        if (!is_array($candidate_keys)) {
            $candidate_keys = [$candidate_keys];
        }

        foreach ($candidate_keys as $key) {
            $options[$candidate_options[$key]['code']] = $candidate_options[$key];
        }
    }

    $options = array_values($options);
    shuffle($options);

    return $options;
}
