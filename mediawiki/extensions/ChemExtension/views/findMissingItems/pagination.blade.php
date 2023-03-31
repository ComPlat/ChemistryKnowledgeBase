<div style="margin-top: 20px;">
@if ($offset > 0)
(<a href="{{$wgScriptPath}}/Special:FindUnusedMolecules?limit={{$limit}}&offset={{$offset-$limit}}">prev {{$limit}}</a>)
@else
(<span>prev {{$limit}}</span>)
@endif
<a href="{{$wgScriptPath}}/Special:FindUnusedMolecules?limit={{$limit}}&offset={{$offset+$limit}}">| next {{$limit}}</a>)
(
@foreach($limits as $l)
    @if($l != $limits[0])|@endif
    @if($l == $limit)
        <span> {{$l}} </span>
    @else
    <a href="{{$wgScriptPath}}/Special:FindUnusedMolecules?limit={{$l}}&offset=0"> {{$l}} </a>
    @endif
@endforeach
)
</div>

