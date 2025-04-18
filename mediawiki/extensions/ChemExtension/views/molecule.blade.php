@if($moleculeKey == '')<span style="display: inline-grid;">
<img src="{{$placeHolderImg}}" width="{{$width}}" height="{{$height}}" style="margin: {{$margin}}"/>
</span>@else<span style="display: inline-grid;">
<a title="{{$fullPageTitle}}" target="_blank" href="{{$url}}"><img class="chemform" src="{{$imageAlreadyRendered ? $imageURL : $placeHolderImg}}" width="{{$width}}" style="margin: {{$margin}}" resource="{{base64_encode($molOrRxn)}}" height="{{$height}}"/></a>
<a style="font-weight: bold; text-decoration: underline" target="_blank" href="{{$url}}" title="{{$fullPageTitle}}">{{is_null($name) ? $label : $name}}</a>
@if($showrgroups)<a class="rgroups-button" style="font-weight: bold; text-decoration: underline" title="{{$fullPageTitle}}" moleculekey="{{$moleculeKey}}" pageid="{{$pageId}}">[Show R-Groups]</a>@endif
</span>@endif