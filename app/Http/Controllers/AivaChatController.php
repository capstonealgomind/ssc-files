<?php

namespace App\Http\Controllers;

use App\Services\AivaChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class AivaChatController extends Controller
{
    public function __construct(private AivaChatService $aivaChat) {}

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message'           => 'required|string|max:1000',
            'history'           => 'nullable|array|max:8',
            'history.*.role'    => 'required_with:history|in:user,assistant',
            'history.*.content' => 'required_with:history|string|max:2000',
        ]);

        try {
            $result = $this->aivaChat->ask(
                $validated['message'],
                $validated['history'] ?? [],
            );
        } catch (RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 503);
        }

        return response()->json($result);
    }
}
