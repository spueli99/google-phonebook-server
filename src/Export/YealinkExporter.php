<?php
declare(strict_types=1);

namespace Phonebook\Export;

use Phonebook\Contacts\ContactNormalizer;

final class YealinkExporter implements ExporterInterface
{
    public function contentType(): string
    {
        return 'application/xml; charset=utf-8';
    }

    public function render(array $contacts): string
    {
        $lines = [
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<YealinkIPPhoneDirectory>',
        ];

        foreach ($contacts as $contact) {
            $name = trim((string)($contact['name'] ?? ''));
            $numbers = ContactNormalizer::phoneEntries($contact);
            if ($name === '' || !$numbers) {
                continue;
            }

            $lines[] = '  <DirectoryEntry>';
            $lines[] = '    <Name>' . self::xmlEscape($name) . '</Name>';
            foreach ($numbers as $entry) {
                $number = trim((string)$entry['number']);
                $label = trim((string)$entry['label']);
                $attribute = $label !== '' ? ' label="' . self::xmlEscape($label) . '"' : '';
                $lines[] = '    <Telephone' . $attribute . '>' . self::xmlEscape($number) . '</Telephone>';
            }
            $lines[] = '  </DirectoryEntry>';
        }

        $lines[] = '</YealinkIPPhoneDirectory>';
        return implode("\n", $lines) . "\n";
    }

    private static function xmlEscape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1 | ENT_SUBSTITUTE, 'UTF-8');
    }
}
