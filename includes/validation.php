<?php

function normalize_quiz_answer($answer)
{
    $answer = strtolower(trim($answer));
    $answer = preg_replace('/\s+/', '', $answer);
    $answer = trim($answer, '<>/');

    return $answer;
}

function get_submitted_answer($question_type, $option, $typed_answer)
{
    if ($question_type === 'sort' || $question_type === 'multiple_selection') {
        return str_replace(',', ' → ', $typed_answer);
    }

    return $question_type === 'input' ? $typed_answer : $option;
}

function is_quiz_answer_correct($question_type, $correct_code, $option, $typed_answer, $timeout, $accepted_answers = [])
{
    if ($timeout === '1') {
        return false;
    }

    if (empty($accepted_answers)) {
        $accepted_answers = [$correct_code];
    }

    if ($question_type === 'input') {
        $normalized_answer = normalize_quiz_answer($typed_answer);

        foreach ($accepted_answers as $accepted_answer) {
            if ($normalized_answer === normalize_quiz_answer($accepted_answer)) {
                return true;
            }
        }

        return false;
    }

    if ($question_type === 'sort') {
        $submitted_answers = array_values(array_filter(array_map('trim', explode(',', $typed_answer)), 'strlen'));
        $normalized_submitted_answer = implode(',', array_map('normalize_quiz_answer', $submitted_answers));
        $normalized_accepted_answer = implode(',', array_map('normalize_quiz_answer', $accepted_answers));

        return $normalized_submitted_answer === $normalized_accepted_answer;
    }

    if ($question_type === 'multiple_selection') {
        $submitted_answers = array_values(array_filter(array_map('trim', explode(',', $typed_answer)), 'strlen'));
        $normalized_submitted_answers = array_values(array_unique(array_map('normalize_quiz_answer', $submitted_answers)));
        $normalized_accepted_answers = array_values(array_unique(array_map('normalize_quiz_answer', $accepted_answers)));

        sort($normalized_submitted_answers);
        sort($normalized_accepted_answers);

        return $normalized_submitted_answers === $normalized_accepted_answers;
    }

    return in_array($option, $accepted_answers, true);
}

function has_submitted_answer($option, $typed_answer, $timeout, $question_type = 'choice')
{
    if ($timeout === '1') {
        return true;
    }

    if ($question_type === 'input') {
        return true;
    }

    if ($question_type === 'sort') {
        return $typed_answer !== '';
    }

    if ($question_type === 'multiple_selection') {
        return $typed_answer !== '';
    }

    return !empty($option);
}

function is_blank_input_answer($question_type, $typed_answer, $timeout)
{
    return $question_type === 'input' && $timeout !== '1' && trim($typed_answer) === '';
}
