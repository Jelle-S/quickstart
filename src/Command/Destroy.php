<?php

namespace Jelle_S\QuickStart\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

class Destroy extends QuickStartCommand
{

    protected function configure()
    {
        $this
            ->setName('destroy')
            ->setDescription('Destroy a database and dns and apache configuration for a domain.')
            ->addOption('dns', null, InputOption::VALUE_NONE, 'Remove dns configuration from /etc/hosts')
            ->addOption('apache', null, InputOption::VALUE_NONE, 'Remove the apache virtualhost')
            ->addOption('database', null, InputOption::VALUE_NONE, 'Remove the database and database user')
            ->addArgument('domain', InputArgument::REQUIRED);
    }

    protected function confirmExecution(InputInterface $input, SymfonyStyle $io)
    {
        $dns = $input->getOption('dns');
        $apache = $input->getOption('apache');
        $database = $input->getOption('database');
        if ($dns === $apache && $apache === $database) {
            $dns = $apache = $database = true;
        }
        $destroy = ['DNS config' => $dns, 'Apache config' => $apache, 'database and user' => $database];
        $question = 'This will destroy ' . implode(', ', array_keys(array_filter($destroy))) . '. Continue?';
        return $io->confirm($question);
    }

    protected function dns($domain, SymfonyStyle $io)
    {
        $io->writeln('<info>Destroying DNS config...</info>');
        $process = Process::fromShellCommandline("sudo sed -i \"/ {$domain} .*#quickstart/d\" /etc/hosts");
        $process->run();
    }

    protected function apache($domain, SymfonyStyle $io)
    {
        $io->writeln('<info>Destroying Apache config...</info>');
        $filename = "/etc/apache2/sites-enabled/{$domain}.conf";
        if ($this->filesystem->exists($filename)) {
            $this->filesystem->remove($filename);
        }

        // Restart Apache.
        $process = Process::fromShellCommandline('sudo apachectl restart');
        $process->run();
    }

    protected function database($domain, SymfonyStyle $io)
    {
        $io->writeln('<info>Destroying database config...</info>');
        $database = str_replace('.', '_', $domain);
        // We don't escape these queries. We assume the user that has permissions to
        // execute this command knows what they're doing _and_ could access the
        // database anyways (they can read env variables and whatnot), so have no
        // reason to try sql injection with this command anyway.
        $this->database->executeQuery("DROP USER IF EXISTS {$database}@localhost");
        $this->database->executeQuery("DROP DATABASE IF EXISTS {$database}");
        $this->database->executeQuery("FLUSH PRIVILEGES");
    }
}
