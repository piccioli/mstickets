<?php

declare(strict_types=1);

namespace App\Filament\Auth\Pages;

use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Filament\Auth\Pages\PasswordReset\RequestPasswordReset as BaseRequestPasswordReset;
use Filament\Facades\Filament;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Auth\Events\PasswordResetLinkSent;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Support\Facades\Password;
use LogicException;
use SensitiveParameter;

/**
 * Sovrascrive la view (design "Login Montagna Servizi", v0.3.0) e riproduce lo step 1→2
 * del mockup (form richiesta → pannello "Controlla la casella") DENTRO la stessa pagina,
 * tramite lo stato `$linkSent`. Il metodo nativo `request()` (vendor) chiama sempre
 * `$this->form->fill()` in fondo per svuotare il campo email — comportamento corretto per
 * il form nativo di Filament, ma incompatibile con la vista custom che deve MOSTRARE
 * l'email appena inserita nel pannello "Controlla la casella" (§6.1 del PRD). Per questo
 * il metodo è replicato qui (stessa chiamata al broker, stesso rate limiting via
 * `WithRateLimiting::rateLimit()` già ereditato, stessa notifica) invece di essere
 * chiamato con `parent::request()` — nessuna logica di sicurezza è stata modificata,
 * solo la gestione dello stato post-invio.
 */
class RequestPasswordReset extends BaseRequestPasswordReset
{
    protected string $view = 'filament.auth.request-password-reset';

    protected static string $layout = 'filament.auth.layout';

    public bool $linkSent = false;

    public ?string $sentEmail = null;

    public function request(): void
    {
        try {
            $this->rateLimit(2);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return;
        }

        $data = $this->form->getState();

        $status = Password::broker(Filament::getAuthPasswordBroker())->sendResetLink(
            $this->getCredentialsFromFormData($data),
            function (CanResetPassword $user, #[SensitiveParameter] string $token): void {
                if (
                    ($user instanceof FilamentUser) &&
                    (! $user->canAccessPanel(Filament::getCurrentOrDefaultPanel()))
                ) {
                    return;
                }

                if (! method_exists($user, 'notify')) {
                    $userClass = $user::class;

                    throw new LogicException("Model [{$userClass}] does not have a [notify()] method.");
                }

                $notification = app(ResetPasswordNotification::class, ['token' => $token]);
                $notification->url = Filament::getResetPasswordUrl($token, $user);

                $user->notify($notification);

                if (class_exists(PasswordResetLinkSent::class)) {
                    event(new PasswordResetLinkSent($user));
                }
            },
        );

        if ($status !== Password::RESET_LINK_SENT) {
            $this->getFailureNotification($status)?->send();

            return;
        }

        $this->sentEmail = $data['email'];
        $this->linkSent = true;
    }

    public function resend(): void
    {
        $this->linkSent = false;

        $this->request();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function getCredentialsFromFormData(array $data): array
    {
        return ['email' => $data['email']];
    }
}
