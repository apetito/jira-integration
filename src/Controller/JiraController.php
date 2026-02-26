<?php

namespace App\Controller;

use App\Service\JiraService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/jira', name: 'jira_')]
class JiraController extends AbstractController
{
    public function __construct(private readonly JiraService $jiraService)
    {
    }

    #[Route('/projects', name: 'projects', methods: ['GET'])]
    public function listProjects(): JsonResponse
    {
        $projects = $this->jiraService->listProjects();

        return $this->json($projects);
    }

    #[Route('/issues', name: 'create_issue', methods: ['POST'])]
    public function createIssue(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['projectKey']) || empty($data['summary'])) {
            return $this->json(['error' => 'Fields "projectKey" and "summary" are required.'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $issue = $this->jiraService->createIssue(
            $data['projectKey'],
            $data['summary'],
            $data['description'] ?? '',
            $data['issueType'] ?? 'Task'
        );

        return $this->json($issue, JsonResponse::HTTP_CREATED);
    }

    #[Route('/issues/{issueKey}', name: 'get_issue', methods: ['GET'])]
    public function getIssue(string $issueKey): JsonResponse
    {
        $issue = $this->jiraService->getIssue($issueKey);

        return $this->json($issue);
    }
}
