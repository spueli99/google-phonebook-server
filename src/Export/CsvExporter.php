<?php
declare(strict_types=1);

namespace Phonebook\Export;

use Phonebook\Contacts\ContactNormalizer;

final class CsvExporter implements ExporterInterface
{
    public function contentType(): string
    {
        return 'text/csv; charset=utf-8';
    }

    public function render(array $contacts): string
    {
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, ['Name', 'Typ', 'Rufnummer']);
        foreach ($contacts as $contact) {
            foreach (ContactNormalizer::phoneEntries($contact) as $entry) {
                fputcsv($handle, [
                    (string)($contact['name'] ?? ''),
                    (string)$entry['label'],
                    (string)$entry['number'],
                ]);
            }
        }
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);
        return (string)$csv;
    }
}
