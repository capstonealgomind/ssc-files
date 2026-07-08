<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Schedule::command('queue:work --stop-when-empty --max-time=55')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('docs:pdf {--output=DOCUMENTATION.pdf}', function () {
    $source = base_path('DOCUMENTATION.txt');
    $output = base_path($this->option('output'));

    if (!is_file($source)) {
        $this->error('DOCUMENTATION.txt not found at project root.');

        return 1;
    }

    $content = file_get_contents($source);
    $content = strtr($content, [
        '┌' => '+', '┐' => '+', '└' => '+', '┘' => '+',
        '┬' => '+', '┴' => '+', '├' => '+', '┤' => '+', '┼' => '+',
        '│' => '|', '─' => '-',
        '▼' => 'v', '▶' => '>', '→' => '->',
        '✓' => 'Y', '•' => '*',
        '—' => '-', '–' => '-', '·' => '.',
    ]);

    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('docs.documentation', [
        'content' => $content,
    ])->setPaper('a4', 'portrait');

    file_put_contents($output, $pdf->output());

    $this->info("PDF saved to: {$output}");

    return 0;
})->purpose('Generate DOCUMENTATION.pdf from DOCUMENTATION.txt');
