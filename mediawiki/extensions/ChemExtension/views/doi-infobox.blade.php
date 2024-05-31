<div>
    <table class="infobox wikitable" style="font-size:90%; margin-top:0; margin-bottom:0;width:410px; max-width:500px;">
        <tr>
            @if($doi !== '')
            <th colspan="2" style="font-size:120%; text-align:center; cursor: pointer;">About</th>
            @else
            <th colspan="2" style="font-size:120%; text-align:center; cursor: pointer;"><span style="color: red;">DOI is missing!</span></th>
            @endif
        </tr>
    </table>
    <table class="wikitable" style="font-size:90%; margin-top:0; margin-bottom:0; width:410px; max-width:500px;">
        <tr style="display: none;">
            <td>DOI</td>
            <td><a href="https://dx.doi.org/{{$doi}}">{{$doi}}</a></td>
        </tr>
        <tr style="display: none;">
            <td>Authors</td>
            <td>
                @foreach($authors as $a)
                    <a href="{{\DIQA\ChemExtension\Jobs\CreateAuthorPageJob::getAuthorPageTitle($a['name'], $a['orcidUrl'])->getFullURL()}}">{{$a['name']}}</a>,
                @endforeach
                @if(count($authors) === 0)
                    -
                @endif
            </td>
        </tr>
        <tr style="display: none;">
            <td>Submitted</td>
            <td>{{$submittedAt}}</td>
        </tr>
        @if($publishedOnlineAt !== '')
            <tr style="display: none;">
                <td>Published online</td>
                <td>{{$publishedOnlineAt}}</td>
            </tr>
        @endif
        @if($licenses !== '')
            <tr style="display: none;">
                <td>Licenses</td>
                <td>
                    @foreach($licenses as $license)
                        <a href="{{$license['URL']}}">{{$license['URL']}}</a>,
                @endforeach
                @if(count($licenses) === 0)
                    -
                @endif
            </tr>
        @endif
        @if($subjects !== '')
            <tr style="display: none;">
                <td>Subjects</td>
                <td>{{implode(", ", $subjects)}}
                    @if(count($subjects) === 0)
                        -
                    @endif
                </td>
            </tr>
        @endif
        @if ($doi !== '')
        <tr style="display: none;">
            <td colspan="2" style="font-size:80%; text-align:center; "><a
                        href="{{$wgScriptPath}}/Special:Literature?doi={{$doi}}">Go to literature page</td>
        </tr>
        @endif
    </table>
</div>