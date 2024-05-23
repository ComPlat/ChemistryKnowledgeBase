@if($image)<span style="display: inline-grid;">
@if($toggleImage)
<span class="ce-moleculelink-show" style="cursor: pointer;">[Show image]</span>
<a title="{{$fullPageTitle}}" target="_blank" href="{{$url}}">
<span class="ce-moleculelink-image" style="display: none;"><img src="{{$imageURL}}" width="{{$width}}" height="{{$height}}"/></span>
</a>
@else
<a title="{{$fullPageTitle}}" target="_blank" href="{{$url}}">
<img src="{{$imageURL}}" width="{{$width}}" height="{{$height}}"/>
</a>
@endif
<a style="font-weight: bold; text-decoration: underline" target="_blank" href="{{$url}}" title="{{$fullPageTitle}}">{{is_null($name) ? $label : $name}}</a>
</span>
@else
<a target="_blank" href="{{$url}}" title="{{$fullPageTitle}}">{{is_null($name) ? $label : $name}}</a>
@endif