<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class TaskController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'status' => ['sometimes', 'in:'.implode(',', Task::STATUSES)],
            'priority' => ['sometimes', 'in:'.implode(',', Task::PRIORITIES)],
            'search' => ['sometimes', 'string', 'max:100'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $tasks = $request->user()->tasks()
            ->when($request->query('status'), fn ($q, $v) => $q->where('status', $v))
            ->when($request->query('priority'), fn ($q, $v) => $q->where('priority', $v))
            ->when($request->query('search'), fn ($q, $v) => $q->where('title', 'like', "%{$v}%"))
            ->latest('id')
            ->paginate($request->integer('per_page', 15));

        return TaskResource::collection($tasks);
    }

    public function store(StoreTaskRequest $request): TaskResource
    {
        $task = $request->user()->tasks()->create($request->validated());

        return new TaskResource($task->refresh());
    }

    public function show(Task $task): TaskResource
    {
        Gate::authorize('view', $task);

        return new TaskResource($task);
    }

    public function update(UpdateTaskRequest $request, Task $task): TaskResource
    {
        Gate::authorize('update', $task);
        $task->update($request->validated());

        return new TaskResource($task);
    }

    public function destroy(Task $task): Response
    {
        Gate::authorize('delete', $task);
        $task->delete();

        return response()->noContent();
    }
}
