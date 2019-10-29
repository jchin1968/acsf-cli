<?php

namespace SiteFactoryAPI\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use SiteFactoryAPI\Config\ConfigFile;

class CreateBackup extends Command {
  /**
   * @inheritdoc
   */
  protected function configure() {
    $this
      ->setName('backups:create')
      ->setDescription('Create a site backup')
      ->addArgument(
        'sitegroup',
        InputArgument::REQUIRED,
        'Combination of sitename and environment in one word. E.g. mystack01live.'
      )
      ->addArgument(
        'site_id',
        InputArgument::REQUIRED,
        'Site ID'
      )
      ->addArgument(
        'label',
        InputArgument::REQUIRED,
        'A description for this backup'
      )
      ->addArgument(
        'components',
        InputArgument::REQUIRED,
        'Components to backup. Possible values: codebase, database, public files, private files, themes. If this parameter is not provided, it will default to a backup with every component.'
      );
  }

  /**
   * @inheritdoc
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $io = new SymfonyStyle($input, $output);
    $site_id = $input->getArgument('site_id');
    $label = $input->getArgument('label');

    $client = ConfigFile::load($input->getArgument('sitegroup'))->getApiClient();

    $response = $client->request('DELETE', "sites/$site_id/backups/$backup_id", [
      'headers' => [
        'Content-Type' => 'application/json'
      ]
    ]);
    $data = $response->getBody();
    $data = json_decode($data, TRUE);

    $io->success("Task created: {$data['task_id']}");
  }
}
