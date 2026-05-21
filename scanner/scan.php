<?php
// scanner/scan.php
require_once '../config/database.php';

$activeSessions = [];
try {
    $s = $pdo->query("SELECT id, session_name, attendance_type FROM attendance_sessions WHERE status = 'ACTIVE' ORDER BY created_at DESC");
    $activeSessions = $s->fetchAll();
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Scanner</title>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&family=JetBrains+Mono:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg:         #09090b;
            --surface:    #111114;
            --card:       #18181b;
            --border:     rgba(255,255,255,0.07);
            --border-hi:  rgba(255,255,255,0.14);
            --accent:     #f97316;
            --accent-dim: rgba(249,115,22,0.12);
            --accent-glow:rgba(249,115,22,0.35);
            --green:      #22c55e;
            --green-dim:  rgba(34,197,94,0.12);
            --red:        #ef4444;
            --red-dim:    rgba(239,68,68,0.12);
            --yellow:     #eab308;
            --yellow-dim: rgba(234,179,8,0.12);
            --text:       #fafafa;
            --text-2:     #a1a1aa;
            --text-3:     #52525b;
            --radius:     16px;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            background: var(--bg);
            color: var(--text);
            font-family: 'Space Grotesk', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
            overflow-x: hidden;
        }

        /* subtle dot grid */
        body::before {
            content: '';
            position: fixed; inset: 0;
            background-image: radial-gradient(rgba(255,255,255,0.035) 1px, transparent 1px);
            background-size: 28px 28px;
            pointer-events: none;
            z-index: 0;
        }

        /* warm glow blob */
        body::after {
            content: '';
            position: fixed;
            width: 600px; height: 600px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(249,115,22,0.06) 0%, transparent 70%);
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            pointer-events: none;
            z-index: 0;
        }

        .wrap {
            position: relative; z-index: 1;
            width: 100%; max-width: 480px;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        /* ── TOP BAR ── */
        .top-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .brand-icon {
            width: 36px; height: 36px;
            border-radius: 10px;
            background: var(--accent);
            display: flex; align-items: center; justify-content: center;
            font-size: 16px;
        }

        .brand-name {
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text);
            letter-spacing: -0.01em;
        }

        .brand-sub {
            font-size: 0.7rem;
            color: var(--text-3);
            font-family: 'JetBrains Mono', monospace;
        }

        .live-pill {
            display: flex;
            align-items: center;
            gap: 6px;
            background: var(--green-dim);
            border: 1px solid rgba(34,197,94,0.2);
            border-radius: 999px;
            padding: 5px 12px;
            font-size: 0.7rem;
            font-weight: 600;
            color: var(--green);
            font-family: 'JetBrains Mono', monospace;
            letter-spacing: 0.05em;
        }

        .live-dot {
            width: 6px; height: 6px;
            border-radius: 50%;
            background: var(--green);
            animation: blink 2s ease-in-out infinite;
        }

        @keyframes blink {
            0%,100% { opacity: 1; }
            50%      { opacity: 0.2; }
        }

        /* ── CLOCK CARD ── */
        .clock-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 28px 24px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .clock-card::before {
            content: '';
            position: absolute;
            top: 0; left: 50%; transform: translateX(-50%);
            width: 40%; height: 1px;
            background: linear-gradient(90deg, transparent, var(--accent), transparent);
        }

        #clock {
            font-family: 'JetBrains Mono', monospace;
            font-size: clamp(2.8rem, 10vw, 4rem);
            font-weight: 700;
            color: var(--text);
            letter-spacing: -0.02em;
            line-height: 1;
        }

        #clock .colon {
            color: var(--accent);
            animation: colonBlink 1s step-end infinite;
        }

        @keyframes colonBlink {
            0%,100% { opacity: 1; }
            50%      { opacity: 0.25; }
        }

        #date-display {
            font-size: 0.78rem;
            color: var(--text-3);
            font-family: 'JetBrains Mono', monospace;
            margin-top: 8px;
            letter-spacing: 0.04em;
        }

        /* ── SESSION PILL ── */
        .session-row {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 10px 16px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.8rem;
        }

        .session-dot {
            width: 7px; height: 7px;
            border-radius: 50%;
            background: var(--accent);
            flex-shrink: 0;
        }

        .session-label {
            color: var(--text-2);
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.72rem;
        }

        .session-name {
            color: var(--text);
            font-weight: 600;
            margin-left: auto;
        }

        .session-type {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.7rem;
            padding: 2px 8px;
            border-radius: 4px;
            background: var(--accent-dim);
            color: var(--accent);
            font-weight: 600;
        }

        /* ── SCANNER CARD ── */
        .scanner-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 24px;
        }

        .scan-label-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 14px;
        }

        .scan-label-text {
            font-size: 0.72rem;
            font-family: 'JetBrains Mono', monospace;
            color: var(--text-3);
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .scan-indicator {
            width: 8px; height: 8px;
            border-radius: 50%;
            background: var(--text-3);
            transition: background 0.3s, box-shadow 0.3s;
        }

        .scan-indicator.active {
            background: var(--accent);
            box-shadow: 0 0 10px var(--accent-glow);
        }

        #rfid_input {
            width: 100%;
            background: var(--card);
            border: 1.5px solid var(--border-hi);
            border-radius: 12px;
            color: var(--text);
            font-family: 'JetBrains Mono', monospace;
            font-size: 1.1rem;
            font-weight: 500;
            letter-spacing: 0.15em;
            padding: 16px 20px;
            text-align: center;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
            caret-color: var(--accent);
        }

        #rfid_input:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px var(--accent-dim);
        }

        #rfid_input::placeholder {
            color: var(--text-3);
            font-size: 0.85rem;
            letter-spacing: 0.05em;
        }

        /* progress track */
        .progress-track {
            height: 2px;
            background: var(--border);
            border-radius: 2px;
            margin-top: 14px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            width: 0;
            background: linear-gradient(90deg, var(--accent), #fb923c);
            border-radius: 2px;
            transition: width 0.2s;
        }

        .progress-fill.scanning {
            width: 100%;
            animation: sweep 0.9s ease-in-out infinite;
        }

        @keyframes sweep {
            0%   { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }

        /* status */
        #status-msg {
            margin-top: 14px;
            text-align: center;
            font-size: 0.82rem;
            font-family: 'JetBrains Mono', monospace;
            letter-spacing: 0.03em;
            color: var(--text-3);
            min-height: 22px;
            transition: color 0.25s;
        }

        #status-msg.ok   { color: var(--green); }
        #status-msg.fail { color: var(--red); }
        #status-msg.warn { color: var(--yellow); }

        /* ── RESULT CARD ── */
        #card-wrap {
            display: none;
            animation: riseIn 0.35s cubic-bezier(0.34,1.56,0.64,1) both;
        }

        @keyframes riseIn {
            from { opacity:0; transform:translateY(16px) scale(0.97); }
            to   { opacity:1; transform:translateY(0) scale(1); }
        }

        .result-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            overflow: hidden;
        }

        /* coloured top line */
        .result-card.success { border-top: 2px solid var(--green); }
        .result-card.error   { border-top: 2px solid var(--red); }
        .result-card.warning { border-top: 2px solid var(--yellow); }

        .rc-header {
            padding: 14px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--border);
        }

        .rc-header-left {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .rc-status-icon {
            width: 32px; height: 32px;
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-size: 14px;
        }

        .rc-status-icon.success { background: var(--green-dim); }
        .rc-status-icon.error   { background: var(--red-dim); }
        .rc-status-icon.warning { background: var(--yellow-dim); }

        .rc-status-text {
            font-size: 0.8rem;
            font-weight: 600;
            font-family: 'JetBrains Mono', monospace;
            letter-spacing: 0.04em;
        }

        .rc-status-text.success { color: var(--green); }
        .rc-status-text.error   { color: var(--red); }
        .rc-status-text.warning { color: var(--yellow); }

        .rc-time {
            font-size: 0.72rem;
            font-family: 'JetBrains Mono', monospace;
            color: var(--text-3);
        }

        /* student info section */
        .rc-body {
            padding: 20px;
            display: flex;
            gap: 16px;
            align-items: flex-start;
        }

        .rc-photo {
            width: 72px; height: 86px;
            border-radius: 10px;
            object-fit: cover;
            border: 1px solid var(--border-hi);
            flex-shrink: 0;
            background: var(--card);
        }

        .rc-photo-placeholder {
            width: 72px; height: 86px;
            border-radius: 10px;
            border: 1px solid var(--border);
            background: var(--card);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.8rem;
            flex-shrink: 0;
            color: var(--text-3);
        }

        .rc-info { flex: 1; min-width: 0; }

        .rc-name {
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--text);
            letter-spacing: -0.01em;
            margin-bottom: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .rc-id {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.72rem;
            color: var(--accent);
            margin-bottom: 12px;
            letter-spacing: 0.05em;
        }

        .rc-fields {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }

        .rc-field label {
            font-size: 0.63rem;
            font-family: 'JetBrains Mono', monospace;
            color: var(--text-3);
            text-transform: uppercase;
            letter-spacing: 0.06em;
            display: block;
            margin-bottom: 2px;
        }

        .rc-field span {
            font-size: 0.8rem;
            color: var(--text-2);
            font-weight: 500;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            display: block;
        }

        /* session type tag in card */
        .rc-type-tag {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.68rem;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 5px;
            letter-spacing: 0.04em;
        }

        .rc-type-tag.IN  { background: var(--green-dim); color: var(--green); }
        .rc-type-tag.OUT { background: var(--red-dim);   color: var(--red); }
        .rc-type-tag.default { background: var(--accent-dim); color: var(--accent); }

        /* ── REFOCUS ── */
        .refocus {
            text-align: center;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.7rem;
            color: var(--text-3);
            cursor: pointer;
            letter-spacing: 0.04em;
            transition: color 0.15s;
            padding: 4px 0;
        }

        .refocus:hover { color: var(--accent); }
    </style>
</head>
<body>
<div class="wrap">

    <!-- Top bar -->
    <div class="top-bar">
        <div class="brand">
            <div class="brand-icon">📡</div>
            <div>
                <div class="brand-name">RFID System</div>
                <div class="brand-sub">Attendance Terminal</div>
            </div>
        </div>
        <div class="live-pill">
            <div class="live-dot"></div>
            LIVE
        </div>
    </div>

    <!-- Clock -->
    <div class="clock-card">
        <div id="clock"><span id="h">00</span><span class="colon">:</span><span id="m">00</span><span class="colon">:</span><span id="s">00</span></div>
        <div id="date-display">--</div>
    </div>

    <!-- Session -->
    <div class="session-row">
        <div class="session-dot"></div>
        <div class="session-label">SESSION</div>
        <?php if (!empty($activeSessions)): ?>
            <div class="session-name"><?= htmlspecialchars($activeSessions[0]['session_name']) ?></div>
            <div class="session-type"><?= htmlspecialchars($activeSessions[0]['attendance_type']) ?></div>
        <?php else: ?>
            <div class="session-name" style="color:var(--text-3)">No active session</div>
        <?php endif; ?>
    </div>

    <!-- Scanner -->
    <div class="scanner-card">
        <div class="scan-label-row">
            <div class="scan-label-text">RFID / QR Input</div>
            <div class="scan-indicator" id="scan-indicator"></div>
        </div>
        <input
            type="text"
            id="rfid_input"
            placeholder="Waiting for card scan..."
            autocomplete="off"
            autofocus
        >
        <div class="progress-track">
            <div class="progress-fill" id="progress-fill"></div>
        </div>
        <div id="status-msg">Ready — place card on reader</div>
    </div>

    <!-- Result card -->
    <div id="card-wrap">
        <div class="result-card" id="result-card">
            <div class="rc-header">
                <div class="rc-header-left">
                    <div class="rc-status-icon" id="rc-icon">—</div>
                    <div class="rc-status-text" id="rc-status-text">—</div>
                </div>
                <div style="display:flex;align-items:center;gap:8px;">
                    <div class="rc-type-tag default" id="rc-type-tag">—</div>
                    <div class="rc-time" id="rc-time">—</div>
                </div>
            </div>
            <div class="rc-body">
                <div id="rc-photo-wrap">
                    <div class="rc-photo-placeholder">👤</div>
                </div>
                <div class="rc-info">
                    <div class="rc-name" id="rc-name">—</div>
                    <div class="rc-id"   id="rc-id">—</div>
                    <div class="rc-fields">
                        <div class="rc-field">
                            <label>Course</label>
                            <span id="rc-course">—</span>
                        </div>
                        <div class="rc-field">
                            <label>Year / Section</label>
                            <span id="rc-year-sec">—</span>
                        </div>
                        <div class="rc-field">
                            <label>Email</label>
                            <span id="rc-email">—</span>
                        </div>
                        <div class="rc-field">
                            <label>Contact</label>
                            <span id="rc-contact">—</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Refocus -->
    <div class="refocus" onclick="refocus()">⚡ Click here if scanner stops responding</div>

</div>

<script>
// ── Clock ──
function updateClock() {
    const now = new Date();
    const pad = n => String(n).padStart(2,'0');
    document.getElementById('h').textContent = pad(now.getHours());
    document.getElementById('m').textContent = pad(now.getMinutes());
    document.getElementById('s').textContent = pad(now.getSeconds());
    document.getElementById('date-display').textContent =
        now.toLocaleDateString('en-US', { weekday:'long', year:'numeric', month:'long', day:'numeric' });
}
setInterval(updateClock, 1000);
updateClock();

// ── Focus ──
const rfidInput = document.getElementById('rfid_input');
function refocus() { rfidInput.focus(); }
document.addEventListener('click', e => { if (e.target.id !== 'rfid_input') refocus(); });
setInterval(refocus, 3000);

// ── Process URL ──
const PROCESS_URL = (() => {
    const loc = window.location.pathname;
    return loc.substring(0, loc.lastIndexOf('/') + 1) + 'process_scan.php';
})();

// ── Input handling ──
let debounceTimer = null;

rfidInput.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        const val = rfidInput.value.trim();
        rfidInput.value = '';
        if (val.length > 0) processScan(val);
    }
});

