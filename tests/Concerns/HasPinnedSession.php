<?php

declare(strict_types=1);

namespace Tests\Concerns;

use Illuminate\Cookie\Middleware\EncryptCookies;

trait HasPinnedSession
{
    public function setUpHasPinnedSession(): void
    {
        $this->withoutMiddleware(EncryptCookies::class);
        $this->withUnencryptedCookies([
            config('session.cookie') => session()->getId(),
        ]);
    }
}
