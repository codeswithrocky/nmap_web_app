<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $target_ip = trim($_POST['target_ip']);
    $nmap_command = $_POST['nmap_command'];
    $save_results = isset($_POST['save_results']); // Checkbox for saving the results

    // Validate the IP address or domain
    if (empty($target_ip)) {
        echo json_encode(['error' => 'Please provide an IP address or domain.']);
        exit;
    }

    if (filter_var($target_ip, FILTER_VALIDATE_IP)) {
        // Valid IP address
    } elseif (filter_var($target_ip, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
        // Valid domain
    } else {
        echo json_encode(['error' => 'Invalid IP address or domain.']);
        exit;
    }

    // Validate the selected Nmap command
    if (empty($nmap_command)) {
        echo json_encode(['error' => 'Please select a scan type.']);
        exit;
    }

    // Escape shell arguments to prevent injection
    $escaped_ip = escapeshellarg($target_ip);
    $escaped_command = escapeshellarg($nmap_command);

    // Build the command
    $command = "sudo nmap $escaped_command $escaped_ip 2>&1"; // Capture stdout and stderr

    // Execute the command
    $output = shell_exec($command);

    if ($output) {
        // If save_results is checked, save the scan output
        if ($save_results) {
            $filename = "nmap_scan_" . time() . ".txt";
            $file_path = __DIR__ . "/scans/" . $filename;

            // Try to save the file
            if (file_put_contents($file_path, $output) !== false) {
                echo json_encode([
                    'output' => $output,
                    'file_link' => 'scans/' . $filename // Provide a link to download the file
                ]);
            } else {
                echo json_encode(['error' => 'Failed to save the scan results.']);
            }
        } else {
            echo json_encode(['output' => $output]);
        }
    } else {
        echo json_encode(['error' => 'Nmap command failed to execute.']);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nmap Command Executor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&family=Montserrat:wght@400;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(to right, #f8f9fa, #e9ecef);
            font-family: 'Poppins', sans-serif;
            color: #343a40;
        }
        .container {
            margin-top: 60px;
        }
        .card {
            background-color: rgba(255, 255, 255, 0.85);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 8px 12px rgba(0, 0, 0, 0.1);
        }
        .output-box {
            background-color: #343a40;
            padding: 20px;
            border-radius: 8px;
            color: #fff;
            height: 400px;
            overflow-y: auto;
            white-space: pre-wrap;
            margin-top: 15px;
        }
        .output-header {
            font-family: 'Montserrat', sans-serif;
            font-weight: 600;
            font-size: 1.5rem;
            margin-top: -41px;
            margin-bottom: 10px;
            color: #007bff;
        }
        .output-message {
            text-align: left; 
            margin-top: -37px;
            color: rgba(255, 255, 255, 0.9);
            font-size: 1rem;
        }
        .form-control, .form-select {
            background-color: rgba(255, 255, 255, 0.8);
            border: 1px solid #ced4da;
            color: #495057;
        }
        .form-control:focus, .form-select:focus {
            background-color: rgba(255, 255, 255, 1);
            box-shadow: 0px 0px 8px rgba(0, 0, 0, 0.2);
            border-color: #80bdff;
        }
        .btn-primary {
            background-color: #007bff;
            border: none;
            font-weight: 500;
            padding: 10px 15px;
            font-size: 1.1rem;
        }
        .btn-primary:hover {
            background-color: #0056b3;
        }
        .error-message {
            color: #dc3545;
            font-weight: bold;
        }
        .loading-spinner {
            display: none;
            margin-top: 20px;
        }
        .spinner-border {
            width: 1rem;
            height: 1rem;
            border-width: 0.2em;
        }
        h1 {
            font-size: 3rem;
            color: #007bff;
            text-align: center;
            margin-bottom: 20px;
            font-weight: 600;
        }
        .text-muted {
            color: rgba(0, 0, 0, 0.6) !important;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="card shadow-lg text-dark">
        <h1 class="text-center"><i class="fas fa-network-wired"></i> Nmap Command Executor</h1>
        <p class="text-center text-muted">Run Nmap scans with ease using a streamlined interface.</p>
        <form id="nmapForm">
            <div class="mb-3">
                <label for="target_ip" class="form-label"><i class="fas fa-laptop-code"></i> Target IP/Domain:</label>
                <input type="text" class="form-control" id="target_ip" name="target_ip" placeholder="Enter IP or domain..." required>
            </div>
            <div class="mb-3">
                <label for="nmap_command" class="form-label"><i class="fas fa-terminal"></i> Select Nmap Scan Type:</label>
                <select class="form-select" id="nmap_command" name="nmap_command" required>
                    <option value="">-- Select Scan Type --</option>
                    <option value="-sP">Ping Scan (-sP)</option>
                    <option value="-sS">SYN Scan (-sS)</option>
                    <option value="-sT">TCP Connect Scan (-sT)</option>
                    <option value="-sU">UDP Scan (-sU)</option>
                    <option value="-p 80">Scan Port 80 (-p 80)</option>
                    <option value="-O">OS Detection (-O)</option>
                    <option value="-sA">ACK Scan (-sA)</option>
                    <option value="-sF">FIN Scan (-sF)</option>
                    <option value="-sN">NULL Scan (-sN)</option>
                    <option value="-sW">Window Scan (-sW)</option>
                    <option value="-sI">Idle Scan (-sI)</option>
                    <option value="-sX">Xmas Scan (-sX)</option>
                    <option value="-sL">List Scan (-sL)</option>
                    <option value="-sC">Default Script Scan (-sC)</option>
                    <option value="-p-">Scan All Ports (-p-)</option>
                    <option value="-p 1-1000">Scan Ports 1-1000 (-p 1-1000)</option>
                    <option value="-T4">Aggressive Timing Template (-T4)</option>
                    <option value="-A">Aggressive Scan Options (-A)</option>
                    <option value="-Pn">Skip Host Discovery (-Pn)</option>
                    <option value="-sR">RPC Scan (-sR)</option>
                    <option value="-sO">IP Protocol Scan (-sO)</option>
                </select>
            </div>
            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="save_results" name="save_results">
                <label class="form-check-label" for="save_results">Save Scan Results</label>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-play"></i> Run Nmap Scan</button>
        </form>

        <div class="output-box mt-4" id="outputBox">
            <div class="output-header">Output:</div>
            <div id="outputContent" class="output-message">
                Please run a scan to see the output here.
            </div>
            <div id="downloadLink" class="mt-3"></div>
        </div>

        <div class="loading-spinner text-center" id="loadingSpinner">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Running Nmap scan...</p>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
$(document).ready(function() {
    $('#nmapForm').on('submit', function(event) {
        event.preventDefault();
        $('#outputContent').html('');
        $('#loadingSpinner').show();
        $('#downloadLink').html('');

        $.ajax({
            url: 'index.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                $('#loadingSpinner').hide();
                if (response.error) {
                    $('#outputContent').html('<div class="error-message">' + response.error + '</div>');
                } else {
                    $('#outputContent').html(response.output);
                    if (response.file_link) {
                        $('#downloadLink').html('<a href="' + response.file_link + '" class="btn btn-success mt-3" download><i class="fas fa-download"></i> Download Results</a>');
                    }
                }
            },
            error: function() {
                $('#loadingSpinner').hide();
                $('#outputContent').html('<div class="error-message">An error occurred while processing the request.</div>');
            }
        });
    });
});
</script>

</body>
</html>
