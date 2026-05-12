<?php
declare(strict_types=1);
require_once __DIR__ . '/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Equipment Manager</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        nav a { margin-right: 12px; }
        table { border-collapse: collapse; width: 100%; margin-top: 16px; }
        th, td { border: 1px solid #333; padding: 8px; text-align: left; }
        th { background: #eee; }
        form { margin-bottom: 20px; }
        label { display: inline-block; min-width: 180px; margin-bottom: 8px; }
        .message { padding: 10px; margin: 12px 0; }
        .success { background: #e7f7e7; border: 1px solid #7cb97c; }
        .error { background: #fdeaea; border: 1px solid #c26a6a; }
        .actions a, .actions button { margin-right: 8px; }
        .section { margin-bottom: 28px; padding-bottom: 16px; border-bottom: 1px solid #ccc; }
    </style>
</head>
<body>
    <h1>Equipment Manager</h1>
    <nav>
        <a href="index.php">Home</a>
        <a href="search.php">Search</a>
        <a href="add_equipment.php">Add Equipment</a>
        <a href="add_device_type.php">Add Device Type</a>
        <a href="add_manufacturer.php">Add Manufacturer</a>
    </nav>
    <hr>
