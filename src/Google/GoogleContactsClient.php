<?php
declare(strict_types=1);

namespace Phonebook\Google;

use Phonebook\Contacts\ContactNormalizer;
use RuntimeException;

final class GoogleContactsClient
{
    public function __construct(
        private readonly OAuthClient $oauth,
        private readonly string $countryCode,
    ) {
    }

    public function fetch(): array
    {
        $accessToken = $this->oauth->accessToken();
        $url = 'https://people.googleapis.com/v1/people/me/connections';
        $contacts = [];
        $pageToken = null;

        do {
            $query = [
                'personFields' => 'names,phoneNumbers',
                'pageSize' => '1000',
            ];
            if ($pageToken) {
                $query['pageToken'] = $pageToken;
            }

            [$status, $body] = $this->oauth->request(
                'GET',
                $url . '?' . http_build_query($query),
                ['Authorization: Bearer ' . $accessToken]
            );
            if ($status < 200 || $status >= 300) {
                throw new RuntimeException("Google People API failed (HTTP {$status}).");
            }
            $data = json_decode($body, true);
            if (!is_array($data)) {
                throw new RuntimeException('Google People API returned invalid JSON.');
            }

            foreach (($data['connections'] ?? []) as $person) {
                $names = $person['names'] ?? [];
                $phones = $person['phoneNumbers'] ?? [];
                if (!$names || !$phones) {
                    continue;
                }
                $name = trim((string)($names[0]['displayName'] ?? ''));
                if ($name === '') {
                    continue;
                }

                $numbers = [];
                foreach ($phones as $phone) {
                    $number = ContactNormalizer::normalizePhone(
                        (string)($phone['value'] ?? ''),
                        $this->countryCode
                    );
                    if ($number === '') {
                        continue;
                    }

                    // Deduplicate by normalized number while preserving the first useful label.
                    if (!isset($numbers[$number])) {
                        $numbers[$number] = [
                            'number' => $number,
                            'label' => ContactNormalizer::phoneLabel(
                                isset($phone['type']) ? (string)$phone['type'] : null,
                                isset($phone['formattedType']) ? (string)$phone['formattedType'] : null,
                            ),
                            'type' => trim((string)($phone['type'] ?? '')),
                        ];
                    }
                }
                if (!$numbers) {
                    continue;
                }
                $contacts[] = [
                    'name' => $name,
                    'numbers' => array_values($numbers),
                ];
            }

            $pageToken = $data['nextPageToken'] ?? null;
        } while ($pageToken);

        usort($contacts, static fn(array $a, array $b): int => strcasecmp($a['name'], $b['name']));
        return $contacts;
    }
}
