<?php
require_once __DIR__ . '/../backend/models/AccountRepository.php';
$r = new AccountRepository();
// inspect resolved file path using public accessor (avoid reflection/deprecated APIs)
$filePath = method_exists($r, 'getHtmlFile') ? $r->getHtmlFile() : null;
echo "Resolved Taikhoan.html path: " . ($filePath ?? 'NULL') . PHP_EOL;
if ($filePath && file_exists($filePath)) {
	echo "File exists. Size: " . filesize($filePath) . " bytes\n";
	echo "--- File head ---\n" . substr(file_get_contents($filePath),0,800) . "\n--- END ---\n";
} else {
	echo "File not found\n";
}
$a = $r->readAccounts();
echo json_encode($a, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;

// Raw regex test
$html = file_get_contents($filePath);
$matches = [];
$ok = preg_match('#<script[^>]*id=["\']savedAccounts["\'][^>]*>(.*?)</script>#is', $html, $matches);
echo "preg_match ok=" . ($ok?1:0) . "\n";
if ($ok) {
	echo "Captured length: " . strlen($matches[1]) . "\n";
	echo "Captured head:\n" . substr($matches[1],0,300) . "\n";
}
