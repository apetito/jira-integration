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
        $issueField = new IssueField();
        $issueField->setProjectKey($projectKey)
            ->setSummary($summary)
            ->setDescription($description)
            ->setIssueType($issueType);

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
