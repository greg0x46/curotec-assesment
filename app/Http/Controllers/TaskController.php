<?php

namespace App\Http\Controllers;

use App\Http\Requests\TaskRequest;
use App\Models\Task;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TaskController extends Controller
{
    public function index(Request $request): Response
    {
        if ($request->user()->cannot('viewAny', Task::class)) {
            abort(403, 'You are not authorized to view the task list.');
        }

        $tasks = Task::query()
            ->with(['owner:id,name', 'assignee:id,name', 'categories:id,name'])
            ->select(['id', 'owner_id', 'assigned_to_id', 'title', 'priority', 'status', 'due_date'])
            ->paginate(10);

        return Inertia::render('Tasks', [
            'tasks' => $tasks,
        ]);
    }

    public function store(TaskRequest $request)
    {
        if ($request->user()->cannot('create', Task::class)) {
            abort(403, 'You are not authorized to create tasks.');
        }

        $fillable = array_merge(
            $request->validated(),
            ['owner_id' => $request->user()->id]
        );

        $task = Task::create($fillable);

        if ($request->has('categories')) {
            $task->categories()->sync($request->input('categories'));
        }

        return redirect()
            ->back()
            ->with('success', 'Task created successfully.');
    }

    public function update(TaskRequest $request, Task $task)
    {
        if ($request->user()->cannot('update', $task)) {
            abort(403, 'You are not authorized to update this task.');
        }

        // (Opcional) Regra de negócio: apenas o dono pode (re)atribuir responsável.
        if ($request->filled('assigned_to_id') && $request->input('assigned_to_id') != $task->assigned_to_id) {
            if ($request->user()->cannot('assign', $task)) {
                abort(403, 'Only the owner can (re)assign this task to another user.');
            }
        }

        $task->update($request->validated());

        if ($request->has('categories')) {
            $task->categories()->sync($request->input('categories'));
        }

        return redirect()
            ->back()
            ->with('success', 'Task updated successfully.');
    }

    public function destroy(Request $request, Task $task)
    {
        if ($request->user()->cannot('delete', $task)) {
            abort(403, 'You are not authorized to delete this task.');
        }

        $task->delete();

        return redirect()
            ->back()
            ->with('success', 'Task deleted successfully.');
    }
}
