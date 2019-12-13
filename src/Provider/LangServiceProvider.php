<?php

namespace tieume\Lang\Provider;

use Illuminate\Support\ServiceProvider;
use tieume\Lang\Command\LangCommand;

class LangServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->commands([
            LangCommand::class,
        ]);
    }
}
