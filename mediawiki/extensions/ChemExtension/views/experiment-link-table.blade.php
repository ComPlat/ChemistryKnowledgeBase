@if($htmlTableEditor->getNumberOfRows() == 1)
    <div>No experiment found</div>
@else
    <div class="experiment-link-container">
        {!! $button !!}
        {!! $htmlTableEditor->toHtml() !!}
    </div>
@endif