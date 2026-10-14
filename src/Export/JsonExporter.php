<?php
declare(strict_types=1);

namespace Phonebook\Export;

final class JsonExporter implements ExporterInterface
{
    public function contentType(): string
    {
        return 'application/json; charset=utf-8';
    }

    public function render(array $contacts): string
    {
        return json_encode(['contacts' => $contacts], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . PHP_EOL;
    }
}
