@if($type != 'undefined')
<div style="float:left" class="ce-page-type ce-page-type-{{$type}}">
 @if($type !== 'other')
 <img src="{{$wgScriptPath."/extensions/ChemExtension/skins/images/" . $type . ".png"}}"/>
 @endif
 {{$text}}
</div>
@endif