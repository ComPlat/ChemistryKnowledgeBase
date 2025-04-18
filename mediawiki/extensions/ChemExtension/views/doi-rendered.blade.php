<div id="literature_{{$index}}" class="chem_ext_literature"><span>[{{$index}}] </span>
    <a target="_blank" href="{{$wgScriptPath}}/Special:Literature?doi={{$doi}}">
        <span
            class="chem-extension-literature-title">{!! $title !!}.
        </span>
    </a>
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
    <span>DOI2: <a href="https://dx.doi.org/{{$doi}}" target="_blank">{{$doi}}</a></span>
    @if(!is_null($publicationPage))
    <br/><span>Publication: <a href="{{$publicationPage->getFullURL()}}" target="_blank">{{$publicationPage->getText()}}</a></span>
    @endif
</div>
