<?php

namespace Jelle_S\QuickStart\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class Create extends QuickStartCommand
{

    protected function configure()
    {
        parent::configure();
        $this
            ->setName('create')
            ->setDescription('Create a database and dns and apache configuration for a domain.');
    }

    protected function getInputValidationConstraints(InputInterface $input) {
        $constraints = parent::getInputValidationConstraints($input);
        $dns = $input->getOption('dns');
        $apache = $input->getOption('apache');
        $database = $input->getOption('database');
        if ($dns === $apache && $apache === $database) {
            $dns = $apache = $database = true;
        }
        if ($dns) {
          $constraints[] = new Callback(function ($domain_name, ExecutionContextInterface $context){
              $hosts_file = file_get_contents('/etc/hosts');
              // Pad the domain name with spaces for searching, to prevent partial matches.
              $padded_domain_name = " {$domain_name} ";

              if (strpos($hosts_file, $padded_domain_name) !== false) {
                  $context->addViolation("DNS entry already exists for {$domain_name}.");
              }
          });
        }

        return $constraints;
    }

    protected function confirmExecution(InputInterface $input, SymfonyStyle $io)
    {
        return true;
    }

    protected function dns($domain, SymfonyStyle $io)
    {
        $io->writeln('<info>Creating DNS config...</info>');
        $entry = "127.0.0.1 {$domain} #quickstart";
        $this->filesystem->appendToFile('/etc/hosts', $entry, true);
    }

    protected function apache($domain, SymfonyStyle $io)
    {
        $io->writeln('<info>Creating Apache config...</info>');
        // Make sure the document root exists.
        $document_root = $this->websitesDir . '/' . $domain;
        if (!$this->filesystem->exists($document_root)) {
            $this->filesystem->mkdir($document_root, 0777);
        }
        if (!$this->filesystem->exists($this->websitesDir . '/logs/' . $domain)) {
            $this->filesystem->mkdir($this->websitesDir . '/logs/' . $domain, 0777);
        }
        if (!$this->filesystem->exists($this->websitesDir . '/logs/' . $domain . '/error_log')) {
            $this->filesystem->touch($this->websitesDir . '/logs/' . $domain . '/error_log');
        }
        if (!$this->filesystem->exists($this->websitesDir . '/logs/' . $domain . '/access_log')) {
            $this->filesystem->touch($this->websitesDir . '/logs/' . $domain . '/access_log');
        }

        // Create the vhost file.
        $vhost = file_get_contents(__DIR__ . '/../../Resources/templates/vhost.txt');
        $vhost = str_replace('#DOMAIN#', $domain, $vhost);
        $vhost = str_replace('#DOCROOT#', $document_root, $vhost);
        $this->filesystem->dumpFile("/etc/apache2/sites-enabled/{$domain}.conf", $vhost);

        // Enable the vhost and restart Apache.
        $process = Process::fromShellCommandline('sudo apachectl restart');
        $process->run();
    }

    protected function database($domain, SymfonyStyle $io)
    {
        $io->writeln('<info>Creating database config...</info>');
        $database = str_replace('.', '_', $domain);
        // We don't escape these queries. We assume the user that has permissions to
        // execute this command knows what they're doing _and_ could access the
        // database anyways (they can read env variables and whatnot), so have no
        // reason to try sql injection with this command anyway.
        $this->database->executeQuery("CREATE USER IF NOT EXISTS '{$database}'@'localhost' IDENTIFIED BY '{$database}' WITH MAX_QUERIES_PER_HOUR 0 MAX_CONNECTIONS_PER_HOUR 0 MAX_UPDATES_PER_HOUR 0 MAX_USER_CONNECTIONS 0");
        $this->database->executeQuery("CREATE DATABASE IF NOT EXISTS {$database}");
        $this->database->executeQuery("GRANT ALL PRIVILEGES ON {$database}.* TO '{$database}'@'localhost'");
        $this->database->executeQuery("FLUSH PRIVILEGES");
    }
}
