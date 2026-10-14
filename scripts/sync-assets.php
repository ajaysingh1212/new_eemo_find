<?php

$root = dirname(__DIR__);
if (! is_dir($root.'/public/assets')) {
    mkdir($root.'/public/assets', 0755, true);
}
$css = file_get_contents($root.'/resources/css/app.css');
$adminTheme = file_get_contents($root.'/resources/css/admin-theme.css');
$javascript = $root.'/resources/js/app.js';
$bootstrap = $root.'/node_modules/bootstrap/dist/css/bootstrap-grid.min.css';
if (! is_file($bootstrap)) {
    fwrite(STDERR, "Run npm ci before synchronizing assets.\n");
    exit(1);
}
$css = str_replace("@import 'bootstrap/dist/css/bootstrap-grid.min.css';", file_get_contents($bootstrap), $css);
if ($adminTheme === false || ! is_file($javascript)) {
    fwrite(STDERR, "The admin theme or application JavaScript source is missing.\n");
    exit(1);
}
if (file_put_contents($root.'/public/assets/app.css', $css) === false
    || file_put_contents($root.'/public/assets/admin-theme.css', $adminTheme) === false) {
    exit(1);
}
if (! copy($javascript, $root.'/public/assets/app.js')) {
    exit(1);
}
echo "Standalone assets synchronized.\n";
