<?php

declare(strict_types=1);

namespace App\Commands;

use App\Support\Config;
use App\Support\SelectsADomain;
use App\Support\SelectsAnAccount;
use LaravelZero\Framework\Commands\Command;

use function Laravel\Prompts\info;
use function Laravel\Prompts\intro;
use function Laravel\Prompts\outro;
use function Laravel\Prompts\spin;

class UpdateNameservers extends Command
{
    use SelectsADomain, SelectsAnAccount;

    protected $signature = 'nameservers:update';

    protected $description = 'Command description';

    public function handle(Config $config)
    {
        intro('Update Nameservers');

        info('Select the domain you want to update the nameservers for.');
        $fromProvider = $this->selectDomain($config);

        info('Select the account you want to update the nameservers to.');
        $toProvider = $this->selectAccount($config);

        spin(function () use ($fromProvider, $toProvider) {
            $toProvider->setDomain($fromProvider->getDomain())->addDomain();
            $fromProvider->updateNameservers($toProvider->getNameservers());
        }, 'Updating nameservers...');

        $this->output->write("\e[1A"); // Move the cursor up one, spinner leaves an extra line

        outro('Nameservers updated!');
    }
}
