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

        $this->form([
            Select::make('submission_bag_id')
                ->label(__('Periode'))
                ->required()
                ->relationship('bag', 'label'),
            ClientPointCreate::getSubmissionTypeField(),
            ClientPointCreate::getSKP2AKConversionForm(),
            ClientPointCreate::getSKPAccumulation(),
            ClientPointCreate::getFinalAKForm(),
            ClientPointCreate::getSKP2AkFileUploadField(),
            ClientPointCreate::getAccumulatedAKFileUploadField(),
            ClientPointCreate::getFinalPAKUploadField(),
        ]);

    }
}
