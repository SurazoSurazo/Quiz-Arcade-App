<?php

function get_ranking_file_path()
{
    return __DIR__ . '/../data/ranking.json';
}

function normalize_score_name($name)
{
    $normalized_name = trim((string) $name);
    if ($normalized_name === '') {
        return 'NO NAME';
    }

    if (function_exists('mb_substr')) {
        return mb_substr($normalized_name, 0, 20);
    }

    return substr($normalized_name, 0, 20);
}

function normalize_ranking_entries($entries)
{
    if (!is_array($entries)) {
        return [];
    }

    return array_values(array_filter(array_map(function ($entry) {
        if (!is_array($entry)) {
            return null;
        }

        return [
            'name' => normalize_score_name($entry['name'] ?? ''),
            'score' => (int) ($entry['score'] ?? 0),
            'prelim_correct' => (int) ($entry['prelim_correct'] ?? 0),
            'final_correct' => (int) ($entry['final_correct'] ?? 0),
            'created_at' => (string) ($entry['created_at'] ?? ''),
        ];
    }, $entries)));
}

function sort_ranking_entries($entries)
{
    usort($entries, function ($a, $b) {
        if ($a['score'] !== $b['score']) {
            return $b['score'] <=> $a['score'];
        }

        return strcmp($a['created_at'], $b['created_at']);
    });

    return $entries;
}

function load_ranking_entries()
{
    $ranking_file = get_ranking_file_path();
    if (!file_exists($ranking_file)) {
        return [];
    }

    $json = file_get_contents($ranking_file);
    $entries = json_decode($json ?: '[]', true);

    return sort_ranking_entries(normalize_ranking_entries($entries));
}

function get_top_ranking_entries($limit = 10)
{
    return array_slice(load_ranking_entries(), 0, $limit);
}

function add_ranking_display_ranks($entries)
{
    $ranked_entries = [];
    $display_rank = 0;
    $previous_score = null;

    foreach (array_values($entries) as $index => $entry) {
        if ($previous_score === null || (int) $entry['score'] !== (int) $previous_score) {
            $display_rank = $index + 1;
            $previous_score = (int) $entry['score'];
        }

        $entry['rank'] = $display_rank;
        $ranked_entries[] = $entry;
    }

    return $ranked_entries;
}

function save_ranking_entry($name, $score, $prelim_correct, $final_correct)
{
    $ranking_file = get_ranking_file_path();
    $handle = fopen($ranking_file, 'c+');
    if (!$handle) {
        return false;
    }

    flock($handle, LOCK_EX);
    $contents = stream_get_contents($handle);
    $entries = normalize_ranking_entries(json_decode($contents ?: '[]', true));

    $entries[] = [
        'name' => normalize_score_name($name),
        'score' => (int) $score,
        'prelim_correct' => (int) $prelim_correct,
        'final_correct' => (int) $final_correct,
        'created_at' => date('Y-m-d H:i:s'),
    ];

    $entries = array_slice(sort_ranking_entries($entries), 0, 50);

    rewind($handle);
    ftruncate($handle, 0);
    fwrite($handle, json_encode($entries, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    fflush($handle);
    flock($handle, LOCK_UN);
    fclose($handle);

    return true;
}

function build_clear_score($game)
{
    $prelim_correct = (int) ($game['prelim_result']['correct'] ?? 0);
    $final_correct = (int) ($game['correct'] ?? 0);
    $prelim_points = (int) ($game['prelim_result']['points'] ?? $prelim_correct);
    $final_points = (int) ($game['points'] ?? $final_correct);

    return [
        'score' => $prelim_points + $final_points,
        'prelim_correct' => $prelim_correct,
        'final_correct' => $final_correct,
        'prelim_points' => $prelim_points,
        'final_points' => $final_points,
    ];
}
