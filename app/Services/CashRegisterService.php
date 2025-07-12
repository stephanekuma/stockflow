<?php

namespace App\Services;

use App\Models\CashRegister;
use App\Models\CashRegisterTransaction;
use Illuminate\Support\Facades\Auth;

class CashRegisterService
{
    /**
     * Record a transaction in the latest open cash register for the store.
     *
     * @param int $storeId
     * @param string $type
     * @param float $amount
     * @param int $referenceId
     * @param string|null $description
     * @return CashRegisterTransaction
     * @throws \Exception
     */
    public static function recordTransaction($storeId, $type, $amount, $referenceId, $description = null)
    {
        $register = CashRegister::where('store_id', $storeId)
            ->where('is_closed', false)
            ->latest('created_at')
            ->first();

        if (!$register) {
            throw new \Exception('Aucune caisse ouverte trouvée pour ce magasin.');
        }

        return CashRegisterTransaction::create([
            'cash_register_id' => $register->id,
            'type' => $type,
            'amount' => $amount,
            'reference_id' => $referenceId,
            'description' => $description,
            'created_by' => Auth::id(),
            // 'store_id' => $storeId,
        ]);
    }
}
