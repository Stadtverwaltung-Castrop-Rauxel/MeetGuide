<?php
$displayId = $_GET['id'] ?? 'default';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Display Dashboard - <?php echo htmlspecialchars($displayId); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700;900&display=swap" rel="stylesheet">
    
	<link href="style.css" rel="stylesheet" >
</head>
<body class="layout-grid">

<div id="app">
    <div class="header">
        <div class="header-left">
            <img src="logo.png" alt="Logo" class="logo">
            <h1 id="displayName">Lade...</h1>
        </div>
        <div id="clock">00:00</div>
    </div>
    <div id="meetingContainer"></div>
    <div id="staticWayfinding">
    <div class="wayfinding-header">🏢 Orientierung & Service</div>
    <div class="wayfinding-grid">
        <div class="wayfinding-item">
            <span class="floor"></span>
            <span class="dest"></span>
        </div>
        <div class="wayfinding-item">
            <span class="floor"></span>
            <span class="dest"></span>
        </div>
        <div class="wayfinding-item">
            <span class="floor"></span>
            <span class="dest"></span>
        </div>
    </div>
</div>
</div>

<script>
    const displayId = "<?php echo $displayId; ?>";
    let validWayfinding = [];
    let currentMeetingPage = 0;
    let rotationInterval = null;
    let configLimits = {
        withWayfinding: 3, // Fallback
        noWayfinding: 6    // Fallback
    };

    function updateClock() {
        const now = new Date();
        document.getElementById('clock').innerText = now.toLocaleTimeString('de-DE', {hour: '2-digit', minute:'2-digit'});
    }

    setInterval(updateClock, 1000);
    updateClock();
    
    async function fetchData() {
    try {
        if (displayId) {
            const response = await fetch(`api/get_display_data.php?id=${displayId}`);
            const data = await response.json();

            const wfBlock = document.getElementById('staticWayfinding');
            const wfGrid = document.querySelector('.wayfinding-grid');

            // 1. Wayfinding Daten filtern
            validWayfinding = data.wayfinding ? data.wayfinding.filter(item => item.text.trim() !== "") : [];
            
            // 2. Prüfen, ob aktive Termine vorhanden sind
            const now = new Date();
            const hasActiveMeetings = data.meetings && data.meetings.some(m => new Date(m.end) > now);
        
            // Limits aus der Datenbank-Antwort übernehmen
            if (data.limit_with_wayfinding) configLimits.withWayfinding = parseInt(data.limit_with_wayfinding);
            if (data.limit_no_wayfinding) configLimits.noWayfinding = parseInt(data.limit_no_wayfinding);

            if (validWayfinding.length > 0) {
                wfBlock.style.display = 'block';
                
                if (!hasActiveMeetings) {
                    // LARGE MODUS (Keine Termine)
                    wfBlock.classList.add('empty-meetings');
                    wfGrid.style.display = 'flex';
                    wfGrid.style.flexDirection = 'column-reverse';
                    wfGrid.style.gridTemplateColumns = 'none'; // Wichtig: Hier lassen
                } else {
                    // NORMAL MODUS (Mit Terminen)
                    wfBlock.classList.remove('empty-meetings');
                    wfGrid.style.display = 'grid';
                    wfGrid.style.flexDirection = 'row'; // Zurücksetzen
                    wfGrid.style.gridTemplateColumns = `repeat(${validWayfinding.length}, 1fr)`;
                }

                // NUR rendern, wenn nicht schon durch das Grid oben überschrieben
                wfGrid.innerHTML = validWayfinding.map(item => `
                    <div class="wayfinding-item">
                        <span class="floor">${item.floor}</span>
                        <span class="dest">${item.text}</span>
                    </div>
                `).join('');
            } else {
                wfBlock.style.display = 'none';
            }

            // Theme & Layout Klassen setzen
            document.body.classList.remove('theme-light', 'theme-dark', 'layout-grid', 'layout-list', 'layout-single');
            document.body.classList.add('layout-' + (data.layout || 'grid'));
            if (data.theme === 'light') document.body.classList.add('theme-light');
            else document.body.classList.add('theme-dark');

            document.getElementById('displayName').innerText = data?.name || 'Unbenanntes Display';

            // Termine rendern (Setzt auch den "Keine Termine"-Text)
            renderMeetings(data.meetings);
        }
    } catch (e) {
        console.error("Fetch Error:", e);
    }
}


