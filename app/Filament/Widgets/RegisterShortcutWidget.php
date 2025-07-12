<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use App\Models\CashRegister;
use Filament\Facades\Filament;
use App\Services\CashRegisterService;
use Filament\Notifications\Notification;

class RegisterShortcutWidget extends Widget
{
    protected static string $view = 'filament.widgets.register-shortcut-widget';

    protected int | string | array $columnSpan = 'full';

    public $confirming = false;
    public $actionType = 'open';
    public $latestRegister;

    public function mount()
    {
        $storeId = Filament::getTenant()?->id;
        $this->latestRegister = CashRegister::where('store_id', $storeId)
            ->where('is_closed', false)
            ->latest('created_at')
            ->first();

        $this->actionType = $this->latestRegister ? 'close' : 'open';
    }

    public function confirmAction()
    {
        $this->confirming = true;
    }

    public function toggleRegister()
    {
        $storeId = Filament::getTenant()?->id;
        $register = $this->latestRegister;

        if (!$register) {
            // Create a new register with day name and 10,000 XOF initial balance
            $now = now();
            $dayName = $now->locale('fr')->dayName;
            $date = $now->format('d/m/Y');
            
            $register = CashRegister::create([
                'store_id' => $storeId,
                'name' => "Caisse {$dayName} {$date}",
                'initial_balance' => 10000,
                'current_balance' => 10000,
                'is_closed' => false,
            ]);

            Notification::make()
                ->title('Caisse créée')
                ->success()
                ->body("Une nouvelle caisse '{$register->name}' a été créée avec un solde initial de 10,000 XOF.")
                ->send();
        } else {
            // Close the register
            $this->closeRegister($register);
        }

        $this->confirming = false;
        $this->mount(); // Refresh state
    }

    protected function closeRegister(CashRegister $register)
    {
        // Close the register properly
        $register->close();

        Notification::make()
            ->title('Caisse fermée')
            ->success()
            ->body("La caisse '{$register->name}' a été fermée. Vous pouvez créer une nouvelle caisse demain.")
            ->send();
    }

    public static function canView(): bool
    {
        return true;
    }
}
