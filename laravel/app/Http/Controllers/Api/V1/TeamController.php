<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\Team\AddTeamMemberDTO;
use App\DTOs\Team\StoreTeamDTO;
use App\DTOs\Team\UpdateTeamDTO;
use App\DTOs\Team\UpdateTeamMemberRoleDTO;
use App\Http\Requests\Api\Team\AddTeamMemberRequest;
use App\Http\Requests\Api\Team\StoreTeamRequest;
use App\Http\Requests\Api\Team\UpdateTeamMemberRoleRequest;
use App\Http\Requests\Api\Team\UpdateTeamRequest;
use App\Http\Resources\Api\TeamMemberResource;
use App\Http\Resources\Api\TeamResource;
use App\Models\Team;
use App\Models\User;
use App\Services\Team\TeamService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeamController extends ApiController
{
    public function __construct(
        protected TeamService $teamService
    ) {}

    /**
     * Display a listing of user teams.
     */
    public function index(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $perPage = (int) $request->input('per_page', 15);
            $teams = $this->teamService->getUserTeams($request->user(), $perPage);

            return $this->jsonResponse(
                data: TeamResource::collection($teams)->response()->getData(true),
                msg: 'Teams retrieved successfully.'
            );
        });
    }

    /**
     * Store a newly created team.
     */
    public function store(StoreTeamRequest $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $dto = StoreTeamDTO::fromRequest($request);
            $team = $this->teamService->createTeam($request->user(), $dto);

            return $this->jsonResponse(
                data: TeamResource::make($team),
                msg: 'Team created successfully.',
                code: 201
            );
        });
    }

    /**
     * Display the specified team.
     */
    public function show(Request $request, Team $team): JsonResponse
    {
        return $this->handleApi(function () use ($team) {
            $this->authorize('view', $team);

            return $this->jsonResponse(
                data: TeamResource::make($team->loadCount('members')),
                msg: 'Team details retrieved successfully.'
            );
        });
    }

    /**
     * Update the specified team in storage.
     */
    public function update(UpdateTeamRequest $request, Team $team): JsonResponse
    {
        return $this->handleApi(function () use ($request, $team) {
            $this->authorize('update', $team);

            $dto = UpdateTeamDTO::fromRequest($request);
            $updatedTeam = $this->teamService->updateTeam($team, $dto);

            return $this->jsonResponse(
                data: TeamResource::make($updatedTeam),
                msg: 'Team updated successfully.'
            );
        });
    }

    /**
     * Remove the specified team from storage.
     */
    public function destroy(Request $request, Team $team): JsonResponse
    {
        return $this->handleApi(function () use ($team) {
            $this->authorize('delete', $team);

            $this->teamService->deleteTeam($team);

            return $this->jsonResponse(
                data: null,
                msg: 'Team deleted successfully.'
            );
        });
    }

    /**
     * List all members of a team.
     */
    public function members(Request $request, Team $team): JsonResponse
    {
        return $this->handleApi(function () use ($team) {
            $this->authorize('view', $team);

            $members = $this->teamService->getTeamMembers($team);

            return $this->jsonResponse(
                data: TeamMemberResource::collection($members),
                msg: 'Team members retrieved successfully.'
            );
        });
    }

    /**
     * Add a member to the team by email.
     */
    public function addMember(AddTeamMemberRequest $request, Team $team): JsonResponse
    {
        return $this->handleApi(function () use ($request, $team) {
            $this->authorize('manageMembers', $team);

            $dto = AddTeamMemberDTO::fromRequest($request);
            $member = $this->teamService->addMember($team, $dto, $request->user());

            return $this->jsonResponse(
                data: TeamMemberResource::make($member),
                msg: 'Member added to team successfully.',
                code: 201
            );
        });
    }

    /**
     * Update the role of a team member.
     */
    public function updateMemberRole(UpdateTeamMemberRoleRequest $request, Team $team, User $user): JsonResponse
    {
        return $this->handleApi(function () use ($request, $team, $user) {
            $this->authorize('changeRole', $team);

            $dto = UpdateTeamMemberRoleDTO::fromRequest($request);
            $this->teamService->changeMemberRole($team, $user, $dto);

            return $this->jsonResponse(
                data: null,
                msg: 'Member role updated successfully.'
            );
        });
    }

    /**
     * Remove a member from the team.
     */
    public function removeMember(Request $request, Team $team, User $user): JsonResponse
    {
        return $this->handleApi(function () use ($team, $user) {
            $this->authorize('manageMembers', $team);

            $this->teamService->removeMember($team, $user);

            return $this->jsonResponse(
                data: null,
                msg: 'Member removed from team successfully.'
            );
        });
    }
}

