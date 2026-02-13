<?php
// tools/daily_bounties.php
// Creates 3 rotating daily bounty issues on GitHub.

$token = getenv('GITHUB_TOKEN');
$repoOwner = getenv('REPO_OWNER');
$repoName = getenv('REPO_NAME');

if (!$token || !$repoOwner || !$repoName) {
    die("Missing environment variables.\n");
}

// Bounty templates — rotates based on day of year
$bountyPool = [
    [
        'title' => '🐞 Bug Squasher Bounty',
        'body'  => "**Daily Bounty — Bug Hunt**\n\nFind and fix any open bug today.\n\n**Reward:** 100 XP + chance at a Loot Crate\n**Class Bonus:** Bug Hunters earn 2x XP\n**Expires:** End of day (UTC)",
        'labels' => ['quest', 'difficulty:easy', 'class:bug-hunter', 'xp:small'],
    ],
    [
        'title' => '📝 Documentation Dash',
        'body'  => "**Daily Bounty — Docs Sprint**\n\nImprove documentation for any feature. Add examples, fix typos, or write missing docs.\n\n**Reward:** 75 XP\n**Class Bonus:** Scribes earn 2x XP\n**Expires:** End of day (UTC)",
        'labels' => ['quest', 'difficulty:easy', 'xp:small'],
    ],
    [
        'title' => '🎨 UI Polish Pass',
        'body'  => "**Daily Bounty — Visual Sweep**\n\nImprove any UI element: fix alignment, improve colors, add hover effects, or enhance responsiveness.\n\n**Reward:** 100 XP + title chance\n**Class Bonus:** Stylists earn 2x XP\n**Expires:** End of day (UTC)",
        'labels' => ['quest', 'difficulty:easy', 'class:stylist', 'xp:small'],
    ],
    [
        'title' => '⚡ Performance Pit Stop',
        'body'  => "**Daily Bounty — Speed Run**\n\nOptimize any slow query, reduce bundle size, or improve loading time.\n\n**Reward:** 150 XP + rare loot crate chance\n**Class Bonus:** Architects earn 2x XP\n**Expires:** End of day (UTC)",
        'labels' => ['quest', 'difficulty:medium', 'class:architect', 'xp:medium'],
    ],
    [
        'title' => '🧪 Test Coverage Climb',
        'body'  => "**Daily Bounty — Test Sprint**\n\nAdd tests to any untested function or component.\n\n**Reward:** 100 XP\n**Expires:** End of day (UTC)",
        'labels' => ['quest', 'difficulty:easy', 'xp:small'],
    ],
    [
        'title' => '🔒 Security Sweep',
        'body'  => "**Daily Bounty — Security Audit**\n\nReview and fix any potential security issue: input validation, SQL injection risks, XSS vectors.\n\n**Reward:** 200 XP + badge chance\n**Class Bonus:** Sentinels earn 2x XP\n**Expires:** End of day (UTC)",
        'labels' => ['quest', 'difficulty:medium', 'xp:medium'],
    ],
    [
        'title' => '♻️ Refactor Rumble',
        'body'  => "**Daily Bounty — Clean Code**\n\nRefactor any messy function or class. Reduce complexity, improve naming, extract methods.\n\n**Reward:** 125 XP\n**Class Bonus:** Architects earn 2x XP\n**Expires:** End of day (UTC)",
        'labels' => ['quest', 'difficulty:medium', 'class:architect', 'xp:small'],
    ],
];

$dayOfYear = (int)date('z');
$totalPool = count($bountyPool);

// Pick 3 bounties for today (rotating)
$today = [];
for ($i = 0; $i < 3; $i++) {
    $index = ($dayOfYear + $i) % $totalPool;
    $today[] = $bountyPool[$index];
}

$url = "https://api.github.com/repos/$repoOwner/$repoName/issues";

foreach ($today as $bounty) {
    $title = $bounty['title'] . ' — ' . date('M j');
    $data = json_encode([
        'title' => $title,
        'body' => $bounty['body'],
        'labels' => $bounty['labels'],
    ]);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: token $token",
        "User-Agent: WebHatchery-DailyBounties",
        "Content-Type: application/json"
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($httpCode === 201) {
        echo "Created: $title\n";
    } else {
        echo "Failed to create: $title (HTTP $httpCode)\n";
    }

    curl_close($ch);
    usleep(500000); // 500ms delay between API calls
}

echo "Daily bounties posted.\n";
