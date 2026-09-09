<?php

namespace App\Filament\Resources\UkomApplicationResource\Pages;

use App\Filament\Resources\UkomApplicationResource;
use App\Models\UkomApplication;
use App\Services\UkomApplicationAccess;
use App\Services\UkomApplicationService;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Validation\ValidationException;

class ViewUkomApplication extends ViewRecord
{
    protected static string $resource = UkomApplicationResource::class;

    protected function getHeaderActions(): array
    {
        /** @var UkomApplication $record */
        $record = $this->getRecord();
        $access = app(UkomApplicationAccess::class);
        $user = auth()->user();

        return [
            Actions\Action::make('forward')
                ->label(__('labels.page.ukom_verification.forward'))
                ->visible(fn () => $access->canForward($user, $record))
                ->requiresConfirmation()
                ->modalHeading(__('labels.page.ukom_verification.forward'))
                ->modalDescription(__('labels.page.ukom_verification.forward_confirm'))
                ->modalSubmitActionLabel(__('labels.page.ukom_verification.forward'))
                ->action(function () use ($record) {
                    app(UkomApplicationService::class)->forward(auth()->user(), $record);
                    Notification::make()->success()->title(__('labels.page.ukom_verification.forwarded'))->send();
                    $this->record = $record->fresh();
                }),
            Actions\Action::make('accept')
                ->label('Terima')
                ->color('success')
                ->visible(fn () => $access->canFinalDecide($user, $record))
                ->requiresConfirmation()
                ->action(function () use ($record) {
                    app(UkomApplicationService::class)->accept(auth()->user(), $record);
                    Notification::make()->success()->title('Pengajuan diterima.')->send();
                    $this->record = $record->fresh();
                }),
            Actions\Action::make('reject')
                ->label('Tolak')
                ->color('danger')
                ->visible(fn () => $access->canForward($user, $record) || $access->canFinalDecide($user, $record))
                ->form([
                    Forms\Components\Textarea::make('rejection_reason')
                        ->label('Alasan')
                        ->required(),
                ])
                ->action(function (array $data) use ($record) {
                    try {
                        app(UkomApplicationService::class)->reject(auth()->user(), $record, $data['rejection_reason']);
                        Notification::make()->success()->title('Pengajuan ditolak.')->send();
                        $this->record = $record->fresh();
                    } catch (ValidationException $exception) {
                        Notification::make()->danger()->title(collect($exception->errors())->flatten()->first())->send();
                    }
                }),
        ];
    }
}
