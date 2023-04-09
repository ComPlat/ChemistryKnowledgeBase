<div id="ce-publication-list" class="ce-list">
    @if(count($list) === 0)
        none
    @else
        <ul>
            @foreach($list as $l)
                <li><a href="{{$l['title']->getFullURL()}}">{{$l['title']->getText()}}</a></li>
            @endforeach
        </ul>
    @endif
</div>