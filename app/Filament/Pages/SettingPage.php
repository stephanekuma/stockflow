<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Models\StoreSetting;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class SettingPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $title = 'Settings';

    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static string $view = 'filament.pages.setting-page';

    public ?array $data = [];

    public static function getNavigationLabel(): string
    {
        return __('Settings');
    }

    public function mount(): void
    {
        $store = Filament::getTenant();

        $settings = Setting::query()
            ->get()
            ->groupBy('group')
            ->map(function ($items) {
                return $items->pluck('value', 'key');
            })
            ->toArray();

        $storeSettings = StoreSetting::query()
            ->where('store_id', $store->id)
            ->get()
            ->keyBy('setting_id');

        $mergedSettings = [];

        foreach ($settings as $group => $items) {
            foreach ($items as $key => $value) {
                $setting = Setting::where('key', $key)->first();

                $storeSetting = $storeSettings->get($setting->id);

                if ($storeSetting) {
                    $mergedSettings[$group][$key] = $storeSetting->value;
                } else {
                    $mergedSettings[$group][$key] = $value;
                }
            }
        }

        $this->form->fill($mergedSettings);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Settings')
                    ->tabs($this->generateTabs())
            ])
            ->statePath('data');
    }

    protected function generateTabs(): array
    {
        $settings = Setting::query()
            ->get()
            ->groupBy('group');

        return $settings->map(function (Collection $moduleSettings, string $module) {
            return Tab::make($module)
                ->label(str($module)->title()->replace('_', ' '))
                ->schema([
                    Grid::make()
                        ->columns(2)
                        ->schema(
                            $moduleSettings->map(function ($setting) {
                                return $this->generateField($setting);
                            })->toArray()
                        )
                ]);
        })->toArray();
    }

    public function generateField($setting)
    {
        $label = str($setting->key)->title()->replace("_", " ");
        $name = "{$setting->group}.{$setting->key}";

        return match ($setting->type) {
            'text' => TextInput::make($name)
                ->label($label),

            'boolean' => Toggle::make($name)
                ->label($label),

            'select' => Select::make($name)
                ->native(false)
                ->label($label)
                ->options(function () use ($setting) {
                    return $setting->attributes['options'];
                })
                ->searchable()
                ->placeholder('Select an option'),

            "file" => FileUpload::make($name)
                ->label($label)
                ->columnSpanFull(),

            default => TextInput::make($name)
                ->label($label)
        };
    }

    public function save(): void
    {
        $tenantId = Filament::getTenant()->id;

        foreach ($this->form->getState() as $group) {
            if (is_array($group)) {
                foreach ($group as $key => $value) {
                    $settings[$key] = $value;
                }
            }
        }
        foreach ($settings as $key => $value) {
            $setting = Setting::query()
                ->where('key', $key)
                ->first();

            if ($setting) {
                StoreSetting::query()
                    ->updateOrCreate([
                        "setting_id" => $setting->id,
                        "store_id" => $tenantId
                    ], [
                        "value" => $value
                    ]);
            }
        }

        Notification::make()
            ->title('Settings saved successfully')
            ->success()
            ->send();
    }
}
