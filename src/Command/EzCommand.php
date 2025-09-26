<?php

namespace Survos\SurvosEzBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand('ez:process', 'Process ez operations')]
class EzCommand
{
	public function __invoke(
		SymfonyStyle $io,
		#[Option('Reset data before processing')]
		?bool $reset = null,
	): int
	{
		$io->success('Command executed successfully!');

		return Command::SUCCESS;
	}
}
