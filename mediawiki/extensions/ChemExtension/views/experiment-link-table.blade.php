@if($htmlTableEditor->getNumberOfRows() == 1)
    <div>No experiment found</div>
@else
<div class="experiment-link-border">
    <div class="experiment-link-control-bar"><div>{!! $button !!}</div><div>{{$description}}</div></div>
    <div class="experiment-link-container" id="ce-show-investigation-{{$buttonCounter}}-table">
      {!! $htmlTableEditor->toHtml() !!}
    </div>
</div>
@endif