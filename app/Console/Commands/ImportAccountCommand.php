<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\CancelAccountImport;
use App\Actions\CreateAccountImport;
use App\Actions\StartAccountImport;
use App\Enums\AccountImportOutcomeEnum;
use App\Enums\AccountImportStatusEnum;
use App\Exceptions\ImportExceedsALimit;
use App\Exceptions\NotEnoughStorageLeft;
use App\Models\AccountImportItem;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Console\Command;
use Illuminate\Http\UploadedFile;
use InvalidArgumentException;

class ImportAccountCommand extends Command
{
    protected $signature = 'monica:import-account
        {file : Path to the export file}
        {--email= : Email address of the account holder the file goes to}
        {--vault= : Name of a vault of that account to write into}
        {--vault-name= : Name of a new vault to create and write into}
        {--me= : Identifier, as the file has it, of the contact who is the account holder}';

    protected $description = 'Import a Monica 4 export into an account, whatever the size of the file';

    public function handle(): int
    {
        $user = User::query()->where('email', (string) $this->option('email'))->first();

        if (! $user instanceof User) {
            $this->error('Nobody on this instance uses the address '.$this->option('email').'.');

            return self::FAILURE;
        }

        $path = (string) $this->argument('file');

        if (! is_readable($path)) {
            $this->error("The file {$path} cannot be read.");

            return self::FAILURE;
        }

        config(['queue.default' => 'sync']);

        try {
            $import = new CreateAccountImport(
                user: $user,
                file: new UploadedFile($path, basename($path), 'application/json', test: true),
            )->execute();
        } catch (AuthorizationException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $import->refresh();

        if ($import->status === AccountImportStatusEnum::Refused) {
            $this->error((string) $import->refusal);
            new CancelAccountImport(user: $user, import: $import)->execute();

            return self::FAILURE;
        }

        foreach ($import->summary ?? [] as $kind => $counts) {
            $this->line("Found {$counts['found']} {$kind}".($counts['left_out'] > 0 ? ", {$counts['left_out']} of which will not come over" : '').'.');
        }

        $vault = $this->option('vault') === null
            ? null
            : Vault::query()->where('account_id', $user->account_id)->where('name', (string) $this->option('vault'))->first();

        if ($this->option('vault') !== null && ! $vault instanceof Vault) {
            $this->error('That account has no vault called '.$this->option('vault').'.');
            new CancelAccountImport(user: $user, import: $import)->execute();

            return self::FAILURE;
        }

        $kindBeingWritten = null;

        AccountImportItem::saved(function (AccountImportItem $item) use ($import, &$kindBeingWritten): void {
            if ($item->account_import_id !== $import->id || $item->outcome === null || $item->kind === $kindBeingWritten) {
                return;
            }

            $kindBeingWritten = $item->kind;
            $this->line("Writing {$item->kind} items...");
        });

        try {
            new StartAccountImport(
                user: $user,
                import: $import,
                vault: $vault,
                vaultName: $vault instanceof Vault ? null : (string) ($this->option('vault-name') ?? 'Monica 4'),
                meSourceUuid: $this->option('me') ?? $import->me_source_uuid,
            )->execute();
        } catch (ImportExceedsALimit|InvalidArgumentException|AuthorizationException|NotEnoughStorageLeft $exception) {
            $this->error($exception->getMessage());

            if ($import->refresh()->status === AccountImportStatusEnum::Read) {
                new CancelAccountImport(user: $user, import: $import)->execute();
            }

            return self::FAILURE;
        }

        $import->refresh();

        if ($import->status !== AccountImportStatusEnum::Completed) {
            $this->error('The import did not finish, and what it wrote was removed.');

            return self::FAILURE;
        }

        $outcomes = $import->items()
            ->reorder()
            ->selectRaw('kind, outcome, count(*) as total')
            ->groupBy('kind', 'outcome')
            ->toBase()
            ->get();

        foreach ($outcomes->groupBy('kind') as $kind => $rows) {
            $leftOutOfKind = (int) $rows->where('outcome', AccountImportOutcomeEnum::LeftOut->value)->sum('total');
            $writtenOfKind = (int) $rows->sum('total') - $leftOutOfKind;

            $this->line("{$kind}: wrote {$writtenOfKind}, left {$leftOutOfKind} out.");
        }

        $leftOut = (int) $outcomes->where('outcome', AccountImportOutcomeEnum::LeftOut->value)->sum('total');
        $imported = (int) $outcomes->sum('total') - $leftOut;

        $this->info("Imported {$imported} items, left {$leftOut} out. The report is at ".route('settings.accountImports.show', $import).'.');

        return self::SUCCESS;
    }
}
