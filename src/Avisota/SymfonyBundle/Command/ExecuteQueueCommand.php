<?php

/**
 * Avisota newsletter and mailing system - symfony bundle
 *
 * PHP Version 5.3
 *
 * @copyright  bit3 UG 2013
 * @author     Tristan Lins <tristan.lins@bit3.de>
 * @package    avisota-core
 * @license    LGPL-3.0+
 * @link       http://avisota.org
 */

namespace Avisota\SymfonyBundle\Command;

use Avisota\Queue\ExecutionConfig;
use Avisota\Queue\QueueInterface;
use Avisota\Transport\SwiftTransport;
use Avisota\Transport\TransportInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\TableHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ExecuteQueueCommand extends ContainerAwareCommand
{
	/**
	 * @see Command
	 */
	protected function configure()
	{
		$this
			->setName('avisota:queue:execute')
			->setDescription('Execute and send messages from a queue')
			->addOption('message-limit', 0, InputOption::VALUE_OPTIONAL, 'The maximum number of messages to send.')
			->addOption(
				'time-limit',
				0,
				InputOption::VALUE_OPTIONAL,
				'The time limit for sending messages (in seconds).'
			)
			->addArgument('queue', InputArgument::REQUIRED, 'The service name of the avisota queue to execute.')
			->addArgument('transport', InputArgument::REQUIRED, 'The service name of the avisota transport to use.')
			->setHelp(
				<<<EOF
				The <info>avisota:queue:execute</info> command execute and sends all emails from a queue.

<info>php app/console avisota:queue:execute --message-limit=10 --time-limit=10 --recover-timeout=900</info>

EOF
			);
	}

	/**
	 * {@inheritdoc}
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$queueName = $input->getArgument('queue');
		$transportName = $input->getArgument('transport');

		$queue = $this->getContainer()->get($queueName);
		$transport = $this->getContainer()->get($transportName);

		if ($queue instanceof QueueInterface && $transport instanceof TransportInterface) {
			$messageLimit = (int) $input->getOption('message-limit');
			$timeLimit = (int) $input->getOption('time-limit');

			$config = new ExecutionConfig();
			if ($messageLimit) {
				$config->setMessageLimit($messageLimit);
			}
			if ($timeLimit) {
				$config->setTimeLimit($timeLimit);
			}

			$statuses = $queue->execute($transport, $config);

			$tableData = array();
			$successful = 0;
			$failed = 0;
			foreach ($statuses as $status) {
				$successful += $status->getSuccessfullySend();
				$failed += count($status->getFailedRecipients());
				$tableData[] = array(
					$status->getMessage()->getSubject(),
					$status->getSuccessfullySend(),
					count($status->getFailedRecipients())
				);
			}
			$tableData[] = array('Total:', $successful, $failed);

			/** @var TableHelper $table */
			$table = $this->getApplication()->getHelperSet()->get('table');
			$table->setHeaders(array('Message', 'Successful', 'Failed'));
			$table->setRows($tableData);
			$table->render($output);
		}
	}
}
