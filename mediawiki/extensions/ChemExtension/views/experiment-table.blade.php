@if($htmlTableEditor->getNumberOfRows() == 1)
    <div>No experiment found</div>
@else
    {!! $htmlTableEditor->toHtml() !!}
@endif
<div>Experiment-Name: {{$experimentName}}</div>