<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConfigurationIndexRequest;
use App\Http\Requests\DeactivateCategoryRequest;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(ConfigurationIndexRequest $request): Response
    {
        Gate::authorize('viewAny', Category::class);

        return Inertia::render('Admin/Categories', ['categories' => Category::orderBy('name')->orderBy('id')->paginate(20, ['id', 'name', 'is_active'])->withQueryString()]);
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        try {
            Category::create($request->validated());
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['name' => 'This name is already in use.']);
        }

        return redirect()->route('admin.categories.index')->with('success', 'Category created.');
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        try {
            $category->update($request->validated());
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['name' => 'This name is already in use.']);
        }

        return back()->with('success', 'Category updated.');
    }

    public function deactivate(DeactivateCategoryRequest $request, Category $category): RedirectResponse
    {
        $category->update(['is_active' => false]);

        return back()->with('success', 'Category deactivated.');
    }
}
