<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdvanceFee;
use App\Models\ParentAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ParentWalletController extends Controller
{
    /**
     * Get wallet balance for authenticated parent
     * 
     * GET /api/parent/wallet
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function balance(Request $request): JsonResponse
    {
        try {
            $parent = $request->user();

            if (!$parent || !($parent instanceof ParentAccount)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid user type. Parent authentication required.',
                    'token' => null,
                ], 403);
            }

            // Find parent's AdvanceFee record
            $advanceFee = $this->findAdvanceFee($parent);

            if (!$advanceFee) {
                return response()->json([
                    'success' => true,
                    'message' => 'Wallet not found. Wallet will be created when you make your first top-up.',
                    'data' => [
                        'available_balance' => 0.00,
                        'total_increase' => 0.00,
                        'total_decrease' => 0.00,
                        'wallet_id' => null,
                    ],
                    'token' => $request->user()->currentAccessToken()->token ?? null,
                ], 200);
            }

            return response()->json([
                'success' => true,
                'message' => 'Wallet balance retrieved successfully.',
                'data' => [
                    'wallet_id' => $advanceFee->id,
                    'available_balance' => (float) ($advanceFee->available_credit ?? 0),
                    'total_increase' => (float) ($advanceFee->increase ?? 0),
                    'total_decrease' => (float) ($advanceFee->decrease ?? 0),
                    'formatted_balance' => 'Rs. ' . number_format($advanceFee->available_credit ?? 0, 2),
                    'name' => $advanceFee->name ?? $parent->name,
                    'phone' => $advanceFee->phone ?? $parent->phone,
                    'email' => $advanceFee->email ?? $parent->email,
                ],
                'token' => $request->user()->currentAccessToken()->token ?? null,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving wallet balance: ' . $e->getMessage(),
                'token' => null,
            ], 500);
        }
    }

    /**
     * Top-up wallet (add money to wallet)
     * Note: In production, this might require admin approval or payment gateway integration
     * 
     * POST /api/parent/wallet/topup
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function topup(Request $request): JsonResponse
    {
        try {
            $parent = $request->user();

            if (!$parent || !($parent instanceof ParentAccount)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid user type. Parent authentication required.',
                    'token' => null,
                ], 403);
            }

            $validated = $request->validate([
                'amount' => ['required', 'numeric', 'min:1'],
                'description' => ['nullable', 'string', 'max:500'],
            ]);

            $amount = (float) $validated['amount'];

            // Find or create parent's AdvanceFee record
            $advanceFee = $this->findAdvanceFee($parent);

            if (!$advanceFee) {
                // Create new wallet record
                $advanceFee = AdvanceFee::create([
                    'parent_id' => (string) $parent->id,
                    'name' => $parent->name,
                    'email' => $parent->email,
                    'phone' => $parent->phone,
                    'id_card_number' => $parent->id_card_number,
                    'available_credit' => $amount,
                    'increase' => $amount,
                    'decrease' => 0,
                ]);
            } else {
                // Update existing wallet
                $currentCredit = (float) ($advanceFee->available_credit ?? 0);
                $currentIncrease = (float) ($advanceFee->increase ?? 0);
                
                $advanceFee->available_credit = $currentCredit + $amount;
                $advanceFee->increase = $currentIncrease + $amount;
                $advanceFee->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Wallet topped up successfully.',
                'data' => [
                    'wallet_id' => $advanceFee->id,
                    'amount_added' => $amount,
                    'previous_balance' => (float) ($advanceFee->available_credit ?? 0) - $amount,
                    'new_balance' => (float) ($advanceFee->available_credit ?? 0),
                    'formatted_new_balance' => 'Rs. ' . number_format($advanceFee->available_credit ?? 0, 2),
                    'description' => $validated['description'] ?? null,
                ],
                'token' => $request->user()->currentAccessToken()->token ?? null,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while topping up wallet: ' . $e->getMessage(),
                'token' => null,
            ], 500);
        }
    }

    /**
     * Pay from wallet (deduct money for fee payment)
     * 
     * POST /api/parent/wallet/pay
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function pay(Request $request): JsonResponse
    {
        try {
            $parent = $request->user();

            if (!$parent || !($parent instanceof ParentAccount)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid user type. Parent authentication required.',
                    'token' => null,
                ], 403);
            }

            $validated = $request->validate([
                'amount' => ['required', 'numeric', 'min:0.01'],
                'student_id' => ['nullable', 'integer', 'exists:students,id'],
                'description' => ['nullable', 'string', 'max:500'],
            ]);

            $amount = (float) $validated['amount'];

            // Find parent's AdvanceFee record
            $advanceFee = $this->findAdvanceFee($parent);

            if (!$advanceFee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Wallet not found. Please top-up your wallet first.',
                    'token' => null,
                ], 404);
            }

            $availableCredit = (float) ($advanceFee->available_credit ?? 0);

            if ($availableCredit < $amount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient wallet balance. Available: Rs. ' . number_format($availableCredit, 2) . ', Required: Rs. ' . number_format($amount, 2),
                    'data' => [
                        'available_balance' => $availableCredit,
                        'required_amount' => $amount,
                        'shortage' => $amount - $availableCredit,
                    ],
                    'token' => $request->user()->currentAccessToken()->token ?? null,
                ], 400);
            }

            // Verify student belongs to parent (if student_id provided)
            if (!empty($validated['student_id'])) {
                $student = $parent->students()->where('id', $validated['student_id'])->first();
                if (!$student) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Student not found or not associated with this parent account.',
                        'token' => null,
                    ], 404);
                }
            }

            // Deduct from wallet
            $currentDecrease = (float) ($advanceFee->decrease ?? 0);
            $advanceFee->available_credit = max(0, $availableCredit - $amount);
            $advanceFee->decrease = $currentDecrease + $amount;
            $advanceFee->save();

            return response()->json([
                'success' => true,
                'message' => 'Payment processed successfully from wallet.',
                'data' => [
                    'wallet_id' => $advanceFee->id,
                    'amount_paid' => $amount,
                    'previous_balance' => $availableCredit,
                    'new_balance' => (float) ($advanceFee->available_credit ?? 0),
                    'formatted_new_balance' => 'Rs. ' . number_format($advanceFee->available_credit ?? 0, 2),
                    'student_id' => $validated['student_id'] ?? null,
                    'description' => $validated['description'] ?? null,
                ],
                'token' => $request->user()->currentAccessToken()->token ?? null,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing payment: ' . $e->getMessage(),
                'token' => null,
            ], 500);
        }
    }

    /**
     * Get wallet transaction history (if needed in future)
     * For now, we can show increase and decrease totals
     * 
     * GET /api/parent/wallet/history
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function history(Request $request): JsonResponse
    {
        try {
            $parent = $request->user();

            if (!$parent || !($parent instanceof ParentAccount)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid user type. Parent authentication required.',
                    'token' => null,
                ], 403);
            }

            $advanceFee = $this->findAdvanceFee($parent);

            if (!$advanceFee) {
                return response()->json([
                    'success' => true,
                    'message' => 'No wallet history found.',
                    'data' => [
                        'transactions' => [],
                        'summary' => [
                            'total_increase' => 0.00,
                            'total_decrease' => 0.00,
                            'current_balance' => 0.00,
                        ],
                    ],
                    'token' => $request->user()->currentAccessToken()->token ?? null,
                ], 200);
            }

            return response()->json([
                'success' => true,
                'message' => 'Wallet history retrieved successfully.',
                'data' => [
                    'summary' => [
                        'total_increase' => (float) ($advanceFee->increase ?? 0),
                        'total_decrease' => (float) ($advanceFee->decrease ?? 0),
                        'current_balance' => (float) ($advanceFee->available_credit ?? 0),
                        'formatted_total_increase' => 'Rs. ' . number_format($advanceFee->increase ?? 0, 2),
                        'formatted_total_decrease' => 'Rs. ' . number_format($advanceFee->decrease ?? 0, 2),
                        'formatted_current_balance' => 'Rs. ' . number_format($advanceFee->available_credit ?? 0, 2),
                    ],
                    'wallet_id' => $advanceFee->id,
                    'created_at' => $advanceFee->created_at ? $advanceFee->created_at->format('Y-m-d H:i:s') : null,
                    'updated_at' => $advanceFee->updated_at ? $advanceFee->updated_at->format('Y-m-d H:i:s') : null,
                ],
                'token' => $request->user()->currentAccessToken()->token ?? null,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving wallet history: ' . $e->getMessage(),
                'token' => null,
            ], 500);
        }
    }

    /**
     * Find AdvanceFee record for parent
     * 
     * @param ParentAccount $parent
     * @return AdvanceFee|null
     */
    private function findAdvanceFee(ParentAccount $parent): ?AdvanceFee
    {
        // First try by parent_id
        $advanceFee = AdvanceFee::where('parent_id', (string) $parent->id)->first();
        
        // If not found, try by id_card_number
        if (!$advanceFee && !empty($parent->id_card_number)) {
            $advanceFee = AdvanceFee::where('id_card_number', $parent->id_card_number)->first();
        }
        
        return $advanceFee;
    }
}
