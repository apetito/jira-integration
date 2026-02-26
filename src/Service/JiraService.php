<?php

namespace App\Service;

use JiraRestApi\Configuration\ArrayConfiguration;
use JiraRestApi\Issue\IssueService;
use JiraRestApi\Issue\IssueField;
use JiraRestApi\Project\ProjectService;

class JiraService
{
    private ArrayConfiguration $config;

    public function __construct(
        string $jiraHost,
        string $jiraUser,
        string $jiraToken
    ) {
        $this->config = new ArrayConfiguration([
            'jiraHost'     => $jiraHost,
            'jiraUser'     => $jiraUser,
            'jiraPassword' => $jiraToken,
        ]);
    }

    public function createIssue(string $projectKey, string $summary, string $description, string $issueType = 'Task'): array
    {
        return $this->createIssueFromInput([
            'project_key' => $projectKey,
            'summary' => $summary,
            'description_text' => $description,
            'issue_type' => $issueType,
        ]);
    }

    public function createIssueFromInput(array $jiraIssueInput, ?string $projectKeyOverride = null): array
    {
        $projectKey = $projectKeyOverride ?? (string) ($jiraIssueInput['project_key'] ?? '');
        $summary = (string) ($jiraIssueInput['summary'] ?? '');

        if ($projectKey === '' || $summary === '') {
            throw new \InvalidArgumentException('Fields "project_key" and "summary" are required to create a Jira issue.');
        }

        $issueType = (string) ($jiraIssueInput['issue_type'] ?? 'Task');
        $description = (string) ($jiraIssueInput['description_text'] ?? '');

        $issueField = new IssueField();
        $issueField->setProjectKey($projectKey)
            ->setSummary($summary)
            ->setIssueTypeAsString($issueType)
            ->setDescription($description);

        $priority = $jiraIssueInput['priority'] ?? null;
        if (is_string($priority) && $priority !== '') {
            $issueField->setPriorityNameAsString($priority);
        }

        $labels = $jiraIssueInput['labels'] ?? [];
        if (is_array($labels)) {
            foreach ($labels as $label) {
                if (is_string($label) && $label !== '') {
                    $issueField->addLabelAsString($label);
                }
            }
        }

        $issueService = new IssueService($this->config);
        $issue = $issueService->create($issueField);

        return [
            'key'  => $issue->key,
            'self' => $issue->self,
        ];
    }

    public function getIssue(string $issueKey): array
    {
        $issueService = new IssueService($this->config);
        $issue = $issueService->get($issueKey);

        return [
            'key'         => $issue->key,
            'summary'     => $issue->fields->summary,
            'description' => $issue->fields->description,
            'status'      => $issue->fields->status->name,
            'issueType'   => $issue->fields->issuetype->name,
        ];
    }

    public function listProjects(): array
    {
        $projectService = new ProjectService($this->config);
        $projects = $projectService->getAllProjects();

        $result = [];
        foreach ($projects as $project) {
            $result[] = [
                'key'  => $project->key,
                'name' => $project->name,
                'id'   => $project->id,
            ];
        }

        return $result;
    }
}
