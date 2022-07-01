<a href="https://dx.doi.org/{{$doi}}" target="_blank">
    <div id="literature_{{$index}}" class="chem_ext_literature"><span>[{{$index}}] </span><span
                class="chem-extension-literature-title">{!! $title !!}.</span>
        @foreach($authors as $author)
            <span class="chem-extension-literature-small">{{$author}}, </span>
        @endforeach
        @if ($journal != '')
            @if($volume == '' && $pages == '')
                <span class="chem-extension-literature-small">{{$journal}} {{$year}}.</span>
            @else
                <span class="chem-extension-literature-small">{{$journal}} {{$year}}, </span>
            @endif
        @endif
        @if ($volume != '' && $pages != '')
            <span class="chem-extension-literature-small">Vol. {{$volume}}, Pages {{$pages}}.</span>
        @endif
        @if ($volume != '' && $pages == '')
            <span class="chem-extension-literature-small">Vol. {{$volume}}.</span>
        @endif
        @if ($volume == '' && $pages != '')
            <span class="chem-extension-literature-small">Pages {{$pages}}.</span>
        @endif
    </div>
</a>