<?php

namespace App\Support;

class CommitteePageCatalog
{
    public const DEFAULT_PAGES = [
        'dashboard',
        'candidates',
    ];

    /**
     * @return array<string, array{label: string, description: string, href: string}>
     */
    public static function pages(): array
    {
        return [
            'dashboard' => [
                'label' => 'Dashboard',
                'description' => 'Overview and live activity',
                'href' => '/dashboard',
            ],
            'candidates' => [
                'label' => 'Candidates',
                'description' => 'Add and manage election candidates',
                'href' => '/candidates',
            ],
            'elections' => [
                'label' => 'Elections',
                'description' => 'Create and manage elections',
                'href' => '/elections',
            ],
            'voters' => [
                'label' => 'Voters',
                'description' => 'Voter accounts and verification',
                'href' => '/voters',
            ],
            'announcements' => [
                'label' => 'Announcements',
                'description' => 'Post and manage announcements',
                'href' => '/announcements/manage',
            ],
            'monitoring' => [
                'label' => 'Monitoring',
                'description' => 'Live voting monitoring',
                'href' => '/monitoring',
            ],
            'reports' => [
                'label' => 'Reports',
                'description' => 'Election reports and exports',
                'href' => '/reports',
            ],
            'support' => [
                'label' => 'Support',
                'description' => 'Help desk tickets',
                'href' => '/support',
            ],
            'reactivation' => [
                'label' => 'Reactivation Request',
                'description' => 'Review account reactivation requests',
                'href' => '/reactivation-requests',
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_keys(self::pages());
    }

    public static function isValid(string $key): bool
    {
        return array_key_exists($key, self::pages());
    }

    /**
     * @return list<array{key: string, label: string, description: string, href: string}>
     */
    public static function forFrontend(): array
    {
        return collect(self::pages())
            ->map(fn (array $page, string $key) => [
                'key' => $key,
                'label' => $page['label'],
                'description' => $page['description'],
                'href' => $page['href'],
            ])
            ->values()
            ->all();
    }
}
