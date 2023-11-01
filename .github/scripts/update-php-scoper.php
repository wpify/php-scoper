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

// Check if a new release is available
$currentVersion = trim(shell_exec('git describe --tags `git rev-list --tags --max-count=1`'));
if ($newVersion !== $currentVersion) {
	// Update composer.json
	$newPhpVersion = $latestRelease['target_commitish']; // Assuming the target commitish is the PHP version
	updateComposerJson($newPhpVersion);

	// Download latest release
	downloadLatestRelease($downloadUrl);

	echo "::set-output name=updated::true";
	echo "::set-output name=new-version::$newVersion";
	echo "::set-output name=php-version::$newPhpVersion";
} else {
	echo "::set-output name=updated::false";
}
