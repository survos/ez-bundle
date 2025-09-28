<?php

namespace Survos\EzBundle\Command;

use Survos\EzBundle\Service\EzService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand('ez:show', 'Process ez operations')]
class EzCommand
{
    public function __construct(private EzService $ezService)
    {
    }

    public function __invoke(
		SymfonyStyle $io,
		#[Option('Reset data before processing')]
		?bool $reset = null,
	): int
	{
        dd($this->ezService->all());
		$io->success('Command executed successfully!');

		return Command::SUCCESS;
	}
}
