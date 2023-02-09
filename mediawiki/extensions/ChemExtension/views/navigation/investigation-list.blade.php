<div id="ce-investigation-list">
    @if(count($list) === 0)
        none
    @else
    <ul>
        @foreach($list as $l)
            <li><a href="{{$l->getFullURL()}}">{{$l->getSubpageText()}}</a></li>
        @endforeach
    </ul>
    @endif
</div>