rfidInput.addEventListener('input', function() {
    clearTimeout(debounceTimer);
    const val = rfidInput.value.trim();
    if (val.length >= 4) {
        debounceTimer = setTimeout(() => {
            const current = rfidInput.value.trim();
            rfidInput.value = '';
            if (current.length > 0) processScan(current);
        }, 200);
    }
});

// ── Scan ──
async function processScan(rfid_uid) {
    setStatus('Processing...', 'neutral');
    setScanActive(true);

    try {
        const res = await fetch(PROCESS_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ rfid_uid, scan_method: 'RFID' })
        });

        if (!res.ok) throw new Error('HTTP ' + res.status);
        const result = await res.json();
        setScanActive(false);
        handleResult(result);
    } catch (err) {
        setScanActive(false);
        setStatus('Network error — ' + err.message, 'fail');
    }

    setTimeout(refocus, 100);
}

function handleResult(result) {
    const card = result.student;

    if (result.success) {
        setStatus('✓ ' + result.message, 'ok');
        if (card) showCard(card, result.session, 'success');
    } else {
        const showCodes = ['DUPLICATE_SCAN', 'INACTIVE_STUDENT', 'NO_ACTIVE_SESSION'];
        const cls = result.code === 'DUPLICATE_SCAN' ? 'warn' : 'fail';
        setStatus('✕ ' + result.message, cls);
        if (card && showCodes.includes(result.code)) {
            showCard(card, result.session || null, result.code === 'DUPLICATE_SCAN' ? 'warning' : 'error');
        } else {
            hideCard();
        }
    }

    setTimeout(() => {
        setStatus('Ready — place card on reader', 'neutral');
        hideCard();
    }, 8000);
}

