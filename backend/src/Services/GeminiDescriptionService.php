<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

final class GeminiDescriptionService
{
    private string $apiKey;
    private string $endpoint;

    public function __construct()
    {
        $this->apiKey = $_ENV['GEMINI_API_KEY'] ?? $_SERVER['GEMINI_API_KEY'] ?? getenv('GEMINI_API_KEY') ?: '';
        $this->endpoint = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent';

        if ($this->apiKey === '') {
            throw new RuntimeException('GEMINI_API_KEY is not configured');
        }
    }

    public function suggestFourteenWordDescription(string $title, string $existingDescription = ''): string
    {
        $title = trim($title);
        $existingDescription = trim($existingDescription);

        if ($title === '') {
            throw new RuntimeException('Project title is required');
        }

        if (strlen($title) > 200 || strlen($existingDescription) > 2000) {
            throw new RuntimeException('Input too long for AI suggestion');
        }

        $attempts = 2;
        $lastCandidate = '';

        for ($i = 0; $i < $attempts; $i++) {
            $prompt = $this->buildPrompt($title, $existingDescription);
            $candidate = $this->normalizeText($this->requestText($prompt));
            $lastCandidate = $candidate;

            $enforced = $this->enforceFourteenWords($candidate);
            if ($enforced !== null) {
                return $enforced;
            }
        }

        throw new RuntimeException(
            'Could not generate a valid 14-word description. Last output: ' . $lastCandidate
        );
    }

    private function buildPrompt(string $title, string $existingDescription): string
    {
        return "Write one concise project description using exactly 14 words.\n"
            . "Rules:\n"
            . "- Exactly 14 words.\n"
            . "- Plain sentence only.\n"
            . "- No numbering, no quotes, no markdown, no extra text.\n"
            . "- Keep it readable and clear.\n\n"
            . "Project title: {$title}\n"
            . "Current description (optional context): {$existingDescription}";
    }

    private function requestText(string $prompt): string
    {
        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ]
        ];

        $ch = curl_init($this->endpoint . '?key=' . $this->apiKey);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);

        if (($_ENV['APP_ENV'] ?? 'production') === 'development') {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        }

        $response = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $errno = curl_errno($ch);
        curl_close($ch);

        if ($response === false) {
            throw new RuntimeException("CURL Error ({$errno}): {$error}");
        }

        if ($httpCode !== 200) {
            throw new RuntimeException(
                "Failed to communicate with Gemini API (HTTP {$httpCode}): " . ($response ?: 'Empty response')
            );
        }

        $result = json_decode($response, true);
        $text = $result['candidates'][0]['content']['parts'][0]['text'] ?? null;

        if (!is_string($text) || trim($text) === '') {
            throw new RuntimeException('Gemini returned an empty response');
        }

        return $text;
    }

    private function normalizeText(string $text): string
    {
        $text = strip_tags($text);
        $text = trim($text);
        $text = preg_replace('/^[\-\*\d\.\)\s]+/u', '', $text) ?? $text;
        $text = str_replace(["\r", "\n", "\t"], ' ', $text);
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
        $text = trim($text, " \t\n\r\0\x0B\"'");

        return trim($text);
    }

    private function enforceFourteenWords(string $text): ?string
    {
        if ($text === '') {
            return null;
        }

        $words = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY);
        if (!is_array($words) || count($words) < 10) {
            return null;
        }

        // Keep good short suggestions rather than hard-failing (10-13 words).
        if (count($words) < 14) {
            return implode(' ', $words);
        }

        if (count($words) > 14) {
            $words = array_slice($words, 0, 14);
        }

        return implode(' ', $words);
    }
}
