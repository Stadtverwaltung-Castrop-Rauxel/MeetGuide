<?php
session_start();
// Nur Anfragen erlauben, wenn eine gültige Admin-Session vorliegt
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    http_response_code(403);
    die(json_encode(['error' => 'Kein Zugriff']));
}
$file = '../config/displays.json';
$action = $_GET['action'] ?? '';

if ($action === 'list') {
    echo file_exists($file) ? file_get_contents($file) : json_encode([]);
} elseif ($action === 'add') {
    $data = json_decode(file_exists($file) ? file_get_contents($file) : '[]', true);
    $new = json_decode(file_get_contents('php://input'), true);
    // Existierende ID überschreiben oder neu hinzufügen
    $found = false;
    foreach($data as &$d) {
        if($d['display_id'] === $new['display_id']) { $d = $new; $found = true; }
    }
    if(!$found) $data[] = $new;
    file_put_contents($file, json_encode($data));
} elseif ($action === 'delete') {
    $data = json_decode(file_get_contents($file), true);
    array_splice($data, (int)$_GET['index'], 1);
    file_put_contents($file, json_encode($data));
}
?>