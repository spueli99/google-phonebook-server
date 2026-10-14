<?php
declare(strict_types=1);

namespace Phonebook\Sync;

use Phonebook\Google\GoogleContactsClient;
use Phonebook\Storage\ContactRepository;
use Phonebook\Storage\StateRepository;
use Phonebook\Storage\SyncHistoryRepository;
use Throwable;

final class SyncService
{
    public function __construct(
        private readonly GoogleContactsClient $google,
        private readonly ContactRepository $contacts,
        private readonly StateRepository $state,
        private readonly SyncHistoryRepository $history,
    ) {
    }

    /** @return array{contacts:int,numbers:int,duration_ms:int} */
    public function run(string $source = 'manual'): array
    {
        $started = microtime(true);
        try {
            $contacts = $this->google->fetch();
            $numberCount = 0;
            foreach ($contacts as $contact) {
                $numberCount += count($contact['numbers'] ?? []);
            }
            $this->contacts->save($contacts);
            $this->state->success(count($contacts), $numberCount);
            $duration = microtime(true) - $started;
            $this->history->add($source, true, count($contacts), $numberCount, $duration);
            return [
                'contacts' => count($contacts),
                'numbers' => $numberCount,
                'duration_ms' => (int)round($duration * 1000),
            ];
        } catch (Throwable $e) {
            $duration = microtime(true) - $started;
            $state = $this->state->all();
            $this->state->failure($e->getMessage());
            $this->history->add(
                $source,
                false,
                (int)($state['contacts'] ?? 0),
                (int)($state['numbers'] ?? 0),
                $duration,
                $e->getMessage()
            );
            throw $e;
        }
    }
}
