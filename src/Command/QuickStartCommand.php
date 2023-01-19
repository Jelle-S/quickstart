<?php

namespace Jelle_S\QuickStart\Command;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Validation;

abstract class QuickStartCommand extends Command
{

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var Connection
     */
    protected $database;

    /**
     * @var string
     */
    protected $websitesDir;

    public function __construct()
    {
        parent::__construct();
        $this->filesystem = new Filesystem();
        $this->websitesDir = getenv('QUICKSTART_WEBSITES_DIR') ?: getenv('HOME') . '/websites';
        $this->database = DriverManager::getConnection([
            'user' => getenv('QUICKSTART_DATABASE_USER') ?: 'root',
            'password' => getenv('QUICKSTART_DATABASE_PASSWORD') ?: '',
            'host' => getenv('QUICKSTART_DATABASE_HOST') ?: 'localhost',
            'driver' => getenv('QUICKSTART_DATABASE_DRIVER') ?: 'pdo_mysql',
        ]);
    }

    protected function configure()
    {
        parent::configure();
        $this->addOption('dns', null, InputOption::VALUE_NONE, 'Add dns configuration to /etc/hosts')
            ->addOption('apache', null, InputOption::VALUE_NONE, 'Create an apache virtualhost')
            ->addOption('database', null, InputOption::VALUE_NONE, 'Create a database and database user')
            ->addArgument('domain', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $violations = $this->validateInput($input);
        if ($violations->count()) {
            foreach ($violations as $violation) {
                $io->error($violation->getMessage());
            }

            return Command::FAILURE;
        }

        if (!$this->confirmExecution($input, $io)) {
          return Command::SUCCESS;
        }

        $domain = $input->getArgument('domain');
        $dns = $input->getOption('dns');
        $apache = $input->getOption('apache');
        $database = $input->getOption('database');

        if ($dns === $apache && $apache === $database) {
            $dns = $apache = $database = true;
        }
        if ($dns) {
            $this->dns($domain, $io);
        }
        if ($apache) {
            $this->apache($domain, $io);
        }
        if ($database) {
            $this->database($domain, $io);
        }

        return Command::SUCCESS;
    }


    protected function validateInput(InputInterface $input)
    {
        $domain = $input->getArgument('domain');
        $validator = Validation::createValidator();
        $violations = $validator->validate($domain, $this->getInputValidationConstraints($input));

        return $violations;
    }

    protected function getInputValidationConstraints(InputInterface $input)
    {
        $constraints = [
            new Length(min: 3, max: 16, maxMessage: 'Must be fewer than 16 characters long for mysql username to work.', minMessage: 'Must be at least 3 characters long'),
            new Regex(pattern: '/\./', message: 'Must contain a dot.'),
        ];

        return $constraints;
    }

    abstract protected function confirmExecution(InputInterface $input, SymfonyStyle $io);
    abstract protected function dns($domain, SymfonyStyle $io);
    abstract protected function apache($domain, SymfonyStyle $io);
    abstract protected function database($domain, SymfonyStyle $io);
}
