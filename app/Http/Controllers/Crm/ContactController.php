<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Organization;
use App\Models\Tag;
use App\Models\User;
use App\Support\Audit;
use App\Support\CustomFields;
use App\Support\OrgAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ContactController extends Controller
{
    private function authorize(Organization $organization): void
    {
        OrgAccess::authorizeData(auth()->user(), $organization, 'org.leads');
    }

    private function ensure(Organization $organization, Contact $contact): void
    {
        abort_unless($contact->organization_id === $organization->id, 404);
    }

    public function index(Request $request, Organization $organization)
    {
        $this->authorize($organization);

        $fields = CustomFields::forEntity($organization, 'contact');
        $listFields = $fields->where('show_in_list', true);

        $query = Contact::with(['company', 'tags'])->where('organization_id', $organization->id);
        if ($search = trim((string) $request->input('q'))) {
            $query->where(function ($where) use ($search) {
                $where->where('lead_id', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('whatsapp', 'like', "%{$search}%");
            });
        }

        return view('member.crm.contacts', [
            'organization' => $organization,
            'contacts' => $query->latest('id')->paginate(25)->withQueryString(),
            'search' => $search,
            'fields' => $fields,
            'listFields' => $listFields,
            'total' => Contact::where('organization_id', $organization->id)->count(),
            'newThisMonth' => Contact::where('organization_id', $organization->id)->where('created_at', '>=', now()->startOfMonth())->count(),
        ]);
    }

    public function create(Organization $organization)
    {
        $this->authorize($organization);

        return view('member.crm.lead-form', [
            'organization' => $organization,
            'contact' => null,
            'fields' => CustomFields::forEntity($organization, 'contact'),
        ]);
    }

    public function show(Organization $organization, Contact $contact)
    {
        $this->authorize($organization);
        $this->ensure($organization, $contact);

        $history = AuditLog::where('scope', 'organization')
            ->where('organization_id', $organization->id)
            ->where('entity_type', 'contact')
            ->where('entity_id', $contact->id)
            ->latest('id')
            ->limit(50)
            ->get();

        return view('member.crm.lead-show', [
            'organization' => $organization,
            'contact' => $contact->load(['owner', 'company', 'tags']),
            'fields' => CustomFields::allForEntity($organization, 'contact'),
            'history' => $history,
            'actors' => User::whereIn('id', $history->pluck('actor_user_id')->filter()->unique())->pluck('name', 'id'),
        ]);
    }

    public function store(Request $request, Organization $organization)
    {
        $this->authorize($organization);
        $fields = CustomFields::forEntity($organization, 'contact');
        $data = $this->validated($request, $organization, $fields);
        $custom = CustomFields::collect($request, $fields);

        $contact = Contact::create([
            'organization_id' => $organization->id,
            'company_id' => $data['company_id'] ?? null,
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'whatsapp' => $data['whatsapp'] ?? null,
            'job_title' => $data['job_title'] ?? null,
            'source' => $data['source'] ?? null,
            'notes' => $data['notes'] ?? null,
            'custom' => $custom,
            'owner_user_id' => auth()->id(),
            'created_by' => auth()->id(),
        ]);
        $this->syncTags($organization, $contact, (string) $request->input('tags', ''));

        Audit::log('contact.created', 'organization', $organization->id, 'contact', $contact->id, null, [
            'lead_id' => $contact->lead_id,
            'name' => $contact->name,
        ]);

        return redirect()
            ->route('member.org.contacts.show', [$organization, $contact])
            ->with('status', 'Lead criado com o ID '.$contact->lead_id.'.');
    }

    public function edit(Organization $organization, Contact $contact)
    {
        $this->authorize($organization);
        $this->ensure($organization, $contact);

        return view('member.crm.lead-form', [
            'organization' => $organization,
            'contact' => $contact->load('tags'),
            'fields' => CustomFields::forEntity($organization, 'contact'),
        ]);
    }

    public function update(Request $request, Organization $organization, Contact $contact)
    {
        $this->authorize($organization);
        $this->ensure($organization, $contact);
        $fields = CustomFields::forEntity($organization, 'contact');
        $data = $this->validated($request, $organization, $fields);

        $custom = CustomFields::collect($request, $fields);
        $allKeys = CustomFields::allForEntity($organization, 'contact')->pluck('key');
        $existing = $contact->custom ?? [];
        foreach ($allKeys as $key) {
            if (! array_key_exists($key, $custom) && array_key_exists($key, $existing)) {
                $custom[$key] = $existing[$key];
            }
        }

        $contact->update([
            'company_id' => $data['company_id'] ?? null,
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'whatsapp' => $data['whatsapp'] ?? null,
            'job_title' => $data['job_title'] ?? null,
            'source' => $data['source'] ?? null,
            'notes' => $data['notes'] ?? null,
            'custom' => $custom,
        ]);
        $this->syncTags($organization, $contact, (string) $request->input('tags', ''));

        Audit::log('contact.updated', 'organization', $organization->id, 'contact', $contact->id, null, [
            'lead_id' => $contact->lead_id,
            'name' => $contact->name,
        ]);

        return redirect()
            ->route('member.org.contacts.show', [$organization, $contact])
            ->with('status', 'Lead atualizado.');
    }

    public function destroy(Organization $organization, Contact $contact)
    {
        $this->authorize($organization);
        $this->ensure($organization, $contact);

        $name = $contact->name;
        $contactId = $contact->id;

        $contact->delete();

        Audit::log('contact.deleted', 'organization', $organization->id, 'contact', $contactId, ['name' => $name], null);

        return redirect()->route('member.org.contacts.index', $organization)->with('status', 'Lead removido.');
    }

    private function validated(Request $request, Organization $organization, $fields): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:180'],
            'email' => ['nullable', 'email', 'max:180'],
            'phone' => ['nullable', 'string', 'max:40'],
            'whatsapp' => ['nullable', 'string', 'max:40'],
            'job_title' => ['nullable', 'string', 'max:120'],
            'source' => ['nullable', 'string', 'max:60'],
            'company_id' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];

        $data = $request->validate(array_merge($rules, CustomFields::rules($fields)));

        if (! empty($data['company_id'])) {
            $data['company_id'] = Company::where('organization_id', $organization->id)->where('id', $data['company_id'])->value('id');
        }

        return $data;
    }

    private function syncTags(Organization $organization, Contact $contact, string $input): void
    {
        $names = array_filter(array_map('trim', explode(',', $input)));
        $ids = [];
        foreach (array_slice($names, 0, 15) as $name) {
            $slug = Str::slug($name) ?: 'tag';
            $tag = Tag::firstOrCreate(
                ['organization_id' => $organization->id, 'slug' => $slug],
                ['name' => mb_substr($name, 0, 60)]
            );
            $ids[] = $tag->id;
        }
        $contact->tags()->sync($ids);
    }
}
