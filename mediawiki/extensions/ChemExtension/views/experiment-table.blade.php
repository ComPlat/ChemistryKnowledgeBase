<div>{{$description}}</div>
@if($htmlTableEditor->getNumberOfRows() == 1)
    <div>No experiment found</div>
@else
    {!! $htmlTableEditor->toHtml(!$inVisualEditor) !!}
@endif
@if(isset($experimentName))
<div>Investigation-Name: <a target="_blank" href="{{$experimentPageTitle->getFullURL()}}">{{$experimentName}}</a>{!! $exportButton !!}
<img class="experiment-help" src="{{$wgScriptPath}}/extensions/ChemExtension/skins/images/question.png" />
</div>
@endif