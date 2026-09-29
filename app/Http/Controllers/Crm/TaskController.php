<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Organization;
use App\Models\Task;
use App\Support\Audit;
use App\Support\OrgAccess;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    private function authorize(Organization $organization): void
    {
        OrgAccess::authorizeData(auth()->user(), $organization, 'org.leads');
    }

    private function ensure(Organization $organization, Task $task): void
    {
        abort_unless($task->organization_id === $organization->id, 404);
    }

    public function index(Request $request, Organization $organization)
    {
        $this->authorize($organization);

        $status = (string) $request->input('status', '');
        $query = Task::with(['contact', 'deal'])->where('organization_id', $organization->id);
        if (in_array($status, ['open', 'done'], true)) {
            $query->where('status', $status);
        }

        return view('member.crm.tasks', [
            'organization' => $organization,
            'tasks' => $query->orderByRaw("case when status = 'open' then 0 else 1 end")
                ->orderBy('due_at')->latest('id')->paginate(30)->withQueryString(),
            'status' => $status,
            'contacts' => Contact::where('organization_id', $organization->id)->orderBy('name')->get(),
            'deals' => Deal::where('organization_id', $organization->id)->orderBy('title')->get(),
        ]);
    }

    public function store(Request $request, Organization $organization)
    {
        $this->authorize($organization);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'due_at' => ['nullable', 'date'],
            'contact_id' => ['nullable', 'integer'],
            'deal_id' => ['nullable', 'integer'],
        ]);

        $task = Task::create([
            'organization_id' => $organization->id,
            'title' => $data['title'],
            'notes' => $data['notes'] ?? null,
            'due_at' => $data['due_at'] ?? null,
            'contact_id' => $this->owned($organization, Contact::class, $data['contact_id'] ?? null),
            'deal_id' => $this->owned($organization, Deal::class, $data['deal_id'] ?? null),
            'assigned_to' => auth()->id(),
            'created_by' => auth()->id(),
        ]);

        Audit::log('task.created', 'organization', $organization->id, 'task', $task->id, null, ['title' => $task->title]);

        return back()->with('status', 'Tarefa criada.');
    }

    public function toggle(Organization $organization, Task $task)
    {
        $this->authorize($organization);
        $this->ensure($organization, $task);

        $task->update(['status' => $task->status === 'open' ? 'done' : 'open']);
        Audit::log('task.toggled', 'organization', $organization->id, 'task', $task->id, null, ['status' => $task->status]);

        return back()->with('status', 'Tarefa atualizada.');
    }

    public function destroy(Organization $organization, Task $task)
    {
        $this->authorize($organization);
        $this->ensure($organization, $task);

        $title = $task->title;
        $taskId = $task->id;
        $task->delete();

        Audit::log('task.deleted', 'organization', $organization->id, 'task', $taskId, ['title' => $title], null);

        return back()->with('status', 'Tarefa removida.');
    }

    private function owned(Organization $organization, string $class, $id): ?int
    {
        if (! $id) {
            return null;
        }

        return $class::where('organization_id', $organization->id)->where('id', $id)->value('id');
    }
}
