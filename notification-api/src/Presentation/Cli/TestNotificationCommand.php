<?php

namespace NotificationApi\Presentation\Cli;

use NotificationApi\Domain\Notification\NotificationServiceInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:notification:test',
    description: 'Send a test notification to a user.'
)]
class TestNotificationCommand extends Command
{
    private NotificationServiceInterface $notificationService;

    public function __construct(NotificationServiceInterface $notificationService)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('userId', InputArgument::REQUIRED, 'User ID')
            ->addArgument('type', InputArgument::REQUIRED, 'Notification Type (alert, reminder, info)')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Simulate sending without calling FCM');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $userId = $input->getArgument('userId');
        $type = $input->getArgument('type');
        $dryRun = $input->getOption('dry-run');

        $io->title('Sending Test Notification');
        $io->text("User: $userId");
        $io->text("Type: $type");
        $io->text('Mode: '.($dryRun ? 'Dry Run' : 'Real Send'));

        $payload = ['message' => 'This is a test notification from CLI'];

        $success = $this->notificationService->send($userId, $type, $payload, (bool) $dryRun);

        if ($success) {
            $io->success('Notification sent successfully.');

            return Command::SUCCESS;
        } else {
            $io->error('Failed to send notification. Check logs.');

            return Command::FAILURE;
        }
    }
}
