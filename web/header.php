<?php
declare(strict_types=1);
require_once __DIR__ . '/functions.php';

$currentPage = basename($_SERVER['SCRIPT_NAME'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Equipment Manager</title>
    <style>
        :root {
            --bg: #f6f7f9;
            --panel: #ffffff;
            --text: #17202a;
            --muted: #657282;
            --line: #d9dee5;
            --line-soft: #edf0f4;
            --primary: #1f6feb;
            --primary-dark: #1557b0;
            --success-bg: #e9f8ef;
            --success-border: #79bf8c;
            --error-bg: #fff0f0;
            --error-border: #dd7b7b;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            background: var(--bg);
            color: var(--text);
            font-family: Arial, Helvetica, sans-serif;
            line-height: 1.5;
        }

        a { color: var(--primary); text-decoration: none; }
        a:hover { text-decoration: underline; }

        .site-header {
            background: var(--panel);
            border-bottom: 1px solid var(--line);
            box-shadow: 0 1px 8px rgba(23, 32, 42, 0.06);
        }

        .header-inner,
        main {
            width: min(1180px, calc(100% - 32px));
            margin: 0 auto;
        }

        .header-inner {
            padding: 22px 0 16px;
        }

        .brand-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            margin-bottom: 16px;
        }

        h1 {
            margin: 0;
            font-size: 28px;
            line-height: 1.2;
        }

        .tagline {
            margin: 4px 0 0;
            color: var(--muted);
            font-size: 14px;
        }

        nav {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        nav a {
            display: inline-flex;
            align-items: center;
            min-height: 38px;
            padding: 8px 12px;
            border: 1px solid var(--line);
            border-radius: 6px;
            color: var(--text);
            background: #fbfcfd;
            font-size: 14px;
            font-weight: 600;
        }

        nav a:hover {
            border-color: #b8c4d2;
            text-decoration: none;
        }

        nav a.active {
            border-color: var(--primary);
            background: #edf4ff;
            color: var(--primary-dark);
        }

        main {
            padding: 28px 0 42px;
        }

        h2 {
            margin: 0 0 16px;
            font-size: 21px;
            line-height: 1.25;
        }

        table {
            width: 100%;
            margin-top: 16px;
            border-collapse: collapse;
            overflow: hidden;
            background: var(--panel);
            border: 1px solid var(--line);
            border-radius: 8px;
        }

        th,
        td {
            padding: 11px 12px;
            border-bottom: 1px solid var(--line-soft);
            text-align: left;
            vertical-align: top;
        }

        th {
            background: #eef2f7;
            color: #334155;
            font-size: 13px;
            text-transform: uppercase;
        }

        tr:last-child td { border-bottom: 0; }
        tbody tr:nth-child(even) { background: #fafbfc; }

        form {
            display: grid;
            grid-template-columns: 190px minmax(220px, 420px);
            gap: 10px 14px;
            align-items: center;
            margin: 0;
        }

        label {
            color: #354252;
            font-weight: 600;
        }

        input,
        select {
            width: 100%;
            min-height: 40px;
            padding: 8px 10px;
            border: 1px solid #b9c3cf;
            border-radius: 6px;
            background: #ffffff;
            color: var(--text);
            font: inherit;
        }

        input:focus,
        select:focus {
            outline: 3px solid #cfe2ff;
            border-color: var(--primary);
        }

        button,
        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 40px;
            width: fit-content;
            padding: 9px 14px;
            border: 1px solid var(--primary);
            border-radius: 6px;
            background: var(--primary);
            color: #ffffff;
            font: inherit;
            font-weight: 700;
            cursor: pointer;
        }

        button:hover,
        .button:hover {
            background: var(--primary-dark);
            text-decoration: none;
        }

        form button {
            grid-column: 2;
            margin-top: 4px;
        }

        br { display: none; }

        ul {
            margin: 0;
            padding-left: 20px;
        }

        li { margin: 8px 0; }

        .message {
            padding: 12px 14px;
            margin: 14px 0;
            border-radius: 8px;
            font-weight: 600;
        }

        .success {
            background: var(--success-bg);
            border: 1px solid var(--success-border);
        }

        .error {
            background: var(--error-bg);
            border: 1px solid var(--error-border);
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
        }

        .actions a,
        .actions button {
            margin-right: 0;
        }

        .section {
            margin-bottom: 22px;
            padding: 22px;
            background: var(--panel);
            border: 1px solid var(--line);
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(23, 32, 42, 0.05);
        }

        .section:last-child { margin-bottom: 0; }

        @media (max-width: 720px) {
            .header-inner,
            main {
                width: min(100% - 20px, 1180px);
            }

            .brand-row {
                display: block;
            }

            nav a {
                flex: 1 1 auto;
                justify-content: center;
            }

            form {
                grid-template-columns: 1fr;
                gap: 7px;
            }

            form button {
                grid-column: 1;
            }

            .section {
                padding: 16px;
            }

            table {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
            }
        }
    </style>
</head>
<body>
    <header class="site-header">
        <div class="header-inner">
            <div class="brand-row">
                <div>
                    <h1>Equipment Manager</h1>
                    <p class="tagline">Track equipment, manufacturers, device types, and serial numbers.</p>
                </div>
            </div>
            <nav>
                <a href="index.php" class="<?= $currentPage === 'index.php' ? 'active' : ''; ?>">Home</a>
                <a href="search.php" class="<?= $currentPage === 'search.php' ? 'active' : ''; ?>">Search</a>
                <a href="add_equipment.php" class="<?= $currentPage === 'add_equipment.php' ? 'active' : ''; ?>">Add Equipment</a>
                <a href="add_device_type.php" class="<?= $currentPage === 'add_device_type.php' ? 'active' : ''; ?>">Add Device Type</a>
                <a href="add_manufacturer.php" class="<?= $currentPage === 'add_manufacturer.php' ? 'active' : ''; ?>">Add Manufacturer</a>
            </nav>
        </div>
    </header>
    <main>
