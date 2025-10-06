<?php

// Function to fetch the latest tag from humbug/php-scoper
function getLatestTag() {
	$url = 'https://api.github.com/repos/humbug/php-scoper/tags';
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_USERAGENT, 'PHP');
	$response = curl_exec($ch);
	curl_close($ch);
	$tags = json_decode($response, true);
	return $tags[0]; // Return the first (latest) tag
}

// Function to get the required PHP version from humbug/php-scoper's composer.json at a specific tag
function getRequiredPhpVersion($tag) {
	$url = "https://raw.githubusercontent.com/humbug/php-scoper/{$tag}/composer.json";
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

// Function to download the PHAR from a specific tag to the bin/ folder
function downloadPharFromTag($tag) {
	$fileName = 'bin/php-scoper.phar';
	$downloadUrl = "https://github.com/humbug/php-scoper/releases/download/{$tag}/php-scoper.phar";
	file_put_contents($fileName, fopen($downloadUrl, 'r'));
}

// Main process
$latestTag = getLatestTag();
$newVersion = $latestTag['name'];

// Get the required PHP version from the specific tag
$newPhpVersion = getRequiredPhpVersion($newVersion);

// Check if a new tag is available
$currentVersion = trim(shell_exec('git tag | tail -n1')); // Get the latest tag, or nothing if no tags exist

if (empty($currentVersion)) {
	$currentVersion = '0.0.0'; // Default version if no tags are present
}

if (version_compare($newVersion, $currentVersion, '>')) {
	echo "Current version: $currentVersion" . PHP_EOL;
	echo "New version:     $newVersion" . PHP_EOL;
	
	// Update composer.json
	updateComposerJson($newPhpVersion);

	// The current MD5
	$current = md5_file('bin/php-scoper.phar');

	// Download PHAR from tag
	downloadPharFromTag($newVersion);

	// The new MD5
	$new = md5_file('bin/php-scoper.phar');

	// Update only of MD5 is different
	if ( $current !== $new ) {
		// Use Environment Files for setting output variables
		file_put_contents(getenv('GITHUB_ENV'), "UPDATED=true" . PHP_EOL, FILE_APPEND);
		file_put_contents(getenv('GITHUB_ENV'), "NEW_VERSION=$newVersion" . PHP_EOL, FILE_APPEND);
		file_put_contents(getenv('GITHUB_ENV'), "PHP_VERSION=$newPhpVersion" . PHP_EOL, FILE_APPEND);		
	} else {
		file_put_contents(getenv('GITHUB_ENV'), "UPDATED=false" . PHP_EOL, FILE_APPEND);		
	}
} else {
	file_put_contents(getenv('GITHUB_ENV'), "UPDATED=false" . PHP_EOL, FILE_APPEND);
}
