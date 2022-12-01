@if($htmlTableEditor->getNumberOfRows() == 1)
    <div>No experiment found</div>
@else
    {!! $htmlTableEditor->toHtml() !!}
@endif
@if(isset($experimentName))
<div>Experiment-Name: {{$experimentName}}</div>
@endif