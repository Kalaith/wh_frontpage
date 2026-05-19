<?php

declare(strict_types=1);

namespace App\Services;

final class ProjectCatalogNormalizer
{
    /**
     * Remove duplicated catalog rows caused by importing Project Roost summary
     * records alongside the older frontpage project records.
     *
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array<string, mixed>>
     */
    public function deduplicateRows(array $rows, bool $preferSourcePaths = false): array
    {
        $deduped = [];

        foreach ($rows as $row) {
            $key = $this->canonicalProjectKey($row);

            if (
                !isset($deduped[$key])
                || $this->rowScore($row, $preferSourcePaths) > $this->rowScore($deduped[$key], $preferSourcePaths)
            ) {
                $deduped[$key] = $row;
            }
        }

        $result = array_values($deduped);
        usort($result, function (array $a, array $b): int {
            $groupCompare = $this->normalizeGroupName((string)($a['group_name'] ?? 'other'))
                <=> $this->normalizeGroupName((string)($b['group_name'] ?? 'other'));

            if ($groupCompare !== 0) {
                return $groupCompare;
            }

            return strcasecmp((string)($a['title'] ?? ''), (string)($b['title'] ?? ''));
        });

        return $result;
    }

    public function normalizeGroupName(string $groupName): string
    {
        $normalized = strtolower(trim($groupName));

        return match ($normalized) {
            'game_apps', 'game-apps' => 'games',
            'rust-games', 'rustgames' => 'rust_games',
            'gdd' => 'game_design',
            '' => 'other',
            default => $normalized,
        };
    }

    public function groupLabel(string $groupName): string
    {
        return match ($this->normalizeGroupName($groupName)) {
            'apps' => 'Web Applications',
            'fiction' => 'Fiction Projects',
            'games' => 'Games',
            'rust_games' => 'Rust Games',
            'game_design' => 'Game Design',
            'templates' => 'Templates',
            default => ucwords(str_replace('_', ' ', $groupName)),
        };
    }

    public function publicPath(?string $path): ?string
    {
        $path = trim((string)$path);

        if ($path === '') {
            return null;
        }

        if (preg_match('#^https?://#i', $path) === 1) {
            return $path;
        }

        $normalized = str_replace('\\', '/', $path);
        $lower = strtolower($normalized);

        foreach (['apps', 'game_apps'] as $root) {
            $slug = $this->segmentAfterRoot($lower, $root);
            if ($slug !== null) {
                return '/' . $slug . '/';
            }
        }

        foreach (['rustgames', 'rust_games'] as $root) {
            $slug = $this->segmentAfterRoot($lower, $root);
            if ($slug !== null) {
                return '/games/' . $slug . '/';
            }
        }

        $gddSlug = $this->segmentAfterRoot($lower, 'gdd');
        if ($gddSlug !== null) {
            return '/gdd/' . $gddSlug . '/';
        }

        $parts = $this->meaningfulPathParts($lower);
        if ($parts === []) {
            return null;
        }

        if ($parts[0] === 'frontpage') {
            return '/';
        }

        if ($parts[0] === 'web' && isset($parts[1])) {
            return '/web/' . $parts[1] . '/';
        }

        return '/' . $parts[0] . '/';
    }

    public function publicStatus(?string $status): string
    {
        $rawStatus = trim((string)$status);
        $normalized = strtolower($rawStatus);

        return match ($normalized) {
            'strong', 'mvp' => 'MVP',
            'complete', 'completed', 'fully-working', 'fully working', 'published', 'production' => 'Complete',
            'concept', 'watch', 'risk', 'prototype', 'in development', 'non-working', 'planning', '' => 'Concept',
            default => $rawStatus,
        };
    }

    public function publicStage(?string $stage, string $groupName): string
    {
        $rawStage = trim((string)$stage);
        $normalized = strtolower($rawStage);

        if (in_array($normalized, ['mvp', 'prototype', 'strong', 'watch', 'risk'], true)) {
            return match ($this->normalizeGroupName($groupName)) {
                'fiction', 'game_design' => 'Static',
                'rust_games' => 'Rust',
                default => 'React',
            };
        }

        return $rawStage !== '' ? $rawStage : 'Static';
    }

    /**
     * @param array<string, mixed> $row
     */
    private function canonicalProjectKey(array $row): string
    {
        $groupName = $this->normalizeGroupName((string)($row['group_name'] ?? 'other'));
        $slug = $this->slugFromPath((string)($row['path'] ?? ''));

        if ($slug === null) {
            $slug = $this->slugifyTitle((string)($row['title'] ?? 'unknown'));
        }

        return $groupName . ':' . $slug;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function rowScore(array $row, bool $preferSourcePaths): int
    {
        $score = 0;
        $path = (string)($row['path'] ?? '');
        $title = (string)($row['title'] ?? '');

        if ($preferSourcePaths && $this->isSourceSummaryPath($path)) {
            $score += 100;
        }

        if (!$preferSourcePaths && !$this->isSourceSummaryPath($path)) {
            $score += 100;
        }

        if ($this->publicPath($path) !== null) {
            $score += 20;
        }

        if (!str_contains($title, '_') && !str_contains($title, '-')) {
            $score += 10;
        }

        if (!empty($row['repository_url'])) {
            $score += 2;
        }

        return $score;
    }

    private function isSourceSummaryPath(string $path): bool
    {
        $normalized = strtolower(str_replace('\\', '/', $path));

        return str_contains($normalized, '/webhatchery/apps/')
            || str_contains($normalized, '/webhatchery/game_apps/')
            || str_contains($normalized, '/webhatchery/gdd/');
    }

    private function slugFromPath(string $path): ?string
    {
        $normalized = strtolower(str_replace('\\', '/', trim($path)));

        foreach (['apps', 'game_apps', 'rustgames', 'rust_games', 'gdd'] as $root) {
            $slug = $this->segmentAfterRoot($normalized, $root);
            if ($slug !== null) {
                return $slug;
            }
        }

        $parts = $this->meaningfulPathParts($normalized);
        if ($parts === []) {
            return null;
        }

        if ($parts[0] === 'web' && isset($parts[1])) {
            return $parts[1];
        }

        return $parts[0];
    }

    private function segmentAfterRoot(string $path, string $root): ?string
    {
        $parts = $this->meaningfulPathParts($path);

        foreach ($parts as $index => $part) {
            if ($part === $root && isset($parts[$index + 1])) {
                return $parts[$index + 1];
            }
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    private function meaningfulPathParts(string $path): array
    {
        $parts = array_values(array_filter(
            explode('/', trim($path, '/')),
            static fn (string $part): bool => $part !== ''
                && $part !== 'frontend'
                && $part !== 'backend'
                && $part !== 'public'
        ));

        if (count($parts) >= 2 && preg_match('/^[a-z]:$/i', $parts[0]) === 1) {
            array_shift($parts);
        }

        return $parts;
    }

    private function slugifyTitle(string $title): string
    {
        $slug = strtolower(trim($title));
        $slug = preg_replace('/[^a-z0-9]+/', '_', $slug) ?? '';
        $slug = trim($slug, '_');

        return $slug !== '' ? $slug : 'unknown';
    }
}
