<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Foundation\Console\ServeCommand as BaseServeCommand;
use Symfony\Component\Console\Attribute\AsCommand;

use function Illuminate\Support\php_binary;

#[AsCommand(name: 'serve')]
class ServeCommand extends BaseServeCommand
{
    /**
     * Get the full server command including PHP upload limit overrides.
     *
     * @return array<int, string>
     */
    protected function serverCommand(): array
    {
        $server = file_exists(base_path('server.php'))
            ? base_path('server.php')
            : base_path('vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php');

        return [
            php_binary(),
            '-d',
            'upload_max_filesize=20M',
            '-d',
            'post_max_size=25M',
            '-S',
            $this->host().':'.$this->port(),
            $server,
        ];
    }
}
