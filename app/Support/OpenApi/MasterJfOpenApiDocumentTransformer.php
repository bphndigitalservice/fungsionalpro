<?php

namespace App\Support\OpenApi;

use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Dedoc\Scramble\Support\Generator\Types\IntegerType;
use Dedoc\Scramble\Support\Generator\Types\ObjectType;
use Dedoc\Scramble\Support\Generator\Types\StringType;

final class MasterJfOpenApiDocumentTransformer
{
    public static function transform(OpenApi $openApi): void
    {
        $openApi->secure(SecurityScheme::apiKey('header', 'X-Api-Key'));

        self::fixAggregateBreakdownTypes($openApi);
        self::annotateCluster($openApi);
    }

    private static function fixAggregateBreakdownTypes(OpenApi $openApi): void
    {
        $schema = $openApi->components->schemas['MasterJfSliceAggregateResource'] ?? null;

        if (! $schema instanceof Schema || ! $schema->type instanceof ObjectType) {
            return;
        }

        $countMap = (new ObjectType)->additionalProperties(new IntegerType);

        foreach (['by_jenjang', 'by_status', 'by_status_kepegawaian', 'by_pengangkatan'] as $property) {
            if ($schema->type->hasProperty($property)) {
                $schema->type->addProperty($property, clone $countMap);
            }
        }
    }

    private static function annotateCluster(OpenApi $openApi): void
    {
        $schema = $openApi->components->schemas['MasterJfGroupResource'] ?? null;

        if (! $schema instanceof Schema || ! $schema->type instanceof ObjectType) {
            return;
        }

        if ($schema->type->hasProperty('cluster')) {
            $cluster = $schema->type->getProperty('cluster');
            if ($cluster instanceof StringType) {
                $cluster->setDescription('Effective cluster: `central`, `local_province`, or `local_regency`.');
            }
        }
    }
}
