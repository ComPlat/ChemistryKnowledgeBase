<div id="ce-publication-list" class="ce-list">
    @if(count($list) === 0)
        none
    @else
        <ul>
            @foreach($list as $l)
                <li><a href="{{$l['title']->getFullURL()}}">{{\DIQA\ChemExtension\Utils\WikiTools::findDisplayTitle($l['title'])}}</a></li>
            @endforeach
        </ul>
    @endif
</div>