function renderMeetings(meetings) {
    const container = document.getElementById('meetingContainer');
    const now = new Date();
    const todayTimestamp = new Date().setHours(0, 0, 0, 0);
    const tomorrowTimestamp = todayTimestamp + 86400000;
    const body = document.body; 
    if (rotationInterval) clearInterval(rotationInterval);

    if (!meetings || meetings.length === 0) {
        container.innerHTML = '<div class="no-meetings">Keine Termine gefunden</div>';
        return;
    }

    // 1. Filtern & Sortieren
    const activeMeetings = meetings.filter(m => new Date(m.end) > now);
    activeMeetings.sort((a, b) => new Date(a.start) - new Date(b.start));
    
    if (activeMeetings.length === 0) {
        // FALL: KEINE TERMINE
        body.classList.remove('has-meetings');
        body.classList.add('no-active-meetings');
        container.innerHTML = '<div class="no-meetings">Aktuell keine anstehenden Termine</div>';
        if (rotationInterval) clearInterval(rotationInterval);
        return;
    } else {
        // FALL: TERMINE VORHANDEN
        body.classList.add('has-meetings');
        body.classList.remove('no-active-meetings');
    }
    
    // 2. Kapazität dynamisch festlegen
    let displayLimit;
    
    if (validWayfinding.length > 0) {    
        // Wenn Wayfinding aktiv ist, nimm das kleine Limit
        displayLimit = configLimits.withWayfinding;
    } else {
        // Wenn kein Wayfinding da ist (Empty-Mode), nimm das große Limit
        displayLimit = configLimits.noWayfinding;
    }

    // 3. Aufteilung in Heute und Zukunft
    const meetingsToday = activeMeetings.filter(m => new Date(m.start) < tomorrowTimestamp);
    const meetingsFuture = activeMeetings.filter(m => new Date(m.start) >= tomorrowTimestamp);

    let pages = [];

    if (meetingsToday.length > displayLimit) {
        // FALL A: Heute sind es so viele, dass wir NUR zwischen Heute-Terminen wechseln
        for (let i = 0; i < meetingsToday.length; i += displayLimit) {
            pages.push(meetingsToday.slice(i, i + displayLimit));
        }
    } else {
        // FALL B: Heute passt auf eine Seite. Wir füllen den Rest mit Zukunft auf.
        const firstPage = [...meetingsToday];
        const remainingSlots = displayLimit - meetingsToday.length;
        
        if (remainingSlots > 0 && meetingsFuture.length > 0) {
            firstPage.push(...meetingsFuture.slice(0, remainingSlots));
        }
        pages.push(firstPage);
        // Hinweis: In diesem Fall gibt es nur eine Seite (pages.length = 1), kein Wechsel.
    }

    // 4. Rendering Funktion 
    const showPage = (pageIndex) => {
        const currentSelection = pages[pageIndex];
        const directionIcons = { 'up': '⬆️', 'right': '➡️', 'left': '⬅️', 'down': '⬇️' };
        const isSingle = document.body.classList.contains('layout-single');

        container.innerHTML = currentSelection.map((m, index) => {
            const start = new Date(m.start);
            const end = new Date(m.end);
            const isToday = new Date(start).setHours(0, 0, 0, 0) === todayTimestamp;
            
            const dir = m.direction || '';
            const info = m.infoText || '';
            const dirIcon = dir ? `<span class="direction-arrow">${directionIcons[dir] || ''}</span>` : '';
            const infoHtml = info ? `<div class="info-text">${info}</div>` : '';

            let status = 'future';
            let statusText = 'Geplant';
            const diffMs = start - now;
            let dateStr = isToday ? 'Heute' : start.toLocaleDateString('de-DE', {weekday: 'short', day: '2-digit', month: '2-digit'});
            if (now >= start && now < end) {
                status = 'running'; 
                statusText = 'Belegt';
                dateStr = 'Jetzt'; // Überschreibt "Heute" mit "Jetzt", wenn der Termin läuft
            } else if (now < start && diffMs < 900000) {
                status = 'starting-soon'; 
                statusText = 'In Kürze';
            } else if (!isToday) {
                status = 'other-day'; 
                statusText = 'Demnächst';
            }

            const timeStr = start.toLocaleTimeString('de-DE', {hour: '2-digit', minute:'2-digit'});

            // Endzeit formatieren
            const endTimeStr = end.toLocaleTimeString('de-DE', {hour: '2-digit', minute:'2-digit'});
            
            const roomContent = (isSingle && index === 0) 
                ? `<div class="room" style="">${infoHtml}</div>` 
                : `<div class="room">${dirIcon}${m.room || 'Raum'}</div>${infoHtml}`;

            return `
                <div class="card ${status}" style="animation: fadeIn 0.5s ease forwards">
                    <div class="status-badge">${statusText}</div>
                    <div class="time-box">
                         
                         <span class="date-label ${status === 'running' ? 'heute' : (isToday ? 'heute' : '')}">${dateStr}</span>
                    <span class="time">
                    ${status === 'running' ? `<span class="time-till">bis ${endTimeStr}</span>` : `${timeStr}`}
            </span>
                    </div>
                    <div class="title">${m.title || 'Kein Titel'}</div>
                    <div class="room-box">
                        ${roomContent}
                    </div>
                </div>`;
        }).join('');

        if (pages.length > 1) {
            container.innerHTML += `
                <div class="terminpager" style="">
                    Dringende Termine: Seite ${pageIndex + 1} von ${pages.length}
                </div>`;
        }
    };

    // Initialanzeige
    currentMeetingPage = 0;
    showPage(currentMeetingPage);

    // Rotation NUR wenn wir mehr als eine Seite haben (also nur in Fall A)
    if (pages.length > 1) {
        rotationInterval = setInterval(() => {
            currentMeetingPage = (currentMeetingPage + 1) % pages.length;
            showPage(currentMeetingPage);
        }, 8000);
    }
}
    // Lädt die Seite alle 60 Minuten neu
    setTimeout(function() {
        location.reload();
    }, 60 * 60 * 1000);

    setInterval(fetchData, 30000);
    fetchData();
</script>
</body>
</html>