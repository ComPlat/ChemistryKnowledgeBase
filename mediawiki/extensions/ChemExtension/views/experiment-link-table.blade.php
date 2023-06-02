@if($htmlTableEditor->getNumberOfRows() == 1)
    <div>No experiment found</div>
@else
    {!! $button !!}
    <div class="experiment-link-container" id="ce-show-investigation-{{$buttonCounter}}-table">
        {!! $htmlTableEditor->toHtml() !!}
    </div>
@endif