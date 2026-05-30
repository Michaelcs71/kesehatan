<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

trait HasCrudViews
{
    /**
     * Get the base view path for the entity (e.g. "master-obat")
     */
    abstract protected function getViewPath(): string;

    /**
     * Get the base route name for the entity (e.g. "master-obat")
     */
    abstract protected function getRouteName(): string;

    /**
     * Return index view
     */
    protected function indexView(array $data = []): View
    {
        return view($this->getViewPath() . '.index', $data);
    }

    /**
     * Return create form view (uses form.blade.php for create + edit)
     */
    protected function createView(array $data = []): View
    {
        return view($this->getViewPath() . '.form', $data);
    }

    /**
     * Return edit form view (passes $id for AJAX load)
     */
    protected function editView(string $id, array $data = []): View
    {
        $data['id'] = $id;
        return view($this->getViewPath() . '.form', $data);
    }

    /**
     * Return show/detail view
     */
    protected function showView(string $id, array $data = []): View
    {
        $data['id'] = $id;
        return view($this->getViewPath() . '.show', $data);
    }

    /**
     * Redirect to index with error message
     */
    protected function redirectToIndexWithError(string $message = 'Data tidak ditemukan'): RedirectResponse
    {
        return redirect()->route($this->getRouteName() . '.index')->with('error', $message);
    }

    /**
     * Redirect to index with success message
     */
    protected function redirectToIndexWithSuccess(string $message): RedirectResponse
    {
        return redirect()->route($this->getRouteName() . '.index')->with('success', $message);
    }
}