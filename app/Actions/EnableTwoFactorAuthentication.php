<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use PragmaRX\Google2FA\Google2FA;

/**
 * Start enrolling somebody in two factor authentication: mint the secret their
 * authenticator app will share with us, and draw the square they point their
 * camera at.
 *
 * The secret is written down straight away, because the code they type back has
 * to be checked against it, but their account is not protected yet. Only
 * ConfirmTwoFactorAuthentication does that, once they have proved the app is
 * showing the right numbers. Somebody who walks away halfway through is left
 * exactly as they were.
 *
 * Each run mints a new secret, so leaving the screen and coming back gives a
 * fresh square rather than one an old, half configured app might answer.
 */
class EnableTwoFactorAuthentication
{
    private const int QR_CODE_SIZE = 190;

    private string $secret;

    public function __construct(
        private readonly User $user,
    ) {}

    /**
     * @return array{secret: string, qrCode: string}
     */
    public function execute(): array
    {
        $this->generate();

        return [
            'secret' => $this->secret,
            'qrCode' => $this->draw(),
        ];
    }

    private function generate(): void
    {
        $this->secret = new Google2FA()->generateSecretKey();

        $this->user->two_factor_secret = $this->secret;
        $this->user->two_factor_confirmed_at = null;
        $this->user->two_factor_recovery_codes = null;
        $this->user->save();
    }

    /**
     * The square, as svg written into the page rather than a file to fetch: it
     * carries a secret, so it has no business sitting at a url of its own.
     *
     * The declaration the writer puts at the top belongs to a document, not to
     * a fragment of one, and a browser reading it inside html would show it, so
     * it is cut off here.
     */
    private function draw(): string
    {
        $url = new Google2FA()->getQRCodeUrl(
            company: config('app.name'),
            holder: $this->user->email,
            secret: $this->secret,
        );

        $renderer = new ImageRenderer(
            rendererStyle: new RendererStyle(size: self::QR_CODE_SIZE, margin: 0),
            imageBackEnd: new SvgImageBackEnd,
        );

        $svg = new Writer($renderer)->writeString($url);

        return mb_substr($svg, (int) mb_strpos($svg, '<svg'));
    }
}
