<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\Project\AddProjectMemberDTO;
use App\DTOs\Project\StoreProjectDTO;
use App\DTOs\Project\UpdateProjectDTO;
use App\DTOs\Project\UpdateProjectMemberRoleDTO;
use App\Http\Requests\Api\Project\AddProjectMemberRequest;
use App\Http\Requests\Api\Project\StoreProjectRequest;
use App\Http\Requests\Api\Project\UpdateProjectMemberRoleRequest;
use App\Http\Requests\Api\Project\UpdateProjectRequest;
use App\Http\Resources\Api\ProjectMemberResource;
use App\Http\Resources\Api\ProjectResource;
use App\Models\Project;
use App\Models\User;
use App\Repositories\Contracts\TeamRepositoryInterface;
use App\Services\Project\ProjectService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectController extends ApiController
{
    public function __construct(
        protected ProjectService $projectService,
        protected TeamRepositoryInterface $teamRepository
    ) {}

    /**
     * Display a listing of projects the authenticated user is enrolled in.
     */
    public function index(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $filters = $request->only(['team_id', 'status']);
            $perPage = (int) $request->input('per_page', 15);
            $projects = $this->projectService->getUserProjects($request->user(), $filters, $perPage);

            return $this->jsonResponse(
                data: ProjectResource::collection($projects)->response()->getData(true),
                msg: 'Projects retrieved successfully.'
            );
        });
    }

    /**
     * Store a newly created project in a team.
     */
    public function store(StoreProjectRequest $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $dto = StoreProjectDTO::fromRequest($request);
            $team = $this->teamRepository->findById($dto->teamId);

            if (! $team) {
                throw new ModelNotFoundException('Team not found.');
            }

            $this->authorize('create', [Project::class, $team]);

            $project = $this->projectService->createProject($request->user(), $dto);

            return $this->jsonResponse(
                data: ProjectResource::make($project),
                msg: 'Project created successfully.',
                code: 201
            );
        });
    }

    /**
     * Display the specified project.
     */
    public function show(Request $request, Project $project): JsonResponse
    {
        return $this->handleApi(function () use ($project) {
            $this->authorize('view', $project);

            return $this->jsonResponse(
                data: ProjectResource::make($project->loadCount(['members', 'tasks'])),
                msg: 'Project details retrieved successfully.'
            );
        });
    }

    /**
     * Update the specified project in storage.
     */
    public function update(UpdateProjectRequest $request, Project $project): JsonResponse
    {
        return $this->handleApi(function () use ($request, $project) {
            $this->authorize('update', $project);

            $dto = UpdateProjectDTO::fromRequest($request);
            $updatedProject = $this->projectService->updateProject($project, $dto);

            return $this->jsonResponse(
                data: ProjectResource::make($updatedProject),
                msg: 'Project updated successfully.'
            );
        });
    }

    /**
     * Remove the specified project from storage.
     */
    public function destroy(Request $request, Project $project): JsonResponse
    {
        return $this->handleApi(function () use ($project) {
            $this->authorize('delete', $project);

            $this->projectService->deleteProject($project);

            return $this->jsonResponse(
                data: null,
                msg: 'Project deleted successfully.'
            );
        });
    }

    /**
     * List all members of a project.
     */
    public function members(Request $request, Project $project): JsonResponse
    {
        return $this->handleApi(function () use ($project) {
            $this->authorize('view', $project);

            $members = $this->projectService->getProjectMembers($project);

            return $this->jsonResponse(
                data: ProjectMemberResource::collection($members),
                msg: 'Project members retrieved successfully.'
            );
        });
    }

    /**
     * Add a member to the project.
     */
    public function addMember(AddProjectMemberRequest $request, Project $project): JsonResponse
    {
        return $this->handleApi(function () use ($request, $project) {
            $this->authorize('manageMembers', $project);

            $dto = AddProjectMemberDTO::fromRequest($request);
            $member = $this->projectService->addMember($project, $dto, $request->user());

            return $this->jsonResponse(
                data: ProjectMemberResource::make($member),
                msg: 'Member added to project successfully.',
                code: 201
            );
        });
    }

    /**
     * Update a member's role in the project.
     */
    public function updateMemberRole(UpdateProjectMemberRoleRequest $request, Project $project, User $user): JsonResponse
    {
        return $this->handleApi(function () use ($request, $project, $user) {
            $this->authorize('changeRole', $project);

            $dto = UpdateProjectMemberRoleDTO::fromRequest($request);
            $this->projectService->changeMemberRole($project, $user, $dto);

            return $this->jsonResponse(
                data: null,
                msg: 'Member role updated successfully.'
            );
        });
    }

    /**
     * Remove a member from the project.
     */
    public function removeMember(Request $request, Project $project, User $user): JsonResponse
    {
        return $this->handleApi(function () use ($project, $user) {
            $this->authorize('manageMembers', $project);

            $this->projectService->removeMember($project, $user);

            return $this->jsonResponse(
                data: null,
                msg: 'Member removed from project successfully.'
            );
        });
    }
}

