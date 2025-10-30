<?php

namespace Jelle_S\QuickStart\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Validation;

class DrupalSettings extends Command
{

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var string
     */
    protected $websitesDir;

    public function __construct()
    {
        parent::__construct();
        $this->filesystem = new Filesystem();
        $this->websitesDir = getenv('QUICKSTART_WEBSITES_DIR') ?: getenv('HOME') . '/websites';
    }

    protected function configure()
    {
        parent::configure();
        $this->setName('drupal-settings')
            ->addArgument('shortname', InputArgument::OPTIONAL, 'The short of the project', '');
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

        $shortname = $input->getArgument('shortname');
        $projectRoot = $this->websitesDir . '/' . $shortname;
        if (!$this->filesystem->exists($projectRoot . '/web/sites/default/settings.php')) {
            if (!$this->filesystem->exists($projectRoot . '/web/sites/default/default.settings.php')) {
                $io->error('The settings.php and default.settings.php files are missing. Did you forget to execute composer install?');
                return Command::FAILURE;
            }
            $this->filesystem->copy($projectRoot . '/web/sites/default/default.settings.php', $projectRoot . '/web/sites/default/settings.php');
        }

        $settings = PHP_EOL . file_get_contents(__DIR__ . '/../../Resources/templates/drupalsettings.txt');
        $settings = str_replace('#SHORTNAME#', str_replace('.', '_', $shortname), $settings);
        $settings = str_replace('#DATABASEHOST#', getenv('QUICKSTART_DATABASE_HOST') ?: 'localhost', $settings);
        $settings = str_replace('#DATABASEDRIVER#', str_replace('pdo_', '', getenv('QUICKSTART_DATABASE_DRIVER') ?: 'pdo_mysql'), $settings);
        $settings = str_replace('#HASHSALT#', base64_encode(random_bytes(55)), $settings);
        $this->filesystem->appendToFile($projectRoot . '/web/sites/default/settings.php', $settings);

        return Command::SUCCESS;
    }


    protected function validateInput(InputInterface $input)
    {
        $shortname = $input->getArgument('shortname');
        $validator = Validation::createValidator();
        $violations = $validator->validate($shortname, $this->getInputValidationConstraints($input));

        return $violations;
    }

    protected function getInputValidationConstraints(InputInterface $input)
    {
        $constraints = [
            new Length(min: 3, max: 16, maxMessage: 'Must be fewer than 16 characters long for mysql username to work.', minMessage: 'Must be at least 3 characters long'),
        ];

        return $constraints;
    }
}
