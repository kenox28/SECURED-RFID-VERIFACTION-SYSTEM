<?php
// scanner/scan.php
require_once '../config/database.php';

// Fetch active sessions for the dropdown (if any)
$activeSessions = [];
try {
    $s = $pdo->query("SELECT id, session_name, attendance_type FROM attendance_sessions WHERE status = 'ACTIVE' ORDER BY created_at DESC");
    $activeSessions = $s->fetchAll();
} catch (Exception $e) {
    // silently fail — scanner still works without pre-selected session
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Scanner</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Share+Tech+Mono&family=Exo+2:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg:        #080c14;
            --surface:   #0d1420;
            --card:      #111b2e;
            --border:    #1e3a5f;
            --accent:    #00d4ff;
            --accent2:   #0066ff;
            --success:   #00ff88;
            --error:     #ff3366;
            --warning:   #ffaa00;
            --text:      #e8f4fd;
            --muted:     #4a7a9b;
            --glow:      0 0 20px rgba(0,212,255,.35);
            --glow-lg:   0 0 60px rgba(0,212,255,.2);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            background: var(--bg);
            color: var(--text);
            font-family: 'Exo 2', sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            overflow-x: hidden;
        }

        /* Animated grid background */
        body::before {
            content: '';
            position: fixed; inset: 0;
            background-image:
                linear-gradient(rgba(0,212,255,.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0,212,255,.04) 1px, transparent 1px);
            background-size: 40px 40px;
            pointer-events: none;
            z-index: 0;
        }

        .page-wrap {
            position: relative; z-index: 1;
            width: 100%; max-width: 900px;
            padding: 24px 16px;
            display: flex;
            flex-direction: column;
            gap: 24px;
            align-items: center;
        }

        /* ── Header ── */
        .header {
            text-align: center;
        }
        .header h1 {
            font-family: 'Orbitron', monospace;
            font-size: clamp(1.4rem, 4vw, 2.2rem);
            font-weight: 900;
            letter-spacing: 4px;
            color: var(--accent);
            text-shadow: var(--glow);
            text-transform: uppercase;
        }
        .header p {
            color: var(--muted);
            font-family: 'Share Tech Mono', monospace;
            font-size: .85rem;
            letter-spacing: 2px;
            margin-top: 4px;
        }

        /* ── Clock ── */
        .clock-wrap {
            text-align: center;
        }
        #clock {
            font-family: 'Orbitron', monospace;
            font-size: clamp(2.5rem, 8vw, 4.5rem);
            font-weight: 700;
            color: var(--accent);
            text-shadow: var(--glow-lg);
            letter-spacing: 6px;
        }
        #date-display {
            font-family: 'Share Tech Mono', monospace;
            color: var(--muted);
            font-size: .9rem;
            letter-spacing: 3px;
            margin-top: 4px;
        }

        /* ── Session info strip ── */
        #session-strip {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 10px 20px;
            font-family: 'Share Tech Mono', monospace;
            font-size: .85rem;
            color: var(--muted);
            text-align: center;
            width: 100%;
            max-width: 520px;
        }
        #session-strip span { color: var(--accent); font-weight: 700; }

        /* ── Scanner box ── */
        .scanner-box {
            background: var(--surface);
            border: 2px solid var(--border);
            border-radius: 16px;
            padding: 32px 28px;
            width: 100%; max-width: 520px;
            box-shadow: 0 8px 40px rgba(0,0,0,.5), inset 0 1px 0 rgba(255,255,255,.05);
            position: relative;
        }
        .scanner-box::before {
            content: '';
            position: absolute; top: 0; left: 50%; transform: translateX(-50%);
            width: 60%; height: 2px;
            background: linear-gradient(90deg, transparent, var(--accent), transparent);
            border-radius: 2px;
        }

        .scan-label {
            font-family: 'Share Tech Mono', monospace;
            font-size: .75rem;
            letter-spacing: 3px;
            color: var(--muted);
            text-transform: uppercase;
            margin-bottom: 10px;
        }

        #rfid_input {
            width: 100%;
            background: var(--bg);
            border: 2px solid var(--border);
            border-radius: 10px;
            color: var(--accent);
            font-family: 'Orbitron', monospace;
            font-size: 1.1rem;
            letter-spacing: 4px;
            padding: 14px 18px;
            text-align: center;
            outline: none;
            transition: border-color .2s, box-shadow .2s;
            caret-color: var(--accent);
        }
        #rfid_input:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(0,212,255,.15), var(--glow);
        }
        #rfid_input::placeholder { color: var(--muted); font-size: .85rem; letter-spacing: 2px; }

        /* scanning animation bar */
        .scan-bar {
            height: 3px;
            background: var(--border);
            border-radius: 3px;
            margin-top: 14px;
            overflow: hidden;
        }
        .scan-bar-inner {
            height: 100%;
            width: 0;
            background: linear-gradient(90deg, var(--accent2), var(--accent));
            border-radius: 3px;
            transition: width .3s;
        }
        .scan-bar-inner.active { animation: scanAnim 1s ease-in-out infinite; width: 100%; }
        @keyframes scanAnim {
            0%   { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }

        /* ── Status message ── */
        #status-msg {
            margin-top: 18px;
            text-align: center;
            font-family: 'Share Tech Mono', monospace;
            font-size: 1rem;
            letter-spacing: 1px;
            min-height: 28px;
            transition: color .3s;
        }
        #status-msg.ok      { color: var(--success); }
        #status-msg.fail    { color: var(--error); }
        #status-msg.warn    { color: var(--warning); }
        #status-msg.neutral { color: var(--muted); }

        /* ── Student ID Card ── */
        #card-wrap {
            display: none;
            width: 100%; max-width: 520px;
            animation: cardIn .4s cubic-bezier(.34,1.56,.64,1) both;
        }
        @keyframes cardIn {
            from { opacity:0; transform: translateY(20px) scale(.97); }
            to   { opacity:1; transform: translateY(0)   scale(1); }
        }

        .id-card {
            background: linear-gradient(135deg, #0d1f3c 0%, #0a1628 60%, #061020 100%);
            border: 2px solid var(--accent);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 0 40px rgba(0,212,255,.25), 0 20px 60px rgba(0,0,0,.6);
            position: relative;
        }
        .id-card::after {
            content: '';
            position: absolute; inset: 0;
            background: linear-gradient(135deg, rgba(0,212,255,.04) 0%, transparent 50%);
            pointer-events: none;
        }

        /* card top stripe */
        .card-stripe {
            background: linear-gradient(90deg, var(--accent2), var(--accent));
            padding: 12px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .card-stripe .school-name {
            font-family: 'Orbitron', monospace;
            font-size: .75rem;
            font-weight: 700;
            color: #fff;
            letter-spacing: 2px;
            text-transform: uppercase;
        }
        .card-stripe .card-type-badge {
            font-family: 'Share Tech Mono', monospace;
            font-size: .7rem;
            background: rgba(255,255,255,.2);
            color: #fff;
            padding: 3px 10px;
            border-radius: 20px;
            letter-spacing: 1px;
        }
        .card-type-badge.IN  { background: rgba(0,255,136,.25); color: var(--success); }
        .card-type-badge.OUT { background: rgba(255,51,102,.25); color: var(--error); }

        /* card body */
        .card-body {
            display: flex;
            gap: 20px;
            padding: 20px 24px;
            align-items: flex-start;
        }

        /* photo */
        .card-photo-wrap {
            flex-shrink: 0;
        }
        .card-photo {
            width: 100px;
            height: 120px;
            border-radius: 10px;
            border: 2px solid var(--accent);
            object-fit: cover;
            background: var(--bg);
            box-shadow: 0 0 20px rgba(0,212,255,.3);
        }
        .card-photo-placeholder {
            width: 100px;
            height: 120px;
            border-radius: 10px;
            border: 2px solid var(--border);
            background: var(--bg);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--muted);
            font-size: 2.5rem;
        }

        /* info section */
        .card-info { flex: 1; min-width: 0; }

        .card-name {
            font-family: 'Exo 2', sans-serif;
            font-size: clamp(1.1rem, 3vw, 1.35rem);
            font-weight: 700;
            color: #fff;
            line-height: 1.2;
            margin-bottom: 4px;
        }
        .card-student-id {
            font-family: 'Orbitron', monospace;
            font-size: .75rem;
            color: var(--accent);
            letter-spacing: 2px;
            margin-bottom: 14px;
        }

        .card-fields {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px 16px;
        }
        .card-field label {
            font-family: 'Share Tech Mono', monospace;
            font-size: .65rem;
            color: var(--muted);
            letter-spacing: 2px;
            text-transform: uppercase;
            display: block;
            margin-bottom: 2px;
        }
        .card-field span {
            font-size: .85rem;
            color: var(--text);
            font-weight: 600;
        }

        /* scan result footer */
        .card-footer {
            border-top: 1px solid var(--border);
            padding: 12px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .scan-time-label {
            font-family: 'Share Tech Mono', monospace;
            font-size: .75rem;
            color: var(--muted);
            letter-spacing: 1px;
        }
        #scan-timestamp {
            font-family: 'Share Tech Mono', monospace;
            font-size: .8rem;
            color: var(--accent);
        }
        .result-badge {
            font-family: 'Orbitron', monospace;
            font-size: .8rem;
            font-weight: 700;
            padding: 5px 16px;
            border-radius: 20px;
            letter-spacing: 2px;
        }
        .result-badge.success { background: rgba(0,255,136,.15); color: var(--success); border: 1px solid var(--success); }
        .result-badge.error   { background: rgba(255,51,102,.15); color: var(--error);   border: 1px solid var(--error); }
        .result-badge.warning { background: rgba(255,170,0,.15);  color: var(--warning); border: 1px solid var(--warning); }

        /* ── Focus refocus note ── */
        .refocus-note {
            font-family: 'Share Tech Mono', monospace;
            font-size: .75rem;
            color: var(--muted);
            letter-spacing: 2px;
            text-align: center;
            cursor: pointer;
            text-decoration: underline dotted;
        }
        .refocus-note:hover { color: var(--accent); }
    </style>
</head>
<body>
<div class="page-wrap">

    <!-- Header -->
    <div class="header">
        <h1>RFID Attendance System</h1>
        <p>// SCAN TERMINAL — READY</p>
    </div>

    <!-- Clock -->
    <div class="clock-wrap">
        <div id="clock">00:00:00</div>
        <div id="date-display">--</div>
    </div>

    <!-- Active session info -->
    <div id="session-strip">
        <?php if (!empty($activeSessions)): ?>
            Active Session: <span><?= htmlspecialchars($activeSessions[0]['session_name']) ?></span>
            &nbsp;|&nbsp; Type: <span><?= htmlspecialchars($activeSessions[0]['attendance_type']) ?></span>
        <?php else: ?>
            No active session — contact administrator
        <?php endif; ?>
    </div>

    <!-- Scanner input -->
    <div class="scanner-box">
        <div class="scan-label">// Scan RFID Card or QR Code</div>
        <input
            type="text"
            id="rfid_input"
            placeholder="Waiting for scan..."
            autocomplete="off"
            autofocus
        >
        <div class="scan-bar"><div class="scan-bar-inner" id="scan-bar-inner"></div></div>
        <div id="status-msg" class="neutral">Ready — place card on reader</div>
    </div>

    <!-- Student ID Card (shown after scan) -->
    <div id="card-wrap">
        <div class="id-card" id="id-card">
            <div class="card-stripe">
                <div class="school-name">Student Identification</div>
                <div class="card-type-badge" id="card-type-badge">--</div>
            </div>
            <div class="card-body">
                <div class="card-photo-wrap" id="photo-wrap">
                    <div class="card-photo-placeholder">👤</div>
                </div>
                <div class="card-info">
                    <div class="card-name" id="card-name">—</div>
                    <div class="card-student-id" id="card-sid">—</div>
                    <div class="card-fields">
                        <div class="card-field">
                            <label>Course</label>
                            <span id="card-course">—</span>
                        </div>
                        <div class="card-field">
                            <label>Year &amp; Section</label>
                            <span id="card-year-sec">—</span>
                        </div>
                        <div class="card-field">
                            <label>Email</label>
                            <span id="card-email">—</span>
                        </div>
                        <div class="card-field">
                            <label>Contact</label>
                            <span id="card-contact">—</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <div>
                    <div class="scan-time-label">SCANNED AT</div>
                    <div id="scan-timestamp">—</div>
                </div>
                <div class="result-badge" id="result-badge">—</div>
            </div>
        </div>
    </div>

    <!-- Tap to refocus -->
    <div class="refocus-note" onclick="refocus()">
        ⚡ Click here if scanner stops responding
    </div>

</div>

<script>
// ─── Clock ───────────────────────────────────────────────────────────────────
function updateClock() {
    const now = new Date();
    document.getElementById('clock').textContent =
        now.toLocaleTimeString('en-US', { hour12: false });
    document.getElementById('date-display').textContent =
        now.toLocaleDateString('en-US', { weekday:'long', year:'numeric', month:'long', day:'numeric' });
}
setInterval(updateClock, 1000);
updateClock();

// ─── Auto-refocus ─────────────────────────────────────────────────────────────
// RFID scanners need the input to always be focused.
const rfidInput = document.getElementById('rfid_input');

function refocus() {
    rfidInput.focus();
}
// Refocus whenever user clicks anywhere on the page
document.addEventListener('click', function(e) {
    if (e.target.id !== 'rfid_input') refocus();
});
// Also refocus every 3 seconds in case something stole focus
setInterval(refocus, 3000);

// ─── Determine correct base path ────────────────────────────────────────────
// Works whether scan.php lives at /scanner/scan.php or /scan.php etc.
const PROCESS_URL = (function() {
    const loc = window.location.pathname;
    // strip the filename, keep the directory
    const dir = loc.substring(0, loc.lastIndexOf('/') + 1);
    return dir + 'process_scan.php';
})();

// ─── Scanner input — wait for Enter key (how real RFID readers work) ─────────
let buffer = '';
let debounceTimer = null;

rfidInput.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        const val = rfidInput.value.trim();
        rfidInput.value = '';
        if (val.length > 0) {
            processScan(val);
        }
    }
});

