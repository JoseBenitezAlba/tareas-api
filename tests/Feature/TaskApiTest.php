<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TaskApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    }

    public function test_can_create_task_with_defaults(): void
    {
        $this->postJson('/api/tasks', ['title' => 'Comprar pan'])
            ->assertCreated()
            ->assertJsonPath('data.title', 'Comprar pan')
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.priority', 'medium');

        $this->assertDatabaseHas('tasks', ['title' => 'Comprar pan', 'user_id' => $this->user->id]);
    }

    public function test_title_is_required_and_enums_validated(): void
    {
        $this->postJson('/api/tasks', ['status' => 'raro', 'priority' => 'urgente'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'status', 'priority']);
    }

    public function test_index_returns_only_own_tasks(): void
    {
        Task::factory()->count(3)->for($this->user)->create();
        Task::factory()->count(2)->create();

        $this->getJson('/api/tasks')->assertOk()->assertJsonCount(3, 'data');
    }

    public function test_index_filters_by_status_priority_and_search(): void
    {
        Task::factory()->for($this->user)->create(['title' => 'Pagar luz', 'status' => 'done', 'priority' => 'high']);
        Task::factory()->for($this->user)->create(['title' => 'Llamar a mamá', 'status' => 'pending', 'priority' => 'low']);

        $this->getJson('/api/tasks?status=done')->assertJsonCount(1, 'data');
        $this->getJson('/api/tasks?priority=low')->assertJsonCount(1, 'data')->assertJsonPath('data.0.title', 'Llamar a mamá');
        $this->getJson('/api/tasks?search=luz')->assertJsonCount(1, 'data');
        $this->getJson('/api/tasks?status=raro')->assertUnprocessable();
    }

    public function test_index_is_paginated(): void
    {
        Task::factory()->count(5)->for($this->user)->create();

        $this->getJson('/api/tasks?per_page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 5);
    }

    public function test_can_show_update_and_delete_own_task(): void
    {
        $task = Task::factory()->for($this->user)->create(['status' => 'pending']);

        $this->getJson("/api/tasks/{$task->id}")->assertOk()->assertJsonPath('data.id', $task->id);

        $this->patchJson("/api/tasks/{$task->id}", ['status' => 'done'])
            ->assertOk()->assertJsonPath('data.status', 'done');

        $this->deleteJson("/api/tasks/{$task->id}")->assertNoContent();
        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    public function test_cannot_access_other_users_task(): void
    {
        $other = Task::factory()->create();

        $this->getJson("/api/tasks/{$other->id}")->assertForbidden();
        $this->patchJson("/api/tasks/{$other->id}", ['title' => 'hack'])->assertForbidden();
        $this->deleteJson("/api/tasks/{$other->id}")->assertForbidden();
        $this->assertDatabaseHas('tasks', ['id' => $other->id, 'title' => $other->title]);
    }

    public function test_missing_task_returns_404(): void
    {
        $this->getJson('/api/tasks/9999')->assertNotFound();
    }
}
