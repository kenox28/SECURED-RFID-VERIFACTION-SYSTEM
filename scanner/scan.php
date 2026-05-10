<?php
// scan.php
require_once '../config/database.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Scanner</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #121212;
            color: #ffffff;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .scanner-container {
            text-align: center;
        }
        .clock {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        .status {
            font-size: 2rem;
            margin-top: 1rem;
        }
    </style>
</head>
<body>
    <div class="scanner-container">
        <div class="clock" id="clock"></div>
        <input type="text" id="rfid_input" class="form-control" placeholder="Scan RFID or QR Code" autofocus>
        <div class="status" id="status">Waiting for scan...</div>
    </div>

    <script>
        // Real-time clock
        function updateClock() {
            const now = new Date();
            document.getElementById('clock').textContent = now.toLocaleTimeString();
        }
        setInterval(updateClock, 1000);
        updateClock();

        // Handle RFID input
        const rfidInput = document.getElementById('rfid_input');
        rfidInput.addEventListener('input', async () => {
            const value = rfidInput.value.trim();
            if (value) {
                document.getElementById('status').textContent = 'Processing...';
                try {
                    const response = await fetch('/scanner/process_scan.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ scanned_value: value })
                    });
                    const result = await response.json();
                    document.getElementById('status').textContent = result.message;
                } catch (error) {
                    document.getElementById('status').textContent = 'Error processing scan';
                }
                rfidInput.value = '';
            }
        });
    </script>
</body>
</html>