<div id="ce-investigation-list">
    @if(count($list) === 0)
        none
    @else
    <ul>
        @if($type==='topic')
        @foreach($list as $l)
            <li><a href="{{$l->getFullURL()}}">{{$l->getSubpageText()}}</a><a style="font-size: small" href="{{$l->getBaseTitle()->getFullURL()}}">({{$l->getBaseText()}})</a></li>
        @endforeach
        @else
            @foreach($list as $l)
                <li><a href="{{$l->getFullURL()}}">{{$l->getSubpageText()}}</a></li>
            @endforeach
        @endif
    </ul>
    @endif
</div>