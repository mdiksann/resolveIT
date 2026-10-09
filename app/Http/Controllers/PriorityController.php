<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConfigurationIndexRequest;
use App\Http\Requests\PriorityActionRequest;
use App\Http\Requests\StorePriorityRequest;
use App\Http\Requests\UpdatePriorityRequest;
use App\Models\Priority;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PriorityController extends Controller
{
    public function index(ConfigurationIndexRequest $request): Response
    {
        Gate::authorize('viewAny', Priority::class);

        return Inertia::render('Admin/Priorities', ['priorities' => Priority::orderBy('rank')->orderBy('id')->paginate(20, ['id', 'name', 'rank', 'sla_hours', 'is_default', 'is_active'])->withQueryString()]);
    }

    public function store(StorePriorityRequest $request): RedirectResponse
    {
        $this->persist(new Priority(['is_active' => true, 'is_default' => false]), $request->validated());

        return redirect()->route('admin.priorities.index')->with('success', 'Priority created.');
    }

    public function update(UpdatePriorityRequest $request, Priority $priority): RedirectResponse
    {
        $this->persist($priority, $request->validated());

        return back()->with('success', 'Priority updated.');
    }

    public function deactivate(PriorityActionRequest $request, Priority $priority): RedirectResponse
    {
        $this->persist($priority, ['is_active' => false]);

        return back()->with('success', 'Priority deactivated.');
    }

    public function setDefault(PriorityActionRequest $request, Priority $priority): RedirectResponse
    {
        $this->persist($priority, ['is_default' => true]);

        return back()->with('success', 'Default priority updated.');
    }

    private function persist(Priority $priority, array $data): void
    {
        try {
            DB::transaction(function () use ($priority, $data): void {
                // ponytail: configuration writes are rare; table locking also serializes the first insert.
                // Move to a dedicated lock row if priority write throughput ever warrants it.
                DB::statement('LOCK TABLE priorities IN SHARE ROW EXCLUSIVE MODE');
                $record = $priority->exists ? Priority::findOrFail($priority->id) : $priority;
                Gate::authorize($record->exists ? 'update' : 'create', $record->exists ? $record : Priority::class);
                $record->fill($data);
                if ($record->getOriginal('is_default') && ! $record->is_default) {
                    throw ValidationException::withMessages(['is_default' => 'Set another active priority as default first.']);
                }
                if ($record->is_default && ! $record->is_active) {
                    throw ValidationException::withMessages(['is_active' => 'The default must stay active. Set another priority as default first.']);
                }
                $record->save();
                $default = $record->is_default ? $record : (Priority::where('is_active', true)->where('is_default', true)->orderBy('id')->first()
                    ?? Priority::where('is_active', true)->orderBy('id')->first());
                if (! $default) {
                    throw ValidationException::withMessages(['is_active' => 'At least one active priority is required.']);
                }
                Priority::where('id', '!=', $default->id)->where('is_default', true)->update(['is_default' => false]);
                $default->is_default = true;
                $default->save();
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['name' => 'This name is already in use.']);
        }
    }
}
