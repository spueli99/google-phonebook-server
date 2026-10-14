<?php
declare(strict_types=1);

namespace Phonebook\Contacts;

final class ContactNormalizer
{
    private const TYPE_LABELS = [
        'mobile' => 'Mobil',
        'home' => 'Privat',
        'work' => 'Büro',
        'main' => 'Hauptnummer',
        'home_fax' => 'Fax privat',
        'work_fax' => 'Fax Büro',
        'other_fax' => 'Fax',
        'pager' => 'Pager',
        'work_mobile' => 'Mobil Büro',
        'work_pager' => 'Pager Büro',
        'assistant' => 'Assistenz',
        'callback' => 'Rückruf',
        'car' => 'Auto',
        'company_main' => 'Firma',
        'isdn' => 'ISDN',
        'radio' => 'Funk',
        'telex' => 'Telex',
        'tty_tdd' => 'TTY/TDD',
        'other' => 'Sonstige',
    ];

    public static function normalizePhone(?string $value, string $countryCode = '49'): string
    {
        $raw = trim((string)$value);
        if ($raw === '') {
            return '';
        }

        $lower = strtolower($raw);
        if (str_starts_with($lower, 'sips:')) {
            $raw = substr($raw, 5);
        } elseif (str_starts_with($lower, 'sip:') || str_starts_with($lower, 'tel:')) {
            $raw = substr($raw, 4);
        }

        $raw = explode('@', $raw, 2)[0];
        $raw = explode(';', $raw, 2)[0];
        $raw = explode('?', $raw, 2)[0];

        $digits = preg_replace('/\D+/', '', $raw) ?? '';
        if ($digits === '') {
            return '';
        }

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        $cc = preg_replace('/\D+/', '', $countryCode) ?? '';
        if ($cc !== '' && str_starts_with($digits, '0')) {
            $digits = $cc . substr($digits, 1);
        }

        return $digits === '' ? '' : '+' . $digits;
    }

    public static function phoneLabel(?string $type, ?string $formattedType = null): string
    {
        $formatted = trim((string)$formattedType);
        if ($formatted !== '') {
            return substr($formatted, 0, 40);
        }

        $normalizedType = strtolower(trim((string)$type));
        if ($normalizedType === '') {
            return 'Telefon';
        }

        return self::TYPE_LABELS[$normalizedType]
            ?? substr(ucfirst(str_replace('_', ' ', $normalizedType)), 0, 40);
    }

    /** @return list<array{number:string,label:string,type:string}> */
    public static function phoneEntries(array $contact): array
    {
        $result = [];
        foreach (($contact['numbers'] ?? []) as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $number = trim((string)($entry['number'] ?? ''));
            if ($number === '') {
                continue;
            }

            $result[] = [
                'number' => $number,
                'label' => trim((string)($entry['label'] ?? '')) ?: 'Telefon',
                'type' => trim((string)($entry['type'] ?? '')),
            ];
        }
        return $result;
    }
}
