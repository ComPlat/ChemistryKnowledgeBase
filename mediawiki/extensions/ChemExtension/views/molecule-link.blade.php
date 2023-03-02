@if($image)
<a title="{{$fullPageTitle}}" target="_blank" href="{{$url}}"><img src="{{$imageURL}}" width="{{$width}}" height="{{$height}}"/></a>
@else
<a target="_blank" href="{{$url}}" title="{{$fullPageTitle}}">{{$label}}</a>
@endif