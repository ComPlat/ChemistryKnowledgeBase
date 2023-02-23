<div style="margin-top: 20px;">
    @if(count($moleculeTitles) === 0)
        <p>No molecules found</p>
    @else
    <ul>
        @foreach($moleculeTitles as $title)
            <li>
                @if(is_string($title))
                    {{++$startIndex}}. <span>{{$title}}</span>
                @else
                    {{++$startIndex}}. <a class="{{$title->exists() ? '':'new'}}" href="{{$title->getFullURL()}}">Molecule:{{$title->getText()}}</a>

                @endif
            </li>
        @endforeach
    </ul>
    @endif
</div>