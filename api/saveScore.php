<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');

require_once(__DIR__ . '/../includes/ranking_functions.php');

$payload = json_decode(file_get_contents('php://input') ?: '[]', true);
if (!is_array($payload)) {
    $payload = [];
}

$name = $_POST['name'] ?? $_POST['score_name'] ?? $payload['name'] ?? '';
$clear_score = $_SESSION['clear_score'] ?? null;

if (!$clear_score) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => '登録できるスコアがありません。'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!empty($_SESSION['score_registered'])) {
    echo json_encode(['ok' => true, 'message' => 'すでに登録済みです。'], JSON_UNESCAPED_UNICODE);
    exit;
}

$saved = save_ranking_entry(
    $name,
    $clear_score['score'],
    $clear_score['prelim_correct'],
    $clear_score['final_correct']
);

if (!$saved) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'スコアの登録に失敗しました。'], JSON_UNESCAPED_UNICODE);
    exit;
}

$_SESSION['score_registered'] = true;

echo json_encode([
    'ok' => true,
    'ranking' => get_top_ranking_entries(10),
], JSON_UNESCAPED_UNICODE);
