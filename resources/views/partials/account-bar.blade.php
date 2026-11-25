<div class="account-bar" role="group" aria-label="Branch and account">
    @if((isset($availableBranches) && $availableBranches->isNotEmpty()) || isset($currentBranch))
        <div class="account-branch">
            <div>
                <span class="account-branch-label">Active Branch</span>
                <span class="account-branch-name">{{ $currentBranch->name ?? ($availableBranches->first()->name ?? 'Unknown') }}</span>
            </div>
            @if(isset($availableBranches) && $availableBranches->count() > 1)
                <form method="POST" action="{{ route('branches.switch') }}" class="account-switch">
                    @csrf
                    <select name="branch_id" aria-label="Switch branch">
                        @php($selectedBranchId = (int) (session('branch_id') ?? auth()->user()->branch_id ?? 0))
                        @foreach($availableBranches as $branchOption)
                            <option value="{{ $branchOption->id }}" @selected($selectedBranchId === (int) $branchOption->id)>{{ $branchOption->name }}</option>
                        @endforeach
                    </select>
                    <button type="submit">Switch</button>
                </form>
            @endif
        </div>
    @endif
    <form method="POST" action="{{ route('logout') }}" class="account-signout">
        @csrf
        <button type="submit">Sign Out</button>
    </form>
</div>
