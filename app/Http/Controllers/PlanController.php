<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Support\Audit;
use App\Support\PlanFeatures;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PlanController extends Controller
{
    public function index()
    {
        return view('plans.index', [
            'plans' => Plan::with('features')->withCount('organizations')->orderBy('sort')->orderBy('price_cents')->get(),
            'catalog' => PlanFeatures::catalog(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'price_reais' => ['required', 'numeric', 'min:0'],
            'interval' => ['required', 'in:month,year'],
            'is_public' => ['nullable', 'boolean'],
        ]);

        $plan = Plan::create([
            'name' => $data['name'],
            'slug' => $this->uniqueSlug($data['name']),
            'description' => (string) $request->input('description', ''),
            'price_cents' => (int) round(((float) $data['price_reais']) * 100),
            'interval' => $data['interval'],
            'is_active' => true,
            'is_public' => $request->boolean('is_public'),
            'sort' => (int) $request->input('sort', 0),
        ]);

        $this->syncFeatures($plan, $request);
        Audit::log('plan.created', 'platform', null, 'plan', $plan->id, null, ['name' => $plan->name]);

        return back()->with('status', 'Plano criado.');
    }

    public function update(Request $request, Plan $plan)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'price_reais' => ['required', 'numeric', 'min:0'],
            'interval' => ['required', 'in:month,year'],
            'is_public' => ['nullable', 'boolean'],
        ]);

        $before = ['name' => $plan->name, 'price_cents' => $plan->price_cents];
        $plan->update([
            'name' => $data['name'],
            'description' => (string) $request->input('description', ''),
            'price_cents' => (int) round(((float) $data['price_reais']) * 100),
            'interval' => $data['interval'],
            'is_public' => $request->boolean('is_public'),
            'sort' => (int) $request->input('sort', 0),
        ]);

        $this->syncFeatures($plan, $request);
        Audit::log('plan.updated', 'platform', null, 'plan', $plan->id, $before, ['name' => $plan->name]);

        return back()->with('status', 'Plano atualizado.');
    }

    public function destroy(Plan $plan)
    {
        if ($plan->organizations()->exists()) {
            return back()->withErrors(['plan' => 'Há organizações usando este plano. Troque o plano delas antes de excluir.']);
        }

        $planId = $plan->id;
        $name = $plan->name;
        $plan->delete();

        Audit::log('plan.deleted', 'platform', null, 'plan', $planId, ['name' => $name], null);

        return back()->with('status', 'Plano excluído.');
    }

    private function syncFeatures(Plan $plan, Request $request): void
    {
        $enabled = (array) $request->input('enabled', []);
        $limits = (array) $request->input('limits', []);

        foreach (PlanFeatures::catalog() as $key => $meta) {
            if ($meta['type'] === 'toggle') {
                $plan->features()->updateOrCreate(['feature_key' => $key], ['enabled' => in_array($key, $enabled, true), 'limit_value' => null]);
                continue;
            }
            $value = $limits[$key] ?? null;
            $limit = ($value === '' || $value === null) ? null : max(0, (int) $value);
            $plan->features()->updateOrCreate(['feature_key' => $key], ['enabled' => true, 'limit_value' => $limit]);
        }
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'plano';
        $slug = $base;
        $suffix = 2;
        while (Plan::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $suffix++;
        }
        return $slug;
    }
}
