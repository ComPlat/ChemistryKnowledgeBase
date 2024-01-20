@if($image)<span style="display: inline-grid;">
<a title="{{$fullPageTitle}}" target="_blank" href="{{$url}}"><img src="{{$imageURL}}" width="{{$width}}" height="{{$height}}"/></a>
<a style="font-weight: bold; text-decoration: underline" target="_blank" href="{{$url}}" title="{{$fullPageTitle}}">{{is_null($name) ? $label : $name}}</a>
</span>
@else
<a target="_blank" href="{{$url}}" title="{{$fullPageTitle}}">{{is_null($name) ? $label : $name}}</a>
@endif