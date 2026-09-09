<?php

namespace Database\Factories;

use App\Models\UkomApplication;
use App\Models\UkomApplicationDocument;
use App\Models\UkomDocumentType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UkomApplicationDocument>
 */
class UkomApplicationDocumentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'ukom_application_id' => UkomApplication::factory(),
            'ukom_document_type_id' => UkomDocumentType::factory(),
            'file_path' => 'ukom/'.fake()->uuid().'.pdf',
        ];
    }
}
