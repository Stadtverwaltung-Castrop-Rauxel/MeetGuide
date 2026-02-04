<?php
/**
 * Zentrale API für die Display-Daten
 */

header('Content-Type: application/json');

$configPath = '../config/accounts.json';
$displayPath = '../config/displays.json';
$cacheDir   = '../cache/';

if (!is_dir($cacheDir)) mkdir($cacheDir, 0777, true);

$displayId = $_GET['id'] ?? null;
if (!$displayId) exit(json_encode(['error' => 'Keine ID']));

$allAccounts = json_decode(file_get_contents($configPath), true);
$allDisplays = json_decode(file_get_contents($displayPath), true);

// Display finden
$currentDisplay = null;
foreach ($allDisplays as $disp) {
    if ($disp['display_id'] === $displayId) { $currentDisplay = $disp; break; }
}
if (!$currentDisplay) exit(json_encode(['error' => 'Display nicht gefunden']));

$allMeetings = [];
$assignedIds = $currentDisplay['assigned_calendars'] ?? [];

foreach ($assignedIds as $id) {
    if (!isset($allAccounts[$id])) continue;
    $acc = $allAccounts[$id];
    
    // Cache-Key basierend auf der URL generieren
    $cacheFile = $cacheDir . 'cache_' . md5($acc['url']) . '.json';
    $cacheTime = 3600; // 1 Stunde

    // Prüfen, ob Cache existiert und noch gültig ist
    if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < $cacheTime)) {
        $meetings = json_decode(file_get_contents($cacheFile), true);
    } else {
        if ($acc['type'] === 'exchange') {
            $meetings = fetchExchangeMeetings($acc);
        } else {
            $meetings = fetchCalDavMeetings($acc);
        }
        file_put_contents($cacheFile, json_encode($meetings));
    }
    
    $allMeetings = array_merge($allMeetings, $meetings);
}


// ZENTRALE SORTIERUNG: Sortiert ALLES chronologisch
usort($allMeetings, function($a, $b) {
    return strtotime($a['start']) - strtotime($b['start']);
});

// Erst JETZT die sortierten Daten ausgeben
header('Cache-Control: no-store, max-age=0');
echo json_encode([
    'name'    => $currentDisplay['name'],
    'layout'  => $currentDisplay['layout'],
    'theme'   => $currentDisplay['theme'],
    'wayfinding' => $currentDisplay['wayfinding'] ?? [], // NEU
    'meetings'=> $allMeetings,
    'limit_with_wayfinding'=> $currentDisplay['limit_with_wayfinding'],
    'limit_no_wayfinding'=> $currentDisplay['limit_no_wayfinding'],
], JSON_UNESCAPED_UNICODE);
// --- Hilfsfunktionen ---

function fetchExchangeMeetings($acc) {
    return [
        [
            'title' => 'Exchange: ' . $acc['roomName'],
            'start' => date('Y-m-d H:i:s', strtotime('+1 hour')),
            'room' => $acc['roomName'],
            'direction' => $acc['direction'] ?? '',
            'infoText' => $acc['infoText'] ?? ''
        ]
    ];
}
function fetchCalDavMeetings($acc) {
    $url = $acc['url'];
    $maxDaysInFuture = 60;
    $maxTotalMeetings = 50;

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $icalData = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) return [];

    // 1. & 2. Cleaning 
    $icalData = preg_replace("/\r\n?/", "\n", $icalData);
    $icalData = preg_replace("/\n[ \t]/", '', $icalData);

    $eventBlocks = explode("BEGIN:VEVENT", $icalData);
    array_shift($eventBlocks);

    $meetings = [];
    $today = strtotime('today');
    $limitDate = strtotime("+$maxDaysInFuture days", $today);

    foreach ($eventBlocks as $block) {
        if (count($meetings) >= $maxTotalMeetings) break;

        // SUMMARY (Titel)
        list($titleRaw) = icalProp($block, 'SUMMARY');
        
        $title = $titleRaw ? trim($titleRaw) : 'Unbekannt';
        $title = str_replace(['\\n','\\,','\\;','\\\\'], ["\n", ',', ';', '\\'], $title);

        // STARTZEIT 
        list($startValue, $startParams) = icalProp($block, 'DTSTART');
        $startDate = formatIcalDate($startValue, $startParams);

        // ENDZEIT
        list($endValue, $endParams) = icalProp($block, 'DTEND');
        $endDate = formatIcalDate($endValue, $endParams);
        
        if (!$endDate && $startDate) {
            $endDate = date('Y-m-d H:i:s', strtotime($startDate . ' +1 hour'));
        }

        if ($startDate) {
            $eventTimestamp = strtotime($startDate);
            if ($eventTimestamp >= $today && $eventTimestamp <= $limitDate) {
                $meetings[] = [
                    'title' => str_replace(['\\,', '\\;'], [',', ';'], $title),
                    'start' => $startDate,
                    'end'   => $endDate,
                    'room'  => $acc['roomName'],
                    'direction' => $acc['direction'] ?? '',
                    'infoText'  => $acc['infoText'] ?? ''
                ];
            }
        }
    }
    return $meetings;
}

function formatIcalDate($value, $params = []) {
    // VALUE=DATE -> ganztägig, lokale Mitternacht
    if (isset($params['VALUE']) && strtoupper($params['VALUE']) === 'DATE' && preg_match('/^\d{8}$/', $value)) {
        $dt = DateTime::createFromFormat('Ymd H:i:s', $value.' 00:00:00', new DateTimeZone('Europe/Berlin'));
        return $dt ? $dt->format('Y-m-d H:i:s') : false;
    }

    // Zeitzone bestimmen
    if (!empty($params['TZID'])) {
        $tz = new DateTimeZone(mapWindowsTz($params['TZID']));
        $v = rtrim($value, 'Z'); // falls doch ein Z dabei ist
        $dt = DateTime::createFromFormat('Ymd\THis', $v, $tz);
    } elseif (substr($value, -1) === 'Z') {
        $v = rtrim($value, 'Z');
        $dt = DateTime::createFromFormat('Ymd\THis', $v, new DateTimeZone('UTC'));
    } else {
        // Lokale Zeitzone als Fallback
        $dt = DateTime::createFromFormat('Ymd\THis', $value, new DateTimeZone('Europe/Berlin'));
    }

    if (!$dt) {
        // Fallback (sollte selten nötig sein)
        $ts = strtotime($value);
        return $ts ? date('Y-m-d H:i:s', $ts) : false;
    }

    // Ausgabe in lokaler Zeit
    $dt->setTimezone(new DateTimeZone('Europe/Berlin'));
    return $dt->format('Y-m-d H:i:s');
}

function icalProp($block, $prop) {
    // Matcht z. B. "DTSTART;TZID=W. Europe Standard Time:20260522T140000"
    if (!preg_match('/^' . preg_quote($prop, '/') . '(;[^:]+)?:([^\r\n]+)/mi', $block, $m)) {
        return [null, []];
    }
    $params = [];
    if (!empty($m[1])) {
        foreach (explode(';', ltrim($m[1], ';')) as $p) {
            $parts = explode('=', $p, 2);
            if (count($parts) === 2) {
                $params[strtoupper(trim($parts[0]))] = trim($parts[1]);
            }
        }
    }
    return [trim($m[2]), $params];
}

function mapWindowsTz($tzid) {
    static $map = [
        'W. Europe Standard Time' => 'Europe/Berlin',
        // ggf. erweitern
    ];
    return $map[$tzid] ?? 'Europe/Berlin';
}