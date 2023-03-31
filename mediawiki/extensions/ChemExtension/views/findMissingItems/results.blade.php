<div style="margin-top: 20px;">
    @if(count($results) === 0)
        <p>No pages found</p>
    @else
    <ul>
        @foreach($results as $r)
            <li>
                @if(is_string($r['title']))
                    {{++$startIndex}}. <span>{{$r['title']}}</span>
                @else
                    {{++$startIndex}}. <a class="{{$r['title']->exists() ? '':'new'}}" href="{{$r['title']->getFullURL()}}">{{$r['title']->getText()}}</a>
                    (<span>{{implode(', ', $r['types'])}}</span>)
                @endif
            </li>
        @endforeach
    </ul>
    @endif
</div>