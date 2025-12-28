<?php
// Copy all files from project-root 'images' into 'frontend/images'
$root = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR;
$src = realpath($root . 'images');
$dest = realpath($root . 'frontend') . DIRECTORY_SEPARATOR . 'images';
if ($src === false) {
    fwrite(STDERR, "Source images directory not found: {$root}images\n");
    exit(2);
}
if (!is_dir($dest)) {
    if (!mkdir($dest, 0755, true)) {
        fwrite(STDERR, "Failed to create destination directory: {$dest}\n");
        exit(3);
    }
}
$files = scandir($src);
$copied = 0;
$skipped = 0;
$report = [];
foreach ($files as $f) {
    if ($f === '.' || $f === '..') continue;
    $s = $src . DIRECTORY_SEPARATOR . $f;
    if (!is_file($s)) continue;
    $d = $dest . DIRECTORY_SEPARATOR . $f;
    if (file_exists($d)) {
        $skipped++;
        $report[] = [ 'file' => $f, 'status' => 'exists' ];
        continue;
    }
    if (!copy($s, $d)) {
        $report[] = [ 'file' => $f, 'status' => 'failed' ];
    } else {
        $copied++;
        $report[] = [ 'file' => $f, 'status' => 'copied' ];
    }
}
echo "Images sync report:\n";
echo "Source: {$src}\n";
echo "Destination: {$dest}\n";
echo "Files copied: {$copied}, skipped (already exist): {$skipped}\n\n";
foreach ($report as $r) {
    echo "$r[file] -> $r[status]\n";
}

exit(0);
