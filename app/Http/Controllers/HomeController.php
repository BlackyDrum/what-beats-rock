<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\Guess;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('Home', [
            'highscore' => Auth::check() ? Auth::user()->highscore : 0,
        ]);
    }


    public function guess(Request $request)
    {
        $validated = $request->validate([
            'gid' => 'required|string',
            'prev' => 'required|string|max:64',
            'guess' => 'required|string|max:64'
        ]);

        if (!$this->isValidCustomUUID($validated['gid'])) {
            return response()->json(['message' => 'Incorrectly formed UUID.'], 400);
        }

        DB::beginTransaction();

        $currentGame = Game::firstOrCreate([
            'gid' => $validated['gid']
        ]);

        $guess = Guess::query()->where('guess', '=', strtolower($validated['guess']))->where('game_id', '=', $currentGame['id'])->first();

        if ($guess || strtolower($validated['guess']) === 'rock') {
            DB::rollBack();

            return response()->json(['message' => 'No repeated guesses! Try something else.'], 403);
        }

        $currentGuess = Guess::query()->create([
            'guess' => $validated['guess'],
            'game_id' => $currentGame['id']
        ]);

        $messages = [
            [
                'role' => 'system',
                'content' => 'We are playing an extended version of rock, paper, scissors, except players get to pick literally anything. You should decide whether each guess beats the previuos guess. You should provide very short witty and snarky explanations.'
            ],
            [
                'role' => 'user',
                'content' => "Previous Guess (old): {$validated['prev']}, Current Guess (new): {$validated['guess']}"
            ]
        ];

        $format = [
            'type' => 'json_schema',
            'json_schema' => [
                'name' => 'what-beats-rock',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'won' => [
                            'type' => 'boolean',
                        ],
                        'explanation' => [
                            'type' => 'string'
                        ],
                        'emoji' => [
                            'type' => 'string',
                            'description' => 'An emoji representing the current guess'
                        ]
                    ],
                    'required' => ['won', 'explanation', 'emoji'],
                    'additionalProperties' => false
                ],
                'strict' => true
            ]
        ];

        $response = Http::withToken(config('api.api_key'))
            ->timeout(60)
            ->post('https://openrouter.ai/api/v1/chat/completions', [
                'model' => 'gpt-4o-mini',
                'messages' => $messages,
                'response_format' => $format
            ]);

        if ($response->failed()) {
            Log::error(
                'OpenAI: Failed to send message',
                [
                    'reason' => $response->json()['error']['message'],
                    'status' => $response->status(),
                    'gid' => $currentGame['gid'],
                ]
            );

            DB::rollBack();

            return response(['message' => 'Internal Server Error'], 500);
        }

        $result = json_decode($response->json()['choices'][0]['message']['content'], true);

        if (!$result['won'] && Auth::check()) {
            $guesses = Guess::query()->where('game_id', '=', $currentGame['id'])->get();

            $currentGameScore = $guesses->count() - 1;

            if ($currentGameScore > Auth::user()->highscore) {
                User::find(Auth::id())->update([
                    'highscore' => $currentGameScore
                ]);
            }
        }

        DB::commit();

        return response()->json($result);
    }

    private function isValidCustomUUID(string $uuid): bool
    {
        $pattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[0-9a-f]{4}-[0-9a-f]{12}$/i';
        return preg_match($pattern, $uuid) === 1;
    }
}
