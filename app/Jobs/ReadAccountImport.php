<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\ReadMonicaFile;
use App\Actions\ReadMonicaFourFile;
use App\Contracts\SourceFormatVisitor;
use App\Enums\AccountImportSourceEnum;
use App\Enums\AccountImportStatusEnum;
use App\Exceptions\ImportFileIsNotReadable;
use App\Models\AccountImport;
use App\Models\AccountImportItem;
use DateTimeInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Traits\Localizable;
use JsonMachine\Exception\JsonMachineException;
use JsonMachine\Items;
use Throwable;

/**
 * Read an uploaded file into the items of its import, on a queue because a file of 512 MB takes far
 * longer than a request may. A file this instance cannot read is refused in the language of the
 * person who uploaded it, and a copy the queue starts while the reading runs waits for it to end.
 */
class ReadAccountImport implements ShouldQueue, SourceFormatVisitor
{
    use Localizable;
    use Queueable;

    public int $timeout = 3600;

    public bool $failOnTimeout = true;

    public int $maxExceptions = 1;

    public bool $deleteWhenMissingModels = true;

    public function __construct(
        public AccountImport $import,
    ) {}

    public function retryUntil(): DateTimeInterface
    {
        return now()->addDay();
    }

    /** @return list<object> */
    public function middleware(): array
    {
        return [
            new WithoutOverlapping((string) $this->import->id)->releaseAfter(60)->expireAfter($this->timeout + 60),
        ];
    }

    public function handle(): void
    {
        if ($this->import->refresh()->status !== AccountImportStatusEnum::Reading) {
            return;
        }

        $this->withLocale($this->import->user->locale ?? (string) config('app.locale'), function (): void {
            try {
                $format = $this->formatOf();
                $format->accept($this);
            } catch (ImportFileIsNotReadable $exception) {
                $this->refuse($exception->getMessage());

                return;
            }

            $isRead = AccountImport::query()
                ->whereKey($this->import->id)
                ->where('status', AccountImportStatusEnum::Reading)
                ->update([
                    'source_format' => $format,
                    'status' => AccountImportStatusEnum::Read,
                ]) === 1;

            if (! $isRead) {
                Storage::disk($this->import->disk)->deleteDirectory($this->import->directory());

                return;
            }

            Storage::disk($this->import->disk)->delete($this->import->path);
        });
    }

    public function failed(?Throwable $exception): void
    {
        $this->withLocale($this->import->user->locale ?? (string) config('app.locale'), function (): void {
            $this->refuse(__('The file could not be read. Try again, and if it fails again, export it again from where it came from.'));
        });
    }

    public function visitMonica(): void
    {
        new ReadMonicaFile(import: $this->import)->execute();
    }

    public function visitMonicaFour(): void
    {
        new ReadMonicaFourFile(import: $this->import)->execute();
    }

    private function formatOf(): AccountImportSourceEnum
    {
        $stream = Storage::disk($this->import->disk)->readStream($this->import->path);

        if (! is_resource($stream)) {
            throw new ImportFileIsNotReadable(__('The uploaded file is no longer there. Upload it again.'));
        }

        try {
            $items = Items::fromStream($stream, ['pointer' => '/version']);

            foreach ($items as $version) {
                return AccountImportSourceEnum::fromVersion($version)
                    ?? throw new ImportFileIsNotReadable(__('This file comes from a version of Monica this instance cannot read (:version).', ['version' => is_scalar($version) ? (string) $version : '']));
            }
        } catch (JsonMachineException $exception) {
            Log::warning('An import file could not be read.', ['import' => $this->import->id, 'exception' => $exception->getMessage()]);

            throw new ImportFileIsNotReadable(__('This file is not an export Monica can read.'));
        } finally {
            fclose($stream);
        }

        throw new ImportFileIsNotReadable(__('This file is not an export Monica can read.'));
    }

    private function refuse(string $reason): void
    {
        $isRefused = AccountImport::query()
            ->whereKey($this->import->id)
            ->where('status', AccountImportStatusEnum::Reading)
            ->update([
                'status' => AccountImportStatusEnum::Refused,
                'refusal' => $reason,
                'ended_at' => now(),
            ]) === 1;

        if (! $isRefused && AccountImport::query()->whereKey($this->import->id)->exists()) {
            return;
        }

        AccountImportItem::query()->where('account_import_id', $this->import->id)->delete();

        Storage::disk($this->import->disk)->deleteDirectory($this->import->directory());
    }
}
