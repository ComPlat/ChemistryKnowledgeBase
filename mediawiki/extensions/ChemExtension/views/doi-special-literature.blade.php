<table class="ce-literature-table">
    <tr>
        <td>ID</td>
        <td><a target="_blank" href="https://dx.doi.org/{{$doi}}">{{$doi}}</a></td>
    </tr>
    <tr>
        <td>Title</td>
        <td>{!! $title !!}</td>
    </tr>
    <tr>
        <td>Type</td>
        <td>{{$type}}</td>
    </tr>
    <tr>
        <td>Authors</td>
        <td>
            @if(count($authors) === 0)
                -
            @elseif(count($authors) === 1)
                <a href="{{$wgScriptPath}}/Special:ShowPublications?orcid={{$authors[0]['orcidUrl']}}&author={{urlencode($authors[0]['name'])}}">{{$authors[0]['name']}}</a> {{$authors[0]['afiliation']}}
                @if($authors[0][orcidUrl] != '')
                    <a href="{{$authors[0]['orcidUrl']}}">ORCID</a>
                @endif
            @else
                <ul>
                @foreach($authors as $a)
                    <li> <a href="{{$wgScriptPath}}/Special:ShowPublications?orcid={{$a['orcidUrl']}}&author={{urlencode($a['name'])}}">{{$a['name']}}</a> {{$a['afiliation']}}
                        @if($a[orcidUrl] != '')
                            <a href="{{$a['orcidUrl']}}">ORCID</a>
                        @endif
                    </li>
                @endforeach
                </ul>
            @endif

        </td>
    </tr>
    <tr>
        <td>Submission date</td>
        <td>{{$submittedAt}}</td>
    </tr>
    <tr>
        <td>Published (online)</td>
        <td>{{$publishedOnlineAt}}</td>
    </tr>
    <tr>
        <td>Published (print)</td>
        <td>{{$publishedPrintAt}}</td>
    </tr>
    <tr>
        <td>Publisher</td>
        <td>{{$publisher}}</td>
    </tr>
    <tr>
        <td>Journal</td>
        <td>{{$journal}}</td>
    </tr>
    <td>Licenses</td>
    <td>
        @if(count($licenses) === 0)
            -
        @elseif(count($licenses) === 1)
            <a href="{{$licenses[0]['URL']}}">{{$licenses[0]['URL']}}</a> from {{$licenses[0]['date']}}
        @else
            <ul>
            @foreach($licenses as $l)
                <li>
                    <a href="{{$l['URL']}}">{{$l['URL']}}</a> from {{$l['date']}}
                </li>
            @endforeach
            </ul>
        @endif

    </td>

    <tr>
        <td>Issue</td>
        <td>{{$issue}}</td>
    </tr>

    <tr>
        <td>Volume</td>
        <td>{{$volume}}</td>
    </tr>

    <tr>
        <td>Pages</td>
        <td>{{$pages}}</td>
    </tr>
    <tr>
    <td>Subjects</td>
    <td>
        @if(count($subjects) === 0)
            -
        @elseif(count($subjects) === 1)
            {{$subjects[0]}}
        @else
            <ul>
            @foreach($subjects as $s)
                <li>
                    {{$s}}
                </li>
            @endforeach
            </ul>
        @endif

    </td>
    </tr>

    <tr>
    <td>Funders</td>
    <td>
        @if(count($funders) === 1)
            {{$funders[0]}}
        @else
            <ul>
                @foreach($funders as $f)
                    <li>
                        {{$f}}
                    </li>
                @endforeach
            </ul>
        @endif

    </td>
    </tr>
</table>

<h2>Used by</h2>
@if (count($usedBy) === 0)
-none-
@else
<ul>
@foreach($usedBy as $page)
   <li><a href="{{$page->getFullURL()}}">{{$page->getPrefixedText()}}</a></li>
@endforeach
</ul>
@endif