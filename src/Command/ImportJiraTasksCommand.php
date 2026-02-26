<?php

namespace App\Command;

use App\Service\JiraService;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Throwable;

#[AsCommand(
    name: 'app:jira:import-tasks',
    description: 'Imports issues from public/JSON/create-order.json into Jira.'
)]
class ImportJiraTasksCommand extends Command
{
    public function __construct(
        private readonly JiraService $jiraService,
        #[Autowire('%kernel.project_dir%')] private readonly string $projectDir
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('path', null, InputOption::VALUE_REQUIRED, 'Path to JSON folder.', 'public/JSON')
            ->addOption('project-key', null, InputOption::VALUE_OPTIONAL, 'Override project key for every imported issue.')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Maximum number of issues to import.')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Validate payloads without creating issues in Jira.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $jsonRoot = $this->resolveJsonRoot((string) $input->getOption('path'));
        if ($jsonRoot === null) {
            $io->error('JSON folder not found. Use --path with a valid directory.');

            return Command::FAILURE;
        }

        $limitOption = $input->getOption('limit');
        $limit = null;
        if ($limitOption !== null) {
            $limitValue = (string) $limitOption;
            if (!ctype_digit($limitValue) || (int) $limitValue < 1) {
                $io->error('Option --limit must be a positive integer.');

                return Command::FAILURE;
            }

            $limit = (int) $limitValue;
        }

        $projectKeyOverrideOption = $input->getOption('project-key');
        $projectKeyOverride = is_string($projectKeyOverrideOption) && $projectKeyOverrideOption !== ''
            ? $projectKeyOverrideOption
            : null;

        $dryRun = (bool) $input->getOption('dry-run');

        try {
            $createOrder = $this->decodeJsonFile($jsonRoot.'/create-order.json');
        } catch (Throwable $exception) {
            $io->error(sprintf('Could not load create-order.json: %s', $exception->getMessage()));

            return Command::FAILURE;
        }

        $createSequence = $createOrder['create_sequence'] ?? null;
        if (!is_array($createSequence)) {
            $io->error('create-order.json is invalid: "create_sequence" must be an array.');

            return Command::FAILURE;
        }

        if ($createSequence === []) {
            $io->warning('No tasks found in create-order.json.');

            return Command::SUCCESS;
        }

        $io->title('Jira task import');
        $io->text(sprintf('Source path: %s', $jsonRoot));
        if ($projectKeyOverride !== null) {
            $io->text(sprintf('Project key override: %s', $projectKeyOverride));
        }
        if ($dryRun) {
            $io->note('Dry-run mode enabled. No issue will be created in Jira.');
        }

        $rows = [];
        $processed = 0;
        $success = 0;
        $failed = 0;

        foreach ($createSequence as $item) {
            if ($limit !== null && $processed >= $limit) {
                break;
            }

            $processed++;

            if (!is_array($item)) {
                $failed++;
                $rows[] = ['N/A', '-', '-', 'failed', 'Invalid item in create_sequence'];
                continue;
            }

            $externalId = (string) ($item['external_id'] ?? 'N/A');
            $payloadFile = (string) ($item['payload_file'] ?? '');
            $payloadPath = $this->resolvePayloadPath($payloadFile, $externalId, $jsonRoot);

            if ($payloadPath === null) {
                $failed++;
                $rows[] = [$externalId, '-', '-', 'failed', 'Payload file not found'];
                continue;
            }

            try {
                $payload = $this->decodeJsonFile($payloadPath);
                $jiraIssueInput = $payload['jira_issue_input'] ?? null;

                if (!is_array($jiraIssueInput)) {
                    throw new RuntimeException('Missing "jira_issue_input" in payload.');
                }

                $summary = (string) ($jiraIssueInput['summary'] ?? '');

                if ($dryRun) {
                    $rows[] = [$externalId, '(dry-run)', $summary, 'validated', basename($payloadPath)];
                    $success++;
                    continue;
                }

                $createdIssue = $this->jiraService->createIssueFromInput($jiraIssueInput, $projectKeyOverride);
                $rows[] = [$externalId, (string) ($createdIssue['key'] ?? '-'), $summary, 'created', basename($payloadPath)];
                $success++;
            } catch (Throwable $exception) {
                $failed++;
                $rows[] = [$externalId, '-', '-', 'failed', $exception->getMessage()];
            }
        }

        $io->table(['External ID', 'Jira Key', 'Summary', 'Status', 'Details'], $rows);
        $io->text(sprintf('Processed: %d | Success: %d | Failed: %d', $processed, $success, $failed));

        if ($failed > 0) {
            $io->warning('Import completed with failures.');

            return Command::FAILURE;
        }

        $io->success($dryRun ? 'Dry-run completed successfully.' : 'Import completed successfully.');

        return Command::SUCCESS;
    }

    private function resolveJsonRoot(string $pathOption): ?string
    {
        $pathOption = trim($pathOption);
        if ($pathOption === '') {
            return null;
        }

        $path = $this->isAbsolutePath($pathOption)
            ? $pathOption
            : $this->projectDir.'/'.ltrim($pathOption, '/\\');

        $path = rtrim($path, '/\\');

        return is_dir($path) ? $path : null;
    }

    private function resolvePayloadPath(string $payloadFile, string $externalId, string $jsonRoot): ?string
    {
        $candidates = [];

        if ($payloadFile !== '') {
            $normalizedPayloadFile = ltrim($payloadFile, '/\\');

            if ($this->isAbsolutePath($payloadFile)) {
                $candidates[] = $payloadFile;
            }

            $candidates[] = $this->projectDir.'/'.$normalizedPayloadFile;
            $candidates[] = $jsonRoot.'/'.$normalizedPayloadFile;

            $legacyPath = preg_replace('#^planejamento/JSON/#', 'public/JSON/', $normalizedPayloadFile);
            if (is_string($legacyPath)) {
                $candidates[] = $this->projectDir.'/'.$legacyPath;
            }

            $candidates[] = $jsonRoot.'/issues/'.basename($normalizedPayloadFile);
        }

        if ($externalId !== '' && $externalId !== 'N/A') {
            $candidates[] = $jsonRoot.'/issues/'.strtolower($externalId).'.json';
        }

        foreach (array_values(array_unique($candidates)) as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function decodeJsonFile(string $path): array
    {
        $content = file_get_contents($path);
        if ($content === false) {
            throw new RuntimeException(sprintf('Could not read file: %s', $path));
        }

        $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($decoded)) {
            throw new RuntimeException(sprintf('Invalid JSON object in file: %s', $path));
        }

        return $decoded;
    }

    private function isAbsolutePath(string $path): bool
    {
        if ($path === '') {
            return false;
        }

        return str_starts_with($path, '/') || preg_match('/^[A-Za-z]:\\\\/', $path) === 1;
    }
}
