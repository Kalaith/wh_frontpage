<?php
// tools/quest_bot.php

$token = getenv('GITHUB_TOKEN');
$issueNumber = getenv('ISSUE_NUMBER');
$labelName = getenv('LABEL_NAME');
$repoOwner = getenv('REPO_OWNER');
$repoName = getenv('REPO_NAME');

if (!$token || !$issueNumber || !$labelName) {
    die("Missing environment variables.\n");
}

$message = '';

// Determine message based on label
if ($labelName === 'quest') {
    $message = "📜 **Quest Posted!**\n\nThis issue is now a Quest. Adventurers, claim this task to earn XP and glory!";
} elseif (str_starts_with($labelName, 'difficulty:')) {
    // Optional: Only comment if it's the first difficulty label? Or just update?
    // Let's keep it simple for now. 
    // Maybe skip if difficulty is added? It's usually added with 'quest'.
    // Let's only comment on specific triggers.
    exit(0); 
} elseif ($labelName === 'boss:damage') {
    $message = "⚔️ **Attack Registered!**\n\nBy taking this issue, you are dealing damage to the active Boss. Merge a PR to strike!";
} elseif ($labelName === 'class:bug-hunter') {
    $message = "🐞 **Bug Hunt Initiated!**\n\nCalling all Bug Hunters! Squash this pest for extra rewards.";
} else {
    exit(0);
}

// Post comment via GitHub API
$url = "https://api.github.com/repos/$repoOwner/$repoName/issues/$issueNumber/comments";

$data = json_encode(['body' => $message]);

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: token $token",
    "User-Agent: WebHatchery-QuestBot",
    "Content-Type: application/json"
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if ($httpCode === 201) {
    echo "Comment posted successfully.\n";
} else {
    echo "Failed to post comment. HTTP Code: $httpCode\nResponse: $response\n";
    exit(1);
}

curl_close($ch);
