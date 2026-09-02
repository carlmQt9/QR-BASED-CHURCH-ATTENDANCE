<?php
echo '<h1>PHP Works!</h1>';
echo '<p>If you see this, PHP is working.</p>';

echo '<h2>Files Check:</h2>';
echo '<ul>';
echo '<li>bootstrap/app.php: ' . (file_exists(__DIR__.'/bootstrap/app.php') ? '<span style="color:green">EXISTS</span>' : '<span style="color:red">MISSING</span>') . '</li>';
echo '<li>vendor/autoload.php: ' . (file_exists(__DIR__.'/vendor/autoload.php') ? '<span style="color:green">EXISTS</span>' : '<span style="color:red">MISSING</span>') . '</li>';
echo '<li>.env: ' . (file_exists(__DIR__.'/.env') ? '<span style="color:green">EXISTS</span>' : '<span style="color:red">MISSING</span>') . '</li>';
echo '</ul>';

echo '<h2>Try Loading Laravel:</h2>';
try {
    require __DIR__.'/vendor/autoload.php';
    echo '<p style="color:green">✓ Autoload works</p>';
    
    $app = require_once __DIR__.'/bootstrap/app.php';
    echo '<p style="color:green">✓ Bootstrap works</p>';
    
    echo '<h3>SUCCESS! Laravel loads fine.</h3>';
    echo '<p>The issue is with routing. Let me check .htaccess...</p>';
    
    if (file_exists(__DIR__.'/.htaccess')) {
        echo '<h3>Root .htaccess exists:</h3>';
        echo '<pre>' . htmlspecialchars(file_get_contents(__DIR__.'/.htaccess')) . '</pre>';
        echo '<p style="color:red;">DELETE THIS FILE! It should not be in root.</p>';
    } else {
        echo '<p style="color:green;">✓ No root .htaccess (good!)</p>';
    }
    
    if (file_exists(__DIR__.'/public/.htaccess')) {
        echo '<h3>public/.htaccess exists:</h3>';
        echo '<pre>' . htmlspecialchars(file_get_contents(__DIR__.'/public/.htaccess')) . '</pre>';
    }
    
} catch (Exception $e) {
    echo '<p style="color:red">✗ Error: ' . $e->getMessage() . '</p>';
    echo '<pre>' . $e->getTraceAsString() . '</pre>';
}
?>
