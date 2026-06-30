<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\DestinationsLayoutStore;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DestinationsLayoutController extends Controller
{
    public function __construct(private DestinationsLayoutStore $store)
    {
    }

    public function edit(): View
    {
        return view('admin.destinations-layout.edit', [
            'layout' => $this->store->get(),
            'defaults' => $this->store->defaults(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'columns' => 'required|integer|min:2|max:6',
            'gap' => 'required|integer|min:2|max:24',
            'width' => 'required|integer|min:680|max:1280',
        ]);

        $this->store->save([
            'columns' => (int) $request->input('columns'),
            'gap' => (int) $request->input('gap'),
            'width' => (int) $request->input('width'),
        ]);

        return redirect()
            ->route('admin.destinations-layout.index')
            ->with('status', 'Destinations menu layout updated.');
    }

    /** Restore the original baseline layout (3 columns / 5px gap / 940px wide). */
    public function reset(): RedirectResponse
    {
        $this->store->save($this->store->defaults());

        return redirect()
            ->route('admin.destinations-layout.index')
            ->with('status', 'Destinations menu layout reset to defaults.');
    }
}