// ── UI helpers ──
function setStatus(msg, cls) {
    const el = document.getElementById('status-msg');
    el.textContent = msg;
    el.className = cls;
}

function setScanActive(on) {
    const ind  = document.getElementById('scan-indicator');
    const prog = document.getElementById('progress-fill');
    ind.classList.toggle('active', on);
    prog.classList.toggle('scanning', on);
}

function showCard(student, session, type) {
    // photo
    const pw = document.getElementById('rc-photo-wrap');
    if (student.photo_url) {
        pw.innerHTML = `<img class="rc-photo" src="${esc(student.photo_url)}" alt="Photo" onerror="this.outerHTML='<div class=\\'rc-photo-placeholder\\'>👤</div>'">`;
    } else {
        pw.innerHTML = '<div class="rc-photo-placeholder">👤</div>';
    }

    document.getElementById('rc-name').textContent    = student.full_name;
    document.getElementById('rc-id').textContent      = student.student_id;
    document.getElementById('rc-course').textContent  = student.course;
    document.getElementById('rc-year-sec').textContent = student.year_level + ' · ' + student.section;
    document.getElementById('rc-email').textContent   = student.email || '—';
    document.getElementById('rc-contact').textContent = student.contact_number || '—';
    document.getElementById('rc-time').textContent    = new Date().toLocaleTimeString('en-US', { hour12: false });

    // status icon + text
    const icons   = { success: '✓', error: '✕', warning: '⚠' };
    const labels  = { success: 'RECORDED', error: 'FAILED', warning: 'DUPLICATE' };

    const icon = document.getElementById('rc-icon');
    icon.textContent  = icons[type] || '—';
    icon.className    = 'rc-status-icon ' + type;

    const stxt = document.getElementById('rc-status-text');
    stxt.textContent = labels[type] || '—';
    stxt.className   = 'rc-status-text ' + type;

    // result card border colour
    document.getElementById('result-card').className = 'result-card ' + type;

    // session type tag
    const tag = document.getElementById('rc-type-tag');
    if (session && session.attendance_type) {
        tag.textContent = session.attendance_type;
        tag.className   = 'rc-type-tag ' + session.attendance_type;
    } else {
        tag.textContent = 'SCAN';
        tag.className   = 'rc-type-tag default';
    }

    const wrap = document.getElementById('card-wrap');
    wrap.style.display = 'block';
    wrap.style.animation = 'none';
    void wrap.offsetWidth;
    wrap.style.animation = '';
}

function hideCard() {
    document.getElementById('card-wrap').style.display = 'none';
}

function esc(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>
</body>
</html>