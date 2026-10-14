<?php
declare(strict_types=1);

namespace Phonebook\Export;

interface ExporterInterface
{
    public function contentType(): string;
    public function render(array $contacts): string;
}
