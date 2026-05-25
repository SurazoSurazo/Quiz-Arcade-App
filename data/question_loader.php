<?php

function load_questions($path = null)
{
    $json_path = $path ?? __DIR__ . '/questions.json';

    if (!is_readable($json_path)) {
        return [];
    }

    $json = file_get_contents($json_path);
    if ($json === false || trim($json) === '') {
        return [];
    }

    $data = json_decode($json, true);
    if (!is_array($data)) {
        return [];
    }

    if (isset($data['questions']) && is_array($data['questions'])) {
        $questions = $data['questions'];
    } elseif (isset($data['quiz']) && is_array($data['quiz'])) {
        $questions = $data['quiz'];
    } elseif (isset($data['sort']) && is_array($data['sort'])) {
        $questions = $data['sort'];
    } elseif (isset($data['sort_questions']) && is_array($data['sort_questions'])) {
        $questions = $data['sort_questions'];
    } else {
        $questions = $data;
    }

    return array_values(array_filter(array_map(function ($question) {
        return normalize_question_data($question);
    }, $questions)));
}

function load_choice_questions()
{
    return array_merge(
        load_typed_questions(__DIR__ . '/four_choice_questions.json', 'choice', 'four_choice'),
        load_typed_questions(__DIR__ . '/five_choice_questions.json', 'choice', 'five_choice')
    );
}

function load_input_questions()
{
    return load_typed_questions(__DIR__ . '/input_questions.json', 'input');
}

function load_sort_questions()
{
    return load_typed_questions(__DIR__ . '/sort_questions.json', 'sort');
}

function load_multiple_selection_questions()
{
    return load_typed_questions(__DIR__ . '/multiple_selection_questions.json', 'multiple_selection');
}

function load_typed_questions($path, $question_type, $code_prefix = null)
{
    $questions = load_questions($path);
    $prefix = $code_prefix ?? $question_type;

    return array_values(array_filter(array_map(function ($question) use ($question_type, $prefix) {
        $question['question_type'] = $question_type;
        if (strpos($question['code'], $prefix . '-') !== 0) {
            $question['code'] = $prefix . '-' . $question['code'];
        }

        return $question;
    }, $questions)));
}

function normalize_question_data($question)
{
    if (!is_array($question)) {
        return null;
    }

    $code = $question['code'] ?? $question['id'] ?? null;
    $description = $question['description'] ?? $question['question'] ?? $question['prompt'] ?? $question['text'] ?? null;
    $raw_choices = $question['choices'] ?? $question['options'] ?? $question['items'] ?? $question['words'] ?? $question['parts'] ?? $question['sortable_items'] ?? [];
    $answer = $question['answer'] ?? $question['answers'] ?? $question['correct_answer'] ?? $question['correct'] ?? $question['order'] ?? $question['correct_order'] ?? $question['sequence'] ?? null;

    if ($answer === null && (($question['type'] ?? '') === 'sort' || !empty($question['items']) || !empty($question['sortable_items']))) {
        $answer = range(1, count($raw_choices));
    }

    if ($code === null || $description === null || $answer === null) {
        return null;
    }

    $choices = normalize_question_choices($raw_choices);
    $answer = normalize_question_answer($answer, $raw_choices, $choices);

    return [
        'code' => (string) $code,
        'meaning' => $question['meaning'] ?? $question['topic'] ?? $question['category'] ?? '',
        'description' => (string) $description,
        'choices' => $choices,
        'answer' => is_array($answer) ? array_values($answer) : (string) $answer,
        'select_count' => (int) ($question['selectCount'] ?? $question['select_count'] ?? (is_array($answer) ? count($answer) : 1)),
        'explanation' => $question['explanation'] ?? '',
        'category' => $question['category'] ?? '',
        'topic' => $question['topic'] ?? '',
    ];
}

function normalize_question_answer($answer, $raw_choices, $choices)
{
    $answers = is_array($answer) ? array_values($answer) : [$answer];
    $normalized_answers = [];
    $raw_choices_are_list = is_array($raw_choices) && array_keys($raw_choices) === range(0, count($raw_choices) - 1);
    $choice_keys = array_keys($choices);

    foreach ($answers as $answer_item) {
        if ($raw_choices_are_list && is_numeric($answer_item)) {
            $answer_number = (int) $answer_item;
            $choice_index = $answer_number - 1;

            if (isset($choice_keys[$choice_index])) {
                $normalized_answers[] = $choice_keys[$choice_index];
                continue;
            }
        }

        $normalized_answers[] = (string) $answer_item;
    }

    return is_array($answer) ? $normalized_answers : (string) ($normalized_answers[0] ?? '');
}

function normalize_question_choices($choices)
{
    if (!is_array($choices)) {
        return [];
    }

    $normalized_choices = [];
    $choice_labels = range('A', 'Z');
    $index = 0;

    foreach ($choices as $key => $choice) {
        $choice_key = is_int($key) ? ($choice_labels[$index] ?? (string) ($index + 1)) : (string) $key;

        if (is_array($choice)) {
            $choice_text = $choice['text'] ?? $choice['label'] ?? $choice['description'] ?? $choice['answer'] ?? null;
            if ($choice_text === null) {
                $choice_text = implode(' ', array_map('strval', $choice));
            }
        } else {
            $choice_text = $choice;
        }

        $normalized_choices[$choice_key] = (string) $choice_text;
        $index++;
    }

    return $normalized_choices;
}

$choice_questions = load_choice_questions();
$input_questions = load_input_questions();
$sort_questions = load_sort_questions();
$multiple_selection_questions = load_multiple_selection_questions();
$status_codes = array_merge($choice_questions, $input_questions, $sort_questions, $multiple_selection_questions);
