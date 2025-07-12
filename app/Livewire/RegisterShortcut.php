<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\CashRegister;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;

class RegisterShortcut extends Component
{
    public $confirming = false;
    public $actionType = 'open'; // 'open' or 'close'
    public $latestRegister;

    public function mount()
    {
        $storeId = Filament::getTenant()?->id;
        $this->latestRegister = CashRegister::where('store_id', $storeId)
            ->latest('created_at')
            ->first();

        // Simple check: if register exists, consider it "open"
        $this->actionType = $this->latestRegister ? 'close' : 'open';
    }

    public function confirmAction()
    {
        $this->confirming = true;
    }

    public function toggleRegister()
    {
        $storeId = Filament::getTenant()?->id;
        $userId = Auth::id();
        $register = $this->latestRegister;

        if (!$register) {
            // Create a new register
            $register = CashRegister::create([
                'store_id' => $storeId,
                'name' => 'Caisse principale',
                'initial_balance' => 0,
                'current_balance' => 0,
            ]);

            Notification::make()
                ->title('Caisse créée')
                ->success()
                ->body('Une nouvelle caisse a été créée.')
                ->send();
        } else {
            // For now, just show a message that register exists
            Notification::make()
                ->title('Caisse disponible')
                ->info()
                ->body('La caisse est disponible pour les transactions.')
                ->send();
        }

        $this->confirming = false;
        $this->mount(); // Refresh state
        $this->dispatch('register-status-updated');
    }

    public function render()
    {
        return view('livewire.register-shortcut', [
            'actionType' => $this->actionType,
            'latestRegister' => $this->latestRegister,
        ]);
    }
}
