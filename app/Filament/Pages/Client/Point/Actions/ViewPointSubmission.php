<?php

namespace App\Filament\Pages\Client\Point\Actions;

use App\Filament\Pages\Client\Point\ClientPointCreate;
use Filament\Forms\Components\Select;
use Filament\Tables\Actions\ViewAction;

class ViewPointSubmission extends ViewAction
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->form(static::getFormSubmissionView());
    }

    public static function getFormSubmissionView(bool $disabled = false): array
    {
        return [
            Select::make('submission_bag_id')
                ->label(__('Periode'))
                ->required()
                ->relationship('bag', 'label')->disabled($disabled),
            ClientPointCreate::getSubmissionTypeField()->disabled($disabled),
            ClientPointCreate::getSKP2AKConversionForm()->disabled($disabled),
            ClientPointCreate::getSKPAccumulation()->disabled($disabled),
            ClientPointCreate::getFinalAKForm()->disabled($disabled),
            ClientPointCreate::getSKP2AkFileUploadField()->disabled($disabled),
            ClientPointCreate::getAccumulatedAKFileUploadField()->disabled($disabled),
            ClientPointCreate::getFinalPAKUploadField()->disabled($disabled),
        ];
    }
}