// Fallback: some readers don't send Enter — use a 200ms debounce instead
rfidInput.addEventListener('input', function() {
    clearTimeout(debounceTimer);
    const val = rfidInput.value.trim();
    if (val.length >= 4) {  // minimum UID length
        debounceTimer = setTimeout(() => {
            const current = rfidInput.value.trim();
            rfidInput.value = '';
            if (current.length > 0) processScan(current);
        }, 200);
    }
});

// ─── Process scan ─────────────────────────────────────────────────────────────
async function processScan(rfid_uid) {
    setStatus('Processing...', 'neutral');
    startScanBar();

    try {
        const response = await fetch(PROCESS_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                rfid_uid:   rfid_uid,
                scan_method: 'RFID'
            })
        });

        if (!response.ok) {
            throw new Error('Server returned HTTP ' + response.status);
        }

        const result = await response.json();
        stopScanBar();
        handleResult(result);

    } catch (err) {
        stopScanBar();
        setStatus('⚠ Network error — ' + err.message, 'fail');
        console.error('Scan error:', err);
    }

    // Always refocus after processing
    setTimeout(refocus, 100);
}

// ─── Handle server result ────────────────────────────────────────────────────
function handleResult(result) {
    const card  = result.student;

    if (result.success) {
        setStatus('✔ ' + result.message, 'ok');
        if (card) showCard(card, result.session, 'success');
    } else {
        // Codes that still show the student card
        const showCardCodes = ['DUPLICATE_SCAN', 'INACTIVE_STUDENT', 'NO_ACTIVE_SESSION'];
        const msgClass = result.code === 'DUPLICATE_SCAN' ? 'warn' : 'fail';

        setStatus('✖ ' + result.message, msgClass);

        if (card && showCardCodes.includes(result.code)) {
            const badgeType = result.code === 'DUPLICATE_SCAN' ? 'warning' : 'error';
            showCard(card, result.session || null, badgeType);
        } else {
            hideCard();
        }
    }

    // Auto-clear the status message after 8 seconds
    setTimeout(() => {
        setStatus('Ready — place card on reader', 'neutral');
        hideCard();
    }, 8000);
}

