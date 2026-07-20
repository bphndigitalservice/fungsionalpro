<?php

namespace App\Filament\Pages\Client\Point\Actions;

use Filament\Actions\ViewAction;
use App\Enums\PointSubmissionPeriod;
use App\Filament\Pages\Client\Point\ClientPointCreate;
use Filament\Forms\Components\Select;

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
            Select::make('submission_type')
                ->options(PointSubmissionPeriod::class)
                ->label(__('Period')),
            ClientPointCreate::getSubmissionTypeField()->disabled($disabled),
            ClientPointCreate::getSKP2AKConversionForm()->disabled($disabled),
            ClientPointCreate::getSKPAccumulation()->disabled($disabled),
            ClientPointCreate::getFinalAKForm()->disabled($disabled),
            ClientPointCreate::getSKPFileUploadField()->disabled($disabled),
            ClientPointCreate::getPerformancePredicateField()->disabled($disabled),
            ClientPointCreate::getSKP2AkFileUploadField()->disabled($disabled),
            ClientPointCreate::getAccumulatedAKFileUploadField()->disabled($disabled),
            ClientPointCreate::getFinalPAKUploadField()->disabled($disabled),
        ];
    }
}
