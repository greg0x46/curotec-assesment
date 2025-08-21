<?php

use App\Models\User;
use App\Models\Task;
use App\Models\Category;
use Inertia\Testing\AssertableInertia as Assert;
use Illuminate\Support\Facades\Notification;
use App\Notifications\TaskDueReminder;
use function Pest\Laravel\{
    get, post, put, delete, from,
    assertDatabaseHas, assertDatabaseMissing, assertSoftDeleted
};

/**
 * Helpers
 */
function scalarEnum(mixed $maybeEnum): mixed
{
    return $maybeEnum instanceof BackedEnum ? $maybeEnum->value : $maybeEnum;
}

function defaultTaskPayload(array $overrides = []): array
{
    $base = [
        'title'       => 'My Task',
        'description' => 'Some description',
        'priority'    => 'normal',
        'status'      => 'pending',
        'due_date'    => now()->addDay()->format('Y-m-d H:i:s'),
    ];

    $payload = array_merge($base, $overrides);

    $payload['priority'] = scalarEnum($payload['priority']);
    $payload['status']   = scalarEnum($payload['status']);

    return $payload;
}

function updateTask(Task $task, array $overrides = [], ?User $user = null)
{
    if ($user) {
        asUser($user);
    }

    $current = [
        'title'       => $task->title,
        'description' => $task->description,
        'priority'    => scalarEnum($task->priority),
        'status'      => scalarEnum($task->status),
        'due_date'    => optional($task->due_date)->format('Y-m-d H:i:s') ?? now()->addDay()->format('Y-m-d H:i:s'),
    ];

    $merged = array_merge($current, $overrides);
    $merged['priority'] = scalarEnum($merged['priority']);
    $merged['status']   = scalarEnum($merged['status']);

    return put(route('tasks.update', $task), $merged);
}

function deleteTask(Task $task, ?User $user = null)
{
    if ($user) {
        asUser($user);
    }

    return delete(route('tasks.destroy', $task));
}

beforeEach(function () {
    $this->user = asUser();
});

/**
 * Index
 */