// ─── UI Helpers ──────────────────────────────────────────────────────────────
function setStatus(msg, cls) {
    const el = document.getElementById('status-msg');
    el.textContent = msg;
    el.className = cls;
}

function startScanBar() {
    document.getElementById('scan-bar-inner').classList.add('active');
}
function stopScanBar() {
    document.getElementById('scan-bar-inner').classList.remove('active');
}

function showCard(student, session, badgeType) {
    // Photo
    const photoWrap = document.getElementById('photo-wrap');
    if (student.photo_url) {
        photoWrap.innerHTML = `<img class="card-photo" src="${escHtml(student.photo_url)}" alt="Student Photo" onerror="this.parentElement.innerHTML='<div class=\\'card-photo-placeholder\\'>👤</div>'">`;
    } else {
        photoWrap.innerHTML = '<div class="card-photo-placeholder">👤</div>';
    }

    // Core info
    document.getElementById('card-name').textContent   = student.full_name;
    document.getElementById('card-sid').textContent    = 'ID: ' + student.student_id;
    document.getElementById('card-course').textContent = student.course;
    document.getElementById('card-year-sec').textContent = student.year_level + ' — ' + student.section;
    document.getElementById('card-email').textContent   = student.email || '—';
    document.getElementById('card-contact').textContent = student.contact_number || '—';

    // Scan timestamp
    document.getElementById('scan-timestamp').textContent =
        new Date().toLocaleTimeString('en-US', { hour12: false });

    // Type badge (IN / OUT from session)
    const typeBadge = document.getElementById('card-type-badge');
    if (session && session.attendance_type) {
        typeBadge.textContent = session.attendance_type;
        typeBadge.className   = 'card-type-badge ' + session.attendance_type;
    } else {
        typeBadge.textContent = 'SCAN';
        typeBadge.className   = 'card-type-badge';
    }

    // Result badge
    const rb = document.getElementById('result-badge');
    const labels = { success: '✔ RECORDED', error: '✖ FAILED', warning: '⚠ DUPLICATE' };
    rb.textContent = labels[badgeType] || '—';
    rb.className   = 'result-badge ' + badgeType;

    // Show card with animation
    const wrap = document.getElementById('card-wrap');
    wrap.style.display = 'block';
    // Force reflow to replay animation
    void wrap.offsetWidth;
    wrap.style.animation = 'none';
    wrap.offsetWidth;
    wrap.style.animation = '';
}

function hideCard() {
    document.getElementById('card-wrap').style.display = 'none';
}

function escHtml(str) {
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}
</script>
</body>
</html>