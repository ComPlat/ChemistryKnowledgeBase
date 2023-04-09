<div id="ce-investigation-list" class="ce-list">
    @if(count($list) === 0)
        none
    @else
    <ul>
        @if($type==='topic')
        @foreach($list as $l)
            <li><a title="{{count($l['type']) > 0 ? reset($l['type']):'unknown type'}}" href="{{$l['title']->getFullURL()}}">{{$l['title']->getSubpageText()}}</a>
                <a class="publication-for-investigation" href="{{$l['title']->getBaseTitle()->getFullURL()}}">({{$l['title']->getBaseText()}})</a>

            </li>
        @endforeach
        @else
            @foreach($list as $l)
                <li><a title="{{count($l['type']) > 0 ? reset($l['type']):'unknown type'}}" href="{{$l['title']->getFullURL()}}">{{$l['title']->getSubpageText()}}</a></li>
            @endforeach
        @endif
    </ul>
    @endif
</div>