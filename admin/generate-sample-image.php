<?php
$html = '<!DOCTYPE html>
<html>
<head>
    <style>
        body {
            margin: 0;
            padding: 0;
            width: 1200px;
            height: 630px;
            background: linear-gradient(135deg, #1a1a1a 0%, #0c7b6f 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: white;
            font-family: Arial, sans-serif;
        }
        .title {
            font-size: 48px;
            text-align: center;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
            max-width: 1000px;
            line-height: 1.2;
        }
        .subtitle {
            font-size: 28px;
            color: #00c7b6;
            text-align: center;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
        }
    </style>
</head>
<body>
    <h1 class="title">10 Essential Digital Marketing Strategies for 2024</h1>
    <p class="subtitle">A Comprehensive Guide to Modern Marketing</p>
</body>
</html>';

// Save the HTML file
file_put_contents('../uploads/marketing-strategies-2024.html', $html);

echo "Sample image HTML generated successfully! You can open this file in a browser and take a screenshot to use as the blog post image.";
?> 