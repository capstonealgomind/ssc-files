<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class FaqChatService
{
    private const OFF_TOPIC_NOTICE = 'I can only help with questions about the SSCEVS election system (registration, voting, ballots, account verification, results, and using this portal). For other concerns, please contact Help & Support.';

    /**
     * @param  array<int, array{role: string, content: string}>  $history
     * @return array{reply: string, off_topic: bool}
     */
    public function ask(string $message, array $history = []): array
    {
        $apiKey = config('services.groq.key');

        if (!$apiKey) {
            throw new RuntimeException('FAQ assistant is not configured.');
        }

        $messages = [
            ['role' => 'system', 'content' => $this->systemPrompt()],
            ...$this->normalizeHistory($history),
            ['role' => 'user', 'content' => $message],
        ];

        try {
            $response = Http::withToken($apiKey)
                ->timeout(30)
                ->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model'       => config('services.groq.model'),
                    'messages'    => $messages,
                    'temperature' => 0.2,
                    'max_tokens'  => 600,
                ])
                ->throw()
                ->json();
        } catch (RequestException $e) {
            throw new RuntimeException('The FAQ assistant is temporarily unavailable. Please try again later.', 0, $e);
        }

        $reply = trim($response['choices'][0]['message']['content'] ?? '');

        if ($reply === '') {
            throw new RuntimeException('The FAQ assistant returned an empty response.');
        }

        return [
            'reply'     => $reply,
            'off_topic' => $this->isOffTopicReply($reply),
        ];
    }

    private function systemPrompt(): string
    {
        $notice = self::OFF_TOPIC_NOTICE;

        $faqs = collect([
            'Who can vote? Only registered voters with a verified account during an active election.',
            'Account verification: Pending accounts see a lock on Elections until an admin approves registration.',
            'Changing votes: Ballots cannot be changed after submission.',
            'Results: Published after the voting period ends and ballots are counted.',
            'My Votes: View submitted ballots and download ballot receipts from the My Votes page.',
            'Help & Support: Submit a support ticket; staff must approve before live chat.',
            'Profile: Update profile photo and password from My Profile.',
            'Registration: Requires student ID scan, email OTP, and admin verification.',
        ])->implode("\n");

        return <<<PROMPT
You are the SSCEVS (Student Council Election Voting System) FAQ assistant.

SCOPE — You may ONLY answer questions about:
- SSCEVS portal features (Dashboard, Elections, My Votes, Results, Announcements, Help & Support, FAQ, Profile)
- Voter registration, ID verification, OTP, and account approval
- Casting ballots, ballot receipts, and election rules on this system
- What voters and admins can do within SSCEVS

OFF-TOPIC — If the user asks about anything else (homework, general knowledge, other schools, politics unrelated to using SSCEVS, coding, personal advice, etc.), do NOT answer the question. Reply with exactly this notice and nothing else:
"{$notice}"

STYLE — Be concise, friendly, and accurate. Use the knowledge below when relevant. If unsure about SSCEVS-specific policy, say to check with the election committee or use Help & Support.

KNOWLEDGE:
{$faqs}
PROMPT;
    }

    /**
     * @param  array<int, array{role?: string, content?: string}>  $history
     * @return array<int, array{role: string, content: string}>
     */
    private function normalizeHistory(array $history): array
    {
        return collect($history)
            ->take(-8)
            ->filter(fn ($item) => in_array($item['role'] ?? '', ['user', 'assistant'], true) && filled($item['content'] ?? null))
            ->map(fn ($item) => [
                'role'    => $item['role'],
                'content' => mb_substr(strip_tags($item['content']), 0, 2000),
            ])
            ->values()
            ->all();
    }

    private function isOffTopicReply(string $reply): bool
    {
        return str_contains($reply, self::OFF_TOPIC_NOTICE)
            || str_starts_with(strtolower($reply), 'i can only help with questions about the sscevs');
    }
}
