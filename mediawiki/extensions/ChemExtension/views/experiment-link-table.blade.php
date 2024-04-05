@if($htmlTableEditor->getNumberOfRows() == 1)
    <div>No experiment found</div>
@else
<div class="experiment-link-border" resource="{{$cacheKey}}">
    <div class="experiment-link-control-bar"><div>{!! $button !!}{!! $refreshButton !!}
            <img title="doubleclick shows/hides column, single click sorts column" src="{{$wgScriptPath}}/extensions/ChemExtension/skins/images/question.png" /></div><div>{{$description}}</div></div>
    <div class="experiment-link-container" id="ce-show-investigation-{{$buttonCounter}}-table">
      {!! $htmlTableEditor->toHtml() !!}
    </div>
</div>
@endif