<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NicheConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LeadIngestController extends Controller
{
    /**
     * Receive lead data from generated websites.
     * POST /api/leads
     *
     * Expected payload:
     * {
     *   "asset": "calculahipoteca.es",
     *   "data": { "name": "...", "email": "...", "phone": "..." }
     * }
     */
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'asset' => 'required|string',
            'data' => 'required|array',
            'data.name' => 'nullable|string|max:255',
            'data.email' => 'nullable|email|max:255',
            'data.phone' => 'nullable|string|max:50',
        ]);

        // Verify the asset exists
        $niche = NicheConfig::where('domain', $validated['asset'])->first();
        if (! $niche) {
            return response()->json(['error' => 'Unknown asset'], 422);
        }

        // Store in leads table (table still exists even though model was removed)
        DB::table('leads')->insert([
            'id' => Str::uuid(),
            'asset' => $validated['asset'],
            'provider' => 'website_form',
            'score' => 50, // Default score, scoring agent will update later
            'status' => 'new',
            'data' => json_encode($validated['data']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Log::info('[leads] New lead received', [
            'asset' => $validated['asset'],
            'has_email' => ! empty($validated['data']['email']),
        ]);

        return response()->json(['status' => 'ok'], 201);
    }
}
