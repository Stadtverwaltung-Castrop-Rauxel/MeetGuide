<?php
// api/manage_accounts.php
session_start();
// Nur Anfragen erlauben, wenn eine gültige Admin-Session vorliegt
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    http_response_code(403);
    die(json_encode(['error' => 'Kein Zugriff']));
}

$file = '../config/accounts.json';

$action = $_GET['action'] ?? '';

if ($action === 'list') {
    echo file_exists($file) ? file_get_contents($file) : json_encode([]);
} 
elseif ($action === 'add') {
    $data = json_decode(file_get_contents('php://input'), true);
    $current = json_decode(file_exists($file) ? file_get_contents($file) : '[]', true);
    $current[] = $data;
    file_put_contents($file, json_encode($current));
    echo json_encode(['status' => 'success']);
} 
elseif ($action === 'delete') {
    $index = $_GET['index'] ?? null;
    if ($index !== null) {
        $current = json_decode(file_get_contents($file), true);
        array_splice($current, $index, 1); // Entfernt das Element am Index
        file_put_contents($file, json_encode(array_values($current))); // array_values stellt numerische Reihenfolge sicher
        echo json_encode(['status' => 'deleted']);
    }
}
?>