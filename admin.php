<?php include 'auth_check.php'; ?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Display und Kalender Admin-Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .card { margin-bottom: 2rem; box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075); }
        .calendar-badge { margin-right: 5px; }
    </style>
</head>
<body>

<nav class="navbar navbar-dark bg-dark mb-4">
    <div class="container">
        <span class="navbar-brand">Kalender und Display Management</span>
    </div>
</nav>

<div class="container">
    <div class="row">
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header bg-primary text-white">1. Kalender-Quellen (Accounts)</div>
                <div class="card-body">
                    <form id="accountForm" class="mb-4">
                        <div class="mb-2">
                            <label class="form-label">Raumname</label>
                            <input type="text" id="acc_name" class="form-control" placeholder="z.B. Konferenzraum A" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Typ</label>
                            <select id="acc_type" class="form-select">
                              <!--  Vllt. mal ergänzen... <option value="exchange">Exchange Online (M365)</option>-->
                                <option value="caldav">CalDAV (Nextcloud/etc.)</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">URL</label>
                            <input type="text" id="acc_url" class="form-control" placeholder="Server-URL https://...." required>
                        </div>
						<div class="mb-2">
                            <label class="form-label">Richtung (Wayfinding)</label>
                            <select id="acc_direction" class="form-select">
                                <option value="">Keine Angabe</option>
                                <option value="up">⬆️ Geradeaus</option>
                                <option value="right">➡️ Rechts</option>
                                <option value="left">⬅️ Links</option>
                                <option value="down">⬇️ Unten</option>
                            </select>
                        </div>
                                            
						<div class="mb-2">
                            <label class="form-label">Zusatz-Info (erscheint unter dem Raum)</label>
                            <input type="text" id="acc_info" class="form-control" placeholder="z.B. Ansprechpartner oder Hinweis">
                        </div>
                        <button type="submit" class="btn btn-success w-100">Account hinzufügen</button>
                    </form>
                    <hr>
                    <h5>Aktive Kalender</h5>
                    <ul class="list-group" id="accountList"></ul>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card">
                <div class="card-header bg-dark text-white">2. Display-Layouts & Zuordnung</div>
                <div class="card-body">
                    <form id="displayForm" class="mb-4">
                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <label class="form-label">Display-ID (für URL)</label>
                                <input type="text" id="disp_id" class="form-control" placeholder="z.B. foyer-1" required>
                            </div>
                            <div class="col-md-6 mb-2">
                                <label class="form-label">Anzeigename</label>
                                <input type="text" id="disp_name" class="form-control" placeholder="z.B. Monitor Eingang">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Layout wählen</label>
                            <select id="disp_layout" class="form-select">
                                <option value="list">Liste (Klassisch)</option>
                                <option value="grid">Grid (Kacheln)</option>
                                <option value="single">Einzelscreen (Fokus)</option>
                            </select>
                        </div>
						<div class="mb-2">
                        <label>Design-Modus</label>
                        <select id="disp_theme" class="form-select">
                            <option value="dark">Dark Mode (Standard)</option>
                            <option value="light">Light Mode</option>
                        </select>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label"><strong>Limit (mit Wayfinding)</strong></label>
                                <select id="disp_limit_wf" class="form-select">
                                    <option value="2">2 Termine</option>
                                    <option value="3" selected>3 Termine (Standard)</option>
                                    <option value="4">4 Termine</option>
                                    <option value="5">5 Termine</option>
                                </select>
                                <small class="text-muted">Anzeige, wenn Etagenliste sichtbar ist.</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><strong>Limit (ohne Wayfinding)</strong></label>
                                <select id="disp_limit_empty" class="form-select">
                                    <option value="4">4 Termine</option>
                                    <option value="5">5 Termine</option>
                                    <option value="6" selected>6 Termine (Standard)</option>
                                    <option value="8">8 Termine</option>
                                    <option value="10">10 Termine</option>
                                </select>
                                <small class="text-muted">Anzeige bei "Empty-Meetings" Modus.</small>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label"><strong>Statische Wayfinding-Infos</strong> (Erscheint unten)</label>
                            <div id="wayfindingInputs">
                                <div class="row g-2 mb-2">
                                    <div class="col-3"><input type="text" class="form-control wf-floor" placeholder="EG" value="EG"></div>
                                    <div class="col-9"><input type="text" class="form-control wf-dest" placeholder="Bürgerbüro, Standesamt..."></div>
                                </div>
                                <div class="row g-2 mb-2">
                                    <div class="col-3"><input type="text" class="form-control wf-floor" placeholder="1. OG" value="1. OG"></div>
                                    <div class="col-9"><input type="text" class="form-control wf-dest" placeholder="Finanzen, Personal..."></div>
                                </div>
                                <div class="row g-2 mb-2">
                                    <div class="col-3"><input type="text" class="form-control wf-floor" placeholder="2. OG" value="2. OG"></div>
                                    <div class="col-9"><input type="text" class="form-control wf-dest" placeholder="IT, Bauamt..."></div>
                                </div>
                                <div class="row g-2 mb-2">
                                    <div class="col-3"><input type="text" class="form-control wf-floor" placeholder="2. OG" value="3. OG"></div>
                                    <div class="col-9"><input type="text" class="form-control wf-dest" placeholder="IT, Bauamt..."></div>
                                </div>

                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label d-block">Zugeordnete Kalender:</label>
                            <div id="calendarCheckboxes" class="border p-2 rounded bg-light" style="max-height: 150px; overflow-y: auto;">
                                </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Display erstellen / aktualisieren</button>
                    </form>
                    <hr>
                    <h5>Aktive Displays</h5>
                    <div id="displayList" class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Layout</th>
                                    <th>Kalender</th>
                                    <th>Aktion</th>
                                </tr>
                            </thead>
                            <tbody id="displayTableBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Globale Daten
