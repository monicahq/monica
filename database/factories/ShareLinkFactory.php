<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Contact;
use App\Models\ShareLink;
use App\Models\Vault;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Override;

/**
 * @extends Factory<ShareLink>
 */
class ShareLinkFactory extends Factory
{
    protected $model = ShareLink::class;

    /**
     * The account and the vault are taken from what is shared, since a link to
     * something in another compartment is nothing. Everything sensitive is out,
     * which is the state a link is really born in.
     *
     * @return array<string, mixed>
     */
    #[Override]
    public function definition(): array
    {
        return [
            'shareable_type' => Contact::class,
            'shareable_id' => Contact::factory(),
            'account_id' => fn (array $attributes): int => Contact::query()->findOrFail($attributes['shareable_id'])->account_id,
            'vault_id' => fn (array $attributes): int => Contact::query()->findOrFail($attributes['shareable_id'])->vault_id,
            'token' => hash('sha256', Str::random(ShareLink::TOKEN_LENGTH)),
            'label' => 'For the family',
            'recipient_hint' => 'Pam Beesly',
            'password_hash' => null,
            'expires_at' => null,
            'revoked_at' => null,
            'includes_files' => false,
            'includes_activities' => false,
            'includes_activity_narrative' => false,
            'includes_day_narrative' => false,
            'includes_relationships' => false,
            'includes_groups' => false,
            'includes_sensitive_fields' => false,
            'excluded_section_ids' => [],
            'excluded_activity_ids' => [],
            'view_count' => 0,
            'last_viewed_at' => null,
            'created_by_user_id' => null,
        ];
    }

    /**
     * Indicate that the link is onto a whole vault rather than onto one page.
     */
    public function forVault(?Vault $vault = null): static
    {
        return $this->state(function (array $attributes) use ($vault): array {
            $vault ??= Vault::factory()->create();

            return [
                'shareable_type' => Vault::class,
                'shareable_id' => $vault->id,
                'account_id' => $vault->account_id,
                'vault_id' => $vault->id,
            ];
        });
    }

    /**
     * Indicate that the link asks for a password before it opens.
     */
    public function withPassword(string $password = 'bears beets battlestar'): static
    {
        return $this->state(fn (array $attributes): array => [
            'password_hash' => Hash::make($password),
        ]);
    }

    /**
     * Indicate that the link has stopped working on its own.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes): array => [
            'expires_at' => now()->subDay(),
        ]);
    }

    /**
     * Indicate that the link was revoked, which is immediate and permanent.
     */
    public function revoked(): static
    {
        return $this->state(fn (array $attributes): array => [
            'revoked_at' => now()->subHour(),
        ]);
    }
}
