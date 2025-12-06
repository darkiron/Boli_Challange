<?php

namespace NotificationApi\Presentation\Cli;

use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use NotificationApi\Infrastructure\Persistence\Doctrine\Document\Notification;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:notification:migrate',
    description: 'Migrate notifications to new format across all databases',
)]
class MigrateNotificationCommand extends Command
{
    private $registry;
    private $logger;

    public function __construct(ManagerRegistry $registry, LoggerInterface $logger)
    {
        parent::__construct();
        $this->registry = $registry;
        $this->logger = $logger;
    }

    protected function configure(): void
    {
        $this->addOption('rollback', 'r', InputOption::VALUE_NONE, 'Rollback the migration');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $isRollback = $input->getOption('rollback');
        $action = $isRollback ? 'Rollback' : 'Migration';
        
        $io->title("Starting Notification $action");

        // List of managers as per requirements (multi-base)
        $managers = ['default', 'wellness', 'maternity'];

        foreach ($managers as $managerName) {
            $io->section("Processing Manager: $managerName");
            
            try {
                $dm = $this->registry->getManager($managerName);
            } catch (\Exception $e) {
                $io->warning("Manager $managerName not found or accessible.");
                continue;
            }
            
            $repo = $dm->getRepository(Notification::class);
            
            // We process all notifications to ensure consistency
            $total = $repo->countAll();
            
            if ($total === 0) {
                $io->note("No notifications found in $managerName.");
                continue;
            }
            
            $io->text("Found $total notifications.");
            $progressBar = new ProgressBar($output, $total);
            $progressBar->start();

            $batchSize = 1000;
            $i = 0;
            
            $iterator = $repo->iterateAll();
            
            // Attempt to start a session for transactions if supported
            $session = null;
            try {
                 $client = $dm->getClient();
                 // startSession might fail on standalone servers
                 $session = $client->startSession();
                 $session->startTransaction();
            } catch (\Throwable $e) {
                $session = null; // Transactions not supported or failed
            }

            try {
                foreach ($iterator as $notification) {
                    $i++;
                    
                    $data = $notification->getData();
                    
                    if ($isRollback) {
                        if (isset($data['version']) && $data['version'] === 2) {
                            unset($data['version']);
                            $notification->setData($data);
                        }
                    } else {
                        if (!isset($data['version'])) {
                            $data['version'] = 2;
                            $notification->setData($data);
                        }
                    }
                    
                    if (($i % $batchSize) === 0) {
                        $dm->flush();
                        if ($session) {
                            $session->commitTransaction();
                            $session->startTransaction();
                        }
                        $dm->clear();
                    }
                    $progressBar->advance();
                }
                
                $dm->flush();
                if ($session) {
                    $session->commitTransaction();
                }
                $dm->clear();
                $progressBar->finish();
                $io->newLine();
                $this->logger->info("$action finished for $managerName", ['count' => $total]);
                
            } catch (\Exception $e) {
                if ($session) {
                    $session->abortTransaction();
                }
                $io->error("Error during $action of $managerName: " . $e->getMessage());
                $this->logger->error("$action failed", ['manager' => $managerName, 'error' => $e->getMessage()]);
                return Command::FAILURE;
            }
        }

        $io->success("All $action operations completed.");
        return Command::SUCCESS;
    }
}
