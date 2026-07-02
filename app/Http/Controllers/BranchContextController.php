<?php

namespace App\Http\Controllers;

use App\Services\Branch\BranchContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BranchContextController extends Controller
{
    /**
     * Switch the active branch scope, then reload so every branch-scoped
     * query reflects the new context (mirrors the old Livewire branch-selector).
     */
    public function update(Request $request, BranchContext $context): RedirectResponse
    {
        $branchId = $request->input('branch');

        $context->setBranch($branchId === 'all' ? 'all' : (int) $branchId);

        return redirect()->back();
    }
}