it('lists tasks for the authenticated user (Inertia)', function () {
    $tasks = Task::factory()
        ->for($this->user, 'owner')
        ->count(3)
        ->create([
            'assigned_to_id' => $this->user->id,
            'priority'       => 'normal',
            'status'         => 'pending',
        ]);

    get(route('tasks.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) =>
        $page->component('Tasks')
            ->where('tasks.data.0.title', $tasks->first()->title)
            ->has('tasks.data', 3)
            ->has('tasks.links')
        );
});

it('redirects guests from index to login', function () {
    auth()->logout();

    get(route('tasks.index'))
        ->assertRedirectToRoute('login');
});

/**
 * Store
 */
it('creates a task tied to the authenticated user as owner', function () {
    from(route('tasks.index'))
        ->post(route('tasks.store'), defaultTaskPayload())
        ->assertRedirectToRoute('tasks.index')
        ->assertSessionHas('success', 'Task created successfully.');

    assertDatabaseHas('tasks', [
        'title'    => 'My Task',
        'owner_id' => $this->user->id,
    ]);
});

it('stores with categories and syncs pivot on store', function () {
    $c1 = Category::factory()->create();
    $c2 = Category::factory()->create();

    from(route('tasks.index'))
        ->post(route('tasks.store'), defaultTaskPayload([
            'categories' => [$c1->id, $c2->id],
        ]))
        ->assertRedirectToRoute('tasks.index')
        ->assertSessionHas('success', 'Task created successfully.');

    $task = Task::query()->where('title', 'My Task')->firstOrFail();

    assertDatabaseHas('category_task', [
        'task_id'     => $task->id,
        'category_id' => $c1->id,
    ]);
    assertDatabaseHas('category_task', [
        'task_id'     => $task->id,
        'category_id' => $c2->id,
    ]);
});

it('redirects guests to login on store', function () {
    auth()->logout();

    post(route('tasks.store'), defaultTaskPayload())
        ->assertRedirectToRoute('login');

    assertDatabaseMissing('tasks', ['title' => 'My Task']);
});

/**
 * Update (policy: owner OR assignee)
 */
it('allows the owner to update a task', function () {
    $task = Task::factory()
        ->for($this->user, 'owner')
        ->create([
            'assigned_to_id' => null,
            'title'          => 'Old Title',
            'status'         => 'pending',
            'priority'       => 'normal',
        ]);

    from(route('tasks.index'))
        ->put(route('tasks.update', $task), defaultTaskPayload(['title' => 'New Title']))
        ->assertRedirectToRoute('tasks.index')
        ->assertSessionHas('success', 'Task updated successfully.');

    assertDatabaseHas('tasks', [
        'id'    => $task->id,
        'title' => 'New Title',
    ]);
});

it('allows the assignee to update a task', function () {
    $assignee = User::factory()->create();

    $task = Task::factory()
        ->for($this->user, 'owner')
        ->create([
            'assigned_to_id' => $assignee->id,
            'title'          => 'Assignee Old',
            'status'         => 'pending',
            'priority'       => 'normal',
        ]);

    from(route('tasks.index'));
    updateTask($task, ['status' => 'done'], $assignee)
        ->assertRedirectToRoute('tasks.index')
        ->assertSessionHas('success', 'Task updated successfully.');

    assertDatabaseHas('tasks', [
        'id'     => $task->id,
        'status' => 'done',
    ]);
});

it('forbids update for users that are neither owner nor assignee', function () {
    $someoneElse = User::factory()->create();

    $task = Task::factory()
        ->for($this->user, 'owner')
        ->create([
            'assigned_to_id' => null,
            'title'          => 'Keep Title',
            'status'         => 'pending',
            'priority'       => 'normal',
        ]);

    updateTask($task, ['title' => 'Hacker Change'], $someoneElse)
        ->assertStatus(403);

    assertDatabaseHas('tasks', [
        'id'    => $task->id,
        'title' => 'Keep Title',
    ]);
});

it('syncs categories on update', function () {
    $task = Task::factory()
        ->for($this->user, 'owner')
        ->create([
            'title'    => 'With Cats',
            'priority' => 'normal',
            'status'   => 'pending',
        ]);

    $c1 = Category::factory()->create();
    $c2 = Category::factory()->create();

    from(route('tasks.index'))
        ->put(route('tasks.update', $task), defaultTaskPayload([
            'categories' => [$c1->id, $c2->id],
        ]))
        ->assertRedirectToRoute('tasks.index');

    assertDatabaseHas('category_task', ['task_id' => $task->id, 'category_id' => $c1->id]);
    assertDatabaseHas('category_task', ['task_id' => $task->id, 'category_id' => $c2->id]);
});

/**
 * Update (assignment rule): only owner can (re)assign assigned_to_id
 */
it('forbids non-owner from reassigning the task', function () {
    [$owner, $assignee, $intruder, $newAssignee] = User::factory()->count(4)->create();

    $task = Task::factory()
        ->for($owner, 'owner')
        ->create([
            'assigned_to_id' => $assignee->id,
            'title'          => 'Reassign Me',
            'status'         => 'pending',
            'priority'       => 'normal',
        ]);

    updateTask($task, ['assigned_to_id' => $newAssignee->id], $intruder)
        ->assertStatus(403);

    assertDatabaseHas('tasks', [
        'id'             => $task->id,
        'assigned_to_id' => $assignee->id,
    ]);
});

it('allows the owner to reassign the task', function () {
    [$owner, $assignee, $newAssignee] = User::factory()->count(3)->create();

    $task = Task::factory()
        ->for($owner, 'owner')
        ->create([
            'assigned_to_id' => $assignee->id,
            'title'          => 'Owner Reassign',
            'status'         => 'pending',
            'priority'       => 'normal',
        ]);

    from(route('tasks.index'));
    updateTask($task, ['assigned_to_id' => $newAssignee->id], $owner)
        ->assertRedirectToRoute('tasks.index')
        ->assertSessionHas('success', 'Task updated successfully.');

    assertDatabaseHas('tasks', [
        'id'             => $task->id,
        'assigned_to_id' => $newAssignee->id,
    ]);
});

/**
 * Destroy
 */
it('soft deletes a task owned by the authenticated user', function () {
    $task = Task::factory()
        ->for($this->user, 'owner')
        ->create([
            'assigned_to_id' => null,
            'title'          => 'Temp Task',
            'status'         => 'pending',
            'priority'       => 'normal',
        ]);

    from(route('tasks.index'))
        ->delete(route('tasks.destroy', $task))
        ->assertRedirectToRoute('tasks.index')
        ->assertSessionHas('success', 'Task deleted successfully.');

    assertSoftDeleted('tasks', ['id' => $task->id]);
});

it('forbids non-owner from deleting the task', function () {
    [$owner, $stranger] = User::factory()->count(2)->create();

    $task = Task::factory()
        ->for($owner, 'owner')
        ->create([
            'assigned_to_id' => null,
            'title'          => 'Non Owner Delete',
            'status'         => 'pending',
            'priority'       => 'normal',
        ]);

    deleteTask($task, $stranger)->assertStatus(403);

    assertDatabaseHas('tasks', [
        'id'         => $task->id,
        'deleted_at' => null,
    ]);
});

it('redirects guests to login on delete', function () {
    $task = Task::factory()
        ->for($this->user, 'owner')
        ->create([
            'assigned_to_id' => null,
            'title'          => 'Guest Block Delete',
            'status'         => 'pending',
            'priority'       => 'normal',
        ]);

    auth()->logout();

    delete(route('tasks.destroy', $task))
        ->assertRedirectToRoute('login');

    assertDatabaseHas('tasks', [
        'id'         => $task->id,
        'deleted_at' => null,
    ]);
});

it('queues a due date reminder for the assignee', function () {
    Notification::fake();

    [$owner, $assignee] = User::factory()->count(2)->create();

    $task = Task::factory()
        ->for($owner, 'owner')
        ->create([
            'assigned_to_id' => $assignee->id,
            'due_date' => now()->addDays(3),
            'status' => 'pending',
            'priority' => 'normal',
        ]);

    $expectedDelay = $task->due_date->subDay();

    Notification::assertSentTo(
        $assignee,
        TaskDueReminder::class,
        function ($notification) use ($task, $expectedDelay) {
            return $notification->task->is($task) && $notification->delay->equalTo($expectedDelay);
        }
    );
});
