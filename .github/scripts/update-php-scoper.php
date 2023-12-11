<?php

// Function to fetch the latest release from humbug/php-scoper
function getLatestRelease() {
	$url = 'https://api.github.com/repos/humbug/php-scoper/releases/latest';
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_USERAGENT, 'PHP');
	$response = curl_exec($ch);
	curl_close($ch);
	return json_decode($response, true);
}

// Function to get the required PHP version from humbug/php-scoper's composer.json
function getRequiredPhpVersion() {
	$url = 'https://raw.githubusercontent.com/humbug/php-scoper/main/composer.json';
	$composerJson = json_decode(file_get_contents($url), true);
	return $composerJson['require']['php'];
}

// Function to update the composer.json file with the new PHP version
function updateComposerJson($newPhpVersion) {
	$composerJsonPath = 'composer.json';
	$composerJson = json_decode(file_get_contents($composerJsonPath), true);
	$composerJson['require']['php'] = $newPhpVersion;
	file_put_contents($composerJsonPath, json_encode($composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

// Function to download the latest release to the bin/ folder
function downloadLatestRelease($downloadUrl) {
	$fileName = 'bin/php-scoper.phar';
	file_put_contents($fileName, fopen($downloadUrl, 'r'));
}

// Main process
$latestRelease = getLatestRelease();
$newVersion = $latestRelease['tag_name'];
$downloadUrl = $latestRelease['assets'][0]['browser_download_url'];

// Get the required PHP version
$newPhpVersion = getRequiredPhpVersion();

// Check if a new release is available
$currentVersion = trim(shell_exec('git tag | tail -n1')); // Get the latest tag, or nothing if no tags exist

if (empty($currentVersion)) {
	$currentVersion = '0.0.0'; // Default version if no tags are present
}

if (version_compare($newVersion, $currentVersion, '>')) {
	echo "Current version: $currentVersion" . PHP_EOL;
	echo "New version:     $newVersion" . PHP_EOL;
	
	// Update composer.json
	updateComposerJson($newPhpVersion);

	// Download latest release
	downloadLatestRelease($downloadUrl);

	// Use Environment Files for setting output variables
	file_put_contents(getenv('GITHUB_ENV'), "UPDATED=true" . PHP_EOL, FILE_APPEND);
	file_put_contents(getenv('GITHUB_ENV'), "NEW_VERSION=$newVersion" . PHP_EOL, FILE_APPEND);
	file_put_contents(getenv('GITHUB_ENV'), "PHP_VERSION=$newPhpVersion" . PHP_EOL, FILE_APPEND);
} else {
	file_put_contents(getenv('GITHUB_ENV'), "UPDATED=false" . PHP_EOL, FILE_APPEND);
}
