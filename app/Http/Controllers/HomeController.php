<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\Guess;
use ArdaGnsrn\Ollama\Ollama;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('Home');
    }


    public function guess(Request $request)
    {
        $validated = $request->validate([
            'gid' => 'required|string',
            'prev' => 'required|string',
            'guess' => 'required|string'
        ]);

        DB::beginTransaction();

        $currentGame = Game::firstOrCreate([
            'gid' => $validated['gid']
        ]);

        $guess = Guess::query()->where('guess', '=', strtolower($validated['guess']))->where('game_id', '=', $currentGame['id'])->first();

        if ($guess || strtolower($validated['guess']) === 'rock') {
            DB::rollBack();

            return response()->json(['message' => 'No repeated guesses! Try something else.'], 400);
        }

        $currentGuess = Guess::query()->create([
            'guess' => $validated['guess'],
            'game_id' => $currentGame['id']
        ]);

        $messages = [
            [
                'role' => 'system',
                'content' => 'Welcome to the Ultimate Word Battle! You will receive two words: the previous word and the players new guess. The words can be anything! Your job is to determine which word wins based on strength or logic, following a rock-paper-scissors-style dynamic. The player won if the current guess beats the previous guess.'
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
                            'type' => 'string',
                            'description' => 'A very short fun and engaging explanation'
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

        DB::commit();

        return response()->json($result);
    }
}