let accounts = [];
let displays = [];

// Initiales Laden
async function loadData() {
    const resAcc = await fetch('api/manage_accounts.php?action=list');
    accounts = await resAcc.json();
    
    const resDisp = await fetch('api/manage_displays.php?action=list');
    displays = await resDisp.json();

    renderAccounts();
    renderDisplays();
}

// Accounts rendern
function renderAccounts() {
    const list = document.getElementById('accountList');
    const checkboxes = document.getElementById('calendarCheckboxes');
    
    list.innerHTML = accounts.map((acc, i) => `
        <li class="list-group-item d-flex justify-content-between align-items-center">
            <div>
                <strong>${acc.roomName}</strong><br>
                <small class="text-muted">${acc.type}</small>
            </div>
            <button class="btn btn-outline-danger btn-sm" onclick="deleteAccount(${i})">X</button>
        </li>
    `).join('');

    checkboxes.innerHTML = accounts.map((acc, i) => `
        <div class="form-check">
            <input class="form-check-input" type="checkbox" value="${i}" id="chk${i}">
            <label class="form-check-label" for="chk${i}">${acc.roomName}</label>
        </div>
    `).join('');
}

// Displays rendern
function renderDisplays() {
    const tableBody = document.getElementById('displayTableBody');
    tableBody.innerHTML = displays.map((disp, i) => {
        const calNames = disp.assigned_calendars.map(idx => accounts[idx] ? accounts[idx].roomName : '?').join(', ');
        return `
        <tr>
            <td><code>${disp.display_id}</code></td>
            <td>${disp.layout}</td>
            <td><small>${calNames}</small></td>
            <td>
                <a href="display.php?id=${disp.display_id}" target="_blank" class="btn btn-info btn-sm">Öffnen</a>
                <button class="btn btn-danger btn-sm" onclick="deleteDisplay(${i})">X</button>
            </td>
        </tr>`;
    }).join('');
}

// Speichern von Accounts
document.getElementById('accountForm').onsubmit = async (e) => {
    e.preventDefault();
    
    // Sicherstellen, dass die Elemente existieren, bevor .value gerufen wird
    const nameEl = document.getElementById('acc_name');
    const typeEl = document.getElementById('acc_type');
    const urlEl = document.getElementById('acc_url');
    const dirEl = document.getElementById('acc_direction');
    const infoEl = document.getElementById('acc_info');

    const newAcc = {
        roomName: nameEl ? nameEl.value : '',
        type: typeEl ? typeEl.value : 'caldav',
        url: urlEl ? urlEl.value : '',
        direction: dirEl ? dirEl.value : '',
        infoText: infoEl ? infoEl.value : ''
    };

    await fetch('api/manage_accounts.php?action=add', { 
        method: 'POST', 
        body: JSON.stringify(newAcc) 
    });
    
    e.target.reset();
    loadData();
};

// Formular-Verarbeitung für neue Displays
document.getElementById('displayForm').onsubmit = async (e) => {
    e.preventDefault();
    const selectedCals = Array.from(document.querySelectorAll('#calendarCheckboxes input:checked')).map(cb => parseInt(cb.value));
    
    // Wayfinding Daten sammeln
    const floors = Array.from(document.querySelectorAll('.wf-floor')).map(el => el.value);
    const dests = Array.from(document.querySelectorAll('.wf-dest')).map(el => el.value);
    const wayfindingData = floors.map((f, i) => ({ floor: f, text: dests[i] }));

    const newDisp = {
        display_id: document.getElementById('disp_id').value,
        name: document.getElementById('disp_name').value,
        layout: document.getElementById('disp_layout').value,
        theme: document.getElementById('disp_theme').value,
        assigned_calendars: selectedCals,
        wayfinding: wayfindingData, 
        limit_with_wayfinding: parseInt(document.getElementById('disp_limit_wf').value),
        limit_no_wayfinding: parseInt(document.getElementById('disp_limit_empty').value)
    };

    await fetch('api/manage_displays.php?action=add', { 
        method: 'POST', 
        body: JSON.stringify(newDisp) 
    });
    
    e.target.reset();
    loadData();
};

async function deleteAccount(index) {
    if(confirm('Account wirklich löschen?')) {
        await fetch(`api/manage_accounts.php?action=delete&index=${index}`);
        loadData();
    }
}

async function deleteDisplay(index) {
    if(confirm('Display wirklich löschen?')) {
        await fetch(`api/manage_displays.php?action=delete&index=${index}`);
        loadData();
    }
}

loadData();
</script>
</body>
</html>