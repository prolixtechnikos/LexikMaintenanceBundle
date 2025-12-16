<?php

namespace Lexik\Bundle\MaintenanceBundle\Command;

use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Create an unlock action
 *
 * @package LexikMaintenanceBundle
 * @author  Gilles Gauthier
 */
class DriverUnlockCommand extends Command
{
    /**
     * Service container used to fetch the maintenance driver factory.
     */
    protected ContainerInterface $container;

    /**
     * Kept for BC with existing service definitions that call setContainer().
     */
    public function setContainer(ContainerInterface $container): void
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('lexik:maintenance:unlock')
            ->setDescription('Unlock access to the site while maintenance...')
            ->setHelp(<<<EOT
    You can execute the unlock without a warning message which you need to interact with:

    <info>%command.full_name% --no-interaction</info>
EOT
            );
    }

    /**
     * Symfony 7-compatible execute signature.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->confirmUnlock($input, $output)) {
            // User cancelled → non-zero exit code
            return Command::FAILURE;
        }

        $driver = $this->container
            ->get('lexik_maintenance.driver.factory')
            ->getDriver();

        $unlockMessage = $driver->getMessageUnlock($driver->unlock());

        $output->writeln('<info>' . $unlockMessage . '</info>');

        return Command::SUCCESS;
    }

    /**
     * Ask the user to confirm unlocking, unless running non-interactively.
     */
    protected function confirmUnlock(InputInterface $input, OutputInterface $output): bool
    {
        $formatter = $this->getHelperSet()->get('formatter');

        // Global Symfony option --no-interaction
        if ($input->getOption('no-interaction')) {
            $confirmation = true;
        } else {
            $output->writeln([
                '',
                $formatter->formatBlock('You are about to unlock your server.', 'bg=green;fg=white', true),
                '',
            ]);

            $confirmation = $this->askConfirmation(
                'WARNING! Are you sure you wish to continue? (y/n) ',
                $input,
                $output
            );
        }

        if (!$confirmation) {
            $output->writeln('<error>Action cancelled!</error>');
        }

        return $confirmation;
    }

    /**
     * Ask a yes/no question using the modern QuestionHelper API.
     */
    protected function askConfirmation(string $question, InputInterface $input, OutputInterface $output): bool
    {
        $helper       = $this->getHelper('question');
        $confirmation = new ConfirmationQuestion('<question>' . $question . '</question>', true);

        return $helper->ask($input, $output, $confirmation);
    }
}
