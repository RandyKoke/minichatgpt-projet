<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;

class CustomInstructionController extends Controller
{
    /**
     * Page instructions (que je conserve ici)
     */
    public function index()
    {
        $customInstruction = Auth::user()->customInstruction;

        return Inertia::render('Settings/Instructions', [
            'customInstruction' => $customInstruction,
        ]);
    }


      // API : On le récupère pour modal

    public function get(): JsonResponse
    {
        try {
            $customInstruction = Auth::user()->customInstruction;

            return response()->json([
                'success' => true,
                'customInstruction' => $customInstruction,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des instructions.',
            ], 500);
        }
    }


    // API : On le sauvegarde ici pour modal

    public function storeApi(Request $request): JsonResponse
    {
        $request->validate([
            'about_you' => 'nullable|string|max:2000',
            'assistant_behavior' => 'nullable|string|max:2000',
            'is_active' => 'boolean',
        ]);

        try {
            Auth::user()->customInstruction()->updateOrCreate(
                ['user_id' => Auth::id()],
                [
                    'about_you' => $request->about_you,
                    'assistant_behavior' => $request->assistant_behavior,
                    'is_active' => $request->is_active ?? true,
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Instructions personnalisées sauvegardées avec succès !',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la sauvegarde.',
            ], 500);
        }
    }


      // API : Statut des instructions

    public function status(): JsonResponse
    {
        try {
            $customInstruction = Auth::user()->customInstruction;

            return response()->json([
                'success' => true,
                'has_instructions' => $customInstruction !== null,
                'is_active' => $customInstruction ? $customInstruction->is_active : false,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du statut.',
            ], 500);
        }
    }


      // API : Suppression

    public function destroy(): JsonResponse
    {
        try {
            $customInstruction = Auth::user()->customInstruction;

            if ($customInstruction) {
                $customInstruction->delete();

                return response()->json([
                    'success' => true,
                    'message' => 'Instructions supprimées avec succès.',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Aucune instruction à supprimer.',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression.',
            ], 500);
        }
    }


      // Encore une sauvegarde ici la pour page /instructions

    public function store(Request $request)
    {
        $request->validate([
            'about_you' => 'nullable|string|max:2000',
            'assistant_behavior' => 'nullable|string|max:2000',
            'is_active' => 'boolean',
        ]);

        Auth::user()->customInstruction()->updateOrCreate(
            ['user_id' => Auth::id()],
            [
                'about_you' => $request->about_you,
                'assistant_behavior' => $request->assistant_behavior,
                'is_active' => $request->is_active ?? true,
            ]
        );

        return back()->with('message', 'Instructions personnalisées sauvegardées avec succès.');
    }
}
