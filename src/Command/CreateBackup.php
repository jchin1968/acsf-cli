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
      ->addOption(
        'label',
        'l',
        InputOption::VALUE_OPTIONAL,
        'The human-readable description of this backup.'
      )
      ->addOption(
        'callback_url',
        'u',
        InputOption::VALUE_OPTIONAL,
        'The callback URL, which is invoked upon completion.'
      )
      ->addOption(
        'callback_method',
        'm',
        InputOption::VALUE_OPTIONAL,
        'The callback method, "GET", or "POST". Uses "POST" if empty.'
      )
      ->addOption(
        'caller_data',
        'r',
        InputOption::VALUE_OPTIONAL,
        'Data that should be included in the callback, json encoded.'
      )
      ->addOption(
        'components',
        'c',
        InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
        'Array of components to be included in the backup. The following component names are accepted: codebase, database, public files, private files, themes. When omitting this parameter it will default to a backup with every component.'
      );
  }

  /**
   * @inheritdoc
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $io = new SymfonyStyle($input, $output);
    $site_id = $input->getArgument('site_id');

    $data = array_filter([
      'label' => $input->getOption('label'),
      'callback_url' => $input->getOption('callback_url'),
      'callback_method' => $input->getOption('callback_method'),
      'caller_data' => $input->getOption('caller_data'),
      'components' => array_map(function ($component) {
        return $component; },
        $input->getOption('components')),
     ]);

    $client = ConfigFile::load($input->getArgument('sitegroup'))->getApiClient();

    $response = $client->request('POST', "sites/$site_id/backup", [
      'headers' => [
        'Content-Type' => 'application/json'
      ]
    ]);
    $data = $response->getBody();
    $data = json_decode($data, TRUE);

    $io->success("Task created: {$data['task_id']}");
  }
}
