<?php
header('Content-Type: application/json; charset=UTF-8');

require_once(__DIR__ . '/../includes/ranking_functions.php');

echo json_encode([
    'ok' => true,
    'ranking' => add_ranking_display_ranks(get_top_ranking_entries(10)),
], JSON_UNESCAPED_UNICODE);
