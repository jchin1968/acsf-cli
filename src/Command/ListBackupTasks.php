<?php

namespace SiteFactoryAPI\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use SiteFactoryAPI\Config\ConfigFile;

class ListBackupTasks extends Command {
  /**
   * @inheritdoc
   */
  protected function configure() {
    $this
      ->setName('backups:tasks')
      ->setDescription('List backup tasks.')
      ->addArgument(
        'sitegroup',
        InputArgument::REQUIRED,
        'Combination of sitename and environment in one word. E.g. mystack01live.'
      )
      ->addOption(
        'status',
        's',
        InputOption::VALUE_OPTIONAL,
        'Filter by status. Allowed values: processing, error, not-started',
        'all'
      )
      ->addOption(
        'limit',
        'l',
        InputOption::VALUE_OPTIONAL,
        'A positive integer (max 100).',
        10
      )      
      ->addOption(
        'page',
        'p',
        InputOption::VALUE_OPTIONAL,
        'A positive integer.',
        1
      );
  }

  /**
   * @inheritdoc
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $io = new SymfonyStyle($input, $output);

    $client = ConfigFile::load($input->getArgument('sitegroup'))->getApiClient();

    $response = $client->request('GET', "tasks", [
      'query' => [
        'page' => $input->getOption('page'),
        'limit' => $input->getOption('limit'),
        'status' => $input->getOption('status'),        
        'group' => "SiteArchive",
      ]
    ]);
    $data = $response->getBody();
    $data = json_decode($data, TRUE);

    if (empty($data)) {
      return;
    }

    // Build formatted output.
    $rows = [];
    $status = [
      4 => 'in progress',
      8 => 'waiting',
      16 => 'completed',
      32 => 'error'
    ];
    
    foreach ($data as $row) {
      $rows[] = [
        'id' => $row['id'],
        'name' => $row['name'],
        'priority' => $row['priority'],
        'status' => $status[$row['status']],
        'added' => date('Y-m-d h:i:s', $row['added']),
        'started' => date('h:i:s', $row['started']),
        'completed' => date('h:i:s', $row['completed']),
        'paused' => $row['paused'],
        'error_message' => $row['error_message'],
      ];  
    }
    $io->table(array_keys($rows[0]), $rows);
  }
}

