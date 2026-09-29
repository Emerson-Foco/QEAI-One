<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Organization;
use App\Models\Tag;
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
        $view = $request->input('view') === 'board' ? 'board' : 'table';
        $groupKey = (string) $request->input('group', (string) optional($fields->firstWhere('type', 'select'))->key);
        $groupField = $fields->firstWhere('key', $groupKey);

        $query = Contact::with(['company', 'tags'])->where('organization_id', $organization->id);
        if ($search = $request->input('q')) {
            $query->where(function ($where) use ($search) {
                $where->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $groups = null;
        if ($view === 'board' && $groupField !== null && $groupField->type === 'select') {
            $all = $query->latest('id')->limit(300)->get();
            $groups = [];
            foreach (($groupField->options ?? []) as $option) {
                $groups[$option] = $all->filter(fn ($c) => (string) ($c->custom[$groupKey] ?? '') === (string) $option);
            }
            $groups['Sem valor'] = $all->filter(fn ($c) => empty($c->custom[$groupKey] ?? null));
            $contacts = null;
        } else {
            $contacts = $query->latest('id')->paginate(25)->withQueryString();
        }

        return view('member.crm.contacts', [
            'organization' => $organization,
            'contacts' => $contacts,
            'companies' => Company::where('organization_id', $organization->id)->orderBy('name')->get(),
            'search' => $search,
            'fields' => $fields,
            'listFields' => $listFields,
            'view' => $view,
            'groupField' => $groupField,
            'groups' => $groups,
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

        Audit::log('contact.created', 'organization', $organization->id, 'contact', $contact->id, null, ['name' => $contact->name]);

        return back()->with('status', 'Contato criado.');
    }

    public function edit(Organization $organization, Contact $contact)
    {
        $this->authorize($organization);
        $this->ensure($organization, $contact);

        return view('member.crm.contact-edit', [
            'organization' => $organization,
            'contact' => $contact->load('tags'),
            'companies' => Company::where('organization_id', $organization->id)->orderBy('name')->get(),
            'fields' => CustomFields::forEntity($organization, 'contact'),
        ]);
    }

    public function update(Request $request, Organization $organization, Contact $contact)
    {
        $this->authorize($organization);
        $this->ensure($organization, $contact);
        $fields = CustomFields::forEntity($organization, 'contact');
        $data = $this->validated($request, $organization, $fields);

        $contact->update([
            'company_id' => $data['company_id'] ?? null,
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'whatsapp' => $data['whatsapp'] ?? null,
            'job_title' => $data['job_title'] ?? null,
            'source' => $data['source'] ?? null,
            'notes' => $data['notes'] ?? null,
            'custom' => CustomFields::collect($request, $fields),
        ]);
        $this->syncTags($organization, $contact, (string) $request->input('tags', ''));

        Audit::log('contact.updated', 'organization', $organization->id, 'contact', $contact->id, null, ['name' => $contact->name]);

        return redirect()->route('member.org.contacts.index', $organization)->with('status', 'Contato atualizado.');
    }

    public function destroy(Organization $organization, Contact $contact)
    {
        $this->authorize($organization);
        $this->ensure($organization, $contact);

        $name = $contact->name;
        $contactId = $contact->id;
        $contact->delete();

        Audit::log('contact.deleted', 'organization', $organization->id, 'contact', $contactId, ['name' => $name], null);

        return back()->with('status', 'Contato removido.');
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
