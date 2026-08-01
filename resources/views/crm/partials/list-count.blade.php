{{-- One line above every paginated CRM list: what it is showing, and how many
     rows a page holds.

     The size select sits here rather than among the filters because it is a
     property of the list, not a filter on it — `form=` still submits it with
     the filter bar it belongs to, so changing it reloads like any other choice. --}}
@php($countNoun = $noun ?? 'record')
<div class="list-count">
    <p>Showing <strong>{{ $paginator->firstItem() ?? 0 }}&ndash;{{ $paginator->lastItem() ?? 0 }}</strong> of <strong>{{ number_format($paginator->total()) }}</strong> {{ $paginator->total() === 1 ? $countNoun : \Illuminate\Support\Str::plural($countNoun) }}@if($paginator->hasPages()) · page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}@endif</p>
    <label class="list-count-size">Show
        <select name="per_page" form="{{ $filterForm }}" data-crm-filter-control aria-label="Rows per page">
            @foreach($perPageOptions as $option)<option value="{{ $option }}" @selected($perPage === $option)>{{ $option }} {{ \Illuminate\Support\Str::plural($countNoun) }}</option>@endforeach
        </select>
    </label>
</div>
