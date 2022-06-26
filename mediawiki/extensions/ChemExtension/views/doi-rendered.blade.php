<a href="https://dx.doi.org/{{$doi}}" target="_blank"><div id="literature_{{$index}}" class="chem_ext_literature"><span>[{{$index}}] </span><span class="chem-extension-literature-title">{{$title}}</span>
@foreach($authors as $author)
        <span class="chem-extension-literature-small">{{$author}}</span>,&nbsp;
@endforeach
@if ($journal != '')
<span class="chem-extension-literature-small">{{$journal}},</span>
@endif
@if ($volume != '')
<span class="chem-extension-literature-small">Vol. {{$volume}},</span>
@endif
@if ($pages != '')
<span class="chem-extension-literature-small">Pages {{$pages}},</span>
@endif
<span class="chem-extension-literature-small">{{$year}}</span>
</div>
</a>