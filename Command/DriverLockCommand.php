<?php

namespace Lexik\Bundle\MaintenanceBundle\Command;

use Lexik\Bundle\MaintenanceBundle\Drivers\AbstractDriver;
use Lexik\Bundle\MaintenanceBundle\Drivers\DriverTtlInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Psr\Container\ContainerInterface;

/**
 * Create a lock action
 *
 * @package LexikMaintenanceBundle
 * @author  Gilles Gauthier <g.gauthier@lexik.fr>
 */
class DriverLockCommand extends Command
{
    /**
     * Time-to-live for maintenance (seconds).
     */
    protected ?int $ttl = null;

    /**
     * Container used to fetch the maintenance driver factory.
     */
    protected ContainerInterface $container;

    /**
     * Keep this for BC with the existing service definition
     * which calls setContainer(@service_container).
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
            ->setName('lexik:maintenance:lock')
            ->setDescription('Lock access to the site while maintenance...')
            ->addArgument(
                'ttl',
                InputArgument::OPTIONAL,
                'Overwrite time to life from your configuration, doesn\'t work with file or shm driver. Time in seconds.',
                null
            )
            ->setHelp(<<<EOT

    You can optionally set a time to life of the maintenance

   <info>%command.full_name% 3600</info>

    You can execute the lock without a warning message which you need to interact with:

    <info>%command.full_name% --no-interaction</info>

    Or

    <info>%command.full_name% 3600 -n</info>
EOT
            );
    }

    /**
     * Symfony 7-compatible execute signature.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $driver = $this->getDriver();

        if ($input->isInteractive()) {
            if (!$this->askConfirmation('WARNING! Are you sure you wish to continue? (y/n)', $input, $output)) {
                $output->writeln('<error>Maintenance cancelled!</error>');

                // Non-zero exit code on cancel
                return Command::FAILURE;
            }
        } elseif (null !== $input->getArgument('ttl')) {
            $this->ttl = (int) $input->getArgument('ttl');
        } elseif ($driver instanceof DriverTtlInterface) {
            $this->ttl = $driver->getTtl();
        }

        // Set ttl from command line if given and driver supports it
        if ($driver instanceof DriverTtlInterface) {
            $driver->setTtl($this->ttl);
        }

        $output->writeln('<info>' . $driver->getMessageLock($driver->lock()) . '</info>');

        // Always return a valid exit code (0 = success)
        return Command::SUCCESS;
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        $driver    = $this->getDriver();
        $default   = $driver->getOptions();
        $formatter = $this->getHelperSet()->get('formatter');

        if (null !== $input->getArgument('ttl') && !is_numeric($input->getArgument('ttl'))) {
            throw new \InvalidArgumentException('Time must be an integer');
        }

        $output->writeln([
            '',
            $formatter->formatBlock('You are about to launch maintenance', 'bg=red;fg=white', true),
            '',
        ]);

        $ttl = null;
        if ($driver instanceof DriverTtlInterface) {
            if (null === $input->getArgument('ttl')) {
                $output->writeln([
                    '',
                    'Do you want to redefine maintenance life time ?',
                    'If yes enter the number of seconds. Press enter to continue',
                    '',
                ]);

                $ttl = $this->askAndValidate(
                    $input,
                    $output,
                    sprintf(
                        '<info>%s</info> [<comment>Default value in your configuration: %s</comment>]%s ',
                        'Set time',
                        $driver->hasTtl() ? $driver->getTtl() : 'unlimited',
                        ':'
                    ),
                    function ($value) use ($default) {
                        if (!is_numeric($value) && null === $default) {
                            return null;
                        }

                        if (!is_numeric($value)) {
                            throw new \InvalidArgumentException('Time must be an integer');
                        }

                        return $value;
                    },
                    1,
                    $default['ttl'] ?? 0
                );
            }

            $ttl       = (int) $ttl;
            $this->ttl = $ttl ?: (int) $input->getArgument('ttl');
        } else {
            $output->writeln([
                '',
                sprintf('<fg=red>Ttl doesn\'t work with %s driver</>', get_class($driver)),
                '',
            ]);
        }
    }

    /**
     * Get driver.
     *
     * @return AbstractDriver
     */
    private function getDriver(): AbstractDriver
    {
        /** @var AbstractDriver $driver */
        $driver = $this->container
            ->get('lexik_maintenance.driver.factory')
            ->getDriver();

        return $driver;
    }

    /**
     * Use the standard QuestionHelper for confirmation (Symfony 7).
     */
    protected function askConfirmation(string $question, InputInterface $input, OutputInterface $output): bool
    {
        $helper         = $this->getHelper('question');
        $confirmation   = new ConfirmationQuestion('<question>' . $question . '</question>', true);

        return $helper->ask($input, $output, $confirmation);
    }

    /**
     * Ask a question and validate the answer using QuestionHelper.
     *
     * @param string        $question
     * @param callable      $validator
     * @param int           $attempts
     * @param mixed         $default
     */
    protected function askAndValidate(
        InputInterface $input,
        OutputInterface $output,
        string $question,
        callable $validator,
        int $attempts = 1,
        mixed $default = null
    ): mixed {
        $helper   = $this->getHelper('question');
        $questObj = new Question($question, $default);
        $questObj->setValidator($validator);
        $questObj->setMaxAttempts($attempts);

        return $helper->ask($input, $output, $questObj);
    }
}
