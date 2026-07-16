<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class AivaChatService
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
            throw new RuntimeException('AIVA is not configured.');
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
                    'temperature' => 0.25,
                    'max_tokens'  => 650,
                ])
                ->throw()
                ->json();
        } catch (RequestException $e) {
            throw new RuntimeException('AIVA is temporarily unavailable. Please try again later.', 0, $e);
        }

        $reply = trim($response['choices'][0]['message']['content'] ?? '');

        if ($reply === '') {
            throw new RuntimeException('AIVA returned an empty response.');
        }

        return [
            'reply'     => $reply,
            'off_topic' => $this->isOffTopicReply($reply),
        ];
    }

    private function systemPrompt(): string
    {
        $notice = self::OFF_TOPIC_NOTICE;

        $knowledge = collect([
            'Welcome page: Guests can open Live Standing, Registration Status, Reactivate Account, Reactivation Status, Log in, and Register (when registration is open).',
            'Registration: Requires student ID scan, email OTP, and admin verification before voting access is unlocked.',
            'Account verification: Pending accounts cannot cast votes until an admin approves registration.',
            'Login: Verified voters, admins, and committee members sign in from the Log in page.',
            'Who can vote? Only registered voters with a verified account during an active election.',
            'Live Standing: Public view of current election standings when results are shown live.',
            'Reactivation: Expired voter accounts can request reactivation from Reactivate Account and check status on Reactivation Status.',
            'Changing votes: Ballots cannot be changed after submission.',
            'Results: Published after the voting period ends and ballots are counted.',
            'My Votes (after login): View submitted ballots and download ballot receipts.',
            'Help & Support (after login): Submit a support ticket; staff must approve before live chat.',
            'Profile (after login): Update profile photo and password from My Profile.',
        ])->implode("\n");

        return <<<PROMPT
You are AIVA, the floating AI assistant on the SSCEVS welcome page for Baao Community College's Student Council Election Voting System.

SCOPE — You may ONLY answer questions about:
- Using the SSCEVS public welcome portal (registration, login, Live Standing, registration status, account reactivation)
- Voter registration, ID verification, OTP, and account approval
- Casting ballots, ballot receipts, election rules, and what happens after login
- What voters, committee members, and admins can do within SSCEVS

OFF-TOPIC — If the user asks about anything else (homework, general knowledge, other schools, politics unrelated to using SSCEVS, coding, personal advice, etc.), do NOT answer the question. Reply with exactly this notice and nothing else:
"{$notice}"

STYLE — Be concise, warm, and clear. You are AIVA (not a generic FAQ bot). Prefer short steps guests can follow from the welcome page. If unsure about SSCEVS-specific policy, say to check with the election committee or use Help & Support after signing in.

KNOWLEDGE:
{$knowledge}
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
