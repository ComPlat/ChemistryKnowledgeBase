@if($image)<div>
<a title="{{$fullPageTitle}}" target="_blank" href="{{$url}}"><img src="{{$imageURL}}" width="{{$width}}" height="{{$height}}"/></a>
<div><a style="font-weight: bold" target="_blank" href="{{$url}}" title="{{$fullPageTitle}}">{{$label}}</a></div>
</div>
@else
<a target="_blank" href="{{$url}}" title="{{$fullPageTitle}}">{{$label}}</a>
@endif